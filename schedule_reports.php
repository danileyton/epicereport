<?php
/**
 * Schedule reports management page
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\schedule_manager;
use local_epicereports\form\schedule_form;

// Parameters.
$courseid   = required_param('courseid', PARAM_INT);
$action     = optional_param('action', 'list', PARAM_ALPHA);
$scheduleid = optional_param('scheduleid', 0, PARAM_INT);
$confirm    = optional_param('confirm', 0, PARAM_BOOL);

// Load course and context.
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:manageschedules', $context);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('scheduledreports', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

// Add navbar.
$PAGE->navbar->add(get_string('pluginname', 'local_epicereports'),
    new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]));
$PAGE->navbar->add(get_string('scheduledreports', 'local_epicereports'));

// Process actions.
switch ($action) {

    // =========================================================================
    // CREATE/EDIT SCHEDULE
    // =========================================================================
    case 'edit':
        $schedule = null;
        if ($scheduleid) {
            $schedule = schedule_manager::get_schedule($scheduleid);
            if (!$schedule || $schedule->courseid != $courseid) {
                throw new moodle_exception('error:invalidscheduleid', 'local_epicereports');
            }
            $PAGE->navbar->add(get_string('editschedule', 'local_epicereports'));
        } else {
            $PAGE->navbar->add(get_string('newschedule', 'local_epicereports'));
        }

        $form = new schedule_form(null, [
            'courseid' => $courseid,
            'schedule' => $schedule,
        ]);

        if ($schedule) {
            $form->set_data($schedule);
        }

        if ($form->is_cancelled()) {
            redirect(new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]));

        } else if ($data = $form->get_data()) {
            // Save schedule.
            if ($data->id) {
                schedule_manager::update_schedule($data);
                $message = get_string('scheduleupdated', 'local_epicereports');
                $scheduleidredirect = $data->id;
            } else {
                $scheduleidredirect = schedule_manager::create_schedule($data);
                $message = get_string('schedulecreated', 'local_epicereports');
            }

            // Redirect to recipients page.
            redirect(
                new moodle_url('/local/epicereports/schedule_recipients.php', [
                    'courseid'   => $courseid,
                    'scheduleid' => $scheduleidredirect,
                ]),
                $message,
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );

        } else {
            // Display form.
            echo $OUTPUT->header();
            echo $OUTPUT->heading($schedule
                ? get_string('editschedule', 'local_epicereports')
                : get_string('newschedule', 'local_epicereports')
            );

            $form->display();

            echo $OUTPUT->footer();
        }
        exit;

    // =========================================================================
    // DELETE SCHEDULE
    // =========================================================================
    case 'delete':
        $schedule = schedule_manager::get_schedule($scheduleid);
        if (!$schedule || $schedule->courseid != $courseid) {
            throw new moodle_exception('error:invalidscheduleid', 'local_epicereports');
        }

        if ($confirm && confirm_sesskey()) {
            schedule_manager::delete_schedule($scheduleid);
            redirect(
                new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]),
                get_string('scheduledeleted', 'local_epicereports'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        // Confirm deletion.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deleteschedule', 'local_epicereports'));

        $confirmurl = new moodle_url('/local/epicereports/schedule_reports.php', [
            'courseid'   => $courseid,
            'action'     => 'delete',
            'scheduleid' => $scheduleid,
            'confirm'    => 1,
            'sesskey'    => sesskey(),
        ]);
        $cancelurl = new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]);

        echo $OUTPUT->confirm(
            get_string('deletescheduleconfirm', 'local_epicereports') . '<br><br><strong>' . s($schedule->name) . '</strong>',
            $confirmurl,
            $cancelurl
        );

        echo $OUTPUT->footer();
        exit;

    // =========================================================================
    // TOGGLE ENABLED STATUS
    // =========================================================================
    case 'toggle':
        require_sesskey();

        $schedule = schedule_manager::get_schedule($scheduleid);
        if (!$schedule || $schedule->courseid != $courseid) {
            throw new moodle_exception('error:invalidscheduleid', 'local_epicereports');
        }

        schedule_manager::toggle_schedule($scheduleid);

        redirect(new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]));
        exit;

    // =========================================================================
    // RUN NOW (manual execution)
    // =========================================================================
    case 'runnow':
        $schedule = schedule_manager::get_schedule($scheduleid);
        if (!$schedule || $schedule->courseid != $courseid) {
            throw new moodle_exception('error:invalidscheduleid', 'local_epicereports');
        }

        if ($confirm && confirm_sesskey()) {
            // TODO: Implement in Etapa 5 - For now, just show message.
            redirect(
                new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]),
                'Funcionalidad de ejecución inmediata se implementará en la siguiente etapa.',
                null,
                \core\output\notification::NOTIFY_INFO
            );
        }

        // Confirm run now.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('runnow', 'local_epicereports'));

        $confirmurl = new moodle_url('/local/epicereports/schedule_reports.php', [
            'courseid'   => $courseid,
            'action'     => 'runnow',
            'scheduleid' => $scheduleid,
            'confirm'    => 1,
            'sesskey'    => sesskey(),
        ]);
        $cancelurl = new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]);

        echo $OUTPUT->confirm(
            get_string('runnowconfirm', 'local_epicereports') . '<br><br><strong>' . s($schedule->name) . '</strong>',
            $confirmurl,
            $cancelurl
        );

        echo $OUTPUT->footer();
        exit;

    // =========================================================================
    // LIST SCHEDULES (default)
    // =========================================================================
    case 'list':
    default:
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('scheduledreports', 'local_epicereports'));

        // Layout with sidebar.
        echo html_writer::start_div('row');

        // Sidebar.
        echo html_writer::start_div('col-md-3 col-lg-2');
        local_epicereports_render_sidebar('schedules', $course);
        echo html_writer::end_div();

        // Main content.
        echo html_writer::start_div('col-md-9 col-lg-10');

        // Add new schedule button.
        $addurl = new moodle_url('/local/epicereports/schedule_reports.php', [
            'courseid' => $courseid,
            'action'   => 'edit',
        ]);
        echo html_writer::div(
            html_writer::link($addurl, get_string('newschedule', 'local_epicereports'), [
                'class' => 'btn btn-primary mb-3'
            ]),
            'mb-3'
        );

        // Get schedules for this course.
        $schedules = schedule_manager::get_schedules_by_course($courseid);

        if (empty($schedules)) {
            echo $OUTPUT->notification(
                'No hay programaciones configuradas para este curso.',
                \core\output\notification::NOTIFY_INFO
            );
        } else {
            // Build table.
            $table = new html_table();
            $table->attributes = [
                'class' => 'table table-striped table-bordered table-sm',
            ];
            $table->head = [
                get_string('schedulename', 'local_epicereports'),
                get_string('schedulestatus', 'local_epicereports'),
                get_string('senddays', 'local_epicereports'),
                get_string('sendtime', 'local_epicereports'),
                get_string('recipients', 'local_epicereports'),
                get_string('lastrun', 'local_epicereports'),
                get_string('nextrun', 'local_epicereports'),
                get_string('actions'),
            ];
            $table->data = [];

            foreach ($schedules as $schedule) {
                // Status badge.
                if ($schedule->enabled) {
                    $statusbadge = html_writer::tag('span', get_string('scheduleenabled', 'local_epicereports'), [
                        'class' => 'badge badge-success'
                    ]);
                } else {
                    $statusbadge = html_writer::tag('span', get_string('scheduledisabled', 'local_epicereports'), [
                        'class' => 'badge badge-secondary'
                    ]);
                }

                // Days.
                $daysarr = [];
                if ($schedule->monday) $daysarr[] = 'Lun';
                if ($schedule->tuesday) $daysarr[] = 'Mar';
                if ($schedule->wednesday) $daysarr[] = 'Mié';
                if ($schedule->thursday) $daysarr[] = 'Jue';
                if ($schedule->friday) $daysarr[] = 'Vie';
                if ($schedule->saturday) $daysarr[] = 'Sáb';
                if ($schedule->sunday) $daysarr[] = 'Dom';
                $daysstr = implode(', ', $daysarr);

                // Recipients count.
                $recipients = schedule_manager::get_recipients($schedule->id);
                $recipientcount = count($recipients);
                $recipientbadge = html_writer::tag('span', $recipientcount, [
                    'class' => $recipientcount > 0 ? 'badge badge-info' : 'badge badge-warning'
                ]);

                // Last run.
                $lastrun = $schedule->lastrun
                    ? userdate($schedule->lastrun, '%d/%m/%Y %H:%M')
                    : get_string('neverrun', 'local_epicereports');

                // Next run.
                $nextrun = $schedule->nextrun
                    ? userdate($schedule->nextrun, '%d/%m/%Y %H:%M')
                    : '-';

                // Actions.
                $actions = [];

                // Edit.
                $editurl = new moodle_url('/local/epicereports/schedule_reports.php', [
                    'courseid'   => $courseid,
                    'action'     => 'edit',
                    'scheduleid' => $schedule->id,
                ]);
                $actions[] = html_writer::link($editurl,
                    $OUTPUT->pix_icon('t/edit', get_string('edit')),
                    ['title' => get_string('edit')]
                );

                // Recipients.
                $recipientsurl = new moodle_url('/local/epicereports/schedule_recipients.php', [
                    'courseid'   => $courseid,
                    'scheduleid' => $schedule->id,
                ]);
                $actions[] = html_writer::link($recipientsurl,
                    $OUTPUT->pix_icon('t/email', get_string('recipients', 'local_epicereports')),
                    ['title' => get_string('recipients', 'local_epicereports')]
                );

                // Toggle.
                $toggleurl = new moodle_url('/local/epicereports/schedule_reports.php', [
                    'courseid'   => $courseid,
                    'action'     => 'toggle',
                    'scheduleid' => $schedule->id,
                    'sesskey'    => sesskey(),
                ]);
                $toggleicon = $schedule->enabled ? 't/hide' : 't/show';
                $toggletitle = $schedule->enabled
                    ? get_string('disable')
                    : get_string('enable');
                $actions[] = html_writer::link($toggleurl,
                    $OUTPUT->pix_icon($toggleicon, $toggletitle),
                    ['title' => $toggletitle]
                );

                // Run now.
                $runnowurl = new moodle_url('/local/epicereports/schedule_reports.php', [
                    'courseid'   => $courseid,
                    'action'     => 'runnow',
                    'scheduleid' => $schedule->id,
                ]);
                $actions[] = html_writer::link($runnowurl,
                    $OUTPUT->pix_icon('t/go', get_string('runnow', 'local_epicereports')),
                    ['title' => get_string('runnow', 'local_epicereports')]
                );

                // Logs.
                $logsurl = new moodle_url('/local/epicereports/schedule_logs.php', [
                    'courseid'   => $courseid,
                    'scheduleid' => $schedule->id,
                ]);
                $actions[] = html_writer::link($logsurl,
                    $OUTPUT->pix_icon('t/log', get_string('viewlogs', 'local_epicereports')),
                    ['title' => get_string('viewlogs', 'local_epicereports')]
                );

                // Delete.
                $deleteurl = new moodle_url('/local/epicereports/schedule_reports.php', [
                    'courseid'   => $courseid,
                    'action'     => 'delete',
                    'scheduleid' => $schedule->id,
                ]);
                $actions[] = html_writer::link($deleteurl,
                    $OUTPUT->pix_icon('t/delete', get_string('delete')),
                    ['title' => get_string('delete')]
                );

                $table->data[] = [
                    s($schedule->name),
                    $statusbadge,
                    $daysstr,
                    $schedule->sendtime,
                    $recipientbadge,
                    $lastrun,
                    $nextrun,
                    implode(' ', $actions),
                ];
            }

            echo html_writer::table($table);
        }

        // Close layout.
        echo html_writer::end_div(); // col-md-9
        echo html_writer::end_div(); // row

        echo $OUTPUT->footer();
        break;
}
