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
 * External function to get usage statistics.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_statistics extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID (0 for all users)', VALUE_DEFAULT, 0),
            'period' => new external_value(PARAM_ALPHA, 'Time period: all, month, week', VALUE_DEFAULT, 'all'),
        ]);
    }

    /**
     * Get usage statistics.
     *
     * @param int $userid User ID (0 for all users)
     * @param string $period Time period filter
     * @return array Statistics data
     */
    public static function execute(int $userid = 0, string $period = 'all'): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'period' => $period,
        ]);

        $context = context_system::instance();
        self::validate_context($context);

        // Determine if user can see all stats or just their own.
        $canviewall = has_capability('local/reuseunit:managetemplates', $context);

        if ($params['userid'] == 0 && !$canviewall) {
            $params['userid'] = $USER->id;
        }

        // Build time condition.
        $timecondition = '';
        $timeparams = [];
        if ($params['period'] === 'week') {
            $timecondition = ' AND timecreated >= :starttime';
            $timeparams['starttime'] = time() - (7 * 24 * 60 * 60);
        } else if ($params['period'] === 'month') {
            $timecondition = ' AND timecreated >= :starttime';
            $timeparams['starttime'] = time() - (30 * 24 * 60 * 60);
        }

        // Build user condition.
        $usercondition = '';
        $userparams = [];
        if ($params['userid'] > 0) {
            $usercondition = ' AND userid = :userid';
            $userparams['userid'] = $params['userid'];
        }

        $allparams = array_merge($timeparams, $userparams);

        // Get import statistics.
        $totalimports = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {local_reuseunit_history} WHERE 1=1 {$usercondition} {$timecondition}",
            $allparams
        );

        $importparams = array_merge(['status' => 'success'], $allparams);
        $successfulimports = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {local_reuseunit_history} WHERE status = :status {$usercondition} {$timecondition}",
            $importparams
        );

        // Get activities and resources imported.
        $contentimported = $DB->get_record_sql(
            "SELECT COALESCE(SUM(activities_count), 0) as activities,
                    COALESCE(SUM(resources_count), 0) as resources
             FROM {local_reuseunit_history}
             WHERE status = 'success' {$usercondition} {$timecondition}",
            $allparams
        );

        // Get template statistics.
        $totaltemplateparams = $params['userid'] > 0 ? ['userid' => $params['userid']] : [];
        $templatecondition = $params['userid'] > 0 ? 'WHERE userid = :userid' : '';

        $totaltemplates = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {local_reuseunit_templates} {$templatecondition}",
            $totaltemplateparams
        );

        // Get template usage.
        $templateusage = $DB->get_field_sql(
            "SELECT COALESCE(SUM(usage_count), 0) FROM {local_reuseunit_templates} {$templatecondition}",
            $totaltemplateparams
        );

        // Get most used templates.
        $mostusedtemplates = $DB->get_records_sql(
            "SELECT id, name, usage_count, share_level
             FROM {local_reuseunit_templates}
             ORDER BY usage_count DESC
             LIMIT 5"
        );

        $toptemplatelist = [];
        foreach ($mostusedtemplates as $template) {
            $toptemplatelist[] = [
                'id' => (int) $template->id,
                'name' => $template->name,
                'usagecount' => (int) $template->usage_count,
                'sharelevel' => $template->share_level,
            ];
        }

        // Get recent imports.
        $recentimportssql = "SELECT h.id, h.source_sectionname, h.dest_courseid,
                                    h.activities_count, h.resources_count, h.timecreated,
                                    c.fullname as coursename
                             FROM {local_reuseunit_history} h
                             LEFT JOIN {course} c ON c.id = h.dest_courseid
                             WHERE h.status = 'success' {$usercondition} {$timecondition}
                             ORDER BY h.timecreated DESC
                             LIMIT 10";

        $recentimports = $DB->get_records_sql($recentimportssql, $allparams);

        $recentimportlist = [];
        foreach ($recentimports as $import) {
            $recentimportlist[] = [
                'id' => (int) $import->id,
                'sectionname' => $import->source_sectionname,
                'coursename' => $import->coursename ?? 'Unknown',
                'activities' => (int) $import->activities_count,
                'resources' => (int) $import->resources_count,
                'timecreated' => (int) $import->timecreated,
                'timeago' => self::time_ago($import->timecreated),
            ];
        }

        // Get imports by course (for charts).
        $importsbycourse = $DB->get_records_sql(
            "SELECT c.id, c.shortname, COUNT(*) as importcount
             FROM {local_reuseunit_history} h
             JOIN {course} c ON c.id = h.dest_courseid
             WHERE h.status = 'success' {$usercondition} {$timecondition}
             GROUP BY c.id, c.shortname
             ORDER BY importcount DESC
             LIMIT 10",
            $allparams
        );

        $coursedata = [];
        foreach ($importsbycourse as $course) {
            $coursedata[] = [
                'id' => (int) $course->id,
                'name' => $course->shortname,
                'count' => (int) $course->importcount,
            ];
        }

        // Get imports by activity type.
        $importsbyactivity = $DB->get_records_sql(
            "SELECT source_modname as modname, COUNT(*) as usecount
             FROM {local_reuseunit_history}
             WHERE status = 'success' AND source_modname IS NOT NULL {$usercondition} {$timecondition}
             GROUP BY source_modname
             ORDER BY usecount DESC
             LIMIT 10",
            $allparams
        );

        $activitydata = [];
        foreach ($importsbyactivity as $activity) {
            $activitydata[] = [
                'name' => $activity->modname,
                'count' => (int) $activity->usecount,
            ];
        }

        return [
            'totalimports' => (int) $totalimports,
            'successfulimports' => (int) $successfulimports,
            'activitiesimported' => (int) ($contentimported->activities ?? 0),
            'resourcesimported' => (int) ($contentimported->resources ?? 0),
            'totaltemplates' => (int) $totaltemplates,
            'templateusage' => (int) $templateusage,
            'toptemplates' => $toptemplatelist,
            'recentimports' => $recentimportlist,
            'importsbycourse' => $coursedata,
            'importsbyactivity' => $activitydata,
            'period' => $params['period'],
            'canviewall' => $canviewall,
        ];
    }

    /**
     * Convert timestamp to human-readable time ago string.
     *
     * @param int $timestamp Unix timestamp
     * @return string Time ago string
     */
    protected static function time_ago(int $timestamp): string {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return get_string('justnow', 'local_reuseunit');
        } else if ($diff < 3600) {
            $mins = floor($diff / 60);
            return get_string('minutesago', 'local_reuseunit', $mins);
        } else if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return get_string('hoursago', 'local_reuseunit', $hours);
        } else {
            $days = floor($diff / 86400);
            return get_string('daysago', 'local_reuseunit', $days);
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'totalimports' => new external_value(PARAM_INT, 'Total number of imports'),
            'successfulimports' => new external_value(PARAM_INT, 'Number of successful imports'),
            'activitiesimported' => new external_value(PARAM_INT, 'Total activities imported'),
            'resourcesimported' => new external_value(PARAM_INT, 'Total resources imported'),
            'totaltemplates' => new external_value(PARAM_INT, 'Total number of templates'),
            'templateusage' => new external_value(PARAM_INT, 'Total template usage count'),
            'toptemplates' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Template ID'),
                    'name' => new external_value(PARAM_TEXT, 'Template name'),
                    'usagecount' => new external_value(PARAM_INT, 'Usage count'),
                    'sharelevel' => new external_value(PARAM_ALPHA, 'Share level'),
                ])
            ),
            'recentimports' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Import ID'),
                    'sectionname' => new external_value(PARAM_TEXT, 'Section name'),
                    'coursename' => new external_value(PARAM_TEXT, 'Course name'),
                    'activities' => new external_value(PARAM_INT, 'Activities count'),
                    'resources' => new external_value(PARAM_INT, 'Resources count'),
                    'timecreated' => new external_value(PARAM_INT, 'Timestamp'),
                    'timeago' => new external_value(PARAM_TEXT, 'Time ago string'),
                ])
            ),
            'importsbycourse' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'name' => new external_value(PARAM_TEXT, 'Course name'),
                    'count' => new external_value(PARAM_INT, 'Import count'),
                ])
            ),
            'importsbyactivity' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_TEXT, 'Activity type'),
                    'count' => new external_value(PARAM_INT, 'Usage count'),
                ])
            ),
            'period' => new external_value(PARAM_ALPHA, 'Period filter applied'),
            'canviewall' => new external_value(PARAM_BOOL, 'Can view all users statistics'),
        ]);
    }
}
