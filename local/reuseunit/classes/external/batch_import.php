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
use context_course;

/**
 * External function to batch import multiple sections.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class batch_import extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'destcourseid' => new external_value(PARAM_INT, 'Destination course ID'),
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'sourcecourseid' => new external_value(PARAM_INT, 'Source course ID'),
                    'sourcesectionid' => new external_value(PARAM_INT, 'Source section ID'),
                    'newsectionname' => new external_value(PARAM_TEXT, 'New section name (optional)', VALUE_DEFAULT, ''),
                ])
            ),
            'position' => new external_value(PARAM_TEXT, 'Position: start, end, after:N', VALUE_DEFAULT, 'end'),
            'resetdates' => new external_value(PARAM_BOOL, 'Reset activity dates', VALUE_DEFAULT, true),
            'includerestrictions' => new external_value(PARAM_BOOL, 'Include access restrictions', VALUE_DEFAULT, false),
            'includegradebook' => new external_value(PARAM_BOOL, 'Include gradebook structure', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Batch import multiple sections to a destination course.
     *
     * @param int $destcourseid Destination course ID
     * @param array $sections Array of sections to import
     * @param string $position Position to insert
     * @param bool $resetdates Reset dates
     * @param bool $includerestrictions Include restrictions
     * @param bool $includegradebook Include gradebook
     * @return array Result with details
     */
    public static function execute(
        int $destcourseid,
        array $sections,
        string $position = 'end',
        bool $resetdates = true,
        bool $includerestrictions = false,
        bool $includegradebook = false
    ): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'destcourseid' => $destcourseid,
            'sections' => $sections,
            'position' => $position,
            'resetdates' => $resetdates,
            'includerestrictions' => $includerestrictions,
            'includegradebook' => $includegradebook,
        ]);

        // Check destination course and capabilities.
        $destcourse = $DB->get_record('course', ['id' => $params['destcourseid']], '*', MUST_EXIST);
        $destcontext = context_course::instance($destcourse->id);
        self::validate_context($destcontext);
        require_capability('local/reuseunit:import', $destcontext);
        require_capability('moodle/course:manageactivities', $destcontext);

        // Validate sections limit.
        $maxsections = get_config('local_reuseunit', 'maxsections') ?: 10;
        if (count($params['sections']) > $maxsections) {
            throw new \moodle_exception('error_toomanyimports', 'local_reuseunit', '', $maxsections);
        }

        $results = [];
        $totalactivities = 0;
        $totalresources = 0;
        $successcount = 0;
        $failcount = 0;

        // Process each section.
        foreach ($params['sections'] as $index => $sectiondata) {
            try {
                // Call import_section for each.
                $result = import_section::execute(
                    $sectiondata['sourcecourseid'],
                    $sectiondata['sourcesectionid'],
                    $params['destcourseid'],
                    $params['position'],
                    $sectiondata['newsectionname'],
                    $params['resetdates'],
                    $params['includerestrictions'],
                    $params['includegradebook']
                );

                if ($result['success']) {
                    $successcount++;
                    $totalactivities += $result['activities'];
                    $totalresources += $result['resources'];
                    $results[] = [
                        'index' => $index,
                        'success' => true,
                        'sectionid' => $result['sectionid'],
                        'sectionnum' => $result['sectionnum'],
                        'activities' => $result['activities'],
                        'resources' => $result['resources'],
                        'message' => $result['message'],
                    ];

                    // Update position to insert after the newly created section.
                    if ($params['position'] !== 'end') {
                        $params['position'] = 'after:' . $result['sectionnum'];
                    }
                } else {
                    $failcount++;
                    $results[] = [
                        'index' => $index,
                        'success' => false,
                        'sectionid' => 0,
                        'sectionnum' => 0,
                        'activities' => 0,
                        'resources' => 0,
                        'message' => $result['message'] ?? get_string('error_backupfailed', 'local_reuseunit'),
                    ];
                }
            } catch (\Exception $e) {
                $failcount++;
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'sectionid' => 0,
                    'sectionnum' => 0,
                    'activities' => 0,
                    'resources' => 0,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Generate overall message.
        $message = get_string('batchimportcomplete', 'local_reuseunit', [
            'success' => $successcount,
            'total' => count($params['sections']),
        ]);

        return [
            'success' => $failcount == 0,
            'totalimported' => $successcount,
            'totalfailed' => $failcount,
            'totalactivities' => $totalactivities,
            'totalresources' => $totalresources,
            'message' => $message,
            'results' => $results,
            'courseurl' => (new \moodle_url('/course/view.php', ['id' => $destcourse->id]))->out(false),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether all imports were successful'),
            'totalimported' => new external_value(PARAM_INT, 'Number of sections successfully imported'),
            'totalfailed' => new external_value(PARAM_INT, 'Number of sections that failed'),
            'totalactivities' => new external_value(PARAM_INT, 'Total activities imported'),
            'totalresources' => new external_value(PARAM_INT, 'Total resources imported'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'results' => new external_multiple_structure(
                new external_single_structure([
                    'index' => new external_value(PARAM_INT, 'Original index in request'),
                    'success' => new external_value(PARAM_BOOL, 'Whether this import succeeded'),
                    'sectionid' => new external_value(PARAM_INT, 'New section ID'),
                    'sectionnum' => new external_value(PARAM_INT, 'New section number'),
                    'activities' => new external_value(PARAM_INT, 'Activities imported'),
                    'resources' => new external_value(PARAM_INT, 'Resources imported'),
                    'message' => new external_value(PARAM_TEXT, 'Result message'),
                ])
            ),
            'courseurl' => new external_value(PARAM_URL, 'URL to destination course'),
        ]);
    }
}
