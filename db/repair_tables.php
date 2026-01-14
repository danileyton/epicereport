<?php
/**
 * Script de reparación de base de datos para local_epicereports
 *
 * Ejecutar este script desde CLI si las tablas no se crearon correctamente:
 * php local/epicereports/db/repair_tables.php
 *
 * O desde el navegador como administrador (con cuidado).
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/upgradelib.php');

// Verificar que el usuario es admin.
if (!is_siteadmin()) {
    echo "ERROR: Este script debe ser ejecutado por un administrador.\n";
    exit(1);
}

echo "=================================================================\n";
echo "local_epicereports: Script de reparación de base de datos\n";
echo "=================================================================\n\n";

global $DB;
$dbman = $DB->get_manager();

$tables_created = 0;
$tables_existed = 0;

// =====================================================================
// Tabla: local_epicereports_schedules
// =====================================================================
echo "Verificando tabla local_epicereports_schedules... ";

$table = new xmldb_table('local_epicereports_schedules');

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

$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
$table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
$table->add_key('createdby_fk', XMLDB_KEY_FOREIGN, ['createdby'], 'user', ['id']);

$table->add_index('courseid_idx', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
$table->add_index('enabled_nextrun_idx', XMLDB_INDEX_NOTUNIQUE, ['enabled', 'nextrun']);

if (!$dbman->table_exists($table)) {
    $dbman->create_table($table);
    echo "CREADA\n";
    $tables_created++;
} else {
    echo "ya existe\n";
    $tables_existed++;
}

// =====================================================================
// Tabla: local_epicereports_recipients
// =====================================================================
echo "Verificando tabla local_epicereports_recipients... ";

$table = new xmldb_table('local_epicereports_recipients');

$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
$table->add_field('scheduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
$table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
$table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
$table->add_field('recipienttype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'to');
$table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
$table->add_key('scheduleid_fk', XMLDB_KEY_FOREIGN, ['scheduleid'], 'local_epicereports_schedules', ['id']);
$table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

$table->add_index('scheduleid_idx', XMLDB_INDEX_NOTUNIQUE, ['scheduleid']);
$table->add_index('scheduleid_email_idx', XMLDB_INDEX_UNIQUE, ['scheduleid', 'email']);

if (!$dbman->table_exists($table)) {
    $dbman->create_table($table);
    echo "CREADA\n";
    $tables_created++;
} else {
    echo "ya existe\n";
    $tables_existed++;
}

// =====================================================================
// Tabla: local_epicereports_logs
// =====================================================================
echo "Verificando tabla local_epicereports_logs... ";

$table = new xmldb_table('local_epicereports_logs');

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

$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
$table->add_key('scheduleid_fk', XMLDB_KEY_FOREIGN, ['scheduleid'], 'local_epicereports_schedules', ['id']);
$table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

$table->add_index('scheduleid_idx', XMLDB_INDEX_NOTUNIQUE, ['scheduleid']);
$table->add_index('courseid_idx', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
$table->add_index('status_idx', XMLDB_INDEX_NOTUNIQUE, ['status']);
$table->add_index('timescheduled_idx', XMLDB_INDEX_NOTUNIQUE, ['timescheduled']);
$table->add_index('status_timescheduled_idx', XMLDB_INDEX_NOTUNIQUE, ['status', 'timescheduled']);

if (!$dbman->table_exists($table)) {
    $dbman->create_table($table);
    echo "CREADA\n";
    $tables_created++;
} else {
    echo "ya existe\n";
    $tables_existed++;
}

// =====================================================================
// Resumen
// =====================================================================
echo "\n=================================================================\n";
echo "RESUMEN:\n";
echo "  Tablas creadas: $tables_created\n";
echo "  Tablas existentes: $tables_existed\n";
echo "=================================================================\n";

if ($tables_created > 0) {
    echo "\n¡Tablas creadas exitosamente!\n";
    echo "Ahora puede usar las funcionalidades de reportes programados.\n";
} else {
    echo "\nTodas las tablas ya existían.\n";
}

// Purgar caché.
echo "\nPurgando caché...\n";
purge_all_caches();
echo "Caché purgada.\n";

echo "\n¡Proceso completado!\n";
