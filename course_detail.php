<?php
/**
 * Página de detalle de un curso - Diseño mejorado con CSS embebido
 *
 * @package    local_epicereports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\helper;

$courseid = required_param('id', PARAM_INT);

if ($courseid <= 0) {
    throw new moodle_exception('invalidcourseid', 'error');
}

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('coursedetail', 'local_epicereports') . ': ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

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
        $('#users-table').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
            order: [[0, 'asc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
            }
        });
    });
");

$course_data = helper::get_course_data_for_excel($courseid);

echo $OUTPUT->header();

// CSS embebido.
echo '<style>
:root {
    --epice-primary: #1e3a5f;
    --epice-primary-light: #2d5a8a;
    --epice-accent: #0ea5e9;
    --epice-success: #10b981;
    --epice-success-light: #34d399;
    --epice-success-bg: rgba(16, 185, 129, 0.1);
    --epice-warning: #f59e0b;
    --epice-warning-light: #fbbf24;
    --epice-warning-bg: rgba(245, 158, 11, 0.1);
    --epice-danger: #ef4444;
    --epice-danger-light: #f87171;
    --epice-danger-bg: rgba(239, 68, 68, 0.1);
    --epice-info: #3b82f6;
    --epice-info-light: #60a5fa;
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

.epice-course-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
.epice-info-item { display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--epice-bg-table-stripe); border-radius: var(--epice-radius); }
.epice-info-icon { width: 40px; height: 40px; border-radius: var(--epice-radius); display: flex; align-items: center; justify-content: center; background: var(--epice-info-bg); color: var(--epice-info); flex-shrink: 0; }
.epice-info-content { flex: 1; min-width: 0; }
.epice-info-label { font-size: 0.7rem; color: var(--epice-text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
.epice-info-value { font-size: 0.95rem; color: var(--epice-text-primary); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.epice-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; font-size: 0.875rem; font-weight: 600; border-radius: var(--epice-radius); border: none; cursor: pointer; transition: var(--epice-transition); text-decoration: none !important; }
.epice-btn:hover { transform: translateY(-1px); }
.epice-btn-success { background: var(--epice-success); color: var(--epice-text-inverse) !important; }
.epice-btn-success:hover { background: var(--epice-success-light); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.epice-btn-info { background: var(--epice-info); color: var(--epice-text-inverse) !important; }
.epice-btn-info:hover { background: var(--epice-info-light); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.epice-btn-warning { background: var(--epice-warning); color: var(--epice-text-primary) !important; }
.epice-btn-warning:hover { background: var(--epice-warning-light); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); }
.epice-btn-outline { background: transparent; color: var(--epice-text-secondary) !important; border: 1px solid var(--epice-border); }
.epice-btn-outline:hover { background: var(--epice-bg-table-header); color: var(--epice-text-primary) !important; }
.epice-btn-group { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }

.epice-badge { display: inline-flex; align-items: center; padding: 4px 10px; font-size: 0.75rem; font-weight: 600; border-radius: 4px; }
.epice-badge-success { background: var(--epice-success-bg); color: var(--epice-success); }
.epice-badge-warning { background: var(--epice-warning-bg); color: #b45309; }
.epice-badge-secondary { background: rgba(100, 116, 139, 0.1); color: var(--epice-text-secondary); }

.epice-progress-wrapper { display: flex; align-items: center; gap: 10px; min-width: 150px; }
.epice-progress { flex: 1; height: 8px; background: var(--epice-border-light); border-radius: 20px; overflow: hidden; }
.epice-progress-bar { height: 100%; border-radius: 20px; transition: width 0.6s ease; }
.epice-progress-success { background: linear-gradient(90deg, var(--epice-success) 0%, var(--epice-success-light) 100%); }
.epice-progress-warning { background: linear-gradient(90deg, var(--epice-warning) 0%, var(--epice-warning-light) 100%); }
.epice-progress-danger { background: linear-gradient(90deg, var(--epice-danger) 0%, var(--epice-danger-light) 100%); }
.epice-progress-info { background: linear-gradient(90deg, var(--epice-info) 0%, var(--epice-info-light) 100%); }
.epice-progress-label { font-size: 0.75rem; font-weight: 600; color: var(--epice-text-secondary); min-width: 40px; }

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
    .epice-btn-group { flex-direction: column; }
    .epice-btn { width: 100%; }
}
</style>';

// Layout principal.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('course_detail', $course);
echo html_writer::end_div();

// Contenido principal.
echo html_writer::start_div('col-md-9 col-lg-10');

// Tarjeta de información del curso.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-info-circle']) . ' ' . get_string('generalinfo', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');
echo html_writer::start_div('epice-course-info');

// Nombre del curso.
echo html_writer::start_div('epice-info-item');
echo html_writer::start_div('epice-info-icon');
echo html_writer::tag('i', '', ['class' => 'fa fa-book']);
echo html_writer::end_div();
echo html_writer::start_div('epice-info-content');
echo html_writer::tag('div', get_string('course', 'local_epicereports'), ['class' => 'epice-info-label']);
echo html_writer::tag('div', format_string($course_data['course']->fullname), ['class' => 'epice-info-value']);
echo html_writer::end_div();
echo html_writer::end_div();

// Nombre corto.
echo html_writer::start_div('epice-info-item');
echo html_writer::start_div('epice-info-icon');
echo html_writer::tag('i', '', ['class' => 'fa fa-tag']);
echo html_writer::end_div();
echo html_writer::start_div('epice-info-content');
echo html_writer::tag('div', get_string('shortname', 'local_epicereports'), ['class' => 'epice-info-label']);
echo html_writer::tag('div', s($course_data['course']->shortname), ['class' => 'epice-info-value']);
echo html_writer::end_div();
echo html_writer::end_div();

// Visibilidad.
echo html_writer::start_div('epice-info-item');
$vis_icon_style = $course_data['course']->visible 
    ? 'background: rgba(16, 185, 129, 0.1); color: #10b981;' 
    : 'background: rgba(100, 116, 139, 0.1); color: #64748b;';
echo html_writer::start_div('epice-info-icon', ['style' => $vis_icon_style]);
echo html_writer::tag('i', '', ['class' => 'fa fa-eye']);
echo html_writer::end_div();
echo html_writer::start_div('epice-info-content');
echo html_writer::tag('div', get_string('visibility', 'local_epicereports'), ['class' => 'epice-info-label']);
$visibility_text = $course_data['course']->visible 
    ? get_string('visible', 'local_epicereports') 
    : get_string('hidden', 'local_epicereports');
echo html_writer::tag('div', $visibility_text, ['class' => 'epice-info-value']);
echo html_writer::end_div();
echo html_writer::end_div();

// Matriculados.
$enrolled_count = count($course_data['users']);
echo html_writer::start_div('epice-info-item');
echo html_writer::start_div('epice-info-icon', ['style' => 'background: rgba(245, 158, 11, 0.1); color: #f59e0b;']);
echo html_writer::tag('i', '', ['class' => 'fa fa-users']);
echo html_writer::end_div();
echo html_writer::start_div('epice-info-content');
echo html_writer::tag('div', get_string('enrolled', 'local_epicereports'), ['class' => 'epice-info-label']);
echo html_writer::tag('div', $enrolled_count . ' ' . get_string('students', 'local_epicereports'), ['class' => 'epice-info-value']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // course-info
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Botones de exportación.
echo html_writer::start_div('epice-btn-group');

// Excel.
$excel_url = new moodle_url('/local/epicereports/export_course_excel.php', ['courseid' => $courseid]);
echo html_writer::link($excel_url, 
    html_writer::tag('i', '', ['class' => 'fa fa-file-excel-o']) . ' ' . get_string('exporttoexcel', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-success', 'target' => '_blank']
);

// Vista previa.
$preview_url = new moodle_url('/local/epicereports/export_course_excel.php', ['courseid' => $courseid, 'preview' => 1]);
echo html_writer::link($preview_url, 
    html_writer::tag('i', '', ['class' => 'fa fa-eye']) . ' ' . get_string('preview', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-outline', 'target' => '_blank']
);

// Certificados (si existen).
$has_certificates = check_course_has_certificates($courseid);
if ($has_certificates) {
    $cert_url = new moodle_url('/local/epicereports/export_certificates.php', ['courseid' => $courseid]);
    echo html_writer::link($cert_url, 
        html_writer::tag('i', '', ['class' => 'fa fa-certificate']) . ' ' . get_string('downloadcertificates', 'local_epicereports'),
        ['class' => 'epice-btn epice-btn-info']
    );
}

// Feedback (si existe).
$has_feedback = check_course_has_feedback($courseid);
if ($has_feedback) {
    $feedback_url = new moodle_url('/local/epicereports/export_feedback.php', ['courseid' => $courseid]);
    echo html_writer::link($feedback_url, 
        html_writer::tag('i', '', ['class' => 'fa fa-comments']) . ' ' . get_string('exportfeedback', 'local_epicereports'),
        ['class' => 'epice-btn epice-btn-warning']
    );
}

// Mensajes de seguimiento.
$followup_url = new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]);
echo html_writer::link($followup_url, 
    html_writer::tag('i', '', ['class' => 'fa fa-paper-plane']) . ' ' . get_string('followupmessages', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-primary']
);

echo html_writer::end_div(); // btn-group

// Tabla de usuarios.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-users']) . ' ' . get_string('enrolledusers', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

if (empty($course_data['users'])) {
    echo html_writer::start_div('epice-card-body');
    echo $OUTPUT->notification(get_string('nousers', 'local_epicereports'), 'info');
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('epice-table-container');
    
    echo html_writer::start_tag('table', [
        'id' => 'users-table',
        'class' => 'epice-table',
        'style' => 'width: 100%'
    ]);

    // Cabeceras.
    $headers = [
        get_string('fullname', 'local_epicereports'),
        get_string('email', 'local_epicereports'),
        get_string('firstaccess', 'local_epicereports'),
        get_string('lastaccess', 'local_epicereports'),
        get_string('groups', 'local_epicereports'),
        get_string('progress', 'local_epicereports'),
        get_string('completionstatus', 'local_epicereports'),
        get_string('finalgrade', 'local_epicereports')
    ];

    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    foreach ($headers as $header) {
        echo html_writer::tag('th', $header);
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');

    foreach ($course_data['users'] as $user) {
        echo html_writer::start_tag('tr');

        // Nombre.
        echo html_writer::tag('td', s($user->fullname), ['style' => 'font-weight: 500;']);

        // Email.
        echo html_writer::tag('td', s($user->email));

        // Primer acceso.
        $primer_acceso = !empty($user->primer_acceso) ? $user->primer_acceso : '-';
        echo html_writer::tag('td', s($primer_acceso));

        // Último acceso.
        $ultimo_acceso = !empty($user->ultimo_acceso) ? $user->ultimo_acceso : '-';
        echo html_writer::tag('td', s($ultimo_acceso));

        // Grupos.
        $grupos = !empty($user->grupos) ? $user->grupos : '-';
        echo html_writer::tag('td', s($grupos));

        // Progreso con barra.
        $porcentaje = $user->porcentaje_avance ?? '0%';
        $porcentaje_num = (float)str_replace('%', '', $porcentaje);
        echo html_writer::tag('td', local_epicereports_render_progress_bar($porcentaje_num));

        // Estado.
        $estado = $user->estado_finalizacion ?? '-';
        $badge_type = 'secondary';
        if ($estado === 'Completado') {
            $badge_type = 'success';
        } else if ($estado === 'En progreso') {
            $badge_type = 'warning';
        }
        echo html_writer::tag('td', html_writer::tag('span', s($estado), ['class' => 'epice-badge epice-badge-' . $badge_type]));

        // Nota final.
        $nota_final = !empty($user->nota_final) ? $user->nota_final : '-';
        echo html_writer::tag('td', s($nota_final), ['style' => 'font-weight: 600;']);

        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div(); // table-container
}

echo html_writer::end_div(); // card

echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row

echo $OUTPUT->footer();

// Funciones auxiliares.
function check_course_has_certificates(int $courseid): bool {
    global $DB;
    $dbman = $DB->get_manager();
    
    if (!$dbman->table_exists('simplecertificate') || !$dbman->table_exists('simplecertificate_issues')) {
        return false;
    }
    
    $certificates = $DB->get_records('simplecertificate', ['course' => $courseid], '', 'id');
    if (empty($certificates)) {
        return false;
    }
    
    $certids = array_keys($certificates);
    list($insql, $params) = $DB->get_in_or_equal($certids, SQL_PARAMS_NAMED);
    
    $sql = "SELECT COUNT(*) FROM {simplecertificate_issues} WHERE certificateid $insql AND (timedeleted IS NULL OR timedeleted = 0)";
    return $DB->count_records_sql($sql, $params) > 0;
}

function check_course_has_feedback(int $courseid): bool {
    global $DB;
    $dbman = $DB->get_manager();
    
    if (!$dbman->table_exists('feedback') || !$dbman->table_exists('feedback_completed')) {
        return false;
    }
    
    $feedbacks = $DB->get_records('feedback', ['course' => $courseid], '', 'id');
    if (empty($feedbacks)) {
        return false;
    }
    
    $feedbackids = array_keys($feedbacks);
    list($insql, $params) = $DB->get_in_or_equal($feedbackids, SQL_PARAMS_NAMED);
    
    $sql = "SELECT COUNT(*) FROM {feedback_completed} WHERE feedback $insql";
    return $DB->count_records_sql($sql, $params) > 0;
}
