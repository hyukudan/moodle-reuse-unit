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
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_reuseunit', get_string('pluginname', 'local_reuseunit'));

    // General settings.
    $settings->add(new admin_setting_heading(
        'local_reuseunit/generalsettings',
        get_string('generalsettings', 'local_reuseunit'),
        ''
    ));

    // Enable/disable plugin.
    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/enabled',
        get_string('enabled', 'local_reuseunit'),
        get_string('enabled_desc', 'local_reuseunit'),
        1
    ));

    // Show in course menu.
    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/showincourse',
        get_string('showincourse', 'local_reuseunit'),
        get_string('showincourse_desc', 'local_reuseunit'),
        1
    ));

    // Default import options.
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

    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/includegroups',
        get_string('includegroups', 'local_reuseunit'),
        get_string('includegroups_desc', 'local_reuseunit'),
        0
    ));

    // Limits section.
    $settings->add(new admin_setting_heading(
        'local_reuseunit/limits',
        get_string('limits', 'local_reuseunit'),
        ''
    ));

    // Maximum sections to import at once.
    $settings->add(new admin_setting_configtext(
        'local_reuseunit/maxsections',
        get_string('maxsections', 'local_reuseunit'),
        get_string('maxsections_desc', 'local_reuseunit'),
        10,
        PARAM_INT
    ));

    // Maximum templates per user.
    $settings->add(new admin_setting_configtext(
        'local_reuseunit/maxtemplates',
        get_string('maxtemplates', 'local_reuseunit'),
        get_string('maxtemplates_desc', 'local_reuseunit'),
        50,
        PARAM_INT
    ));

    // History retention days.
    $settings->add(new admin_setting_configtext(
        'local_reuseunit/historyretention',
        get_string('historyretention', 'local_reuseunit'),
        get_string('historyretention_desc', 'local_reuseunit'),
        365,
        PARAM_INT
    ));

    // Template sharing settings.
    $settings->add(new admin_setting_heading(
        'local_reuseunit/templatesettings',
        get_string('templatesettings', 'local_reuseunit'),
        ''
    ));

    // Allow global templates.
    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/allowglobal',
        get_string('allowglobal', 'local_reuseunit'),
        get_string('allowglobal_desc', 'local_reuseunit'),
        1
    ));

    // Require approval for global templates.
    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/requireapproval',
        get_string('requireapproval', 'local_reuseunit'),
        get_string('requireapproval_desc', 'local_reuseunit'),
        1
    ));

    // Allow category sharing.
    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/allowcategoryshare',
        get_string('allowcategoryshare', 'local_reuseunit'),
        get_string('allowcategoryshare_desc', 'local_reuseunit'),
        1
    ));

    // Synchronization settings.
    $settings->add(new admin_setting_heading(
        'local_reuseunit/syncsettings',
        get_string('syncsettings', 'local_reuseunit'),
        ''
    ));

    // Allow auto-sync.
    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/allowautosync',
        get_string('allowautosync', 'local_reuseunit'),
        get_string('allowautosync_desc', 'local_reuseunit'),
        1
    ));

    // Default sync mode.
    $settings->add(new admin_setting_configselect(
        'local_reuseunit/defaultsyncmode',
        get_string('defaultsyncmode', 'local_reuseunit'),
        get_string('defaultsyncmode_desc', 'local_reuseunit'),
        'replace',
        [
            'replace' => get_string('syncmode_replace', 'local_reuseunit'),
            'merge' => get_string('syncmode_merge', 'local_reuseunit'),
        ]
    ));

    // Notifications settings.
    $settings->add(new admin_setting_heading(
        'local_reuseunit/notificationsettings',
        get_string('notificationsettings', 'local_reuseunit'),
        ''
    ));

    // Enable import notifications.
    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/notifyimport',
        get_string('notifyimport', 'local_reuseunit'),
        get_string('notifyimport_desc', 'local_reuseunit'),
        1
    ));

    // Enable template notifications.
    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/notifytemplate',
        get_string('notifytemplate', 'local_reuseunit'),
        get_string('notifytemplate_desc', 'local_reuseunit'),
        1
    ));

    // Cleanup settings.
    $settings->add(new admin_setting_heading(
        'local_reuseunit/cleanupsettings',
        get_string('cleanupsettings', 'local_reuseunit'),
        ''
    ));

    // Auto-cleanup old scheduled imports.
    $settings->add(new admin_setting_configcheckbox(
        'local_reuseunit/autocleanup',
        get_string('autocleanup', 'local_reuseunit'),
        get_string('autocleanup_desc', 'local_reuseunit'),
        1
    ));

    // Cleanup age in days.
    $settings->add(new admin_setting_configtext(
        'local_reuseunit/cleanupage',
        get_string('cleanupage', 'local_reuseunit'),
        get_string('cleanupage_desc', 'local_reuseunit'),
        30,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
