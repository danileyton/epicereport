<?php
/**
 * Página de detalle de un curso - Diseño mejorado
 *
 * @package    local_epicereports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\helper;

$courseid = required_param('id', PARAM_INT);

if ($courseid <= 0) {
    throw new moodle_exception('invalidcourseid', 'error');
}

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('coursedetail', 'local_epicereports') . ': ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

// CSS personalizado.
$PAGE->requires->css('/local/epicereports/css/styles.css');

// jQuery y DataTables.
$PAGE->requires->jquery();
$PAGE->requires->css(new moodle_url('https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css'));

$PAGE->requires->js_init_code("
    require.config({
        paths: {
            'datatables.net': 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min'
        },
        shim: {
            'datatables.net': {
                deps: ['jquery'],
                exports: 'jQuery.fn.dataTable'
            }
        }
    });

    require(['jquery', 'datatables.net'], function($) {
        $('#users-table').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
            order: [[0, 'asc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
            }
        });
    });
");

$course_data = helper::get_course_data_for_excel($courseid);

echo $OUTPUT->header();

// Layout principal.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('course_detail', $course);
echo html_writer::end_div();

// Contenido principal.
echo html_writer::start_div('col-md-9 col-lg-10');

// Tarjeta de información del curso.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-info-circle']) . ' ' . get_string('generalinfo', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');
echo html_writer::start_div('epice-course-info');

// Nombre del curso.
echo html_writer::start_div('epice-info-item');
echo html_writer::start_div('epice-info-icon');
echo html_writer::tag('i', '', ['class' => 'fa fa-book']);
echo html_writer::end_div();
echo html_writer::start_div('epice-info-content');
echo html_writer::tag('div', get_string('course', 'local_epicereports'), ['class' => 'epice-info-label']);
echo html_writer::tag('div', format_string($course_data['course']->fullname), ['class' => 'epice-info-value']);
echo html_writer::end_div();
echo html_writer::end_div();

// Nombre corto.
echo html_writer::start_div('epice-info-item');
echo html_writer::start_div('epice-info-icon');
echo html_writer::tag('i', '', ['class' => 'fa fa-tag']);
echo html_writer::end_div();
echo html_writer::start_div('epice-info-content');
echo html_writer::tag('div', get_string('shortname', 'local_epicereports'), ['class' => 'epice-info-label']);
echo html_writer::tag('div', s($course_data['course']->shortname), ['class' => 'epice-info-value']);
echo html_writer::end_div();
echo html_writer::end_div();

// Visibilidad.
echo html_writer::start_div('epice-info-item');
$vis_icon_style = $course_data['course']->visible 
    ? 'background: rgba(16, 185, 129, 0.1); color: #10b981;' 
    : 'background: rgba(100, 116, 139, 0.1); color: #64748b;';
echo html_writer::start_div('epice-info-icon', ['style' => $vis_icon_style]);
echo html_writer::tag('i', '', ['class' => 'fa fa-eye']);
echo html_writer::end_div();
echo html_writer::start_div('epice-info-content');
echo html_writer::tag('div', get_string('visibility', 'local_epicereports'), ['class' => 'epice-info-label']);
$visibility_text = $course_data['course']->visible 
    ? get_string('visible', 'local_epicereports') 
    : get_string('hidden', 'local_epicereports');
echo html_writer::tag('div', $visibility_text, ['class' => 'epice-info-value']);
echo html_writer::end_div();
echo html_writer::end_div();

// Matriculados.
$enrolled_count = count($course_data['users']);
echo html_writer::start_div('epice-info-item');
echo html_writer::start_div('epice-info-icon', ['style' => 'background: rgba(245, 158, 11, 0.1); color: #f59e0b;']);
echo html_writer::tag('i', '', ['class' => 'fa fa-users']);
echo html_writer::end_div();
echo html_writer::start_div('epice-info-content');
echo html_writer::tag('div', get_string('enrolled', 'local_epicereports'), ['class' => 'epice-info-label']);
echo html_writer::tag('div', $enrolled_count . ' ' . get_string('students', 'local_epicereports'), ['class' => 'epice-info-value']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // course-info
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Botones de exportación.
echo html_writer::start_div('epice-btn-group');

// Excel.
$excel_url = new moodle_url('/local/epicereports/export_course_excel.php', ['courseid' => $courseid]);
echo html_writer::link($excel_url, 
    html_writer::tag('i', '', ['class' => 'fa fa-file-excel-o']) . ' ' . get_string('exporttoexcel', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-success', 'target' => '_blank']
);

// Vista previa.
$preview_url = new moodle_url('/local/epicereports/export_course_excel.php', ['courseid' => $courseid, 'preview' => 1]);
echo html_writer::link($preview_url, 
    html_writer::tag('i', '', ['class' => 'fa fa-eye']) . ' ' . get_string('preview', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-outline', 'target' => '_blank']
);

// Certificados (si existen).
$has_certificates = check_course_has_certificates($courseid);
if ($has_certificates) {
    $cert_url = new moodle_url('/local/epicereports/export_certificates.php', ['courseid' => $courseid]);
    echo html_writer::link($cert_url, 
        html_writer::tag('i', '', ['class' => 'fa fa-certificate']) . ' ' . get_string('downloadcertificates', 'local_epicereports'),
        ['class' => 'epice-btn epice-btn-info']
    );
}

// Feedback (si existe).
$has_feedback = check_course_has_feedback($courseid);
if ($has_feedback) {
    $feedback_url = new moodle_url('/local/epicereports/export_feedback.php', ['courseid' => $courseid]);
    echo html_writer::link($feedback_url, 
        html_writer::tag('i', '', ['class' => 'fa fa-comments']) . ' ' . get_string('exportfeedback', 'local_epicereports'),
        ['class' => 'epice-btn epice-btn-warning']
    );
}

echo html_writer::end_div(); // btn-group

// Tabla de usuarios.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-users']) . ' ' . get_string('enrolledusers', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

if (empty($course_data['users'])) {
    echo html_writer::start_div('epice-card-body');
    echo $OUTPUT->notification(get_string('nousers', 'local_epicereports'), 'info');
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('epice-table-container');
    
    echo html_writer::start_tag('table', [
        'id' => 'users-table',
        'class' => 'epice-table',
        'style' => 'width: 100%'
    ]);

    // Cabeceras.
    $headers = [
        get_string('fullname', 'local_epicereports'),
        get_string('email', 'local_epicereports'),
        get_string('firstaccess', 'local_epicereports'),
        get_string('lastaccess', 'local_epicereports'),
        get_string('groups', 'local_epicereports'),
        get_string('progress', 'local_epicereports'),
        get_string('completionstatus', 'local_epicereports'),
        get_string('finalgrade', 'local_epicereports')
    ];

    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    foreach ($headers as $header) {
        echo html_writer::tag('th', $header);
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');

    foreach ($course_data['users'] as $user) {
        echo html_writer::start_tag('tr');

        // Nombre.
        echo html_writer::tag('td', s($user->fullname), ['style' => 'font-weight: 500;']);

        // Email.
        echo html_writer::tag('td', s($user->email));

        // Primer acceso.
        $primer_acceso = !empty($user->primer_acceso) ? $user->primer_acceso : '-';
        echo html_writer::tag('td', s($primer_acceso));

        // Último acceso.
        $ultimo_acceso = !empty($user->ultimo_acceso) ? $user->ultimo_acceso : '-';
        echo html_writer::tag('td', s($ultimo_acceso));

        // Grupos.
        $grupos = !empty($user->grupos) ? $user->grupos : '-';
        echo html_writer::tag('td', s($grupos));

        // Progreso con barra.
        $porcentaje = $user->porcentaje_avance ?? '0%';
        $porcentaje_num = (float)str_replace('%', '', $porcentaje);
        echo html_writer::tag('td', local_epicereports_render_progress_bar($porcentaje_num));

        // Estado.
        $estado = $user->estado_finalizacion ?? '-';
        $badge_type = 'secondary';
        if ($estado === 'Completado') {
            $badge_type = 'success';
        } else if ($estado === 'En progreso') {
            $badge_type = 'warning';
        }
        echo html_writer::tag('td', html_writer::tag('span', s($estado), ['class' => 'epice-badge epice-badge-' . $badge_type]));

        // Nota final.
        $nota_final = !empty($user->nota_final) ? $user->nota_final : '-';
        echo html_writer::tag('td', s($nota_final), ['style' => 'font-weight: 600;']);

        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div(); // table-container
}

echo html_writer::end_div(); // card

echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row

echo $OUTPUT->footer();

// Funciones auxiliares.
function check_course_has_certificates(int $courseid): bool {
    global $DB;
    $dbman = $DB->get_manager();
    
    if (!$dbman->table_exists('simplecertificate') || !$dbman->table_exists('simplecertificate_issues')) {
        return false;
    }
    
    $certificates = $DB->get_records('simplecertificate', ['course' => $courseid], '', 'id');
    if (empty($certificates)) {
        return false;
    }
    
    $certids = array_keys($certificates);
    list($insql, $params) = $DB->get_in_or_equal($certids, SQL_PARAMS_NAMED);
    
    $sql = "SELECT COUNT(*) FROM {simplecertificate_issues} WHERE certificateid $insql AND (timedeleted IS NULL OR timedeleted = 0)";
    return $DB->count_records_sql($sql, $params) > 0;
}

function check_course_has_feedback(int $courseid): bool {
    global $DB;
    $dbman = $DB->get_manager();
    
    if (!$dbman->table_exists('feedback') || !$dbman->table_exists('feedback_completed')) {
        return false;
    }
    
    $feedbacks = $DB->get_records('feedback', ['course' => $courseid], '', 'id');
    if (empty($feedbacks)) {
        return false;
    }
    
    $feedbackids = array_keys($feedbacks);
    list($insql, $params) = $DB->get_in_or_equal($feedbackids, SQL_PARAMS_NAMED);
    
    $sql = "SELECT COUNT(*) FROM {feedback_completed} WHERE feedback $insql";
    return $DB->count_records_sql($sql, $params) > 0;
}