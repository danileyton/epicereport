<?php
/**
 * Página de detalle de un curso
 *
 * @package    local_epicereports
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\helper;

// Parámetro: ID del curso (estandarizado a 'id').
$courseid = required_param('id', PARAM_INT);

// Validar courseid.
if ($courseid <= 0) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// Cargar curso y contexto.
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);

// Verificar capacidad.
require_capability('local/epicereports:view', $context);

// Configurar página.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('coursedetail', 'local_epicereports') . ': ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

// Añadir navegación breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'local_epicereports'), 
    new moodle_url('/local/epicereports/dashboard.php'));
$PAGE->navbar->add(get_string('courses', 'local_epicereports'), 
    new moodle_url('/local/epicereports/courses.php'));
$PAGE->navbar->add(format_string($course->shortname));

// CSS de DataTables (local).
$PAGE->requires->css('/local/epicereports/css/jquery.dataTables.min.css');

// Incluir jQuery.
$PAGE->requires->jquery();

// Cargar DataTables de forma compatible con RequireJS.
$dtjspath = (new moodle_url('/local/epicereports/js/jquery.dataTables.min.js'))->out(false);
$langurl = (new moodle_url('/local/epicereports/js/dataTables.spanish.json'))->out(false);

$PAGE->requires->js_init_code("
    require(['jquery'], function($) {
        // Guardar referencia a define/AMD.
        var oldDefine = window.define;
        var oldDefineAmd = oldDefine && oldDefine.amd;

        // Desactivar AMD temporalmente.
        if (oldDefine && oldDefine.amd) {
            oldDefine.amd = undefined;
        }

        // Cargar DataTables manualmente.
        var script = document.createElement('script');
        script.src = '{$dtjspath}';
        script.onload = function() {
            // Restaurar define/AMD.
            if (oldDefine) {
                window.define = oldDefine;
                if (oldDefineAmd) {
                    oldDefine.amd = oldDefineAmd;
                }
            }

            // Inicializar DataTable.
            $('#users-table').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, '" . get_string('all', 'local_epicereports') . "']],
                order: [[0, 'asc']],
                language: {
                    url: '{$langurl}'
                },
                dom: '<\"row\"<\"col-sm-12 col-md-6\"l><\"col-sm-12 col-md-6\"f>>' +
                     '<\"row\"<\"col-sm-12\"tr>>' +
                     '<\"row\"<\"col-sm-12 col-md-5\"i><\"col-sm-12 col-md-7\"p>>',
                responsive: true
            });
        };
        script.onerror = function() {
            console.error('Error loading DataTables');
        };
        document.head.appendChild(script);
    });
");

// Obtener datos del helper.
$course_data = helper::get_course_data_for_excel($courseid);

// Renderizar página.
echo $OUTPUT->header();

// Layout con menú lateral + contenido.
echo html_writer::start_div('row');

// =========================================================================
// COLUMNA IZQUIERDA: Menú lateral
// =========================================================================
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('course_detail', $course);
echo html_writer::end_div();

// =========================================================================
// COLUMNA DERECHA: Contenido principal
// =========================================================================
echo html_writer::start_div('col-md-9 col-lg-10');

// -------------------------------------------------------------------------
// Tarjeta: Información general del curso
// -------------------------------------------------------------------------
echo html_writer::start_div('card shadow-sm mb-4');
echo html_writer::start_div('card-header bg-primary text-white');
echo html_writer::tag('h5', get_string('generalinfo', 'local_epicereports'), ['class' => 'mb-0']);
echo html_writer::end_div();

echo html_writer::start_div('card-body');

echo html_writer::start_div('row');

// Columna 1: Nombre y nombre corto.
echo html_writer::start_div('col-md-6');
echo html_writer::tag('p', 
    html_writer::tag('strong', get_string('course', 'local_epicereports') . ': ') . 
    format_string($course_data['course']->fullname)
);
echo html_writer::tag('p', 
    html_writer::tag('strong', get_string('shortname', 'local_epicereports') . ': ') . 
    s($course_data['course']->shortname)
);
echo html_writer::end_div();

// Columna 2: Visibilidad y matriculados.
echo html_writer::start_div('col-md-6');

$visibility_badge = $course_data['course']->visible 
    ? html_writer::tag('span', get_string('visible', 'local_epicereports'), ['class' => 'badge badge-success'])
    : html_writer::tag('span', get_string('hidden', 'local_epicereports'), ['class' => 'badge badge-secondary']);

echo html_writer::tag('p', 
    html_writer::tag('strong', get_string('visibility', 'local_epicereports') . ': ') . 
    $visibility_badge
);

$enrolled_count = count($course_data['users']);
echo html_writer::tag('p', 
    html_writer::tag('strong', get_string('enrolled', 'local_epicereports') . ': ') . 
    html_writer::tag('span', $enrolled_count, ['class' => 'badge badge-info'])
);

echo html_writer::end_div();

echo html_writer::end_div(); // row

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// -------------------------------------------------------------------------
// Botones de exportación
// -------------------------------------------------------------------------
echo html_writer::start_div('mb-4');

// Fila 1: Botones principales de reporte.
echo html_writer::start_div('btn-group mr-2 mb-2', ['role' => 'group']);

// Botón Exportar a Excel.
$excel_url = new moodle_url('/local/epicereports/export_course_excel.php', ['courseid' => $courseid]);
echo html_writer::link($excel_url, 
    html_writer::tag('i', '', ['class' => 'fa fa-file-excel-o mr-2']) . 
    get_string('exporttoexcel', 'local_epicereports'), 
    [
        'class' => 'btn btn-success',
        'target' => '_blank'
    ]
);

// Botón Vista previa.
$preview_url = new moodle_url('/local/epicereports/export_course_excel.php', [
    'courseid' => $courseid,
    'preview' => 1
]);
echo html_writer::link($preview_url, 
    html_writer::tag('i', '', ['class' => 'fa fa-eye mr-2']) . 
    get_string('preview', 'local_epicereports'), 
    [
        'class' => 'btn btn-outline-secondary',
        'target' => '_blank'
    ]
);

echo html_writer::end_div(); // btn-group

// Fila 2: Botones adicionales (Diplomas y Feedback).
// Verificar si existen módulos de simplecertificate y feedback en el curso.
$has_certificates = check_course_has_certificates($courseid);
$has_feedback = check_course_has_feedback($courseid);

if ($has_certificates || $has_feedback) {
    echo html_writer::start_div('btn-group mb-2', ['role' => 'group']);
    
    // Botón Descargar Diplomas (solo si hay certificados).
    if ($has_certificates) {
        $cert_url = new moodle_url('/local/epicereports/export_certificates.php', ['courseid' => $courseid]);
        echo html_writer::link($cert_url, 
            html_writer::tag('i', '', ['class' => 'fa fa-certificate mr-2']) . 
            get_string('downloadcertificates', 'local_epicereports'), 
            [
                'class' => 'btn btn-info',
                'title' => get_string('downloadcertificates_help', 'local_epicereports')
            ]
        );
    }
    
    // Botón Exportar Feedback (solo si hay feedback).
    if ($has_feedback) {
        $feedback_url = new moodle_url('/local/epicereports/export_feedback.php', ['courseid' => $courseid]);
        echo html_writer::link($feedback_url, 
            html_writer::tag('i', '', ['class' => 'fa fa-comments mr-2']) . 
            get_string('exportfeedback', 'local_epicereports'), 
            [
                'class' => 'btn btn-warning',
                'title' => get_string('exportfeedback_help', 'local_epicereports')
            ]
        );
    }
    
    echo html_writer::end_div(); // btn-group
}

echo html_writer::end_div(); // mb-4

// -------------------------------------------------------------------------
// Tabla de usuarios matriculados
// -------------------------------------------------------------------------
echo html_writer::start_div('card shadow-sm mb-4');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', get_string('enrolledusers', 'local_epicereports'), ['class' => 'mb-0']);
echo html_writer::end_div();

echo html_writer::start_div('card-body p-0');

if (empty($course_data['users'])) {
    echo html_writer::div(
        $OUTPUT->notification(get_string('nousers', 'local_epicereports'), 'info'),
        'p-3'
    );
} else {
    echo html_writer::start_div('table-responsive');

    // Tabla con DataTables.
    echo html_writer::start_tag('table', [
        'class' => 'table table-striped table-bordered table-hover mb-0',
        'id' => 'users-table',
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

    echo html_writer::start_tag('thead', ['class' => 'thead-light']);
    echo html_writer::start_tag('tr');
    foreach ($headers as $header) {
        echo html_writer::tag('th', $header);
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    // Cuerpo de la tabla.
    echo html_writer::start_tag('tbody');

    foreach ($course_data['users'] as $user) {
        echo html_writer::start_tag('tr');

        // Nombre completo.
        echo html_writer::tag('td', s($user->fullname));

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

        // Porcentaje de avance con barra de progreso.
        $porcentaje = $user->porcentaje_avance ?? '0%';
        $porcentaje_num = (float)str_replace('%', '', $porcentaje);

        $progress_class = 'bg-danger';
        if ($porcentaje_num >= 100) {
            $progress_class = 'bg-success';
        } else if ($porcentaje_num >= 50) {
            $progress_class = 'bg-warning';
        } else if ($porcentaje_num >= 25) {
            $progress_class = 'bg-info';
        }

        $progress_bar = html_writer::start_div('progress', ['style' => 'height: 20px; min-width: 100px;']);
        $progress_bar .= html_writer::div($porcentaje, 'progress-bar ' . $progress_class, [
            'role' => 'progressbar',
            'style' => 'width: ' . $porcentaje_num . '%',
            'aria-valuenow' => $porcentaje_num,
            'aria-valuemin' => '0',
            'aria-valuemax' => '100'
        ]);
        $progress_bar .= html_writer::end_div();

        echo html_writer::tag('td', $progress_bar);

        // Estado de finalización con badge.
        $estado = $user->estado_finalizacion ?? '-';
        $estado_class = 'badge-secondary';
        if ($estado === 'Completado') {
            $estado_class = 'badge-success';
        } else if ($estado === 'En progreso') {
            $estado_class = 'badge-warning';
        }
        echo html_writer::tag('td', html_writer::tag('span', s($estado), ['class' => 'badge ' . $estado_class]));

        // Nota final.
        $nota_final = !empty($user->nota_final) ? $user->nota_final : '-';
        echo html_writer::tag('td', s($nota_final));

        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::end_div(); // table-responsive
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Cierre columna derecha.
echo html_writer::end_div(); // col-md-9 col-lg-10

// Cierre fila principal.
echo html_writer::end_div(); // row

echo $OUTPUT->footer();

// =========================================================================
// FUNCIONES AUXILIARES
// =========================================================================

/**
 * Verifica si el curso tiene módulos de simplecertificate con diplomas emitidos.
 *
 * @param int $courseid ID del curso.
 * @return bool True si hay certificados emitidos.
 */
