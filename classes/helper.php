<?php
namespace local_epicereports;

use context_course;
use context_system;
use core_user;

defined('MOODLE_INTERNAL') || die();

class helper {

    public static function get_dashboard_statistics() {
        global $DB;
        $stats = [];
        $stats['active_users'] = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
        $stats['visible_courses'] = $DB->count_records('course', ['visible' => 1]);
        $stats['hidden_courses'] = $DB->count_records('course', ['visible' => 0]);
        $stats['total_enrolments'] = 0;
        $stats['satisfaction_percent'] = self::get_platform_satisfaction_percent();
        return $stats;
    }

    public static function get_course_users_with_progress($courseid) {
        global $DB;
        $course = get_course($courseid);
        $context = context_course::instance($courseid);
        $enrolled_users = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);

        $users_with_progress = [];
        foreach ($enrolled_users as $user) {
            $completion = new \completion_info($course);
            $percentage = \core_completion\progress::get_course_progress($courseid, $user->id);
            $completion_time = $completion->get_completion_time($user->id);
            $grade_info = grade_get_grades($courseid, 'mod', '', 0, $user->id);
            $final_grade = null;
            if (!empty($grade_info->items)) {
                $grade_item = reset($grade_info->items);
                if (isset($grade_item->grades[$user->id])) {
                    $final_grade = $grade_item->grades[$user->id]->grade;
                }
            }
            $users_with_progress[] = [
                'userid' => $user->id,
                'username' => $user->username,
                'fullname' => fullname($user),
                'progress_percentage' => $percentage,
                'timecompleted' => $completion_time ? userdate($completion_time) : '-',
                'final_grade' => $final_grade ? round($final_grade, 2) : '-'
            ];
        }
        return $users_with_progress;
    }

    public static function get_courses_list($name = '', $category = 0, $visible = -1) {
        global $DB;
        $sql = "SELECT c.id, c.fullname, c.shortname, c.visible FROM {course} c WHERE c.id > 1";
        $params = [];
        if (!empty($name)) {
            $sql .= " AND " . $DB->sql_like('c.fullname', ':name', false);
            $params['name'] = '%' . $name . '%';
        }
        if ($category > 0) {
            $sql .= " AND c.category = :category";
            $params['category'] = $category;
        }
        if ($visible !== -1) {
            $sql .= " AND c.visible = :visible";
            $params['visible'] = $visible;
        }
        $sql .= " ORDER BY c.fullname ASC";
        return $DB->get_records_sql($sql, $params);
    }

    public static function get_course_categories() {
        global $DB;
        $sql = "SELECT id, name FROM {course_categories} ORDER BY name ASC";
        $categories = $DB->get_records_sql($sql);
        $menu = [0 => 'Todas'];
        foreach ($categories as $category) {
            $menu[$category->id] = $category->name;
        }
        return $menu;
    }

    public static function get_course_detail_data($courseid) {
        global $DB;
        $course = get_course($courseid);
        $context = context_course::instance($courseid);
        $enrolled_users = get_enrolled_users($context, 'moodle/course:viewparticipants');

        $users_with_progress = [];
        foreach ($enrolled_users as $user) {
            $completion = \completion_info::create($course);
            $percentage = \core_completion\progress::get_course_progress($courseid, $user->id);
            $completion_time = $completion->get_completion_time($user->id);
            $grade_info = grade_get_grades($courseid, 'mod', '', 0, $user->id);
            $final_grade = null;
            if (!empty($grade_info->items)) {
                $grade_item = reset($grade_info->items);
                if (isset($grade_item->grades[$user->id])) {
                    $final_grade = $grade_item->grades[$user->id]->grade;
                }
            }
            $users_with_progress[] = [
                'fullname' => fullname($user),
                'progress_percentage' => $percentage,
                'timecompleted' => $completion_time ? userdate($completion_time) : '-',
                'final_grade' => $final_grade ? round($final_grade, 2) : '-'
            ];
        }
        return ['course' => $course, 'users' => $users_with_progress];
    }

    public static function get_course_data_for_excel($courseid) {
        global $DB;
        $course = get_course($courseid);

        $sql = "SELECT u.*
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = :courseid AND u.deleted = 0 AND u.suspended = 0 AND ue.status = 0
              ORDER BY u.lastname, u.firstname";
        $enrolled_users = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        if (empty($enrolled_users)) {
            return ['course' => $course, 'modules' => [], 'users' => []];
        }

        $modinfo = get_fast_modinfo($courseid);
        $cms = $modinfo->get_cms();
        $modules_for_csv = [];

        foreach ($cms as $cm) {
            if (empty($cm->visible) || !empty($cm->deletioninprogress)) continue;
            if (empty($cm->completion) || (int)$cm->completion === 0) continue;
            if ($cm->modname === 'label') continue;
            $modules_for_csv[] = [
                'id' => $cm->id,
                'name' => $cm->name,
                'modname' => $cm->modname,
                'instance' => $cm->instance,
            ];
        }

        $users_data = [];
        foreach ($enrolled_users as $user) {
            $user_row = new \stdClass();
            $user_row->userid = $user->id;
            $user_row->username = $user->username;
            $user_row->idnumber = $user->idnumber;
            $user_row->fullname = fullname($user);
            $user_row->email = $user->email;
            $user_row->primer_acceso = $user->firstaccess ? userdate($user->firstaccess, '%Y-%m-%d') : '';
            $user_row->ultimo_acceso = $user->lastaccess ? userdate($user->lastaccess, '%Y-%m-%d') : '';
            $user_row->grupos = self::get_user_groups_names($courseid, $user->id);

            $total_activities = 0;
            $completed_activities = 0;

            foreach ($modules_for_csv as $moddata) {
                $module = (object)$moddata;
                $module_key = 'mod_' . $module->id;
                $detail = self::get_user_module_detail($courseid, $user->id, $module);
                $user_row->$module_key = $detail;
                if (!empty($detail->estado)) {
                    $total_activities++;
                    if ($detail->estado === 'Finalizado') {
                        $completed_activities++;
                    }
                }
            }

            $progress = ($total_activities > 0) ? round(($completed_activities / $total_activities) * 100, 2) : 0;
            $user_row->porcentaje_avance = $progress . '%';
            $user_row->nota_final = self::get_user_final_grade($courseid, $user->id);

            $timecompleted = self::get_user_completion_time($courseid, $user->id);
            if ($timecompleted) {
                $user_row->fecha_finalizacion = userdate($timecompleted, '%Y-%m-%d');
                $user_row->estado_finalizacion = 'Completado';
            } else if ($progress >= 100) {
                $lastcmcompletion = self::get_user_last_activity_completion_time($courseid, $user->id);
                $user_row->fecha_finalizacion = $lastcmcompletion ? userdate($lastcmcompletion, '%Y-%m-%d') : '';
                $user_row->estado_finalizacion = 'Completado';
            } else {
                $user_row->fecha_finalizacion = '';
                $user_row->estado_finalizacion = 'En progreso';
            }

            $user_row->certificado = self::get_user_certificate_status($courseid, $user->id);
            $users_data[] = $user_row;
        }

        return ['course' => $course, 'modules' => array_values($modules_for_csv), 'users' => $users_data];
    }

    private static function get_user_groups_names($courseid, $userid) {
        global $DB;
        $sql = "SELECT g.name FROM {groups} g JOIN {groups_members} gm ON g.id = gm.groupid WHERE gm.userid = :userid AND g.courseid = :courseid";
        $groups = $DB->get_fieldset_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
        return implode(', ', $groups);
    }

    private static function calculate_user_progress($courseid, $userid) {
        global $DB;
        $sql_total = "SELECT COUNT(*) FROM {course_modules} WHERE course = :courseid AND completion > 0 AND visible = 1";
        $sql_completed = "SELECT COUNT(*) FROM {course_modules} cm JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid WHERE cm.course = :courseid AND cmc.userid = :userid AND cmc.completionstate IN (1,2)";
        $total = $DB->count_records_sql($sql_total, ['courseid' => $courseid]);
        $completed = $DB->count_records_sql($sql_completed, ['courseid' => $courseid, 'userid' => $userid]);
        return ($total > 0) ? round(($completed / $total) * 100, 2) : 0;
    }

    private static function get_user_completion_time($courseid, $userid) {
        global $DB;
        $sql = "SELECT timecompleted FROM {course_completions} WHERE course = :courseid AND userid = :userid";
        $record = $DB->get_record_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);
        return $record ? $record->timecompleted : 0;
    }

    private static function get_user_final_grade($courseid, $userid) {
        global $DB;
        $sql = "SELECT gi.grademax, gg.finalgrade FROM {grade_items} gi JOIN {grade_grades} gg ON gg.itemid = gi.id WHERE gi.courseid = :courseid AND gi.itemtype = 'course' AND gg.userid = :userid";
        $record = $DB->get_record_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);
        if (!$record || $record->finalgrade === null || $record->grademax <= 0) return null;
        $percent = round(($record->finalgrade / $record->grademax) * 100, 2);
        return $percent . '%';
    }

    private static function get_user_certificate_status($courseid, $userid) {
        global $DB;
        $dbman = $DB->get_manager();
        if ($dbman->table_exists('certificate_issues') && $dbman->table_exists('certificate')) {
            $sql = "SELECT 1 FROM {certificate_issues} ci JOIN {certificate} c ON ci.certificateid = c.id WHERE c.course = :courseid AND ci.userid = :userid";
            if ($DB->record_exists_sql($sql, ['courseid' => $courseid, 'userid' => $userid])) return 'Emitido';
        }
        if ($dbman->table_exists('simplecertificate_issues') && $dbman->table_exists('simplecertificate')) {
            $sql = "SELECT 1 FROM {simplecertificate_issues} si JOIN {simplecertificate} s ON si.certificateid = s.id WHERE s.course = :courseid AND si.userid = :userid";
            if ($DB->record_exists_sql($sql, ['courseid' => $courseid, 'userid' => $userid])) return 'Emitido';
        }
        return '-';
    }

    private static function get_scorm_attempts_count($scormid, $userid) {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('scorm_attempt')) return 0;
        $sql = "SELECT COUNT(*) FROM {scorm_attempt} WHERE scormid = :scormid AND userid = :userid";
        $count = $DB->get_field_sql($sql, ['scormid' => $scormid, 'userid' => $userid]);
        return $count ? (int)$count : 0;
    }

    private static function get_scorm_progress_percent($scormid, $userid) {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('scorm_scoes') || !$dbman->table_exists('scorm_attempt') || !$dbman->table_exists('scorm_element') || !$dbman->table_exists('scorm_scoes_value')) return 0;
        $totalsql = "SELECT COUNT(*) FROM {scorm_scoes} WHERE scorm = :scormid AND launch <> ''";
        $total = $DB->get_field_sql($totalsql, ['scormid' => $scormid]);
        if (!$total) return 0;
        $completedsql = "SELECT COUNT(DISTINCT ssv.scoid) FROM {scorm_scoes_value} ssv JOIN {scorm_attempt} sa ON sa.id = ssv.attemptid JOIN {scorm_element} se ON se.id = ssv.elementid WHERE sa.scormid = :scormid AND sa.userid = :userid AND se.element = :element AND ssv.value IN ('completed','passed','failed','browsed')";
        $completed = $DB->get_field_sql($completedsql, ['scormid' => $scormid, 'userid' => $userid, 'element' => 'cmi.core.lesson_status']);
        if (!$completed) return 0;
        return round(($completed / $total) * 100, 2);
    }

    private static function get_scorm_score_percent($scormid, $userid) {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('grade_items') || !$dbman->table_exists('grade_grades')) return null;
        $sql = "SELECT gi.grademax, gg.finalgrade FROM {grade_items} gi JOIN {grade_grades} gg ON gg.itemid = gi.id WHERE gi.itemmodule = 'scorm' AND gi.iteminstance = :scormid AND gg.userid = :userid";
        $record = $DB->get_record_sql($sql, ['scormid' => $scormid, 'userid' => $userid]);
        if (!$record || $record->finalgrade === null || $record->grademax <= 0) return null;
        $percent = round(($record->finalgrade / $record->grademax) * 100, 2);
        return $percent . '%';
    }

    private static function get_quiz_highest_grade($quizid, $userid) {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('quiz_grades')) return null;
        $sql = "SELECT MAX(grade) FROM {quiz_grades} WHERE quiz = :quizid AND userid = :userid";
        $grade = $DB->get_field_sql($sql, ['quizid' => $quizid, 'userid' => $userid]);
        if ($grade === false || $grade === null) return null;
        return round((float)$grade, 2);
    }

    private static function get_user_module_detail($courseid, $userid, $module) {
        global $DB;
        $detail = new \stdClass();
        $detail->estado = '';
        $detail->intentos = null;
        $detail->puntuacion = null;
        $detail->nota = null;
        $detail->entrega = null;
        $detail->fechaentrega = null;

        $cmid = 0;
        $modname = '';
        $instanceid = 0;

        if (is_object($module)) {
            $cmid = !empty($module->id) ? (int)$module->id : (!empty($module->cmid) ? (int)$module->cmid : 0);
            $modname = !empty($module->modname) ? $module->modname : '';
            $instanceid = !empty($module->instance) ? (int)$module->instance : 0;
        } else if (is_array($module)) {
            $cmid = !empty($module['id']) ? (int)$module['id'] : (!empty($module['cmid']) ? (int)$module['cmid'] : 0);
            $modname = !empty($module['modname']) ? $module['modname'] : '';
            $instanceid = !empty($module['instance']) ? (int)$module['instance'] : 0;
        }

        if (!$cmid) return $detail;

        $cm = $DB->get_record('course_modules', ['id' => $cmid], 'id, completion', IGNORE_MISSING);
        if (!$cm || (int)$cm->completion === 0) return $detail;

        $completion = $DB->get_record('course_modules_completion', ['coursemoduleid' => $cmid, 'userid' => $userid]);
        $detail->estado = ($completion && (int)$completion->completionstate > 0) ? 'Finalizado' : 'Pendiente';

        $dbman = $DB->get_manager();

        if ($modname === 'scorm' && $instanceid) {
            $detail->intentos = self::get_scorm_attempts_count($instanceid, $userid);
            $detail->puntuacion = self::get_scorm_score_percent($instanceid, $userid);
            $puntuacion_numero = (float)str_replace('%', '', $detail->puntuacion);
            if ($detail->intentos == 0) $detail->estado = 'Sin iniciar';
            else if ($detail->intentos > 0 && $puntuacion_numero < 100) $detail->estado = 'En proceso';
            else if ($detail->intentos > 0 && $puntuacion_numero >= 100) $detail->estado = 'Finalizado';
        }

        if ($modname === 'quiz' && $instanceid) {
            if ($dbman->table_exists('quiz_attempts')) {
                $detail->intentos = self::get_quiz_attempts_count($instanceid, $userid);
            }
            $nota = self::get_quiz_highest_grade($instanceid, $userid);
            if ($nota !== null) $detail->nota = $nota;
        }

        if ($modname === 'assign' && $instanceid) {
            if ($dbman->table_exists('assign_submission')) {
                $submission = $DB->get_record('assign_submission', ['assignment' => $instanceid, 'userid' => $userid], '*', IGNORE_MISSING);
                if ($submission && $submission->status === 'submitted') {
                    $detail->entrega = 'Entregado';
                    $detail->fechaentrega = $submission->timemodified ? userdate($submission->timemodified) : '';
                } else {
                    $detail->entrega = 'No entregó';
                    $detail->fechaentrega = '';
                }
            }
            if ($dbman->table_exists('grade_items') && $dbman->table_exists('grade_grades')) {
                $sql = "SELECT gi.grademax, gg.finalgrade FROM {grade_items} gi JOIN {grade_grades} gg ON gg.itemid = gi.id WHERE gi.courseid = :courseid AND gi.itemmodule = 'assign' AND gi.iteminstance = :assignid AND gg.userid = :userid";
                $grade = $DB->get_record_sql($sql, ['courseid' => $courseid, 'assignid' => $instanceid, 'userid' => $userid]);
                if ($grade && $grade->finalgrade !== null && $grade->grademax > 0) {
                    $percent = round(($grade->finalgrade / $grade->grademax) * 100, 2);
                    $detail->nota = $percent . '%';
                }
            }
        }

        return $detail;
    }

    private static function get_quiz_attempts_count($quizid, $userid) {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('quiz_attempts')) return 0;
        $sql = "SELECT COUNT(*) FROM {quiz_attempts} WHERE quiz = :quizid AND userid = :userid";
        $count = $DB->get_field_sql($sql, ['quizid' => $quizid, 'userid' => $userid]);
        return $count ? (int)$count : 0;
    }

    private static function get_user_last_activity_completion_time($courseid, $userid) {
        global $DB;
        $sql = "SELECT MAX(cmc.timemodified) FROM {course_modules_completion} cmc JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid WHERE cm.course = :courseid AND cmc.userid = :userid AND cmc.completionstate > 0";
        $time = $DB->get_field_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);
        return $time ? (int)$time : 0;
    }

    private static function get_platform_satisfaction_percent(): float {
        global $DB;
        $pattern = '%' . $DB->sql_like_escape('satisfecho con el curso') . '%';
        $sql = "SELECT fv.value AS valor, COUNT(*) AS total FROM {feedback_completed} fc JOIN {feedback} f ON f.id = fc.feedback JOIN {feedback_item} fi ON fi.feedback = f.id JOIN {feedback_value} fv ON fv.completed = fc.id AND fv.item = fi.id WHERE fi.typ = 'multichoice' AND " . $DB->sql_like('fi.name', ':pregunta', false) . " GROUP BY fv.value";
        $records = $DB->get_records_sql($sql, ['pregunta' => $pattern]);
        if (empty($records)) return 0.0;

        $maxraw = 0;
        foreach ($records as $rec) {
            $v = (int)$rec->valor;
            if ($v > $maxraw) $maxraw = $v;
        }
        if ($maxraw <= 0) return 0.0;

        $scalemax = $maxraw > 7 ? 7 : $maxraw;
        if ($scalemax < 2) return 0.0;

        $weighted = 0.0;
        $answers = 0;
        foreach ($records as $rec) {
            $v = (int)$rec->valor;
            $count = (int)$rec->total;
            if ($v <= 0 || $v > $scalemax) continue;
            $weighted += $v * $count;
            $answers += $count;
        }
        if ($answers === 0) return 0.0;

        $maxpossible = $answers * $scalemax;
        $percent = ($weighted / $maxpossible) * 100.0;
        return round($percent, 2);
    }
}
