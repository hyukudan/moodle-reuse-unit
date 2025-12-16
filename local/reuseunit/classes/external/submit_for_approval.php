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
use context_system;
use local_reuseunit\notification_helper;

/**
 * External function to submit a template for global approval.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submit_for_approval extends external_api {

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
     * Submit a template for global approval.
     *
     * @param int $templateid Template ID
     * @return array Result
     */
    public static function execute(int $templateid): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'templateid' => $templateid,
        ]);

        $context = context_system::instance();
        self::validate_context($context);

        // Get template.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $params['templateid']], '*', MUST_EXIST);

        // Only template owner can submit.
        if ($template->userid != $USER->id) {
            throw new \moodle_exception('error_nopermission', 'local_reuseunit');
        }

        // Template must be personal or category level.
        if ($template->share_level === 'global' && $template->approval_status === 'approved') {
            throw new \moodle_exception('error_alreadyapproved', 'local_reuseunit');
        }

        // Update template status to pending.
        $template->share_level = 'global';
        $template->approval_status = 'pending';
        $template->submitted_at = time();
        $template->timemodified = time();

        $DB->update_record('local_reuseunit_templates', $template);

        // Notify admins.
        $admins = get_admins();
        foreach ($admins as $admin) {
            notification_helper::notify_approval_needed($admin->id, $template, $USER->id);
        }

        return [
            'success' => true,
            'message' => get_string('submittedforapproval', 'local_reuseunit'),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether submission was successful'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
