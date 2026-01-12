<?php
/**
 * Schedule Manager class for local_epicereports
 *
 * Handles CRUD operations for report schedules
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports;

defined('MOODLE_INTERNAL') || die();

/**
 * Class schedule_manager
 *
 * Manages scheduled report configurations and recipients
 */
class schedule_manager {

    /** @var string Table name for schedules */
    const TABLE_SCHEDULES = 'local_epicereports_schedules';

    /** @var string Table name for recipients */
    const TABLE_RECIPIENTS = 'local_epicereports_recipients';

    /** @var string Table name for logs */
    const TABLE_LOGS = 'local_epicereports_logs';

    /**
     * Get all schedules for a course.
     *
     * @param int $courseid The course ID.
     * @return array Array of schedule objects.
     */
    public static function get_schedules_by_course(int $courseid): array {
        global $DB;

        return $DB->get_records(self::TABLE_SCHEDULES, ['courseid' => $courseid], 'name ASC');
    }

    /**
     * Get a single schedule by ID.
     *
     * @param int $scheduleid The schedule ID.
     * @return object|false The schedule object or false if not found.
     */
    public static function get_schedule(int $scheduleid) {
        global $DB;

        return $DB->get_record(self::TABLE_SCHEDULES, ['id' => $scheduleid]);
    }

    /**
     * Get all enabled schedules that are due to run.
     *
     * @param int $timestamp Current timestamp to check against.
     * @return array Array of schedule objects ready to run.
     */
    public static function get_pending_schedules(int $timestamp): array {
        global $DB;

        $sql = "SELECT s.*
                  FROM {" . self::TABLE_SCHEDULES . "} s
                 WHERE s.enabled = 1
                   AND s.startdate <= :now1
                   AND (s.enddate IS NULL OR s.enddate >= :now2)
                   AND (s.nextrun IS NULL OR s.nextrun <= :now3)
              ORDER BY s.nextrun ASC";

        return $DB->get_records_sql($sql, [
            'now1' => $timestamp,
            'now2' => $timestamp,
            'now3' => $timestamp,
        ]);
    }

    /**
     * Create a new schedule.
     *
     * @param object $data The schedule data.
     * @return int The new schedule ID.
     */
    public static function create_schedule(object $data): int {
        global $DB, $USER;

        $now = time();

        $record = new \stdClass();
        $record->courseid = $data->courseid;
        $record->name = $data->name;
        $record->enabled = isset($data->enabled) ? (int)$data->enabled : 1;
        $record->startdate = $data->startdate;
        $record->enddate = !empty($data->enddate) ? $data->enddate : null;
        $record->sendtime = $data->sendtime ?? '08:00';
        $record->monday = !empty($data->monday) ? 1 : 0;
        $record->tuesday = !empty($data->tuesday) ? 1 : 0;
        $record->wednesday = !empty($data->wednesday) ? 1 : 0;
        $record->thursday = !empty($data->thursday) ? 1 : 0;
        $record->friday = !empty($data->friday) ? 1 : 0;
        $record->saturday = !empty($data->saturday) ? 1 : 0;
        $record->sunday = !empty($data->sunday) ? 1 : 0;
        $record->include_course_report = !empty($data->include_course_report) ? 1 : 0;
        $record->include_feedback_report = !empty($data->include_feedback_report) ? 1 : 0;
        $record->email_subject = $data->email_subject ?? null;
        $record->email_body = $data->email_body ?? null;
        $record->lastrun = null;
        $record->nextrun = self::calculate_next_run($record);
        $record->createdby = $USER->id;
        $record->timecreated = $now;
        $record->timemodified = $now;

        return $DB->insert_record(self::TABLE_SCHEDULES, $record);
    }

