<?php
/**
 * Historial de envíos de reportes - Diseño mejorado
 *
 * @package    local_epicereports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\schedule_manager;

$courseid = required_param('courseid', PARAM_INT);
$status_filter = optional_param('status', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/schedule_logs.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('reportlogs', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

$perpage = 25;

// CORREGIDO: Usar los métodos correctos get_logs_by_course y count_logs_by_course
$status_param = !empty($status_filter) ? $status_filter : null;
$logs = schedule_manager::get_logs_by_course($courseid, $perpage, $page * $perpage, $status_param);
$total_logs = schedule_manager::count_logs_by_course($courseid, $status_param);

echo $OUTPUT->header();

echo '<style>
:root {
    --epice-primary: #1e3a5f; --epice-primary-light: #2d5a8a; --epice-accent: #0ea5e9;
    --epice-success: #10b981; --epice-success-bg: rgba(16, 185, 129, 0.1);
    --epice-warning: #f59e0b; --epice-warning-bg: rgba(245, 158, 11, 0.1);
    --epice-danger: #ef4444; --epice-danger-bg: rgba(239, 68, 68, 0.1);
    --epice-info: #3b82f6; --epice-info-bg: rgba(59, 130, 246, 0.1);
    --epice-bg-card: #ffffff; --epice-bg-sidebar: linear-gradient(180deg, #1e3a5f 0%, #0f2744 100%);
    --epice-bg-header: linear-gradient(135deg, #1e3a5f 0%, #2d5a8a 100%);
    --epice-bg-table-header: #f1f5f9; --epice-bg-table-stripe: #f8fafc;
    --epice-text-primary: #1e293b; --epice-text-secondary: #64748b; --epice-text-inverse: #ffffff;
    --epice-border: #e2e8f0; --epice-border-light: #f1f5f9;
    --epice-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); --epice-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --epice-radius: 8px; --epice-radius-md: 12px; --epice-radius-lg: 16px; --epice-transition: all 0.2s ease-in-out;
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
.epice-nav-separator { padding: 12px 16px 4px 16px; }
.epice-nav-separator-text { color: rgba(255, 255, 255, 0.4); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; }
.epice-card { background: var(--epice-bg-card); border-radius: var(--epice-radius-md); box-shadow: var(--epice-shadow); border: 1px solid var(--epice-border-light); margin-bottom: 24px; overflow: hidden; }
.epice-card-header { background: var(--epice-bg-header); padding: 16px 24px; }
.epice-card-header-light { background: var(--epice-bg-table-header); padding: 16px 24px; border-bottom: 1px solid var(--epice-border); }
.epice-card-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
.epice-card-title-dark { color: var(--epice-text-primary); }
.epice-card-body { padding: 24px; }
.epice-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; font-size: 0.875rem; font-weight: 600; border-radius: var(--epice-radius); border: none; cursor: pointer; transition: var(--epice-transition); text-decoration: none !important; }
.epice-btn-primary { background: var(--epice-primary); color: var(--epice-text-inverse) !important; }
.epice-btn-outline { background: transparent; color: var(--epice-text-secondary) !important; border: 1px solid var(--epice-border); }
.epice-badge { display: inline-flex; align-items: center; padding: 4px 10px; font-size: 0.75rem; font-weight: 600; border-radius: 4px; }
.epice-badge-success { background: var(--epice-success-bg); color: var(--epice-success); }
.epice-badge-warning { background: var(--epice-warning-bg); color: #b45309; }
.epice-badge-danger { background: var(--epice-danger-bg); color: var(--epice-danger); }
.epice-badge-secondary { background: rgba(100, 116, 139, 0.1); color: var(--epice-text-secondary); }
.epice-filter-form { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
.epice-form-group { display: flex; flex-direction: column; gap: 4px; }
.epice-form-label { font-size: 0.7rem; font-weight: 600; color: var(--epice-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.epice-form-select { padding: 10px 16px; font-size: 0.875rem; border: 1px solid var(--epice-border); border-radius: var(--epice-radius); background: var(--epice-bg-card); color: var(--epice-text-primary); min-width: 180px; }
.epice-results-count { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: var(--epice-info-bg); border-radius: var(--epice-radius); font-size: 0.875rem; color: var(--epice-info); font-weight: 500; margin-bottom: 16px; }
.epice-table-container { overflow-x: auto; padding: 16px; }
table.epice-table { width: 100%; border-collapse: separate; border-spacing: 0; }
table.epice-table thead th { background: var(--epice-bg-table-header); color: var(--epice-text-primary); font-weight: 600; font-size: 0.8rem; padding: 14px 16px; text-align: left; border-bottom: 2px solid var(--epice-border); text-transform: uppercase; }
table.epice-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--epice-border-light); color: var(--epice-text-primary); font-size: 0.875rem; }
table.epice-table tbody tr:hover { background: rgba(14, 165, 233, 0.04); }
table.epice-table tbody tr:nth-child(even) { background: var(--epice-bg-table-stripe); }
.epice-empty-state { text-align: center; padding: 48px 24px; color: var(--epice-text-secondary); }
.epice-empty-state i { font-size: 3rem; margin-bottom: 16px; opacity: 0.5; }
@media (max-width: 768px) { .epice-sidebar { margin-bottom: 24px; } .epice-filter-form { flex-direction: column; } .epice-form-select { width: 100%; } }
</style>';

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('logs', $course);
echo html_writer::end_div();

echo html_writer::start_div('col-md-9 col-lg-10');

// Filtros.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header-light');
echo html_writer::tag('h5', html_writer::tag('i', '', ['class' => 'fa fa-filter']) . ' ' . get_string('filter', 'local_epicereports'), ['class' => 'epice-card-title epice-card-title-dark']);
echo html_writer::end_div();
echo html_writer::start_div('epice-card-body');

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'epice-filter-form']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', get_string('status', 'local_epicereports'), ['class' => 'epice-form-label']);
$status_options = ['' => get_string('all', 'local_epicereports'), 'sent' => get_string('sent', 'local_epicereports'), 'failed' => get_string('failed', 'local_epicereports'), 'pending' => get_string('pending', 'local_epicereports')];
echo html_writer::select($status_options, 'status', $status_filter, null, ['class' => 'epice-form-select']);
echo html_writer::end_div();

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', '&nbsp;', ['class' => 'epice-form-label']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'epice-btn epice-btn-primary', 'value' => get_string('filter', 'local_epicereports')]);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// Resultados.
echo html_writer::div(html_writer::tag('i', '', ['class' => 'fa fa-list']) . ' ' . get_string('results', 'local_epicereports') . ': ' . html_writer::tag('strong', $total_logs), 'epice-results-count');

// Tabla de logs.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', html_writer::tag('i', '', ['class' => 'fa fa-history']) . ' ' . get_string('reportlogs', 'local_epicereports'), ['class' => 'epice-card-title']);
echo html_writer::end_div();

if (empty($logs)) {
    echo html_writer::start_div('epice-card-body');
    echo html_writer::start_div('epice-empty-state');
    echo html_writer::tag('i', '', ['class' => 'fa fa-inbox']);
    echo html_writer::tag('p', get_string('nologs', 'local_epicereports'));
    echo html_writer::end_div();
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('epice-table-container');
    echo html_writer::start_tag('table', ['class' => 'epice-table']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('timescheduled', 'local_epicereports'));
    echo html_writer::tag('th', get_string('recipient', 'local_epicereports'));
    echo html_writer::tag('th', get_string('status', 'local_epicereports'));
    echo html_writer::tag('th', get_string('timesent', 'local_epicereports'));
    echo html_writer::tag('th', get_string('retries', 'local_epicereports'));
    echo html_writer::tag('th', get_string('error', 'local_epicereports'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($logs as $log) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', userdate($log->timescheduled, '%Y-%m-%d %H:%M'));
        echo html_writer::tag('td', s($log->recipientemail));
        
        $badge_type = 'secondary';
        if ($log->status === 'sent') $badge_type = 'success';
        else if ($log->status === 'failed') $badge_type = 'danger';
        else if ($log->status === 'pending') $badge_type = 'warning';
        echo html_writer::tag('td', local_epicereports_render_badge(get_string($log->status, 'local_epicereports'), $badge_type));
        
        $timesent = $log->timesent ? userdate($log->timesent, '%Y-%m-%d %H:%M') : '-';
        echo html_writer::tag('td', $timesent);
        echo html_writer::tag('td', $log->retrycount ?? 0);
        
        // CORREGIDO: usar errormessage en lugar de error
        $error_msg = !empty($log->errormessage) ? s($log->errormessage) : '-';
        echo html_writer::tag('td', $error_msg);
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();

    // Paginación.
    if ($total_logs > $perpage) {
        $baseurl = new moodle_url('/local/epicereports/schedule_logs.php', ['courseid' => $courseid, 'status' => $status_filter]);
        echo $OUTPUT->paging_bar($total_logs, $page, $perpage, $baseurl);
    }
}

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
