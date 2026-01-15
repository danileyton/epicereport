<?php
/**
 * Gestión de reportes programados - Diseño mejorado con CSS embebido
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

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('scheduledreports', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

$notification_message = '';
$notification_type = '';

// Procesar acciones.
if ($action === 'toggle' && $id && confirm_sesskey()) {
    schedule_manager::toggle_schedule($id);
    redirect(new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]));
}

if ($action === 'delete' && $id && confirm_sesskey()) {
    schedule_manager::delete_schedule($id);
    redirect(new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]),
        get_string('scheduledeleted', 'local_epicereports'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// ============ ENVÍO MANUAL ============
if ($action === 'sendnow' && $id && confirm_sesskey()) {
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
                // $attachments contendrá arrays con ['filepath' => '...', 'filename' => '...']
                $attachments = [];
                $attachment_names = []; // Para el log
                $files_to_cleanup = []; // Para limpiar después
                
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
                    // Enviar a cada destinatario usando email_sender.
                    $success_count = 0;
                    $error_count = 0;
                    
                    foreach ($recipients as $recipient) {
                        // Crear log para este envío.
                        $logid = schedule_manager::create_log(
                            $id,
                            $courseid,
                            $recipient->email,
                            $recipient->id,
                            time(),
                            'pending',
                            $attachment_names
                        );
                        
                        // Enviar correo usando el método existente.
                        // send_report_email espera $attachments como array de arrays con filepath/filename
                        $result = email_sender::send_report_email(
                            $schedule,
                            $recipient,
                            $attachments,
                            $course
                        );
                        
                        // Actualizar log con resultado.
                        // Usar 'sent' para éxito y 'failed' para error (valores estándar)
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
                    
                    // Actualizar lastrun usando mark_schedule_run.
                    schedule_manager::mark_schedule_run($id);
                    
                    // Limpiar archivos temporales.
                    foreach ($files_to_cleanup as $file) {
                        if (file_exists($file)) {
                            @unlink($file);
                        }
                    }
                    
                    // Mensaje de resultado.
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

// Obtener programaciones del curso.
$schedules = schedule_manager::get_schedules_by_course($courseid);

// Contar estudiantes matriculados.
$enrolled_count = count_enrolled_users($context, 'mod/assign:submit');

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
    --epice-danger-bg: rgba(239, 68, 68, 0.1);
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
    --epice-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --epice-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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
.epice-nav-separator { padding: 12px 16px 4px 16px; }
.epice-nav-separator-text { color: rgba(255, 255, 255, 0.4); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; }

.epice-card { background: var(--epice-bg-card); border-radius: var(--epice-radius-md); box-shadow: var(--epice-shadow); border: 1px solid var(--epice-border-light); margin-bottom: 24px; overflow: hidden; }
.epice-card-header { background: var(--epice-bg-header); padding: 16px 24px; }
.epice-card-header-light { background: var(--epice-bg-table-header); padding: 16px 24px; border-bottom: 1px solid var(--epice-border); }
.epice-card-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
.epice-card-title-dark { color: var(--epice-text-primary); }
.epice-card-body { padding: 24px; }

.epice-course-info { display: flex; flex-wrap: wrap; gap: 24px; align-items: center; }
.epice-course-name { font-size: 1.25rem; font-weight: 600; color: var(--epice-text-primary); margin: 0; }
.epice-course-meta { display: flex; gap: 16px; flex-wrap: wrap; }
.epice-course-meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.875rem; color: var(--epice-text-secondary); }
.epice-course-meta-item i { color: var(--epice-accent); }

.epice-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; font-size: 0.875rem; font-weight: 600; border-radius: var(--epice-radius); border: none; cursor: pointer; transition: var(--epice-transition); text-decoration: none !important; }
.epice-btn:hover { transform: translateY(-1px); }
.epice-btn-sm { padding: 6px 12px; font-size: 0.8rem; }
.epice-btn-primary { background: var(--epice-primary); color: var(--epice-text-inverse) !important; }
.epice-btn-primary:hover { background: var(--epice-primary-light); }
.epice-btn-success { background: var(--epice-success); color: var(--epice-text-inverse) !important; }
.epice-btn-info { background: var(--epice-info); color: var(--epice-text-inverse) !important; }
.epice-btn-warning { background: var(--epice-warning); color: var(--epice-text-primary) !important; }
.epice-btn-danger { background: var(--epice-danger); color: var(--epice-text-inverse) !important; }
.epice-btn-outline { background: transparent; color: var(--epice-text-secondary) !important; border: 1px solid var(--epice-border); }
.epice-btn-outline:hover { background: var(--epice-bg-table-header); color: var(--epice-text-primary) !important; }
.epice-btn-send { background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); color: var(--epice-text-inverse) !important; }
.epice-btn-send:hover { background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); }
.epice-btn-group { display: flex; gap: 8px; flex-wrap: wrap; }

.epice-badge { display: inline-flex; align-items: center; padding: 4px 10px; font-size: 0.75rem; font-weight: 600; border-radius: 4px; }
.epice-badge-success { background: var(--epice-success-bg); color: var(--epice-success); }
.epice-badge-warning { background: var(--epice-warning-bg); color: #b45309; }
.epice-badge-danger { background: var(--epice-danger-bg); color: var(--epice-danger); }
.epice-badge-secondary { background: rgba(100, 116, 139, 0.1); color: var(--epice-text-secondary); }
.epice-badge-info { background: var(--epice-info-bg); color: var(--epice-info); }

.epice-table-container { overflow-x: auto; }
table.epice-table { width: 100%; border-collapse: separate; border-spacing: 0; }
table.epice-table thead th { background: var(--epice-bg-table-header); color: var(--epice-text-primary); font-weight: 600; font-size: 0.8rem; padding: 14px 16px; text-align: left; border-bottom: 2px solid var(--epice-border); text-transform: uppercase; }
table.epice-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--epice-border-light); color: var(--epice-text-primary); font-size: 0.875rem; }
table.epice-table tbody tr:hover { background: rgba(14, 165, 233, 0.04); }
table.epice-table tbody tr:nth-child(even) { background: var(--epice-bg-table-stripe); }

.epice-empty-state { text-align: center; padding: 48px 24px; color: var(--epice-text-secondary); }
.epice-empty-state i { font-size: 3rem; margin-bottom: 16px; opacity: 0.5; }
.epice-empty-state p { margin: 0; font-size: 1rem; }

@media (max-width: 768px) {
    .epice-sidebar { margin-bottom: 24px; }
    .epice-btn-group { flex-direction: column; }
    .epice-btn { width: 100%; }
    .epice-course-info { flex-direction: column; align-items: flex-start; }
}
</style>';

// Layout principal.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('schedules', $course);
echo html_writer::end_div();

// Contenido principal.
echo html_writer::start_div('col-md-9 col-lg-10');

// Mostrar notificación si existe.
if ($notification_message) {
    $notify_type = $notification_type === 'success' ? 'success' : ($notification_type === 'warning' ? 'warning' : 'error');
    echo $OUTPUT->notification($notification_message, $notify_type);
}

// ============ TARJETA DE INFORMACIÓN DEL CURSO ============
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header-light');
echo html_writer::start_div('epice-course-info');

// Nombre del curso.
echo html_writer::tag('h4', 
    html_writer::tag('i', '', ['class' => 'fa fa-graduation-cap', 'style' => 'color: var(--epice-accent); margin-right: 8px;']) . 
    format_string($course->fullname), 
    ['class' => 'epice-course-name']
);

// Meta información.
echo html_writer::start_div('epice-course-meta');
echo html_writer::tag('span', 
    html_writer::tag('i', '', ['class' => 'fa fa-tag']) . ' ' . format_string($course->shortname),
    ['class' => 'epice-course-meta-item']
);
echo html_writer::tag('span', 
    html_writer::tag('i', '', ['class' => 'fa fa-users']) . ' ' . $enrolled_count . ' ' . get_string('students', 'local_epicereports'),
    ['class' => 'epice-course-meta-item']
);
echo html_writer::tag('span', 
    html_writer::tag('i', '', ['class' => 'fa fa-calendar']) . ' ' . count($schedules) . ' ' . get_string('schedules', 'local_epicereports'),
    ['class' => 'epice-course-meta-item']
);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// ============ TARJETA DE PROGRAMACIONES ============
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5',
    html_writer::tag('i', '', ['class' => 'fa fa-clock']) . ' ' . get_string('scheduledreports', 'local_epicereports'),
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();
echo html_writer::start_div('epice-card-body');

// Botón nueva programación.
$new_url = new moodle_url('/local/epicereports/schedule_form.php', ['courseid' => $courseid]);
echo html_writer::start_div('epice-btn-group', ['style' => 'margin-bottom: 24px;']);
echo html_writer::link($new_url,
    html_writer::tag('i', '', ['class' => 'fa fa-plus']) . ' ' . get_string('newschedule', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-success']
);
echo html_writer::end_div();

// Tabla de programaciones.
if (empty($schedules)) {
    echo html_writer::start_div('epice-empty-state');
    echo html_writer::tag('i', '', ['class' => 'fa fa-calendar-times']);
    echo html_writer::tag('p', get_string('noschedules', 'local_epicereports'));
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('epice-table-container');
    echo html_writer::start_tag('table', ['class' => 'epice-table']);

    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('schedulename', 'local_epicereports'));
    echo html_writer::tag('th', get_string('status', 'local_epicereports'));
    echo html_writer::tag('th', get_string('senddays', 'local_epicereports'));
    echo html_writer::tag('th', get_string('sendtime', 'local_epicereports'));
    echo html_writer::tag('th', get_string('daterange', 'local_epicereports'));
    echo html_writer::tag('th', get_string('lastrun', 'local_epicereports'));
    echo html_writer::tag('th', get_string('actions', 'local_epicereports'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');

    foreach ($schedules as $schedule) {
        echo html_writer::start_tag('tr');

        echo html_writer::tag('td', s($schedule->name), ['style' => 'font-weight: 500;']);

        $status_badge = $schedule->enabled
            ? local_epicereports_render_badge(get_string('enabled', 'local_epicereports'), 'success')
            : local_epicereports_render_badge(get_string('disabled', 'local_epicereports'), 'secondary');
        echo html_writer::tag('td', $status_badge);

        // Días de envío.
        $days = [];
        $day_names = ['monday' => 'L', 'tuesday' => 'M', 'wednesday' => 'X', 'thursday' => 'J', 'friday' => 'V', 'saturday' => 'S', 'sunday' => 'D'];
        foreach ($day_names as $day => $abbrev) {
            if (!empty($schedule->$day)) {
                $days[] = $abbrev;
            }
        }
        echo html_writer::tag('td', implode(', ', $days) ?: '-');

        echo html_writer::tag('td', $schedule->sendtime ?: '-');

        // Rango de fechas.
        $start = userdate($schedule->startdate, '%d/%m/%Y');
        $end = !empty($schedule->enddate) ? userdate($schedule->enddate, '%d/%m/%Y') : get_string('noenddate', 'local_epicereports');
        echo html_writer::tag('td', $start . ' - ' . $end);

        $lastrun = $schedule->lastrun ? userdate($schedule->lastrun, '%Y-%m-%d %H:%M') : '-';
        echo html_writer::tag('td', $lastrun);

        // Acciones.
        echo html_writer::start_tag('td');
        echo html_writer::start_div('epice-btn-group');

        $edit_url = new moodle_url('/local/epicereports/schedule_form.php', ['courseid' => $courseid, 'id' => $schedule->id]);
        echo html_writer::link($edit_url,
            html_writer::tag('i', '', ['class' => 'fa fa-edit']),
            ['class' => 'epice-btn epice-btn-primary epice-btn-sm', 'title' => get_string('edit')]
        );

        $recipients_url = new moodle_url('/local/epicereports/schedule_recipients.php', ['courseid' => $courseid, 'scheduleid' => $schedule->id]);
        echo html_writer::link($recipients_url,
            html_writer::tag('i', '', ['class' => 'fa fa-users']),
            ['class' => 'epice-btn epice-btn-info epice-btn-sm', 'title' => get_string('recipients', 'local_epicereports')]
        );

        // ============ BOTÓN ENVÍO MANUAL ============
        $sendnow_url = new moodle_url('/local/epicereports/schedule_reports.php', [
            'courseid' => $courseid,
            'action' => 'sendnow',
            'id' => $schedule->id,
            'sesskey' => sesskey()
        ]);
        echo html_writer::link($sendnow_url,
            html_writer::tag('i', '', ['class' => 'fa fa-paper-plane']),
            ['class' => 'epice-btn epice-btn-send epice-btn-sm', 
             'title' => get_string('sendnow', 'local_epicereports'),
             'onclick' => "return confirm('" . get_string('confirmsendnow', 'local_epicereports') . "');"]
        );

        $toggle_url = new moodle_url('/local/epicereports/schedule_reports.php', [
            'courseid' => $courseid,
            'action' => 'toggle',
            'id' => $schedule->id,
            'sesskey' => sesskey()
        ]);
        $toggle_icon = $schedule->enabled ? 'fa-pause' : 'fa-play';
        $toggle_class = $schedule->enabled ? 'epice-btn-warning' : 'epice-btn-success';
        echo html_writer::link($toggle_url,
            html_writer::tag('i', '', ['class' => 'fa ' . $toggle_icon]),
            ['class' => 'epice-btn ' . $toggle_class . ' epice-btn-sm', 'title' => get_string('togglestatus', 'local_epicereports')]
        );

        $delete_url = new moodle_url('/local/epicereports/schedule_reports.php', [
            'courseid' => $courseid,
            'action' => 'delete',
            'id' => $schedule->id,
            'sesskey' => sesskey()
        ]);
        echo html_writer::link($delete_url,
            html_writer::tag('i', '', ['class' => 'fa fa-trash']),
            ['class' => 'epice-btn epice-btn-outline epice-btn-sm', 'title' => get_string('delete'),
                'onclick' => "return confirm('" . get_string('confirmdelete', 'local_epicereports') . "');"]
        );

        echo html_writer::end_div();
        echo html_writer::end_tag('td');

        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