    /**
     * Update an existing schedule.
     *
     * @param object $data The schedule data with id.
     * @return bool True on success.
     */
    public static function update_schedule(object $data): bool {
        global $DB;

        $record = self::get_schedule($data->id);
        if (!$record) {
            return false;
        }

        $record->name = $data->name;
        $record->enabled = isset($data->enabled) ? (int)$data->enabled : $record->enabled;
        $record->startdate = $data->startdate;
        $record->enddate = !empty($data->enddate) ? $data->enddate : null;
        $record->sendtime = $data->sendtime ?? $record->sendtime;
        $record->monday = isset($data->monday) ? (!empty($data->monday) ? 1 : 0) : $record->monday;
        $record->tuesday = isset($data->tuesday) ? (!empty($data->tuesday) ? 1 : 0) : $record->tuesday;
        $record->wednesday = isset($data->wednesday) ? (!empty($data->wednesday) ? 1 : 0) : $record->wednesday;
        $record->thursday = isset($data->thursday) ? (!empty($data->thursday) ? 1 : 0) : $record->thursday;
        $record->friday = isset($data->friday) ? (!empty($data->friday) ? 1 : 0) : $record->friday;
        $record->saturday = isset($data->saturday) ? (!empty($data->saturday) ? 1 : 0) : $record->saturday;
        $record->sunday = isset($data->sunday) ? (!empty($data->sunday) ? 1 : 0) : $record->sunday;
        $record->include_course_report = isset($data->include_course_report) ? (!empty($data->include_course_report) ? 1 : 0) : $record->include_course_report;
        $record->include_feedback_report = isset($data->include_feedback_report) ? (!empty($data->include_feedback_report) ? 1 : 0) : $record->include_feedback_report;
        $record->email_subject = $data->email_subject ?? $record->email_subject;
        $record->email_body = $data->email_body ?? $record->email_body;
        $record->nextrun = self::calculate_next_run($record);
        $record->timemodified = time();

        return $DB->update_record(self::TABLE_SCHEDULES, $record);
    }

    /**
     * Delete a schedule and its recipients.
     *
     * @param int $scheduleid The schedule ID.
     * @return bool True on success.
     */
    public static function delete_schedule(int $scheduleid): bool {
        global $DB;

        // Delete recipients first (foreign key constraint).
        $DB->delete_records(self::TABLE_RECIPIENTS, ['scheduleid' => $scheduleid]);

        // Note: Logs are kept for audit purposes, but scheduleid reference will be orphaned.
        // Alternatively, you could delete logs too or set scheduleid to null.

        return $DB->delete_records(self::TABLE_SCHEDULES, ['id' => $scheduleid]);
    }

    /**
     * Toggle schedule enabled status.
     *
     * @param int $scheduleid The schedule ID.
     * @return bool New enabled status.
     */
    public static function toggle_schedule(int $scheduleid): bool {
        global $DB;

        $schedule = self::get_schedule($scheduleid);
        if (!$schedule) {
            return false;
        }

        $newstatus = $schedule->enabled ? 0 : 1;

        $DB->set_field(self::TABLE_SCHEDULES, 'enabled', $newstatus, ['id' => $scheduleid]);
        $DB->set_field(self::TABLE_SCHEDULES, 'timemodified', time(), ['id' => $scheduleid]);

        // Recalculate next run if enabling.
        if ($newstatus) {
            $schedule->enabled = $newstatus;
            $nextrun = self::calculate_next_run($schedule);
            $DB->set_field(self::TABLE_SCHEDULES, 'nextrun', $nextrun, ['id' => $scheduleid]);
        }

        return (bool)$newstatus;
    }

    /**
     * Calculate the next run timestamp for a schedule.
     *
     * @param object $schedule The schedule object.
     * @return int|null The next run timestamp or null if no valid next run.
     */
    public static function calculate_next_run(object $schedule): ?int {
        if (!$schedule->enabled) {
            return null;
        }

        // Get enabled days as array (1=Monday, 7=Sunday in PHP).
        $enabledDays = [];
        if ($schedule->monday) $enabledDays[] = 1;
        if ($schedule->tuesday) $enabledDays[] = 2;
        if ($schedule->wednesday) $enabledDays[] = 3;
        if ($schedule->thursday) $enabledDays[] = 4;
        if ($schedule->friday) $enabledDays[] = 5;
        if ($schedule->saturday) $enabledDays[] = 6;
        if ($schedule->sunday) $enabledDays[] = 7;

        if (empty($enabledDays)) {
            return null;
        }

        // Parse send time.
        $timeParts = explode(':', $schedule->sendtime);
        $sendHour = (int)($timeParts[0] ?? 8);
        $sendMinute = (int)($timeParts[1] ?? 0);

        // Start from now or lastrun, whichever is later.
        $now = time();
        $baseTime = max($now, $schedule->lastrun ?? 0);

        // If we have a lastrun on the same day but time has passed, start from tomorrow.
        $baseDate = new \DateTime('@' . $baseTime);
        $baseDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        // Try to find next valid day within 8 days (covers all weekdays).
        for ($i = 0; $i <= 7; $i++) {
            $checkDate = clone $baseDate;
            $checkDate->modify("+{$i} days");
            $checkDate->setTime($sendHour, $sendMinute, 0);

            $dayOfWeek = (int)$checkDate->format('N'); // 1=Monday, 7=Sunday

            // Check if this day is enabled.
            if (!in_array($dayOfWeek, $enabledDays)) {
                continue;
            }

            $checkTimestamp = $checkDate->getTimestamp();

            // Must be in the future.
            if ($checkTimestamp <= $now) {
                continue;
            }

            // Must be within schedule date range.
            if ($checkTimestamp < $schedule->startdate) {
                continue;
            }

            if ($schedule->enddate && $checkTimestamp > $schedule->enddate) {
                return null; // Schedule has ended.
            }

            return $checkTimestamp;
        }

        return null;
    }

