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
 * External functions and service definitions for local_reuseunit.
 *
 * @package    local_reuseunit
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_reuseunit_search_courses' => [
        'classname' => 'local_reuseunit\external\search_courses',
        'description' => 'Search for courses the user has access to',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_reuseunit_get_sections' => [
        'classname' => 'local_reuseunit\external\get_sections',
        'description' => 'Get sections from a course with content summary',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_reuseunit_get_section_content' => [
        'classname' => 'local_reuseunit\external\get_section_content',
        'description' => 'Get detailed content of a section for preview',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_reuseunit_import_section' => [
        'classname' => 'local_reuseunit\external\import_section',
        'description' => 'Import a section from one course to another',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_reuseunit_get_import_history' => [
        'classname' => 'local_reuseunit\external\get_import_history',
        'description' => 'Get import history for the current user',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_reuseunit_toggle_favorite' => [
        'classname' => 'local_reuseunit\external\toggle_favorite',
        'description' => 'Add or remove a section from favorites',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],

    // Template management services.
    'local_reuseunit_save_template' => [
        'classname' => 'local_reuseunit\external\save_template',
        'description' => 'Save a section as a reusable template',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_reuseunit_get_templates' => [
        'classname' => 'local_reuseunit\external\get_templates',
        'description' => 'Get available templates for the user',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_reuseunit_delete_template' => [
        'classname' => 'local_reuseunit\external\delete_template',
        'description' => 'Delete a template',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_reuseunit_import_template' => [
        'classname' => 'local_reuseunit\external\import_template',
        'description' => 'Import a template into a course',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],

    // Section management services.
    'local_reuseunit_duplicate_section' => [
        'classname' => 'local_reuseunit\external\duplicate_section',
        'description' => 'Duplicate a section within the same course',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_reuseunit_export_section' => [
        'classname' => 'local_reuseunit\external\export_section',
        'description' => 'Export a section as .mbz backup file',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_reuseunit_search_sections' => [
        'classname' => 'local_reuseunit\external\search_sections',
        'description' => 'Search for sections by name and activity types',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];

$services = [
    'Reuse Unit Service' => [
        'functions' => [
            'local_reuseunit_search_courses',
            'local_reuseunit_get_sections',
            'local_reuseunit_get_section_content',
            'local_reuseunit_import_section',
            'local_reuseunit_get_import_history',
            'local_reuseunit_toggle_favorite',
            'local_reuseunit_save_template',
            'local_reuseunit_get_templates',
            'local_reuseunit_delete_template',
            'local_reuseunit_import_template',
            'local_reuseunit_duplicate_section',
            'local_reuseunit_export_section',
            'local_reuseunit_search_sections',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_reuseunit',
    ],
];
