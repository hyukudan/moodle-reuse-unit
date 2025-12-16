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
 * External function to import a template into a course.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_template extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'templateid' => new external_value(PARAM_INT, 'Template ID to import'),
            'destcourseid' => new external_value(PARAM_INT, 'Destination course ID'),
            'position' => new external_value(PARAM_TEXT, 'Position: start or end', VALUE_DEFAULT, 'end'),
            'newsectionname' => new external_value(PARAM_TEXT, 'New section name', VALUE_DEFAULT, ''),
            'resetdates' => new external_value(PARAM_BOOL, 'Reset dates', VALUE_DEFAULT, true),
            'includerestrictions' => new external_value(PARAM_BOOL, 'Include restrictions', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Import a template into a course.
     *
     * @param int $templateid Template ID
     * @param int $destcourseid Destination course ID
     * @param string $position Position
     * @param string $newsectionname New section name
     * @param bool $resetdates Reset dates
     * @param bool $includerestrictions Include restrictions
     * @return array Result
     */
    public static function execute(
        int $templateid,
        int $destcourseid,
        string $position = 'end',
        string $newsectionname = '',
        bool $resetdates = true,
        bool $includerestrictions = false
    ): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'templateid' => $templateid,
            'destcourseid' => $destcourseid,
            'position' => $position,
            'newsectionname' => $newsectionname,
            'resetdates' => $resetdates,
            'includerestrictions' => $includerestrictions,
        ]);

        // Get template.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $params['templateid']], '*', MUST_EXIST);

        // Check destination course access.
        $destcourse = $DB->get_record('course', ['id' => $params['destcourseid']], '*', MUST_EXIST);
        $destcontext = context_course::instance($destcourse->id);
        self::validate_context($destcontext);
        require_capability('local/reuseunit:import', $destcontext);

        // Import using the source section from the template.
        // We reuse the import_section logic.
        $sourcecourse = $DB->get_record('course', ['id' => $template->source_courseid]);

        if (!$sourcecourse) {
            throw new \moodle_exception('error_coursenotfound', 'local_reuseunit');
        }

        $sourcecontext = context_course::instance($sourcecourse->id);

        // Call the import_section logic.
        $result = import_section::execute(
            $template->source_courseid,
            $template->source_sectionid,
            $destcourse->id,
            $params['position'],
            $params['newsectionname'] ?: $template->name,
            $params['resetdates'],
            $params['includerestrictions'],
            false // includegradebook
        );

        // Update template usage count.
        $DB->execute(
            "UPDATE {local_reuseunit_templates} SET usagecount = usagecount + 1 WHERE id = ?",
            [$template->id]
        );

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether import was successful'),
            'sectionid' => new external_value(PARAM_INT, 'New section ID'),
            'sectionnum' => new external_value(PARAM_INT, 'New section number'),
            'activities' => new external_value(PARAM_INT, 'Number of activities imported'),
            'resources' => new external_value(PARAM_INT, 'Number of resources imported'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'courseurl' => new external_value(PARAM_URL, 'URL to course'),
        ]);
    }
}
