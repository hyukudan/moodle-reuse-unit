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

namespace local_reuseunit;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for sending notifications.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_helper {

    /**
     * Send notification when import is completed.
     *
     * @param int $userid User ID to notify
     * @param object $importdata Import details
     * @return bool Success
     */
    public static function notify_import_completed(int $userid, object $importdata): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $importdata->destcourseid]);

        $subject = get_string('notification_importcompleted_subject', 'local_reuseunit');
        $message = get_string('notification_importcompleted_message', 'local_reuseunit', [
            'sectionname' => $importdata->sectionname,
            'coursename' => $course->fullname,
            'activities' => $importdata->activities,
            'resources' => $importdata->resources,
        ]);

        $contexturl = new \moodle_url('/course/view.php', ['id' => $course->id]);

        return self::send_notification(
            $user,
            'importcompleted',
            $subject,
            $message,
            $contexturl->out(false),
            get_string('viewcourse', 'local_reuseunit')
        );
    }

    /**
     * Send notification when a template is shared.
     *
     * @param int $userid User ID to notify
     * @param object $template Template details
     * @param int $sharerid User who shared the template
     * @return bool Success
     */
    public static function notify_template_shared(int $userid, object $template, int $sharerid): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $sharer = $DB->get_record('user', ['id' => $sharerid], '*', MUST_EXIST);

        $subject = get_string('notification_templateshared_subject', 'local_reuseunit');
        $message = get_string('notification_templateshared_message', 'local_reuseunit', [
            'templatename' => $template->name,
            'sharername' => fullname($sharer),
        ]);

        $contexturl = new \moodle_url('/local/reuseunit/index.php', ['tab' => 'templates']);

        return self::send_notification(
            $user,
            'templateshared',
            $subject,
            $message,
            $contexturl->out(false),
            get_string('viewtemplates', 'local_reuseunit')
        );
    }

    /**
     * Send notification when a template needs approval.
     *
     * @param int $adminid Admin user ID to notify
     * @param object $template Template details
     * @param int $submitterid User who submitted the template
     * @return bool Success
     */
    public static function notify_approval_needed(int $adminid, object $template, int $submitterid): bool {
        global $DB;

        $admin = $DB->get_record('user', ['id' => $adminid], '*', MUST_EXIST);
        $submitter = $DB->get_record('user', ['id' => $submitterid], '*', MUST_EXIST);

        $subject = get_string('notification_approvalneeded_subject', 'local_reuseunit');
        $message = get_string('notification_approvalneeded_message', 'local_reuseunit', [
            'templatename' => $template->name,
            'submittername' => fullname($submitter),
        ]);

        $contexturl = new \moodle_url('/local/reuseunit/approve.php', ['id' => $template->id]);

        return self::send_notification(
            $admin,
            'templateapprovalneeded',
            $subject,
            $message,
            $contexturl->out(false),
            get_string('reviewtemplate', 'local_reuseunit')
        );
    }

    /**
     * Send notification when a template is approved.
     *
     * @param int $userid User ID to notify (template owner)
     * @param object $template Template details
     * @return bool Success
     */
    public static function notify_template_approved(int $userid, object $template): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        $subject = get_string('notification_templateapproved_subject', 'local_reuseunit');
        $message = get_string('notification_templateapproved_message', 'local_reuseunit', [
            'templatename' => $template->name,
        ]);

        $contexturl = new \moodle_url('/local/reuseunit/index.php', ['tab' => 'templates']);

        return self::send_notification(
            $user,
            'templateapproved',
            $subject,
            $message,
            $contexturl->out(false),
            get_string('viewtemplates', 'local_reuseunit')
        );
    }

    /**
     * Send notification when a template is rejected.
     *
     * @param int $userid User ID to notify (template owner)
     * @param object $template Template details
     * @param string $reason Rejection reason
     * @return bool Success
     */
    public static function notify_template_rejected(int $userid, object $template, string $reason = ''): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        $subject = get_string('notification_templaterejected_subject', 'local_reuseunit');
        $message = get_string('notification_templaterejected_message', 'local_reuseunit', [
            'templatename' => $template->name,
            'reason' => $reason ?: get_string('noreasonprovided', 'local_reuseunit'),
        ]);

        $contexturl = new \moodle_url('/local/reuseunit/index.php', ['tab' => 'templates']);

        return self::send_notification(
            $user,
            'templaterejected',
            $subject,
            $message,
            $contexturl->out(false),
            get_string('viewtemplates', 'local_reuseunit')
        );
    }

    /**
     * Send notification when a template is updated.
     *
     * @param int $userid User ID to notify (template user)
     * @param object $template Template details
     * @param int $newversion New version number
     * @return bool Success
     */
    public static function notify_template_updated(int $userid, object $template, int $newversion): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        $subject = get_string('notification_templateupdated_subject', 'local_reuseunit');
        $message = get_string('notification_templateupdated_message', 'local_reuseunit', [
            'templatename' => $template->name,
            'version' => $newversion,
        ]);

        $contexturl = new \moodle_url('/local/reuseunit/index.php', ['tab' => 'templates']);

        return self::send_notification(
            $user,
            'templateupdated',
            $subject,
            $message,
            $contexturl->out(false),
            get_string('viewtemplates', 'local_reuseunit')
        );
    }

    /**
     * Send notification when scheduled import is completed.
     *
     * @param int $userid User ID to notify
     * @param object $taskdata Task details
     * @return bool Success
     */
    public static function notify_scheduled_import_completed(int $userid, object $taskdata): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $taskdata->destcourseid]);

        $subject = get_string('notification_scheduledimport_subject', 'local_reuseunit');
        $message = get_string('notification_scheduledimport_message', 'local_reuseunit', [
            'coursename' => $course->fullname,
            'sectionsimported' => $taskdata->sectionsimported,
            'status' => $taskdata->status,
        ]);

        $contexturl = new \moodle_url('/course/view.php', ['id' => $course->id]);

        return self::send_notification(
            $user,
            'scheduledimportcompleted',
            $subject,
            $message,
            $contexturl->out(false),
            get_string('viewcourse', 'local_reuseunit')
        );
    }

    /**
     * Send a notification message.
     *
     * @param object $user User object to send to
     * @param string $messagename Message provider name
     * @param string $subject Message subject
     * @param string $fullmessage Full message text
     * @param string $contexturl URL for context
     * @param string $contexturlname URL link text
     * @return bool Success
     */
    protected static function send_notification(
        object $user,
        string $messagename,
        string $subject,
        string $fullmessage,
        string $contexturl,
        string $contexturlname
    ): bool {
        $message = new \core\message\message();
        $message->component = 'local_reuseunit';
        $message->name = $messagename;
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $fullmessage;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = nl2br(s($fullmessage));
        $message->smallmessage = $subject;
        $message->notification = 1;
        $message->contexturl = $contexturl;
        $message->contexturlname = $contexturlname;

        return message_send($message) !== false;
    }
}
