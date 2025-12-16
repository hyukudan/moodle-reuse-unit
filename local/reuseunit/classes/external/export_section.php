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
 * External function to export a section or template as .mbz file.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_section extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID to export'),
            'filename' => new external_value(PARAM_TEXT, 'Filename for export', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Export a section as .mbz file.
     *
     * @param int $courseid Course ID
     * @param int $sectionid Section ID
     * @param string $filename Desired filename
     * @return array Result with download URL
     */
    public static function execute(int $courseid, int $sectionid, string $filename = ''): array {
        global $DB, $USER, $CFG;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'filename' => $filename,
        ]);

        // Check course and capabilities.
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        self::validate_context($context);
        require_capability('local/reuseunit:export', $context);
        require_capability('moodle/backup:backupcourse', $context);

        // Get section info.
        $section = $DB->get_record('course_sections', ['id' => $params['sectionid'], 'course' => $course->id], '*', MUST_EXIST);
        $sectionname = get_section_name($course, $section);

        // Generate filename if not provided.
        if (empty($params['filename'])) {
            $safename = clean_filename($sectionname);
            $params['filename'] = $safename . '-' . date('Ymd-His');
        }

        // Create backup controller for section only.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );

        // Configure to backup only this section.
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
                        $setting->set_value($snum == $section->section ? 1 : 0);
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
            // Set the filename.
            if ($settingname === 'filename') {
                $setting->set_value($params['filename']);
            }
        }

        // Execute backup.
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'] ?? null;
        $bc->destroy();

        if (!$file) {
            throw new \moodle_exception('error_backupfailed', 'local_reuseunit');
        }

        // Generate download URL.
        $downloadurl = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            true
        );

        return [
            'success' => true,
            'filename' => $file->get_filename(),
            'filesize' => $file->get_filesize(),
            'downloadurl' => $downloadurl->out(false),
            'message' => get_string('exportsuccessful', 'local_reuseunit'),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether export was successful'),
            'filename' => new external_value(PARAM_TEXT, 'Generated filename'),
            'filesize' => new external_value(PARAM_INT, 'File size in bytes'),
            'downloadurl' => new external_value(PARAM_URL, 'Download URL'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
