<?php
/**
 * Schedule recipients management page
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\schedule_manager;

// Parameters.
$courseid    = required_param('courseid', PARAM_INT);
$scheduleid  = required_param('scheduleid', PARAM_INT);
$action      = optional_param('action', 'list', PARAM_ALPHA);
$recipientid = optional_param('recipientid', 0, PARAM_INT);

// Load course and context.
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:manageschedules', $context);

// Load schedule.
$schedule = schedule_manager::get_schedule($scheduleid);
if (!$schedule || $schedule->courseid != $courseid) {
    throw new moodle_exception('error:invalidscheduleid', 'local_epicereports');
}

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/schedule_recipients.php', [
    'courseid'   => $courseid,
    'scheduleid' => $scheduleid,
]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('recipients', 'local_epicereports'));
$PAGE->set_heading(format_string($course->fullname));

// Add navbar.
$PAGE->navbar->add(get_string('pluginname', 'local_epicereports'),
    new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]));
$PAGE->navbar->add(get_string('scheduledreports', 'local_epicereports'),
    new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]));
$PAGE->navbar->add(get_string('recipients', 'local_epicereports'));

// Process actions.
switch ($action) {

    // =========================================================================
    // ADD RECIPIENT
    // =========================================================================
    case 'add':
        $email    = required_param('email', PARAM_EMAIL);
        $fullname = optional_param('fullname', '', PARAM_TEXT);
        $userid   = optional_param('userid', 0, PARAM_INT);
        $type     = optional_param('type', 'to', PARAM_ALPHA);

        require_sesskey();

        // If userid provided, get user details.
        if ($userid) {
            $user = $DB->get_record('user', ['id' => $userid]);
            if ($user) {
                $email = $user->email;
                $fullname = fullname($user);
            }
        }

        // Validate email.
        if (!validate_email($email)) {
            redirect(
                new moodle_url('/local/epicereports/schedule_recipients.php', [
                    'courseid'   => $courseid,
                    'scheduleid' => $scheduleid,
                ]),
                get_string('invalidemail', 'local_epicereports'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }

        // Add recipient.
        $result = schedule_manager::add_recipient($scheduleid, $email, $fullname, $userid ?: null, $type);

        if ($result) {
            $message = 'Destinatario agregado correctamente.';
            $msgtype = \core\output\notification::NOTIFY_SUCCESS;
        } else {
            $message = get_string('duplicateemail', 'local_epicereports');
            $msgtype = \core\output\notification::NOTIFY_WARNING;
        }

        redirect(
            new moodle_url('/local/epicereports/schedule_recipients.php', [
                'courseid'   => $courseid,
                'scheduleid' => $scheduleid,
            ]),
            $message,
            null,
            $msgtype
        );
        break;

    // =========================================================================
    // REMOVE RECIPIENT
    // =========================================================================
    case 'remove':
        require_sesskey();

        schedule_manager::remove_recipient($recipientid);

        redirect(
            new moodle_url('/local/epicereports/schedule_recipients.php', [
                'courseid'   => $courseid,
                'scheduleid' => $scheduleid,
            ]),
            get_string('removerecipient', 'local_epicereports'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
        break;

    // =========================================================================
    // TOGGLE RECIPIENT
    // =========================================================================
    case 'toggle':
        require_sesskey();

        schedule_manager::toggle_recipient($recipientid);

        redirect(new moodle_url('/local/epicereports/schedule_recipients.php', [
            'courseid'   => $courseid,
            'scheduleid' => $scheduleid,
        ]));
        break;

    // =========================================================================
    // LIST RECIPIENTS (default)
    // =========================================================================
    case 'list':
    default:
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('recipients', 'local_epicereports') . ': ' . s($schedule->name));

        // Layout with sidebar.
        echo html_writer::start_div('row');

        // Sidebar.
        echo html_writer::start_div('col-md-3 col-lg-2');
        local_epicereports_render_sidebar('schedules', $course);
        echo html_writer::end_div();

        // Main content.
        echo html_writer::start_div('col-md-9 col-lg-10');

        // Back button.
        $backurl = new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $courseid]);
        echo html_writer::div(
            html_writer::link($backurl, '← Volver a programaciones', ['class' => 'btn btn-secondary mb-3']),
            'mb-3'
        );

        // =====================================================================
        // Add Recipient Form
        // =====================================================================
        echo html_writer::start_div('card mb-4');
        echo html_writer::start_div('card-header');
        echo html_writer::tag('h5', get_string('addrecipient', 'local_epicereports'), ['class' => 'mb-0']);
        echo html_writer::end_div();
        echo html_writer::start_div('card-body');

        // Tabs for adding recipients.
        echo html_writer::start_tag('ul', ['class' => 'nav nav-tabs mb-3', 'id' => 'recipientTabs', 'role' => 'tablist']);
        echo html_writer::tag('li',
            html_writer::link('#moodleuser', 'Usuario Moodle', [
                'class' => 'nav-link active',
                'data-toggle' => 'tab',
                'role' => 'tab',
            ]),
            ['class' => 'nav-item']
        );
        echo html_writer::tag('li',
            html_writer::link('#externaluser', 'Correo Externo', [
                'class' => 'nav-link',
                'data-toggle' => 'tab',
                'role' => 'tab',
            ]),
            ['class' => 'nav-item']
        );
        echo html_writer::end_tag('ul');

        echo html_writer::start_div('tab-content');

        // Tab 1: Moodle User.
        echo html_writer::start_div('tab-pane fade show active', ['id' => 'moodleuser', 'role' => 'tabpanel']);

        // Get enrolled users for autocomplete.
        $enrolledusers = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email');
        $useroptions = ['' => '-- Seleccionar usuario --'];
        foreach ($enrolledusers as $user) {
            $useroptions[$user->id] = fullname($user) . ' (' . $user->email . ')';
        }

        $formurl = new moodle_url('/local/epicereports/schedule_recipients.php', [
            'courseid'   => $courseid,
            'scheduleid' => $scheduleid,
            'action'     => 'add',
            'sesskey'    => sesskey(),
        ]);

        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $formurl->out(false),
            'class'  => 'form-inline',
        ]);

        echo html_writer::start_div('form-group mr-2 mb-2');
        echo html_writer::select($useroptions, 'userid', '', '', [
            'class' => 'form-control',
            'id'    => 'userid',
            'required' => 'required',
        ]);
        echo html_writer::end_div();

        echo html_writer::start_div('form-group mr-2 mb-2');
        echo html_writer::select(
            ['to' => 'Para', 'cc' => 'CC', 'bcc' => 'CCO'],
            'type',
            'to',
            '',
            ['class' => 'form-control']
        );
        echo html_writer::end_div();

        // Hidden email field (will be filled via JS or we fetch it server-side).
        echo html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => 'email',
            'id'    => 'moodle_email',
            'value' => 'placeholder@email.com', // Will be ignored if userid is set.
        ]);

        echo html_writer::start_div('form-group mb-2');
        echo html_writer::empty_tag('input', [
            'type'  => 'submit',
            'class' => 'btn btn-primary',
            'value' => get_string('addrecipient', 'local_epicereports'),
        ]);
        echo html_writer::end_div();

        echo html_writer::end_tag('form');
        echo html_writer::end_div(); // tab-pane moodleuser

        // Tab 2: External Email.
        echo html_writer::start_div('tab-pane fade', ['id' => 'externaluser', 'role' => 'tabpanel']);

        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $formurl->out(false),
            'class'  => 'form-inline',
        ]);

        echo html_writer::start_div('form-group mr-2 mb-2');
        echo html_writer::empty_tag('input', [
            'type'        => 'text',
            'name'        => 'fullname',
            'class'       => 'form-control',
            'placeholder' => 'Nombre completo',
        ]);
        echo html_writer::end_div();

        echo html_writer::start_div('form-group mr-2 mb-2');
        echo html_writer::empty_tag('input', [
            'type'        => 'email',
            'name'        => 'email',
            'class'       => 'form-control',
            'placeholder' => 'correo@ejemplo.com',
            'required'    => 'required',
        ]);
        echo html_writer::end_div();

        echo html_writer::start_div('form-group mr-2 mb-2');
        echo html_writer::select(
            ['to' => 'Para', 'cc' => 'CC', 'bcc' => 'CCO'],
            'type',
            'to',
            '',
            ['class' => 'form-control']
        );
        echo html_writer::end_div();

        echo html_writer::start_div('form-group mb-2');
        echo html_writer::empty_tag('input', [
            'type'  => 'submit',
            'class' => 'btn btn-primary',
            'value' => get_string('addrecipient', 'local_epicereports'),
        ]);
        echo html_writer::end_div();

        echo html_writer::end_tag('form');
        echo html_writer::end_div(); // tab-pane externaluser

        echo html_writer::end_div(); // tab-content
        echo html_writer::end_div(); // card-body
        echo html_writer::end_div(); // card

        // =====================================================================
        // Recipients List
        // =====================================================================
        $recipients = schedule_manager::get_recipients($scheduleid);

        if (empty($recipients)) {
            echo $OUTPUT->notification(
                get_string('norecipients', 'local_epicereports'),
                \core\output\notification::NOTIFY_WARNING
            );
        } else {
            $table = new html_table();
            $table->attributes = [
                'class' => 'table table-striped table-bordered table-sm',
            ];
            $table->head = [
                get_string('recipientname', 'local_epicereports'),
                get_string('recipientemail', 'local_epicereports'),
                get_string('recipienttype', 'local_epicereports'),
                get_string('schedulestatus', 'local_epicereports'),
                get_string('actions'),
            ];
            $table->data = [];

            foreach ($recipients as $recipient) {
                // Type badge.
                $typelabels = [
                    'to'  => ['Para', 'badge-primary'],
                    'cc'  => ['CC', 'badge-info'],
                    'bcc' => ['CCO', 'badge-secondary'],
                ];
                $typebadge = html_writer::tag('span',
                    $typelabels[$recipient->recipienttype][0] ?? 'Para',
                    ['class' => 'badge ' . ($typelabels[$recipient->recipienttype][1] ?? 'badge-primary')]
                );

                // Status badge.
                if ($recipient->enabled) {
                    $statusbadge = html_writer::tag('span', 'Activo', ['class' => 'badge badge-success']);
                } else {
                    $statusbadge = html_writer::tag('span', 'Inactivo', ['class' => 'badge badge-secondary']);
                }

                // Actions.
                $actions = [];

                // Toggle.
                $toggleurl = new moodle_url('/local/epicereports/schedule_recipients.php', [
                    'courseid'    => $courseid,
                    'scheduleid'  => $scheduleid,
                    'action'      => 'toggle',
                    'recipientid' => $recipient->id,
                    'sesskey'     => sesskey(),
                ]);
                $toggleicon = $recipient->enabled ? 't/hide' : 't/show';
                $toggletitle = $recipient->enabled ? get_string('disable') : get_string('enable');
                $actions[] = html_writer::link($toggleurl,
                    $OUTPUT->pix_icon($toggleicon, $toggletitle),
                    ['title' => $toggletitle]
                );

                // Remove.
                $removeurl = new moodle_url('/local/epicereports/schedule_recipients.php', [
                    'courseid'    => $courseid,
                    'scheduleid'  => $scheduleid,
                    'action'      => 'remove',
                    'recipientid' => $recipient->id,
                    'sesskey'     => sesskey(),
                ]);
                $actions[] = html_writer::link($removeurl,
                    $OUTPUT->pix_icon('t/delete', get_string('delete')),
                    [
                        'title'   => get_string('delete'),
                        'onclick' => "return confirm('¿Eliminar este destinatario?');",
                    ]
                );

                // User indicator.
                $namehtml = s($recipient->fullname ?: '-');
                if ($recipient->userid) {
                    $namehtml .= ' ' . html_writer::tag('small', '(usuario Moodle)', ['class' => 'text-muted']);
                }

                $table->data[] = [
                    $namehtml,
                    s($recipient->email),
                    $typebadge,
                    $statusbadge,
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
