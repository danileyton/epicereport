<?php
/**
 * Gestión de destinatarios de programación - Diseño mejorado
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
$scheduleid = required_param('scheduleid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

// Verificar que la programación existe y pertenece al curso.
$schedule = schedule_manager::get_schedule($scheduleid);
if (!$schedule || $schedule->courseid != $courseid) {
    throw new moodle_exception('error:invalidscheduleid', 'local_epicereports');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/schedule_recipients.php', ['courseid' => $courseid, 'scheduleid' => $scheduleid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('recipients', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

$message = '';
$messagetype = '';

// Procesar acciones.
if ($action === 'add' && confirm_sesskey()) {
    $email = required_param('email', PARAM_EMAIL);
    $fullname = optional_param('fullname', '', PARAM_TEXT);
    $type = optional_param('type', 'to', PARAM_ALPHA);
    
    schedule_manager::add_recipient($scheduleid, $email, $fullname, null, $type);
    $message = get_string('recipientadded', 'local_epicereports');
    $messagetype = 'success';
}

if ($action === 'toggle' && $id && confirm_sesskey()) {
    schedule_manager::toggle_recipient($id);
    redirect(new moodle_url('/local/epicereports/schedule_recipients.php', ['courseid' => $courseid, 'scheduleid' => $scheduleid]));
}

if ($action === 'delete' && $id && confirm_sesskey()) {
    schedule_manager::remove_recipient($id);
    $message = get_string('recipientdeleted', 'local_epicereports');
    $messagetype = 'success';
}

// CORREGIDO: Usar el método correcto get_recipients()
$recipients = schedule_manager::get_recipients($scheduleid);

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
.epice-btn:hover { transform: translateY(-1px); }
.epice-btn-sm { padding: 6px 12px; font-size: 0.8rem; }
.epice-btn-primary { background: var(--epice-primary); color: var(--epice-text-inverse) !important; }
.epice-btn-success { background: var(--epice-success); color: var(--epice-text-inverse) !important; }
.epice-btn-warning { background: var(--epice-warning); color: var(--epice-text-primary) !important; }
.epice-btn-outline { background: transparent; color: var(--epice-text-secondary) !important; border: 1px solid var(--epice-border); }
.epice-btn-outline:hover { background: var(--epice-bg-table-header); color: var(--epice-text-primary) !important; }
.epice-btn-group { display: flex; gap: 8px; flex-wrap: wrap; }
.epice-badge { display: inline-flex; align-items: center; padding: 4px 10px; font-size: 0.75rem; font-weight: 600; border-radius: 4px; }
.epice-badge-success { background: var(--epice-success-bg); color: var(--epice-success); }
.epice-badge-warning { background: var(--epice-warning-bg); color: #b45309; }
.epice-badge-danger { background: var(--epice-danger-bg); color: var(--epice-danger); }
.epice-badge-secondary { background: rgba(100, 116, 139, 0.1); color: var(--epice-text-secondary); }
.epice-badge-info { background: var(--epice-info-bg); color: var(--epice-info); }
.epice-filter-form { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
.epice-form-group { display: flex; flex-direction: column; gap: 4px; }
.epice-form-label { font-size: 0.7rem; font-weight: 600; color: var(--epice-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.epice-form-input, .epice-form-select { padding: 10px 16px; font-size: 0.875rem; border: 1px solid var(--epice-border); border-radius: var(--epice-radius); background: var(--epice-bg-card); color: var(--epice-text-primary); min-width: 200px; }
.epice-table-container { overflow-x: auto; }
table.epice-table { width: 100%; border-collapse: separate; border-spacing: 0; }
table.epice-table thead th { background: var(--epice-bg-table-header); color: var(--epice-text-primary); font-weight: 600; font-size: 0.8rem; padding: 14px 16px; text-align: left; border-bottom: 2px solid var(--epice-border); text-transform: uppercase; }
table.epice-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--epice-border-light); color: var(--epice-text-primary); font-size: 0.875rem; }
table.epice-table tbody tr:hover { background: rgba(14, 165, 233, 0.04); }
table.epice-table tbody tr:nth-child(even) { background: var(--epice-bg-table-stripe); }
.epice-empty-state { text-align: center; padding: 48px 24px; color: var(--epice-text-secondary); }
.epice-empty-state i { font-size: 3rem; margin-bottom: 16px; opacity: 0.5; }
.epice-empty-state p { margin: 0; font-size: 1rem; }
.epice-schedule-info { background: var(--epice-info-bg); border-radius: var(--epice-radius); padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.epice-schedule-info i { color: var(--epice-info); }
.epice-schedule-info span { color: var(--epice-text-primary); font-weight: 500; }
@media (max-width: 768px) { .epice-sidebar { margin-bottom: 24px; } .epice-filter-form { flex-direction: column; } .epice-form-input, .epice-form-select { width: 100%; min-width: auto; } }
</style>';

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('schedules', $course);
echo html_writer::end_div();

echo html_writer::start_div('col-md-9 col-lg-10');

// Mostrar mensaje si existe.
if ($message) {
    $notifytype = $messagetype === 'success' ? 'success' : 'error';
    echo $OUTPUT->notification($message, $notifytype);
}

// Info de la programación.
echo html_writer::start_div('epice-schedule-info');
echo html_writer::tag('i', '', ['class' => 'fa fa-calendar-check']);
echo html_writer::tag('span', get_string('schedulename', 'local_epicereports') . ': ' . s($schedule->name));
echo html_writer::end_div();

// Formulario agregar destinatario.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header-light');
echo html_writer::tag('h5', html_writer::tag('i', '', ['class' => 'fa fa-user-plus']) . ' ' . get_string('addrecipient', 'local_epicereports'), ['class' => 'epice-card-title epice-card-title-dark']);
echo html_writer::end_div();
echo html_writer::start_div('epice-card-body');

$formurl = new moodle_url('/local/epicereports/schedule_recipients.php', [
    'courseid' => $courseid,
    'scheduleid' => $scheduleid,
    'action' => 'add',
    'sesskey' => sesskey()
]);

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl->out(false), 'class' => 'epice-filter-form']);

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', get_string('email', 'local_epicereports') . ' *', ['class' => 'epice-form-label']);
echo html_writer::empty_tag('input', ['type' => 'email', 'name' => 'email', 'required' => 'required', 'class' => 'epice-form-input', 'placeholder' => 'ejemplo@dominio.com']);
echo html_writer::end_div();

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', get_string('name', 'local_epicereports') . ' (' . get_string('optional', 'local_epicereports') . ')', ['class' => 'epice-form-label']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'fullname', 'class' => 'epice-form-input', 'placeholder' => 'Nombre del destinatario']);
echo html_writer::end_div();

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', get_string('type', 'local_epicereports'), ['class' => 'epice-form-label']);
$type_options = ['to' => 'Para (To)', 'cc' => 'Copia (CC)', 'bcc' => 'Copia oculta (BCC)'];
echo html_writer::select($type_options, 'type', 'to', null, ['class' => 'epice-form-select']);
echo html_writer::end_div();

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', '&nbsp;', ['class' => 'epice-form-label']);
echo html_writer::tag('button', html_writer::tag('i', '', ['class' => 'fa fa-plus']) . ' ' . get_string('add', 'local_epicereports'), ['type' => 'submit', 'class' => 'epice-btn epice-btn-success']);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// Lista de destinatarios.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', html_writer::tag('i', '', ['class' => 'fa fa-users']) . ' ' . get_string('recipients', 'local_epicereports') . ' (' . count($recipients) . ')', ['class' => 'epice-card-title']);
echo html_writer::end_div();

if (empty($recipients)) {
    echo html_writer::start_div('epice-card-body');
    echo html_writer::start_div('epice-empty-state');
    echo html_writer::tag('i', '', ['class' => 'fa fa-user-slash']);
    echo html_writer::tag('p', get_string('norecipients', 'local_epicereports'));
    echo html_writer::end_div();
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('epice-table-container');
    echo html_writer::start_tag('table', ['class' => 'epice-table']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('email', 'local_epicereports'));
    echo html_writer::tag('th', get_string('name', 'local_epicereports'));
    echo html_writer::tag('th', get_string('type', 'local_epicereports'));
    echo html_writer::tag('th', get_string('status', 'local_epicereports'));
    echo html_writer::tag('th', get_string('actions', 'local_epicereports'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($recipients as $recipient) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', s($recipient->email));
        echo html_writer::tag('td', s($recipient->fullname) ?: '-');
        
        // Badge de tipo.
        $type_badge = 'secondary';
        $type_label = strtoupper($recipient->type ?? 'to');
        if ($recipient->type === 'cc') $type_badge = 'info';
        if ($recipient->type === 'bcc') $type_badge = 'warning';
        echo html_writer::tag('td', local_epicereports_render_badge($type_label, $type_badge));
        
        // Estado.
        $status_badge = !empty($recipient->enabled)
            ? local_epicereports_render_badge(get_string('enabled', 'local_epicereports'), 'success')
            : local_epicereports_render_badge(get_string('disabled', 'local_epicereports'), 'secondary');
        echo html_writer::tag('td', $status_badge);
        
        // Acciones.
        echo html_writer::start_tag('td');
        echo html_writer::start_div('epice-btn-group');
        
        $toggle_url = new moodle_url('/local/epicereports/schedule_recipients.php', [
            'courseid' => $courseid,
            'scheduleid' => $scheduleid,
            'action' => 'toggle',
            'id' => $recipient->id,
            'sesskey' => sesskey()
        ]);
        $toggle_icon = !empty($recipient->enabled) ? 'fa-toggle-on' : 'fa-toggle-off';
        $toggle_class = !empty($recipient->enabled) ? 'epice-btn-warning' : 'epice-btn-success';
        echo html_writer::link($toggle_url,
            html_writer::tag('i', '', ['class' => 'fa ' . $toggle_icon]),
            ['class' => 'epice-btn ' . $toggle_class . ' epice-btn-sm', 'title' => get_string('togglestatus', 'local_epicereports')]
        );
        
        $delete_url = new moodle_url('/local/epicereports/schedule_recipients.php', [
            'courseid' => $courseid,
            'scheduleid' => $scheduleid,
            'action' => 'delete',
            'id' => $recipient->id,
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

echo html_writer::end_div();

// Botón volver.
$back_url = new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]);
echo html_writer::div(
    html_writer::link($back_url, html_writer::tag('i', '', ['class' => 'fa fa-arrow-left']) . ' ' . get_string('back', 'local_epicereports'), ['class' => 'epice-btn epice-btn-outline']),
    'mt-3'
);

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
