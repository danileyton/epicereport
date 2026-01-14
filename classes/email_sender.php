<?php
/**
 * Email Sender class for local_epicereports
 *
 * Handles sending emails with report attachments
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports;

defined('MOODLE_INTERNAL') || die();

/**
 * Class email_sender
 *
 * Sends scheduled report emails with Excel attachments
 */
class email_sender {

    /** @var int Maximum retry attempts for failed emails */
    const MAX_RETRIES = 3;

    /** @var array Supported placeholder tokens */
    const PLACEHOLDERS = [
        '{coursename}',
        '{courseshortname}',
        '{date}',
        '{datetime}',
        '{recipientname}',
        '{recipientemail}',
    ];

    /**
     * Send report email to a single recipient.
     *
     * @param object $schedule The schedule object.
     * @param object $recipient The recipient object.
     * @param array $attachments Array of attachment files [['filepath' => string, 'filename' => string], ...].
     * @param object|null $course Optional course object (will be loaded if not provided).
     * @return array ['success' => bool, 'error' => string, 'errorcode' => string]
     */
    public static function send_report_email(
        object $schedule,
        object $recipient,
        array $attachments,
        ?object $course = null
    ): array {
        global $DB, $CFG, $SITE;

        try {
            // Load course if not provided.
            if (!$course) {
                $course = get_course($schedule->courseid);
            }

            // Get or create recipient user object.
            $touser = self::get_recipient_user($recipient);

            // Get the "from" user (noreply or support).
            $fromuser = self::get_from_user();

            // Prepare subject and body with placeholders replaced.
            $subject = self::replace_placeholders(
                $schedule->email_subject ?: get_string('emailsubjectdefault', 'local_epicereports'),
                $course,
                $touser
            );

            $body = self::replace_placeholders(
                $schedule->email_body ?: get_string('emailbodydefault', 'local_epicereports'),
                $course,
                $touser
            );

            // Convert body to HTML.
            $bodyhtml = self::text_to_html($body);

            // Prepare attachments for email_to_user().
            $attachmentdata = self::prepare_attachments($attachments);

            // Send the email.
            $result = self::do_send_email(
                $touser,
                $fromuser,
                $subject,
                $body,
                $bodyhtml,
                $attachmentdata['files'],
                $attachmentdata['filenames']
            );

            if ($result) {
                return [
                    'success'   => true,
                    'error'     => '',
                    'errorcode' => '',
                ];
            } else {
                return [
                    'success'   => false,
                    'error'     => 'La función email_to_user() retornó false',
                    'errorcode' => 'EMAIL_SEND_FAILED',
                ];
            }

        } catch (\Exception $e) {
            return [
                'success'   => false,
                'error'     => $e->getMessage(),
                'errorcode' => 'EXCEPTION',
            ];
        }
    }

    /**
     * Send reports to all recipients of a schedule.
     *
     * @param object $schedule The schedule object.
     * @param array $attachments Array of attachment files.
     * @return array ['total' => int, 'sent' => int, 'failed' => int, 'results' => array]
     */
    public static function send_to_all_recipients(object $schedule, array $attachments): array {
        $recipients = schedule_manager::get_enabled_recipients($schedule->id);
        $course = get_course($schedule->courseid);

        $results = [
            'total'   => count($recipients),
            'sent'    => 0,
            'failed'  => 0,
            'results' => [],
        ];

        $now = time();

        foreach ($recipients as $recipient) {
            // Create log entry.
            $logid = schedule_manager::create_log(
                $schedule->id,
                $schedule->courseid,
                $recipient->email,
                $recipient->id,
                $now,
                'pending',
                array_column($attachments, 'filename')
            );

            // Send email.
            $sendresult = self::send_report_email($schedule, $recipient, $attachments, $course);

            // Update log.
            if ($sendresult['success']) {
                schedule_manager::update_log_status($logid, 'sent');
                $results['sent']++;
            } else {
                schedule_manager::update_log_status(
                    $logid,
                    'failed',
                    $sendresult['errorcode'],
                    $sendresult['error']
                );
                $results['failed']++;
            }

            $results['results'][] = [
                'recipient' => $recipient->email,
                'success'   => $sendresult['success'],
                'error'     => $sendresult['error'],
                'logid'     => $logid,
            ];
        }

        return $results;
    }

