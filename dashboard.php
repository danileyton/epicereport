<?php
/**
 * Dashboard principal - Diseño mejorado
 *
 * @package    local_epicereports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\helper;

require_login();

$context = context_system::instance();
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/dashboard.php'));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('pluginname', 'local_epicereports'));
$PAGE->set_heading(get_string('pluginname', 'local_epicereports'));

// CSS personalizado.
$PAGE->requires->css('/local/epicereports/css/styles.css');

// Obtener estadísticas.
$stats = helper::get_dashboard_statistics();

echo $OUTPUT->header();

// Layout principal.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('dashboard');
echo html_writer::end_div();

// Contenido principal.
echo html_writer::start_div('col-md-9 col-lg-10');

// Bienvenida.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-tachometer-alt']) . ' ' . get_string('dashboard', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();
echo html_writer::start_div('epice-card-body');
echo html_writer::tag('p', get_string('dashboardwelcome', 'local_epicereports'), ['style' => 'color: #64748b; margin: 0;']);
echo html_writer::end_div();
echo html_writer::end_div();

// Tarjetas de estadísticas.
echo html_writer::start_div('row');

// Usuarios activos.
echo html_writer::start_div('col-sm-6 col-lg-3 mb-4');
local_epicereports_render_stat_card(
    'fa-users',
    number_format($stats['active_users']),
    get_string('active_users', 'local_epicereports'),
    'primary'
);
echo html_writer::end_div();

// Cursos visibles.
echo html_writer::start_div('col-sm-6 col-lg-3 mb-4');
local_epicereports_render_stat_card(
    'fa-book',
    number_format($stats['visible_courses']),
    get_string('visible_courses', 'local_epicereports'),
    'success'
);
echo html_writer::end_div();

// Cursos ocultos.
echo html_writer::start_div('col-sm-6 col-lg-3 mb-4');
local_epicereports_render_stat_card(
    'fa-eye-slash',
    number_format($stats['hidden_courses']),
    get_string('hidden_courses', 'local_epicereports'),
    'warning'
);
echo html_writer::end_div();

// Satisfacción.
$satisfaction = isset($stats['satisfaction_percent']) ? number_format($stats['satisfaction_percent'], 1) . '%' : '0%';
echo html_writer::start_div('col-sm-6 col-lg-3 mb-4');
local_epicereports_render_stat_card(
    'fa-smile',
    $satisfaction,
    get_string('satisfaction', 'local_epicereports'),
    'success'
);
echo html_writer::end_div();

echo html_writer::end_div(); // row stats

// Acceso rápido.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header-light');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-bolt']) . ' ' . get_string('quickaccess', 'local_epicereports'), 
    ['class' => 'epice-card-title epice-card-title-dark']
);
echo html_writer::end_div();
echo html_writer::start_div('epice-card-body');

echo html_writer::start_div('epice-btn-group');

$courses_url = new moodle_url('/local/epicereports/courses.php');
echo html_writer::link($courses_url, 
    html_writer::tag('i', '', ['class' => 'fa fa-book']) . ' ' . get_string('viewcourses', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-primary']
);

$all_visible_url = new moodle_url('/local/epicereports/courses.php', ['visible' => 1]);
echo html_writer::link($all_visible_url, 
    html_writer::tag('i', '', ['class' => 'fa fa-eye']) . ' ' . get_string('visiblecoursesonly', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-success']
);

$all_hidden_url = new moodle_url('/local/epicereports/courses.php', ['visible' => 0]);
echo html_writer::link($all_hidden_url, 
    html_writer::tag('i', '', ['class' => 'fa fa-eye-slash']) . ' ' . get_string('hiddencoursesonly', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-outline']
);

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row

echo $OUTPUT->footer();