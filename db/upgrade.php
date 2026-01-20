<?php
/**
 * Upgrade script for local_epicereports
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_epicereports plugin.
 *
 * @param int $oldversion The old version of the plugin.
 * @return bool
 */
function xmldb_local_epicereports_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade para añadir las tablas de programación de reportes.
    // También verifica si las tablas existen aunque la versión sea superior (por si hubo error en instalación anterior).
    if ($oldversion < 2024060104 || !$dbman->table_exists('local_epicereports_schedules')) {

        // =====================================================================
        // Tabla: local_epicereports_schedules
        // Programaciones de envío automático de reportes por curso
        // =====================================================================
        $table = new xmldb_table('local_epicereports_schedules');

        // Definir campos.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sendtime', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, '08:00');
        $table->add_field('monday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('tuesday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('wednesday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('thursday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('friday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('saturday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sunday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('include_course_report', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('include_feedback_report', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('email_subject', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('email_body', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('lastrun', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('nextrun', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Definir keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('createdby_fk', XMLDB_KEY_FOREIGN, ['createdby'], 'user', ['id']);

        // Definir índices.
        $table->add_index('enabled_nextrun_idx', XMLDB_INDEX_NOTUNIQUE, ['enabled', 'nextrun']);

        // Crear tabla si no existe.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // =====================================================================
        // Tabla: local_epicereports_recipients
        // Destinatarios de las programaciones de reportes
        // =====================================================================
        $table = new xmldb_table('local_epicereports_recipients');

        // Definir campos.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scheduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('recipienttype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'to');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Definir keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('scheduleid_fk', XMLDB_KEY_FOREIGN, ['scheduleid'], 'local_epicereports_schedules', ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Definir índices.
        $table->add_index('scheduleid_email_idx', XMLDB_INDEX_UNIQUE, ['scheduleid', 'email']);

        // Crear tabla si no existe.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // =====================================================================
        // Tabla: local_epicereports_logs
        // Registro histórico de envíos de reportes
        // =====================================================================
        $table = new xmldb_table('local_epicereports_logs');

        // Definir campos.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scheduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('recipientid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('recipientemail', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('errorcode', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('errormessage', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('attachments', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('retrycount', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timescheduled', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timesent', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Definir keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('scheduleid_fk', XMLDB_KEY_FOREIGN, ['scheduleid'], 'local_epicereports_schedules', ['id']);
        $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Definir índices.
        $table->add_index('status_idx', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('timescheduled_idx', XMLDB_INDEX_NOTUNIQUE, ['timescheduled']);
        $table->add_index('status_timescheduled_idx', XMLDB_INDEX_NOTUNIQUE, ['status', 'timescheduled']);

        // Crear tabla si no existe.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Actualizar savepoint solo si estamos realmente en una versión anterior.
        if ($oldversion < 2024060104) {
            upgrade_plugin_savepoint(true, 2024060104, 'local', 'epicereports');
        }
    }

    // Asegurar que las tablas existen para versiones posteriores también.
    if ($oldversion < 2024060106) {
        // Verificar y crear tablas si faltan (por si hubo error previo).
        $tables_to_check = ['local_epicereports_schedules', 'local_epicereports_recipients', 'local_epicereports_logs'];
        
        foreach ($tables_to_check as $tablename) {
            if (!$dbman->table_exists($tablename)) {
                // Forzar recreación ejecutando el bloque anterior.
                // Esto es un fallback de seguridad.
                throw new moodle_exception('missingtables', 'local_epicereports', '', null,
                    "La tabla $tablename no existe. Por favor, desinstale y reinstale el plugin.");
            }
        }
        
        upgrade_plugin_savepoint(true, 2024060106, 'local', 'epicereports');
    }

    // =====================================================================
    // Upgrade v1.6.0: Tablas para mensajes de seguimiento (followup)
    // =====================================================================
    if ($oldversion < 2024060200 || !$dbman->table_exists('local_epicereports_followup')) {

        // =====================================================================
        // Tabla: local_epicereports_followup
        // Programaciones de mensajes de seguimiento a alumnos no completados
        // =====================================================================
        $table = new xmldb_table('local_epicereports_followup');

        // Definir campos.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sendtime', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, '09:00');
        $table->add_field('monday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('tuesday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('wednesday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('thursday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('friday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('saturday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sunday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('specific_dates', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('target_status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'all_incomplete');
        $table->add_field('send_email', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('send_message', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('message_subject', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('message_body', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('message_bodyformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('max_per_user', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'daily');
        $table->add_field('lastrun', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('nextrun', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Definir keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('createdby_fk', XMLDB_KEY_FOREIGN, ['createdby'], 'user', ['id']);

        // Definir índices.
        $table->add_index('enabled_nextrun_idx', XMLDB_INDEX_NOTUNIQUE, ['enabled', 'nextrun']);

        // Crear tabla si no existe.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // =====================================================================
        // Tabla: local_epicereports_followup_logs
        // Registro histórico de mensajes de seguimiento enviados
        // =====================================================================
        $table = new xmldb_table('local_epicereports_followup_logs');

        // Definir campos.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('followupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('recipientemail', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('channel', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'email');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('errorcode', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('errormessage', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timescheduled', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timesent', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Definir keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('followupid_fk', XMLDB_KEY_FOREIGN, ['followupid'], 'local_epicereports_followup', ['id']);
        $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Definir índices.
        $table->add_index('status_idx', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('followupid_userid_timesent_idx', XMLDB_INDEX_NOTUNIQUE, ['followupid', 'userid', 'timesent']);

        // Crear tabla si no existe.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Actualizar savepoint.
        if ($oldversion < 2024060200) {
            upgrade_plugin_savepoint(true, 2024060200, 'local', 'epicereports');
        }
    }

    return true;
}
