<?php
/**
 * Dashboard principal de EpicE Reports
 *
 * @package    local_epicereports
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\helper;

// Requiere login.
require_login();

// Contexto y capacidades.
$context = context_system::instance();
require_capability('local/epicereports:view', $context);

// Configurar página.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/dashboard.php'));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'local_epicereports'));
$PAGE->set_heading(get_string('pluginname', 'local_epicereports'));

// Breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'local_epicereports'));

// Obtener estadísticas.
$stats = helper::get_dashboard_statistics();

// Preparar configuración para el gráfico JavaScript.
$chart_config = [
    'activeUsers' => (int)$stats['active_users'],
    'visibleCourses' => (int)$stats['visible_courses'],
    'hiddenCourses' => (int)$stats['hidden_courses'],
    'totalEnrolments' => (int)$stats['total_enrolments'],
    'satisfactionPercent' => (float)$stats['satisfaction_percent'],
    'strings' => [
        'visible' => get_string('visible', 'local_epicereports'),
        'hidden' => get_string('hidden', 'local_epicereports'),
    ]
];

// Cargar módulo AMD para el gráfico.
$PAGE->requires->js_call_amd('local_epicereports/dashboard', 'init', [$chart_config]);

// =========================================================================
// RENDERIZADO
// =========================================================================
echo $OUTPUT->header();

// Layout con menú lateral + contenido.
echo html_writer::start_div('row');

// =========================================================================
// COLUMNA IZQUIERDA: Menú lateral
// =========================================================================
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('dashboard');
echo html_writer::end_div();

// =========================================================================
// COLUMNA DERECHA: Contenido principal
// =========================================================================
echo html_writer::start_div('col-md-9 col-lg-10');

// -------------------------------------------------------------------------
// Tarjetas de estadísticas
// -------------------------------------------------------------------------
echo html_writer::start_div('row');

// --- Tarjeta: Usuarios activos ---
echo render_stat_card(
    $stats['active_users'],
    get_string('active_users', 'local_epicereports'),
    'fa-users',
    'primary'
);

// --- Tarjeta: Cursos totales ---
$total_courses = $stats['visible_courses'] + $stats['hidden_courses'];
echo render_stat_card(
    $total_courses,
    get_string('courses', 'local_epicereports'),
    'fa-book',
    'success',
    get_string('visible', 'local_epicereports') . ': ' . $stats['visible_courses'] . ' | ' .
    get_string('hidden', 'local_epicereports') . ': ' . $stats['hidden_courses']
);

// --- Tarjeta: Matrículas totales ---
echo render_stat_card(
    $stats['total_enrolments'],
    get_string('total_enrolments', 'local_epicereports'),
    'fa-user-graduate',
    'info'
);

// --- Tarjeta: Porcentaje de satisfacción ---
$satisfaction_value = number_format($stats['satisfaction_percent'], 1) . '%';
echo render_stat_card(
    $satisfaction_value,
    get_string('satisfaction_percent', 'local_epicereports'),
    'fa-smile',
    'warning'
);

echo html_writer::end_div(); // row de tarjetas

// -------------------------------------------------------------------------
// Sección de gráficos
// -------------------------------------------------------------------------
echo html_writer::start_div('row mt-4');

// --- Gráfico: Distribución de cursos ---
echo html_writer::start_div('col-lg-6 mb-4');
echo html_writer::start_div('card shadow-sm h-100');
echo html_writer::start_div('card-header');
echo html_writer::tag('h6', get_string('courses', 'local_epicereports'), ['class' => 'mb-0 font-weight-bold']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');
echo html_writer::start_div('chart-container', ['style' => 'position: relative; height: 250px;']);
echo html_writer::tag('canvas', '', ['id' => 'coursesChart']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// --- Información adicional o segundo gráfico ---
echo html_writer::start_div('col-lg-6 mb-4');
echo html_writer::start_div('card shadow-sm h-100');
echo html_writer::start_div('card-header');
echo html_writer::tag('h6', get_string('summary', 'local_epicereports'), ['class' => 'mb-0 font-weight-bold']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');

// Lista de resumen.
echo html_writer::start_tag('ul', ['class' => 'list-group list-group-flush']);

echo html_writer::tag('li', 
    html_writer::tag('span', get_string('active_users', 'local_epicereports'), ['class' => 'font-weight-bold']) .
    html_writer::tag('span', $stats['active_users'], ['class' => 'badge badge-primary float-right']),
    ['class' => 'list-group-item d-flex justify-content-between align-items-center']
);

echo html_writer::tag('li', 
    html_writer::tag('span', get_string('visible_courses', 'local_epicereports'), ['class' => 'font-weight-bold']) .
    html_writer::tag('span', $stats['visible_courses'], ['class' => 'badge badge-success float-right']),
    ['class' => 'list-group-item d-flex justify-content-between align-items-center']
);

echo html_writer::tag('li', 
    html_writer::tag('span', get_string('hidden_courses', 'local_epicereports'), ['class' => 'font-weight-bold']) .
    html_writer::tag('span', $stats['hidden_courses'], ['class' => 'badge badge-secondary float-right']),
    ['class' => 'list-group-item d-flex justify-content-between align-items-center']
);

echo html_writer::tag('li', 
    html_writer::tag('span', get_string('total_enrolments', 'local_epicereports'), ['class' => 'font-weight-bold']) .
    html_writer::tag('span', $stats['total_enrolments'], ['class' => 'badge badge-info float-right']),
    ['class' => 'list-group-item d-flex justify-content-between align-items-center']
);

echo html_writer::tag('li', 
    html_writer::tag('span', get_string('satisfaction_percent', 'local_epicereports'), ['class' => 'font-weight-bold']) .
    html_writer::tag('span', $satisfaction_value, ['class' => 'badge badge-warning float-right']),
    ['class' => 'list-group-item d-flex justify-content-between align-items-center']
);

echo html_writer::end_tag('ul');

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col-lg-6

echo html_writer::end_div(); // row de gráficos

// -------------------------------------------------------------------------
// Accesos rápidos
// -------------------------------------------------------------------------
echo html_writer::start_div('row mt-2');
echo html_writer::start_div('col-12');
echo html_writer::start_div('card shadow-sm');
echo html_writer::start_div('card-header');
echo html_writer::tag('h6', get_string('actions', 'local_epicereports'), ['class' => 'mb-0 font-weight-bold']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');

echo html_writer::start_div('btn-group', ['role' => 'group']);

// Botón: Ver todos los cursos.
echo html_writer::link(
    new moodle_url('/local/epicereports/courses.php'),
    html_writer::tag('i', '', ['class' => 'fa fa-list mr-2']) . 
    get_string('courselist', 'local_epicereports'),
    ['class' => 'btn btn-outline-primary']
);

// Botón: Ir a la plataforma.
echo html_writer::link(
    new moodle_url('/my/'),
    html_writer::tag('i', '', ['class' => 'fa fa-home mr-2']) . 
    get_string('backtoplatform', 'local_epicereports'),
    ['class' => 'btn btn-outline-secondary']
);

echo html_writer::end_div(); // btn-group

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col-12
echo html_writer::end_div(); // row

// Cierre columna derecha.
echo html_writer::end_div(); // col-md-9 col-lg-10

// Cierre fila principal.
echo html_writer::end_div(); // row

echo $OUTPUT->footer();

// =========================================================================
// FUNCIONES AUXILIARES
// =========================================================================

/**
 * Renderiza una tarjeta de estadística.
 *
 * @param mixed $value Valor a mostrar.
 * @param string $label Etiqueta descriptiva.
 * @param string $icon Clase del icono FontAwesome (sin 'fa').
 * @param string $color Color de Bootstrap (primary, success, info, warning, danger).
 * @param string|null $subtitle Subtítulo opcional.
 * @return string HTML de la tarjeta.
 */
