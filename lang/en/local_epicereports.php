<?php
defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'EpicE Reports';
$string['pluginname_help'] = 'Advanced reporting system for Moodle';

// Dashboard.
$string['dashboard'] = 'Dashboard';
$string['dashboardwelcome'] = 'Welcome to the reports panel';
$string['active_users'] = 'Active Users';
$string['visible_courses'] = 'Visible Courses';
$string['hidden_courses'] = 'Hidden Courses';
$string['total_enrolments'] = 'Total Enrolments';
$string['satisfaction_percent'] = 'Satisfaction Percentage';
$string['quickaccess'] = 'Quick access';

// Courses list.
$string['courses'] = 'Courses';
$string['courselist'] = 'Course List';
$string['course'] = 'Course';
$string['coursedetail'] = 'Course Detail';
$string['courseid'] = 'Course ID';
$string['students'] = 'Students';
$string['currentcourse'] = 'Current course';
$string['nocourses'] = 'No courses available.';
$string['viewcourses'] = 'View courses';
$string['viewdetail'] = 'View detail';

// Course filters.
$string['category'] = 'Category';
$string['visibility'] = 'Visibility';
$string['visible'] = 'Visible';
$string['hidden'] = 'Hidden';
$string['visiblecoursesonly'] = 'Visible courses only';
$string['hiddencoursesonly'] = 'Hidden courses only';

// Course info.
$string['fullname'] = 'Full name';
$string['shortname'] = 'Short name';
$string['generalinfo'] = 'General information';
$string['enrolled'] = 'Enrolled';
$string['enrolledusers'] = 'Enrolled users';

// User data.
$string['userdata'] = 'User data';
$string['userid'] = 'User ID';
$string['username'] = 'Username';
$string['idnumber'] = 'ID number';
$string['firstaccess'] = 'First access';
$string['lastaccess'] = 'Last access';
$string['groups'] = 'Groups';

// Progress.
$string['progress'] = 'Progress';
$string['percentage'] = 'Percentage';
$string['completionstatus'] = 'Completion status';
$string['completiondate'] = 'Completion date';
$string['notstarted'] = 'Not started';
$string['started'] = 'Started';
$string['inprogress'] = 'In progress';
$string['completed'] = 'Completed';

// Grades.
$string['grade'] = 'Grade';
$string['finalgrade'] = 'Final grade';
$string['highestgrade'] = 'Highest grade';
$string['score'] = 'Score';
$string['attempts'] = 'Attempts';

// Certificate.
$string['certificate'] = 'Certificate';
$string['certificated'] = 'Certificated';
$string['nocertificates'] = 'No certificates available.';
$string['noissuedcertificates'] = 'No issued certificates.';
$string['downloadcertificates'] = 'Download certificates';

// Feedback / Satisfaction.
$string['satisfaction'] = 'Satisfaction';
$string['feedbackreport'] = 'Feedback report';
$string['nofeedback'] = 'No feedback surveys.';
$string['nofeedbackresponses'] = 'No feedback responses.';
$string['responsesnumber'] = 'Number of responses';

// Submission (assignments).
$string['submission'] = 'Submission';
$string['submissiondate'] = 'Submission date';

// Navigation.
$string['back'] = 'Back';
$string['backtomoodle'] = 'Back to Moodle';

// Scheduled Reports.
$string['scheduledreports'] = 'Scheduled Reports';
$string['schedules'] = 'Schedules';
$string['newschedule'] = 'New Schedule';
$string['editschedule'] = 'Edit Schedule';
$string['schedulename'] = 'Name';
$string['schedulename_help'] = 'Descriptive name to identify this schedule.';
$string['scheduleenabled'] = 'Enabled';
$string['schedulestatus'] = 'Schedule status';
$string['noschedules'] = 'No schedules configured.';
$string['schedulecreated'] = 'Schedule created successfully.';
$string['scheduleupdated'] = 'Schedule updated successfully.';
$string['scheduledeleted'] = 'Schedule deleted successfully.';

// Dates.
$string['daterange'] = 'Date Range';
$string['startdate'] = 'Start Date';
$string['startdate_help'] = 'Date from which the report will start being sent.';
$string['enddate'] = 'End Date';
$string['enddate_help'] = 'Date until which the report will be sent. Leave empty for indefinite sending.';
$string['noenddate'] = 'No end date';
$string['exportdate'] = 'Export date';

