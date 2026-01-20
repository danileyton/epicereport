<?php
/**
 * Student Helper class for local_epicereports
 *
 * Handles student data retrieval and processing
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports;

defined('MOODLE_INTERNAL') || die();

/**
 * Class student_helper
 *
 * Helper functions for student reports
 */
class student_helper {

    /** @var string Default custom field shortname for company */
    const COMPANY_FIELD = 'empresa';

    /**
     * Get all students with optional filters.
     *
     * @param string $search Search term (name or email).
     * @param int $cohortid Filter by cohort ID (0 = all).
     * @param string $company Filter by company (custom field).
     * @param int $datefrom Filter by registration date from (timestamp).
     * @param int $dateto Filter by registration date to (timestamp).
     * @param int $limit Maximum records to return (0 = no limit).
     * @param int $offset Offset for pagination.
     * @return array Array of student objects.
     */
    public static function get_students(
        string $search = '',
        int $cohortid = 0,
        string $company = '',
        int $datefrom = 0,
        int $dateto = 0,
        int $limit = 0,
        int $offset = 0
    ): array {
        global $DB;

        $params = [];
        $joins = [];
        $where = ['u.deleted = 0', 'u.suspended = 0', 'u.id > 2']; // Exclude guest and admin

        // Search by name or email.
        if (!empty($search)) {
            $search = trim($search);
            $where[] = $DB->sql_like(
                $DB->sql_concat('u.firstname', "' '", 'u.lastname', "' '", 'u.email'),
                ':search',
                false
            );
            $params['search'] = '%' . $DB->sql_like_escape($search) . '%';
        }

        // Filter by cohort.
        if ($cohortid > 0) {
            $joins[] = "JOIN {cohort_members} cm ON cm.userid = u.id AND cm.cohortid = :cohortid";
            $params['cohortid'] = $cohortid;
        }

        // Filter by company (custom user field).
        if (!empty($company)) {
            $fieldid = self::get_company_field_id();
            if ($fieldid) {
                $joins[] = "JOIN {user_info_data} uid ON uid.userid = u.id AND uid.fieldid = :fieldid AND uid.data = :company";
                $params['fieldid'] = $fieldid;
                $params['company'] = $company;
            }
        }

        // Filter by registration date.
        if ($datefrom > 0) {
            $where[] = "u.timecreated >= :datefrom";
            $params['datefrom'] = $datefrom;
        }
        if ($dateto > 0) {
            $where[] = "u.timecreated <= :dateto";
            $params['dateto'] = $dateto;
        }

        $joinssql = implode(' ', $joins);
        $wheresql = implode(' AND ', $where);

        $sql = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email, 
                       u.timecreated, u.lastaccess, u.idnumber
                  FROM {user} u
                  $joinssql
                 WHERE $wheresql
              ORDER BY u.lastname, u.firstname";

        if ($limit > 0) {
            return $DB->get_records_sql($sql, $params, $offset, $limit);
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Count students with optional filters.
     *
     * @param string $search Search term.
     * @param int $cohortid Cohort ID.
     * @param string $company Company name.
     * @param int $datefrom Date from.
     * @param int $dateto Date to.
     * @return int Number of students.
     */
    public static function count_students(
        string $search = '',
        int $cohortid = 0,
        string $company = '',
        int $datefrom = 0,
        int $dateto = 0
    ): int {
        global $DB;

        $params = [];
        $joins = [];
        $where = ['u.deleted = 0', 'u.suspended = 0', 'u.id > 2'];

        if (!empty($search)) {
            $search = trim($search);
            $where[] = $DB->sql_like(
                $DB->sql_concat('u.firstname', "' '", 'u.lastname', "' '", 'u.email'),
                ':search',
                false
            );
            $params['search'] = '%' . $DB->sql_like_escape($search) . '%';
        }

        if ($cohortid > 0) {
            $joins[] = "JOIN {cohort_members} cm ON cm.userid = u.id AND cm.cohortid = :cohortid";
            $params['cohortid'] = $cohortid;
        }

        if (!empty($company)) {
            $fieldid = self::get_company_field_id();
            if ($fieldid) {
                $joins[] = "JOIN {user_info_data} uid ON uid.userid = u.id AND uid.fieldid = :fieldid AND uid.data = :company";
                $params['fieldid'] = $fieldid;
                $params['company'] = $company;
            }
        }

        if ($datefrom > 0) {
            $where[] = "u.timecreated >= :datefrom";
            $params['datefrom'] = $datefrom;
        }
        if ($dateto > 0) {
            $where[] = "u.timecreated <= :dateto";
            $params['dateto'] = $dateto;
        }

        $joinssql = implode(' ', $joins);
        $wheresql = implode(' AND ', $where);

        $sql = "SELECT COUNT(DISTINCT u.id)
                  FROM {user} u
                  $joinssql
                 WHERE $wheresql";

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Get basic information of a student.
     *
     * @param int $userid User ID.
     * @return object|null Student object or null if not found.
     */
    public static function get_student_info(int $userid): ?object {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', IGNORE_MISSING);
        if (!$user) {
            return null;
        }

        $student = new \stdClass();
        $student->id = $user->id;
        $student->username = $user->username;
        $student->firstname = $user->firstname;
        $student->lastname = $user->lastname;
        $student->fullname = fullname($user);
        $student->email = $user->email;
        $student->idnumber = $user->idnumber;
        $student->timecreated = $user->timecreated;
        $student->lastaccess = $user->lastaccess;
        $student->picture = $user->picture;

        // Get company from custom field.
        $student->company = self::get_user_company($userid);

        // Get cohorts.
        $student->cohorts = self::get_user_cohorts($userid);

        return $student;
    }

    /**
     * Get courses summary for a student (completed, in progress, not started).
     *
     * @param int $userid User ID.
     * @return array Array with summary data.
     */
    public static function get_student_courses_summary(int $userid): array {
        global $DB;

        $summary = [
            'total' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'not_started' => 0,
            'completion_rate' => 0,
        ];

        // Get all courses where user is enrolled.
        $sql = "SELECT DISTINCT c.id
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = :userid
                   AND c.id > 1
                   AND ue.status = 0";

        $courses = $DB->get_records_sql($sql, ['userid' => $userid]);

        if (empty($courses)) {
            return $summary;
        }

        $summary['total'] = count($courses);

        foreach ($courses as $course) {
            $status = self::get_user_course_status($userid, $course->id);

            if ($status === 'completed') {
                $summary['completed']++;
            } else if ($status === 'in_progress') {
                $summary['in_progress']++;
            } else {
                $summary['not_started']++;
            }
        }

        if ($summary['total'] > 0) {
            $summary['completion_rate'] = round(($summary['completed'] / $summary['total']) * 100, 1);
        }

        return $summary;
    }

    /**
     * Get detailed list of enrolled courses for a student.
     *
     * @param int $userid User ID.
     * @return array Array of course data objects.
     */
    public static function get_student_enrolled_courses(int $userid): array {
        global $DB;

        $sql = "SELECT c.id, c.fullname, c.shortname, c.visible,
                       ue.timecreated as enrollmentdate,
                       ue.timestart, ue.timeend
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = :userid
                   AND c.id > 1
                   AND ue.status = 0
              ORDER BY c.fullname";

        $courses = $DB->get_records_sql($sql, ['userid' => $userid]);
        $result = [];

        foreach ($courses as $course) {
            $coursedata = new \stdClass();
            $coursedata->id = $course->id;
            $coursedata->fullname = $course->fullname;
            $coursedata->shortname = $course->shortname;
            $coursedata->visible = $course->visible;
            $coursedata->enrollmentdate = $course->enrollmentdate;

            // Get completion status.
            $coursedata->status = self::get_user_course_status($userid, $course->id);

            // Get progress percentage.
            $coursedata->progress = self::get_user_course_progress($userid, $course->id);

            // Get final grade.
            $coursedata->finalgrade = self::get_user_course_grade($userid, $course->id);

            // Get completion date.
            $coursedata->completiondate = self::get_user_course_completion_date($userid, $course->id);

            // Check for certificate.
            $coursedata->certificate = self::get_student_certificate($userid, $course->id);

            $result[] = $coursedata;
        }

        return $result;
    }

    /**
     * Get certificate for a student in a course.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID.
     * @return object|null Certificate data or null if not available.
     */
    public static function get_student_certificate(int $userid, int $courseid): ?object {
        global $DB;

        $dbman = $DB->get_manager();

        // Check simplecertificate plugin.
        if ($dbman->table_exists('simplecertificate') && $dbman->table_exists('simplecertificate_issues')) {
            $sql = "SELECT si.id, si.code, si.timecreated, s.name as certificatename, s.id as certid
                      FROM {simplecertificate_issues} si
                      JOIN {simplecertificate} s ON si.certificateid = s.id
                     WHERE si.userid = :userid
                       AND s.course = :courseid
                       AND (si.timedeleted IS NULL OR si.timedeleted = 0)
                  ORDER BY si.timecreated DESC
                     LIMIT 1";

            $cert = $DB->get_record_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);

            if ($cert) {
                $certificate = new \stdClass();
                $certificate->id = $cert->id;
                $certificate->certid = $cert->certid;
                $certificate->code = $cert->code;
                $certificate->name = $cert->certificatename;
                $certificate->timecreated = $cert->timecreated;
                $certificate->downloadurl = new \moodle_url('/mod/simplecertificate/view.php', [
                    'id' => self::get_course_module_id('simplecertificate', $cert->certid),
                    'action' => 'get',
                    'userid' => $userid
                ]);
                return $certificate;
            }
        }

        return null;
    }

    /**
     * Get all cohorts for filter dropdown.
     *
     * @return array Array of cohort [id => name].
     */
    public static function get_cohorts_for_filter(): array {
        global $DB;

        $cohorts = $DB->get_records('cohort', [], 'name ASC', 'id, name');
        $result = [0 => get_string('allcohorts', 'local_epicereports')];

        foreach ($cohorts as $cohort) {
            $result[$cohort->id] = $cohort->name;
        }

        return $result;
    }

    /**
     * Get all companies (from custom user field) for filter dropdown.
     *
     * @param string $fieldshortname Shortname of the custom field.
     * @return array Array of company names.
     */
    public static function get_companies_for_filter(string $fieldshortname = ''): array {
        global $DB;

        if (empty($fieldshortname)) {
            $fieldshortname = self::COMPANY_FIELD;
        }

        $result = ['' => get_string('allcompanies', 'local_epicereports')];

        $fieldid = self::get_company_field_id($fieldshortname);
        if (!$fieldid) {
            return $result;
        }

        $sql = "SELECT DISTINCT uid.data
                  FROM {user_info_data} uid
                  JOIN {user} u ON u.id = uid.userid
                 WHERE uid.fieldid = :fieldid
                   AND uid.data IS NOT NULL
                   AND uid.data <> ''
                   AND u.deleted = 0
              ORDER BY uid.data";

        $companies = $DB->get_records_sql($sql, ['fieldid' => $fieldid]);

        foreach ($companies as $company) {
            $result[$company->data] = $company->data;
        }

        return $result;
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Get the field ID for the company custom field.
     *
     * @param string $shortname Field shortname.
     * @return int|null Field ID or null.
     */
    private static function get_company_field_id(string $shortname = ''): ?int {
        global $DB;

        if (empty($shortname)) {
            $shortname = self::COMPANY_FIELD;
        }

        $field = $DB->get_record('user_info_field', ['shortname' => $shortname], 'id');
        return $field ? (int)$field->id : null;
    }

    /**
     * Get user's company from custom field.
     *
     * @param int $userid User ID.
     * @return string Company name or empty string.
     */
    private static function get_user_company(int $userid): string {
        global $DB;

        $fieldid = self::get_company_field_id();
        if (!$fieldid) {
            return '';
        }

        $data = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $fieldid], 'data');
        return $data ? $data->data : '';
    }

    /**
     * Get user's cohorts.
     *
     * @param int $userid User ID.
     * @return string Comma-separated list of cohort names.
     */
    private static function get_user_cohorts(int $userid): string {
        global $DB;

        $sql = "SELECT c.name
                  FROM {cohort} c
                  JOIN {cohort_members} cm ON cm.cohortid = c.id
                 WHERE cm.userid = :userid
              ORDER BY c.name";

        $cohorts = $DB->get_records_sql($sql, ['userid' => $userid]);

        if (empty($cohorts)) {
            return '';
        }

        $names = array_map(function($c) { return $c->name; }, $cohorts);
        return implode(', ', $names);
    }

    /**
     * Get user's course completion status.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID.
     * @return string Status: completed, in_progress, not_started.
     */
    private static function get_user_course_status(int $userid, int $courseid): string {
        global $DB;

        // Check course completion.
        $completion = $DB->get_record('course_completions', [
            'userid' => $userid,
            'course' => $courseid
        ]);

        if ($completion && !empty($completion->timecompleted)) {
            return 'completed';
        }

        // Check if user has any activity.
        $sql = "SELECT COUNT(*)
                  FROM {course_modules_completion} cmc
                  JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                 WHERE cmc.userid = :userid
                   AND cm.course = :courseid
                   AND cmc.completionstate > 0";

        $hasActivity = $DB->count_records_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);

        if ($hasActivity > 0) {
            return 'in_progress';
        }

        // Check if user has accessed the course.
        $lastaccess = $DB->get_record('user_lastaccess', [
            'userid' => $userid,
            'courseid' => $courseid
        ]);

        if ($lastaccess) {
            return 'in_progress';
        }

        return 'not_started';
    }

