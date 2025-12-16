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

/**
 * Plugin settings.
 *
 * @package    local_reuseunit
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_reuseunit', get_string('pluginname', 'local_reuseunit'));

    // Default options for import.
    $settings->add(new admin_setting_heading(
        'local_reuseunit/defaultoptions',
        get_string('defaultoptions', 'local_reuseunit'),
        get_string('defaultoptions_desc', 'local_reuseunit')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/resetdates',
        get_string('resetdates', 'local_reuseunit'),
        get_string('resetdates_desc', 'local_reuseunit'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/includerestrictions',
        get_string('includerestrictions', 'local_reuseunit'),
        get_string('includerestrictions_desc', 'local_reuseunit'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/includegradebook',
        get_string('includegradebook', 'local_reuseunit'),
        get_string('includegradebook_desc', 'local_reuseunit'),
        0
    ));

    // Maximum sections to import at once.
    $settings->add(new admin_setting_configtext(
        'local_reuseunit/maxsections',
        get_string('maxsections', 'local_reuseunit'),
        get_string('maxsections_desc', 'local_reuseunit'),
        10,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
