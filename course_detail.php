<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php'); // <-- menú lateral

use local_epicereports\helper;

// 1) Obtener el ID del curso desde la URL (courseid o id).
$courseid = optional_param('courseid', 0, PARAM_INT);
if (!$courseid) {
    // Si no viene courseid, exigimos id (como en view.php).
    $courseid = required_param('id', PARAM_INT);
}

// 2) Cargar curso y contexto, y exigir login sobre el curso.
$course  = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]));
// Antes estaba 'popup'; con menú lateral encaja mejor 'admin' o 'report'.
$PAGE->set_pagelayout('popup');

// 2.1) Incluir jQuery y CSS de DataTables.
$PAGE->requires->jquery();
$PAGE->requires->css(new moodle_url('https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css'));

// 2.2) Configurar RequireJS para cargar DataTables desde CDN y luego inicializar la tabla.
$PAGE->requires->js_init_code("
    require.config({
        paths: {
            'datatables.net': 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min'
        }
    });

    require(['jquery', 'datatables.net'], function($) {
        $('#users-table').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1],[10, 25, 50, 100, 'Todos']],
            order: [[0, 'asc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
            }
        });
    });
");

// 3) Obtener datos del helper (reutilizamos la función que alimenta el Excel).
$course_data = helper::get_course_data_for_excel($courseid);

// 4) Render.
echo $OUTPUT->header();
echo $OUTPUT->heading('Detalle del curso: ' . format_string($course_data['course']->fullname));

// -------------------------------------------------------------------------
// Layout con menú lateral (izquierda) + contenido (derecha)
// -------------------------------------------------------------------------
echo html_writer::start_div('row');

// Columna izquierda: menú lateral
echo html_writer::start_div('col-md-3 col-lg-2');
local_epicereports_render_sidebar('course_detail', $course);
echo html_writer::end_div();

// Columna derecha: todo el contenido actual del detalle
echo html_writer::start_div('col-md-9 col-lg-10');

// Tarjeta info general.
echo html_writer::start_div('card mb-4 p-3');
echo html_writer::tag('h6', 'Información general', ['class' => 'mb-3']);
echo html_writer::tag('p', 'Nombre corto: ' . s($course_data['course']->shortname));
echo html_writer::tag('p', 'Visibilidad: ' . ($course_data['course']->visible ? 'Visible' : 'Oculto'));
echo html_writer::end_div();

// Botones de exportación.
echo html_writer::start_div('mt-4');
echo html_writer::start_div('row');

// Botón Exportar a Excel.
echo html_writer::start_div('col-auto');
$url  = new moodle_url('/local/epicereports/export_course_excel.php', ['courseid' => $courseid]);
$link = html_writer::link($url, 'Exportar a Excel', [
    'target' => '_blank',
    'class'  => 'btn btn-success'
]);
echo $link;
echo html_writer::end_div();

// Botón Vista previa.
echo html_writer::start_div('col-auto');
$url  = new moodle_url('/local/epicereports/export_course_excel.php', [
    'courseid' => $courseid,
    'preview'  => 1
]);
$link = html_writer::link($url, 'Vista previa a exportar', [
    'target' => '_blank',
    'class'  => 'btn btn-secondary'
]);
echo $link;
echo html_writer::end_div();

echo html_writer::end_div(); // row
echo html_writer::end_div(); // mt-4

// Tabla usuarios (mejorada con DataTables).
echo html_writer::start_div('card shadow mb-4 mt-4');
echo html_writer::tag('h6', 'Alumnos matriculados y progreso', ['class' => 'p-3 pb-0']);
echo html_writer::start_div('table-responsive p-3');

// Tabla con id=users-table para enganchar DataTables.
echo html_writer::start_tag('table', [
    'class' => 'table table-striped table-bordered table-sm',
    'id'    => 'users-table'
]);

$headers = [
    'Nombre completo',
    'Email',
    'Primer acceso',
    'Último acceso',
    'Grupos',
    '% avance',
    'Estado curso',
    'Nota final (%)'
];

echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
foreach ($headers as $header) {
    echo html_writer::tag('th', $header);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

// Cuerpo tabla.
echo html_writer::start_tag('tbody');

if (!empty($course_data['users'])) {
    foreach ($course_data['users'] as $user) {
        echo html_writer::start_tag('tr');

        echo html_writer::tag('td', s($user->fullname));
        echo html_writer::tag('td', s($user->email));
        echo html_writer::tag('td', s($user->primer_acceso));
        echo html_writer::tag('td', s($user->ultimo_acceso));
        echo html_writer::tag('td', s($user->grupos));
        echo html_writer::tag('td', s($user->porcentaje_avance));
        echo html_writer::tag('td', s($user->estado_finalizacion));
        echo html_writer::tag('td', s($user->nota_final));

        echo html_writer::end_tag('tr');
    }
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo html_writer::end_div(); // table-responsive
echo html_writer::end_div(); // card

// Cierre columna derecha y fila principal
echo html_writer::end_div(); // col-md-9 col-lg-10
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