// Schedule Time.
$string['sendtime'] = 'Send Time';
$string['sendtime_help'] = 'Time of day when the report will be sent.';
$string['senddays'] = 'Send Days';
$string['senddays_help'] = 'Days of the week when the report will be sent.';
$string['lastrun'] = 'Last Run';

// Days of the week.
$string['monday'] = 'Monday';
$string['tuesday'] = 'Tuesday';
$string['wednesday'] = 'Wednesday';
$string['thursday'] = 'Thursday';
$string['friday'] = 'Friday';
$string['saturday'] = 'Saturday';
$string['sunday'] = 'Sunday';

// Report Content.
$string['reportcontent'] = 'Report Content';
$string['includecourseexcel'] = 'Include course progress report (Excel)';
$string['includecourseexcel_help'] = 'Include the Excel report with student progress.';
$string['includefeedbackexcel'] = 'Include feedback report (Excel)';
$string['includefeedbackexcel_help'] = 'Include the satisfaction survey report.';
$string['coursereport'] = 'Course report';

// Email Settings.
$string['emailsettings'] = 'Email Settings';
$string['emailsubject'] = 'Email Subject';
$string['emailsubject_help'] = 'Email subject. You can use placeholders like {coursename}.';
$string['emailbody'] = 'Email Body';
$string['emailbody_help'] = 'Email body. You can use placeholders like {coursename}, {date}, {recipientname}.';
$string['emailsubjectdefault'] = 'Course Report {coursename} - {date}';
$string['emailbodydefault'] = 'Dear {recipientname},

Please find attached the report for course {coursename} for {date}.

Best regards.';

// Placeholders.
$string['availableplaceholders'] = 'Available placeholders';
$string['placeholder:coursename'] = 'Course name';
$string['placeholder:date'] = 'Report date';
$string['placeholder:recipientname'] = 'Recipient name';

// Recipients.
$string['recipients'] = 'Recipients';
$string['recipient'] = 'Recipient';
$string['recipientsinstructions'] = 'Recipients are configured after saving the schedule.';
$string['recipientsconfigured'] = 'recipient(s) configured';
$string['addrecipient'] = 'Add Recipient';
$string['norecipients'] = 'No recipients configured.';
$string['recipientadded'] = 'Recipient added successfully.';
$string['recipientdeleted'] = 'Recipient deleted successfully.';

// Status.
$string['status'] = 'Status';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['active'] = 'Active';
$string['inactive'] = 'Inactive';
$string['togglestatus'] = 'Toggle status';

// Actions.
$string['actions'] = 'Actions';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['save'] = 'Save';
$string['cancel'] = 'Cancel';
$string['add'] = 'Add';
$string['clear'] = 'Clear';
$string['confirmdelete'] = 'Are you sure you want to delete this item?';

// Manual Send.
$string['sendnow'] = 'Send Now';
$string['confirmsendnow'] = 'Are you sure you want to send the report now to all enabled recipients?';
$string['manualsend:success'] = 'Report sent successfully to {$a} recipient(s).';
$string['manualsend:partial'] = 'Partial send: {$a->success} successful, {$a->errors} error(s).';

// Logs / History.
$string['logs'] = 'Send History';
$string['reportlogs'] = 'Send History';
$string['nologs'] = 'No send records found.';
$string['logdate'] = 'Date';
$string['logemail'] = 'Email';
$string['logstatus'] = 'Status';
$string['logattachments'] = 'Attachments';
$string['logerror'] = 'Error';

// Log table columns.
$string['timescheduled'] = 'Scheduled Date';
$string['timesent'] = 'Sent Date';
$string['retries'] = 'Retries';

// Filter.
$string['filter'] = 'Filter';
$string['all'] = 'All';
$string['results'] = 'Results';
$string['count'] = 'Total';

// Status values.
$string['success'] = 'Success';
$string['error'] = 'Error';
$string['pending'] = 'Pending';
$string['sent'] = 'Sent';
$string['failed'] = 'Failed';

