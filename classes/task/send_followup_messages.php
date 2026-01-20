<?php
/**
 * Scheduled task to send follow-up messages to students
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to send follow-up messages to students who haven't completed courses
 */
class send_followup_messages extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:sendfollowupmessages', 'local_epicereports');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $CFG;

        require_once($CFG->dirroot . '/local/epicereports/classes/followup_manager.php');

        mtrace('EpicE Reports: Iniciando tarea de mensajes de seguimiento...');

        // TODO: Implementar en Fase 7
        // 1. Obtener followups pendientes
        // 2. Para cada followup:
        //    a. Obtener estudiantes que cumplen criterios
        //    b. Verificar límites por usuario
        //    c. Enviar mensajes (email y/o mensajería)
        //    d. Registrar logs
        //    e. Marcar followup como ejecutado

        $followups = \local_epicereports\followup_manager::get_pending_followups();

        if (empty($followups)) {
            mtrace('  No hay programaciones de seguimiento pendientes.');
            return;
        }

        mtrace('  Encontradas ' . count($followups) . ' programación(es) pendiente(s).');

        foreach ($followups as $followup) {
            mtrace('  Procesando: ' . $followup->name . ' (ID: ' . $followup->id . ')');
            
            // TODO: Implementar lógica de envío
            
            mtrace('    [Pendiente de implementación]');
        }

        mtrace('EpicE Reports: Tarea de mensajes de seguimiento completada.');
    }
}
