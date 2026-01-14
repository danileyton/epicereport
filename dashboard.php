<?php
/**
 * Dashboard principal - Diseño mejorado con CSS embebido
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

// Obtener estadísticas.
$stats = helper::get_dashboard_statistics();

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
    --epice-text-primary: #1e293b;
    --epice-text-secondary: #64748b;
    --epice-text-inverse: #ffffff;
    --epice-border: #e2e8f0;
    --epice-border-light: #f1f5f9;
    --epice-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --epice-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --epice-radius: 8px;
    --epice-radius-md: 12px;
    --epice-radius-lg: 16px;
    --epice-transition: all 0.2s ease-in-out;
}

.epice-sidebar {
    background: var(--epice-bg-sidebar);
    border-radius: var(--epice-radius-lg);
    padding: 16px;
    box-shadow: var(--epice-shadow-md);
}

.epice-sidebar-header {
    padding: 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.epice-sidebar-logo { font-size: 1.5rem; margin-bottom: 4px; }

.epice-sidebar-title {
    color: var(--epice-text-inverse);
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
}

.epice-sidebar-subtitle {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.7rem;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.epice-nav { list-style: none; padding: 0; margin: 0; }
.epice-nav-item { margin-bottom: 4px; }

.epice-nav-link {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    color: rgba(255, 255, 255, 0.8) !important;
    text-decoration: none !important;
    border-radius: var(--epice-radius);
    transition: var(--epice-transition);
    font-size: 0.9rem;
    font-weight: 500;
}

.epice-nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: var(--epice-text-inverse) !important;
}

.epice-nav-link.active {
    background-color: var(--epice-accent);
    color: var(--epice-text-inverse) !important;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
}

.epice-nav-icon { margin-right: 10px; width: 20px; text-align: center; }

.epice-card {
    background: var(--epice-bg-card);
    border-radius: var(--epice-radius-md);
    box-shadow: var(--epice-shadow);
    border: 1px solid var(--epice-border-light);
    margin-bottom: 24px;
    overflow: hidden;
}

.epice-card-header {
    background: var(--epice-bg-header);
    padding: 16px 24px;
}

.epice-card-header-light {
    background: var(--epice-bg-table-header);
    padding: 16px 24px;
    border-bottom: 1px solid var(--epice-border);
}

.epice-card-title {
    color: var(--epice-text-inverse);
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.epice-card-title-dark { color: var(--epice-text-primary); }
.epice-card-body { padding: 24px; }

.epice-stat-card {
    background: var(--epice-bg-card);
    border-radius: var(--epice-radius-md);
    padding: 24px;
    box-shadow: var(--epice-shadow);
    border: 1px solid var(--epice-border-light);
    transition: var(--epice-transition);
    position: relative;
    overflow: hidden;
}

.epice-stat-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}

.epice-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--epice-shadow-md);
}

.epice-stat-card.stat-primary::before { background: var(--epice-info); }
.epice-stat-card.stat-success::before { background: var(--epice-success); }
.epice-stat-card.stat-warning::before { background: var(--epice-warning); }
.epice-stat-card.stat-danger::before { background: var(--epice-danger); }

.epice-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--epice-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: 16px;
}

.epice-stat-icon.icon-primary { background: var(--epice-info-bg); color: var(--epice-info); }
.epice-stat-icon.icon-success { background: var(--epice-success-bg); color: var(--epice-success); }
.epice-stat-icon.icon-warning { background: var(--epice-warning-bg); color: #b45309; }
.epice-stat-icon.icon-danger { background: var(--epice-danger-bg); color: var(--epice-danger); }

.epice-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--epice-text-primary);
    line-height: 1;
    margin-bottom: 4px;
}

.epice-stat-label {
    font-size: 0.875rem;
    color: var(--epice-text-secondary);
    font-weight: 500;
}

.epice-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: var(--epice-radius);
    border: none;
    cursor: pointer;
    transition: var(--epice-transition);
    text-decoration: none !important;
}

.epice-btn:hover { transform: translateY(-1px); }

.epice-btn-primary {
    background: var(--epice-primary);
    color: var(--epice-text-inverse) !important;
}

.epice-btn-primary:hover {
    background: var(--epice-primary-light);
    box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
}

.epice-btn-success {
    background: var(--epice-success);
    color: var(--epice-text-inverse) !important;
}

.epice-btn-success:hover {
    background: #34d399;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.epice-btn-outline {
    background: transparent;
    color: var(--epice-text-secondary) !important;
    border: 1px solid var(--epice-border);
}

.epice-btn-outline:hover {
    background: var(--epice-bg-table-header);
    color: var(--epice-text-primary) !important;
}

.epice-btn-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .epice-sidebar { margin-bottom: 24px; }
    .epice-stat-value { font-size: 1.5rem; }
    .epice-btn-group { flex-direction: column; }
    .epice-btn { width: 100%; }
}
</style>';

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
local_epicereports_render_stat_card('fa-users', number_format($stats['active_users']), get_string('active_users', 'local_epicereports'), 'primary');
echo html_writer::end_div();

// Cursos visibles.
echo html_writer::start_div('col-sm-6 col-lg-3 mb-4');
local_epicereports_render_stat_card('fa-book', number_format($stats['visible_courses']), get_string('visible_courses', 'local_epicereports'), 'success');
echo html_writer::end_div();

// Cursos ocultos.
echo html_writer::start_div('col-sm-6 col-lg-3 mb-4');
local_epicereports_render_stat_card('fa-eye-slash', number_format($stats['hidden_courses']), get_string('hidden_courses', 'local_epicereports'), 'warning');
echo html_writer::end_div();

// Satisfacción.
$satisfaction = isset($stats['satisfaction_percent']) ? number_format($stats['satisfaction_percent'], 1) . '%' : '0%';
echo html_writer::start_div('col-sm-6 col-lg-3 mb-4');
local_epicereports_render_stat_card('fa-smile', $satisfaction, get_string('satisfaction', 'local_epicereports'), 'success');
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
