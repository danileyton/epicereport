<?php
/**
 * Listado de cursos con filtros - Diseño mejorado
 *
 * @package    local_epicereports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\helper;

require_login();

$context = context_system::instance();
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/courses.php'));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('courselist', 'local_epicereports'));
$PAGE->set_heading(get_string('courselist', 'local_epicereports'));

// Filtros.
$category_filter = optional_param('category', 0, PARAM_INT);
$visible_filter = optional_param('visible', -1, PARAM_INT);

$categories = helper::get_course_categories();
$courses_list = helper::get_courses_list('', $category_filter, $visible_filter);

// CSS personalizado.
$PAGE->requires->css('/local/epicereports/css/styles.css');

// jQuery y DataTables.
$PAGE->requires->jquery();
$PAGE->requires->css(new moodle_url('https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css'));

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

echo $OUTPUT->header();

// Layout principal.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('courses');
echo html_writer::end_div();

// Contenido principal.
echo html_writer::start_div('col-md-9 col-lg-10');

// Tarjeta de filtros.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header-light');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-filter']) . ' ' . get_string('filter', 'local_epicereports'), 
    ['class' => 'epice-card-title epice-card-title-dark']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $PAGE->url->out_omit_querystring(),
    'class' => 'epice-filter-form'
]);

// Filtro categoría.
echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', get_string('category', 'local_epicereports'), ['class' => 'epice-form-label']);
echo html_writer::select($categories, 'category', $category_filter, null, ['class' => 'epice-form-select']);
echo html_writer::end_div();

// Filtro visibilidad.
echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', get_string('visibility', 'local_epicereports'), ['class' => 'epice-form-label']);
$visibility_options = [
    -1 => get_string('all', 'local_epicereports'),
    1 => get_string('visible', 'local_epicereports'),
    0 => get_string('hidden', 'local_epicereports')
];
echo html_writer::select($visibility_options, 'visible', $visible_filter, null, ['class' => 'epice-form-select']);
echo html_writer::end_div();

// Botones.
echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', '&nbsp;', ['class' => 'epice-form-label']);
echo html_writer::start_div('epice-btn-group');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'epice-btn epice-btn-primary',
    'value' => get_string('filter', 'local_epicereports')
]);

if ($category_filter > 0 || $visible_filter !== -1) {
    echo html_writer::link(
        new moodle_url('/local/epicereports/courses.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-times']) . ' ' . get_string('clear', 'moodle'),
        ['class' => 'epice-btn epice-btn-outline']
    );
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// Contador de resultados.
$total_courses = count($courses_list);
echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-list']) . ' ' .
    get_string('courses', 'local_epicereports') . ': ' . html_writer::tag('strong', $total_courses),
    'epice-results-count'
);

// Tabla de cursos.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-book']) . ' ' . get_string('courselist', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

if (empty($courses_list)) {
    echo html_writer::start_div('epice-card-body');
    echo $OUTPUT->notification(get_string('nocourses', 'local_epicereports'), 'info');
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('epice-table-container');
    
    echo html_writer::start_tag('table', [
        'id' => 'courses-table',
        'class' => 'epice-table',
        'style' => 'width: 100%'
    ]);

    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', 'ID', ['style' => 'width: 60px;']);
    echo html_writer::tag('th', get_string('course', 'local_epicereports'));
    echo html_writer::tag('th', get_string('shortname', 'local_epicereports'));
    echo html_writer::tag('th', get_string('category', 'local_epicereports'));
    echo html_writer::tag('th', get_string('visibility', 'local_epicereports'), ['style' => 'text-align: center;']);
    echo html_writer::tag('th', get_string('actions', 'local_epicereports'), ['style' => 'text-align: center;']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');

    foreach ($courses_list as $course) {
        echo html_writer::start_tag('tr');

        echo html_writer::tag('td', $course->id);

        $course_url = new moodle_url('/course/view.php', ['id' => $course->id]);
        $course_link = html_writer::link($course_url, format_string($course->fullname), [
            'target' => '_blank',
            'style' => 'color: #1e3a5f; font-weight: 500;'
        ]);
        echo html_writer::tag('td', $course_link);

        echo html_writer::tag('td', format_string($course->shortname));

        $category_name = '-';
        if (!empty($course->category) && isset($categories[$course->category])) {
            $category_name = $categories[$course->category];
        }
        echo html_writer::tag('td', $category_name);

        if ($course->visible) {
            $badge = html_writer::tag('span', get_string('yes', 'local_epicereports'), ['class' => 'epice-badge epice-badge-success']);
        } else {
            $badge = html_writer::tag('span', get_string('no', 'local_epicereports'), ['class' => 'epice-badge epice-badge-secondary']);
        }
        echo html_writer::tag('td', $badge, ['style' => 'text-align: center;']);

        $detail_url = new moodle_url('/local/epicereports/course_detail.php', ['id' => $course->id]);
        $detail_link = html_writer::link($detail_url, 
            html_writer::tag('i', '', ['class' => 'fa fa-chart-bar']) . ' ' . get_string('viewdetail', 'local_epicereports'),
            ['class' => 'epice-btn epice-btn-primary', 'style' => 'padding: 6px 12px; font-size: 0.8rem;']
        );
        echo html_writer::tag('td', $detail_link, ['style' => 'text-align: center;']);

        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();