function check_course_has_certificates(int $courseid): bool {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    // Verificar que las tablas existen.
    if (!$dbman->table_exists('simplecertificate') || !$dbman->table_exists('simplecertificate_issues')) {
        return false;
    }
    
    // Verificar si hay certificados en el curso.
    $certificates = $DB->get_records('simplecertificate', ['course' => $courseid], '', 'id');
    
    if (empty($certificates)) {
        return false;
    }
    
    // Verificar si hay al menos un diploma emitido.
    $certids = array_keys($certificates);
    list($insql, $params) = $DB->get_in_or_equal($certids, SQL_PARAMS_NAMED);
    
    $sql = "SELECT COUNT(*)
              FROM {simplecertificate_issues}
             WHERE certificateid $insql
               AND (timedeleted IS NULL OR timedeleted = 0)";
    
    $count = $DB->count_records_sql($sql, $params);
    
    return $count > 0;
}

/**
 * Verifica si el curso tiene módulos de feedback con respuestas.
 *
 * @param int $courseid ID del curso.
 * @return bool True si hay feedback con respuestas.
 */
function check_course_has_feedback(int $courseid): bool {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    // Verificar que las tablas existen.
    if (!$dbman->table_exists('feedback') || !$dbman->table_exists('feedback_completed')) {
        return false;
    }
    
    // Verificar si hay feedback en el curso.
    $feedbacks = $DB->get_records('feedback', ['course' => $courseid], '', 'id');
    
    if (empty($feedbacks)) {
        return false;
    }
    
    // Verificar si hay al menos una respuesta completada.
    $feedbackids = array_keys($feedbacks);
    list($insql, $params) = $DB->get_in_or_equal($feedbackids, SQL_PARAMS_NAMED);
    
    $sql = "SELECT COUNT(*)
              FROM {feedback_completed}
             WHERE feedback $insql";
    
    $count = $DB->count_records_sql($sql, $params);
    
    return $count > 0;
}