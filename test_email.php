<?php
/**
 * Página de prueba de correos - Diseño mejorado
 *
 * @package    local_epicereports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\email_sender;
use local_epicereports\report_generator;

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/test_email.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('testemail', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

$message = '';
$messagetype = '';

if ($action === 'send' && confirm_sesskey()) {
    $email = required_param('email', PARAM_EMAIL);
    $includeattachment = optional_param('attachment', 0, PARAM_BOOL);

    // CORREGIDO: Usar send_test_email con los parámetros correctos (email, courseid, includeattachment)
    $result = email_sender::send_test_email($email, $courseid, $includeattachment);

    if (!empty($result['success'])) {
        $message = get_string('testemailsent', 'local_epicereports') . ' ' . $email;
        $messagetype = 'success';
    } else {
        $error_detail = !empty($result['error']) ? ': ' . $result['error'] : '';
        $message = get_string('testemailfailed', 'local_epicereports') . $error_detail;
        $messagetype = 'error';
    }
}

echo $OUTPUT->header();

echo '<style>
:root {
    --epice-primary: #1e3a5f; --epice-accent: #0ea5e9; --epice-success: #10b981; --epice-success-bg: rgba(16, 185, 129, 0.1);
    --epice-warning: #f59e0b; --epice-warning-bg: rgba(245, 158, 11, 0.1); --epice-danger: #ef4444; --epice-danger-bg: rgba(239, 68, 68, 0.1);
    --epice-info: #3b82f6; --epice-info-bg: rgba(59, 130, 246, 0.1);
    --epice-bg-card: #ffffff; --epice-bg-sidebar: linear-gradient(180deg, #1e3a5f 0%, #0f2744 100%);
    --epice-bg-header: linear-gradient(135deg, #1e3a5f 0%, #2d5a8a 100%); --epice-bg-table-header: #f1f5f9;
    --epice-text-primary: #1e293b; --epice-text-secondary: #64748b; --epice-text-inverse: #ffffff;
    --epice-border: #e2e8f0; --epice-border-light: #f1f5f9; --epice-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --epice-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1); --epice-radius: 8px; --epice-radius-md: 12px; --epice-radius-lg: 16px;
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
.epice-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; font-size: 0.875rem; font-weight: 600; border-radius: var(--epice-radius); border: none; cursor: pointer; transition: var(--epice-transition); text-decoration: none !important; }
.epice-btn-success { background: var(--epice-success); color: var(--epice-text-inverse) !important; }
.epice-badge { display: inline-flex; align-items: center; padding: 4px 10px; font-size: 0.75rem; font-weight: 600; border-radius: 4px; }
.epice-badge-success { background: var(--epice-success-bg); color: var(--epice-success); }
.epice-badge-warning { background: var(--epice-warning-bg); color: #b45309; }
.epice-badge-danger { background: var(--epice-danger-bg); color: var(--epice-danger); }
.epice-filter-form { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
.epice-form-group { display: flex; flex-direction: column; gap: 4px; }
.epice-form-label { font-size: 0.7rem; font-weight: 600; color: var(--epice-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.epice-form-input { padding: 10px 16px; font-size: 0.875rem; border: 1px solid var(--epice-border); border-radius: var(--epice-radius); background: var(--epice-bg-card); color: var(--epice-text-primary); min-width: 280px; }
.epice-config-item { margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
@media (max-width: 768px) { .epice-sidebar { margin-bottom: 24px; } .epice-filter-form { flex-direction: column; } .epice-form-input { width: 100%; } }
</style>';

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('test_email', $course);
echo html_writer::end_div();

echo html_writer::start_div('col-md-9 col-lg-10');

echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', html_writer::tag('i', '', ['class' => 'fa fa-envelope']) . ' ' . get_string('testemail', 'local_epicereports'), ['class' => 'epice-card-title']);
echo html_writer::end_div();
echo html_writer::start_div('epice-card-body');

if ($message) {
    $notifytype = $messagetype === 'success' ? 'success' : 'error';
    echo $OUTPUT->notification($message, $notifytype);
}

echo html_writer::tag('p', get_string('testemailinfo', 'local_epicereports'), ['style' => 'color: #64748b; margin-bottom: 20px;']);

// Configuración SMTP.
echo html_writer::start_div('epice-card', ['style' => 'margin-bottom: 20px;']);
echo html_writer::start_div('epice-card-header-light');
echo html_writer::tag('h5', html_writer::tag('i', '', ['class' => 'fa fa-cog']) . ' ' . get_string('emailconfig', 'local_epicereports'), ['class' => 'epice-card-title epice-card-title-dark']);
echo html_writer::end_div();
echo html_writer::start_div('epice-card-body');

$smtphost = get_config('', 'smtphosts');
$noreplyaddress = get_config('', 'noreplyaddress');

if (!empty($smtphost)) {
    echo html_writer::div(local_epicereports_render_badge('✓', 'success') . ' SMTP: ' . s($smtphost), 'epice-config-item');
} else {
    echo html_writer::div(local_epicereports_render_badge('!', 'warning') . ' SMTP: No configurado (usando mail() de PHP)', 'epice-config-item');
}

if (!empty($noreplyaddress)) {
    echo html_writer::div(local_epicereports_render_badge('✓', 'success') . ' No-reply: ' . s($noreplyaddress), 'epice-config-item');
} else {
    echo html_writer::div(local_epicereports_render_badge('!', 'danger') . ' No-reply: No configurado', 'epice-config-item');
}

echo html_writer::end_div();
echo html_writer::end_div();

// Formulario de prueba.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header-light');
echo html_writer::tag('h5', html_writer::tag('i', '', ['class' => 'fa fa-paper-plane']) . ' ' . get_string('sendtestemail', 'local_epicereports'), ['class' => 'epice-card-title epice-card-title-dark']);
echo html_writer::end_div();
echo html_writer::start_div('epice-card-body');

$formurl = new moodle_url('/local/epicereports/test_email.php', ['courseid' => $courseid, 'action' => 'send', 'sesskey' => sesskey()]);
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl->out(false), 'class' => 'epice-filter-form']);

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', get_string('email', 'local_epicereports'), ['class' => 'epice-form-label']);
echo html_writer::empty_tag('input', ['type' => 'email', 'name' => 'email', 'required' => 'required', 'class' => 'epice-form-input', 'value' => $USER->email]);
echo html_writer::end_div();

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', get_string('options', 'local_epicereports'), ['class' => 'epice-form-label']);
echo html_writer::checkbox('attachment', 1, false, ' ' . get_string('includeattachment', 'local_epicereports'));
echo html_writer::end_div();

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', '&nbsp;', ['class' => 'epice-form-label']);
echo html_writer::tag('button', html_writer::tag('i', '', ['class' => 'fa fa-paper-plane']) . ' ' . get_string('sendtestemail', 'local_epicereports'), ['type' => 'submit', 'class' => 'epice-btn epice-btn-success']);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
