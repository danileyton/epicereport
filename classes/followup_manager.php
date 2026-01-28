<?php
/**
 * Followup Manager class for local_epicereports
 *
 * Handles CRUD operations for follow-up message schedules
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports;

defined('MOODLE_INTERNAL') || die();

/**
 * Class followup_manager
 *
 * Manages follow-up message schedules and sending
 */
class followup_manager {

    /** @var string Table name for followup schedules */
    const TABLE_FOLLOWUP = 'local_epicereports_followup';

    /** @var string Table name for followup logs */
    const TABLE_LOGS = 'local_epicereports_followup_logs';

    // =========================================================================
    // FOLLOWUP SCHEDULE MANAGEMENT
    // =========================================================================

    /**
     * Get all followup schedules for a course.
     *
     * @param int $courseid The course ID.
     * @return array Array of followup schedule objects.
     */
    public static function get_followups_by_course(int $courseid): array {
        global $DB;

        return $DB->get_records(self::TABLE_FOLLOWUP, ['courseid' => $courseid], 'name ASC');
    }

    /**
     * Get a single followup schedule by ID.
     *
     * @param int $followupid The followup ID.
     * @return object|false The followup object or false if not found.
     */
    public static function get_followup(int $followupid) {
        global $DB;

        return $DB->get_record(self::TABLE_FOLLOWUP, ['id' => $followupid]);
    }

    /**
     * Create a new followup schedule.
     *
     * @param object $data The followup data.
     * @return int The new followup ID.
     */
    public static function create_followup(object $data): int {
        global $DB, $USER;

        $now = time();

        $record = new \stdClass();
        $record->courseid = $data->courseid;
        $record->name = $data->name;
        $record->enabled = isset($data->enabled) ? (int)$data->enabled : 1;
        $record->startdate = $data->startdate;
        $record->enddate = !empty($data->enddate) ? $data->enddate : null;
        $record->sendtime = $data->sendtime ?? '09:00';
        $record->monday = !empty($data->monday) ? 1 : 0;
        $record->tuesday = !empty($data->tuesday) ? 1 : 0;
        $record->wednesday = !empty($data->wednesday) ? 1 : 0;
        $record->thursday = !empty($data->thursday) ? 1 : 0;
        $record->friday = !empty($data->friday) ? 1 : 0;
        $record->saturday = !empty($data->saturday) ? 1 : 0;
        $record->sunday = !empty($data->sunday) ? 1 : 0;
        $record->specific_dates = $data->specific_dates ?? null;
        $record->target_status = $data->target_status ?? 'all_incomplete';
        $record->send_email = !empty($data->send_email) ? 1 : 0;
        $record->send_message = !empty($data->send_message) ? 1 : 0;
        $record->message_subject = $data->message_subject ?? null;
        $record->message_body = $data->message_body ?? null;
        $record->message_bodyformat = $data->message_bodyformat ?? FORMAT_HTML;
        $record->max_per_user = $data->max_per_user ?? 'daily';
        $record->lastrun = null;
        $record->nextrun = self::calculate_next_run($record);
        $record->createdby = $USER->id;
        $record->timecreated = $now;
        $record->timemodified = $now;

        return $DB->insert_record(self::TABLE_FOLLOWUP, $record);
    }

    /**
     * Update an existing followup schedule.
     *
     * @param object $data The followup data with id.
     * @return bool True on success.
     */
    public static function update_followup(object $data): bool {
        global $DB;

        $record = self::get_followup($data->id);
        if (!$record) {
            return false;
        }

        // Update fields
        $record->name = $data->name ?? $record->name;
        $record->enabled = isset($data->enabled) ? (int)$data->enabled : $record->enabled;
        $record->startdate = $data->startdate ?? $record->startdate;
        $record->enddate = isset($data->enddate) ? ($data->enddate ?: null) : $record->enddate;
        $record->sendtime = $data->sendtime ?? $record->sendtime;
        $record->monday = isset($data->monday) ? (!empty($data->monday) ? 1 : 0) : $record->monday;
        $record->tuesday = isset($data->tuesday) ? (!empty($data->tuesday) ? 1 : 0) : $record->tuesday;
        $record->wednesday = isset($data->wednesday) ? (!empty($data->wednesday) ? 1 : 0) : $record->wednesday;
        $record->thursday = isset($data->thursday) ? (!empty($data->thursday) ? 1 : 0) : $record->thursday;
        $record->friday = isset($data->friday) ? (!empty($data->friday) ? 1 : 0) : $record->friday;
        $record->saturday = isset($data->saturday) ? (!empty($data->saturday) ? 1 : 0) : $record->saturday;
        $record->sunday = isset($data->sunday) ? (!empty($data->sunday) ? 1 : 0) : $record->sunday;
        $record->specific_dates = $data->specific_dates ?? $record->specific_dates;
        $record->target_status = $data->target_status ?? $record->target_status;
        $record->send_email = isset($data->send_email) ? (!empty($data->send_email) ? 1 : 0) : $record->send_email;
        $record->send_message = isset($data->send_message) ? (!empty($data->send_message) ? 1 : 0) : $record->send_message;
        $record->message_subject = $data->message_subject ?? $record->message_subject;
        $record->message_body = $data->message_body ?? $record->message_body;
        $record->message_bodyformat = $data->message_bodyformat ?? $record->message_bodyformat;
        $record->max_per_user = $data->max_per_user ?? $record->max_per_user;
        $record->nextrun = self::calculate_next_run($record);
        $record->timemodified = time();

        return $DB->update_record(self::TABLE_FOLLOWUP, $record);
    }

