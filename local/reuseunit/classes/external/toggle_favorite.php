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
 * External function to toggle favorite status of a section.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggle_favorite extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
            'name' => new external_value(PARAM_TEXT, 'Custom name for favorite', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Toggle favorite status of a section.
     *
     * @param int $courseid Course ID
     * @param int $sectionid Section ID
     * @param string $name Custom name
     * @return array Result
     */
    public static function execute(int $courseid, int $sectionid, string $name = ''): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'name' => $name,
        ]);

        $courseid = $params['courseid'];
        $sectionid = $params['sectionid'];

        // Check course exists and user has access.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('local/reuseunit:export', $context);

        // Check if favorite exists.
        $existing = $DB->get_record('local_reuseunit_favorites', [
            'userid' => $USER->id,
            'courseid' => $courseid,
            'sectionid' => $sectionid,
        ]);

        if ($existing) {
            // Remove favorite.
            $DB->delete_records('local_reuseunit_favorites', ['id' => $existing->id]);
            return [
                'isfavorite' => false,
                'message' => get_string('favoriteremoved', 'local_reuseunit'),
            ];
        } else {
            // Add favorite.
            $favorite = new \stdClass();
            $favorite->userid = $USER->id;
            $favorite->courseid = $courseid;
            $favorite->sectionid = $sectionid;
            $favorite->name = $params['name'] ?: null;
            $favorite->timecreated = time();
            $DB->insert_record('local_reuseunit_favorites', $favorite);

            return [
                'isfavorite' => true,
                'message' => get_string('favoriteadded', 'local_reuseunit'),
            ];
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'isfavorite' => new external_value(PARAM_BOOL, 'Whether the section is now a favorite'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