    /**
     * Update schedule after successful run.
     *
     * @param int $scheduleid The schedule ID.
     * @return void
     */
    public static function mark_schedule_run(int $scheduleid): void {
        global $DB;

        $schedule = self::get_schedule($scheduleid);
        if (!$schedule) {
            return;
        }

        $now = time();
        $schedule->lastrun = $now;
        $schedule->nextrun = self::calculate_next_run($schedule);
        $schedule->timemodified = $now;

        $DB->update_record(self::TABLE_SCHEDULES, $schedule);
    }

    // =========================================================================
    // RECIPIENTS MANAGEMENT
    // =========================================================================

    /**
     * Get all recipients for a schedule.
     *
     * @param int $scheduleid The schedule ID.
     * @return array Array of recipient objects.
     */
    public static function get_recipients(int $scheduleid): array {
        global $DB;

        return $DB->get_records(self::TABLE_RECIPIENTS, ['scheduleid' => $scheduleid], 'recipienttype ASC, email ASC');
    }

    /**
     * Get enabled recipients for a schedule.
     *
     * @param int $scheduleid The schedule ID.
     * @return array Array of enabled recipient objects.
     */
    public static function get_enabled_recipients(int $scheduleid): array {
        global $DB;

        return $DB->get_records(self::TABLE_RECIPIENTS, [
            'scheduleid' => $scheduleid,
            'enabled' => 1
        ], 'recipienttype ASC, email ASC');
    }

    /**
     * Add a recipient to a schedule.
     *
     * @param int $scheduleid The schedule ID.
     * @param string $email The email address.
     * @param string $fullname Optional full name.
     * @param int|null $userid Optional Moodle user ID.
     * @param string $type Recipient type (to, cc, bcc).
     * @return int|false The new recipient ID or false on failure.
     */
    public static function add_recipient(int $scheduleid, string $email, string $fullname = '', ?int $userid = null, string $type = 'to') {
        global $DB;

        // Validate email.
        if (!validate_email($email)) {
            return false;
        }

        // Check for duplicate.
        if ($DB->record_exists(self::TABLE_RECIPIENTS, ['scheduleid' => $scheduleid, 'email' => $email])) {
            return false;
        }

        $record = new \stdClass();
        $record->scheduleid = $scheduleid;
        $record->userid = $userid;
        $record->email = $email;
        $record->fullname = $fullname;
        $record->recipienttype = in_array($type, ['to', 'cc', 'bcc']) ? $type : 'to';
        $record->enabled = 1;
        $record->timecreated = time();

        return $DB->insert_record(self::TABLE_RECIPIENTS, $record);
    }

    /**
     * Remove a recipient from a schedule.
     *
     * @param int $recipientid The recipient ID.
     * @return bool True on success.
     */
    public static function remove_recipient(int $recipientid): bool {
        global $DB;

        return $DB->delete_records(self::TABLE_RECIPIENTS, ['id' => $recipientid]);
    }

    /**
     * Toggle recipient enabled status.
     *
     * @param int $recipientid The recipient ID.
     * @return bool New enabled status.
     */
    public static function toggle_recipient(int $recipientid): bool {
        global $DB;

        $recipient = $DB->get_record(self::TABLE_RECIPIENTS, ['id' => $recipientid]);
        if (!$recipient) {
            return false;
        }

        $newstatus = $recipient->enabled ? 0 : 1;
        $DB->set_field(self::TABLE_RECIPIENTS, 'enabled', $newstatus, ['id' => $recipientid]);

        return (bool)$newstatus;
    }