    /**
     * Delete a followup schedule.
     *
     * @param int $followupid The followup ID.
     * @return bool True on success.
     */
    public static function delete_followup(int $followupid): bool {
        global $DB;

        // Logs are kept for audit purposes
        return $DB->delete_records(self::TABLE_FOLLOWUP, ['id' => $followupid]);
    }

    /**
     * Toggle followup enabled status.
     *
     * @param int $followupid The followup ID.
     * @return bool New enabled status.
     */
    public static function toggle_followup(int $followupid): bool {
        global $DB;

        $followup = self::get_followup($followupid);
        if (!$followup) {
            return false;
        }

        $newstatus = $followup->enabled ? 0 : 1;

        $DB->set_field(self::TABLE_FOLLOWUP, 'enabled', $newstatus, ['id' => $followupid]);
        $DB->set_field(self::TABLE_FOLLOWUP, 'timemodified', time(), ['id' => $followupid]);

        if ($newstatus) {
            $followup->enabled = $newstatus;
            $nextrun = self::calculate_next_run($followup);
            $DB->set_field(self::TABLE_FOLLOWUP, 'nextrun', $nextrun, ['id' => $followupid]);
        }

        return (bool)$newstatus;
    }

    /**
     * Calculate the next run timestamp for a followup schedule.
     *
     * @param object $followup The followup object.
     * @return int|null The next run timestamp or null if no valid next run.
     */
    public static function calculate_next_run(object $followup): ?int {
        if (empty($followup->enabled)) {
            return null;
        }

        // Parse send time.
        $timeparts = explode(':', $followup->sendtime);
        $sendhour = (int)($timeparts[0] ?? 9);
        $sendminute = (int)($timeparts[1] ?? 0);

        // Start from today or tomorrow depending on current time.
        $now = time();
        $today = strtotime('today midnight');
        $todaysendtime = $today + ($sendhour * 3600) + ($sendminute * 60);

        // If today's send time hasn't passed yet and today is a valid day, use today.
        // Otherwise start checking from tomorrow.
        if ($now < $todaysendtime) {
            $checkdate = $today;
        } else {
            $checkdate = strtotime('+1 day', $today);
        }

        // Day mapping.
        $daymap = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            0 => 'sunday',
        ];

        // Check the next 14 days for a valid send day.
        for ($i = 0; $i < 14; $i++) {
            $dayofweek = (int)date('w', $checkdate);
            $dayfield = $daymap[$dayofweek];

            if (!empty($followup->$dayfield)) {
                // Check if within date range.
                if ($checkdate >= $followup->startdate) {
                    if (empty($followup->enddate) || $checkdate <= $followup->enddate) {
                        return $checkdate + ($sendhour * 3600) + ($sendminute * 60);
                    }
                }
            }

            $checkdate = strtotime('+1 day', $checkdate);
        }

