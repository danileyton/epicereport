<?php
/**
 * Mensajes de seguimiento - Listado de programaciones con DataTables
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
$action = optional_param('action', '', PARAM_ALPHA);
$followupid = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('followupmessages', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

// Cargar estilos CSS ANTES de $OUTPUT->header().
local_epicereports_include_styles();

// Cargar DataTables.
local_epicereports_include_datatables('followup-table', [
    'pageLength' => 25,
    'order' => [[0, 'asc']],
    'orderableExclude' => -1
]);

// Process actions.
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'delete':
            if ($confirm && $followupid) {
                followup_manager::delete_followup($followupid);
                redirect(
                    new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]),
                    get_string('followupdeleted', 'local_epicereports'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
            break;
            
        case 'toggle':
            if ($followupid) {
                followup_manager::toggle_followup($followupid);
                redirect(new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]));
            }
            break;
    }
}

// Get followup schedules for this course.
$followups = followup_manager::get_followups_by_course($courseid);

echo $OUTPUT->header();

// Delete confirmation.
if ($action === 'delete' && $followupid && !$confirm) {
    $followup = followup_manager::get_followup($followupid);
    if ($followup) {
        echo html_writer::start_div('row');
        echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
        local_epicereports_render_sidebar('followup', $course);
        echo html_writer::end_div();
        
        echo html_writer::start_div('col-md-9 col-lg-10');
        echo html_writer::start_div('epice-card');
        echo html_writer::start_div('epice-card-header');
        echo html_writer::tag('h5', 
            html_writer::tag('i', '', ['class' => 'fa fa-exclamation-triangle']) . ' ' . 
            get_string('confirmfollowupdelete', 'local_epicereports'),
            ['class' => 'epice-card-title']
        );
        echo html_writer::end_div();
        
        echo html_writer::start_div('epice-card-body');
        echo html_writer::tag('p', 
            get_string('confirmfollowupdelete', 'local_epicereports') . '<br><strong>' . s($followup->name) . '</strong>',
            ['style' => 'margin-bottom: 24px;']
        );
        
        $confirmurl = new moodle_url('/local/epicereports/followup_messages.php', [
            'courseid' => $courseid,
            'action' => 'delete',
            'id' => $followupid,
            'confirm' => 1,
            'sesskey' => sesskey()
        ]);
        $cancelurl = new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]);
        
        echo html_writer::link($confirmurl, get_string('delete', 'local_epicereports'), 
            ['class' => 'epice-btn epice-btn-danger']);
        echo ' ';
        echo html_writer::link($cancelurl, get_string('cancel', 'local_epicereports'), 
            ['class' => 'epice-btn epice-btn-outline']);
        
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        
        echo $OUTPUT->footer();
        exit;
    }
}

// Main layout.
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
    html_writer::tag('i', '', ['class' => 'fa fa-paper-plane']) . ' ' . 
    get_string('followupmessages', 'local_epicereports'),
    ['class' => 'epice-card-title']
);

// New button.
$newurl = new moodle_url('/local/epicereports/followup_form.php', ['courseid' => $courseid]);
echo html_writer::link($newurl, 
    html_writer::tag('i', '', ['class' => 'fa fa-plus']) . ' ' . get_string('newfollowup', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-success epice-btn-sm']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');

// Description.
echo html_writer::tag('p', get_string('followupmessagesdesc', 'local_epicereports'), ['class' => 'epice-description']);

if (empty($followups)) {
    // Empty state.
    echo html_writer::start_div('epice-empty-state');
    echo html_writer::tag('i', '', ['class' => 'fa fa-inbox']);
    echo html_writer::tag('h4', get_string('nofollowups', 'local_epicereports'));
    echo html_writer::tag('p', get_string('nofollowupsdesc', 'local_epicereports'));
    echo html_writer::link($newurl, 
        html_writer::tag('i', '', ['class' => 'fa fa-plus']) . ' ' . get_string('newfollowup', 'local_epicereports'),
        ['class' => 'epice-btn epice-btn-success', 'style' => 'margin-top: 16px;']
    );
    echo html_writer::end_div();
} else {
    // Table.
    echo html_writer::start_div('epice-table-container');
    echo html_writer::start_tag('table', ['class' => 'epice-table', 'id' => 'followup-table']);
    
    // Headers.
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('name', 'local_epicereports'));
    echo html_writer::tag('th', get_string('status', 'local_epicereports'));
    echo html_writer::tag('th', get_string('targetstudents', 'local_epicereports'));
    echo html_writer::tag('th', get_string('sendchannels', 'local_epicereports'));
    echo html_writer::tag('th', get_string('senddays', 'local_epicereports'));
    echo html_writer::tag('th', get_string('lastrun', 'local_epicereports'));
    echo html_writer::tag('th', get_string('actions', 'local_epicereports'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    
    foreach ($followups as $followup) {
        echo html_writer::start_tag('tr');
        
        // Name.
        echo html_writer::tag('td', s($followup->name), ['style' => 'font-weight: 500;']);
        
        // Status.
        if ($followup->enabled) {
            $statusbadge = html_writer::tag('span', get_string('enabled', 'local_epicereports'), 
                ['class' => 'epice-badge epice-badge-success']);
        } else {
            $statusbadge = html_writer::tag('span', get_string('disabled', 'local_epicereports'), 
                ['class' => 'epice-badge epice-badge-danger']);
        }
        echo html_writer::tag('td', $statusbadge);
        
        // Target.
        $targettext = get_string('target_' . $followup->target_status, 'local_epicereports');
        echo html_writer::tag('td', $targettext);
        
        // Channels.
        $emailicon = $followup->send_email 
            ? html_writer::tag('i', '', ['class' => 'fa fa-envelope active', 'title' => get_string('sendemail', 'local_epicereports')])
            : html_writer::tag('i', '', ['class' => 'fa fa-envelope inactive']);
        $messageicon = $followup->send_message 
            ? html_writer::tag('i', '', ['class' => 'fa fa-comment active', 'title' => get_string('sendmessage', 'local_epicereports')])
            : html_writer::tag('i', '', ['class' => 'fa fa-comment inactive']);
        echo html_writer::tag('td', html_writer::div($emailicon . $messageicon, 'epice-channel-icons'));
        
        // Days.
        $dayshtml = '';
        $daynames = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
        $dayfields = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($dayfields as $i => $field) {
            $active = $followup->$field ? 'active' : 'inactive';
            $dayshtml .= html_writer::tag('span', $daynames[$i], ['class' => 'epice-day ' . $active]);
        }
        echo html_writer::tag('td', html_writer::div($dayshtml, 'epice-days'));
        
        // Last run.
        $lastrun = $followup->lastrun ? userdate($followup->lastrun, '%d/%m/%Y %H:%M') : '-';
        echo html_writer::tag('td', $lastrun);
        
        // Actions.
        $actions = html_writer::start_div('epice-actions');
        
        // Edit.
        $editurl = new moodle_url('/local/epicereports/followup_form.php', [
            'courseid' => $courseid,
            'id' => $followup->id
        ]);
        $actions .= html_writer::link($editurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-edit']),
            ['class' => 'epice-btn epice-btn-outline epice-btn-xs', 'title' => get_string('edit', 'local_epicereports')]
        );
        
        // Toggle.
        $toggleurl = new moodle_url('/local/epicereports/followup_messages.php', [
            'courseid' => $courseid,
            'action' => 'toggle',
            'id' => $followup->id,
            'sesskey' => sesskey()
        ]);
        $toggleicon = $followup->enabled ? 'fa-pause' : 'fa-play';
        $toggletitle = $followup->enabled ? get_string('disable', 'local_epicereports') : get_string('enable', 'local_epicereports');
        $actions .= html_writer::link($toggleurl, 
            html_writer::tag('i', '', ['class' => 'fa ' . $toggleicon]),
            ['class' => 'epice-btn epice-btn-outline epice-btn-xs', 'title' => $toggletitle]
        );
        
        // Logs.
        $logsurl = new moodle_url('/local/epicereports/followup_logs.php', [
            'courseid' => $courseid,
            'id' => $followup->id
        ]);
        $actions .= html_writer::link($logsurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-history']),
            ['class' => 'epice-btn epice-btn-outline epice-btn-xs', 'title' => get_string('logs', 'local_epicereports')]
        );
        
        // Send now.
        $sendurl = new moodle_url('/local/epicereports/followup_send.php', [
            'courseid' => $courseid,
            'id' => $followup->id
        ]);
        $actions .= html_writer::link($sendurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-paper-plane']),
            ['class' => 'epice-btn epice-btn-outline epice-btn-xs', 'title' => get_string('followupsendnow', 'local_epicereports')]
        );
        
        // Delete.
        $deleteurl = new moodle_url('/local/epicereports/followup_messages.php', [
            'courseid' => $courseid,
            'action' => 'delete',
            'id' => $followup->id,
            'sesskey' => sesskey()
        ]);
        $actions .= html_writer::link($deleteurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-trash']),
            ['class' => 'epice-btn epice-btn-danger epice-btn-xs', 'title' => get_string('delete', 'local_epicereports')]
        );
        
        $actions .= html_writer::end_div();
        echo html_writer::tag('td', $actions);
        
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
