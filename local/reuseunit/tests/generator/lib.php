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
 * Data generator for local_reuseunit.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Data generator class for local_reuseunit.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_reuseunit_generator extends testing_module_generator {

    /**
     * Create a template.
     *
     * @param array $data Template data
     * @return stdClass The created template record
     */
    public function create_template(array $data = []): stdClass {
        global $DB, $USER;

        $defaults = [
            'userid' => $USER->id,
            'name' => 'Test Template',
            'description' => 'A test template',
            'tags' => 'test',
            'sharelevel' => 'personal',
            'categoryid' => null,
            'source_courseid' => 1,
            'source_sectionid' => 1,
            'activities_count' => 0,
            'resources_count' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
            'usagecount' => 0,
        ];

        $record = (object) array_merge($defaults, $data);
        $record->id = $DB->insert_record('local_reuseunit_templates', $record);

        return $record;
    }

    /**
     * Create an import history record.
     *
     * @param array $data History data
     * @return stdClass The created history record
     */
    public function create_import_history(array $data = []): stdClass {
        global $DB, $USER;

        $defaults = [
            'userid' => $USER->id,
            'source_courseid' => 1,
            'source_sectionid' => 1,
            'source_sectionname' => 'Test Section',
            'dest_courseid' => 2,
            'dest_sectionid' => null,
            'options' => '{}',
            'status' => 'completed',
            'activities_count' => 0,
            'resources_count' => 0,
            'timecreated' => time(),
            'timecompleted' => time(),
        ];

        $record = (object) array_merge($defaults, $data);
        $record->id = $DB->insert_record('local_reuseunit_history', $record);

        return $record;
    }

    /**
     * Create a favorite.
     *
     * @param array $data Favorite data
     * @return stdClass The created favorite record
     */
    public function create_favorite(array $data = []): stdClass {
        global $DB, $USER;

        $defaults = [
            'userid' => $USER->id,
            'courseid' => 1,
            'sectionid' => 1,
            'name' => null,
            'timecreated' => time(),
        ];

        $record = (object) array_merge($defaults, $data);
        $record->id = $DB->insert_record('local_reuseunit_favorites', $record);

        return $record;
    }

    /**
     * Create a section link (for synchronization).
     *
     * @param array $data Link data
     * @return stdClass The created link record
     */
    public function create_link(array $data = []): stdClass {
        global $DB, $USER;

        $defaults = [
            'courseid' => 1,
            'sectionid' => 1,
            'templateid' => 1,
            'userid' => $USER->id,
            'autosync' => 0,
            'template_version' => 1,
            'last_synced' => time(),
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $record = (object) array_merge($defaults, $data);
        $record->id = $DB->insert_record('local_reuseunit_links', $record);

        return $record;
    }

    /**
     * Create a scheduled import.
     *
     * @param array $data Scheduled import data
     * @return stdClass The created scheduled import record
     */
    public function create_scheduled(array $data = []): stdClass {
        global $DB, $USER;

        $defaults = [
            'userid' => $USER->id,
            'dest_courseid' => 1,
            'import_data' => '[]',
            'scheduled_time' => time() + 3600,
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'result_data' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $record = (object) array_merge($defaults, $data);
        $record->id = $DB->insert_record('local_reuseunit_scheduled', $record);

        return $record;
    }

    /**
     * Create a template version.
     *
     * @param array $data Version data
     * @return stdClass The created version record
     */
    public function create_version(array $data = []): stdClass {
        global $DB, $USER;

        $defaults = [
            'templateid' => 1,
            'version' => 1,
            'userid' => $USER->id,
            'changelog' => 'Initial version',
            'source_courseid' => 1,
            'source_sectionid' => 1,
            'activities_count' => 0,
            'resources_count' => 0,
            'timecreated' => time(),
        ];

        $record = (object) array_merge($defaults, $data);
        $record->id = $DB->insert_record('local_reuseunit_versions', $record);

        return $record;
    }
}
