<?php
/**
 * Historial de envíos de reportes - Con DataTables y filtro por programación
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
$scheduleid = optional_param('scheduleid', 0, PARAM_INT);
$status_filter = optional_param('status', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/schedule_logs.php', [
    'courseid' => $courseid,
    'scheduleid' => $scheduleid
]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('reportlogs', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

// Cargar estilos CSS ANTES de $OUTPUT->header().
local_epicereports_include_styles();

// Cargar DataTables.
local_epicereports_include_datatables('logs-table', [
    'pageLength' => 25,
    'order' => [[0, 'desc']],
    'orderableExclude' => -1
]);

$perpage = 50;

// Load schedule if specified.
$schedule = null;
if ($scheduleid) {
    $schedule = schedule_manager::get_schedule($scheduleid);
    if (!$schedule || $schedule->courseid != $courseid) {
        $schedule = null;
        $scheduleid = 0;
    }
}

// Get logs - filtrar por scheduleid si está presente.
$status_param = !empty($status_filter) ? $status_filter : null;

if ($scheduleid) {
    $logs = schedule_manager::get_logs_by_schedule($scheduleid, $perpage, $page * $perpage, $status_param);
    $total_logs = schedule_manager::count_logs_by_schedule($scheduleid, $status_param);
} else {
    $logs = schedule_manager::get_logs_by_course($courseid, $perpage, $page * $perpage, $status_param);
    $total_logs = schedule_manager::count_logs_by_course($courseid, $status_param);
}

// Get schedule names for display.
$scheduleids = array_unique(array_column($logs, 'scheduleid'));
$schedulenames = [];
if (!empty($scheduleids)) {
    global $DB;
    list($insql, $params) = $DB->get_in_or_equal($scheduleids);
    $schedulesdata = $DB->get_records_select('local_epicereports_schedules', "id $insql", $params, '', 'id, name');
    foreach ($schedulesdata as $s) {
        $schedulenames[$s->id] = $s->name;
    }
}

echo $OUTPUT->header();

// Layout.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('schedules', $course);
echo html_writer::end_div();

// Main content.
echo html_writer::start_div('col-md-9 col-lg-10');

// Card.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-history']) . ' ' . get_string('reportlogs', 'local_epicereports'),
    ['class' => 'epice-card-title']
);

// Back button.
$backurl = new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]);
echo html_writer::link($backurl, 
    html_writer::tag('i', '', ['class' => 'fa fa-arrow-left']) . ' ' . get_string('back', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-outline epice-btn-sm']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');

// Filter info if viewing specific schedule.
if ($schedule) {
    echo html_writer::start_div('epice-filter-info');
    echo html_writer::tag('i', '', ['class' => 'fa fa-filter']);
    echo get_string('filteringbyschedule', 'local_epicereports') . ': <strong>' . s($schedule->name) . '</strong>';
    
    $clearurl = new moodle_url('/local/epicereports/schedule_logs.php', ['courseid' => $courseid]);
    echo ' ' . html_writer::link($clearurl, get_string('clearfilter', 'local_epicereports'), ['style' => 'margin-left: 12px;']);
    echo html_writer::end_div();
}

// Status filter form.
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'mb-4', 'style' => 'display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
if ($scheduleid) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'scheduleid', 'value' => $scheduleid]);
}

echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 4px;']);
echo html_writer::tag('label', get_string('status', 'local_epicereports'), ['style' => 'font-size: 0.75rem; font-weight: 600; color: var(--epice-text-secondary); text-transform: uppercase;']);
$status_options = [
    '' => get_string('all', 'local_epicereports'), 
    'sent' => get_string('sent', 'local_epicereports'), 
    'failed' => get_string('failed', 'local_epicereports'), 
    'pending' => get_string('pending', 'local_epicereports')
];
echo html_writer::select($status_options, 'status', $status_filter, null, ['class' => 'epice-filter-input', 'style' => 'padding: 8px 12px; border: 1px solid var(--epice-border); border-radius: 8px;']);
echo html_writer::end_div();

echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'epice-btn epice-btn-primary epice-btn-sm', 'value' => get_string('filter', 'local_epicereports')]);

echo html_writer::end_tag('form');

// Results count.
echo html_writer::start_div('epice-filter-info', ['style' => 'margin-bottom: 16px;']);
echo html_writer::tag('i', '', ['class' => 'fa fa-list']);
echo get_string('results', 'local_epicereports') . ': <strong>' . $total_logs . '</strong>';
echo html_writer::end_div();

if (empty($logs)) {
    // Empty state.
    echo html_writer::start_div('epice-empty-state');
    echo html_writer::tag('i', '', ['class' => 'fa fa-inbox']);
    echo html_writer::tag('h4', get_string('nologs', 'local_epicereports'));
    echo html_writer::tag('p', get_string('nologsdesc', 'local_epicereports'));
    echo html_writer::end_div();
} else {
    // Table.
    echo html_writer::start_div('epice-table-container');
    echo html_writer::start_tag('table', ['class' => 'epice-table', 'id' => 'logs-table']);
    
    // Headers.
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('date', 'local_epicereports'));
    if (!$scheduleid) {
        echo html_writer::tag('th', get_string('schedule', 'local_epicereports'));
    }
    echo html_writer::tag('th', get_string('recipient', 'local_epicereports'));
    echo html_writer::tag('th', get_string('status', 'local_epicereports'));
    echo html_writer::tag('th', get_string('timesent', 'local_epicereports'));
    echo html_writer::tag('th', get_string('attachments', 'local_epicereports'));
    echo html_writer::tag('th', get_string('error', 'local_epicereports'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    
    foreach ($logs as $log) {
        echo html_writer::start_tag('tr');
        
        // Date.
        $date = userdate($log->timescheduled, '%d/%m/%Y %H:%M');
        echo html_writer::tag('td', $date);
        
        // Schedule name (only if not filtering by schedule).
        if (!$scheduleid) {
            $sname = $schedulenames[$log->scheduleid] ?? '-';
            echo html_writer::tag('td', s($sname));
        }
        
        // Recipient.
        echo html_writer::tag('td', s($log->recipientemail));
        
        // Status.
        $badge_type = 'secondary';
        $status_text = $log->status;
        
        if ($log->status === 'sent' || $log->status === 'success') {
            $badge_type = 'success';
            $status_text = 'sent';
        } else if ($log->status === 'failed' || $log->status === 'error') {
            $badge_type = 'danger';
            $status_text = 'failed';
        } else if ($log->status === 'pending') {
            $badge_type = 'warning';
            $status_text = 'pending';
        }
        echo html_writer::tag('td', local_epicereports_render_badge(get_string($status_text, 'local_epicereports'), $badge_type));
        
        // Time sent.
        $timesent = $log->timesent ? userdate($log->timesent, '%d/%m/%Y %H:%M') : '-';
        echo html_writer::tag('td', $timesent);
        
        // Attachments.
        $attachments = '-';
        if (!empty($log->attachments)) {
            $attachments = html_writer::tag('small', s(substr($log->attachments, 0, 50)), ['title' => s($log->attachments)]);
        }
        echo html_writer::tag('td', $attachments);
        
        // Error.
        $error_msg = '-';
        if (!empty($log->errormessage)) {
            $error_msg = html_writer::tag('small', s(substr($log->errormessage, 0, 50)), 
                ['class' => 'text-danger', 'title' => s($log->errormessage)]);
        }
        echo html_writer::tag('td', $error_msg);
        
        echo html_writer::end_tag('tr');
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
