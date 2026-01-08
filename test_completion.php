<?php
// Incluimos el config de Moodle para tener acceso a $CFG y al autoloader.
require_once('../../config.php');

// Imprimimos un mensaje para saber que el script se ejecuta.
echo "Intentando cargar la clase completion_info...<br>";

try {
    // Intentamos crear una instancia de la clase.
    // No necesitamos un curso real para esta prueba.
    $completion = new completion_info(null);
    echo "ÉXITO: La clase completion_info se ha cargado correctamente.<br>";

} catch (Exception $e) {
    // Si falla, mostramos el error.
    echo "ERROR: No se pudo cargar la clase completion_info.<br>";
    echo "Mensaje de error: " . $e->getMessage() . "<br>";
}