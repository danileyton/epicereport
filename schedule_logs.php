<?php
/**
 * Schedule logs page
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\schedule_manager;

// Parameters.
$courseid    = required_param('courseid', PARAM_INT);
$scheduleid  = optional_param('scheduleid', 0, PARAM_INT);
$status      = optional_param('status', '', PARAM_ALPHA);
$page        = optional_param('page', 0, PARAM_INT);
$perpage     = optional_param('perpage', 50, PARAM_INT);

// Load course and context.
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:viewlogs', $context);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/schedule_logs.php', [
    'courseid'   => $courseid,
    'scheduleid' => $scheduleid,
]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('reportlogs', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

// Add navbar.
$PAGE->navbar->add(get_string('pluginname', 'local_epicereports'),
    new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]));
$PAGE->navbar->add(get_string('scheduledreports', 'local_epicereports'),
    new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]));
$PAGE->navbar->add(get_string('reportlogs', 'local_epicereports'));

// Load schedule if specified.
$schedule = null;
if ($scheduleid) {
    $schedule = schedule_manager::get_schedule($scheduleid);
    if (!$schedule || $schedule->courseid != $courseid) {
        throw new moodle_exception('error:invalidscheduleid', 'local_epicereports');
    }
}

echo $OUTPUT->header();

$heading = get_string('reportlogs', 'local_epicereports');
if ($schedule) {
    $heading .= ': ' . s($schedule->name);
}
echo $OUTPUT->heading($heading);

// Layout with sidebar.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2');
local_epicereports_render_sidebar('logs', $course);
echo html_writer::end_div();

// Main content.
echo html_writer::start_div('col-md-9 col-lg-10');

// Back button.
$backurl = new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]);
echo html_writer::div(
    html_writer::link($backurl, '← Volver a programaciones', ['class' => 'btn btn-secondary mb-3']),
    'mb-3'
);

// =====================================================================
// Filters
// =====================================================================
echo html_writer::start_tag('form', [
    'method' => 'get',
    'class'  => 'form-inline mb-3',
]);

echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);

// Schedule filter.
$schedules = schedule_manager::get_schedules_by_course($courseid);
$scheduleoptions = ['' => '-- Todas las programaciones --'];
foreach ($schedules as $s) {
    $scheduleoptions[$s->id] = $s->name;
}

echo html_writer::start_div('form-group mr-2 mb-2');
echo html_writer::label('Programación: ', 'scheduleid', ['class' => 'mr-2']);
echo html_writer::select($scheduleoptions, 'scheduleid', $scheduleid, '', [
    'class' => 'form-control',
    'id'    => 'scheduleid',
]);
echo html_writer::end_div();

// Status filter.
$statusoptions = [
    ''        => '-- Todos los estados --',
    'pending' => get_string('logstatus_pending', 'local_epicereports'),
    'sent'    => get_string('logstatus_sent', 'local_epicereports'),
    'failed'  => get_string('logstatus_failed', 'local_epicereports'),
    'retry'   => get_string('logstatus_retry', 'local_epicereports'),
];

echo html_writer::start_div('form-group mr-2 mb-2');
echo html_writer::label('Estado: ', 'status', ['class' => 'mr-2']);
echo html_writer::select($statusoptions, 'status', $status, '', [
    'class' => 'form-control',
    'id'    => 'status',
]);
echo html_writer::end_div();

echo html_writer::start_div('form-group mb-2');
echo html_writer::empty_tag('input', [
    'type'  => 'submit',
    'class' => 'btn btn-primary',
    'value' => 'Filtrar',
]);
echo html_writer::end_div();

echo html_writer::end_tag('form');

// =====================================================================
// Logs Table
// =====================================================================

// Get logs.
if ($scheduleid) {
    $logs = schedule_manager::get_logs_by_schedule($scheduleid, $perpage, $page * $perpage);
    $totalcount = count(schedule_manager::get_logs_by_schedule($scheduleid, 0, 0));
} else {
    $logs = schedule_manager::get_logs_by_course($courseid, $perpage, $page * $perpage, $status ?: null);
    $totalcount = schedule_manager::count_logs_by_course($courseid, $status ?: null);
}

if (empty($logs)) {
    echo $OUTPUT->notification(
        get_string('nologs', 'local_epicereports'),
        \core\output\notification::NOTIFY_INFO
    );
} else {
    // Summary stats.
    $sentcount = schedule_manager::count_logs_by_course($courseid, 'sent');
    $failedcount = schedule_manager::count_logs_by_course($courseid, 'failed');
    $pendingcount = schedule_manager::count_logs_by_course($courseid, 'pending');

    echo html_writer::start_div('mb-3');
    echo html_writer::tag('span', "Enviados: $sentcount", ['class' => 'badge badge-success mr-2']);
    echo html_writer::tag('span', "Fallidos: $failedcount", ['class' => 'badge badge-danger mr-2']);
    echo html_writer::tag('span', "Pendientes: $pendingcount", ['class' => 'badge badge-warning mr-2']);
    echo html_writer::end_div();

    $table = new html_table();
    $table->attributes = [
        'class' => 'table table-striped table-bordered table-sm',
    ];
    $table->head = [
        'ID',
        'Programación',
        get_string('logrecipient', 'local_epicereports'),
        get_string('logstatus', 'local_epicereports'),
        get_string('logtimescheduled', 'local_epicereports'),
        get_string('logtimesent', 'local_epicereports'),
        get_string('logretrycount', 'local_epicereports'),
        get_string('logerror', 'local_epicereports'),
    ];
    $table->data = [];

    // Build schedule name lookup.
    $schedulenames = [];
    foreach ($schedules as $s) {
        $schedulenames[$s->id] = $s->name;
    }

    foreach ($logs as $log) {
        // Status badge.
        $statusbadges = [
            'pending' => 'badge-warning',
            'sent'    => 'badge-success',
            'failed'  => 'badge-danger',
            'retry'   => 'badge-info',
        ];
        $statuslabels = [
            'pending' => get_string('logstatus_pending', 'local_epicereports'),
            'sent'    => get_string('logstatus_sent', 'local_epicereports'),
            'failed'  => get_string('logstatus_failed', 'local_epicereports'),
            'retry'   => get_string('logstatus_retry', 'local_epicereports'),
        ];
        $statusbadge = html_writer::tag('span',
            $statuslabels[$log->status] ?? $log->status,
            ['class' => 'badge ' . ($statusbadges[$log->status] ?? 'badge-secondary')]
        );

        // Schedule name.
        $schedulename = $schedulenames[$log->scheduleid] ?? 'N/A';

        // Times.
        $timescheduled = userdate($log->timescheduled, '%d/%m/%Y %H:%M');
        $timesent = $log->timesent ? userdate($log->timesent, '%d/%m/%Y %H:%M') : '-';

        // Error (truncated).
        $error = '';
        if ($log->errorcode || $log->errormessage) {
            $error = s($log->errorcode ?: '');
            if ($log->errormessage) {
                $shortmsg = mb_substr($log->errormessage, 0, 50);
                if (mb_strlen($log->errormessage) > 50) {
                    $shortmsg .= '...';
                }
                $error .= ' ' . html_writer::tag('small', s($shortmsg), [
                    'class' => 'text-muted',
                    'title' => s($log->errormessage),
                ]);
            }
        } else {
            $error = '-';
        }

        $table->data[] = [
            $log->id,
            s($schedulename),
            s($log->recipientemail),
            $statusbadge,
            $timescheduled,
            $timesent,
            $log->retrycount,
            $error,
        ];
    }

    echo html_writer::table($table);

    // Pagination.
    $baseurl = new moodle_url('/local/epicereports/schedule_logs.php', [
        'courseid'   => $courseid,
        'scheduleid' => $scheduleid,
        'status'     => $status,
    ]);
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
}

// Close layout.
echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
