<?php
/**
 * Scheduled task to send report emails
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to process and send scheduled report emails.
 */
class send_scheduled_reports extends \core\task\scheduled_task {

    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskname', 'local_epicereports');
    }

    /**
     * Execute the task.
     *
     * This is a placeholder implementation.
     * Full implementation will be added in Etapa 5.
     */
    public function execute(): void {
        mtrace('local_epicereports: Checking for pending scheduled reports...');

        // TODO: Implement in Etapa 5
        // 1. Get pending schedules using schedule_manager::get_pending_schedules()
        // 2. For each schedule:
        //    a. Check if current day/time matches schedule
        //    b. Generate report files
        //    c. Get recipients
        //    d. Send emails with attachments
        //    e. Log results
        //    f. Update schedule lastrun/nextrun

        mtrace('local_epicereports: Task placeholder - full implementation pending.');
    }
}
