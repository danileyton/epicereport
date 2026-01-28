<?php
/**
 * Formulario de mensajes de seguimiento
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\followup_manager;

$courseid = required_param('courseid', PARAM_INT);
$followupid = optional_param('id', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/followup_form.php', ['courseid' => $courseid, 'id' => $followupid]));
$PAGE->set_pagelayout('popup');

$isedit = ($followupid > 0);
$pagetitle = $isedit ? get_string('editfollowup', 'local_epicereports') : get_string('newfollowup', 'local_epicereports');

$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($course->fullname));

// Cargar estilos CSS ANTES de $OUTPUT->header().
local_epicereports_include_styles();

// Load existing data if editing.
$followup = null;
if ($isedit) {
    $followup = followup_manager::get_followup($followupid);
    if (!$followup || $followup->courseid != $courseid) {
        throw new moodle_exception('invalidfollowup', 'local_epicereports');
    }
}

/**
 * Followup form class
 */
class followup_form extends moodleform {
    
    protected function definition() {
        global $CFG;
        
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $followup = $this->_customdata['followup'] ?? null;
        
        // Hidden fields.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        
        if ($followup) {
            $mform->addElement('hidden', 'id', $followup->id);
            $mform->setType('id', PARAM_INT);
        }
        
        // ===============================================================
        // GENERAL SETTINGS
        // ===============================================================
        $mform->addElement('header', 'generalhdr', get_string('general', 'local_epicereports'));
        
        // Name.
        $mform->addElement('text', 'name', get_string('followupname', 'local_epicereports'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255, 'client');
        
        // Enabled.
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_epicereports'));
        $mform->setDefault('enabled', 1);
        
        // Target status.
        $targetoptions = [
            'all_incomplete' => get_string('target_all_incomplete', 'local_epicereports'),
            'not_started' => get_string('target_not_started', 'local_epicereports'),
            'in_progress' => get_string('target_in_progress', 'local_epicereports'),
        ];
        $mform->addElement('select', 'target_status', get_string('targetstatus', 'local_epicereports'), $targetoptions);
        $mform->setDefault('target_status', 'all_incomplete');
        $mform->addHelpButton('target_status', 'targetstatus', 'local_epicereports');
        
        // ===============================================================
        // SCHEDULE SETTINGS
        // ===============================================================
        $mform->addElement('header', 'schedulehdr', get_string('schedule', 'local_epicereports'));
        
        // Start date.
        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'local_epicereports'));
        $mform->setDefault('startdate', time());
        $mform->addRule('startdate', null, 'required', null, 'client');
        
        // End date.
        $mform->addElement('date_selector', 'enddate', get_string('enddate', 'local_epicereports'), ['optional' => true]);
        
        // Send time.
        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[sprintf('%02d', $h)] = sprintf('%02d', $h);
        }
        $minutes = ['00' => '00', '15' => '15', '30' => '30', '45' => '45'];
        
        $timegroup = [];
        $timegroup[] = $mform->createElement('select', 'sendtime_hour', '', $hours);
        $timegroup[] = $mform->createElement('static', '', '', ':');
        $timegroup[] = $mform->createElement('select', 'sendtime_minute', '', $minutes);
        $mform->addGroup($timegroup, 'sendtimegroup', get_string('sendtime', 'local_epicereports'), ' ', false);
        $mform->setDefault('sendtime_hour', '09');
        $mform->setDefault('sendtime_minute', '00');
        
        // Days of week.
        $mform->addElement('static', 'dayslabel', get_string('senddays', 'local_epicereports'), '');
        
        $days = [
            'monday' => get_string('monday', 'calendar'),
            'tuesday' => get_string('tuesday', 'calendar'),
            'wednesday' => get_string('wednesday', 'calendar'),
            'thursday' => get_string('thursday', 'calendar'),
            'friday' => get_string('friday', 'calendar'),
            'saturday' => get_string('saturday', 'calendar'),
            'sunday' => get_string('sunday', 'calendar'),
        ];
        
        foreach ($days as $daykey => $dayname) {
            $mform->addElement('advcheckbox', $daykey, '', $dayname);
        }
        
        // Limit per user.
        $limitoptions = [
            'daily' => get_string('limitdaily', 'local_epicereports'),
            'weekly' => get_string('limitweekly', 'local_epicereports'),
        ];
        $mform->addElement('select', 'max_per_user', get_string('maxperuser', 'local_epicereports'), $limitoptions);
        $mform->setDefault('max_per_user', 'daily');
        $mform->addHelpButton('max_per_user', 'maxperuser', 'local_epicereports');
        
        // ===============================================================
        // DELIVERY CHANNELS
        // ===============================================================
        $mform->addElement('header', 'channelshdr', get_string('deliverychannels', 'local_epicereports'));
        
        // Send email.
        $mform->addElement('advcheckbox', 'send_email', get_string('sendemail', 'local_epicereports'));
        $mform->setDefault('send_email', 1);
        
        // Send Moodle message.
        $mform->addElement('advcheckbox', 'send_message', get_string('sendmessage', 'local_epicereports'));
        $mform->setDefault('send_message', 1);
        
        // ===============================================================
        // MESSAGE CONTENT
        // ===============================================================
        $mform->addElement('header', 'messagehdr', get_string('messagecontent', 'local_epicereports'));
        
        // Subject.
        $mform->addElement('text', 'message_subject', get_string('messagesubject', 'local_epicereports'), ['size' => 60]);
        $mform->setType('message_subject', PARAM_TEXT);
        $mform->setDefault('message_subject', 'Recordatorio: Completa tu curso');
        $mform->addRule('message_subject', get_string('required'), 'required', null, 'client');
        
        // Body.
        $mform->addElement('editor', 'message_body_editor', get_string('messagebody', 'local_epicereports'), 
            ['rows' => 10], 
            ['maxfiles' => 0, 'noclean' => true]
        );
        $mform->setType('message_body_editor', PARAM_RAW);
        
        // Placeholders help.
        $placeholders = html_writer::tag('strong', get_string('availableplaceholders', 'local_epicereports')) . '<br>';
        $placeholders .= '<code>{FULLNAME}</code> - ' . get_string('placeholder_fullname', 'local_epicereports') . '<br>';
        $placeholders .= '<code>{FIRSTNAME}</code> - ' . get_string('placeholder_firstname', 'local_epicereports') . '<br>';
        $placeholders .= '<code>{LASTNAME}</code> - ' . get_string('placeholder_lastname', 'local_epicereports') . '<br>';
        $placeholders .= '<code>{EMAIL}</code> - ' . get_string('placeholder_email', 'local_epicereports') . '<br>';
        $placeholders .= '<code>{COURSENAME}</code> - ' . get_string('placeholder_coursename', 'local_epicereports') . '<br>';
        $placeholders .= '<code>{PROGRESS}</code> - ' . get_string('placeholder_progress', 'local_epicereports') . '<br>';
        $placeholders .= '<code>{COURSEURL}</code> - ' . get_string('placeholder_courseurl', 'local_epicereports') . '<br>';
        
        $mform->addElement('static', 'placeholdershelp', '', html_writer::div($placeholders, 'alert alert-info'));
        
        // ===============================================================
        // BUTTONS
        // ===============================================================
        $this->add_action_buttons(true, get_string('savechanges'));
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // At least one day must be selected.
        $daysselected = false;
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            if (!empty($data[$day])) {
                $daysselected = true;
                break;
            }
        }
        
        if (!$daysselected) {
            $errors['monday'] = get_string('error_nodays', 'local_epicereports');
        }
        
        // At least one channel must be selected.
        if (empty($data['send_email']) && empty($data['send_message'])) {
            $errors['send_email'] = get_string('error_nochannels', 'local_epicereports');
        }
        
        // End date must be after start date.
        if (!empty($data['enddate']) && $data['enddate'] < $data['startdate']) {
            $errors['enddate'] = get_string('error_endbeforestart', 'local_epicereports');
        }
        
        return $errors;
    }
}

