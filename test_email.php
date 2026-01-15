<?php
/**
 * Página de prueba de envío de correo - Con diagnóstico completo
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
$debug_info = [];

// Procesar envío de prueba.
if ($action === 'send' && confirm_sesskey()) {
    $email = required_param('email', PARAM_EMAIL);
    $include_attachment = optional_param('attachment', 0, PARAM_BOOL);
    
    $debug_info[] = "=== INICIO DE PRUEBA DE ENVÍO ===";
    $debug_info[] = "Fecha/Hora: " . date('Y-m-d H:i:s');
    $debug_info[] = "Email destino: $email";
    $debug_info[] = "Incluir adjunto: " . ($include_attachment ? 'Sí' : 'No');
    $debug_info[] = "";
    
    // Verificar configuración de email.
    $debug_info[] = "=== CONFIGURACIÓN DE MOODLE ===";
    $debug_info[] = "smtphosts: " . ($CFG->smtphosts ?? 'No configurado (usando PHP mail())');
    $debug_info[] = "noreplyaddress: " . ($CFG->noreplyaddress ?? 'No configurado');
    $debug_info[] = "supportemail: " . ($CFG->supportemail ?? 'No configurado');
    $debug_info[] = "noemailever: " . (!empty($CFG->noemailever) ? 'SÍ (emails deshabilitados!)' : 'No');
    $debug_info[] = "emaildisable: " . (!empty($CFG->emaildisable) ? 'SÍ' : 'No');
    $debug_info[] = "";
    
    try {
        // Crear usuario temporal para el envío.
        $touser = new stdClass();
        $touser->id = -1;
        $touser->email = $email;
        $touser->firstname = 'Prueba';
        $touser->lastname = 'EpicE';
        $touser->maildisplay = 1;
        $touser->mailformat = 1; // HTML
        $touser->auth = 'manual';
        $touser->deleted = 0;
        $touser->suspended = 0;
        $touser->emailstop = 0;
        
        // Usuario remitente.
        $fromuser = core_user::get_noreply_user();
        $debug_info[] = "=== USUARIOS ===";
        $debug_info[] = "De: " . $fromuser->email;
        $debug_info[] = "Para: " . $touser->email;
        $debug_info[] = "";
        
        $subject = "[EpicE Reports] Prueba de correo - " . format_string($course->shortname);
        $body = "Este es un correo de prueba enviado desde EpicE Reports.\n\n";
        $body .= "Curso: " . format_string($course->fullname) . "\n";
        $body .= "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
        $body .= "Si recibes este mensaje, la configuración de correo está funcionando correctamente.";
        
        $bodyhtml = nl2br(htmlspecialchars($body));
        
        $attachment = '';
        $attachname = '';
        
        // Generar adjunto si se solicitó.
        if ($include_attachment) {
            $debug_info[] = "=== GENERANDO REPORTE ===";
            $report_result = report_generator::generate_course_excel($courseid);
            
            if (!empty($report_result['success']) && !empty($report_result['filepath'])) {
                $attachment = $report_result['filepath'];
                $attachname = $report_result['filename'];
                $debug_info[] = "Archivo generado: $attachname";
                $debug_info[] = "Ruta: $attachment";
                $debug_info[] = "Tamaño: " . filesize($attachment) . " bytes";
                $debug_info[] = "Existe: " . (file_exists($attachment) ? 'Sí' : 'NO!');
            } else {
                $debug_info[] = "ERROR al generar reporte: " . ($report_result['error'] ?? 'Desconocido');
            }
            $debug_info[] = "";
        }
        
        $debug_info[] = "=== ENVIANDO CORREO ===";
        $debug_info[] = "Asunto: $subject";
        
        // Llamar a email_to_user directamente para mejor diagnóstico.
        $result = email_to_user(
            $touser,
            $fromuser,
            $subject,
            $body,
            $bodyhtml,
            $attachment,
            $attachname,
            true
        );
        
        $debug_info[] = "Resultado email_to_user(): " . ($result ? 'TRUE (éxito)' : 'FALSE (falló)');
        $debug_info[] = "";
        
        // Limpiar archivo temporal.
        if (!empty($attachment) && file_exists($attachment)) {
            @unlink($attachment);
            $debug_info[] = "Archivo temporal eliminado.";
        }
        
        if ($result) {
            $message = "Correo enviado exitosamente a $email. Revisa tu bandeja de entrada (y SPAM).";
            $messagetype = 'success';
        } else {
            $message = "La función email_to_user() retornó FALSE. Revisa la configuración SMTP de Moodle.";
            $messagetype = 'error';
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messagetype = 'error';
        $debug_info[] = "EXCEPCIÓN: " . $e->getMessage();
        $debug_info[] = "Archivo: " . $e->getFile() . ":" . $e->getLine();
    }
    
    $debug_info[] = "";
    $debug_info[] = "=== FIN DE PRUEBA ===";
}

echo $OUTPUT->header();

echo '<style>
:root {
    --epice-primary: #1e3a5f; --epice-accent: #0ea5e9; --epice-success: #10b981;
    --epice-bg-card: #ffffff; --epice-bg-sidebar: linear-gradient(180deg, #1e3a5f 0%, #0f2744 100%);
    --epice-bg-header: linear-gradient(135deg, #1e3a5f 0%, #2d5a8a 100%);
    --epice-text-primary: #1e293b; --epice-text-secondary: #64748b; --epice-text-inverse: #ffffff;
    --epice-border: #e2e8f0; --epice-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --epice-radius: 8px; --epice-radius-md: 12px;
}
.epice-sidebar { background: var(--epice-bg-sidebar); border-radius: 16px; padding: 16px; box-shadow: var(--epice-shadow); }
.epice-nav { list-style: none; padding: 0; margin: 0; }
.epice-nav-link { display: flex; align-items: center; padding: 10px 16px; color: rgba(255,255,255,0.8) !important; text-decoration: none !important; border-radius: var(--epice-radius); font-size: 0.9rem; }
.epice-nav-link:hover { background: rgba(255,255,255,0.1); }
.epice-nav-link.active { background: var(--epice-accent); color: white !important; }
.epice-card { background: var(--epice-bg-card); border-radius: var(--epice-radius-md); box-shadow: var(--epice-shadow); margin-bottom: 24px; overflow: hidden; }
.epice-card-header { background: var(--epice-bg-header); padding: 16px 24px; }
.epice-card-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 600; margin: 0; }
.epice-card-body { padding: 24px; }
.epice-form-group { margin-bottom: 16px; }
.epice-form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--epice-text-secondary); margin-bottom: 4px; text-transform: uppercase; }
.epice-form-input { width: 100%; padding: 10px 16px; border: 1px solid var(--epice-border); border-radius: var(--epice-radius); font-size: 0.9rem; }
.epice-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-weight: 600; border-radius: var(--epice-radius); border: none; cursor: pointer; text-decoration: none !important; }
.epice-btn-success { background: var(--epice-success); color: white !important; }
.epice-btn-primary { background: var(--epice-primary); color: white !important; }
.epice-debug { background: #1e293b; color: #10b981; padding: 16px; border-radius: var(--epice-radius); font-family: monospace; font-size: 0.85rem; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
</style>';

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('testemail', $course);
echo html_writer::end_div();

echo html_writer::start_div('col-md-9 col-lg-10');

if ($message) {
    $notifytype = $messagetype === 'success' ? 'success' : 'error';
    echo $OUTPUT->notification($message, $notifytype);
}

// Formulario.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', '<i class="fa fa-envelope"></i> ' . get_string('testemail', 'local_epicereports'), ['class' => 'epice-card-title']);
echo html_writer::end_div();
echo html_writer::start_div('epice-card-body');

$formurl = new moodle_url('/local/epicereports/test_email.php', [
    'courseid' => $courseid,
    'action' => 'send',
    'sesskey' => sesskey()
]);

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl->out(false)]);

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', get_string('email', 'local_epicereports') . ' *', ['class' => 'epice-form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'email',
    'name' => 'email',
    'required' => 'required',
    'class' => 'epice-form-input',
    'placeholder' => 'ejemplo@dominio.com',
    'value' => $USER->email ?? ''
]);
echo html_writer::end_div();

echo html_writer::start_div('epice-form-group');
echo html_writer::tag('label', '', ['class' => 'epice-form-label']);
echo html_writer::checkbox('attachment', 1, false, ' Incluir reporte Excel de prueba como adjunto');
echo html_writer::end_div();

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => 'Enviar correo de prueba',
    'class' => 'epice-btn epice-btn-success'
]);

echo html_writer::end_tag('form');

// Mostrar información de configuración.
echo html_writer::tag('h6', 'Configuración actual de correo:', ['class' => 'mt-4 mb-2']);
echo html_writer::start_tag('ul');
echo html_writer::tag('li', '<strong>SMTP:</strong> ' . ($CFG->smtphosts ?? 'No configurado (usando PHP mail())'));
echo html_writer::tag('li', '<strong>Noreply:</strong> ' . ($CFG->noreplyaddress ?? 'No configurado'));
echo html_writer::tag('li', '<strong>Emails deshabilitados:</strong> ' . (!empty($CFG->noemailever) ? '<span style="color:red">SÍ</span>' : 'No'));
echo html_writer::end_tag('ul');

echo html_writer::end_div();
echo html_writer::end_div();

// Mostrar debug si hay.
if (!empty($debug_info)) {
    echo html_writer::start_div('epice-card');
    echo html_writer::start_div('epice-card-header');
    echo html_writer::tag('h5', '<i class="fa fa-bug"></i> Información de diagnóstico', ['class' => 'epice-card-title']);
    echo html_writer::end_div();
    echo html_writer::start_div('epice-card-body');
    echo html_writer::tag('pre', implode("\n", $debug_info), ['class' => 'epice-debug']);
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
