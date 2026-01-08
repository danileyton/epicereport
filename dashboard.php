<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php'); // <<< importante para el menú lateral

require_login();
// ¡REACTIVAR ANTES DE PONER EN PRODUCCIÓN!
// $context = context_system::instance();
// require_capability('local/epicereports:view', $context);

$context = context_system::instance();
$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/local/epicereports/dashboard.php'));
$PAGE->set_title(get_string('pluginname', 'local_epicereports'));
$PAGE->set_heading(get_string('pluginname', 'local_epicereports'));
// Mejor usar un layout de admin para que se vea como las otras pantallas
$PAGE->set_pagelayout('popup');

use local_epicereports\helper;

// Obtenemos los datos usando el helper.
$stats = helper::get_dashboard_statistics();

// Preparamos la configuración para pasarle a JavaScript (gráfico).
$chart_config = [
    'activeUsers'        => $stats['active_users'],
    'visibleCourses'     => $stats['visible_courses'],
    'hiddenCourses'      => $stats['hidden_courses'],
    'satisfactionPercent'=> $stats['satisfaction_percent'], // NUEVO
];


// Cargamos nuestro módulo AMD y le pasamos la config.
// (Lo hacemos ANTES del header para que Moodle incluya bien el JS en la cabecera).
$PAGE->requires->js_call_amd('local_epicereports/dashboard', 'init', [$chart_config]);

echo $OUTPUT->header();
echo $OUTPUT->heading('Dashboard de EpicE Reports');

// -------------------------------------------------------------------------
// Layout: fila con menú lateral (izquierda) + contenido (derecha)
// -------------------------------------------------------------------------
echo html_writer::start_div('row');

// Columna izquierda: menú lateral
echo html_writer::start_div('col-md-3 col-lg-2');
local_epicereports_render_sidebar('dashboard'); // << activa el ítem "Dashboard"
echo html_writer::end_div();

// Columna derecha: contenido del dashboard (tarjetas + gráfico)
echo html_writer::start_div('col-md-9 col-lg-10');

// Puedes seguir usando container-fluid dentro de la columna para jugar con márgenes
echo html_writer::start_div('container-fluid');

// -------------------------------------------------------------------------
// Tarjetas superiores (Usuarios activos, Cursos, etc.)
// -------------------------------------------------------------------------

 
echo html_writer::start_div('row mb-4');

// --- Usuarios activos ---
echo html_writer::start_div('col-12 col-sm-6 col-lg-3 mb-3');
echo html_writer::start_div('card shadow-sm h-100');
echo html_writer::start_div('card-body d-flex align-items-center');

    // Icono
    echo html_writer::start_div('mr-3');
    echo html_writer::tag('i', '', ['class' => 'fas fa-users fa-2x text-primary']);
    echo html_writer::end_div();

    // Número + texto
    echo html_writer::start_div();
    echo html_writer::tag('div', $stats['active_users'], [
        'class' => 'h3 mb-0 font-weight-bold text-gray-800'
    ]);
    echo html_writer::tag('div', 'Usuarios activos', [
        'class' => 'text-muted small mt-1'
    ]);
    echo html_writer::end_div();

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col


// --- Cursos totales ---
echo html_writer::start_div('col-12 col-sm-6 col-lg-3 mb-3');
echo html_writer::start_div('card shadow-sm h-100');
echo html_writer::start_div('card-body d-flex align-items-center');

    echo html_writer::start_div('mr-3');
    echo html_writer::tag('i', '', ['class' => 'fas fa-book fa-2x text-success']);
    echo html_writer::end_div();

    echo html_writer::start_div();
    echo html_writer::tag('div',
        $stats['visible_courses'] + $stats['hidden_courses'],
        ['class' => 'h3 mb-0 font-weight-bold text-gray-800']
    );
    echo html_writer::tag('div', 'Cursos totales', [
        'class' => 'text-muted small mt-1'
    ]);
    echo html_writer::end_div();

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col


// --- Porcentaje de satisfacción ---
$valor_satisfaccion = isset($stats['satisfaction_percent'])
    ? number_format($stats['satisfaction_percent'], 2) . '%'
    : '0.00%';

echo html_writer::start_div('col-12 col-sm-6 col-lg-3 mb-3');
echo html_writer::start_div('card shadow-sm h-100');
echo html_writer::start_div('card-body d-flex align-items-center');

    echo html_writer::start_div('mr-3');
    echo html_writer::tag('i', '', ['class' => 'fas fa-smile fa-2x text-info']);
    echo html_writer::end_div();

    echo html_writer::start_div();
    echo html_writer::tag('div', $valor_satisfaccion, [
        'class' => 'h3 mb-0 font-weight-bold text-gray-800'
    ]);
    echo html_writer::tag('div', 'Porcentaje de satisfacción', [
        'class' => 'text-muted small mt-1'
    ]);
    echo html_writer::end_div();

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col


// Puedes agregar una 4ª tarjeta aquí si la necesitas…

echo html_writer::end_div(); // row tarjetas

/** fin de grafico  **/

// Cierre container-fluid dentro de la columna principal
echo html_writer::end_div(); // container-fluid

// Cierre columna derecha
echo html_writer::end_div(); // col-md-9 col-lg-10

// Cierre fila principal
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
