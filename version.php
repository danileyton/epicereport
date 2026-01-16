<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_epicereports';
$plugin->version   = 2024060115;           // v1.5.7 - Fix: Evitar re-ejecuciones múltiples el mismo día
$plugin->requires  = 2022112800;
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = 'v1.5.7-beta';
