# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**local_epicereports** is a Moodle 4.1+ local plugin that generates course activity reports and feedback/survey reports, with scheduled email delivery functionality. The plugin creates Excel-formatted reports showing student progress, completion status, module grades, and feedback survey responses.

**Key capabilities:**
- Generate Excel reports for course completions (SCORM, Quiz, Assignment modules)
- Export feedback/survey results to Excel
- Schedule automated report delivery via email (cron-based)
- Manage recipients and delivery schedules per course
- Track email delivery logs with retry mechanism

## Development Commands

This is a Moodle plugin - there are no build or test commands. Development workflow:

1. **Install/Update plugin**: Navigate to Moodle admin notifications (`/admin/index.php`) after modifying version.php
2. **Database upgrades**: Edit `db/upgrade.php` and increment version in `version.php`
3. **Clear caches**: Use Moodle admin purge all caches (`/admin/purgecaches.php`) or CLI:
   ```bash
   php admin/cli/purge_caches.php
   ```
4. **Test scheduled task manually**:
   ```bash
   php admin/tool/task/cli/schedule_task.php --execute='\local_epicereports\task\send_scheduled_reports'
   ```
5. **Language strings**: Edit `lang/en/local_epicereports.php` and `lang/es/local_epicereports.php`

## Architecture

### Database Schema (3 main tables)

**local_epicereports_schedules** - Report delivery schedules per course
- Controls when/how reports are sent (days of week, time, date range)
- Fields: courseid, enabled, startdate, enddate, sendtime, monday-sunday flags
- Tracks `lastrun` and `nextrun` timestamps for scheduling
- Contains email template (subject/body with placeholder support)

**local_epicereports_recipients** - Email recipients per schedule
- Supports Moodle users (userid) or external emails
- Types: to/cc/bcc
- Can be individually enabled/disabled

**local_epicereports_logs** - Delivery audit trail
- Status tracking: pending → sent/failed/retry
- Stores error messages and retry counts (max 3 attempts)
- Records which attachments were sent

### Core Classes

**\local_epicereports\schedule_manager** ([classes/schedule_manager.php](classes/schedule_manager.php))
- CRUD operations for schedules, recipients, and logs
- `get_pending_schedules()` - Finds schedules ready to execute
- `calculate_next_run()` - Complex logic for next execution timestamp based on enabled weekdays
- `mark_schedule_run()` - Updates lastrun/nextrun after execution

**\local_epicereports\report_generator** ([classes/report_generator.php](classes/report_generator.php))
- Uses PhpSpreadsheet (Moodle's bundled library) to create Excel files
- `generate_course_excel()` - Creates detailed student progress report with module-specific columns
- `generate_feedback_excel()` - Creates survey response report (one sheet per feedback activity)
- Saves to temporary directory: `$CFG->tempdir/local_epicereports/reports/`
- Module-specific handling: SCORM (status/attempts/score), Quiz (status/attempts/grade), Assign (status/submission/date/grade)

**\local_epicereports\email_sender** ([classes/email_sender.php](classes/email_sender.php))
- Wraps Moodle's `email_to_user()` with retry logic
- Supports placeholders: `{coursename}`, `{date}`, `{recipientname}`, etc.
- Creates fake user objects for external email recipients
- Handles multiple attachments as comma-separated file paths

**\local_epicereports\helper** ([classes/helper.php](classes/helper.php))
- `get_course_data_for_excel()` - Complex aggregation of student data, module completions, grades
- Queries multiple Moodle tables: course_modules_completion, scorm_scoes_track, quiz_attempts, assign_submission

### Scheduled Task

**\local_epicereports\task\send_scheduled_reports** ([classes/task/send_scheduled_reports.php](classes/task/send_scheduled_reports.php))
- Runs every 5 minutes (configured in [db/tasks.php](db/tasks.php))
- Workflow per pending schedule:
  1. Get enabled recipients
  2. Generate Excel reports via report_generator
  3. Send emails with attachments via email_sender
  4. Create log entries (one per recipient)
  5. Update schedule lastrun/nextrun
  6. Clean up temporary files
- Also processes retry queue (failed emails with retrycount < 3)

### User Interface

All pages use a common sidebar navigation (`local_epicereports_render_sidebar()` in [locallib.php](locallib.php)):
- [dashboard.php](dashboard.php) - Platform-wide statistics
- [courses.php](courses.php) - List all courses with search/filter
- [course_detail.php](course_detail.php) - Student progress for specific course
- [schedule_reports.php](schedule_reports.php) - Manage scheduled deliveries
- [schedule_logs.php](schedule_logs.php) - View delivery history
- [test_reports.php](test_reports.php) - Generate/preview reports manually
- [test_email.php](test_email.php) - Send test emails

Export pages (generate Excel downloads):
- [export_course_excel.php](export_course_excel.php)
- [export_course_csv.php](export_course_csv.php)
- [export_certificates.php](export_certificates.php)
- [export_feedback.php](export_feedback.php)

### Moodle Integration Points

**Navigation**: `local_epicereports_extend_navigation()` in [lib.php](lib.php) adds menu item to global navigation

**Capabilities** ([db/access.php](db/access.php)):
- `local/epicereports:view` - View reports (managers, editing teachers)
- `local/epicereports:manageschedules` - Create/edit schedules
- `local/epicereports:viewlogs` - View delivery logs
- `local/epicereports:resend` - Manually retry failed deliveries

**Database upgrades**: [db/upgrade.php](db/upgrade.php) - Creates tables on version 2024060104+

## Important Patterns

**Next Run Calculation**
The `calculate_next_run()` method in schedule_manager is critical for scheduling:
- Checks enabled days (monday through sunday boolean fields)
- Parses `sendtime` (HH:MM format)
- Respects `startdate`/`enddate` range
- Returns null if schedule has ended or no valid days

**Module-Specific Data Collection**
When generating course reports, helper.php queries different tables based on module type:
- SCORM: `scorm_scoes_track` table for score/status
- Quiz: `quiz_attempts` and `quiz_grades` for attempts/grades
- Assignment: `assign_submission` and `assign_grades`
- Generic: `course_modules_completion` for completion status

**Temporary File Management**
Reports are generated in `$CFG->tempdir/local_epicereports/reports/` and deleted after email sending. The cleanup_temp_files scheduled task (runs daily at 3:30 AM) removes files older than 24 hours.

**Email Recipient Types**
Recipients can be:
1. Moodle users (has `userid`) - full user object loaded
2. External emails (no `userid`) - fake user object created with email/firstname

## Version History

Current version: **2024060208** (v1.3-beta)
- 2024060103: Initial version
- 2024060104: Added scheduling tables
- 2024060105: Excel report generator
- 2024060106: Email sending and scheduled task complete
- 2024060207-208: Current iteration with bug fixes

## Language Support

Bilingual plugin with English ([lang/en/local_epicereports.php](lang/en/local_epicereports.php)) and Spanish ([lang/es/local_epicereports.php](lang/es/local_epicereports.php)) translations.

## Styling

Custom CSS in [css/styles.css](css/styles.css) and inline styles in [locallib.php](locallib.php) for sidebar, stat cards, badges, and progress bars. Uses Bootstrap 4 classes (Moodle 4.x default) and Font Awesome icons.
