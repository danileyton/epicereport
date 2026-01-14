<?php
/**
 * Report Generator - Generador de reportes para Epice Reports
 *
 * @package    local_epicereports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->dirroot . '/local/epicereports/locallib.php');

class report_generator {

    /**
     * Genera un reporte Excel del curso.
     *
     * @param int $courseid
     * @return string|false Ruta del archivo generado o false en caso de error.
     */
    public static function generate_course_excel($courseid) {
        global $CFG, $DB, $PAGE;

        require_once($CFG->libdir . '/excellib.class.php');

        // Asegurar contexto
        $context = \context_course::instance($courseid);

        // Evitar error "Undefined variable $PAGE" si no existe
        if (empty($PAGE)) {
            $PAGE = new \moodle_page();
            $PAGE->set_context($context);
        } else {
            $PAGE->set_context($context);
        }

        $course = get_course($courseid);
        $filename = "course_report_{$courseid}_" . date('Ymd_His') . ".xlsx";
        $filepath = make_temp_directory('epicereports') . '/' . $filename;

        $workbook = new \MoodleExcelWorkbook($filepath);
        $workbook->send($filename);
        $worksheet = $workbook->add_worksheet('Reporte Curso');

        // Encabezados
        $headers = ['ID Usuario', 'Nombre Completo', 'Email', 'Progreso', 'Último Acceso'];
        foreach ($headers as $col => $header) {
            $worksheet->write_string(0, $col, $header);
        }

        // Obtener usuarios matriculados
        $enrolled = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email, u.lastaccess');

        $row = 1;
        foreach ($enrolled as $user) {
            $worksheet->write_number($row, 0, $user->id);
            $worksheet->write_string($row, 1, fullname($user));
            $worksheet->write_string($row, 2, $user->email);
            $worksheet->write_string($row, 3, get_string('notavailable', 'moodle')); // placeholder progreso
            $worksheet->write_string($row, 4, userdate($user->lastaccess));
            $row++;
        }

        $workbook->close();

        return $filepath;
    }

    /**
     * Genera un reporte Excel del feedback del curso.
     *
     * @param int $courseid
     * @return string|false Ruta del archivo generado o false en caso de error.
     */
    public static function generate_feedback_excel($courseid) {
        global $CFG, $DB, $PAGE;

        require_once($CFG->libdir . '/excellib.class.php');

        $context = \context_course::instance($courseid);

        if (empty($PAGE)) {
            $PAGE = new \moodle_page();
            $PAGE->set_context($context);
        } else {
            $PAGE->set_context($context);
        }

        $course = get_course($courseid);
        $filename = "feedback_report_{$courseid}_" . date('Ymd_His') . ".xlsx";
        $filepath = make_temp_directory('epicereports') . '/' . $filename;

        $workbook = new \MoodleExcelWorkbook($filepath);
        $workbook->send($filename);
        $worksheet = $workbook->add_worksheet('Feedback');

        $headers = ['ID Usuario', 'Nombre', 'Email', 'Feedback'];
        foreach ($headers as $col => $header) {
            $worksheet->write_string(0, $col, $header);
        }

        // Obtener datos de feedback (si el módulo existe)
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email, f.name AS feedbackname
                  FROM {user} u
             LEFT JOIN {feedback} f ON f.course = :courseid
              ORDER BY u.lastname ASC";
        $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        $row = 1;
        foreach ($records as $r) {
            $worksheet->write_number($row, 0, $r->id);
            $worksheet->write_string($row, 1, fullname($r));
            $worksheet->write_string($row, 2, $r->email);
            $worksheet->write_string($row, 3, $r->feedbackname ?? '-');
            $row++;
        }

        $workbook->close();

        return $filepath;
    }
}