function render_stat_card($value, string $label, string $icon, string $color = 'primary', ?string $subtitle = null): string {
    $output = '';

    $output .= html_writer::start_div('col-xl-3 col-md-6 mb-4');
    $output .= html_writer::start_div('card border-left-' . $color . ' shadow-sm h-100 py-2');
    $output .= html_writer::start_div('card-body');
    $output .= html_writer::start_div('row no-gutters align-items-center');

    // Columna de contenido.
    $output .= html_writer::start_div('col mr-2');
    $output .= html_writer::tag('div', $label, [
        'class' => 'text-xs font-weight-bold text-' . $color . ' text-uppercase mb-1'
    ]);
    $output .= html_writer::tag('div', $value, [
        'class' => 'h5 mb-0 font-weight-bold text-gray-800'
    ]);

    // Subtítulo opcional.
    if ($subtitle) {
        $output .= html_writer::tag('div', $subtitle, [
            'class' => 'text-xs text-muted mt-1'
        ]);
    }

    $output .= html_writer::end_div(); // col mr-2

    // Columna del icono.
    $output .= html_writer::start_div('col-auto');
    $output .= html_writer::tag('i', '', [
        'class' => 'fa ' . $icon . ' fa-2x text-gray-300'
    ]);
    $output .= html_writer::end_div(); // col-auto

    $output .= html_writer::end_div(); // row
    $output .= html_writer::end_div(); // card-body
    $output .= html_writer::end_div(); // card
    $output .= html_writer::end_div(); // col-xl-3

    return $output;
}