// Create form.
$mform = new followup_form(null, ['courseid' => $courseid, 'followup' => $followup]);

// Set existing data.
if ($followup) {
    $formdata = clone $followup;
    
    // Corregir message_subject si tiene valor malo.
    if (empty($formdata->message_subject) || strpos($formdata->message_subject, '[[') !== false) {
        $formdata->message_subject = 'Recordatorio: Completa tu curso';
    }
    
    // Parse sendtime.
    $timeparts = explode(':', $followup->sendtime);
    $formdata->sendtime_hour = $timeparts[0] ?? '09';
    $formdata->sendtime_minute = $timeparts[1] ?? '00';
    
    // Editor field.
    $formdata->message_body_editor = [
        'text' => $followup->message_body ?? '',
        'format' => $followup->message_bodyformat ?? FORMAT_HTML,
    ];
    
    $mform->set_data($formdata);
}

// Process form.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]));
    
} else if ($data = $mform->get_data()) {
    // Build record.
    $record = new stdClass();
    $record->courseid = $data->courseid;
    $record->name = $data->name;
    $record->enabled = $data->enabled;
    $record->startdate = $data->startdate;
    $record->enddate = !empty($data->enddate) ? $data->enddate : null;
    $record->sendtime = sprintf('%s:%s', $data->sendtime_hour, $data->sendtime_minute);
    $record->monday = !empty($data->monday) ? 1 : 0;
    $record->tuesday = !empty($data->tuesday) ? 1 : 0;
    $record->wednesday = !empty($data->wednesday) ? 1 : 0;
    $record->thursday = !empty($data->thursday) ? 1 : 0;
    $record->friday = !empty($data->friday) ? 1 : 0;
    $record->saturday = !empty($data->saturday) ? 1 : 0;
    $record->sunday = !empty($data->sunday) ? 1 : 0;
    $record->target_status = $data->target_status;
    $record->send_email = $data->send_email;
    $record->send_message = $data->send_message;
    $record->message_subject = $data->message_subject;
    $record->message_body = $data->message_body_editor['text'];
    $record->message_bodyformat = $data->message_body_editor['format'];
    $record->max_per_user = $data->max_per_user;
    
    if ($isedit) {
        $record->id = $data->id;
        followup_manager::update_followup($record);
        $message = get_string('followupupdated', 'local_epicereports');
    } else {
        followup_manager::create_followup($record);
        $message = get_string('followupcreated', 'local_epicereports');
    }
    
    redirect(
        new moodle_url('/local/epicereports/followup_messages.php', ['courseid' => $courseid]),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Output.
echo $OUTPUT->header();

// Los estilos se cargan automáticamente desde locallib.php cuando se renderiza el sidebar.

// Layout.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('followup', $course);
echo html_writer::end_div();

// Main content.
echo html_writer::start_div('col-md-9 col-lg-10');

echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-edit']) . ' ' . $pagetitle,
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');
$mform->display();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