// Tools.
$string['tools'] = 'Tools';
$string['testreports'] = 'Test Reports';
$string['testreportsinfo'] = 'Generate and download test reports to verify the configuration.';
$string['testemail'] = 'Test Email';
$string['generatereport'] = 'Generate Report';
$string['sendemail'] = 'Send Email';
$string['downloadreport'] = 'Download Report';

// Export.
$string['exporttoexcel'] = 'Export to Excel';
$string['exportfeedback'] = 'Export feedback';
$string['downloadexcel'] = 'Download Excel';
$string['preview'] = 'Preview';
$string['previewdescription'] = 'Preview of data to export.';
$string['generatedby'] = 'Generated by';
$string['nofilesfound'] = 'No files found.';
$string['nofilesfound_help'] = 'Verify that the course has data to export.';

// Form fields.
$string['email'] = 'Email';
$string['name'] = 'Name';
$string['type'] = 'Type';
$string['optional'] = 'optional';
$string['criteria'] = 'Criteria';
$string['summary'] = 'Summary';

// Yes/No.
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['nousers'] = 'No users.';

// Error messages.
$string['error:invalidscheduleid'] = 'Invalid schedule ID.';
$string['error:norecipients'] = 'No enabled recipients for this schedule.';
$string['error:noreportsgenerated'] = 'Could not generate any reports.';
$string['error:sendfailed'] = 'Error sending report';
$string['error:nodaysselected'] = 'You must select at least one day of the week.';
$string['error:enddatebeforestart'] = 'End date must be after start date.';
$string['error:noreportselected'] = 'You must select at least one report type.';

// Capabilities.
$string['epicereports:view'] = 'View EpicE reports';
$string['epicereports:manage'] = 'Manage EpicE reports';
$string['epicereports:viewstudents'] = 'View student reports';
$string['epicereports:managefollowup'] = 'Manage follow-up messages';

// Task.
$string['task:sendscheduledreports'] = 'Send scheduled reports';
$string['task:cleanuptempfiles'] = 'Cleanup temporary files';
$string['task:sendfollowupmessages'] = 'Send follow-up messages';

// =====================================================================
// F1: Individual Student Report
// =====================================================================

// Students list.
$string['studentslist'] = 'Students list';
$string['studentreport'] = 'Student report';
$string['studentdetail'] = 'Student detail';
$string['viewstudent'] = 'View student';
$string['nostudents'] = 'No students registered.';
$string['searchstudent'] = 'Search student';
$string['searchbyname'] = 'Search by name or email';

// Student filters.
$string['filterby'] = 'Filter by';
$string['cohort'] = 'Cohort';
$string['allcohorts'] = 'All cohorts';
$string['registrationdate'] = 'Registration date';
$string['company'] = 'Company';
$string['allcompanies'] = 'All companies';
$string['daterange'] = 'Date range';
$string['datefrom'] = 'From';
$string['dateto'] = 'To';

// Student info.
$string['studentinfo'] = 'Student information';
$string['personaldata'] = 'Personal data';
$string['registeredon'] = 'Registered on';
$string['lastlogin'] = 'Last login';

// Student courses summary.
$string['coursessummary'] = 'Courses summary';
$string['coursescompleted'] = 'Completed courses';
$string['coursesinprogress'] = 'In progress courses';
$string['coursesnotstarted'] = 'Not started courses';
$string['totalenrolled'] = 'Total enrolled';
$string['completionrate'] = 'Completion rate';

// Student courses table.
$string['enrolledcourses'] = 'Enrolled courses';
$string['coursename'] = 'Course name';
$string['enrollmentdate'] = 'Enrollment date';
$string['downloadcertificate'] = 'Download certificate';
$string['nocertificate'] = 'No certificate';
$string['certificateavailable'] = 'Certificate available';

// PDF Export.
$string['exporttopdf'] = 'Export to PDF';
$string['studentreportpdf'] = 'Student report PDF';
$string['generatedby'] = 'Generated by';
$string['generatedon'] = 'Generated on';
$string['page'] = 'Page';
$string['of'] = 'of';

// Chart labels.
$string['chartcompletedlabel'] = 'Completed';
$string['chartinprogresslabel'] = 'In progress';
$string['chartnotstartedlabel'] = 'Not started';

// =====================================================================
// F2: Follow-up Messages
// =====================================================================

