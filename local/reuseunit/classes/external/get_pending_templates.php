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
use external_multiple_structure;
use external_value;
use context_system;

/**
 * External function to get templates pending approval.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_pending_templates extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Get templates pending approval.
     *
     * @return array Templates data
     */
    public static function execute(): array {
        global $DB;

        $context = context_system::instance();
        self::validate_context($context);

        // Require template management capability.
        require_capability('local/reuseunit:managetemplates', $context);

        // Get pending templates.
        $sql = "SELECT t.*, u.firstname, u.lastname, u.email,
                       c.fullname as coursename, cs.name as sectionname
                FROM {local_reuseunit_templates} t
                JOIN {user} u ON u.id = t.userid
                LEFT JOIN {course} c ON c.id = t.source_courseid
                LEFT JOIN {course_sections} cs ON cs.id = t.source_sectionid
                WHERE t.approval_status = 'pending'
                ORDER BY t.submitted_at ASC";

        $templates = $DB->get_records_sql($sql);

        $result = [];
        foreach ($templates as $template) {
            $result[] = [
                'id' => (int) $template->id,
                'name' => $template->name,
                'description' => $template->description ?? '',
                'tags' => $template->tags ?? '',
                'submittername' => fullname($template),
                'submitteremail' => $template->email,
                'coursename' => $template->coursename ?? 'Unknown',
                'sectionname' => $template->sectionname ?? 'Unknown',
                'activitiescount' => (int) ($template->activities_count ?? 0),
                'resourcescount' => (int) ($template->resources_count ?? 0),
                'submittedat' => (int) ($template->submitted_at ?? $template->timecreated),
                'submittedatformatted' => userdate($template->submitted_at ?? $template->timecreated),
            ];
        }

        return ['templates' => $result, 'count' => count($result)];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'templates' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Template ID'),
                    'name' => new external_value(PARAM_TEXT, 'Template name'),
                    'description' => new external_value(PARAM_TEXT, 'Template description'),
                    'tags' => new external_value(PARAM_TEXT, 'Template tags'),
                    'submittername' => new external_value(PARAM_TEXT, 'Submitter name'),
                    'submitteremail' => new external_value(PARAM_TEXT, 'Submitter email'),
                    'coursename' => new external_value(PARAM_TEXT, 'Source course name'),
                    'sectionname' => new external_value(PARAM_TEXT, 'Source section name'),
                    'activitiescount' => new external_value(PARAM_INT, 'Activities count'),
                    'resourcescount' => new external_value(PARAM_INT, 'Resources count'),
                    'submittedat' => new external_value(PARAM_INT, 'Submission timestamp'),
                    'submittedatformatted' => new external_value(PARAM_TEXT, 'Formatted submission date'),
                ])
            ),
            'count' => new external_value(PARAM_INT, 'Total count'),
        ]);
    }
}
