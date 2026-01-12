<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\helper;

$courseid = optional_param('courseid', 0, PARAM_INT);
if (!$courseid) {
    $courseid = required_param('id', PARAM_INT);
}

$course  = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]));
$PAGE->set_pagelayout('incourse');

$PAGE->requires->jquery();
$PAGE->requires->css(new moodle_url('https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css'));

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

$course_data = helper::get_course_data_for_excel($courseid);

echo $OUTPUT->header();
echo $OUTPUT->heading('Detalle del curso: ' . format_string($course_data['course']->fullname));

echo html_writer::start_div('row');

echo html_writer::start_div('col-md-3 col-lg-2');
local_epicereports_render_sidebar('course_detail', $course);
echo html_writer::end_div();

echo html_writer::start_div('col-md-9 col-lg-10');

echo html_writer::start_div('card mb-4 p-3');
echo html_writer::tag('h6', 'Información general', ['class' => 'mb-3']);
echo html_writer::tag('p', 'Nombre corto: ' . s($course_data['course']->shortname));
echo html_writer::tag('p', 'Visibilidad: ' . ($course_data['course']->visible ? 'Visible' : 'Oculto'));
echo html_writer::end_div();

echo html_writer::start_div('mt-4');
echo html_writer::start_div('row');

echo html_writer::start_div('col-auto');
$url  = new moodle_url('/local/epicereports/export_course_excel.php', ['courseid' => $courseid]);
$link = html_writer::link($url, 'Exportar a Excel', ['target' => '_blank', 'class' => 'btn btn-success']);
echo $link;
echo html_writer::end_div();

echo html_writer::start_div('col-auto');
$url  = new moodle_url('/local/epicereports/export_course_excel.php', ['courseid' => $courseid, 'preview' => 1]);
$link = html_writer::link($url, 'Vista previa a exportar', ['target' => '_blank', 'class' => 'btn btn-secondary']);
echo $link;
echo html_writer::end_div();

// Nuevo botón: Programar envíos
echo html_writer::start_div('col-auto');
$url  = new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]);
$link = html_writer::link($url, 'Programar Envíos', ['class' => 'btn btn-primary']);
echo $link;
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card shadow mb-4 mt-4');
echo html_writer::tag('h6', 'Alumnos matriculados y progreso', ['class' => 'p-3 pb-0']);
echo html_writer::start_div('table-responsive p-3');

echo html_writer::start_tag('table', ['class' => 'table table-striped table-bordered table-sm', 'id' => 'users-table']);

$headers = ['Nombre completo', 'Email', 'Primer acceso', 'Último acceso', 'Grupos', '% avance', 'Estado curso', 'Nota final (%)'];

echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
foreach ($headers as $header) {
    echo html_writer::tag('th', $header);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

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

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