// Followup messages.
$string['followupmessages'] = 'Follow-up messages';
$string['followupmessagesdesc'] = 'Send automatic reminders to students who have not completed the course.';
$string['newfollowup'] = 'New schedule';
$string['editfollowup'] = 'Edit schedule';
$string['deletefollowup'] = 'Delete schedule';
$string['nofollowups'] = 'No follow-up schedules configured.';

// Followup form.
$string['followupname'] = 'Schedule name';
$string['followupname_help'] = 'Descriptive name to identify this message schedule.';
$string['followupenabled'] = 'Schedule active';
$string['targetstudents'] = 'Target students';
$string['targetstudents_help'] = 'Select which students will receive the follow-up message.';
$string['target_not_started'] = 'Students who have not started';
$string['target_in_progress'] = 'Students in progress';
$string['target_all_incomplete'] = 'All not completed';

// Channels.
$string['sendchannels'] = 'Send channels';
$string['sendchannels_help'] = 'Select through which channels the messages will be sent.';
$string['sendemail'] = 'Send by external email';
$string['sendmessage'] = 'Send by Moodle messaging';

// Message content.
$string['messagecontent'] = 'Message content';
$string['messagesubject'] = 'Message subject';
$string['messagesubject_help'] = 'Message subject. You can use placeholders like {username}, {coursename}.';
$string['messagebody'] = 'Message body';
$string['messagebody_help'] = 'Message body in HTML. You can use placeholders like {username}, {coursename}, {progress}.';
$string['messagesubjectdefault'] = 'Reminder: Continue your course {coursename}';
$string['messagebodydefault'] = '<p>Hello {username},</p>
<p>We remind you that you have pending to complete the course <strong>{coursename}</strong>.</p>
<p>Your current progress is {progress}%.</p>
<p>Keep up with your training!</p>
<p>Best regards.</p>';

// Placeholders for followup.
$string['placeholder:username'] = 'Student name';
$string['placeholder:useremail'] = 'Student email';
$string['placeholder:coursename'] = 'Course name';
$string['placeholder:progress'] = 'Progress percentage';
$string['placeholder:duedate'] = 'Course due date (if exists)';

// Schedule settings.
$string['schedulesettings'] = 'Schedule settings';
$string['specificdates'] = 'Specific dates';
$string['specificdates_help'] = 'Enter specific sending dates (one per line, format DD/MM/YYYY). Messages will be sent on these dates in addition to the selected weekdays.';
$string['maxperuser'] = 'Limit per user';
$string['maxperuser_help'] = 'Maximum number of messages a user can receive.';
$string['maxperuser_daily'] = 'Maximum 1 per day';
$string['maxperuser_weekly'] = 'Maximum 1 per week';

// Followup actions.
$string['followupcreated'] = 'Follow-up schedule created successfully.';
$string['followupupdated'] = 'Follow-up schedule updated successfully.';
$string['followupdeleted'] = 'Follow-up schedule deleted successfully.';
$string['confirmfollowupdelete'] = 'Are you sure you want to delete this follow-up schedule?';

// Followup send now.
$string['followupsendnow'] = 'Send now';
$string['confirmfollowupsendnow'] = 'Are you sure you want to send the follow-up message now to all students who meet the criteria?';
$string['followupsent'] = 'Follow-up message sent to {$a} student(s).';
$string['followupsentnone'] = 'No students meet the criteria to receive the message.';

// Followup logs.
$string['followuplogs'] = 'Follow-up message history';
$string['nofollowuplogs'] = 'No follow-up message records.';
$string['channel'] = 'Channel';
$string['channel_email'] = 'Email';
$string['channel_message'] = 'Moodle messaging';
$string['channel_both'] = 'Both';

// Preview.
$string['previewmessage'] = 'Message preview';
$string['previewsampledata'] = 'Sample data for preview';

// Errors.
$string['error:nochannelselected'] = 'You must select at least one send channel.';
$string['error:invalidfollowupid'] = 'Invalid follow-up schedule ID.';
$string['error:nostudentsmatching'] = 'No students meet the selected criteria.';
$string['error:userlimitreached'] = 'The student has already received the maximum allowed messages.';
$string['error:invaliddate'] = 'Invalid date: {$a}';