    /**
     * Retry sending a failed email from log.
     *
     * @param int $logid The log entry ID.
     * @return array ['success' => bool, 'error' => string]
     */
    public static function retry_from_log(int $logid): array {
        global $DB;

        $log = $DB->get_record(schedule_manager::TABLE_LOGS, ['id' => $logid]);
        if (!$log) {
            return ['success' => false, 'error' => 'Log no encontrado'];
        }

        // Check retry count.
        if ($log->retrycount >= self::MAX_RETRIES) {
            return ['success' => false, 'error' => 'Máximo de reintentos alcanzado'];
        }

        // Get schedule.
        $schedule = schedule_manager::get_schedule($log->scheduleid);
        if (!$schedule) {
            schedule_manager::update_log_status($logid, 'failed', 'SCHEDULE_NOT_FOUND', 'Programación no encontrada');
            return ['success' => false, 'error' => 'Programación no encontrada'];
        }

        // Regenerate reports.
        $reportresults = report_generator::generate_schedule_reports($schedule);

        if (empty($reportresults['files'])) {
            schedule_manager::update_log_status($logid, 'failed', 'NO_REPORTS', 'No se pudieron generar reportes');
            return ['success' => false, 'error' => 'No se pudieron generar reportes'];
        }

        // Create recipient object.
        $recipient = new \stdClass();
        $recipient->id = $log->recipientid;
        $recipient->email = $log->recipientemail;
        $recipient->fullname = '';
        $recipient->userid = null;

        // If we have a recipient ID, get full info.
        if ($log->recipientid) {
            $fullrecipient = $DB->get_record(schedule_manager::TABLE_RECIPIENTS, ['id' => $log->recipientid]);
            if ($fullrecipient) {
                $recipient = $fullrecipient;
            }
        }

        // Send email.
        $sendresult = self::send_report_email($schedule, $recipient, $reportresults['files']);

        // Update log.
        if ($sendresult['success']) {
            schedule_manager::update_log_status($logid, 'sent');
        } else {
            schedule_manager::update_log_status(
                $logid,
                'failed',
                $sendresult['errorcode'],
                $sendresult['error']
            );
        }

        return $sendresult;
    }

