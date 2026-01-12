<?php
/**
 * Version information for local_epicereports
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_epicereports';  // Nombre único del plugin.
$plugin->version   = 2024060105;            // Versión del plugin (Formato: AAAAMMDDXX).
                                            // 2024060103 -> versión original
                                            // 2024060104 -> añade tablas de programación de reportes
                                            // 2024060105 -> añade generador de reportes Excel
$plugin->requires  = 2022112800;            // Versión mínima de Moodle requerida (Moodle 4.1).
$plugin->maturity  = MATURITY_ALPHA;        // Madurez: ALPHA, BETA, RC, STABLE.
$plugin->release   = 'v1.2-alpha';          // Nombre de la versión para mostrar.
                                            // v1.0-alpha -> versión original
                                            // v1.1-alpha -> sistema de envío programado
                                            // v1.2-alpha -> generador de reportes Excel