        return null;
    }

    // =========================================================================
    // TARGET STUDENTS
    // =========================================================================

    /**
     * Get students who match the followup criteria.
     *
     * @param int $courseid The course ID.
     * @param string $targetStatus Target status filter.
     * @return array Array of user objects.
     */
    public static function get_target_students(int $courseid, string $targetStatus = 'all_incomplete'): array {
        global $DB;

        $context = \context_course::instance($courseid);

        // Get all enrolled students.
        $enrolledusers = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);

        if (empty($enrolledusers)) {
            return [];
        }

        $course = get_course($courseid);
        $completion = new \completion_info($course);

        if (!$completion->is_enabled()) {
            // If completion is not enabled, return all enrolled users for 'all_incomplete'.
            if ($targetStatus === 'all_incomplete') {
                return $enrolledusers;
            }
            return [];
        }

        $targetstudents = [];

        foreach ($enrolledusers as $user) {
            // Get progress.
            $progress = \core_completion\progress::get_course_progress_percentage($course, $user->id);
            $user->progress = $progress ?? 0;

            // Check if completed.
            $iscompleted = $completion->is_course_complete($user->id);

            if ($iscompleted) {
                // Skip completed students.
                continue;
            }

            // Filter by target status.
            switch ($targetStatus) {
                case 'not_started':
                    // Progress is 0 or null.
                    if ($user->progress == 0) {
                        $targetstudents[] = $user;
                    }
                    break;

                case 'in_progress':
                    // Progress is > 0 but not complete.
                    if ($user->progress > 0) {
                        $targetstudents[] = $user;
                    }
                    break;

                case 'all_incomplete':
                default:
                    // Any non-completed student.
                    $targetstudents[] = $user;
                    break;
            }
        }

        return $targetstudents;
    }

    /**
     * Check if a user has exceeded the message limit.
     *
     * @param int $followupid The followup ID.
     * @param int $userid The user ID.
     * @param string $maxPerUser Limit type (daily/weekly).
     * @return bool True if limit reached.
     */
    public static function has_user_reached_limit(int $followupid, int $userid, string $maxPerUser): bool {
        global $DB;

        $now = time();

        if ($maxPerUser === 'daily') {
            $since = strtotime('today midnight');
        } else {
            // weekly
            $since = strtotime('-7 days');
        }

        $sql = "SELECT COUNT(*)
                  FROM {" . self::TABLE_LOGS . "}
                 WHERE followupid = :followupid
                   AND userid = :userid
                   AND status = 'sent'
                   AND timesent >= :since";

        $count = $DB->count_records_sql($sql, [
            'followupid' => $followupid,
            'userid' => $userid,
            'since' => $since,
        ]);

        return $count > 0;
    }

    // =========================================================================
    // LOGS MANAGEMENT
    // =========================================================================

    /**
     * Create a log entry.
     *
     * @param int $followupid The followup ID.
     * @param int $courseid The course ID.
     * @param int $userid The user ID.
     * @param string $email User email.
     * @param string $channel Channel used (email/message/both).
     * @param int $timescheduled Scheduled time.
     * @param string $status Status.
     * @return int The log ID.
     */
    public static function create_log(
        int $followupid,
        int $courseid,
        int $userid,
        string $email,
        string $channel,
        int $timescheduled,
        string $status = 'pending'
    ): int {
        global $DB;

        $record = new \stdClass();
        $record->followupid = $followupid;
        $record->courseid = $courseid;
        $record->userid = $userid;
        $record->recipientemail = $email;
        $record->channel = $channel;
        $record->status = $status;
        $record->timescheduled = $timescheduled;
        $record->timecreated = time();

        return $DB->insert_record(self::TABLE_LOGS, $record);
    }

    /**
     * Update log status.
     *
     * @param int $logid The log ID.
     * @param string $status New status.
     * @param string|null $errorcode Error code if failed.
     * @param string|null $errormessage Error message if failed.
     * @return bool True on success.
     */
    public static function update_log_status(
        int $logid,
        string $status,
        ?string $errorcode = null,
        ?string $errormessage = null
    ): bool {
        global $DB;

        $record = new \stdClass();
        $record->id = $logid;
        $record->status = $status;

        if ($status === 'sent') {
            $record->timesent = time();
        }

        if ($status === 'failed') {
            $record->errorcode = $errorcode;
            $record->errormessage = $errormessage;
        }

        return $DB->update_record(self::TABLE_LOGS, $record);
    }

    /**
     * Get logs for a course.
     *
     * @param int $courseid The course ID.
     * @param int $limit Maximum logs to return.
     * @param int $offset Offset for pagination.
     * @return array Array of log objects.
     */
    public static function get_logs_by_course(int $courseid, int $limit = 50, int $offset = 0): array {
        global $DB;

        return $DB->get_records(
            self::TABLE_LOGS,
            ['courseid' => $courseid],
            'timescheduled DESC',
            '*',
            $offset,
            $limit
        );
    }

    /**
     * Get logs for a followup schedule.
     *
     * @param int $followupid The followup ID.
     * @param int $limit Maximum logs to return.
     * @param int $offset Offset for pagination.
     * @return array Array of log objects.
     */
    public static function get_logs_by_followup(int $followupid, int $limit = 50, int $offset = 0): array {
        global $DB;

        return $DB->get_records(
            self::TABLE_LOGS,
            ['followupid' => $followupid],
            'timescheduled DESC',
            '*',
            $offset,
            $limit
        );
    }

    /**
     * Get pending followup schedules ready to run.
     *
     * @param int|null $timestamp Current timestamp.
     * @return array Array of followup objects.
     */
    public static function get_pending_followups(?int $timestamp = null): array {
        global $DB;

        if ($timestamp === null) {
            $timestamp = time();
        }

        $startOfToday = strtotime('today midnight');

        $sql = "SELECT f.*
                  FROM {" . self::TABLE_FOLLOWUP . "} f
                 WHERE f.enabled = 1
                   AND f.startdate <= :now1
                   AND (f.enddate IS NULL OR f.enddate = 0 OR f.enddate >= :now2)
                   AND (f.nextrun IS NULL OR f.nextrun <= :now3)
                   AND (f.lastrun IS NULL OR f.lastrun < :startoftoday)
              ORDER BY f.nextrun ASC";

        return $DB->get_records_sql($sql, [
            'now1' => $timestamp,
            'now2' => $timestamp,
            'now3' => $timestamp,
            'startoftoday' => $startOfToday,
        ]);
    }

    /**
     * Mark a followup schedule as run.
     *
     * @param int $followupid The followup ID.
     * @return bool True on success.
     */
    public static function mark_followup_run(int $followupid): bool {
        global $DB;

        $followup = self::get_followup($followupid);
        if (!$followup) {
            return false;
        }

        $now = time();
        $followup->lastrun = $now;
        $followup->nextrun = self::calculate_next_run($followup);
        $followup->timemodified = $now;

        return $DB->update_record(self::TABLE_FOLLOWUP, $followup);
    }

    // =========================================================================
    // MESSAGE SENDING UTILITIES
    // =========================================================================

    /**
     * Replace placeholders in message content.
     *
     * @param string $content The content with placeholders.
     * @param object $user The user object.
     * @param object $course The course object.
     * @return string The content with placeholders replaced.
     */
    public static function replace_placeholders(string $content, object $user, object $course): string {
        global $CFG;

        $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);

        // Calculate progress.
        $progress = 0;
        try {
            $completion = new \completion_info($course);
            if ($completion->is_enabled()) {
                $progress = \core_completion\progress::get_course_progress_percentage($course, $user->id);
                $progress = $progress ?? 0;
            }
        } catch (\Exception $e) {
            $progress = 0;
        }

        $replacements = [
            '{FULLNAME}' => fullname($user),
            '{FIRSTNAME}' => $user->firstname ?? '',
            '{LASTNAME}' => $user->lastname ?? '',
            '{EMAIL}' => $user->email ?? '',
            '{COURSENAME}' => format_string($course->fullname),
            '{COURSESHORTNAME}' => format_string($course->shortname),
            '{PROGRESS}' => round($progress) . '%',
            '{COURSEURL}' => $courseurl->out(false),
            '{SITEURL}' => $CFG->wwwroot,
            '{SITENAME}' => format_string($CFG->sitename ?? 'Moodle'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Send an email to a user.
     *
     * @param object $user The user object.
     * @param string $subject Email subject.
     * @param string $body Email body (HTML).
     * @param object $course The course object.
     * @return bool True on success.
     */
    public static function send_email(object $user, string $subject, string $body, object $course): bool {
        global $CFG;

        // Get the noreply user.
        $noreplyuser = \core_user::get_noreply_user();

        // Send the email.
        $result = email_to_user(
            $user,
            $noreplyuser,
            $subject,
            html_to_text($body),
            $body
        );

        return (bool)$result;
    }

    /**
     * Send a Moodle message to a user.
     *
     * @param object $user The user object.
     * @param string $subject Message subject.
     * @param string $body Message body (HTML).
     * @param object $course The course object.
     * @return bool True on success.
     */
    public static function send_moodle_message(object $user, string $subject, string $body, object $course): bool {
        global $USER;

        $message = new \core\message\message();
        $message->component = 'local_epicereports';
        $message->name = 'followup';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = html_to_text($body);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = $body;
        $message->smallmessage = $subject;
        $message->notification = 1;
        $message->contexturl = new \moodle_url('/course/view.php', ['id' => $course->id]);
        $message->contexturlname = format_string($course->fullname);

        try {
            $messageid = message_send($message);
            return !empty($messageid);
        } catch (\Exception $e) {
            debugging('Error sending Moodle message: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
}
