<?php
/**
 * Helper class for EpicE Reports
 *
 * @package    local_epicereports
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports;

use context_course;
use context_system;
use core_user;
use stdClass;

defined('MOODLE_INTERNAL') || die();

// Incluir librerías necesarias.
global $CFG;
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Clase auxiliar para permitir propiedades dinámicas en PHP 8.2+
 */
#[\AllowDynamicProperties]
class user_row extends stdClass {
    public int $userid;
    public string $username;
    public string $idnumber;
    public string $fullname;
    public string $email;
    public string $primer_acceso;
    public string $ultimo_acceso;
    public string $grupos;
    public string $porcentaje_avance;
    public ?string $nota_final;
    public string $fecha_finalizacion;
    public string $estado_finalizacion;
    public string $certificado;
}

/**
 * Helper class para el plugin epicereports.
 */
class helper {

    /**
     * Obtiene estadísticas generales para el dashboard.
     *
     * @return array Array con estadísticas del sitio.
     */
    public static function get_dashboard_statistics(): array {
        global $DB;

        $stats = [];

        // 1. Total de usuarios activos (no suspendidos, no eliminados, excluyendo guests).
        $stats['active_users'] = $DB->count_records_select(
            'user',
            'deleted = 0 AND suspended = 0 AND id > 2'
        );

        // 2. Total de cursos visibles (excluyendo el curso sitio id=1).
        $stats['visible_courses'] = $DB->count_records_select(
            'course',
            'visible = 1 AND id > 1'
        );

        // 3. Total de cursos ocultos.
        $stats['hidden_courses'] = $DB->count_records_select(
            'course',
            'visible = 0 AND id > 1'
        );

        // 4. Total de matrículas activas.
        $sql = "SELECT COUNT(DISTINCT ue.id)
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.status = 0
                   AND e.status = 0";
        $stats['total_enrolments'] = (int)$DB->count_records_sql($sql);

        // 5. Porcentaje de satisfacción global (0–100).
        $stats['satisfaction_percent'] = self::get_platform_satisfaction_percent();

        return $stats;
    }

    /**
     * Obtiene los usuarios de un curso con su progreso.
     *
     * @param int $courseid ID del curso.
     * @return array Array de objetos con datos de progreso del usuario.
     */
    public static function get_course_users_with_progress(int $courseid): array {
        global $DB;

        $course = get_course($courseid);
        $context = context_course::instance($courseid);

        // Usuarios matriculados activos.
        $enrolled_users = get_enrolled_users(
            $context,
            '',
            0,
            'u.*',
            null,
            0,
            0,
            true
        );

        if (empty($enrolled_users)) {
            return [];
        }

        $users_with_progress = [];

        foreach ($enrolled_users as $user) {
            // Porcentaje de progreso usando la API correcta.
            $percentage = \core_completion\progress::get_course_progress_percentage($course, $user->id);
            if ($percentage === null) {
                $percentage = 0;
            }

            // Tiempo de finalización desde course_completions.
            $completion_time = self::get_user_completion_time($courseid, $user->id);

            // Nota final del curso.
            $final_grade = self::get_user_final_grade_raw($courseid, $user->id);

            $users_with_progress[] = [
                'userid' => $user->id,
                'username' => $user->username,
                'fullname' => fullname($user),
                'progress_percentage' => round($percentage, 2),
                'timecompleted' => $completion_time ? userdate($completion_time) : '-',
                'final_grade' => $final_grade !== null ? round($final_grade, 2) : '-'
            ];
        }

        return $users_with_progress;
    }

