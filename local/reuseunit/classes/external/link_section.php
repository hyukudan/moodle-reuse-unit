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

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_course;

/**
 * External function to create a link between a section and a template.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class link_section extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
            'templateid' => new external_value(PARAM_INT, 'Template ID'),
            'autosync' => new external_value(PARAM_BOOL, 'Enable auto-sync', VALUE_DEFAULT, false),
            'importedcmids' => new external_value(PARAM_TEXT, 'JSON array of imported cmids', VALUE_DEFAULT, ''),
            'partialimport' => new external_value(PARAM_BOOL, 'Whether this was a partial import', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Create a link between a section and a template.
     *
     * @param int $courseid Course ID
     * @param int $sectionid Section ID
     * @param int $templateid Template ID
     * @param bool $autosync Enable auto-sync
     * @param string $importedcmids JSON array of imported cmids
     * @param bool $partialimport Whether this was a partial import
     * @return array Result
     */
    public static function execute(
        int $courseid,
        int $sectionid,
        int $templateid,
        bool $autosync = false,
        string $importedcmids = '',
        bool $partialimport = false
    ): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'templateid' => $templateid,
            'autosync' => $autosync,
            'importedcmids' => $importedcmids,
            'partialimport' => $partialimport,
        ]);

        // Check course access.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/reuseunit:import', $context);

        // Verify section exists.
        $section = $DB->get_record('course_sections', [
            'id' => $params['sectionid'],
            'course' => $params['courseid'],
        ], '*', MUST_EXIST);

        // Verify template exists.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $params['templateid']], '*', MUST_EXIST);

        // Check if link already exists.
        $existing = $DB->get_record('local_reuseunit_links', [
            'courseid' => $params['courseid'],
            'sectionid' => $params['sectionid'],
        ]);

        if ($existing) {
            // Update existing link.
            $existing->templateid = $params['templateid'];
            $existing->autosync = $params['autosync'] ? 1 : 0;
            $existing->template_version = $template->current_version ?? 1;
            $existing->timemodified = time();
            // Update partial import data if provided.
            if (!empty($params['importedcmids'])) {
                $existing->imported_cmids = $params['importedcmids'];
                $existing->partial_import = $params['partialimport'] ? 1 : 0;
            }
            $DB->update_record('local_reuseunit_links', $existing);
            $linkid = $existing->id;
        } else {
            // Create new link.
            $link = new \stdClass();
            $link->courseid = $params['courseid'];
            $link->sectionid = $params['sectionid'];
            $link->templateid = $params['templateid'];
            $link->userid = $USER->id;
            $link->autosync = $params['autosync'] ? 1 : 0;
            $link->template_version = $template->current_version ?? 1;
            $link->last_synced = time();
            $link->timecreated = time();
            $link->timemodified = time();
            // Store partial import data.
            $link->imported_cmids = $params['importedcmids'] ?: null;
            $link->partial_import = $params['partialimport'] ? 1 : 0;
            $linkid = $DB->insert_record('local_reuseunit_links', $link);
        }

        return [
            'success' => true,
            'linkid' => $linkid,
            'message' => get_string('sectionlinked', 'local_reuseunit'),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether linking was successful'),
            'linkid' => new external_value(PARAM_INT, 'Link ID'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
