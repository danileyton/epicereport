<?php
/**
 * English language strings for local_epicereports
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'EpicE Reports';
$string['pluginname_help'] = 'Advanced reporting system for Moodle';

// Dashboard.
$string['active_users'] = 'Active Users';
$string['visible_courses'] = 'Visible Courses';
$string['hidden_courses'] = 'Hidden Courses';

// Capabilities.
$string['epicereports:view'] = 'View EpicE Reports';
$string['epicereports:manageschedules'] = 'Manage report schedules';
$string['epicereports:viewlogs'] = 'View report sending logs';
$string['epicereports:resend'] = 'Resend reports manually';

// Schedule management.
$string['scheduledreports'] = 'Scheduled Reports';
$string['newschedule'] = 'New Schedule';
$string['editschedule'] = 'Edit Schedule';
$string['deleteschedule'] = 'Delete Schedule';
$string['deletescheduleconfirm'] = 'Are you sure you want to delete this schedule? This action cannot be undone.';
$string['schedulename'] = 'Schedule Name';
$string['schedulename_help'] = 'A descriptive name for this schedule (e.g., "Weekly Report to Manager")';
$string['scheduleenabled'] = 'Enabled';
$string['scheduledisabled'] = 'Disabled';
$string['schedulestatus'] = 'Status';

// Date and time settings.
$string['daterange'] = 'Date Range';
$string['startdate'] = 'Start Date';
$string['startdate_help'] = 'The schedule will start sending reports from this date';
$string['enddate'] = 'End Date';
$string['enddate_help'] = 'The schedule will stop sending reports after this date. Leave empty for no end date.';
$string['sendtime'] = 'Send Time';
$string['sendtime_help'] = 'Time of day when reports will be sent (server timezone)';
$string['senddays'] = 'Send Days';
$string['senddays_help'] = 'Select the days of the week when reports should be sent';
$string['monday'] = 'Monday';
$string['tuesday'] = 'Tuesday';
$string['wednesday'] = 'Wednesday';
$string['thursday'] = 'Thursday';
$string['friday'] = 'Friday';
$string['saturday'] = 'Saturday';
$string['sunday'] = 'Sunday';

// Report content.
$string['reportcontent'] = 'Report Content';
$string['includecourseexcel'] = 'Include Course Report (Excel)';
$string['includecourseexcel_help'] = 'Attach the course progress Excel report to the email';
$string['includefeedbackexcel'] = 'Include Feedback Report (Excel)';
$string['includefeedbackexcel_help'] = 'Attach the feedback/survey responses Excel report to the email';

// Email settings.
$string['emailsettings'] = 'Email Settings';
$string['emailsubject'] = 'Email Subject';
$string['emailsubject_help'] = 'Custom subject for the email. You can use placeholders: {coursename}, {date}';
$string['emailsubjectdefault'] = 'Course Report: {coursename} - {date}';
$string['emailbody'] = 'Email Body';
$string['emailbody_help'] = 'Custom message body for the email. You can use placeholders: {coursename}, {date}, {recipientname}';
$string['emailbodydefault'] = 'Dear {recipientname},

Please find attached the report(s) for course "{coursename}" generated on {date}.

This is an automated message from the EpicE Reports system.';

// Recipients.
$string['recipients'] = 'Recipients';
$string['addrecipient'] = 'Add Recipient';
$string['removerecipient'] = 'Remove Recipient';
$string['recipientemail'] = 'Email Address';
$string['recipientname'] = 'Name';
$string['recipienttype'] = 'Type';
$string['recipienttype_to'] = 'To';
$string['recipienttype_cc'] = 'CC';
$string['recipienttype_bcc'] = 'BCC';
$string['selectuser'] = 'Select Moodle User';
$string['externalrecipient'] = 'External Email';
$string['norecipients'] = 'No recipients configured';
$string['atleastonerecipient'] = 'At least one recipient is required';
$string['duplicateemail'] = 'This email address is already added';
$string['invalidemail'] = 'Invalid email address';

// Execution.
$string['lastrun'] = 'Last Run';
$string['nextrun'] = 'Next Run';
$string['neverrun'] = 'Never';
$string['runningnow'] = 'Running now...';
$string['runnow'] = 'Run Now';
$string['runnowconfirm'] = 'Are you sure you want to run this schedule now? Reports will be sent immediately.';

// Logs.
$string['reportlogs'] = 'Report Logs';
$string['viewlogs'] = 'View Logs';
$string['logstatus'] = 'Status';
$string['logstatus_pending'] = 'Pending';
$string['logstatus_sent'] = 'Sent';
$string['logstatus_failed'] = 'Failed';
$string['logstatus_retry'] = 'Retry Pending';
$string['logtimescheduled'] = 'Scheduled Time';
$string['logtimesent'] = 'Sent Time';
$string['logrecipient'] = 'Recipient';
$string['logerror'] = 'Error';
$string['logattachments'] = 'Attachments';
$string['logretrycount'] = 'Retry Count';
$string['resend'] = 'Resend';
$string['resendconfirm'] = 'Are you sure you want to resend this report?';
$string['resendsuccessful'] = 'Report has been queued for resending';
$string['nologs'] = 'No logs found';

// Task.
$string['taskname'] = 'Send scheduled reports';
$string['taskdescription'] = 'Processes and sends scheduled report emails with attachments';

// Messages and notifications.
$string['schedulecreated'] = 'Schedule created successfully';
$string['scheduleupdated'] = 'Schedule updated successfully';
$string['scheduledeleted'] = 'Schedule deleted successfully';
$string['schedulenotsaved'] = 'Error saving schedule';
$string['emailsent'] = 'Email sent successfully';
$string['emailfailed'] = 'Failed to send email';
$string['reportgenerated'] = 'Report generated successfully';
$string['reportgenerationfailed'] = 'Failed to generate report';
$string['noactivitieswithcompletion'] = 'No activities with completion tracking in this course';

// Errors.
$string['error:invalidcourseid'] = 'Invalid course ID';
$string['error:invalidscheduleid'] = 'Invalid schedule ID';
$string['error:nopermission'] = 'You do not have permission to perform this action';
$string['error:nodaysselected'] = 'At least one day must be selected';
$string['error:invalidtimeformat'] = 'Invalid time format. Use HH:MM';
$string['error:enddatebeforestart'] = 'End date cannot be before start date';
$string['error:startdateinpast'] = 'Start date cannot be in the past';
$string['error:noreportselected'] = 'At least one report type must be selected';

// Sidebar menu.
$string['menu_schedules'] = 'Scheduled Reports';
$string['menu_logs'] = 'Send Logs';
