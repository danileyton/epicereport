<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// PhpSpreadsheet (basado en tu getdetallecourselistexcelv2.php).
require_once($CFG->libdir . '/phpspreadsheet/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php');

use local_epicereports\helper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

$enroled = 0;
$notStart = 0;
$notStartPercent = 0;
$started = 0;
$startedPercent = 0;
$inProcess = 0;
$inProcessPercent = 0;
$completed = 0;
$completedPercent = 0;
$certificated = 0;
$certificatedPercent = 0;

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

// Obtenemos los datos estructurados para Excel desde el helper.
$excel_data = helper::get_course_data_for_excel($courseid);

$course  = $excel_data['course']  ?? null;
$modules = $excel_data['modules'] ?? [];
$users   = $excel_data['users']   ?? [];

// Si no existe el curso, error estándar de Moodle.
if (!$course) {
    print_error('invalidcourseid', 'error');
}




/**
 * Convierte un índice 0-based de columna en letra(s) de Excel (A, B, ..., Z, AA, AB, ...).
 *
 * @param int $index Índice 0-based.
 * @return string Letra(s) de columna.
 */
function local_epicereports_excel_col($index): string {
    $index++; // Convertimos a 1-based.
    $letters = '';
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $letters = chr(65 + $mod) . $letters;
        $index = (int)(($index - $mod) / 26);
    }
    return $letters;
}


// --- Cálculo de resumen general (matriculados, estados y certificados).
// Estos valores se usan tanto en la vista previa como en el Excel.
$enroled      = count($users);
$notStart     = 0;
$started      = 0;
$inProcess    = 0;
$completed    = 0;
$certificated = 0;

if (!empty($users)) {
    foreach ($users as $u) {
        $hasstarted     = !empty($u->primer_acceso);
        $iscompleted    = !empty($u->estado_finalizacion) && $u->estado_finalizacion === 'Completado';
        $hascertificate = !empty($u->certificado) && $u->certificado !== '-';

        // Matriculados ya están contados en $enroled.
        if ($hasstarted) {
            $started++;
        } else {
            $notStart++;
        }

        // En proceso: ha iniciado pero aún no completa.
        if ($hasstarted && !$iscompleted) {
            $inProcess++;
        }

        // Completados.
        if ($iscompleted) {
            $completed++;
        }

        // Certificados emitidos.
        if ($hascertificate) {
            $certificated++;
        }
    }
}

// Porcentajes:
// - Sin iniciar / Iniciados respecto del total de matriculados.
// - En proceso / Completado / Certificados respecto del total de iniciados.
$notStartPercent     = $enroled > 0 ? ($notStart / $enroled) * 100 : 0;
$startedPercent      = $enroled > 0 ? ($started  / $enroled) * 100 : 0;
$inProcessPercent    = $started > 0 ? ($inProcess    / $started) * 100 : 0;
$completedPercent    = $started > 0 ? ($completed    / $started) * 100 : 0;
$certificatedPercent = $started > 0 ? ($certificated / $started) * 100 : 0;

// Versiones formateadas con dos decimales y símbolo % para mostrar en el Excel.
$notStartPercentStr     = $notStartPercent > 0 ? number_format($notStartPercent, 2) . '%' : '0%';
$startedPercentStr      = $startedPercent > 0 ? number_format($startedPercent, 2) . '%' : '0%';
$inProcessPercentStr    = $inProcessPercent > 0 ? number_format($inProcessPercent, 2) . '%' : '0%';
$completedPercentStr    = $completedPercent > 0 ? number_format($completedPercent, 2) . '%' : '0%';
$certificatedPercentStr = $certificatedPercent > 0 ? number_format($certificatedPercent, 2) . '%' : '0%';


