<?php
/**
 * Formulario de programación de reportes - Diseño mejorado
 *
 * @package    local_epicereports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\schedule_manager;
use local_epicereports\form\schedule_form;

$courseid = required_param('courseid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/schedule_form.php', ['courseid' => $courseid, 'id' => $id]));
$PAGE->set_pagelayout('popup');

$schedule = null;
if ($id) {
    $schedule = schedule_manager::get_schedule($id);
    if (!$schedule || $schedule->courseid != $courseid) {
        throw new moodle_exception('error:invalidscheduleid', 'local_epicereports');
    }
    $PAGE->set_title(get_string('editschedule', 'local_epicereports'));
} else {
    $PAGE->set_title(get_string('newschedule', 'local_epicereports'));
}
$PAGE->set_heading(format_string($course->fullname));

$customdata = ['courseid' => $courseid, 'schedule' => $schedule];
$form = new schedule_form(null, $customdata);

// CORRECCIÓN: Cargar los datos existentes en el formulario
if ($schedule) {
    $form->set_data($schedule);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]));
}

if ($data = $form->get_data()) {
    if ($id) {
        $data->id = $id;
        schedule_manager::update_schedule($data);
        $message = get_string('scheduleupdated', 'local_epicereports');
    } else {
        $data->courseid = $courseid;
        schedule_manager::create_schedule($data);
        $message = get_string('schedulecreated', 'local_epicereports');
    }
    redirect(new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]),
        $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

echo '<style>
:root {
    --epice-primary: #1e3a5f; --epice-accent: #0ea5e9; --epice-success: #10b981; --epice-success-bg: rgba(16, 185, 129, 0.1);
    --epice-bg-card: #ffffff; --epice-bg-sidebar: linear-gradient(180deg, #1e3a5f 0%, #0f2744 100%);
    --epice-bg-header: linear-gradient(135deg, #1e3a5f 0%, #2d5a8a 100%); --epice-bg-table-header: #f1f5f9;
    --epice-text-primary: #1e293b; --epice-text-secondary: #64748b; --epice-text-inverse: #ffffff;
    --epice-border: #e2e8f0; --epice-border-light: #f1f5f9; --epice-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --epice-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1); --epice-radius: 8px; --epice-radius-md: 12px;
    --epice-radius-lg: 16px; --epice-transition: all 0.2s ease-in-out;
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
.epice-card-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
.epice-card-body { padding: 24px; }
.epice-course-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(14, 165, 233, 0.1); color: #0369a1; padding: 8px 16px; border-radius: 8px; font-size: 0.9rem; font-weight: 500; margin-bottom: 20px; }
.epice-course-badge i { color: #0ea5e9; }
@media (max-width: 768px) { .epice-sidebar { margin-bottom: 24px; } }
</style>';

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('schedules', $course);
echo html_writer::end_div();

echo html_writer::start_div('col-md-9 col-lg-10');

// Mostrar nombre del curso.
echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-graduation-cap']) . ' ' . format_string($course->fullname),
    'epice-course-badge'
);

$title = $id ? get_string('editschedule', 'local_epicereports') : get_string('newschedule', 'local_epicereports');
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', html_writer::tag('i', '', ['class' => 'fa fa-calendar-plus']) . ' ' . $title, ['class' => 'epice-card-title']);
echo html_writer::end_div();
echo html_writer::start_div('epice-card-body');
$form->display();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
