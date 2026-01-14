<?php
/**
 * English strings for local_epicereports
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
$string['dashboard'] = 'Dashboard';
$string['dashboardwelcome'] = 'Welcome to the reports center. Here you will find general platform statistics.';
$string['quickaccess'] = 'Quick access';

// Statistics.
$string['active_users'] = 'Active users';
$string['visible_courses'] = 'Visible courses';
$string['hidden_courses'] = 'Hidden courses';
$string['satisfaction'] = 'Satisfaction';
$string['students'] = 'Students';

// Navigation.
$string['courses'] = 'Courses';
$string['courselist'] = 'Course list';
$string['coursedetail'] = 'Course detail';
$string['backtomoodle'] = 'Back to Moodle';
$string['viewcourses'] = 'View all courses';
$string['visiblecoursesonly'] = 'Visible courses only';
$string['hiddencoursesonly'] = 'Hidden courses only';
$string['viewdetail'] = 'View detail';

// Filters and forms.
$string['filter'] = 'Filter';
$string['category'] = 'Category';
$string['visibility'] = 'Visibility';
$string['all'] = 'All';
$string['visible'] = 'Visible';
$string['hidden'] = 'Hidden';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['actions'] = 'Actions';
$string['status'] = 'Status';
$string['results'] = 'Results';

// Courses.
$string['course'] = 'Course';
$string['shortname'] = 'Short name';
$string['nocourses'] = 'No courses found.';
$string['enrolledusers'] = 'Enrolled users';
$string['nousers'] = 'No users enrolled in this course.';
$string['generalinfo'] = 'General information';
$string['enrolled'] = 'Enrolled';
$string['currentcourse'] = 'Current course';
$string['scheduleforcourse'] = 'Report scheduling for';

// User fields.
$string['fullname'] = 'Full name';
$string['email'] = 'Email';
$string['firstaccess'] = 'First access';
$string['lastaccess'] = 'Last access';
$string['groups'] = 'Groups';
$string['progress'] = 'Progress';
$string['completionstatus'] = 'Status';
$string['finalgrade'] = 'Final grade';
$string['name'] = 'Name';

// Export.
$string['exporttoexcel'] = 'Export to Excel';
$string['preview'] = 'Preview';
$string['downloadcertificates'] = 'Download certificates';
$string['exportfeedback'] = 'Export feedback';

// Scheduled reports.
$string['scheduledreports'] = 'Scheduled reports';
$string['schedules'] = 'Schedules';
$string['newschedule'] = 'New schedule';
$string['editschedule'] = 'Edit schedule';
$string['schedulename'] = 'Name';
$string['scheduleenabled'] = 'Enabled';
$string['schedulestatus'] = 'Schedule status';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['active'] = 'Active';
$string['inactive'] = 'Inactive';
$string['noschedules'] = 'No schedules configured.';

// Days.
$string['senddays'] = 'Send days';
$string['monday'] = 'Monday';
$string['tuesday'] = 'Tuesday';
$string['wednesday'] = 'Wednesday';
$string['thursday'] = 'Thursday';
$string['friday'] = 'Friday';
$string['saturday'] = 'Saturday';
$string['sunday'] = 'Sunday';

// Times.
$string['sendtime'] = 'Send time';
$string['startdate'] = 'Start date';
$string['enddate'] = 'End date';
$string['lastrun'] = 'Last run';
$string['nextrun'] = 'Next run';
$string['daterange'] = 'Date range';
$string['noenddate'] = 'No end date';

// Report content.
$string['reportcontent'] = 'Report content';
$string['includecourseexcel'] = 'Include course Excel report';
$string['includefeedbackexcel'] = 'Include feedback report';

// Email.
$string['emailsettings'] = 'Email settings';
$string['emailsubject'] = 'Email subject';
$string['emailbody'] = 'Email body';
$string['emailsubjectdefault'] = 'Course report: {coursename}';
$string['emailbodydefault'] = 'Dear {recipientname},

Please find attached the report for course {coursename}.

Generation date: {date}

Best regards.';

// Recipients.
$string['recipients'] = 'Recipients';
$string['recipient'] = 'Recipient';
$string['addrecipient'] = 'Add recipient';
$string['recipienttype'] = 'Type';
$string['norecipients'] = 'No recipients configured.';
$string['recipientadded'] = 'Recipient added successfully.';
$string['recipientdeleted'] = 'Recipient deleted successfully.';
$string['type'] = 'Type';
$string['optional'] = 'Optional';
$string['add'] = 'Add';
$string['back'] = 'Back';

// Logs.
$string['reportlogs'] = 'Send history';
$string['nologs'] = 'No send records found.';
$string['timescheduled'] = 'Scheduled';
$string['timesent'] = 'Sent';
$string['retries'] = 'Retries';
$string['error'] = 'Error';

// Send statuses.
$string['sent'] = 'Sent';
$string['failed'] = 'Failed';
$string['pending'] = 'Pending';
$string['retry'] = 'Retrying';

// Tools.
$string['tools'] = 'Tools';
$string['testemail'] = 'Test email';
$string['testreports'] = 'Test reports';
$string['testemailinfo'] = 'Use this tool to test sending emails from the platform.';
$string['testreportsinfo'] = 'Use this tool to test course report generation.';
$string['emailconfig'] = 'Email configuration';
$string['sendtestemail'] = 'Send test email';
$string['options'] = 'Options';
$string['includeattachment'] = 'Include test attachment';
$string['testemailsent'] = 'Test email sent successfully to';
$string['testemailfailed'] = 'Error sending test email.';

// Actions.
$string['togglestatus'] = 'Toggle status';
$string['confirmdelete'] = 'Are you sure you want to delete this item?';
$string['scheduledeleted'] = 'Schedule deleted successfully.';
$string['schedulecreated'] = 'Schedule created successfully.';
$string['scheduleupdated'] = 'Schedule updated successfully.';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['save'] = 'Save';
$string['cancel'] = 'Cancel';

// Placeholders.
$string['availableplaceholders'] = 'Available placeholders';
$string['placeholder_coursename'] = 'Full course name';
$string['placeholder_courseshortname'] = 'Short course name';
$string['placeholder_date'] = 'Current date (short format)';
$string['placeholder_datetime'] = 'Current date and time';
$string['placeholder_recipientname'] = 'Recipient name';
$string['placeholder_recipientemail'] = 'Recipient email';

// Errors.
$string['error:invalidcourseid'] = 'Invalid course ID.';
$string['error:invalidscheduleid'] = 'Invalid schedule ID.';
$string['error:nopermission'] = 'You do not have permission to perform this action.';

// Capabilities.
$string['epicereports:view'] = 'View EpicE reports';
$string['epicereports:manage'] = 'Manage EpicE reports';

// Form validation errors.
$string['error:nodaysselected'] = 'You must select at least one day of the week.';
$string['error:enddatebeforestart'] = 'End date must be after start date.';
$string['error:noreportselected'] = 'You must select at least one report type.';

// Help strings.
$string['schedulename_help'] = 'Descriptive name to identify this schedule.';
$string['startdate_help'] = 'Date from which the report will start being sent.';
$string['enddate_help'] = 'Date until which the report will be sent. Leave empty for indefinite sending.';
$string['sendtime_help'] = 'Time of day when the report will be sent.';
$string['senddays_help'] = 'Days of the week when the report will be sent.';
$string['includecourseexcel_help'] = 'Include the Excel report with student progress.';
$string['includefeedbackexcel_help'] = 'Include the satisfaction survey report.';
$string['emailsubject_help'] = 'Email subject. You can use placeholders like {coursename}.';
$string['emailbody_help'] = 'Email body. You can use placeholders like {coursename}, {date}, {recipientname}.';

// Manual send.
$string['sendnow'] = 'Send now';
$string['confirmsendnow'] = 'Are you sure you want to send this report now to all configured recipients?';
$string['manualsend:success'] = 'Report sent successfully to {$a} recipient(s).';
$string['manualsend:partial'] = 'Report sent with some errors: {$a->success} successful, {$a->errors} failed.';
$string['error:norecipients'] = 'There are no recipients configured for this schedule.';
$string['error:noreportsgenerated'] = 'Could not generate the reports.';
$string['error:sendfailed'] = 'Error sending the report';
$string['error:invalidscheduleid'] = 'Invalid schedule ID.';

// Manual send.
$string['sendnow'] = 'Send now';
$string['confirmsendnow'] = 'Are you sure you want to send the report now to all enabled recipients?';
$string['manualsend:success'] = 'Report successfully sent to {$a} recipient(s).';
$string['manualsend:partial'] = 'Partial send: {$a->success} successful, {$a->errors} error(s).';
$string['error:norecipients'] = 'No enabled recipients for this schedule.';
$string['error:noreportsgenerated'] = 'Could not generate any reports.';
$string['error:sendfailed'] = 'Error sending the report';
$string['error:invalidscheduleid'] = 'Invalid schedule ID.';

// Placeholders.
$string['availableplaceholders'] = 'Available placeholders';
$string['placeholder:coursename'] = 'Course name';
$string['placeholder:date'] = 'Report date';
$string['placeholder:recipientname'] = 'Recipient name';
$string['recipientsinstructions'] = 'Recipients are configured after saving the schedule.';
$string['recipientsconfigured'] = 'recipient(s) configured';
