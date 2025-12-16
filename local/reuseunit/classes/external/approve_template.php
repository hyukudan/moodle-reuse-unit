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
 * External function to approve a global template.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class approve_template extends external_api {

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
     * Approve a global template.
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

        // Require template management capability.
        require_capability('local/reuseunit:managetemplates', $context);

        // Get template.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $params['templateid']], '*', MUST_EXIST);

        // Template must be pending.
        if ($template->approval_status !== 'pending') {
            throw new \moodle_exception('error_notpending', 'local_reuseunit');
        }

        // Update template status to approved.
        $template->approval_status = 'approved';
        $template->approved_by = $USER->id;
        $template->approved_at = time();
        $template->timemodified = time();

        $DB->update_record('local_reuseunit_templates', $template);

        // Notify template owner.
        notification_helper::notify_template_approved($template->userid, $template);

        return [
            'success' => true,
            'message' => get_string('templateapprovedmsg', 'local_reuseunit'),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether approval was successful'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