    /**
     * Get user's course progress percentage.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID.
     * @return float Progress percentage (0-100).
     */
    private static function get_user_course_progress(int $userid, int $courseid): float {
        global $DB;

        // Count total trackable activities.
        $sql_total = "SELECT COUNT(*)
                        FROM {course_modules} cm
                       WHERE cm.course = :courseid
                         AND cm.completion > 0
                         AND cm.visible = 1
                         AND cm.deletioninprogress = 0";

        $total = $DB->count_records_sql($sql_total, ['courseid' => $courseid]);

        if ($total == 0) {
            return 0;
        }

        // Count completed activities.
        $sql_completed = "SELECT COUNT(*)
                            FROM {course_modules_completion} cmc
                            JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                           WHERE cmc.userid = :userid
                             AND cm.course = :courseid
                             AND cmc.completionstate IN (1, 2)
                             AND cm.visible = 1
                             AND cm.deletioninprogress = 0";

        $completed = $DB->count_records_sql($sql_completed, ['userid' => $userid, 'courseid' => $courseid]);

        return round(($completed / $total) * 100, 1);
    }

    /**
     * Get user's final grade for a course.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID.
     * @return string|null Grade as percentage string or null.
     */
    private static function get_user_course_grade(int $userid, int $courseid): ?string {
        global $DB;

        $sql = "SELECT gi.grademax, gg.finalgrade
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gi.courseid = :courseid
                   AND gi.itemtype = 'course'
                   AND gg.userid = :userid";

        $grade = $DB->get_record_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);

        if (!$grade || $grade->finalgrade === null || $grade->grademax <= 0) {
            return null;
        }

        $percent = round(($grade->finalgrade / $grade->grademax) * 100, 1);
        return $percent . '%';
    }

    /**
     * Get user's course completion date.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID.
     * @return int|null Completion timestamp or null.
     */
    private static function get_user_course_completion_date(int $userid, int $courseid): ?int {
        global $DB;

        $completion = $DB->get_record('course_completions', [
            'userid' => $userid,
            'course' => $courseid
        ], 'timecompleted');

        return ($completion && $completion->timecompleted) ? (int)$completion->timecompleted : null;
    }

    /**
     * Get course module ID for a module instance.
     *
     * @param string $modname Module name.
     * @param int $instanceid Instance ID.
     * @return int|null Course module ID or null.
     */
    private static function get_course_module_id(string $modname, int $instanceid): ?int {
        global $DB;

        $module = $DB->get_record('modules', ['name' => $modname], 'id');
        if (!$module) {
            return null;
        }

        $cm = $DB->get_record('course_modules', [
            'module' => $module->id,
            'instance' => $instanceid
        ], 'id');

        return $cm ? (int)$cm->id : null;
    }
}
