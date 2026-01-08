<?php
namespace local_epicereports;

use context_course;
use context_system;
use core_user;
// Las clases completion_info y progress están obsoletas o no funcionan en tu entorno,
// por lo que hemos reemplazado su funcionalidad con consultas SQL directas y seguras.

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Obtiene estadísticas generales para el dashboard.
     *
     * @return array Array con estadísticas del sitio.
     */
    public static function get_dashboard_statistics() {
        global $DB;

        $stats = [];

        // 1. Total de usuarios activos (no suspendidos, no eliminados).
        $stats['active_users'] = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);

        // 2. Total de cursos visibles.
        $stats['visible_courses'] = $DB->count_records('course', ['visible' => 1]);

        // 3. Total de cursos ocultos.
        $stats['hidden_courses'] = $DB->count_records('course', ['visible' => 0]);

        // 4. Total de usuarios matriculados (placeholder por ahora).
        $stats['total_enrolments'] = 0;
        
        // 5. NUEVO: porcentaje de satisfacción global (0–100).
        $stats['satisfaction_percent'] = self::get_platform_satisfaction_percent();

        return $stats;
    }

    /**
     * Obtiene los usuarios de un curso con su progreso.
     * Esta función reemplaza a tu 'mdl_get_cursos_proges_by_porcentajes_view'.
     *
     * @param int $courseid ID del curso.
     * @return array Array de objetos con datos de progreso del usuario.
     */
    public static function get_course_users_with_progress($courseid) {
        global $DB;

        $course = get_course($courseid);
        $context = context_course::instance($courseid);
        // Trae TODOS los usuarios matriculados en el curso, solo activos.
        $enrolled_users = get_enrolled_users(
            $context,
            '',          // sin filtrar por capability
            0,           // cualquier grupo
            'u.*',       // todos los campos de usuario
            null,        // sin orden especial
            0,           // sin límite desde
            0,           // sin límite cantidad
            true         // solo matrículas activas
        );


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

    /**
     * Obtiene una lista de cursos, aplicando filtros.
     *
     * @param string $name Filtro por nombre de curso.
     * @param int $category Filtro por ID de categoría.
     * @param int $visible Filtro por visibilidad (1=visible, 0=oculto, -1=todos).
     * @return array Array de objetos de curso.
     */
    public static function get_courses_list($name = '', $category = 0, $visible = -1) {
        global $DB;

        $sql = "SELECT c.id, c.fullname, c.shortname, c.visible 
                 FROM {course} c 
                 WHERE c.id > 1";
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

    /**
     * Obtiene una lista de categorías de cursos para un menú desplegable.
     *
     * @return array Array de categorías [id => name].
     */
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

    /**
     * Obtiene datos detallados de un curso para la vista web.
     * Esta función es más simple y se usa para mostrar en la página.
     *
     * @param int $courseid ID del curso.
     * @return array Array con datos del curso y usuarios.
     */
    public static function get_course_detail_data($courseid) {
        global $DB;
        $course = get_course($courseid);
        $context = context_course::instance($courseid);
        $enrolled_users = get_enrolled_users($context, 'moodle/course:viewparticipants');

        $users_with_progress = [];
        foreach ($enrolled_users as $user) {
            // Obtenemos la información de progreso del usuario.
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

        return [
            'course' => $course,
            'users' => $users_with_progress
        ];
    }

   /**
     * Obtiene TODOS los datos de un curso de forma estructurada para exportar a Excel.
     * Esta es la función más compleja y eficiente para la exportación.
     *
     * @param int $courseid ID del curso.
     * @return array Array estructurado con toda la información necesaria.
     */
    public static function get_course_data_for_excel($courseid) {
        global $DB;
    
        // Curso (lanza excepción si no existe).
        $course = get_course($courseid);
    
        // ------------------------------------------------------------------
        // 0) Usuarios matriculados activos en el curso (sin depender de roles)
        // ------------------------------------------------------------------
        $sql = "SELECT u.*
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e           ON e.id = ue.enrolid
                 WHERE e.courseid   = :courseid
                   AND u.deleted    = 0
                   AND u.suspended  = 0
                   AND ue.status    = 0
              ORDER BY u.lastname, u.firstname";
    
        $enrolled_users = $DB->get_records_sql($sql, ['courseid' => $courseid]);
    
        if (empty($enrolled_users)) {
            return [
                'course'  => $course,
                'modules' => [],
                'users'   => []
            ];
        }
    
        // ------------------------------------------------------------------
        // 1) Lista de módulos en orden real del curso
        //     - Solo visibles
        //     - Solo con finalización activada
        //     - Excluimos labels (títulos decorativos)
        // ------------------------------------------------------------------
        $modinfo = get_fast_modinfo($courseid);
        $cms     = $modinfo->get_cms();
    
        $modules_for_csv = [];
    
        foreach ($cms as $cm) {
            // Solo actividades/recursos visibles (ojito abierto) y no en borrado.
            if (empty($cm->visible) || !empty($cm->deletioninprogress)) {
                continue;
            }
    
            // Solo elementos con seguimiento de finalización activado.
            if (empty($cm->completion) || (int)$cm->completion === 0) {
                continue;
            }
    
            // Excluir etiquetas (labels) que suelen ser títulos de sección.
            if ($cm->modname === 'label') {
                continue;
            }
    
            $modules_for_csv[] = [
                'id'       => $cm->id,        // course_modules.id (cmid)
                'name'     => $cm->name,
                'modname'  => $cm->modname,
                'instance' => $cm->instance,
            ];
        }
    
        // ------------------------------------------------------------------
        // 2) Datos por usuario
        // ------------------------------------------------------------------
        $users_data = [];
    
        foreach ($enrolled_users as $user) {
            $user_row = new \stdClass();
    
            // Datos básicos del usuario.
            $user_row->userid        = $user->id;
            $user_row->username      = $user->username;
            $user_row->idnumber      = $user->idnumber;
            $user_row->fullname      = fullname($user);
            $user_row->email         = $user->email;
            $user_row->primer_acceso = $user->firstaccess
                ? userdate($user->firstaccess, '%Y-%m-%d')
                : '';
            $user_row->ultimo_acceso = $user->lastaccess
                ? userdate($user->lastaccess, '%Y-%m-%d')
                : '';
            $user_row->grupos        = self::get_user_groups_names($courseid, $user->id);
    
            // Para calcular % de avance (solo actividades con seguimiento de finalización).
            $total_activities     = 0;
            $completed_activities = 0;
    
            // Estado de cada actividad.
            foreach ($modules_for_csv as $moddata) {
                $module     = (object)$moddata;
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
            if ($total_activities > 0) {
                $progress = round(($completed_activities / $total_activities) * 100, 2);
            } else {
                $progress = 0;
            }
            $user_row->porcentaje_avance = $progress . '%';
    
            // 2.2) Nota final del curso (en % sobre la nota máxima del curso).
            //      (get_user_final_grade ya devuelve algo como "85%")
            $user_row->nota_final = self::get_user_final_grade($courseid, $user->id);
    
            // 2.3) Estado y fecha de finalización del curso.
            //      1) Intentamos usar la tabla oficial {course_completions}.
            $timecompleted = self::get_user_completion_time($courseid, $user->id);
    
            if ($timecompleted) {
                // Curso marcado como completado oficialmente en Moodle.
                $user_row->fecha_finalizacion  = userdate($timecompleted, '%Y-%m-%d');
                $user_row->estado_finalizacion = 'Completado';
    
            } else if ($progress >= 100) {
                // No hay registro en course_completions, pero localmente tiene 100% de avance.
                // Tomamos como fecha de finalización la última actividad completada.
                $lastcmcompletion = self::get_user_last_activity_completion_time($courseid, $user->id);
    
                $user_row->fecha_finalizacion  = $lastcmcompletion
                    ? userdate($lastcmcompletion, '%Y-%m-%d')
                    : '';
                $user_row->estado_finalizacion = 'Completado';
    
            } else {
                // Todavía en progreso.
                $user_row->fecha_finalizacion  = '';
                $user_row->estado_finalizacion = 'En progreso';
            }
    
            // 2.4) Certificado (certificate o simplecertificate, según lo que exista).
            $user_row->certificado = self::get_user_certificate_status($courseid, $user->id);
    
            $users_data[] = $user_row;
        }
    
        return [
            'course'  => $course,
            'modules' => array_values($modules_for_csv),
            'users'   => $users_data
        ];
    }


    // --- Funciones auxiliares para mantener el código limpio ---

    /**
     * Obtiene los nombres de los grupos a los que pertenece un usuario en un curso.
     *
     * @param int $courseid ID del curso.
     * @param int $userid ID del usuario.
     * @return string Nombres de los grupos separados por coma.
     */
    private static function get_user_groups_names($courseid, $userid) {
        global $DB;
        $sql = "SELECT g.name
                 FROM {groups} g
                 JOIN {groups_members} gm ON g.id = gm.groupid
                WHERE gm.userid = :userid AND g.courseid = :courseid";
        $groups = $DB->get_fieldset_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
        return implode(', ', $groups);
    }

    /**
     * Calcula el progreso de un usuario en un curso.
     *
     * @param int $courseid ID del curso.
     * @param int $userid ID del usuario.
     * @return int Porcentaje de progreso (0-100).
     */
    private static function calculate_user_progress($courseid, $userid) {
        $sql_total = "SELECT COUNT(*) FROM {course_modules} WHERE course = :courseid AND completion > 0 AND visible = 1";
        $sql_completed = "SELECT COUNT(*) FROM {course_modules} cm
                          JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                         WHERE cm.course = :courseid AND cmc.userid = :userid AND cmc.completionstate IN (1,2)";
        
        global $DB;
        $total = $DB->count_records_sql($sql_total, ['courseid' => $courseid]);
        $completed = $DB->count_records_sql($sql_completed, ['courseid' => $courseid, 'userid' => $userid]);
        
        return ($total > 0) ? round(($completed / $total) * 100, 2) : 0;
    }

    /**
     * Obtiene la fecha de finalización de un curso para un usuario.
     *
     * @param int $courseid ID del curso.
     * @param int $userid ID del usuario.
     * @return int Timestamp de la fecha de finalización, o 0 si no ha finalizado.
     */
    private static function get_user_completion_time($courseid, $userid) {
        $sql = "SELECT timecompleted FROM {course_completions} WHERE course = :courseid AND userid = :userid";
        global $DB;
        $record = $DB->get_record_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);
        return $record ? $record->timecompleted : 0;
    }

    /**
     * Obtiene la calificación final de un usuario en un curso como porcentaje (0–100%).
     *
     * @param int $courseid ID del curso.
     * @param int $userid   ID del usuario.
     * @return string|null  Ej: "85%" o null si no tiene nota.
     */
    private static function get_user_final_grade($courseid, $userid) {
        global $DB;
    
        $sql = "SELECT gi.grademax, gg.finalgrade
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gi.courseid = :courseid
                   AND gi.itemtype = 'course'
                   AND gg.userid   = :userid";
    
        $record = $DB->get_record_sql($sql, [
            'courseid' => $courseid,
            'userid'   => $userid
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
     * Soporta:
     * - mod_certificate   → tablas certificate / certificate_issues
     * - mod_simplecertificate → tablas simplecertificate / simplecertificate_issues
     *
     * @param int $courseid
     * @param int $userid
     * @return string 'Emitido' si tiene al menos un diploma en el curso, '-' en caso contrario.
     */
    private static function get_user_certificate_status($courseid, $userid) {
        global $DB;
    
        $dbman = $DB->get_manager();
    
        // 1) Primero revisamos mod_certificate (plugin clásico).
        if ($dbman->table_exists('certificate_issues') && $dbman->table_exists('certificate')) {
            $sql = "SELECT 1
                      FROM {certificate_issues} ci
                      JOIN {certificate}       c  ON ci.certificateid = c.id
                     WHERE c.course = :courseid
                       AND ci.userid = :userid";
            if ($DB->record_exists_sql($sql, ['courseid' => $courseid, 'userid' => $userid])) {
                return 'Emitido';
            }
        }
    
        // 2) Luego revisamos mod_simplecertificate (plugin de diplomas simplecertificate).
        if ($dbman->table_exists('simplecertificate_issues') && $dbman->table_exists('simplecertificate')) {
            $sql = "SELECT 1
                      FROM {simplecertificate_issues} si
                      JOIN {simplecertificate}       s  ON si.certificateid = s.id
                     WHERE s.course = :courseid
                       AND si.userid = :userid";
            if ($DB->record_exists_sql($sql, ['courseid' => $courseid, 'userid' => $userid])) {
                return 'Emitido';
            }
        }
    
        // 3) Si no encontramos nada en ninguno de los plugins soportados:
        return '-';
    }



    /**
     * Número de intentos de un SCORM para un usuario (según tabla scorm_attempt).
     *
     * @param int $scormid ID de la instancia del SCORM (tabla scorm.id).
     * @param int $userid  ID del usuario.
     * @return int
     */
    private static function get_scorm_attempts_count($scormid, $userid) {
        global $DB;
    
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('scorm_attempt')) {
            // El módulo SCORM no está instalado completamente.
            return 0;
        }
    
        $sql = "SELECT COUNT(*)
                  FROM {scorm_attempt}
                 WHERE scormid = :scormid
                   AND userid  = :userid";
    
        $count = $DB->get_field_sql($sql, [
            'scormid' => $scormid,
            'userid'  => $userid,
        ]);
    
        return $count ? (int)$count : 0;
    }


    /**
     * Calcula un % de avance en un SCORM para un usuario,
     * usando las tablas scorm_scoes, scorm_attempt, scorm_element y scorm_scoes_value.
     *
     * @param int $scormid ID del SCORM (tabla scorm.id).
     * @param int $userid  ID del usuario.
     * @return float Porcentaje (0-100).
     */
    private static function get_scorm_progress_percent($scormid, $userid) {
        global $DB;
    
        $dbman = $DB->get_manager();
    
        // Nos aseguramos de que todas las tablas existan.
        if (
            !$dbman->table_exists('scorm_scoes') ||
            !$dbman->table_exists('scorm_attempt') ||
            !$dbman->table_exists('scorm_element') ||
            !$dbman->table_exists('scorm_scoes_value')
        ) {
            return 0;
        }
    
        // 1) Total de SCOs lanzables de este SCORM.
        $totalsql = "SELECT COUNT(*)
                       FROM {scorm_scoes}
                      WHERE scorm = :scormid
                        AND launch <> ''";
        $total = $DB->get_field_sql($totalsql, ['scormid' => $scormid]);
    
        if (!$total) {
            return 0;
        }
    
        // 2) SCOs que tienen estado de lección "completado/passed/failed/browsed"
        // para este usuario y este scorm, en cualquiera de sus intentos.
        $completedsql = "SELECT COUNT(DISTINCT ssv.scoid)
                           FROM {scorm_scoes_value} ssv
                           JOIN {scorm_attempt} sa ON sa.id = ssv.attemptid
                           JOIN {scorm_element} se ON se.id = ssv.elementid
                          WHERE sa.scormid = :scormid
                            AND sa.userid  = :userid
                            AND se.element = :element
                            AND ssv.value IN ('completed','passed','failed','browsed')";
    
        $completed = $DB->get_field_sql($completedsql, [
            'scormid' => $scormid,
            'userid'  => $userid,
            'element' => 'cmi.core.lesson_status',
        ]);
    
        if (!$completed) {
            return 0;
        }
    
        return round(($completed / $total) * 100, 2);
    }

    /**
     * Puntuación del SCORM para un usuario, como porcentaje sobre la nota máxima.
     *
     * Usa el libro de calificaciones (grade_items + grade_grades) para el módulo scorm.
     *
     * @param int $scormid ID de la instancia de SCORM (tabla scorm.id).
     * @param int $userid  ID del usuario.
     * @return string|null Ej: "85%" o null si no tiene nota.
     */
    private static function get_scorm_score_percent($scormid, $userid) {
        global $DB;
    
        $dbman = $DB->get_manager();
        if (
            !$dbman->table_exists('grade_items') ||
            !$dbman->table_exists('grade_grades')
        ) {
            return null;
        }
    
        $sql = "SELECT gi.grademax, gg.finalgrade
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gi.itemmodule   = 'scorm'
                   AND gi.iteminstance = :scormid
                   AND gg.userid       = :userid";
    
        $record = $DB->get_record_sql($sql, [
            'scormid' => $scormid,
            'userid'  => $userid,
        ]);
    
        if (!$record || $record->finalgrade === null || $record->grademax <= 0) {
            return null;
        }
    
        $percent = round(($record->finalgrade / $record->grademax) * 100, 2);
        return $percent . '%';
    }

    /**
     * Calificación más alta de un quiz para un usuario (según tabla quiz_grades).
     *
     * @param int $quizid ID del quiz.
     * @param int $userid ID del usuario.
     * @return float|null Nota más alta, o null si no hay registros.
     */
    private static function get_quiz_highest_grade($quizid, $userid) {
        global $DB;
    
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('quiz_grades')) {
            return null;
        }
    
        $sql = "SELECT MAX(grade)
                  FROM {quiz_grades}
                 WHERE quiz   = :quizid
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
     * - Siempre:  ->estado = "Finalizado" / "Pendiente" / ''.
     * - SCORM:    ->intentos, ->avance (en %).
     * - Quiz:     ->intentos, ->nota (nota más alta).
     * - Tarea:    ->entrega ("Entregado"/"No entregó"), ->fechaentrega, ->nota.
     *
     * @param int   $courseid ID del curso.
     * @param int   $userid   ID del usuario.
     * @param mixed $module   Objeto/array con info del course_module (id, modname, instance...).
     * @return \stdClass
     */
    private static function get_user_module_detail($courseid, $userid, $module) {
        global $DB;
    
        $detail = new \stdClass();
        
        $detail->estado        = '';
        $detail->intentos      = null;  // SCORM / quiz
        $detail->puntuacion    = null;  // SCORM (% nota)
        $detail->nota          = null;  // quiz / tarea
        $detail->entrega       = null;  // tarea
        $detail->fechaentrega  = null;  // tarea

        // ----------------------------
        // Detectar cmid, modname e instance
        // ----------------------------
        $cmid       = 0;
        $modname    = '';
        $instanceid = 0;
    
        if (is_object($module)) {
            if (!empty($module->id)) {
                $cmid = (int)$module->id;
            } else if (!empty($module->cmid)) {
                $cmid = (int)$module->cmid;
            }
            if (!empty($module->modname)) {
                $modname = $module->modname;
            }
            if (!empty($module->instance)) {
                $instanceid = (int)$module->instance;
            }
        } else if (is_array($module)) {
            if (!empty($module['id'])) {
                $cmid = (int)$module['id'];
            } else if (!empty($module['cmid'])) {
                $cmid = (int)$module['cmid'];
            }
            if (!empty($module['modname'])) {
                $modname = $module['modname'];
            }
            if (!empty($module['instance'])) {
                $instanceid = (int)$module['instance'];
            }
        }
    
        if (!$cmid) {
            return $detail;
        }
    
        // ----------------------------
        // 1) ¿Tiene seguimiento de finalización?
        // ----------------------------
        $cm = $DB->get_record('course_modules', ['id' => $cmid], 'id, completion', IGNORE_MISSING);
        if (!$cm || (int)$cm->completion === 0) {
            // Sin tracking de finalización → columna vacía
            return $detail;
        }
    
        // ----------------------------
        // 2) Estado de finalización genérico
        // ----------------------------
        $completion = $DB->get_record('course_modules_completion', [
            'coursemoduleid' => $cmid,
            'userid'         => $userid
        ]);
    
        if ($completion && (int)$completion->completionstate > 0) {
            $detail->estado = 'Finalizado';
        } else {
            $detail->estado = 'Pendiente';
        }
    
        $dbman = $DB->get_manager();
    
        // ----------------------------
        // 3) Extras SOLO para SCORM
        // ----------------------------
        if ($modname === 'scorm' && $instanceid) {
            $detail->intentos   = self::get_scorm_attempts_count($instanceid, $userid);
            $detail->puntuacion = self::get_scorm_score_percent($instanceid, $userid);
            
            $puntuacion_numero = (float) str_replace('%', '', $detail->puntuacion);
             
            
            if($detail->intentos==0){$detail->estado = 'Sin iniciar';}
            if($detail->intentos>0 && $puntuacion_numero<100){$detail->estado = 'En proceso';}
            if($detail->intentos>0 && $puntuacion_numero>=100){$detail->estado = 'Finalizado';}
        }

        // ----------------------------
        // 4) Extras SOLO para QUIZ
        // ----------------------------
        if ($modname === 'quiz' && $instanceid) {
            // Intentos
            if ($dbman->table_exists('quiz_attempts')) {
                $detail->intentos = self::get_quiz_attempts_count($instanceid, $userid);
            }
    
            // Nota más alta
            $nota = self::get_quiz_highest_grade($instanceid, $userid);
            if ($nota !== null) {
                $detail->nota = $nota;
            }
        }
    
        // ----------------------------
        // 5) Extras SOLO para TAREAS (assign)
        // ----------------------------
        if ($modname === 'assign' && $instanceid) {
            // a) ¿Entregó? y fecha de entrega (tabla assign_submission).
            if ($dbman->table_exists('assign_submission')) {
                $submission = $DB->get_record('assign_submission', [
                    'assignment' => $instanceid,
                    'userid'     => $userid
                ], '*', IGNORE_MISSING);
    
                if ($submission && $submission->status === 'submitted') {
                    $detail->entrega      = 'Entregado';
                    $detail->fechaentrega = $submission->timemodified
                        ? userdate($submission->timemodified)
                        : '';
                } else {
                    $detail->entrega      = 'No entregó';
                    $detail->fechaentrega = '';
                }
            }
            
            // b) Nota de la tarea (desde gradebook para ese assign), como porcentaje.
            if ($dbman->table_exists('grade_items') && $dbman->table_exists('grade_grades')) {
                $sql = "SELECT gi.grademax, gg.finalgrade
                          FROM {grade_items} gi
                          JOIN {grade_grades} gg ON gg.itemid = gi.id
                         WHERE gi.courseid     = :courseid
                           AND gi.itemmodule   = 'assign'
                           AND gi.iteminstance = :assignid
                           AND gg.userid       = :userid";
                $grade = $DB->get_record_sql($sql, [
                    'courseid' => $courseid,
                    'assignid' => $instanceid,
                    'userid'   => $userid
                ]);
        
                if ($grade && $grade->finalgrade !== null && $grade->grademax > 0) {
                    $percent = round(($grade->finalgrade / $grade->grademax) * 100, 2);
                    $detail->nota = $percent . '%';
                }
            }
        }
    
        return $detail;
    }

    
    /**
     * Número de intentos de un quiz para un usuario.
     *
     * @param int $quizid ID de la instancia del quiz (tabla quiz.id).
     * @param int $userid ID del usuario.
     * @return int
     */
    private static function get_quiz_attempts_count($quizid, $userid) {
        global $DB;
    
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('quiz_attempts')) {
            // El módulo quiz no está instalado completamente.
            return 0;
        }
    
        $sql = "SELECT COUNT(*)
                  FROM {quiz_attempts}
                 WHERE quiz   = :quizid
                   AND userid = :userid";
    
        $count = $DB->get_field_sql($sql, [
            'quizid' => $quizid,
            'userid' => $userid,
        ]);
    
        return $count ? (int)$count : 0;
    }
    
    /**
     * Devuelve el timestamp de la última actividad completada en el curso por el usuario.
     *
     * @param int $courseid
     * @param int $userid
     * @return int Timestamp o 0 si no hay actividades completadas.
     */
    private static function get_user_last_activity_completion_time($courseid, $userid) {
        global $DB;
    
        $sql = "SELECT MAX(cmc.timemodified)
                  FROM {course_modules_completion} cmc
                  JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                 WHERE cm.course = :courseid
                   AND cmc.userid = :userid
                   AND cmc.completionstate > 0";
    
        $time = $DB->get_field_sql($sql, [
            'courseid' => $courseid,
            'userid'   => $userid
        ]);
    
        return $time ? (int)$time : 0;
    }
    
    /**
     * Porcentaje de satisfacción global (0–100) a partir de las actividades mod_feedback.
     *
     * Busca ítems tipo multichoice cuya pregunta contenga
     * la frase "satisfecho con el curso" y calcula un promedio
     * ponderado según la escala (1–5 o 1–7). Ignora la opción 8 ("No aplica").
     *
     * @return float
     */
    private static function get_platform_satisfaction_percent(): float {
        global $DB;

        // Frase común de la pregunta (sirve para "En general, quedé/estoy satisfecho con el curso").
        $pattern = '%' . $DB->sql_like_escape('satisfecho con el curso') . '%';

        $sql = "SELECT fv.value AS valor, COUNT(*) AS total
                  FROM {feedback_completed} fc
                  JOIN {feedback}       f  ON f.id  = fc.feedback
                  JOIN {feedback_item}  fi ON fi.feedback = f.id
                  JOIN {feedback_value} fv ON fv.completed = fc.id
                                           AND fv.item      = fi.id
                 WHERE fi.typ = 'multichoice'
                   AND " . $DB->sql_like('fi.name', ':pregunta', false) . "
              GROUP BY fv.value";

        $records = $DB->get_records_sql($sql, ['pregunta' => $pattern]);

        if (empty($records)) {
            return 0.0;
        }

        // --- 1ª pasada: detectar el máximo valor real de la escala (5 o 7 normalmente) ---
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

        // Si vemos un 8 asumimos que es "No aplica" y que la escala real llega hasta 7.
        $scalemax = $maxraw > 7 ? 7 : $maxraw;
        if ($scalemax < 2) {
            // Algo raro, evitamos divisiones por cero.
            return 0.0;
        }

        // --- 2ª pasada: acumular sólo valores entre 1 y $scalemax (ignoramos 0, 8, etc.) ---
        $weighted = 0.0;
        $answers  = 0;

        foreach ($records as $rec) {
            $v     = (int)$rec->valor;
            $count = (int)$rec->total;

            if ($v <= 0 || $v > $scalemax) {
                continue; // fuera de escala o "No aplica"
            }

            $weighted += $v * $count;
            $answers  += $count;
        }

        if ($answers === 0) {
            return 0.0;
        }

        $maxpossible = $answers * $scalemax;
        $percent     = ($weighted / $maxpossible) * 100.0;

        return round($percent, 2);
    }


}