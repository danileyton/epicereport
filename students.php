<?php
/**
 * Listado de alumnos - Funcionalidad completa
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\student_helper;

require_login();

$context = context_system::instance();
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/students.php'));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('studentslist', 'local_epicereports'));
$PAGE->set_heading(get_string('studentslist', 'local_epicereports'));

// Get filter parameters.
$search = optional_param('search', '', PARAM_TEXT);
$cohortid = optional_param('cohort', 0, PARAM_INT);
$company = optional_param('company', '', PARAM_TEXT);
$datefrom = optional_param('datefrom', '', PARAM_TEXT);
$dateto = optional_param('dateto', '', PARAM_TEXT);

// Convert dates to timestamps.
$datefrom_ts = !empty($datefrom) ? strtotime($datefrom) : 0;
$dateto_ts = !empty($dateto) ? strtotime($dateto . ' 23:59:59') : 0;

// Get filter options.
$cohorts = student_helper::get_cohorts_for_filter();
$companies = student_helper::get_companies_for_filter();

// Get students.
$students = student_helper::get_students($search, $cohortid, $company, $datefrom_ts, $dateto_ts);
$totalcount = count($students);

// DataTables JS.
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
        $('#students-table').DataTable({
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
    --epice-danger: #ef4444;
    --epice-info: #3b82f6;
    --epice-info-bg: rgba(59, 130, 246, 0.1);
    --epice-bg-card: #ffffff;
    --epice-bg-sidebar: linear-gradient(180deg, #1e3a5f 0%, #0f2744 100%);
    --epice-bg-header: linear-gradient(135deg, #1e3a5f 0%, #2d5a8a 100%);
    --epice-bg-table-header: #f1f5f9;
    --epice-bg-table-stripe: #f8fafc;
    --epice-text-primary: #1e293b;
    --epice-text-secondary: #64748b;
    --epice-text-muted: #94a3b8;
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

.epice-sidebar { background: var(--epice-bg-sidebar); border-radius: var(--epice-radius-lg); padding: 16px; box-shadow: var(--epice-shadow-md); }
.epice-sidebar-header { padding: 16px; margin-bottom: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); text-align: center; }
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
.epice-card-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
.epice-card-body { padding: 24px; }

.epice-filters { background: var(--epice-bg-table-stripe); padding: 20px; border-radius: var(--epice-radius); margin-bottom: 24px; }
.epice-filters-title { font-size: 0.875rem; font-weight: 600; color: var(--epice-text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.epice-filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
.epice-filter-group { display: flex; flex-direction: column; gap: 6px; }
.epice-filter-label { font-size: 0.75rem; font-weight: 600; color: var(--epice-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.epice-filter-input { padding: 10px 12px; border: 1px solid var(--epice-border); border-radius: var(--epice-radius); font-size: 0.875rem; transition: var(--epice-transition); }
.epice-filter-input:focus { outline: none; border-color: var(--epice-accent); box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }
.epice-filter-actions { display: flex; gap: 8px; align-items: flex-end; }

.epice-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; font-size: 0.875rem; font-weight: 600; border-radius: var(--epice-radius); border: none; cursor: pointer; transition: var(--epice-transition); text-decoration: none !important; }
.epice-btn:hover { transform: translateY(-1px); }
.epice-btn-primary { background: var(--epice-primary); color: var(--epice-text-inverse) !important; }
.epice-btn-primary:hover { background: var(--epice-primary-light); box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3); }
.epice-btn-success { background: var(--epice-success); color: var(--epice-text-inverse) !important; }
.epice-btn-outline { background: transparent; color: var(--epice-text-secondary) !important; border: 1px solid var(--epice-border); }
.epice-btn-outline:hover { background: var(--epice-bg-table-header); color: var(--epice-text-primary) !important; }
.epice-btn-sm { padding: 6px 12px; font-size: 0.8rem; }

.epice-table-container { overflow-x: auto; }
table.epice-table { width: 100%; border-collapse: separate; border-spacing: 0; }
table.epice-table thead th { background: var(--epice-bg-table-header); color: var(--epice-text-primary); font-weight: 600; font-size: 0.8rem; padding: 14px 16px; text-align: left; border-bottom: 2px solid var(--epice-border); text-transform: uppercase; letter-spacing: 0.03em; }
table.epice-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--epice-border-light); color: var(--epice-text-primary); font-size: 0.875rem; vertical-align: middle; }
table.epice-table tbody tr:hover { background: rgba(14, 165, 233, 0.04); }
table.epice-table tbody tr:nth-child(even) { background: var(--epice-bg-table-stripe); }

.epice-badge { display: inline-flex; align-items: center; padding: 4px 10px; font-size: 0.75rem; font-weight: 600; border-radius: 4px; }
.epice-badge-info { background: var(--epice-info-bg); color: var(--epice-info); }

.epice-stat { display: flex; align-items: center; gap: 8px; padding: 12px 16px; background: var(--epice-info-bg); border-radius: var(--epice-radius); color: var(--epice-info); font-weight: 600; }

.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding: 16px; color: var(--epice-text-secondary); font-size: 0.875rem; }
.dataTables_wrapper .dataTables_filter input { padding: 8px 12px; border: 1px solid var(--epice-border); border-radius: var(--epice-radius); margin-left: 8px; }
.dataTables_wrapper .dataTables_filter input:focus { outline: none; border-color: var(--epice-accent); box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }
.dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 4px; border: 1px solid var(--epice-border) !important; background: var(--epice-bg-card) !important; color: var(--epice-text-secondary) !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--epice-bg-table-header) !important; color: var(--epice-text-primary) !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--epice-accent) !important; color: var(--epice-text-inverse) !important; border-color: var(--epice-accent) !important; }

@media (max-width: 768px) {
    .epice-filters-grid { grid-template-columns: 1fr; }
    .epice-sidebar { margin-bottom: 24px; }
}
</style>';

// Layout principal.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('students');
echo html_writer::end_div();

// Contenido principal.
echo html_writer::start_div('col-md-9 col-lg-10');

// Card principal.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-user-graduate']) . ' ' . get_string('studentslist', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');

// Filtros.
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'epice-filters']);
echo html_writer::tag('div', 
    html_writer::tag('i', '', ['class' => 'fa fa-filter']) . ' ' . get_string('filterby', 'local_epicereports'),
    ['class' => 'epice-filters-title']
);

echo html_writer::start_div('epice-filters-grid');

// Search.
echo html_writer::start_div('epice-filter-group');
echo html_writer::tag('label', get_string('searchbyname', 'local_epicereports'), ['class' => 'epice-filter-label']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'search',
    'value' => $search,
    'class' => 'epice-filter-input',
    'placeholder' => get_string('searchstudent', 'local_epicereports')
]);
echo html_writer::end_div();

// Cohort.
echo html_writer::start_div('epice-filter-group');
echo html_writer::tag('label', get_string('cohort', 'local_epicereports'), ['class' => 'epice-filter-label']);
echo html_writer::select($cohorts, 'cohort', $cohortid, null, ['class' => 'epice-filter-input']);
echo html_writer::end_div();

// Company.
echo html_writer::start_div('epice-filter-group');
echo html_writer::tag('label', get_string('company', 'local_epicereports'), ['class' => 'epice-filter-label']);
echo html_writer::select($companies, 'company', $company, null, ['class' => 'epice-filter-input']);
echo html_writer::end_div();

// Date from.
echo html_writer::start_div('epice-filter-group');
echo html_writer::tag('label', get_string('datefrom', 'local_epicereports'), ['class' => 'epice-filter-label']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'datefrom',
    'value' => $datefrom,
    'class' => 'epice-filter-input'
]);
echo html_writer::end_div();

// Date to.
echo html_writer::start_div('epice-filter-group');
echo html_writer::tag('label', get_string('dateto', 'local_epicereports'), ['class' => 'epice-filter-label']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'dateto',
    'value' => $dateto,
    'class' => 'epice-filter-input'
]);
echo html_writer::end_div();

// Actions.
echo html_writer::start_div('epice-filter-actions');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('filter', 'local_epicereports'),
    'class' => 'epice-btn epice-btn-primary'
]);
$clearurl = new moodle_url('/local/epicereports/students.php');
echo html_writer::link($clearurl, get_string('clear', 'local_epicereports'), ['class' => 'epice-btn epice-btn-outline']);
echo html_writer::end_div();

echo html_writer::end_div(); // filters-grid
echo html_writer::end_tag('form');

// Stats.
echo html_writer::start_div('mb-4');
echo html_writer::tag('div', 
    html_writer::tag('i', '', ['class' => 'fa fa-users']) . ' ' . 
    get_string('results', 'local_epicereports') . ': ' . $totalcount . ' ' . get_string('students', 'local_epicereports'),
    ['class' => 'epice-stat']
);
echo html_writer::end_div();

// Table.
if (empty($students)) {
    echo $OUTPUT->notification(get_string('nostudents', 'local_epicereports'), 'info');
} else {
    echo html_writer::start_div('epice-table-container');
    
    echo html_writer::start_tag('table', [
        'id' => 'students-table',
        'class' => 'epice-table',
        'style' => 'width: 100%'
    ]);

    // Headers.
    $headers = [
        'ID',
        get_string('fullname', 'local_epicereports'),
        get_string('email', 'local_epicereports'),
        get_string('idnumber', 'local_epicereports'),
        get_string('registrationdate', 'local_epicereports'),
        get_string('lastaccess', 'local_epicereports'),
        get_string('actions', 'local_epicereports')
    ];

    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    foreach ($headers as $header) {
        echo html_writer::tag('th', $header);
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');

    foreach ($students as $student) {
        echo html_writer::start_tag('tr');

        // ID.
        echo html_writer::tag('td', $student->id);

        // Full name.
        $fullname = fullname($student);
        echo html_writer::tag('td', s($fullname), ['style' => 'font-weight: 500;']);

        // Email.
        echo html_writer::tag('td', s($student->email));

        // ID Number.
        $idnumber = !empty($student->idnumber) ? $student->idnumber : '-';
        echo html_writer::tag('td', s($idnumber));

        // Registration date.
        $regdate = $student->timecreated ? userdate($student->timecreated, '%d/%m/%Y') : '-';
        echo html_writer::tag('td', $regdate);

        // Last access.
        $lastaccess = $student->lastaccess ? userdate($student->lastaccess, '%d/%m/%Y %H:%M') : '-';
        echo html_writer::tag('td', $lastaccess);

        // Actions.
        $detailurl = new moodle_url('/local/epicereports/student_detail.php', ['id' => $student->id]);
        $actions = html_writer::link($detailurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-eye']) . ' ' . get_string('viewstudent', 'local_epicereports'),
            ['class' => 'epice-btn epice-btn-primary epice-btn-sm']
        );
        echo html_writer::tag('td', $actions);

        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div(); // table-container
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
