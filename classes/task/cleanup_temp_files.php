<?php
/**
 * Scheduled task to clean up old temporary report files
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports\task;

defined('MOODLE_INTERNAL') || die();

use local_epicereports\report_generator;

/**
 * Task to clean up old temporary report files.
 */
class cleanup_temp_files extends \core\task\scheduled_task {

    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Limpiar archivos temporales de reportes';
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        mtrace('local_epicereports: Limpiando archivos temporales de reportes...');

        // Delete files older than 24 hours.
        $deleted = report_generator::cleanup_old_files(86400);

        mtrace("local_epicereports: Se eliminaron $deleted archivo(s) temporal(es).");
    }
}
