<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_epicereports\helper;

// --- INICIALIZACIÓN BÁSICA ---
require_login();
$context = context_system::instance();
$PAGE->set_context($context);

// Parámetros.
$courseid = required_param('courseid', PARAM_INT);
$preview  = optional_param('preview', 0, PARAM_BOOL);

// Validar que venga un courseid válido (> 0).
if ($courseid <= 0) {
    print_error('invalidcourseid', 'error');
}

// Obtenemos los datos estructurados para Excel.
$excel_data = helper::get_course_data_for_excel($courseid);


$course  = $excel_data['course']  ?? null;
$modules = $excel_data['modules'] ?? [];
$users   = $excel_data['users']   ?? [];

// Si no existe el curso, error estándar de Moodle.
if (!$course) {
    print_error('invalidcourseid', 'error');
}

// ---------------------------------------------------------------------------
//  MODO PREVISUALIZACIÓN (preview=1)
// ---------------------------------------------------------------------------
if ($preview) {
    $PAGE->set_url(new moodle_url('/local/epicereports/export_course_excel.php', [
        'courseid' => $courseid,
        'preview'  => 1
    ]));
    $PAGE->set_title(get_string('pluginname', 'local_epicereports'));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_pagelayout('admin');

    echo $OUTPUT->header();
    echo $OUTPUT->heading('Previsualización de datos para exportar');

    if (empty($users)) {
        echo $OUTPUT->notification('No hay usuarios matriculados en este curso.', 'info');
        echo $OUTPUT->footer();
        exit;
    }

    echo html_writer::tag('p',
        'A continuación se muestra una vista previa de los datos que se exportarán. ' .
        'Si todo se ve correcto, puedes descargar el archivo Excel desde el botón de abajo.'
    );

    // Botón para descargar directamente el Excel (CSV).
    $downloadurl = new moodle_url('/local/epicereports/export_course_excel.php', [
        'courseid' => $courseid
    ]);
    echo html_writer::div(
        html_writer::link($downloadurl, 'Descargar Excel', ['class' => 'btn btn-primary mb-3']),
        'mb-3'
    );

    // Construimos la tabla de previsualización.
    $table = new html_table();
    $table->attributes['class'] = 'table table-striped table-bordered table-sm';

    // Cabeceras básicas de usuario.
    $headers = [
        'ID usuario',
        'Usuario',
        'ID interno / RUT',
        'Nombre completo',
        'Email',
        'Primer acceso',
        'Último acceso',
        'Grupos'
    ];

    // Columnas por actividad.
    // - Actividades normales: solo "(estado)".
    // - SCORM: "(estado)", "(intentos)", "(% avance)".
    // - Quiz:  "(estado)", "(intentos)", "(nota más alta)".
    foreach ($modules as $module) {
    $modname = $module['modname'] ?? '';

    if ($modname === 'scorm') {
        $headers[] = format_string($module['name']) . ' (estado)';
        $headers[] = format_string($module['name']) . ' (intentos)';
        $headers[] = format_string($module['name']) . ' (% avance)';
    } else if ($modname === 'quiz') {
        $headers[] = format_string($module['name']) . ' (estado)';
        $headers[] = format_string($module['name']) . ' (intentos)';
        $headers[] = format_string($module['name']) . ' (nota más alta)';
    } else if ($modname === 'assign') {
        $headers[] = format_string($module['name']) . ' (estado)';
        $headers[] = format_string($module['name']) . ' (entrega)';
        $headers[] = format_string($module['name']) . ' (fecha entrega)';
        $headers[] = format_string($module['name']) . ' (nota)';
    } else {
        $headers[] = format_string($module['name']) . ' (estado)';
    }
}



    // Resumen final del usuario.
    $headers = array_merge($headers, [
        'Certificado',
        'Avance',
        'Nota final',
        'Fecha finalización',
        'Estado finalización'
    ]);

    $table->head = $headers;
    $table->data = [];

    foreach ($users as $user) {
        $row = [];

        // Datos base de usuario.
        $row[] = $user->userid            ?? '';
        $row[] = $user->username          ?? '';
        $row[] = $user->idnumber          ?? '';
        $row[] = $user->fullname          ?? '';
        $row[] = $user->email             ?? '';
        $row[] = $user->primer_acceso     ?? '';
        $row[] = $user->ultimo_acceso     ?? '';
        $row[] = $user->grupos            ?? '';

        // Datos por módulo: usamos mod_{cmid}.
        //   - Siempre mostramos ->estado.
        //   - SCORM: además ->intentos y ->avance.
        //   - Quiz:  además ->intentos y ->nota.
        foreach ($modules as $module) {
            $modname   = $module['modname'] ?? '';
            $key       = 'mod_' . $module['id'];
        
            $estado       = '';
            $intentos     = '';
            $puntuacion   = '';
            $notaquiz     = '';
            $entrega      = '';
            $fechaentrega = '';
            $notaassign   = '';

            if (isset($user->$key)) {
                $detail = $user->$key;
        
                if (is_object($detail)) {
                    $estado = $detail->estado ?? '';
        
                    if ($modname === 'scorm') {
                        if (isset($detail->intentos)) {
                            $intentos = $detail->intentos;
                        }
                        if (isset($detail->puntuacion)) {
                            $puntuacion = $detail->puntuacion;
                        }
                    } else if ($modname === 'quiz') {
                        if (isset($detail->intentos)) {
                            $intentos = $detail->intentos;
                        }
                        if (isset($detail->nota)) {
                            $notaquiz = $detail->nota;
                        }
                    } else if ($modname === 'assign') {
                        if (isset($detail->entrega)) {
                            $entrega = $detail->entrega;
                        }
                        if (isset($detail->fechaentrega)) {
                            $fechaentrega = $detail->fechaentrega;
                        }
                        if (isset($detail->nota)) {
                            $notaassign = $detail->nota;
                        }
                    }
                } else {
                    $estado = (string)$detail;
                }
            }
        
            // Normalizamos visualmente algunos campos:
            // - intentos: 0 -> '-'
            // - nota (quiz/tarea): null -> '-'
            // - fechaentrega vacía -> '-'
            if ($modname === 'scorm') {
                $intentosdisplay    = ($intentos === '' || $intentos === null || (string)$intentos === '0') ? '-' : $intentos;
                $puntuaciondisplay  = ($puntuacion === '' || $puntuacion === null) ? '-' : $puntuacion;
            
                $row[] = $estado;
                $row[] = $intentosdisplay;
                $row[] = $puntuaciondisplay;
        
            } else if ($modname === 'quiz') {
                $intentosdisplay = ($intentos === '' || $intentos === null || (string)$intentos === '0') ? '-' : $intentos;
                $notadisplay     = ($notaquiz === '' || $notaquiz === null) ? '-' : $notaquiz;
        
                $row[] = $estado;
                $row[] = $intentosdisplay;
                $row[] = $notadisplay;
        
            } else if ($modname === 'assign') {
                $fechaentdisplay = ($fechaentrega === '' || $fechaentrega === null) ? '-' : $fechaentrega;
                $notaassigndisp  = ($notaassign === '' || $notaassign === null) ? '-' : $notaassign;
        
                $row[] = $estado;                      // estado de finalización
                $row[] = $entrega;                     // "Entregado" / "No entregó"
                $row[] = $fechaentdisplay;            // fecha o '-'
                $row[] = $notaassigndisp;             // nota o '-'
        
            } else {
                $row[] = $estado;
            }

        }


        // --- LÓGICA PARA CONTROLAR LA FECHA SEGÚN EL PORCENTAJE ---
        // Puede venir "100", "100.0" o "100%" → lo limpiamos a número.
        $rawporc    = $user->porcentaje_avance ?? 0;
        $porcentaje = (float) filter_var($rawporc, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Solo mostramos fecha si el avance es 100 o más.
        $fechafinal = '';
        if ($porcentaje >= 100) {
            $fechafinal = $user->fecha_finalizacion ?? '';
        }

        // Resumen final.
        $row[] = $user->certificado         ?? '';
        $row[] = $rawporc;                      // mostramos el porcentaje tal como viene (ej: "0%", "100%")
        $row[] = $user->nota_final          ?? '';
        $row[] = $fechafinal;                   // aquí ya va condicionado
        $row[] = $user->estado_finalizacion ?? '';

        $table->data[] = $row;

    }

    echo html_writer::table($table);
    echo $OUTPUT->footer();
    exit;
}

// ---------------------------------------------------------------------------
//  MODO EXPORTACIÓN (CSV para Excel)
// ---------------------------------------------------------------------------

if (empty($users)) {
    // Opcional: en vez de generar un CSV vacío, mostramos mensaje.
    $PAGE->set_url(new moodle_url('/local/epicereports/export_course_excel.php', [
        'courseid' => $courseid
    ]));
    $PAGE->set_title(get_string('pluginname', 'local_epicereports'));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_pagelayout('admin');

    echo $OUTPUT->header();
    echo $OUTPUT->notification('No hay usuarios matriculados en este curso.', 'info');
    echo $OUTPUT->footer();
    exit;
}

// Nombre de archivo.
$filename = clean_filename('reporte_curso_' . $courseid . '_' . date('Ymd_His') . '.csv');

// Cabeceras para descarga.
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// BOM para que Excel reconozca UTF-8 correctamente.
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Fila 1: información general del curso.
fputcsv($output, ['Curso', format_string($course->fullname), 'ID curso', $courseid]);

// Fila en blanco.
fputcsv($output, []);

// -------------------------------
// Cabecera de columnas (igual que en la previsualización)
// -------------------------------
$headers = [
    'ID usuario',
    'Usuario',
    'ID interno / RUT',
    'Nombre completo',
    'Email',
    'Primer acceso',
    'Último acceso',
    'Grupos'
];

foreach ($modules as $module) {
    $modname = $module['modname'] ?? '';

    if ($modname === 'scorm') {
        $headers[] = format_string($module['name']) . ' (estado)';
        $headers[] = format_string($module['name']) . ' (intentos)';
        $headers[] = format_string($module['name']) . ' (puntuación)';
    } else if ($modname === 'quiz') {
        $headers[] = format_string($module['name']) . ' (estado)';
        $headers[] = format_string($module['name']) . ' (intentos)';
        $headers[] = format_string($module['name']) . ' (nota más alta)';
    } else if ($modname === 'assign') {
        $headers[] = format_string($module['name']) . ' (estado)';
        $headers[] = format_string($module['name']) . ' (entrega)';
        $headers[] = format_string($module['name']) . ' (fecha entrega)';
        $headers[] = format_string($module['name']) . ' (nota)';
    } else {
        $headers[] = format_string($module['name']) . ' (estado)';
    }
}

$headers = array_merge($headers, [
    'Certificado',
    'Avance',
    'Nota final',
    'Fecha finalización',
    'Estado finalización'
]);

fputcsv($output, $headers);

// -------------------------------
// Filas de datos
// -------------------------------
foreach ($users as $user) {
    $row = [];

    // Datos base usuario.
    $row[] = $user->userid            ?? '';
    $row[] = $user->username          ?? '';
    $row[] = $user->idnumber          ?? '';
    $row[] = $user->fullname          ?? '';
    $row[] = $user->email             ?? '';
    $row[] = $user->primer_acceso     ?? '';
    $row[] = $user->ultimo_acceso     ?? '';
    $row[] = $user->grupos            ?? '';

    // Datos por módulo (igual que en la previsualización).
    foreach ($modules as $module) {
        $modname   = $module['modname'] ?? '';
        $key       = 'mod_' . $module['id'];

        $estado       = '';
        $intentos     = '';
        $puntuacion   = '';
        $notaquiz     = '';
        $entrega      = '';
        $fechaentrega = '';
        $notaassign   = '';


        if (isset($user->$key)) {
            $detail = $user->$key;

            if (is_object($detail)) {
                $estado = $detail->estado ?? '';

                if ($modname === 'scorm') {
                    if (isset($detail->intentos)) {
                        $intentos = $detail->intentos;
                    }
                    if (isset($detail->puntuacion)) {
                        $puntuacion = $detail->puntuacion;
                    }
                } else if ($modname === 'quiz') {
                    if (isset($detail->intentos)) {
                        $intentos = $detail->intentos;
                    }
                    if (isset($detail->nota)) {
                        $notaquiz = $detail->nota;
                    }
                } else if ($modname === 'assign') {
                    if (isset($detail->entrega)) {
                        $entrega = $detail->entrega;
                    }
                    if (isset($detail->fechaentrega)) {
                        $fechaentrega = $detail->fechaentrega;
                    }
                    if (isset($detail->nota)) {
                        $notaassign = $detail->nota;
                    }
                }
            } else {
                $estado = (string)$detail;
            }
        }

        // Normalizamos visualmente los campos para el CSV
        if ($modname === 'scorm') {
            $intentosdisplay    = ($intentos === '' || $intentos === null || (string)$intentos === '0') ? '-' : $intentos;
            $puntuaciondisplay  = ($puntuacion === '' || $puntuacion === null) ? '-' : $puntuacion;
        
            $row[] = $estado;
            $row[] = $intentosdisplay;
            $row[] = $puntuaciondisplay;
        } else if ($modname === 'quiz') {
            $intentosdisplay = ($intentos === '' || $intentos === null || (string)$intentos === '0') ? '-' : $intentos;
            $notadisplay     = ($notaquiz === '' || $notaquiz === null) ? '-' : $notaquiz;

            $row[] = $estado;
            $row[] = $intentosdisplay;
            $row[] = $notadisplay;

        } else if ($modname === 'assign') {
            $fechaentdisplay = ($fechaentrega === '' || $fechaentrega === null) ? '-' : $fechaentrega;
            $notaassigndisp  = ($notaassign === '' || $notaassign === null) ? '-' : $notaassign;

            $row[] = $estado;
            $row[] = $entrega;
            $row[] = $fechaentdisplay;
            $row[] = $notaassigndisp;

        } else {
            $row[] = $estado;
        }
    }

    // --- LÓGICA PARA CONTROLAR LA FECHA SEGÚN EL PORCENTAJE ---
    // Puede venir "100", "100.0" o "100%" → lo limpiamos a número.
    $rawporc    = $user->porcentaje_avance ?? 0;
    $porcentaje = (float) filter_var($rawporc, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Solo mostramos fecha si el avance es 100 o más.
    $fechafinal = '';
    if ($porcentaje >= 100) {
        $fechafinal = $user->fecha_finalizacion ?? '';
    }

    // Resumen final.
    $row[] = $user->certificado         ?? '';
    $row[] = $rawporc;                      // porcentaje tal como viene, ej: "85%"
    $row[] = $user->nota_final          ?? '';
    $row[] = $fechafinal;
    $row[] = $user->estado_finalizacion ?? '';

    fputcsv($output, $row);
}

fclose($output);
exit;

