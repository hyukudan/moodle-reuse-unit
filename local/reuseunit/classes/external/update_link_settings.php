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
 * External function to update link settings including auto-sync options.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_link_settings extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'linkid' => new external_value(PARAM_INT, 'Link ID'),
            'autosync' => new external_value(PARAM_BOOL, 'Enable automatic sync', VALUE_DEFAULT, null),
            'autosync_add' => new external_value(PARAM_BOOL, 'Auto-sync: add new modules', VALUE_DEFAULT, null),
            'autosync_update' => new external_value(PARAM_BOOL, 'Auto-sync: update modified modules', VALUE_DEFAULT, null),
            'autosync_remove' => new external_value(PARAM_BOOL, 'Auto-sync: remove deleted modules', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * Update link settings.
     *
     * @param int $linkid Link ID
     * @param bool|null $autosync Enable automatic sync
     * @param bool|null $autosync_add Auto-sync: add new modules
     * @param bool|null $autosync_update Auto-sync: update modified modules
     * @param bool|null $autosync_remove Auto-sync: remove deleted modules
     * @return array Result
     */
    public static function execute(
        int $linkid,
        ?bool $autosync = null,
        ?bool $autosync_add = null,
        ?bool $autosync_update = null,
        ?bool $autosync_remove = null
    ): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'linkid' => $linkid,
            'autosync' => $autosync,
            'autosync_add' => $autosync_add,
            'autosync_update' => $autosync_update,
            'autosync_remove' => $autosync_remove,
        ]);

        // Get the link.
        $link = $DB->get_record('local_reuseunit_links', ['id' => $params['linkid']], '*', MUST_EXIST);

        // Check course access.
        $context = context_course::instance($link->courseid);
        self::validate_context($context);
        require_capability('local/reuseunit:import', $context);

        // Update fields that were provided.
        $updated = false;

        if ($params['autosync'] !== null) {
            $link->autosync = $params['autosync'] ? 1 : 0;
            $updated = true;
        }

        if ($params['autosync_add'] !== null) {
            $link->autosync_add = $params['autosync_add'] ? 1 : 0;
            $updated = true;
        }

        if ($params['autosync_update'] !== null) {
            $link->autosync_update = $params['autosync_update'] ? 1 : 0;
            $updated = true;
        }

        if ($params['autosync_remove'] !== null) {
            $link->autosync_remove = $params['autosync_remove'] ? 1 : 0;
            $updated = true;
        }

        if ($updated) {
            $link->timemodified = time();
            $DB->update_record('local_reuseunit_links', $link);
        }

        return [
            'success' => true,
            'autosync' => (bool)$link->autosync,
            'autosync_add' => (bool)($link->autosync_add ?? true),
            'autosync_update' => (bool)($link->autosync_update ?? true),
            'autosync_remove' => (bool)($link->autosync_remove ?? false),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether update was successful'),
            'autosync' => new external_value(PARAM_BOOL, 'Current autosync setting'),
            'autosync_add' => new external_value(PARAM_BOOL, 'Current autosync_add setting'),
            'autosync_update' => new external_value(PARAM_BOOL, 'Current autosync_update setting'),
            'autosync_remove' => new external_value(PARAM_BOOL, 'Current autosync_remove setting'),
        ]);
    }
}