// ---------------------------------------------------------------------------
//  MODO PREVISUALIZACIÓN (preview=1) → tabla HTML
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

    // Botón para descargar directamente el Excel (XLSX).
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

    // Columnas por actividad:
    // - SCORM: (estado), (intentos), (puntuación)
    // - Quiz:  (estado), (intentos), (nota más alta)
    // - Tarea: (estado), (entrega), (fecha entrega), (nota)
    // - Otros: (estado)
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

    // Resumen final del usuario.
    $headers = array_merge($headers, [
        'Certificado',
        'Avance',
        'Nota final (%)',
        'Fecha finalización',
        'Estado finalización'
    ]);

    $table->head = $headers;
    $table->data = [];
    
    $usersCount = 0;

    foreach ($users as $user) {
        $usersCount++;
        $row = [];

        // Datos base de usuario.
        $row[] = $user->userid        ?? '';
        $row[] = $user->username      ?? '';
        $row[] = $user->idnumber      ?? '';
        $row[] = $user->fullname      ?? '';
        $row[] = $user->email         ?? '';
        $row[] = $user->primer_acceso ?? '';
        $row[] = $user->ultimo_acceso ?? '';
        $row[] = $user->grupos        ?? '';
        
        

        // Datos por módulo: usamos mod_{cmid}.
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

            if ($modname === 'scorm') {
                $intentosdisplay   = ($intentos === '' || $intentos === null || (string)$intentos === '0') ? '-' : $intentos;
                $puntuaciondisplay = ($puntuacion === '' || $puntuacion === null) ? '-' : $puntuacion;

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

        // Resumen final.
        $row[] = $user->certificado         ?? '';
        $row[] = $user->porcentaje_avance   ?? '';
        $row[] = $user->nota_final          ?? '';
        $row[] = $user->fecha_finalizacion  ?? '';
        $row[] = $user->estado_finalizacion ?? '';
        
        if($user->fecha_finalizacion){$completed++;}
        if($user->primer_acceso){$started++;}else{$notStart++;}

        $table->data[] = $row;
    }

    echo html_writer::table($table);
    echo $OUTPUT->footer();
    exit;
}

// ---------------------------------------------------------------------------
//  MODO EXPORTACIÓN (XLSX real con PhpSpreadsheet)
// ---------------------------------------------------------------------------

if (empty($users)) {
    // Opcional: en vez de generar un XLSX vacío, mostramos mensaje.
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

// Creamos el Excel.
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();

// Título de hoja (máx 31 caracteres).
$sheet->setTitle(substr(format_string($course->shortname), 0, 31));

// -----------------------------
// Fila 1: información general
// -----------------------------

// Encabezados de la tabla de resumen (fila 1).
$sheet->setCellValue('A1', 'Criterio');
$sheet->setCellValue('B1', 'N°');
$sheet->setCellValue('C1', 'Porcentaje');

// Encabezados en negrita y centrados.
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2E75B6']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
];