    /**
     * Send a test email (for debugging/verification).
     *
     * @param string $email Recipient email address.
     * @param int $courseid Course ID for test report.
     * @param bool $includeattachment Whether to include a test attachment.
     * @return array ['success' => bool, 'error' => string]
     */
    public static function send_test_email(string $email, int $courseid, bool $includeattachment = true): array {
        global $USER;

        try {
            $course = get_course($courseid);

            // Create a fake recipient.
            $recipient = new \stdClass();
            $recipient->email = $email;
            $recipient->fullname = 'Usuario de Prueba';
            $recipient->userid = null;

            // Create a fake schedule.
            $schedule = new \stdClass();
            $schedule->courseid = $courseid;
            $schedule->email_subject = '[PRUEBA] Reporte del curso: {coursename}';
            $schedule->email_body = "Este es un correo de prueba del sistema EpicE Reports.\n\n" .
                "Curso: {coursename}\n" .
                "Fecha: {datetime}\n" .
                "Destinatario: {recipientname} ({recipientemail})\n\n" .
                "Si recibiste este correo, el sistema de envío está funcionando correctamente.";
            $schedule->include_course_report = 1;
            $schedule->include_feedback_report = 0;

            $attachments = [];

            if ($includeattachment) {
                // Generate a test report.
                $reportresult = report_generator::generate_course_excel($courseid, 'test_report_' . time());

                if ($reportresult['success']) {
                    $attachments[] = [
                        'filepath' => $reportresult['filepath'],
                        'filename' => $reportresult['filename'],
                    ];
                }
            }

            return self::send_report_email($schedule, $recipient, $attachments, $course);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Get or create a user object for the recipient.
     *
     * @param object $recipient The recipient object.
     * @return object User-like object for email_to_user().
     */
    private static function get_recipient_user(object $recipient): object {
        global $DB;

        // If recipient has a Moodle user ID, get the full user.
        if (!empty($recipient->userid)) {
            $user = $DB->get_record('user', ['id' => $recipient->userid]);
            if ($user) {
                return $user;
            }
        }

        // Create a fake user object for external recipients.
        $fakeuser = new \stdClass();
        $fakeuser->id = -1;
        $fakeuser->email = $recipient->email;
        $fakeuser->firstname = $recipient->fullname ?: 'Destinatario';
        $fakeuser->lastname = '';
        $fakeuser->maildisplay = 1;
        $fakeuser->mailformat = 1; // HTML
        $fakeuser->deleted = 0;
        $fakeuser->auth = 'manual';
        $fakeuser->suspended = 0;
        $fakeuser->emailstop = 0;

        return $fakeuser;
    }

    /**
     * Get the "from" user for sending emails.
     *
     * @return object The from user object.
     */
    private static function get_from_user(): object {
        global $CFG;

        // Use noreply user.
        return \core_user::get_noreply_user();
    }

    /**
     * Replace placeholders in text.
     *
     * @param string $text The text with placeholders.
     * @param object $course The course object.
     * @param object $user The recipient user object.
     * @return string Text with placeholders replaced.
     */
    private static function replace_placeholders(string $text, object $course, object $user): string {
        $replacements = [
            '{coursename}'      => format_string($course->fullname),
            '{courseshortname}' => format_string($course->shortname),
            '{date}'            => userdate(time(), '%d/%m/%Y'),
            '{datetime}'        => userdate(time(), '%d/%m/%Y %H:%M'),
            '{recipientname}'   => fullname($user),
            '{recipientemail}'  => $user->email,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Convert plain text to HTML.
     *
     * @param string $text Plain text.
     * @return string HTML formatted text.
     */
    private static function text_to_html(string $text): string {
        // Convert newlines to <br> and wrap in basic HTML.
        $html = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));

        return '<html><body style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6;">' .
               '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">' .
               $html .
               '</div></body></html>';
    }

    /**
     * Prepare attachment files for email_to_user().
     *
     * @param array $attachments Array of ['filepath' => string, 'filename' => string].
     * @return array ['files' => string, 'filenames' => string] Comma-separated lists.
     */
    private static function prepare_attachments(array $attachments): array {
        $files = [];
        $filenames = [];

        foreach ($attachments as $attachment) {
            if (!empty($attachment['filepath']) && file_exists($attachment['filepath'])) {
                $files[] = $attachment['filepath'];
                $filenames[] = $attachment['filename'];
            }
        }

        // email_to_user() expects comma-separated strings for multiple attachments
        // or single string for one attachment.
        return [
            'files'     => implode(',', $files),
            'filenames' => implode(',', $filenames),
        ];
    }

    /**
     * Actually send the email using Moodle's email_to_user().
     *
     * @param object $touser Recipient user object.
     * @param object $fromuser Sender user object.
     * @param string $subject Email subject.
     * @param string $body Plain text body.
     * @param string $bodyhtml HTML body.
     * @param string $attachments Comma-separated file paths.
     * @param string $attachnames Comma-separated file names.
     * @return bool True on success.
     */
    private static function do_send_email(
        object $touser,
        object $fromuser,
        string $subject,
        string $body,
        string $bodyhtml,
        string $attachments = '',
        string $attachnames = ''
    ): bool {
        global $CFG;

        // email_to_user signature:
        // email_to_user($user, $from, $subject, $messagetext, $messagehtml='',
        //               $attachment='', $attachname='', $usetrueaddress=true,
        //               $replyto='', $replytoname='', $wordwrapwidth=79)

        return email_to_user(
            $touser,                    // To user
            $fromuser,                  // From user
            $subject,                   // Subject
            $body,                      // Plain text body
            $bodyhtml,                  // HTML body
            $attachments,               // Attachment file path(s)
            $attachnames,               // Attachment file name(s)
            true,                       // Use true address
            '',                         // Reply-to email
            '',                         // Reply-to name
            79                          // Word wrap width
        );
    }

    /**
     * Validate email configuration.
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validate_email_config(): array {
        global $CFG;

        $errors = [];

        // Check if email is enabled.
        if (!empty($CFG->noemailever)) {
            $errors[] = 'El envío de correos está deshabilitado globalmente (noemailever)';
        }

        // Check SMTP configuration.
        if (empty($CFG->smtphosts) && empty($CFG->smtphost)) {
            // Using PHP mail() - should work but may have issues.
            // Not an error, just a note.
        }

        // Check noreply address.
        if (empty($CFG->noreplyaddress)) {
            $errors[] = 'No hay dirección de correo noreply configurada';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get email configuration info for debugging.
     *
     * @return array Configuration details.
     */
    public static function get_email_config_info(): array {
        global $CFG;

        return [
            'smtp_hosts'      => $CFG->smtphosts ?? $CFG->smtphost ?? 'PHP mail()',
            'smtp_user'       => !empty($CFG->smtpuser) ? '(configurado)' : '(no configurado)',
            'smtp_security'   => $CFG->smtpsecure ?? 'ninguno',
            'noreply_address' => $CFG->noreplyaddress ?? '(no configurado)',
            'email_disabled'  => !empty($CFG->noemailever) ? 'Sí' : 'No',
            'charset'         => $CFG->sitemailcharset ?? 'UTF-8',
        ];
    }

    /**
     * Send an email with attachments to a single recipient.
     * Simplified method for manual sending.
     *
     * @param string $email Recipient email address.
     * @param string $subject Email subject.
     * @param string $body Email body text.
     * @param array $attachments Array of file paths to attach.
     * @return array ['success' => bool, 'error' => string]
     */
    public static function send_email_with_attachments(
        string $email,
        string $subject,
        string $body,
        array $attachments
    ): array {
        global $CFG;

        try {
            // Create a fake recipient object.
            $touser = new \stdClass();
            $touser->id = -1;
            $touser->email = $email;
            $touser->emailstop = 0;
            $touser->mailformat = 1;
            $touser->firstname = '';
            $touser->lastname = '';
            $touser->auth = 'manual';
            $touser->suspended = 0;
            $touser->deleted = 0;

            // Get the "from" user.
            $fromuser = self::get_from_user();

            // Convert body to HTML.
            $bodyhtml = self::text_to_html($body);

            // Prepare attachments.
            $attachmentdata = self::prepare_attachments($attachments);

            // Send the email.
            $result = self::do_send_email(
                $touser,
                $fromuser,
                $subject,
                $body,
                $bodyhtml,
                $attachmentdata['files'],
                $attachmentdata['filenames']
            );

            if ($result) {
                return [
                    'success' => true,
                    'error'   => '',
                ];
            } else {
                return [
                    'success' => false,
                    'error'   => 'La función email_to_user() retornó false',
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
}