    /**
     * Obtiene una lista de cursos, aplicando filtros.
     *
     * @param string $name Filtro por nombre de curso.
     * @param int $category Filtro por ID de categoría.
     * @param int $visible Filtro por visibilidad (1=visible, 0=oculto, -1=todos).
     * @return array Array de objetos de curso.
     */
    public static function get_courses_list(string $name = '', int $category = 0, int $visible = -1): array {
        global $DB;

        $sql = "SELECT c.id, c.fullname, c.shortname, c.visible, c.category
                  FROM {course} c
                 WHERE c.id > 1";
        $params = [];

        if (!empty($name)) {
            $sql .= " AND " . $DB->sql_like('c.fullname', ':name', false);
            $params['name'] = '%' . $DB->sql_like_escape($name) . '%';
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

    /**
     * Obtiene una lista de categorías de cursos para un menú desplegable.
     *
     * @return array Array de categorías [id => name].
     */
    public static function get_course_categories(): array {
        global $DB;

        $sql = "SELECT id, name FROM {course_categories} ORDER BY sortorder, name ASC";
        $categories = $DB->get_records_sql($sql);

        $menu = [0 => get_string('all', 'core')];
        foreach ($categories as $category) {
            $menu[$category->id] = format_string($category->name);
        }

        return $menu;
    }

    /**
     * Obtiene datos detallados de un curso para la vista web.
     *
     * @param int $courseid ID del curso.
     * @return array Array con datos del curso y usuarios.
     */
    public static function get_course_detail_data(int $courseid): array {
        global $DB;

        $course = get_course($courseid);
        $context = context_course::instance($courseid);

        // Usuarios matriculados con capacidad de ver participantes.
        $enrolled_users = get_enrolled_users($context, 'moodle/course:viewparticipants');

        if (empty($enrolled_users)) {
            return [
                'course' => $course,
                'users' => []
            ];
        }

        $users_with_progress = [];

        foreach ($enrolled_users as $user) {
            // Progreso usando API correcta.
            $percentage = \core_completion\progress::get_course_progress_percentage($course, $user->id);
            if ($percentage === null) {
                $percentage = 0;
            }

            // Tiempo de finalización.
            $completion_time = self::get_user_completion_time($courseid, $user->id);

            // Nota final.
            $final_grade = self::get_user_final_grade_raw($courseid, $user->id);

            $users_with_progress[] = [
                'fullname' => fullname($user),
                'progress_percentage' => round($percentage, 2),
                'timecompleted' => $completion_time ? userdate($completion_time) : '-',
                'final_grade' => $final_grade !== null ? round($final_grade, 2) : '-'
            ];
        }

        return [
            'course' => $course,
            'users' => $users_with_progress
        ];
    }

    /**
     * Obtiene TODOS los datos de un curso de forma estructurada para exportar a Excel.
     *
     * @param int $courseid ID del curso.
     * @return array Array estructurado con toda la información necesaria.
     */
    public static function get_course_data_for_excel(int $courseid): array {
        global $DB;

        // Curso (lanza excepción si no existe).
        $course = get_course($courseid);

        // ------------------------------------------------------------------
        // 0) Usuarios matriculados activos en el curso
        // ------------------------------------------------------------------
        $sql = "SELECT DISTINCT u.*
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = :courseid
                   AND u.deleted = 0
                   AND u.suspended = 0
                   AND ue.status = 0
              ORDER BY u.lastname, u.firstname";

        $enrolled_users = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        if (empty($enrolled_users)) {
            return [
                'course' => $course,
                'modules' => [],
                'users' => []
            ];
        }

        // ------------------------------------------------------------------
        // 1) Lista de módulos en orden real del curso
        // ------------------------------------------------------------------
        $modinfo = get_fast_modinfo($courseid);
        $cms = $modinfo->get_cms();

        $modules_for_csv = [];

        foreach ($cms as $cm) {
            // Solo actividades visibles y no en borrado.
            if (empty($cm->visible) || !empty($cm->deletioninprogress)) {
                continue;
            }

            // Solo elementos con seguimiento de finalización activado.
            if (empty($cm->completion) || (int)$cm->completion === 0) {
                continue;
            }

            // Excluir etiquetas (labels).
            if ($cm->modname === 'label') {
                continue;
            }

            $modules_for_csv[] = [
                'id' => $cm->id,
                'name' => $cm->name,
                'modname' => $cm->modname,
                'instance' => $cm->instance,
            ];
        }

        // ------------------------------------------------------------------
        // 2) Datos por usuario
        // ------------------------------------------------------------------
        $users_data = [];

        foreach ($enrolled_users as $user) {
            $user_row = new user_row();

            // Datos básicos del usuario.
            $user_row->userid = (int)$user->id;
            $user_row->username = $user->username ?? '';
            $user_row->idnumber = $user->idnumber ?? '';
            $user_row->fullname = fullname($user);
            $user_row->email = $user->email ?? '';
            $user_row->primer_acceso = $user->firstaccess
                ? userdate($user->firstaccess, '%Y-%m-%d')
                : '';
            $user_row->ultimo_acceso = $user->lastaccess
                ? userdate($user->lastaccess, '%Y-%m-%d')
                : '';
            $user_row->grupos = self::get_user_groups_names($courseid, $user->id);

            // Contadores para calcular % de avance.
            $total_activities = 0;
            $completed_activities = 0;

            // Estado de cada actividad.
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

            // ------------------------------------------------------------------
            // Resumen del curso para el usuario
            // ------------------------------------------------------------------

            // 2.1) Porcentaje de avance local.
            $progress = 0;
            if ($total_activities > 0) {
                $progress = round(($completed_activities / $total_activities) * 100, 2);
            }
            $user_row->porcentaje_avance = $progress . '%';

            // 2.2) Nota final del curso (en % sobre la nota máxima).
            $user_row->nota_final = self::get_user_final_grade($courseid, $user->id);

            // 2.3) Estado y fecha de finalización del curso.
            $timecompleted = self::get_user_completion_time($courseid, $user->id);

            if ($timecompleted) {
                $user_row->fecha_finalizacion = userdate($timecompleted, '%Y-%m-%d');
                $user_row->estado_finalizacion = 'Completado';
            } else if ($progress >= 100) {
                $lastcmcompletion = self::get_user_last_activity_completion_time($courseid, $user->id);
                $user_row->fecha_finalizacion = $lastcmcompletion
                    ? userdate($lastcmcompletion, '%Y-%m-%d')
                    : '';
                $user_row->estado_finalizacion = 'Completado';
            } else {
                $user_row->fecha_finalizacion = '';
                $user_row->estado_finalizacion = 'En progreso';
            }

            // 2.4) Certificado.
            $user_row->certificado = self::get_user_certificate_status($courseid, $user->id);

            $users_data[] = $user_row;
        }

        return [
            'course' => $course,
            'modules' => array_values($modules_for_csv),
            'users' => $users_data
        ];
    }

    // =========================================================================
    // FUNCIONES AUXILIARES PRIVADAS
    // =========================================================================

    /**
     * Obtiene los nombres de los grupos a los que pertenece un usuario en un curso.
     *
     * @param int $courseid ID del curso.
     * @param int $userid ID del usuario.
     * @return string Nombres de los grupos separados por coma.
     */
    private static function get_user_groups_names(int $courseid, int $userid): string {
        global $DB;

        $sql = "SELECT g.name
                  FROM {groups} g
                  JOIN {groups_members} gm ON g.id = gm.groupid
                 WHERE gm.userid = :userid
                   AND g.courseid = :courseid
              ORDER BY g.name";

        $groups = $DB->get_fieldset_sql($sql, [
            'userid' => $userid,
            'courseid' => $courseid
        ]);

        return implode(', ', $groups);
    }

    /**
     * Calcula el progreso de un usuario en un curso (método alternativo).
     *
     * @param int $courseid ID del curso.
     * @param int $userid ID del usuario.
     * @return float Porcentaje de progreso (0-100).
     */
    private static function calculate_user_progress(int $courseid, int $userid): float {
        global $DB;

        $sql_total = "SELECT COUNT(*)
                        FROM {course_modules}
                       WHERE course = :courseid
                         AND completion > 0
                         AND visible = 1
                         AND deletioninprogress = 0";

        $sql_completed = "SELECT COUNT(*)
                            FROM {course_modules} cm
                            JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                           WHERE cm.course = :courseid
                             AND cmc.userid = :userid
                             AND cmc.completionstate IN (1, 2)
                             AND cm.visible = 1
                             AND cm.deletioninprogress = 0";

        $total = $DB->count_records_sql($sql_total, ['courseid' => $courseid]);
        $completed = $DB->count_records_sql($sql_completed, [
            'courseid' => $courseid,
            'userid' => $userid
        ]);

        return ($total > 0) ? round(($completed / $total) * 100, 2) : 0.0;
    }

    /**
     * Obtiene la fecha de finalización de un curso para un usuario.
     *
     * @param int $courseid ID del curso.
     * @param int $userid ID del usuario.
     * @return int Timestamp de la fecha de finalización, o 0 si no ha finalizado.
     */
    private static function get_user_completion_time(int $courseid, int $userid): int {
        global $DB;

        $record = $DB->get_record('course_completions', [
            'course' => $courseid,
            'userid' => $userid
        ], 'timecompleted');

        return ($record && $record->timecompleted) ? (int)$record->timecompleted : 0;
    }

    /**
     * Obtiene la calificación final raw de un usuario en un curso.
     *
     * @param int $courseid ID del curso.
     * @param int $userid ID del usuario.
     * @return float|null Nota final o null si no tiene.
     */
    private static function get_user_final_grade_raw(int $courseid, int $userid): ?float {
        global $DB;

        $sql = "SELECT gg.finalgrade
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gi.courseid = :courseid
                   AND gi.itemtype = 'course'
                   AND gg.userid = :userid";

        $record = $DB->get_record_sql($sql, [
            'courseid' => $courseid,
            'userid' => $userid
        ]);

        if (!$record || $record->finalgrade === null) {
            return null;
        }

        return (float)$record->finalgrade;
    }

    /**
     * Obtiene la calificación final de un usuario en un curso como porcentaje.
     *
     * @param int $courseid ID del curso.
     * @param int $userid ID del usuario.
     * @return string|null Ej: "85%" o null si no tiene nota.
     */
    private static function get_user_final_grade(int $courseid, int $userid): ?string {
        global $DB;

        $sql = "SELECT gi.grademax, gg.finalgrade
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gi.courseid = :courseid
                   AND gi.itemtype = 'course'
                   AND gg.userid = :userid";

        $record = $DB->get_record_sql($sql, [
            'courseid' => $courseid,
            'userid' => $userid
        ]);

        if (!$record || $record->finalgrade === null || $record->grademax <= 0) {
            return null;
        }

        $percent = round(($record->finalgrade / $record->grademax) * 100, 2);
        return $percent . '%';
    }

    /**
     * Obtiene el estado de certificado de un usuario en un curso.
     *
     * @param int $courseid
     * @param int $userid
     * @return string 'Emitido' o '-'.
     */
    private static function get_user_certificate_status(int $courseid, int $userid): string {
        global $DB;

        $dbman = $DB->get_manager();

        // 1) mod_certificate (plugin clásico).
        if ($dbman->table_exists('certificate_issues') && $dbman->table_exists('certificate')) {
            $sql = "SELECT 1
                      FROM {certificate_issues} ci
                      JOIN {certificate} c ON ci.certificateid = c.id
                     WHERE c.course = :courseid
                       AND ci.userid = :userid";

            if ($DB->record_exists_sql($sql, ['courseid' => $courseid, 'userid' => $userid])) {
                return 'Emitido';
            }
        }

        // 2) mod_simplecertificate.
        if ($dbman->table_exists('simplecertificate_issues') && $dbman->table_exists('simplecertificate')) {
            $sql = "SELECT 1
                      FROM {simplecertificate_issues} si
                      JOIN {simplecertificate} s ON si.certificateid = s.id
                     WHERE s.course = :courseid
                       AND si.userid = :userid";

            if ($DB->record_exists_sql($sql, ['courseid' => $courseid, 'userid' => $userid])) {
                return 'Emitido';
            }
        }

        // 3) mod_customcert (plugin popular en Moodle 4.x/5.x).
        if ($dbman->table_exists('customcert_issues') && $dbman->table_exists('customcert')) {
            $sql = "SELECT 1
                      FROM {customcert_issues} ci
                      JOIN {customcert} c ON ci.customcertid = c.id
                     WHERE c.course = :courseid
                       AND ci.userid = :userid";

            if ($DB->record_exists_sql($sql, ['courseid' => $courseid, 'userid' => $userid])) {
                return 'Emitido';
            }
        }

        return '-';
    }

    /**
     * Número de intentos de un SCORM para un usuario.
     *
     * @param int $scormid ID de la instancia del SCORM.
     * @param int $userid ID del usuario.
     * @return int
     */
    private static function get_scorm_attempts_count(int $scormid, int $userid): int {
        global $DB;

        $dbman = $DB->get_manager();

        // Verificar tabla scorm_attempt (Moodle 4.x+).
        if ($dbman->table_exists('scorm_attempt')) {
            $sql = "SELECT COUNT(*)
                      FROM {scorm_attempt}
                     WHERE scormid = :scormid
                       AND userid = :userid";

            $count = $DB->get_field_sql($sql, [
                'scormid' => $scormid,
                'userid' => $userid,
            ]);

            if ($count) {
                return (int)$count;
            }
        }

        // Fallback: contar intentos desde scorm_scoes_track.
        if ($dbman->table_exists('scorm_scoes_track')) {
            $sql = "SELECT COUNT(DISTINCT attempt)
                      FROM {scorm_scoes_track}
                     WHERE scormid = :scormid
                       AND userid = :userid";

            $count = $DB->get_field_sql($sql, [
                'scormid' => $scormid,
                'userid' => $userid,
            ]);

            return $count ? (int)$count : 0;
        }

        return 0;
    }

    /**
     * Calcula un % de avance en un SCORM para un usuario.
     *
     * @param int $scormid ID del SCORM.
     * @param int $userid ID del usuario.
     * @return float Porcentaje (0-100).
     */
    private static function get_scorm_progress_percent(int $scormid, int $userid): float {
        global $DB;

        $dbman = $DB->get_manager();

        // Método moderno (Moodle 4.x+).
        if (
            $dbman->table_exists('scorm_scoes') &&
            $dbman->table_exists('scorm_attempt') &&
            $dbman->table_exists('scorm_element') &&
            $dbman->table_exists('scorm_scoes_value')
        ) {
            // Total de SCOs lanzables.
            $totalsql = "SELECT COUNT(*)
                           FROM {scorm_scoes}
                          WHERE scorm = :scormid
                            AND launch <> ''";
            $total = $DB->get_field_sql($totalsql, ['scormid' => $scormid]);

            if (!$total) {
                return 0.0;
            }

            // SCOs completados.
            $completedsql = "SELECT COUNT(DISTINCT ssv.scoid)
                               FROM {scorm_scoes_value} ssv
                               JOIN {scorm_attempt} sa ON sa.id = ssv.attemptid
                               JOIN {scorm_element} se ON se.id = ssv.elementid
                              WHERE sa.scormid = :scormid
                                AND sa.userid = :userid
                                AND se.element = :element
                                AND ssv.value IN ('completed', 'passed', 'failed', 'browsed')";

            $completed = $DB->get_field_sql($completedsql, [
                'scormid' => $scormid,
                'userid' => $userid,
                'element' => 'cmi.core.lesson_status',
            ]);

            if (!$completed) {
                return 0.0;
            }

            return round(($completed / $total) * 100, 2);
        }

        // Fallback: método legacy con scorm_scoes_track.
        if ($dbman->table_exists('scorm_scoes') && $dbman->table_exists('scorm_scoes_track')) {
            $totalsql = "SELECT COUNT(*)
                           FROM {scorm_scoes}
                          WHERE scorm = :scormid
                            AND launch <> ''";
            $total = $DB->get_field_sql($totalsql, ['scormid' => $scormid]);

            if (!$total) {
                return 0.0;
            }

            $completedsql = "SELECT COUNT(DISTINCT scoid)
                               FROM {scorm_scoes_track}
                              WHERE scormid = :scormid
                                AND userid = :userid
                                AND element = 'cmi.core.lesson_status'
                                AND value IN ('completed', 'passed', 'failed', 'browsed')";

            $completed = $DB->get_field_sql($completedsql, [
                'scormid' => $scormid,
                'userid' => $userid,
            ]);

            if (!$completed) {
                return 0.0;
            }

            return round(($completed / $total) * 100, 2);
        }

        return 0.0;
    }

    /**
     * Puntuación del SCORM para un usuario, como porcentaje sobre la nota máxima.
     *
     * @param int $scormid ID de la instancia de SCORM.
     * @param int $userid ID del usuario.
     * @return string|null Ej: "85%" o null si no tiene nota.
     */
    private static function get_scorm_score_percent(int $scormid, int $userid): ?string {
        global $DB;

        $sql = "SELECT gi.grademax, gg.finalgrade
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gi.itemmodule = 'scorm'
                   AND gi.iteminstance = :scormid
                   AND gg.userid = :userid";

        $record = $DB->get_record_sql($sql, [
            'scormid' => $scormid,
            'userid' => $userid,
        ]);

        if (!$record || $record->finalgrade === null || $record->grademax <= 0) {
            return null;
        }

        $percent = round(($record->finalgrade / $record->grademax) * 100, 2);
        return $percent . '%';
    }

    /**
     * Número de intentos de un quiz para un usuario.
     *
     * @param int $quizid ID del quiz.
     * @param int $userid ID del usuario.
     * @return int
     */
    private static function get_quiz_attempts_count(int $quizid, int $userid): int {
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('quiz_attempts')) {
            return 0;
        }

        $sql = "SELECT COUNT(*)
                  FROM {quiz_attempts}
                 WHERE quiz = :quizid
                   AND userid = :userid
                   AND state = 'finished'";

        $count = $DB->get_field_sql($sql, [
            'quizid' => $quizid,
            'userid' => $userid,
        ]);

        return $count ? (int)$count : 0;
    }

    /**
     * Calificación más alta de un quiz para un usuario.
     *
     * @param int $quizid ID del quiz.
     * @param int $userid ID del usuario.
     * @return float|null Nota más alta, o null si no hay registros.
     */
    private static function get_quiz_highest_grade(int $quizid, int $userid): ?float {
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('quiz_grades')) {
            return null;
        }

        $sql = "SELECT grade
                  FROM {quiz_grades}
                 WHERE quiz = :quizid
                   AND userid = :userid";

        $grade = $DB->get_field_sql($sql, [
            'quizid' => $quizid,
            'userid' => $userid,
        ]);

        if ($grade === false || $grade === null) {
            return null;
        }

        return round((float)$grade, 2);
    }

