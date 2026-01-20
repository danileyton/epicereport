<?php
/**
 * Scheduled tasks definition for local_epicereports
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_epicereports\task\send_scheduled_reports',
        'blocking'  => 0,
        'minute'    => '*/5',      // Ejecutar cada 5 minutos para verificar pendientes
        'hour'      => '*',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_epicereports\task\cleanup_temp_files',
        'blocking'  => 0,
        'minute'    => '30',       // Ejecutar una vez al día a las 3:30 AM
        'hour'      => '3',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_epicereports\task\send_followup_messages',
        'blocking'  => 0,
        'minute'    => '*/10',     // Ejecutar cada 10 minutos para verificar pendientes
        'hour'      => '*',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
    ],
];
