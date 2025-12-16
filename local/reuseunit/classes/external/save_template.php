<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_reuseunit\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_course;
use context_system;
use backup_controller;
use backup;

/**
 * External function to save a section as a template.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_template extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Source course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Source section ID'),
            'name' => new external_value(PARAM_TEXT, 'Template name'),
            'description' => new external_value(PARAM_TEXT, 'Template description', VALUE_DEFAULT, ''),
            'tags' => new external_value(PARAM_TEXT, 'Comma-separated tags', VALUE_DEFAULT, ''),
            'sharelevel' => new external_value(PARAM_ALPHA, 'Share level: personal, category, global', VALUE_DEFAULT, 'personal'),
        ]);
    }

    /**
     * Save a section as a reusable template.
     *
     * @param int $courseid Source course ID
     * @param int $sectionid Source section ID
     * @param string $name Template name
     * @param string $description Template description
     * @param string $tags Comma-separated tags
     * @param string $sharelevel Share level
     * @return array Result
     */
    public static function execute(
        int $courseid,
        int $sectionid,
        string $name,
        string $description = '',
        string $tags = '',
        string $sharelevel = 'personal'
    ): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'name' => $name,
            'description' => $description,
            'tags' => $tags,
            'sharelevel' => $sharelevel,
        ]);

        // Check course exists and user has access.
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        self::validate_context($context);
        require_capability('local/reuseunit:export', $context);

        // For global templates, check manage capability.
        if ($params['sharelevel'] === 'global') {
            require_capability('local/reuseunit:managetemplates', context_system::instance());
        }

        // Get section info.
        $modinfo = get_fast_modinfo($course);
        $sectioninfo = null;
        $sectionnum = null;

        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->id == $params['sectionid']) {
                $sectioninfo = $section;
                $sectionnum = $section->section;
                break;
            }
        }

        if (!$sectioninfo) {
            throw new \moodle_exception('error_sectionnotfound', 'local_reuseunit');
        }

        // Count activities and resources.
        $activities = 0;
        $resources = 0;

        if (!empty($modinfo->sections[$sectionnum])) {
            foreach ($modinfo->sections[$sectionnum] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                $archetype = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                    $resources++;
                } else {
                    $activities++;
                }
            }
        }

        // Create the template record.
        $template = new \stdClass();
        $template->userid = $USER->id;
        $template->name = $params['name'];
        $template->description = $params['description'];
        $template->tags = $params['tags'];
        $template->sharelevel = $params['sharelevel'];
        $template->categoryid = ($params['sharelevel'] === 'category') ? $course->category : null;
        $template->source_courseid = $course->id;
        $template->source_sectionid = $sectioninfo->id;
        $template->activities_count = $activities;
        $template->resources_count = $resources;
        $template->timecreated = time();
        $template->timemodified = time();
        $template->usagecount = 0;

        // Create backup of the section for the template.
        // This creates a stored backup that can be used later.
        try {
            $bc = new backup_controller(
                backup::TYPE_1COURSE,
                $course->id,
                backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO,
                backup::MODE_GENERAL,
                $USER->id
            );

            // Configure to backup only the section.
            $plan = $bc->get_plan();
            $tasks = $plan->get_tasks();

            foreach ($tasks as $task) {
                if ($task instanceof \backup_section_task) {
                    $tasksettings = $task->get_settings();
                    foreach ($tasksettings as $setting) {
                        if ($setting->get_status() != \backup_setting::NOT_LOCKED) {
                            continue;
                        }
                        $settingname = $setting->get_name();
                        if (preg_match('/section_(\d+)_included/', $settingname, $matches)) {
                            $snum = (int)$matches[1];
                            $setting->set_value($snum == $sectionnum ? 1 : 0);
                        }
                    }
                }
            }

            // Disable user data.
            foreach ($plan->get_settings() as $setting) {
                if ($setting->get_status() != \backup_setting::NOT_LOCKED) {
                    continue;
                }
                $settingname = $setting->get_name();
                if (in_array($settingname, ['users', 'role_assignments', 'comments', 'userscompletion', 'logs', 'grade_histories'])) {
                    $setting->set_value(0);
                }
            }

            $bc->execute_plan();
            $results = $bc->get_results();
            $file = $results['backup_destination'] ?? null;

            if ($file) {
                // Store reference to the backup file.
                $template->contenthash = $file->get_contenthash();
                $template->fileitemid = $file->get_itemid();
            }

            $bc->destroy();
        } catch (\Exception $e) {
            // If backup fails, still save the template but without backup file.
            $template->contenthash = null;
            $template->fileitemid = null;
        }

        $templateid = $DB->insert_record('local_reuseunit_templates', $template);

        return [
            'success' => true,
            'templateid' => $templateid,
            'message' => get_string('templatesaved', 'local_reuseunit'),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether save was successful'),
            'templateid' => new external_value(PARAM_INT, 'Created template ID'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