// Estilo general de datos.
$dataStyle = [
    'font' => [
        'bold' => true,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

$dataItems = [
     
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

// Encabezados.
$sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

// Etiquetas de criterios (columna A).
$sheet->setCellValue('A2', 'Matriculados');
$sheet->setCellValue('A3', 'Sin iniciar');
$sheet->setCellValue('A4', 'Iniciados');
$sheet->setCellValue('A5', 'En proceso');
$sheet->setCellValue('A6', 'Completado');
$sheet->setCellValue('A7', 'Certificados');

// Valores absolutos (columna B).
$sheet->setCellValue('B2', $enroled);
$sheet->setCellValue('B3', $notStart);
$sheet->setCellValue('B4', $started);
$sheet->setCellValue('B5', $inProcess);
$sheet->setCellValue('B6', $completed);
$sheet->setCellValue('B7', $certificated);

// Porcentajes (columna C).
// Matriculados no lleva porcentaje (es el 100 % base).
$sheet->setCellValue('C2', '');
$sheet->setCellValue('C3', $notStartPercentStr);
$sheet->setCellValue('C4', $startedPercentStr);
$sheet->setCellValue('C5', $inProcessPercentStr);
$sheet->setCellValue('C6', $completedPercentStr);
$sheet->setCellValue('C7', $certificatedPercentStr);

// Aplicar estilos a filas de datos.
$sheet->getStyle('A2:A7')->applyFromArray($dataStyle);
$sheet->getStyle('B2:B7')->applyFromArray($dataItems);
$sheet->getStyle('C2:C7')->applyFromArray($dataItems);

// -----------------------------
// A partir de la fila 9 sigues con tus encabezados dinámicos
// (ID usuario, username, módulos, etc.)
// -----------------------------


// Fila en blanco (fila 8).
// Fila 7: cabeceras.
$rownum = 9;
$col    = 0;

// Cabeceras básicas.
$baseheaders = [
    'ID usuario',
    'Usuario',
    'ID interno / RUT',
    'Nombre completo',
    'Email',
    'Primer acceso',
    'Último acceso',
    'Grupos'
];

foreach ($baseheaders as $h) {
    $sheet->setCellValue(local_epicereports_excel_col($col) . $rownum, $h);
    $col++;
}

// Cabeceras por módulo (igual que en la previsualización).
foreach ($modules as $module) {
    $modname = $module['modname'] ?? '';
    $name    = format_string($module['name']);

    if ($modname === 'scorm') {
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (estado)');
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (intentos)');
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (puntuación)');
    } else if ($modname === 'quiz') {
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (estado)');
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (intentos)');
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (nota más alta)');
    } else if ($modname === 'assign') {
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (estado)');
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (entrega)');
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (fecha entrega)');
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (nota)');
    } else {
        $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $name . ' (estado)');
    }
}

// Cabeceras resumen final.
$sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, 'Certificado');
$sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, 'Avance');
$sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, 'Nota final (%)');
$sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, 'Fecha finalización');
$sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, 'Estado finalización');

// Un poco de estilo de cabecera (fondo gris claro y negrita).
$lastcolletter = local_epicereports_excel_col($col - 1);
$sheet->getStyle('A' . $rownum . ':' . $lastcolletter . $rownum)->getFont()->setBold(true);
$sheet->getStyle('A' . $rownum . ':' . $lastcolletter . $rownum)
    ->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()
    ->setARGB('FFEFEFEF');

// -----------------------------
// Filas de datos
// -----------------------------
$rownum++;

foreach ($users as $user) {
    $col = 0;

    // Datos base usuario.
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->userid        ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->username      ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->idnumber      ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->fullname      ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->email         ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->primer_acceso ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->ultimo_acceso ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->grupos        ?? '');

    // Datos por módulo.
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

        if ($modname === 'scorm') {
            $intentosdisplay   = ($intentos === '' || $intentos === null || (string)$intentos === '0') ? '-' : $intentos;
            $puntuaciondisplay = ($puntuacion === '' || $puntuacion === null) ? '-' : $puntuacion;

            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $estado);
            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $intentosdisplay);
            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $puntuaciondisplay);

        } else if ($modname === 'quiz') {
            $intentosdisplay = ($intentos === '' || $intentos === null || (string)$intentos === '0') ? '-' : $intentos;
            $notadisplay     = ($notaquiz === '' || $notaquiz === null) ? '-' : $notaquiz;

            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $estado);
            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $intentosdisplay);
            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $notadisplay);

        } else if ($modname === 'assign') {
            $fechaentdisplay = ($fechaentrega === '' || $fechaentrega === null) ? '-' : $fechaentrega;
            $notaassigndisp  = ($notaassign === '' || $notaassign === null) ? '-' : $notaassign;

            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $estado);
            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $entrega);
            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $fechaentdisplay);
            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $notaassigndisp);

        } else {
            $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $estado);
        }
    }

    // Resumen final.
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->certificado         ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->porcentaje_avance   ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->nota_final          ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->fecha_finalizacion  ?? '');
    $sheet->setCellValue(local_epicereports_excel_col($col++) . $rownum, $user->estado_finalizacion ?? '');

    $rownum++;
}

// Autoajustar ancho de columnas.
for ($i = 0; $i < $col; $i++) {
    $sheet->getColumnDimension(local_epicereports_excel_col($i))->setAutoSize(true);
}

// Nombre de archivo.
$filename = 'reporte_curso_' . $courseid . '_' . date('Ymd_His') . '.xlsx';

// Cabeceras de descarga.
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
