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

use local_epicereports\schedule_manager;
use local_epicereports\report_generator;
use local_epicereports\email_sender;

/**
 * Scheduled task to process and send scheduled report emails.
 */
class send_scheduled_reports extends \core\task\scheduled_task {

    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_sendreports', 'local_epicereports');
    }

    /**
     * Execute the task.
     *
     * Main workflow:
     * 1. Get all pending schedules (enabled, within date range, correct day/time)
     * 2. For each schedule:
     *    a. Generate report files
     *    b. Send to all recipients
     *    c. Update schedule timestamps
     *    d. Clean up temporary files
     */
    public function execute(): void {
        global $DB;

        mtrace('');
        mtrace('=================================================================');
        mtrace('local_epicereports: Iniciando tarea de envío de reportes programados');
        mtrace('Fecha/hora actual: ' . userdate(time(), '%Y-%m-%d %H:%M:%S'));
        mtrace('=================================================================');

        // Get all pending schedules.
        $schedules = schedule_manager::get_pending_schedules();

        if (empty($schedules)) {
            mtrace('No hay programaciones pendientes de ejecutar.');
            mtrace('');
            return;
        }

        mtrace('Programaciones pendientes encontradas: ' . count($schedules));
        mtrace('');

        $totalsent = 0;
        $totalfailed = 0;
        $processedschedules = 0;

        foreach ($schedules as $schedule) {
            mtrace('-----------------------------------------------------------------');
            mtrace("Procesando programación ID {$schedule->id}: " . s($schedule->name));

            // Verify schedule is still valid.
            if (!$this->is_schedule_ready($schedule)) {
                mtrace('  → Programación no está lista para ejecutar (verificación adicional)');
                continue;
            }

            // Get course info.
            try {
                $course = get_course($schedule->courseid);
                mtrace("  Curso: " . s($course->fullname) . " (ID: {$course->id})");
            } catch (\Exception $e) {
                mtrace("  ✗ Error: Curso no encontrado (ID: {$schedule->courseid})");
                continue;
            }

            // Check recipients.
            $recipients = schedule_manager::get_enabled_recipients($schedule->id);
            if (empty($recipients)) {
                mtrace('  ✗ No hay destinatarios activos configurados');
                // Update lastrun anyway to avoid re-processing.
                schedule_manager::mark_schedule_run($schedule->id);
                continue;
            }

            mtrace("  Destinatarios activos: " . count($recipients));

            // Generate reports.
            mtrace('  Generando reportes...');
            $reportresults = report_generator::generate_schedule_reports($schedule);

            if (empty($reportresults['files'])) {
                mtrace('  ✗ No se pudieron generar los reportes');
                if (!empty($reportresults['errors'])) {
                    foreach ($reportresults['errors'] as $error) {
                        mtrace("    Error: $error");
                    }
                }
                // Update lastrun anyway.
                schedule_manager::mark_schedule_run($schedule->id);
                continue;
            }

            mtrace('  ✓ Reportes generados: ' . count($reportresults['files']));
            foreach ($reportresults['files'] as $file) {
                mtrace("    - {$file['filename']}");
            }

            // Send emails.
            mtrace('  Enviando correos...');
            $sendresults = email_sender::send_to_all_recipients($schedule, $reportresults['files']);

            mtrace("  ✓ Enviados: {$sendresults['sent']} / {$sendresults['total']}");
            if ($sendresults['failed'] > 0) {
                mtrace("  ✗ Fallidos: {$sendresults['failed']}");
                foreach ($sendresults['results'] as $result) {
                    if (!$result['success']) {
                        mtrace("    - {$result['recipient']}: {$result['error']}");
                    }
                }
            }

            $totalsent += $sendresults['sent'];
            $totalfailed += $sendresults['failed'];

            // Update schedule timestamps.
            schedule_manager::mark_schedule_run($schedule->id);
            mtrace('  ✓ Programación actualizada');

            // Clean up temporary files.
            foreach ($reportresults['files'] as $file) {
                if (file_exists($file['filepath'])) {
                    unlink($file['filepath']);
                }
            }
            mtrace('  ✓ Archivos temporales eliminados');

            $processedschedules++;
        }

        mtrace('');
        mtrace('=================================================================');
        mtrace('RESUMEN DE EJECUCIÓN:');
        mtrace("  Programaciones procesadas: $processedschedules");
        mtrace("  Correos enviados: $totalsent");
        mtrace("  Correos fallidos: $totalfailed");
        mtrace('=================================================================');
        mtrace('');

        // Also process any failed emails that can be retried.
        $this->process_retry_queue();
    }

    /**
     * Process failed emails that are eligible for retry.
     */
    private function process_retry_queue(): void {
        mtrace('Verificando cola de reintentos...');

        $retriablelogs = schedule_manager::get_retriable_logs(email_sender::MAX_RETRIES);

        if (empty($retriablelogs)) {
            mtrace('  No hay correos pendientes de reintento.');
            return;
        }

        mtrace('  Correos a reintentar: ' . count($retriablelogs));

        $retried = 0;
        $succeeded = 0;

        foreach ($retriablelogs as $log) {
            // Only retry a limited number per execution to avoid overloading.
            if ($retried >= 10) {
                mtrace('  Límite de reintentos por ejecución alcanzado (10)');
                break;
            }

            mtrace("  Reintentando log ID {$log->id} ({$log->recipientemail})...");

            $result = email_sender::retry_from_log($log->id);

            if ($result['success']) {
                mtrace('    ✓ Éxito');
                $succeeded++;
            } else {
                mtrace("    ✗ Falló: {$result['error']}");
            }

            $retried++;
        }

        mtrace("  Reintentos: $succeeded/$retried exitosos");
    }

    /**
     * Additional verification that a schedule is ready to run.
     *
     * @param object $schedule The schedule object.
     * @return bool True if ready.
     */
    private function is_schedule_ready(object $schedule): bool {
        // Already checked in get_pending_schedules, but double-check here.
        if (!$schedule->enabled) {
            return false;
        }

        $now = time();

        // Check date range.
        if ($schedule->startdate && $now < $schedule->startdate) {
            return false;
        }

        if ($schedule->enddate && $now > $schedule->enddate) {
            return false;
        }

        // Check day of week.
        $currentday = strtolower(date('l'));
        $daymap = [
            'monday'    => $schedule->monday,
            'tuesday'   => $schedule->tuesday,
            'wednesday' => $schedule->wednesday,
            'thursday'  => $schedule->thursday,
            'friday'    => $schedule->friday,
            'saturday'  => $schedule->saturday,
            'sunday'    => $schedule->sunday,
        ];

        if (empty($daymap[$currentday])) {
            return false;
        }

        return true;
    }
}