    /**
     * Devuelve el detalle de un módulo para un usuario.
     *
     * @param int $courseid ID del curso.
     * @param int $userid ID del usuario.
     * @param object $module Objeto con info del course_module.
     * @return stdClass
     */
    private static function get_user_module_detail(int $courseid, int $userid, object $module): stdClass {
        global $DB;

        $detail = new stdClass();
        $detail->estado = '';
        $detail->intentos = null;
        $detail->puntuacion = null;
        $detail->nota = null;
        $detail->entrega = null;
        $detail->fechaentrega = null;

        // Detectar cmid, modname e instance.
        $cmid = (int)($module->id ?? $module->cmid ?? 0);
        $modname = $module->modname ?? '';
        $instanceid = (int)($module->instance ?? 0);

        if (!$cmid) {
            return $detail;
        }

        // 1) ¿Tiene seguimiento de finalización?
        $cm = $DB->get_record('course_modules', ['id' => $cmid], 'id, completion', IGNORE_MISSING);
        if (!$cm || (int)$cm->completion === 0) {
            return $detail;
        }

        // 2) Estado de finalización genérico.
        $completion = $DB->get_record('course_modules_completion', [
            'coursemoduleid' => $cmid,
            'userid' => $userid
        ]);

        if ($completion && (int)$completion->completionstate > 0) {
            $detail->estado = 'Finalizado';
        } else {
            $detail->estado = 'Pendiente';
        }

        $dbman = $DB->get_manager();

        // 3) Extras para SCORM.
        if ($modname === 'scorm' && $instanceid) {
            $detail->intentos = self::get_scorm_attempts_count($instanceid, $userid);
            $detail->puntuacion = self::get_scorm_score_percent($instanceid, $userid);

            $puntuacion_numero = 0;
            if ($detail->puntuacion !== null) {
                $puntuacion_numero = (float)str_replace('%', '', $detail->puntuacion);
            }

            if ($detail->intentos == 0) {
                $detail->estado = 'Sin iniciar';
            } else if ($detail->intentos > 0 && $puntuacion_numero < 100) {
                $detail->estado = 'En proceso';
            } else if ($detail->intentos > 0 && $puntuacion_numero >= 100) {
                $detail->estado = 'Finalizado';
            }
        }

        // 4) Extras para QUIZ.
        if ($modname === 'quiz' && $instanceid) {
            if ($dbman->table_exists('quiz_attempts')) {
                $detail->intentos = self::get_quiz_attempts_count($instanceid, $userid);
            }

            $nota = self::get_quiz_highest_grade($instanceid, $userid);
            if ($nota !== null) {
                $detail->nota = $nota;
            }
        }

        // 5) Extras para TAREAS (assign).
        if ($modname === 'assign' && $instanceid) {
            // ¿Entregó?
            if ($dbman->table_exists('assign_submission')) {
                $submission = $DB->get_record('assign_submission', [
                    'assignment' => $instanceid,
                    'userid' => $userid,
                    'latest' => 1
                ], '*', IGNORE_MISSING);

                if ($submission && $submission->status === 'submitted') {
                    $detail->entrega = 'Entregado';
                    $detail->fechaentrega = $submission->timemodified
                        ? userdate($submission->timemodified, '%Y-%m-%d %H:%M')
                        : '';
                } else {
                    $detail->entrega = 'No entregó';
                    $detail->fechaentrega = '';
                }
            }

            // Nota de la tarea como porcentaje.
            $sql = "SELECT gi.grademax, gg.finalgrade
                      FROM {grade_items} gi
                      JOIN {grade_grades} gg ON gg.itemid = gi.id
                     WHERE gi.courseid = :courseid
                       AND gi.itemmodule = 'assign'
                       AND gi.iteminstance = :assignid
                       AND gg.userid = :userid";

            $grade = $DB->get_record_sql($sql, [
                'courseid' => $courseid,
                'assignid' => $instanceid,
                'userid' => $userid
            ]);

            if ($grade && $grade->finalgrade !== null && $grade->grademax > 0) {
                $percent = round(($grade->finalgrade / $grade->grademax) * 100, 2);
                $detail->nota = $percent . '%';
            }
        }

        return $detail;
    }

