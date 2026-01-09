<?php
/**
 * Listado de cursos con filtros
 *
 * @package    local_epicereports
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\helper;

// Requiere login.
require_login();

// Contexto y capacidades.
$context = context_system::instance();
require_capability('local/epicereports:view', $context);

// Configurar página - IMPORTANTE: usar 'popup' como en el original.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/courses.php'));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('courselist', 'local_epicereports'));
$PAGE->set_heading(get_string('courselist', 'local_epicereports'));

// =========================================================================
// FILTROS
// =========================================================================
$category_filter = optional_param('category', 0, PARAM_INT);
$visible_filter = optional_param('visible', -1, PARAM_INT);

// Obtener lista de categorías.
$categories = helper::get_course_categories();

// Obtener cursos filtrados.
$courses_list = helper::get_courses_list('', $category_filter, $visible_filter);

// =========================================================================
// DATATABLES - MÉTODO ORIGINAL QUE FUNCIONABA
// =========================================================================

// jQuery - IMPORTANTE: esto debe ir ANTES del js_init_code.
$PAGE->requires->jquery();

// CSS de DataTables desde CDN.
$PAGE->requires->css(new moodle_url('https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css'));

// DataTables via RequireJS.
$PAGE->requires->js_init_code("
    require.config({
        paths: {
            'datatables.net': 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min'
        },
        shim: {
            'datatables.net': {
                deps: ['jquery'],
                exports: 'jQuery.fn.dataTable'
            }
        }
    });

    require(['jquery', 'datatables.net'], function($) {
        $('#courses-table').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
            order: [[1, 'asc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
            },
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });
    });
");

// =========================================================================
// RENDERIZADO
// =========================================================================

$PAGE->requires->css(new moodle_url('/local/epicereports/styles_epicereports.css'));
echo $OUTPUT->header();

// Layout con menú lateral + contenido.
echo html_writer::start_div('row');

// =========================================================================
// COLUMNA IZQUIERDA: Menú lateral
// =========================================================================
echo html_writer::start_div('col-md-3 col-lg-2 mb-4 epicereports-sidebar');

local_epicereports_render_sidebar('courses');
echo html_writer::end_div();

// =========================================================================
// COLUMNA DERECHA: Contenido principal
// =========================================================================
echo html_writer::start_div('col-md-9 col-lg-10');

// -------------------------------------------------------------------------
// Tarjeta de filtros
// -------------------------------------------------------------------------
echo html_writer::start_div('card shadow-sm mb-4');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', get_string('filter', 'local_epicereports'), ['class' => 'mb-0']);
echo html_writer::end_div();

echo html_writer::start_div('card-body');

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $PAGE->url->out_omit_querystring(),
    'class' => 'form-inline'
]);

// Filtro por categoría.
echo html_writer::start_div('form-group mr-3 mb-2');
echo html_writer::label(get_string('category', 'local_epicereports'), 'category_filter', true, ['class' => 'mr-2']);
echo html_writer::select(
    $categories,
    'category',
    $category_filter,
    null,
    [
        'id' => 'category_filter',
        'class' => 'form-control'
    ]
);
echo html_writer::end_div();

// Filtro por visibilidad.
echo html_writer::start_div('form-group mr-3 mb-2');
echo html_writer::label(get_string('visibility', 'local_epicereports'), 'visible_filter', true, ['class' => 'mr-2']);

$visibility_options = [
    -1 => get_string('all', 'local_epicereports'),
    1 => get_string('visible', 'local_epicereports'),
    0 => get_string('hidden', 'local_epicereports')
];

echo html_writer::select(
    $visibility_options,
    'visible',
    $visible_filter,
    null,
    [
        'id' => 'visible_filter',
        'class' => 'form-control'
    ]
);
echo html_writer::end_div();

// Botón de filtrar.
echo html_writer::start_div('form-group mb-2');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'value' => get_string('filter', 'local_epicereports')
]);
echo html_writer::end_div();

// Botón de limpiar filtros.
if ($category_filter > 0 || $visible_filter !== -1) {
    echo html_writer::start_div('form-group mb-2 ml-2');
    echo html_writer::link(
        new moodle_url('/local/epicereports/courses.php'),
        get_string('clear', 'moodle'),
        ['class' => 'btn btn-outline-secondary']
    );
    echo html_writer::end_div();
}

echo html_writer::end_tag('form');

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// -------------------------------------------------------------------------
// Información de resultados
// -------------------------------------------------------------------------
$total_courses = count($courses_list);
echo html_writer::div(
    html_writer::tag('small', 
        get_string('courses', 'local_epicereports') . ': ' . 
        html_writer::tag('strong', $total_courses),
        ['class' => 'text-muted']
    ),
    'mb-3'
);

// -------------------------------------------------------------------------
// Tabla de cursos
// -------------------------------------------------------------------------
echo html_writer::start_div('card shadow-sm');
echo html_writer::start_div('card-body p-0');

if (empty($courses_list)) {
    echo html_writer::div(
        $OUTPUT->notification(get_string('nocourses', 'local_epicereports'), 'info'),
        'p-3'
    );
} else {
    echo html_writer::start_div('table-responsive p-3');

    echo html_writer::start_tag('table', [
        'id' => 'courses-table',
        'class' => 'table table-striped table-bordered table-hover mb-0',
        'style' => 'width: 100%'
    ]);

    // Cabeceras.
    echo html_writer::start_tag('thead', ['class' => 'thead-light']);
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', 'ID', ['style' => 'width: 60px;']);
    echo html_writer::tag('th', get_string('course', 'local_epicereports'));
    echo html_writer::tag('th', get_string('shortname', 'local_epicereports'), ['style' => 'width: 150px;']);
    echo html_writer::tag('th', get_string('category', 'local_epicereports'), ['style' => 'width: 150px;']);
    echo html_writer::tag('th', get_string('visibility', 'local_epicereports'), ['style' => 'width: 100px;']);
    echo html_writer::tag('th', get_string('actions', 'local_epicereports'), ['style' => 'width: 120px;']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    // Cuerpo de la tabla.
    echo html_writer::start_tag('tbody');

    foreach ($courses_list as $course) {
        echo html_writer::start_tag('tr');

        // ID.
        echo html_writer::tag('td', $course->id);

        // Nombre completo (con enlace al curso).
        $course_url = new moodle_url('/course/view.php', ['id' => $course->id]);
        $course_link = html_writer::link($course_url, format_string($course->fullname), [
            'target' => '_blank',
            'title' => get_string('viewcourse', 'moodle')
        ]);
        echo html_writer::tag('td', $course_link);

        // Nombre corto.
        echo html_writer::tag('td', format_string($course->shortname));

        // Categoría.
        $category_name = '-';
        if (!empty($course->category) && isset($categories[$course->category])) {
            $category_name = $categories[$course->category];
        } else if (!empty($course->category)) {
            global $DB;
            $cat = $DB->get_field('course_categories', 'name', ['id' => $course->category]);
            $category_name = $cat ? format_string($cat) : '-';
        }
        echo html_writer::tag('td', $category_name);

        // Visibilidad con badge.
        if ($course->visible) {
            $visible_badge = html_writer::tag('span', 
                get_string('yes', 'local_epicereports'), 
                ['class' => 'badge badge-success']
            );
        } else {
            $visible_badge = html_writer::tag('span', 
                get_string('no', 'local_epicereports'), 
                ['class' => 'badge badge-secondary']
            );
        }
        echo html_writer::tag('td', $visible_badge, ['class' => 'text-center']);

        // Acciones.
        $detail_url = new moodle_url('/local/epicereports/course_detail.php', ['id' => $course->id]);
        $detail_link = html_writer::link($detail_url, 
            html_writer::tag('i', '', ['class' => 'fa fa-chart-bar mr-1']) . 
            get_string('viewdetail', 'local_epicereports'), 
            ['class' => 'btn btn-sm btn-outline-primary']
        );
        echo html_writer::tag('td', $detail_link, ['class' => 'text-center']);

        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::end_div(); // table-responsive
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Cierre columna derecha.
echo html_writer::end_div(); // col-md-9 col-lg-10

// Cierre fila principal.
echo html_writer::end_div(); // row

echo $OUTPUT->footer();