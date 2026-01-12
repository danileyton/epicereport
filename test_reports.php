<?php
/**
 * Test report generation page
 *
 * This page allows testing report generation without sending emails.
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\report_generator;

// Parameters.
$courseid = required_param('courseid', PARAM_INT);
$action   = optional_param('action', '', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);

// Load course and context.
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:manageschedules', $context);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/test_reports.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title('Probar Generación de Reportes');
$PAGE->set_heading(format_string($course->fullname));

// Handle download actions.
if ($download === 'course') {
    $result = report_generator::generate_course_excel($courseid);

    if ($result['success']) {
        // Send file for download.
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . filesize($result['filepath']));
        header('Cache-Control: max-age=0');

        readfile($result['filepath']);

        // Optionally delete temp file after download.
        // unlink($result['filepath']);

        exit;
    } else {
        redirect(
            new moodle_url('/local/epicereports/test_reports.php', ['courseid' => $courseid]),
            'Error: ' . $result['error'],
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

if ($download === 'feedback') {
    $result = report_generator::generate_feedback_excel($courseid);

    if ($result['success']) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . filesize($result['filepath']));
        header('Cache-Control: max-age=0');

        readfile($result['filepath']);
        exit;
    } else {
        redirect(
            new moodle_url('/local/epicereports/test_reports.php', ['courseid' => $courseid]),
            'Error: ' . $result['error'],
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Handle cleanup action.
if ($action === 'cleanup') {
    require_sesskey();

    $deleted = report_generator::cleanup_old_files(0); // Delete all files for testing.

    redirect(
        new moodle_url('/local/epicereports/test_reports.php', ['courseid' => $courseid]),
        "Se eliminaron $deleted archivo(s) temporales.",
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Display page.
echo $OUTPUT->header();
echo $OUTPUT->heading('Probar Generación de Reportes');

// Layout with sidebar.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2');
local_epicereports_render_sidebar('course_detail', $course);
echo html_writer::end_div();

// Main content.
echo html_writer::start_div('col-md-9 col-lg-10');

// Info alert.
echo html_writer::div(
    '<strong>Página de pruebas</strong><br>
    Esta página permite probar la generación de reportes Excel sin enviar correos.
    Los archivos se generan en el directorio temporal del servidor.',
    'alert alert-info'
);

// Test buttons.
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'Generar Reportes', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');

// Course report button.
$courseurl = new moodle_url('/local/epicereports/test_reports.php', [
    'courseid' => $courseid,
    'download' => 'course',
]);
echo html_writer::link($courseurl, 'Generar Reporte del Curso (Excel)', [
    'class' => 'btn btn-success mr-2 mb-2',
]);

// Feedback report button.
$feedbackurl = new moodle_url('/local/epicereports/test_reports.php', [
    'courseid' => $courseid,
    'download' => 'feedback',
]);
echo html_writer::link($feedbackurl, 'Generar Reporte de Encuestas (Excel)', [
    'class' => 'btn btn-primary mr-2 mb-2',
]);

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Temp directory info.
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'Directorio Temporal', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');

$tempdir = report_generator::get_temp_dir();
echo html_writer::tag('p', '<strong>Ruta:</strong> <code>' . s($tempdir) . '</code>');

// List existing temp files.
if (is_dir($tempdir)) {
    $files = glob($tempdir . '/*.xlsx');

    if (!empty($files)) {
        echo html_writer::tag('p', '<strong>Archivos temporales:</strong>');

        $table = new html_table();
        $table->attributes = ['class' => 'table table-sm table-bordered'];
        $table->head = ['Archivo', 'Tamaño', 'Fecha creación'];
        $table->data = [];

        foreach ($files as $file) {
            $table->data[] = [
                basename($file),
                round(filesize($file) / 1024, 2) . ' KB',
                date('d/m/Y H:i:s', filemtime($file)),
            ];
        }

        echo html_writer::table($table);

        // Cleanup button.
        $cleanupurl = new moodle_url('/local/epicereports/test_reports.php', [
            'courseid' => $courseid,
            'action'   => 'cleanup',
            'sesskey'  => sesskey(),
        ]);
        echo html_writer::link($cleanupurl, 'Limpiar archivos temporales', [
            'class'   => 'btn btn-warning',
            'onclick' => "return confirm('¿Eliminar todos los archivos temporales?');",
        ]);
    } else {
        echo html_writer::tag('p', '<em>No hay archivos temporales.</em>', ['class' => 'text-muted']);
    }
} else {
    echo html_writer::tag('p', '<em>El directorio temporal aún no existe.</em>', ['class' => 'text-muted']);
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Test API usage.
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'Uso de la API', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');

echo html_writer::tag('p', 'La clase <code>report_generator</code> puede usarse de la siguiente manera:');

$codeexample = '
// Generar reporte de curso.
$result = \local_epicereports\report_generator::generate_course_excel($courseid);

if ($result[\'success\']) {
    $filepath = $result[\'filepath\']; // Ruta completa al archivo
    $filename = $result[\'filename\']; // Nombre del archivo
    
    // El archivo está listo para adjuntar a un email.
} else {
    $error = $result[\'error\']; // Mensaje de error
}

// Generar reporte de encuestas.
$result = \local_epicereports\report_generator::generate_feedback_excel($courseid);

// Generar todos los reportes para una programación.
$results = \local_epicereports\report_generator::generate_schedule_reports($schedule);

// Limpiar archivos viejos (más de 24 horas).
$deleted = \local_epicereports\report_generator::cleanup_old_files(86400);
';

echo html_writer::tag('pre', s($codeexample), ['style' => 'background: #f5f5f5; padding: 15px; border-radius: 5px;']);

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Close layout.
echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
