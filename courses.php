<?php
/**
 * Listado de cursos - Diseño mejorado con CSS embebido
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

// CSS embebido.
echo '<style>
:root {
    --epice-primary: #1e3a5f;
    --epice-primary-light: #2d5a8a;
    --epice-accent: #0ea5e9;
    --epice-success: #10b981;
    --epice-success-bg: rgba(16, 185, 129, 0.1);
    --epice-warning: #f59e0b;
    --epice-warning-bg: rgba(245, 158, 11, 0.1);
    --epice-info: #3b82f6;
    --epice-info-bg: rgba(59, 130, 246, 0.1);
    --epice-bg-card: #ffffff;
    --epice-bg-sidebar: linear-gradient(180deg, #1e3a5f 0%, #0f2744 100%);
    --epice-bg-header: linear-gradient(135deg, #1e3a5f 0%, #2d5a8a 100%);
    --epice-bg-table-header: #f1f5f9;
    --epice-bg-table-stripe: #f8fafc;
    --epice-text-primary: #1e293b;
    --epice-text-secondary: #64748b;
    --epice-text-inverse: #ffffff;
    --epice-border: #e2e8f0;
    --epice-border-light: #f1f5f9;
    --epice-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --epice-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --epice-radius: 8px;
    --epice-radius-md: 12px;
    --epice-radius-lg: 16px;
    --epice-transition: all 0.2s ease-in-out;
}

.epice-sidebar {
    background: var(--epice-bg-sidebar);
    border-radius: var(--epice-radius-lg);
    padding: 16px;
    box-shadow: var(--epice-shadow-md);
}

.epice-sidebar-header {
    padding: 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.epice-sidebar-logo { font-size: 1.5rem; margin-bottom: 4px; }
.epice-sidebar-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 700; margin: 0; }
.epice-sidebar-subtitle { color: rgba(255, 255, 255, 0.6); font-size: 0.7rem; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.1em; }

.epice-nav { list-style: none; padding: 0; margin: 0; }
.epice-nav-item { margin-bottom: 4px; }
.epice-nav-link { display: flex; align-items: center; padding: 10px 16px; color: rgba(255, 255, 255, 0.8) !important; text-decoration: none !important; border-radius: var(--epice-radius); transition: var(--epice-transition); font-size: 0.9rem; font-weight: 500; }
.epice-nav-link:hover { background-color: rgba(255, 255, 255, 0.1); color: var(--epice-text-inverse) !important; }
.epice-nav-link.active { background-color: var(--epice-accent); color: var(--epice-text-inverse) !important; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4); }
.epice-nav-icon { margin-right: 10px; width: 20px; text-align: center; }

.epice-card { background: var(--epice-bg-card); border-radius: var(--epice-radius-md); box-shadow: var(--epice-shadow); border: 1px solid var(--epice-border-light); margin-bottom: 24px; overflow: hidden; }
.epice-card-header { background: var(--epice-bg-header); padding: 16px 24px; }
.epice-card-header-light { background: var(--epice-bg-table-header); padding: 16px 24px; border-bottom: 1px solid var(--epice-border); }
.epice-card-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
.epice-card-title-dark { color: var(--epice-text-primary); }
.epice-card-body { padding: 24px; }

.epice-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; font-size: 0.875rem; font-weight: 600; border-radius: var(--epice-radius); border: none; cursor: pointer; transition: var(--epice-transition); text-decoration: none !important; }
.epice-btn:hover { transform: translateY(-1px); }
.epice-btn-primary { background: var(--epice-primary); color: var(--epice-text-inverse) !important; }
.epice-btn-primary:hover { background: var(--epice-primary-light); box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3); }
.epice-btn-outline { background: transparent; color: var(--epice-text-secondary) !important; border: 1px solid var(--epice-border); }
.epice-btn-outline:hover { background: var(--epice-bg-table-header); color: var(--epice-text-primary) !important; }
.epice-btn-group { display: flex; gap: 8px; flex-wrap: wrap; }

.epice-badge { display: inline-flex; align-items: center; padding: 4px 10px; font-size: 0.75rem; font-weight: 600; border-radius: 4px; }
.epice-badge-success { background: var(--epice-success-bg); color: var(--epice-success); }
.epice-badge-secondary { background: rgba(100, 116, 139, 0.1); color: var(--epice-text-secondary); }

.epice-filter-form { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
.epice-form-group { display: flex; flex-direction: column; gap: 4px; }
.epice-form-label { font-size: 0.7rem; font-weight: 600; color: var(--epice-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.epice-form-select { padding: 10px 16px; font-size: 0.875rem; border: 1px solid var(--epice-border); border-radius: var(--epice-radius); background: var(--epice-bg-card); color: var(--epice-text-primary); min-width: 180px; }
.epice-form-select:focus { outline: none; border-color: var(--epice-accent); box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }

.epice-results-count { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: var(--epice-info-bg); border-radius: var(--epice-radius); font-size: 0.875rem; color: var(--epice-info); font-weight: 500; margin-bottom: 16px; }

.epice-table-container { overflow-x: auto; padding: 16px; }
table.epice-table { width: 100%; border-collapse: separate; border-spacing: 0; }
table.epice-table thead th { background: var(--epice-bg-table-header); color: var(--epice-text-primary); font-weight: 600; font-size: 0.8rem; padding: 14px 16px; text-align: left; border-bottom: 2px solid var(--epice-border); text-transform: uppercase; letter-spacing: 0.03em; }
table.epice-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--epice-border-light); color: var(--epice-text-primary); font-size: 0.875rem; }
table.epice-table tbody tr:hover { background: rgba(14, 165, 233, 0.04); }
table.epice-table tbody tr:nth-child(even) { background: var(--epice-bg-table-stripe); }

.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding: 16px; color: var(--epice-text-secondary); font-size: 0.875rem; }
.dataTables_wrapper .dataTables_filter input { padding: 8px 12px; border: 1px solid var(--epice-border); border-radius: var(--epice-radius); margin-left: 8px; }
.dataTables_wrapper .dataTables_filter input:focus { outline: none; border-color: var(--epice-accent); box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }
.dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 4px; border: 1px solid var(--epice-border) !important; background: var(--epice-bg-card) !important; color: var(--epice-text-secondary) !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--epice-bg-table-header) !important; color: var(--epice-text-primary) !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--epice-primary) !important; color: var(--epice-text-inverse) !important; border-color: var(--epice-primary) !important; }

@media (max-width: 768px) {
    .epice-sidebar { margin-bottom: 24px; }
    .epice-filter-form { flex-direction: column; }
    .epice-form-select { width: 100%; }
    .epice-btn-group { flex-direction: column; }
    .epice-btn { width: 100%; }
}
</style>';

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