    /**
     * Devuelve el timestamp de la última actividad completada en el curso por el usuario.
     *
     * @param int $courseid
     * @param int $userid
     * @return int Timestamp o 0.
     */
    private static function get_user_last_activity_completion_time(int $courseid, int $userid): int {
        global $DB;

        $sql = "SELECT MAX(cmc.timemodified)
                  FROM {course_modules_completion} cmc
                  JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                 WHERE cm.course = :courseid
                   AND cmc.userid = :userid
                   AND cmc.completionstate > 0";

        $time = $DB->get_field_sql($sql, [
            'courseid' => $courseid,
            'userid' => $userid
        ]);

        return $time ? (int)$time : 0;
    }

    /**
     * Porcentaje de satisfacción global a partir de actividades mod_feedback.
     *
     * @return float
     */
    private static function get_platform_satisfaction_percent(): float {
        global $DB;

        $dbman = $DB->get_manager();

        // Verificar que las tablas de feedback existen.
        if (
            !$dbman->table_exists('feedback_completed') ||
            !$dbman->table_exists('feedback') ||
            !$dbman->table_exists('feedback_item') ||
            !$dbman->table_exists('feedback_value')
        ) {
            return 0.0;
        }

        $pattern = '%' . $DB->sql_like_escape('satisfecho con el curso') . '%';

        $sql = "SELECT fv.value AS valor, COUNT(*) AS total
                  FROM {feedback_completed} fc
                  JOIN {feedback} f ON f.id = fc.feedback
                  JOIN {feedback_item} fi ON fi.feedback = f.id
                  JOIN {feedback_value} fv ON fv.completed = fc.id AND fv.item = fi.id
                 WHERE fi.typ = 'multichoice'
                   AND " . $DB->sql_like('fi.name', ':pregunta', false) . "
              GROUP BY fv.value";

        $records = $DB->get_records_sql($sql, ['pregunta' => $pattern]);

        if (empty($records)) {
            return 0.0;
        }

        // Detectar el máximo valor real de la escala.
        $maxraw = 0;
        foreach ($records as $rec) {
            $v = (int)$rec->valor;
            if ($v > $maxraw) {
                $maxraw = $v;
            }
        }

        if ($maxraw <= 0) {
            return 0.0;
        }

        // Si vemos un 8, asumimos escala hasta 7 (8 = "No aplica").
        $scalemax = $maxraw > 7 ? 7 : $maxraw;
        if ($scalemax < 2) {
            return 0.0;
        }

        // Acumular valores válidos.
        $weighted = 0.0;
        $answers = 0;

        foreach ($records as $rec) {
            $v = (int)$rec->valor;
            $count = (int)$rec->total;

            if ($v <= 0 || $v > $scalemax) {
                continue;
            }

            $weighted += $v * $count;
            $answers += $count;
        }

        if ($answers === 0) {
            return 0.0;
        }

        $maxpossible = $answers * $scalemax;
        $percent = ($weighted / $maxpossible) * 100.0;

        return round($percent, 2);
    }
}