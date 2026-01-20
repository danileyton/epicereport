<?php
/**
 * Mensajes de seguimiento - Fase 1 Placeholder
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:manageschedules', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('followupmessages', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

// CSS embebido.
echo '<style>
:root {
    --epice-primary: #1e3a5f;
    --epice-accent: #0ea5e9;
    --epice-success: #10b981;
    --epice-bg-card: #ffffff;
    --epice-bg-sidebar: linear-gradient(180deg, #1e3a5f 0%, #0f2744 100%);
    --epice-bg-header: linear-gradient(135deg, #1e3a5f 0%, #2d5a8a 100%);
    --epice-text-primary: #1e293b;
    --epice-text-secondary: #64748b;
    --epice-text-inverse: #ffffff;
    --epice-border-light: #f1f5f9;
    --epice-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --epice-radius: 8px;
    --epice-radius-md: 12px;
}

.epice-sidebar { background: var(--epice-bg-sidebar); border-radius: var(--epice-radius-md); padding: 16px; box-shadow: var(--epice-shadow); }
.epice-sidebar-header { padding: 16px; margin-bottom: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); text-align: center; }
.epice-sidebar-logo { font-size: 1.5rem; margin-bottom: 4px; }
.epice-sidebar-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 700; margin: 0; }
.epice-sidebar-subtitle { color: rgba(255, 255, 255, 0.6); font-size: 0.7rem; margin-top: 4px; text-transform: uppercase; }
.epice-nav { list-style: none; padding: 0; margin: 0; }
.epice-nav-item { margin-bottom: 4px; }
.epice-nav-link { display: flex; align-items: center; padding: 10px 16px; color: rgba(255, 255, 255, 0.8) !important; text-decoration: none !important; border-radius: var(--epice-radius); font-size: 0.9rem; }
.epice-nav-link:hover { background-color: rgba(255, 255, 255, 0.1); color: var(--epice-text-inverse) !important; }
.epice-nav-link.active { background-color: var(--epice-accent); color: var(--epice-text-inverse) !important; }
.epice-nav-icon { margin-right: 10px; width: 20px; text-align: center; }
.epice-nav-separator { padding: 16px 16px 8px 16px; }
.epice-nav-separator-text { color: rgba(255, 255, 255, 0.4); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; }
.epice-card { background: var(--epice-bg-card); border-radius: var(--epice-radius-md); box-shadow: var(--epice-shadow); border: 1px solid var(--epice-border-light); margin-bottom: 24px; }
.epice-card-header { background: var(--epice-bg-header); padding: 16px 24px; border-radius: var(--epice-radius-md) var(--epice-radius-md) 0 0; }
.epice-card-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
.epice-card-body { padding: 24px; }
.epice-placeholder { text-align: center; padding: 40px; color: var(--epice-text-secondary); }
.epice-placeholder i { font-size: 3rem; margin-bottom: 16px; opacity: 0.5; }
.epice-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-size: 0.875rem; font-weight: 600; border-radius: var(--epice-radius); border: none; cursor: pointer; text-decoration: none !important; }
.epice-btn-success { background: var(--epice-success); color: var(--epice-text-inverse) !important; }
</style>';

// Layout principal.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('followup', $course);
echo html_writer::end_div();

// Contenido principal.
echo html_writer::start_div('col-md-9 col-lg-10');

echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-paper-plane']) . ' ' . get_string('followupmessages', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');
echo html_writer::tag('p', get_string('followupmessagesdesc', 'local_epicereports'), ['style' => 'margin-bottom: 24px; color: #64748b;']);

echo html_writer::start_div('epice-placeholder');
echo html_writer::tag('i', '', ['class' => 'fa fa-cogs']);
echo html_writer::tag('h4', 'Funcionalidad en desarrollo');
echo html_writer::tag('p', 'Los mensajes de seguimiento estarán disponibles en la Fase 5-7 del desarrollo.');
echo html_writer::tag('p', 'Esta sección permitirá programar mensajes automáticos para alumnos que no han completado el curso.');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
