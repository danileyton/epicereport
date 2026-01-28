<?php
/**
 * Gestión de reportes programados - Diseño unificado con followup_messages
 *
 * @package    local_epicereports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\schedule_manager;
use local_epicereports\report_generator;
use local_epicereports\email_sender;

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('scheduledreports', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

// Cargar estilos CSS ANTES de $OUTPUT->header().
local_epicereports_include_styles();

// Cargar DataTables.
local_epicereports_include_datatables('schedules-table', [
    'pageLength' => 25,
    'order' => [[0, 'asc']],
    'orderableExclude' => -1
]);

$notification_message = '';
$notification_type = '';

// Procesar acciones.
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'toggle':
            if ($id) {
                schedule_manager::toggle_schedule($id);
                redirect(new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]));
            }
            break;
            
        case 'delete':
            if ($confirm && $id) {
                schedule_manager::delete_schedule($id);
                redirect(
                    new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]),
                    get_string('scheduledeleted', 'local_epicereports'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
            break;
            
        case 'sendnow':
            if ($id) {
                $schedule = schedule_manager::get_schedule($id);
                
                if ($schedule && $schedule->courseid == $courseid) {
                    // Obtener destinatarios habilitados.
                    $recipients = schedule_manager::get_enabled_recipients($id);
                    
                    if (empty($recipients)) {
                        $notification_message = get_string('error:norecipients', 'local_epicereports');
                        $notification_type = 'error';
                    } else {
                        try {
                            // Generar reportes.
                            $attachments = [];
                            $attachment_names = [];
                            $files_to_cleanup = [];
                            
                            if (!empty($schedule->include_course_report)) {
                                $course_report = report_generator::generate_course_excel($courseid);
                                if (!empty($course_report['success']) && !empty($course_report['filepath'])) {
                                    $attachments[] = [
                                        'filepath' => $course_report['filepath'],
                                        'filename' => $course_report['filename']
                                    ];
                                    $attachment_names[] = $course_report['filename'];
                                    $files_to_cleanup[] = $course_report['filepath'];
                                }
                            }
                            
                            if (!empty($schedule->include_feedback_report)) {
                                $feedback_report = report_generator::generate_feedback_excel($courseid);
                                if (!empty($feedback_report['success']) && !empty($feedback_report['filepath'])) {
                                    $attachments[] = [
                                        'filepath' => $feedback_report['filepath'],
                                        'filename' => $feedback_report['filename']
                                    ];
                                    $attachment_names[] = $feedback_report['filename'];
                                    $files_to_cleanup[] = $feedback_report['filepath'];
                                }
                            }
                            
                            if (empty($attachments)) {
                                $notification_message = get_string('error:noreportsgenerated', 'local_epicereports');
                                $notification_type = 'error';
                            } else {
                                // Enviar a cada destinatario.
                                $success_count = 0;
                                $error_count = 0;
                                
                                foreach ($recipients as $recipient) {
                                    $logid = schedule_manager::create_log(
                                        $id,
                                        $courseid,
                                        $recipient->email,
                                        $recipient->id,
                                        time(),
                                        'pending',
                                        $attachment_names
                                    );
                                    
                                    $result = email_sender::send_report_email(
                                        $schedule,
                                        $recipient,
                                        $attachments,
                                        $course
                                    );
                                    
                                    schedule_manager::update_log_status(
                                        $logid,
                                        $result['success'] ? 'sent' : 'failed',
                                        $result['success'] ? null : ($result['errorcode'] ?? 'UNKNOWN'),
                                        $result['success'] ? null : ($result['error'] ?? 'Unknown error')
                                    );
                                    
                                    if ($result['success']) {
                                        $success_count++;
                                    } else {
                                        $error_count++;
                                    }
                                }
                                
                                schedule_manager::mark_schedule_run($id);
                                
                                // Limpiar archivos temporales.
                                foreach ($files_to_cleanup as $file) {
                                    if (file_exists($file)) {
                                        @unlink($file);
                                    }
                                }
                                
                                if ($error_count === 0) {
                                    $notification_message = get_string('manualsend:success', 'local_epicereports', $success_count);
                                    $notification_type = 'success';
                                } else {
                                    $notification_message = get_string('manualsend:partial', 'local_epicereports', 
                                        (object)['success' => $success_count, 'errors' => $error_count]);
                                    $notification_type = 'warning';
                                }
                            }
                        } catch (\Exception $e) {
                            $notification_message = get_string('error:sendfailed', 'local_epicereports') . ': ' . $e->getMessage();
                            $notification_type = 'error';
                        }
                    }
                } else {
                    $notification_message = get_string('error:invalidscheduleid', 'local_epicereports');
                    $notification_type = 'error';
                }
            }
            break;
    }
}

// Obtener programaciones del curso.
$schedules = schedule_manager::get_schedules_by_course($courseid);

echo $OUTPUT->header();

// Delete confirmation.
if ($action === 'delete' && $id && !$confirm) {
    $schedule = schedule_manager::get_schedule($id);
    if ($schedule) {
        echo html_writer::start_div('row');
        echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
        local_epicereports_render_sidebar('schedules', $course);
        echo html_writer::end_div();
        
        echo html_writer::start_div('col-md-9 col-lg-10');
        echo html_writer::start_div('epice-card');
        echo html_writer::start_div('epice-card-header');
        echo html_writer::tag('h5', 
            html_writer::tag('i', '', ['class' => 'fa fa-exclamation-triangle']) . ' ' . 
            get_string('confirmdelete', 'local_epicereports'),
            ['class' => 'epice-card-title']
        );
        echo html_writer::end_div();
        
        echo html_writer::start_div('epice-card-body');
        echo html_writer::tag('p', 
            get_string('confirmscheduledelete', 'local_epicereports') . '<br><strong>' . s($schedule->name) . '</strong>',
            ['style' => 'margin-bottom: 24px;']
        );
        
        $confirmurl = new moodle_url('/local/epicereports/schedule_reports.php', [
            'courseid' => $courseid,
            'action' => 'delete',
            'id' => $id,
            'confirm' => 1,
            'sesskey' => sesskey()
        ]);
        $cancelurl = new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]);
        
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
local_epicereports_render_sidebar('schedules', $course);
echo html_writer::end_div();

// Main content.
echo html_writer::start_div('col-md-9 col-lg-10');

// Mostrar notificación si existe.
if ($notification_message) {
    $notify_type = $notification_type === 'success' ? 'success' : ($notification_type === 'warning' ? 'warning' : 'error');
    echo $OUTPUT->notification($notification_message, $notify_type);
}

// Card.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-clock']) . ' ' . 
    get_string('scheduledreports', 'local_epicereports'),
    ['class' => 'epice-card-title']
);

// New button.
$newurl = new moodle_url('/local/epicereports/schedule_form.php', ['courseid' => $courseid]);
echo html_writer::link($newurl, 
    html_writer::tag('i', '', ['class' => 'fa fa-plus']) . ' ' . get_string('newschedule', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-success epice-btn-sm']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');

// Description.
echo html_writer::tag('p', get_string('scheduledreportsdesc', 'local_epicereports'), ['class' => 'epice-description']);

if (empty($schedules)) {
    // Empty state.
    echo html_writer::start_div('epice-empty-state');
    echo html_writer::tag('i', '', ['class' => 'fa fa-calendar-times']);
    echo html_writer::tag('h4', get_string('noschedules', 'local_epicereports'));
    echo html_writer::tag('p', get_string('noschedulesdesc', 'local_epicereports'));
    echo html_writer::link($newurl, 
        html_writer::tag('i', '', ['class' => 'fa fa-plus']) . ' ' . get_string('newschedule', 'local_epicereports'),
        ['class' => 'epice-btn epice-btn-success', 'style' => 'margin-top: 16px;']
    );
    echo html_writer::end_div();
} else {
    // Table.
    echo html_writer::start_div('epice-table-container');
    echo html_writer::start_tag('table', ['class' => 'epice-table', 'id' => 'schedules-table']);
    
    // Headers.
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('name', 'local_epicereports'));
    echo html_writer::tag('th', get_string('status', 'local_epicereports'));
    echo html_writer::tag('th', get_string('reports', 'local_epicereports'));
    echo html_writer::tag('th', get_string('senddays', 'local_epicereports'));
    echo html_writer::tag('th', get_string('sendtime', 'local_epicereports'));
    echo html_writer::tag('th', get_string('lastrun', 'local_epicereports'));
    echo html_writer::tag('th', get_string('actions', 'local_epicereports'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    
    foreach ($schedules as $schedule) {
        echo html_writer::start_tag('tr');
        
        // Name.
        echo html_writer::tag('td', s($schedule->name), ['style' => 'font-weight: 500;']);
        
        // Status.
        if ($schedule->enabled) {
            $statusbadge = html_writer::tag('span', get_string('enabled', 'local_epicereports'), 
                ['class' => 'epice-badge epice-badge-success']);
        } else {
            $statusbadge = html_writer::tag('span', get_string('disabled', 'local_epicereports'), 
                ['class' => 'epice-badge epice-badge-danger']);
        }
        echo html_writer::tag('td', $statusbadge);
        
        // Reports included.
        $reports = [];
        if (!empty($schedule->include_course_report)) {
            $reports[] = html_writer::tag('i', '', ['class' => 'fa fa-file-excel', 'title' => get_string('coursereport', 'local_epicereports')]);
        }
        if (!empty($schedule->include_feedback_report)) {
            $reports[] = html_writer::tag('i', '', ['class' => 'fa fa-comments', 'title' => get_string('feedbackreport', 'local_epicereports')]);
        }
        echo html_writer::tag('td', html_writer::div(implode(' ', $reports), 'epice-channel-icons'));
        
        // Days.
        $dayshtml = '';
        $daynames = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
        $dayfields = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($dayfields as $i => $field) {
            $active = !empty($schedule->$field) ? 'active' : 'inactive';
            $dayshtml .= html_writer::tag('span', $daynames[$i], ['class' => 'epice-day ' . $active]);
        }
        echo html_writer::tag('td', html_writer::div($dayshtml, 'epice-days'));
        
        // Send time.
        echo html_writer::tag('td', $schedule->sendtime ?: '-');
        
        // Last run.
        $lastrun = $schedule->lastrun ? userdate($schedule->lastrun, '%d/%m/%Y %H:%M') : '-';
        echo html_writer::tag('td', $lastrun);
        
        // Actions.
        $actions = html_writer::start_div('epice-actions');
        
        // Edit.
        $editurl = new moodle_url('/local/epicereports/schedule_form.php', [
            'courseid' => $courseid,
            'id' => $schedule->id
        ]);
        $actions .= html_writer::link($editurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-edit']),
            ['class' => 'epice-btn epice-btn-outline epice-btn-xs', 'title' => get_string('edit', 'local_epicereports')]
        );
        
        // Toggle.
        $toggleurl = new moodle_url('/local/epicereports/schedule_reports.php', [
            'courseid' => $courseid,
            'action' => 'toggle',
            'id' => $schedule->id,
            'sesskey' => sesskey()
        ]);
        $toggleicon = $schedule->enabled ? 'fa-pause' : 'fa-play';
        $toggletitle = $schedule->enabled ? get_string('disable', 'local_epicereports') : get_string('enable', 'local_epicereports');
        $actions .= html_writer::link($toggleurl, 
            html_writer::tag('i', '', ['class' => 'fa ' . $toggleicon]),
            ['class' => 'epice-btn epice-btn-outline epice-btn-xs', 'title' => $toggletitle]
        );
        
        // Recipients.
        $recipientsurl = new moodle_url('/local/epicereports/schedule_recipients.php', [
            'courseid' => $courseid,
            'scheduleid' => $schedule->id
        ]);
        $actions .= html_writer::link($recipientsurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-users']),
            ['class' => 'epice-btn epice-btn-outline epice-btn-xs', 'title' => get_string('recipients', 'local_epicereports')]
        );
        
        // Logs - filtrado por esta programación.
        $logsurl = new moodle_url('/local/epicereports/schedule_logs.php', [
            'courseid' => $courseid,
            'scheduleid' => $schedule->id
        ]);
        $actions .= html_writer::link($logsurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-history']),
            ['class' => 'epice-btn epice-btn-outline epice-btn-xs', 'title' => get_string('logs', 'local_epicereports')]
        );
        
        // Send now.
        $sendurl = new moodle_url('/local/epicereports/schedule_reports.php', [
            'courseid' => $courseid,
            'action' => 'sendnow',
            'id' => $schedule->id,
            'sesskey' => sesskey()
        ]);
        $actions .= html_writer::link($sendurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-paper-plane']),
            ['class' => 'epice-btn epice-btn-outline epice-btn-xs', 
             'title' => get_string('sendnow', 'local_epicereports'),
             'onclick' => "return confirm('" . get_string('confirmsendnow', 'local_epicereports') . "');"]
        );
        
        // Delete.
        $deleteurl = new moodle_url('/local/epicereports/schedule_reports.php', [
            'courseid' => $courseid,
            'action' => 'delete',
            'id' => $schedule->id,
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
