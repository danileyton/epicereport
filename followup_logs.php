<?php
/**
 * Historial de mensajes de seguimiento enviados - Con DataTables
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\followup_manager;

$courseid = required_param('courseid', PARAM_INT);
$followupid = optional_param('id', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/followup_logs.php', [
    'courseid' => $courseid,
    'id' => $followupid
]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('followuplogs', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

// Cargar estilos CSS ANTES de $OUTPUT->header().
local_epicereports_include_styles();

// Cargar DataTables.
local_epicereports_include_datatables('followup-logs-table', [
    'pageLength' => 25,
    'order' => [[0, 'desc']],
    'orderableExclude' => -1
]);

// Load followup if specified.
$followup = null;
if ($followupid) {
    $followup = followup_manager::get_followup($followupid);
    if (!$followup || $followup->courseid != $courseid) {
        throw new moodle_exception('invalidfollowup', 'local_epicereports');
    }
}

// Get logs.
if ($followupid) {
    $logs = followup_manager::get_logs_by_followup($followupid, $perpage, $page * $perpage);
} else {
    $logs = followup_manager::get_logs_by_course($courseid, $perpage, $page * $perpage);
}

// Get user info for each log.
$userids = array_unique(array_column($logs, 'userid'));
$users = [];
if (!empty($userids)) {
    global $DB;
    list($insql, $params) = $DB->get_in_or_equal($userids);
    $usersdata = $DB->get_records_select('user', "id $insql", $params, '', 'id, firstname, lastname, email');
    foreach ($usersdata as $u) {
        $users[$u->id] = $u;
    }
}

// Get followup names.
$followupids = array_unique(array_column($logs, 'followupid'));
$followupnames = [];
if (!empty($followupids)) {
    global $DB;
    list($insql, $params) = $DB->get_in_or_equal($followupids);
    $followupsdata = $DB->get_records_select('local_epicereports_followup', "id $insql", $params, '', 'id, name');
    foreach ($followupsdata as $f) {
        $followupnames[$f->id] = $f->name;
    }
}

echo $OUTPUT->header();

// Layout.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('followup', $course);
echo html_writer::end_div();

// Main content.
echo html_writer::start_div('col-md-9 col-lg-10');

// Card.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-history']) . ' ' . get_string('followuplogs', 'local_epicereports'),
    ['class' => 'epice-card-title']
);

// Back button.
$backurl = new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]);
echo html_writer::link($backurl, 
    html_writer::tag('i', '', ['class' => 'fa fa-arrow-left']) . ' ' . get_string('back', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-outline epice-btn-sm']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');

// Filter info if viewing specific followup.
if ($followup) {
    echo html_writer::start_div('epice-filter-info');
    echo html_writer::tag('i', '', ['class' => 'fa fa-filter']);
    echo get_string('filteringbyfollowup', 'local_epicereports') . ': <strong>' . s($followup->name) . '</strong>';
    
    $clearurl = new moodle_url('/local/epicereports/followup_logs.php', ['courseid' => $courseid]);
    echo ' ' . html_writer::link($clearurl, get_string('clearfilter', 'local_epicereports'), ['style' => 'margin-left: 12px;']);
    echo html_writer::end_div();
}

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
    echo html_writer::start_tag('table', ['class' => 'epice-table', 'id' => 'followup-logs-table']);
    
    // Headers.
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('date', 'local_epicereports'));
    echo html_writer::tag('th', get_string('followup', 'local_epicereports'));
    echo html_writer::tag('th', get_string('recipient', 'local_epicereports'));
    echo html_writer::tag('th', get_string('channel', 'local_epicereports'));
    echo html_writer::tag('th', get_string('status', 'local_epicereports'));
    echo html_writer::tag('th', get_string('error', 'local_epicereports'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    
    foreach ($logs as $log) {
        echo html_writer::start_tag('tr');
        
        // Date.
        $date = $log->timesent ? userdate($log->timesent, '%d/%m/%Y %H:%M') : userdate($log->timescheduled, '%d/%m/%Y %H:%M');
        echo html_writer::tag('td', $date);
        
        // Followup name.
        $fname = $followupnames[$log->followupid] ?? '-';
        echo html_writer::tag('td', s($fname));
        
        // Recipient.
        $user = $users[$log->userid] ?? null;
        if ($user) {
            $recipient = fullname($user) . '<br><small class="text-muted">' . s($log->recipientemail) . '</small>';
        } else {
            $recipient = s($log->recipientemail);
        }
        echo html_writer::tag('td', $recipient);
        
        // Channel.
        switch ($log->channel) {
            case 'email':
                $channelbadge = html_writer::tag('span', 
                    html_writer::tag('i', '', ['class' => 'fa fa-envelope']) . ' Email',
                    ['class' => 'epice-channel-badge']
                );
                break;
            case 'message':
                $channelbadge = html_writer::tag('span', 
                    html_writer::tag('i', '', ['class' => 'fa fa-comment']) . ' Mensaje',
                    ['class' => 'epice-channel-badge']
                );
                break;
            case 'both':
                $channelbadge = html_writer::tag('span', 
                    html_writer::tag('i', '', ['class' => 'fa fa-envelope']) . ' + ' .
                    html_writer::tag('i', '', ['class' => 'fa fa-comment']),
                    ['class' => 'epice-channel-badge']
                );
                break;
            default:
                $channelbadge = '-';
        }
        echo html_writer::tag('td', $channelbadge);
        
        // Status.
        switch ($log->status) {
            case 'sent':
                $statusbadge = html_writer::tag('span', get_string('status_sent', 'local_epicereports'), 
                    ['class' => 'epice-badge epice-badge-success']);
                break;
            case 'pending':
                $statusbadge = html_writer::tag('span', get_string('status_pending', 'local_epicereports'), 
                    ['class' => 'epice-badge epice-badge-warning']);
                break;
            case 'failed':
                $statusbadge = html_writer::tag('span', get_string('status_failed', 'local_epicereports'), 
                    ['class' => 'epice-badge epice-badge-danger']);
                break;
            default:
                $statusbadge = html_writer::tag('span', $log->status, 
                    ['class' => 'epice-badge epice-badge-info']);
        }
        echo html_writer::tag('td', $statusbadge);
        
        // Error.
        $error = '-';
        if ($log->status === 'failed' && !empty($log->errormessage)) {
            $error = html_writer::tag('small', s(substr($log->errormessage, 0, 100)), 
                ['class' => 'text-danger', 'title' => s($log->errormessage)]);
        }
        echo html_writer::tag('td', $error);
        
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
