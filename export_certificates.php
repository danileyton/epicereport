<?php
/**
 * Exportar diplomas emitidos de simplecertificate como ZIP
 *
 * @package    local_epicereports
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

// Parámetros.
$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT); // Course module ID específico (opcional).

// Validar.
if ($courseid <= 0) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// Cargar curso y contexto.
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

// Verificar que el módulo simplecertificate existe.
global $DB;
$dbman = $DB->get_manager();

if (!$dbman->table_exists('simplecertificate') || !$dbman->table_exists('simplecertificate_issues')) {
    throw new moodle_exception('error', 'local_epicereports', '', null,
        'El módulo simplecertificate no está instalado en esta plataforma.');
}

// Obtener todas las instancias de simplecertificate en el curso.
$certificates = $DB->get_records('simplecertificate', ['course' => $courseid]);

if (empty($certificates)) {
    // No hay certificados en este curso.
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/epicereports/export_certificates.php', ['courseid' => $courseid]));
    $PAGE->set_pagelayout('report');
    $PAGE->set_title(get_string('pluginname', 'local_epicereports'));
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('nocertificates', 'local_epicereports'), 'warning');
    echo $OUTPUT->single_button(
        new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]),
        get_string('back', 'local_epicereports'),
        'get'
    );
    echo $OUTPUT->footer();
    exit;
}

// Si se especificó un cmid, filtrar solo ese certificado.
if ($cmid > 0) {
    $cm = get_coursemodule_from_id('simplecertificate', $cmid, $courseid);
    if ($cm) {
        $certificates = [$cm->instance => $DB->get_record('simplecertificate', ['id' => $cm->instance])];
    }
}

// Recopilar todos los certificados emitidos.
$issued_certificates = [];

foreach ($certificates as $cert) {
    $sql = "SELECT si.*, u.firstname, u.lastname, u.username, u.email, sc.name as certname
              FROM {simplecertificate_issues} si
              JOIN {user} u ON u.id = si visibleuserid
              JOIN {simplecertificate} sc ON sc.id = si.certificateid
             WHERE si.certificateid = :certid
               AND si.timedeleted IS NULL
          ORDER BY u.lastname, u.firstname";

    // Corregir la consulta.
    $sql = "SELECT si.*, u.firstname, u.lastname, u.username, u.email, sc.name as certname
              FROM {simplecertificate_issues} si
              JOIN {user} u ON u.id = si.userid
              JOIN {simplecertificate} sc ON sc.id = si.certificateid
             WHERE si.certificateid = :certid
               AND (si.timedeleted IS NULL OR si.timedeleted = 0)
          ORDER BY u.lastname, u.firstname";

    $issues = $DB->get_records_sql($sql, ['certid' => $cert->id]);

    if (!empty($issues)) {
        foreach ($issues as $issue) {
            $issued_certificates[] = $issue;
        }
    }
}

if (empty($issued_certificates)) {
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/epicereports/export_certificates.php', ['courseid' => $courseid]));
    $PAGE->set_pagelayout('report');
    $PAGE->set_title(get_string('pluginname', 'local_epicereports'));
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('noissuedcertificates', 'local_epicereports'), 'info');
    echo $OUTPUT->single_button(
        new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]),
        get_string('back', 'local_epicereports'),
        'get'
    );
    echo $OUTPUT->footer();
    exit;
}

// Crear archivo ZIP con todos los certificados.
$tempdir = make_temp_directory('epicereports_certificates');
$zipfilename = 'diplomas_curso_' . $courseid . '_' . date('Ymd_His') . '.zip';
$zipfilepath = $tempdir . '/' . $zipfilename;

$zip = new ZipArchive();
if ($zip->open($zipfilepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new moodle_exception('error', 'local_epicereports', '', null, 'No se pudo crear el archivo ZIP.');
}

$files_added = 0;
$fs = get_file_storage();

foreach ($issued_certificates as $issue) {
    // Obtener el archivo del certificado desde Moodle file storage.
    // Los certificados de simplecertificate se guardan en el área 'issues'.
    
    // Buscar el course module para obtener el contexto correcto.
    $cm = get_coursemodule_from_instance('simplecertificate', $issue->certificateid, $courseid);
    if (!$cm) {
        continue;
    }
    
    $modulecontext = context_module::instance($cm->id);
    
    // Buscar el archivo PDF del certificado.
    $files = $fs->get_area_files(
        $modulecontext->id,
        'mod_simplecertificate',
        'issues',
        $issue->id,
        'timemodified DESC',
        false
    );
    
    if (!empty($files)) {
        $file = reset($files);
        
        // Nombre del archivo en el ZIP.
        $username_clean = clean_filename($issue->username);
        $fullname_clean = clean_filename($issue->lastname . '_' . $issue->firstname);
        $certname_clean = clean_filename($issue->certname);
        
        $filename_in_zip = $certname_clean . '/' . $fullname_clean . '_' . $username_clean . '_' . $issue->id . '.pdf';
        
        // Añadir al ZIP.
        $zip->addFromString($filename_in_zip, $file->get_content());
        $files_added++;
    } else {
        // Intentar generar el PDF si no existe el archivo.
        // Algunos certificados se generan on-the-fly.
        
        // Verificar si existe el pathnamehash en el issue.
        if (!empty($issue->pathnamehash)) {
            $file = $fs->get_file_by_hash($issue->pathnamehash);
            if ($file && !$file->is_directory()) {
                $username_clean = clean_filename($issue->username);
                $fullname_clean = clean_filename($issue->lastname . '_' . $issue->firstname);
                $certname_clean = clean_filename($issue->certname);
                
                $filename_in_zip = $certname_clean . '/' . $fullname_clean . '_' . $username_clean . '_' . $issue->id . '.pdf';
                
                $zip->addFromString($filename_in_zip, $file->get_content());
                $files_added++;
            }
        }
    }
}

$zip->close();

if ($files_added === 0) {
    // No se encontraron archivos PDF.
    @unlink($zipfilepath);
    
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/epicereports/export_certificates.php', ['courseid' => $courseid]));
    $PAGE->set_pagelayout('report');
    $PAGE->set_title(get_string('pluginname', 'local_epicereports'));
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('nofilesfound', 'local_epicereports'), 'warning');
    echo html_writer::tag('p', get_string('nofilesfound_help', 'local_epicereports'));
    echo $OUTPUT->single_button(
        new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]),
        get_string('back', 'local_epicereports'),
        'get'
    );
    echo $OUTPUT->footer();
    exit;
}

// Enviar el archivo ZIP al navegador.
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipfilename . '"');
header('Content-Length: ' . filesize($zipfilepath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($zipfilepath);

// Limpiar archivo temporal.
@unlink($zipfilepath);

exit;