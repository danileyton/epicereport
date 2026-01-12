<?php
/**
 * Schedule form for local_epicereports
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating/editing report schedules.
 */
class schedule_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $customdata = $this->_customdata;

        $courseid = $customdata['courseid'] ?? 0;
        $schedule = $customdata['schedule'] ?? null;

        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $schedule->id ?? 0);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $courseid);

        // =====================================================================
        // General Settings
        // =====================================================================
        $mform->addElement('header', 'general', get_string('general'));

        // Schedule name.
        $mform->addElement('text', 'name', get_string('schedulename', 'local_epicereports'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'schedulename', 'local_epicereports');

        // Enabled.
        $mform->addElement('advcheckbox', 'enabled', get_string('schedulestatus', 'local_epicereports'),
            get_string('scheduleenabled', 'local_epicereports'));
        $mform->setDefault('enabled', 1);

        // =====================================================================
        // Date Range
        // =====================================================================
        $mform->addElement('header', 'daterangeheader', get_string('daterange', 'local_epicereports'));

        // Start date.
        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'local_epicereports'));
        $mform->addRule('startdate', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('startdate', 'startdate', 'local_epicereports');
        $mform->setDefault('startdate', time());

        // End date (optional).
        $mform->addElement('date_selector', 'enddate', get_string('enddate', 'local_epicereports'), ['optional' => true]);
        $mform->addHelpButton('enddate', 'enddate', 'local_epicereports');

        // =====================================================================
        // Schedule Time
        // =====================================================================
        $mform->addElement('header', 'scheduleheader', get_string('sendtime', 'local_epicereports'));

        // Send time (hour:minute selector).
        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[sprintf('%02d', $i)] = sprintf('%02d', $i);
        }
        $minutes = [];
        for ($i = 0; $i < 60; $i += 5) {
            $minutes[sprintf('%02d', $i)] = sprintf('%02d', $i);
        }

        $timegroup = [];
        $timegroup[] = $mform->createElement('select', 'sendtime_hour', '', $hours);
        $timegroup[] = $mform->createElement('static', 'timesep', '', ':');
        $timegroup[] = $mform->createElement('select', 'sendtime_minute', '', $minutes);

        $mform->addGroup($timegroup, 'sendtimegroup', get_string('sendtime', 'local_epicereports'), ' ', false);
        $mform->addHelpButton('sendtimegroup', 'sendtime', 'local_epicereports');

        // Set defaults for time.
        $defaulthour = '08';
        $defaultminute = '00';
        if ($schedule && !empty($schedule->sendtime)) {
            $parts = explode(':', $schedule->sendtime);
            $defaulthour = $parts[0] ?? '08';
            $defaultminute = $parts[1] ?? '00';
        }
        $mform->setDefault('sendtime_hour', $defaulthour);
        $mform->setDefault('sendtime_minute', $defaultminute);

        // =====================================================================
        // Days of the week
        // =====================================================================
        $mform->addElement('header', 'daysheader', get_string('senddays', 'local_epicereports'));

        $days = [
            'monday'    => get_string('monday', 'local_epicereports'),
            'tuesday'   => get_string('tuesday', 'local_epicereports'),
            'wednesday' => get_string('wednesday', 'local_epicereports'),
            'thursday'  => get_string('thursday', 'local_epicereports'),
            'friday'    => get_string('friday', 'local_epicereports'),
            'saturday'  => get_string('saturday', 'local_epicereports'),
            'sunday'    => get_string('sunday', 'local_epicereports'),
        ];

        $daysgroup = [];
        foreach ($days as $daykey => $daylabel) {
            $daysgroup[] = $mform->createElement('advcheckbox', $daykey, '', $daylabel);
        }

        $mform->addGroup($daysgroup, 'daysgroup', get_string('senddays', 'local_epicereports'), '<br>', false);
        $mform->addHelpButton('daysgroup', 'senddays', 'local_epicereports');

        // =====================================================================
        // Report Content
        // =====================================================================
        $mform->addElement('header', 'contentheader', get_string('reportcontent', 'local_epicereports'));

        $mform->addElement('advcheckbox', 'include_course_report', '',
            get_string('includecourseexcel', 'local_epicereports'));
        $mform->setDefault('include_course_report', 1);
        $mform->addHelpButton('include_course_report', 'includecourseexcel', 'local_epicereports');

        $mform->addElement('advcheckbox', 'include_feedback_report', '',
            get_string('includefeedbackexcel', 'local_epicereports'));
        $mform->setDefault('include_feedback_report', 0);
        $mform->addHelpButton('include_feedback_report', 'includefeedbackexcel', 'local_epicereports');

        // =====================================================================
        // Email Settings
        // =====================================================================
        $mform->addElement('header', 'emailheader', get_string('emailsettings', 'local_epicereports'));

        // Email subject.
        $mform->addElement('text', 'email_subject', get_string('emailsubject', 'local_epicereports'), ['size' => 60]);
        $mform->setType('email_subject', PARAM_TEXT);
        $mform->addHelpButton('email_subject', 'emailsubject', 'local_epicereports');
        $mform->setDefault('email_subject', get_string('emailsubjectdefault', 'local_epicereports'));

        // Email body.
        $mform->addElement('textarea', 'email_body', get_string('emailbody', 'local_epicereports'),
            ['rows' => 8, 'cols' => 60]);
        $mform->setType('email_body', PARAM_RAW);
        $mform->addHelpButton('email_body', 'emailbody', 'local_epicereports');
        $mform->setDefault('email_body', get_string('emailbodydefault', 'local_epicereports'));

        // Placeholders info.
        $mform->addElement('static', 'placeholdersinfo', '',
            '<div class="alert alert-info">' .
            '<strong>Marcadores disponibles:</strong><br>' .
            '<code>{coursename}</code> - Nombre del curso<br>' .
            '<code>{date}</code> - Fecha del reporte<br>' .
            '<code>{recipientname}</code> - Nombre del destinatario' .
            '</div>'
        );

        // =====================================================================
        // Recipients Section
        // =====================================================================
        $mform->addElement('header', 'recipientsheader', get_string('recipients', 'local_epicereports'));

        // Instructions.
        $mform->addElement('static', 'recipientsinstructions', '',
            '<div class="alert alert-secondary">' .
            'Los destinatarios se configuran después de guardar la programación.' .
            '</div>'
        );

        // If editing, show current recipients count.
        if ($schedule && $schedule->id) {
            $recipientcount = \local_epicereports\schedule_manager::get_recipients($schedule->id);
            $count = count($recipientcount);
            $mform->addElement('static', 'currentrecipients', get_string('recipients', 'local_epicereports'),
                '<span class="badge badge-info">' . $count . ' destinatario(s) configurado(s)</span>');
        }

        // =====================================================================
        // Action Buttons
        // =====================================================================
        $this->add_action_buttons(true, $schedule ? get_string('savechanges') : get_string('create'));
    }

    /**
     * Form validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate that at least one day is selected.
        $daysselected = false;
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            if (!empty($data[$day])) {
                $daysselected = true;
                break;
            }
        }
        if (!$daysselected) {
            $errors['daysgroup'] = get_string('error:nodaysselected', 'local_epicereports');
        }

        // Validate end date is after start date.
        if (!empty($data['enddate']) && $data['enddate'] < $data['startdate']) {
            $errors['enddate'] = get_string('error:enddatebeforestart', 'local_epicereports');
        }

        // Validate at least one report type is selected.
        if (empty($data['include_course_report']) && empty($data['include_feedback_report'])) {
            $errors['include_course_report'] = get_string('error:noreportselected', 'local_epicereports');
        }

        return $errors;
    }

    /**
     * Get submitted data with processed fields.
     *
     * @return object|null
     */
    public function get_data() {
        $data = parent::get_data();

        if ($data) {
            // Combine time fields.
            $hour = $data->sendtime_hour ?? '08';
            $minute = $data->sendtime_minute ?? '00';
            $data->sendtime = sprintf('%02d:%02d', $hour, $minute);

            // Clean up time fields.
            unset($data->sendtime_hour);
            unset($data->sendtime_minute);
        }

        return $data;
    }

    /**
     * Set form data from schedule object.
     *
     * @param object $schedule
     */
    public function set_data($schedule) {
        if ($schedule) {
            // Parse sendtime for the form.
            if (!empty($schedule->sendtime)) {
                $parts = explode(':', $schedule->sendtime);
                $schedule->sendtime_hour = $parts[0] ?? '08';
                $schedule->sendtime_minute = $parts[1] ?? '00';
            }
        }

        parent::set_data($schedule);
    }
}
