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
 * Block reuseunit - Quick access to Reuse Unit plugin functionality.
 *
 * @package    block_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block reuseunit class.
 *
 * @package    block_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_reuseunit extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_reuseunit');
    }

    /**
     * Get block content.
     *
     * @return stdClass Block content object
     */
    public function get_content() {
        global $CFG, $COURSE, $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Check if local_reuseunit is installed.
        if (!file_exists($CFG->dirroot . '/local/reuseunit/version.php')) {
            $this->content->text = get_string('pluginnotinstalled', 'block_reuseunit');
            return $this->content;
        }

        // Check context.
        $context = context_course::instance($COURSE->id);

        // Check capability.
        $canimport = has_capability('local/reuseunit:import', $context);
        $canexport = has_capability('local/reuseunit:export', $context);

        if (!$canimport && !$canexport) {
            return $this->content;
        }

        // Build block content.
        $html = '<div class="reuseunit-block">';

        // Quick actions.
        $html .= '<div class="reuseunit-quick-actions mb-3">';

        if ($canimport) {
            $importurl = new moodle_url('/local/reuseunit/index.php', ['courseid' => $COURSE->id]);
            $html .= html_writer::link(
                $importurl,
                $OUTPUT->pix_icon('i/import', '') . ' ' . get_string('importunit', 'block_reuseunit'),
                ['class' => 'btn btn-primary btn-block mb-2']
            );
        }

        if ($canexport) {
            $html .= html_writer::tag(
                'button',
                $OUTPUT->pix_icon('i/backup', '') . ' ' . get_string('managetemplates', 'block_reuseunit'),
                [
                    'class' => 'btn btn-outline-secondary btn-block mb-2',
                    'data-action' => 'reuseunit-manage-templates',
                    'data-courseid' => $COURSE->id,
                ]
            );
        }

        $html .= '</div>';

        // Recent imports section.
        if ($canimport) {
            $html .= $this->render_recent_imports($COURSE->id);
        }

        // Favorite templates section.
        if ($canimport) {
            $html .= $this->render_favorite_templates($COURSE->id);
        }

        $html .= '</div>';

        $this->content->text = $html;

        // Add JS.
        $this->page->requires->js_call_amd('local_reuseunit/course_integration', 'init', [
            ['courseid' => $COURSE->id, 'contextid' => $context->id]
        ]);

        return $this->content;
    }

    /**
     * Render recent imports section.
     *
     * @param int $courseid Course ID
     * @return string HTML
     */
    private function render_recent_imports($courseid) {
        global $DB, $USER;

        $html = '<div class="reuseunit-recent mb-3">';
        $html .= '<h6 class="mb-2">' . get_string('recentimports', 'block_reuseunit') . '</h6>';

        $records = $DB->get_records_sql(
            "SELECT h.*, cs.name as sectionname, c.shortname as courseshortname
             FROM {local_reuseunit_history} h
             LEFT JOIN {course_sections} cs ON cs.id = h.sourcesectionid
             LEFT JOIN {course} c ON c.id = h.sourcecourseid
             WHERE h.userid = :userid AND h.destcourseid = :destcourseid
             ORDER BY h.timecreated DESC
             LIMIT 5",
            ['userid' => $USER->id, 'destcourseid' => $courseid]
        );

        if (empty($records)) {
            $html .= '<p class="text-muted small">' . get_string('norecentimports', 'block_reuseunit') . '</p>';
        } else {
            $html .= '<ul class="list-unstyled small">';
            foreach ($records as $record) {
                $sectionname = $record->sectionname ?: get_string('unknownsection', 'block_reuseunit');
                $time = userdate($record->timecreated, get_string('strftimedatetime', 'langconfig'));
                $html .= '<li class="mb-1">';
                $html .= '<i class="fa fa-history text-muted mr-1"></i>';
                $html .= '<span title="' . $time . '">' . s($sectionname) . '</span>';
                $html .= ' <small class="text-muted">(' . s($record->courseshortname) . ')</small>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render favorite templates section.
     *
     * @param int $courseid Course ID
     * @return string HTML
     */
    private function render_favorite_templates($courseid) {
        global $DB, $USER, $OUTPUT;

        $html = '<div class="reuseunit-favorites">';
        $html .= '<h6 class="mb-2">' . get_string('favoritetemplates', 'block_reuseunit') . '</h6>';

        // Get favorites.
        $favorites = $DB->get_records_sql(
            "SELECT f.*, t.name, t.description, t.activitycount, t.resourcecount
             FROM {local_reuseunit_favorites} f
             JOIN {local_reuseunit_templates} t ON t.id = f.templateid
             WHERE f.userid = :userid
             ORDER BY f.timecreated DESC
             LIMIT 5",
            ['userid' => $USER->id]
        );

        if (empty($favorites)) {
            $html .= '<p class="text-muted small">' . get_string('nofavorites', 'block_reuseunit') . '</p>';
        } else {
            $html .= '<ul class="list-unstyled small">';
            foreach ($favorites as $fav) {
                $html .= '<li class="mb-1 d-flex justify-content-between align-items-center">';
                $html .= '<span>';
                $html .= '<i class="fa fa-star text-warning mr-1"></i>';
                $html .= s($fav->name);
                $html .= '</span>';
                $html .= '<button type="button" class="btn btn-sm btn-link p-0" ';
                $html .= 'data-action="reuseunit-quick-import" ';
                $html .= 'data-templateid="' . $fav->templateid . '" ';
                $html .= 'data-courseid="' . $courseid . '" ';
                $html .= 'title="' . get_string('quickimport', 'block_reuseunit') . '">';
                $html .= '<i class="fa fa-plus"></i>';
                $html .= '</button>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Can this block be added to the Dashboard.
     *
     * @return bool
     */
    public function applicable_formats() {
        return [
            'all' => false,
            'course-view' => true,
            'site-index' => false,
            'my' => true,
        ];
    }

    /**
     * Allow multiple instances.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Has configuration.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }
}
