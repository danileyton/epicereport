<?php
/**
 * Envío manual de mensajes de seguimiento
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
$followupid = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/followup_send.php', [
    'courseid' => $courseid,
    'id' => $followupid
]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('followupsendnow', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

// Cargar estilos CSS ANTES de $OUTPUT->header().
local_epicereports_include_styles();

// Load followup.
$followup = followup_manager::get_followup($followupid);
if (!$followup || $followup->courseid != $courseid) {
    throw new moodle_exception('invalidfollowup', 'local_epicereports');
}

// Get target students.
$targetstudents = followup_manager::get_target_students($courseid, $followup->target_status);

// Process send.
if ($confirm && confirm_sesskey()) {
    $sent = 0;
    $skipped = 0;
    $failed = 0;
    $now = time();
    
    foreach ($targetstudents as $student) {
        // Check limit.
        if (followup_manager::has_user_reached_limit($followupid, $student->id, $followup->max_per_user)) {
            $skipped++;
            continue;
        }
        
        // Determine channel.
        $channel = 'email';
        if ($followup->send_email && $followup->send_message) {
            $channel = 'both';
        } else if ($followup->send_message) {
            $channel = 'message';
        }
        
        // Create log entry.
        $logid = followup_manager::create_log(
            $followupid,
            $courseid,
            $student->id,
            $student->email,
            $channel,
            $now,
            'pending'
        );
        
        // Prepare message.
        $subject = $followup->message_subject;
        if (empty($subject) || strpos($subject, '[[') !== false) {
            // Si está vacío o contiene un string no resuelto, usar valor directo.
            $subject = 'Recordatorio: Completa tu curso - ' . format_string($course->shortname);
        }
        $subject = followup_manager::replace_placeholders($subject, $student, $course);
        
        $body = $followup->message_body;
        if (empty($body)) {
            $body = '<p>Hola {FULLNAME}, te recordamos que aún no has completado el curso {COURSENAME}. Tu progreso actual es {PROGRESS}.</p><p>Accede aquí: {COURSEURL}</p>';
        }
        $body = followup_manager::replace_placeholders($body, $student, $course);
        
        $success = true;
        $errormsgs = [];
        
        // Send email.
        if ($followup->send_email) {
            $emailresult = followup_manager::send_email($student, $subject, $body, $course);
            if (!$emailresult) {
                $success = false;
                $errormsgs[] = 'Email failed';
            }
        }
        
        // Send Moodle message.
        if ($followup->send_message) {
            $msgresult = followup_manager::send_moodle_message($student, $subject, $body, $course);
            if (!$msgresult) {
                $success = false;
                $errormsgs[] = 'Moodle message failed';
            }
        }
        
        // Update log.
        if ($success) {
            followup_manager::update_log_status($logid, 'sent');
            $sent++;
        } else {
            followup_manager::update_log_status($logid, 'failed', 'SEND_ERROR', implode(', ', $errormsgs));
            $failed++;
        }
    }
    
    // Mark followup as run.
    followup_manager::mark_followup_run($followupid);
    
    // Redirect with message.
    $message = get_string('followupsentsummary', 'local_epicereports', [
        'sent' => $sent,
        'skipped' => $skipped,
        'failed' => $failed
    ]);
    
    redirect(
        new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]),
        $message,
        null,
        $failed > 0 ? \core\output\notification::NOTIFY_WARNING : \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

// Los estilos se cargan automáticamente desde locallib.php cuando se renderiza el sidebar.

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
    html_writer::tag('i', '', ['class' => 'fa fa-paper-plane']) . ' ' . get_string('followupsendnow', 'local_epicereports'),
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');

// Summary box.
echo html_writer::start_div('epice-summary-box');
echo html_writer::tag('h6', get_string('followupsummary', 'local_epicereports'), ['style' => 'margin-bottom: 16px; font-weight: 600;']);

echo html_writer::start_div('epice-summary-item');
echo html_writer::tag('span', get_string('name', 'local_epicereports'), ['class' => 'epice-summary-label']);
echo html_writer::tag('span', s($followup->name), ['class' => 'epice-summary-value']);
echo html_writer::end_div();

echo html_writer::start_div('epice-summary-item');
echo html_writer::tag('span', get_string('targetstudents', 'local_epicereports'), ['class' => 'epice-summary-label']);
echo html_writer::tag('span', get_string('target_' . $followup->target_status, 'local_epicereports'), ['class' => 'epice-summary-value']);
echo html_writer::end_div();

echo html_writer::start_div('epice-summary-item');
echo html_writer::tag('span', get_string('sendchannels', 'local_epicereports'), ['class' => 'epice-summary-label']);
$channels = [];
if ($followup->send_email) $channels[] = 'Email';
if ($followup->send_message) $channels[] = 'Mensaje Moodle';
echo html_writer::tag('span', implode(' + ', $channels), ['class' => 'epice-summary-value']);
echo html_writer::end_div();

echo html_writer::start_div('epice-summary-item');
echo html_writer::tag('span', get_string('recipientcount', 'local_epicereports'), ['class' => 'epice-summary-label']);
echo html_writer::tag('span', count($targetstudents), ['class' => 'epice-summary-value']);
echo html_writer::end_div();

echo html_writer::end_div(); // summary-box

// Warning if no students.
if (empty($targetstudents)) {
    echo html_writer::start_div('epice-alert epice-alert-warning');
    echo html_writer::tag('i', '', ['class' => 'fa fa-exclamation-triangle', 'style' => 'margin-right: 8px;']);
    echo get_string('nostudentstarget', 'local_epicereports');
    echo html_writer::end_div();
} else {
    // Info.
    echo html_writer::start_div('epice-alert epice-alert-info');
    echo html_writer::tag('i', '', ['class' => 'fa fa-info-circle', 'style' => 'margin-right: 8px;']);
    echo get_string('followupsendconfirm', 'local_epicereports', count($targetstudents));
    echo html_writer::end_div();
    
    // Preview table.
    echo html_writer::tag('h6', get_string('recipientpreview', 'local_epicereports'), ['style' => 'margin: 20px 0 12px 0; font-weight: 600;']);
    
    echo html_writer::start_div('epice-table-container');
    echo html_writer::start_tag('table', ['class' => 'epice-table']);
    
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('name', 'local_epicereports'));
    echo html_writer::tag('th', get_string('email', 'local_epicereports'));
    echo html_writer::tag('th', get_string('progress', 'local_epicereports'));
    echo html_writer::tag('th', get_string('limitstatus', 'local_epicereports'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    
    $previewcount = 0;
    foreach ($targetstudents as $student) {
        if ($previewcount >= 20) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', get_string('andmore', 'local_epicereports', count($targetstudents) - 20), ['colspan' => 4, 'style' => 'text-align: center; font-style: italic;']);
            echo html_writer::end_tag('tr');
            break;
        }
        
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', fullname($student));
        echo html_writer::tag('td', s($student->email));
        echo html_writer::tag('td', isset($student->progress) ? round($student->progress) . '%' : '-');
        
        // Limit status.
        $reachedlimit = followup_manager::has_user_reached_limit($followupid, $student->id, $followup->max_per_user);
        if ($reachedlimit) {
            $limitbadge = html_writer::tag('span', get_string('limitreached', 'local_epicereports'), ['class' => 'epice-badge epice-badge-warning']);
        } else {
            $limitbadge = html_writer::tag('span', get_string('willsend', 'local_epicereports'), ['class' => 'epice-badge epice-badge-success']);
        }
        echo html_writer::tag('td', $limitbadge);
        
        echo html_writer::end_tag('tr');
        $previewcount++;
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

// Buttons.
echo html_writer::start_div('', ['style' => 'margin-top: 24px;']);

if (!empty($targetstudents)) {
    $confirmurl = new moodle_url('/local/epicereports/followup_send.php', [
        'courseid' => $courseid,
        'id' => $followupid,
        'confirm' => 1,
        'sesskey' => sesskey()
    ]);
    echo html_writer::link($confirmurl, 
        html_writer::tag('i', '', ['class' => 'fa fa-paper-plane']) . ' ' . get_string('sendnow', 'local_epicereports'),
        ['class' => 'epice-btn epice-btn-success']
    );
    echo ' ';
}

$cancelurl = new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]);
echo html_writer::link($cancelurl, get_string('cancel', 'local_epicereports'), ['class' => 'epice-btn epice-btn-outline']);

echo html_writer::end_div();

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
