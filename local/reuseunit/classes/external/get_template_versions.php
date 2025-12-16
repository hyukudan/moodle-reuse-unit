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

/**
 * External function to get template version history.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_template_versions extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'templateid' => new external_value(PARAM_INT, 'Template ID'),
        ]);
    }

    /**
     * Get version history for a template.
     *
     * @param int $templateid Template ID
     * @return array Version history
     */
    public static function execute(int $templateid): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'templateid' => $templateid,
        ]);

        // Get template.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $params['templateid']], '*', MUST_EXIST);

        // Check if user can view this template.
        $canview = false;
        if ($template->userid == $USER->id) {
            $canview = true;
        } elseif ($template->sharelevel === 'global') {
            $canview = true;
        } elseif ($template->sharelevel === 'category' && $template->categoryid) {
            // Check if user has access to this category.
            $context = \context_coursecat::instance($template->categoryid, IGNORE_MISSING);
            if ($context && has_capability('local/reuseunit:import', $context)) {
                $canview = true;
            }
        }

        if (!$canview) {
            throw new \moodle_exception('error_nopermission', 'local_reuseunit');
        }

        // Get versions.
        $versions = $DB->get_records_sql(
            "SELECT v.*, u.firstname, u.lastname
             FROM {local_reuseunit_versions} v
             JOIN {user} u ON u.id = v.userid
             WHERE v.templateid = :templateid
             ORDER BY v.version DESC",
            ['templateid' => $params['templateid']]
        );

        $result = [];
        foreach ($versions as $version) {
            $result[] = [
                'id' => $version->id,
                'version' => $version->version,
                'changelog' => $version->changelog ?? '',
                'activities' => $version->activities_count,
                'resources' => $version->resources_count,
                'authorname' => fullname($version),
                'timecreated' => $version->timecreated,
                'timecreatedformatted' => userdate($version->timecreated),
                'iscurrent' => ($version->version == $template->current_version),
            ];
        }

        return [
            'templateid' => $template->id,
            'templatename' => $template->name,
            'currentversion' => $template->current_version ?? 1,
            'versions' => $result,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'templateid' => new external_value(PARAM_INT, 'Template ID'),
            'templatename' => new external_value(PARAM_TEXT, 'Template name'),
            'currentversion' => new external_value(PARAM_INT, 'Current version number'),
            'versions' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Version ID'),
                    'version' => new external_value(PARAM_INT, 'Version number'),
                    'changelog' => new external_value(PARAM_TEXT, 'Change description'),
                    'activities' => new external_value(PARAM_INT, 'Activities count'),
                    'resources' => new external_value(PARAM_INT, 'Resources count'),
                    'authorname' => new external_value(PARAM_TEXT, 'Author name'),
                    'timecreated' => new external_value(PARAM_INT, 'Unix timestamp'),
                    'timecreatedformatted' => new external_value(PARAM_TEXT, 'Formatted date'),
                    'iscurrent' => new external_value(PARAM_BOOL, 'Is current version'),
                ])
            ),
        ]);
    }
}