    /**
     * Update recipients for a schedule (bulk operation).
     *
     * @param int $scheduleid The schedule ID.
     * @param array $recipients Array of recipient data.
     * @return bool True on success.
     */
    public static function update_recipients(int $scheduleid, array $recipients): bool {
        global $DB;

        // Delete all existing recipients.
        $DB->delete_records(self::TABLE_RECIPIENTS, ['scheduleid' => $scheduleid]);

        // Add new recipients.
        foreach ($recipients as $recipient) {
            if (empty($recipient['email'])) {
                continue;
            }

            self::add_recipient(
                $scheduleid,
                $recipient['email'],
                $recipient['fullname'] ?? '',
                $recipient['userid'] ?? null,
                $recipient['type'] ?? 'to'
            );
        }

        return true;
    }

    // =========================================================================
    // LOGS MANAGEMENT
    // =========================================================================

    /**
     * Create a log entry.
     *
     * @param int $scheduleid The schedule ID.
     * @param int $courseid The course ID.
     * @param string $email Recipient email.
     * @param int|null $recipientid Optional recipient ID.
     * @param int $timescheduled Scheduled time.
     * @param string $status Status (pending, sent, failed).
     * @param array $attachments List of attachment filenames.
     * @return int The log ID.
     */
    public static function create_log(
        int $scheduleid,
        int $courseid,
        string $email,
        ?int $recipientid,
        int $timescheduled,
        string $status = 'pending',
        array $attachments = []
    ): int {
        global $DB;

        $record = new \stdClass();
        $record->scheduleid = $scheduleid;
        $record->courseid = $courseid;
        $record->recipientid = $recipientid;
        $record->recipientemail = $email;
        $record->status = $status;
        $record->attachments = !empty($attachments) ? json_encode($attachments) : null;
        $record->retrycount = 0;
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

        if ($status === 'failed' || $status === 'retry') {
            $record->errorcode = $errorcode;
            $record->errormessage = $errormessage;

            // Increment retry count.
            $current = $DB->get_record(self::TABLE_LOGS, ['id' => $logid]);
            if ($current) {
                $record->retrycount = $current->retrycount + 1;
            }
        }

        return $DB->update_record(self::TABLE_LOGS, $record);
    }

    /**
     * Get logs for a course.
     *
     * @param int $courseid The course ID.
     * @param int $limit Maximum number of logs to return.
     * @param int $offset Offset for pagination.
     * @param string|null $status Filter by status.
     * @return array Array of log objects.
     */
    public static function get_logs_by_course(int $courseid, int $limit = 50, int $offset = 0, ?string $status = null): array {
        global $DB;

        $params = ['courseid' => $courseid];
        $where = 'courseid = :courseid';

        if ($status) {
            $where .= ' AND status = :status';
            $params['status'] = $status;
        }

        return $DB->get_records_select(
            self::TABLE_LOGS,
            $where,
            $params,
            'timescheduled DESC',
            '*',
            $offset,
            $limit
        );
    }

    /**
     * Get logs for a schedule.
     *
     * @param int $scheduleid The schedule ID.
     * @param int $limit Maximum number of logs to return.
     * @param int $offset Offset for pagination.
     * @return array Array of log objects.
     */
    public static function get_logs_by_schedule(int $scheduleid, int $limit = 50, int $offset = 0): array {
        global $DB;

        return $DB->get_records(
            self::TABLE_LOGS,
            ['scheduleid' => $scheduleid],
            'timescheduled DESC',
            '*',
            $offset,
            $limit
        );
    }

    /**
     * Count logs for a course.
     *
     * @param int $courseid The course ID.
     * @param string|null $status Filter by status.
     * @return int Number of logs.
     */
    public static function count_logs_by_course(int $courseid, ?string $status = null): int {
        global $DB;

        $params = ['courseid' => $courseid];

        if ($status) {
            $params['status'] = $status;
        }

        return $DB->count_records(self::TABLE_LOGS, $params);
    }

    /**
     * Get failed logs that can be retried.
     *
     * @param int $maxretries Maximum retry attempts.
     * @return array Array of log objects.
     */
    public static function get_retriable_logs(int $maxretries = 3): array {
        global $DB;

        $sql = "SELECT *
                  FROM {" . self::TABLE_LOGS . "}
                 WHERE status IN ('failed', 'retry')
                   AND retrycount < :maxretries
              ORDER BY timescheduled ASC";

        return $DB->get_records_sql($sql, ['maxretries' => $maxretries]);
    }

    /**
     * Mark a log for retry.
     *
     * @param int $logid The log ID.
     * @return bool True on success.
     */
    public static function mark_for_retry(int $logid): bool {
        return self::update_log_status($logid, 'retry');
    }
}
