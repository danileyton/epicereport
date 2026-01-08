<?php
/**
 * Exportación de datos de curso a Excel (XLSX)
 *
 * @package    local_epicereports
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// PhpSpreadsheet - Cargar con detección automática de ruta.
$phpspreadsheet_paths = [
    $CFG->libdir . '/phpspreadsheet/vendor/autoload.php',
    $CFG->libdir . '/phpspreadsheet/autoload.php',
    $CFG->libdir . '/phpspreadsheet/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php',
];

$phpspreadsheet_loaded = false;
foreach ($phpspreadsheet_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $phpspreadsheet_loaded = true;
        break;
    }
}

if (!$phpspreadsheet_loaded) {
    throw new \moodle_exception('error', 'local_epicereports', '', null, 
        'PhpSpreadsheet not found. Please check your Moodle installation.');
}

use local_epicereports\helper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// --- INICIALIZACIÓN BÁSICA ---
require_login();
$context = context_system::instance();
$PAGE->set_context($context);

// Verificar capacidad.
require_capability('local/epicereports:view', $context);

// Parámetros.
$courseid = required_param('courseid', PARAM_INT);
$preview  = optional_param('preview', 0, PARAM_BOOL);

// Validar que venga un courseid válido (> 0).
if ($courseid <= 0) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// Obtenemos los datos estructurados para Excel desde el helper.
$excel_data = helper::get_course_data_for_excel($courseid);

$course  = $excel_data['course']  ?? null;
$modules = $excel_data['modules'] ?? [];
$users   = $excel_data['users']   ?? [];

// Si no existe el curso, error estándar de Moodle.
if (!$course) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// =========================================================================
// CÁLCULO DE RESUMEN GENERAL
// =========================================================================

$summary = calculate_course_summary($users);

// Extraer valores para uso posterior.
$enroled             = $summary['enroled'];
$notStart            = $summary['not_started'];
$started             = $summary['started'];
$inProcess           = $summary['in_process'];
$completed           = $summary['completed'];
$certificated        = $summary['certificated'];
$notStartPercentStr  = $summary['not_started_percent_str'];
$startedPercentStr   = $summary['started_percent_str'];
$inProcessPercentStr = $summary['in_process_percent_str'];
$completedPercentStr = $summary['completed_percent_str'];
$certificatedPercentStr = $summary['certificated_percent_str'];

/**
 * Calcula el resumen estadístico de usuarios del curso.
 *
 * @param array $users Lista de usuarios con sus datos.
 * @return array Resumen con conteos y porcentajes.
 */
function calculate_course_summary(array $users): array {
    $enroled      = count($users);
    $notStart     = 0;
    $started      = 0;
    $inProcess    = 0;
    $completed    = 0;
    $certificated = 0;

    foreach ($users as $u) {
        $hasstarted     = !empty($u->primer_acceso);
        $iscompleted    = !empty($u->estado_finalizacion) && $u->estado_finalizacion === 'Completado';
        $hascertificate = !empty($u->certificado) && $u->certificado !== '-';

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

    // Porcentajes.
    $notStartPercent     = $enroled > 0 ? ($notStart / $enroled) * 100 : 0;
    $startedPercent      = $enroled > 0 ? ($started / $enroled) * 100 : 0;
    $inProcessPercent    = $started > 0 ? ($inProcess / $started) * 100 : 0;
    $completedPercent    = $started > 0 ? ($completed / $started) * 100 : 0;
    $certificatedPercent = $started > 0 ? ($certificated / $started) * 100 : 0;

    return [
        'enroled'                 => $enroled,
        'not_started'             => $notStart,
        'started'                 => $started,
        'in_process'              => $inProcess,
        'completed'               => $completed,
        'certificated'            => $certificated,
        'not_started_percent'     => $notStartPercent,
        'started_percent'         => $startedPercent,
        'in_process_percent'      => $inProcessPercent,
        'completed_percent'       => $completedPercent,
        'certificated_percent'    => $certificatedPercent,
        'not_started_percent_str' => format_percent($notStartPercent),
        'started_percent_str'     => format_percent($startedPercent),
        'in_process_percent_str'  => format_percent($inProcessPercent),
        'completed_percent_str'   => format_percent($completedPercent),
        'certificated_percent_str'=> format_percent($certificatedPercent),
    ];
}

/**
 * Formatea un porcentaje para mostrar.
 *
 * @param float $value Valor del porcentaje.
 * @return string Porcentaje formateado.
 */
function format_percent(float $value): string {
    return $value > 0 ? number_format($value, 2) . '%' : '0%';
}

/**
 * Convierte un índice 0-based de columna en letra(s) de Excel.
 *
 * @param int $index Índice 0-based.
 * @return string Letra(s) de columna.
 */
function get_excel_column(int $index): string {
    return Coordinate::stringFromColumnIndex($index + 1);
}

/**
 * Construye las cabeceras de la tabla según los módulos del curso.
 *
 * @param array $modules Lista de módulos.
 * @return array Cabeceras para la tabla.
 */
function build_table_headers(array $modules): array {
    // Cabeceras básicas de usuario.
    $headers = [
        get_string('userid', 'local_epicereports'),
        get_string('username', 'local_epicereports'),
        get_string('idnumber', 'local_epicereports'),
        get_string('fullname', 'local_epicereports'),
        get_string('email', 'local_epicereports'),
        get_string('firstaccess', 'local_epicereports'),
        get_string('lastaccess', 'local_epicereports'),
        get_string('groups', 'local_epicereports'),
    ];

    // Columnas por actividad.
    foreach ($modules as $module) {
        $modname = $module['modname'] ?? '';
        $name = format_string($module['name']);

        switch ($modname) {
            case 'scorm':
                $headers[] = $name . ' (' . get_string('status', 'local_epicereports') . ')';
                $headers[] = $name . ' (' . get_string('attempts', 'local_epicereports') . ')';
                $headers[] = $name . ' (' . get_string('score', 'local_epicereports') . ')';
                break;

            case 'quiz':
                $headers[] = $name . ' (' . get_string('status', 'local_epicereports') . ')';
                $headers[] = $name . ' (' . get_string('attempts', 'local_epicereports') . ')';
                $headers[] = $name . ' (' . get_string('highestgrade', 'local_epicereports') . ')';
                break;

            case 'assign':
                $headers[] = $name . ' (' . get_string('status', 'local_epicereports') . ')';
                $headers[] = $name . ' (' . get_string('submission', 'local_epicereports') . ')';
                $headers[] = $name . ' (' . get_string('submissiondate', 'local_epicereports') . ')';
                $headers[] = $name . ' (' . get_string('grade', 'local_epicereports') . ')';
                break;

            default:
                $headers[] = $name . ' (' . get_string('status', 'local_epicereports') . ')';
                break;
        }
    }

    // Resumen final del usuario.
    $headers[] = get_string('certificate', 'local_epicereports');
    $headers[] = get_string('progress', 'local_epicereports');
    $headers[] = get_string('finalgrade', 'local_epicereports');
    $headers[] = get_string('completiondate', 'local_epicereports');
    $headers[] = get_string('completionstatus', 'local_epicereports');

    return $headers;
}

/**
 * Construye una fila de datos para un usuario.
 *
 * @param object $user Datos del usuario.
 * @param array $modules Lista de módulos.
 * @return array Fila de datos.
 */
function build_user_row(object $user, array $modules): array {
    $row = [];

    // Datos base de usuario.
    $row[] = $user->userid ?? '';
    $row[] = $user->username ?? '';
    $row[] = $user->idnumber ?? '';
    $row[] = $user->fullname ?? '';
    $row[] = $user->email ?? '';
    $row[] = $user->primer_acceso ?? '';
    $row[] = $user->ultimo_acceso ?? '';
    $row[] = $user->grupos ?? '';

    // Datos por módulo.
    foreach ($modules as $module) {
        $modname = $module['modname'] ?? '';
        $key = 'mod_' . $module['id'];

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

                switch ($modname) {
                    case 'scorm':
                        $intentos = $detail->intentos ?? '';
                        $puntuacion = $detail->puntuacion ?? '';
                        break;

                    case 'quiz':
                        $intentos = $detail->intentos ?? '';
                        $notaquiz = $detail->nota ?? '';
                        break;

                    case 'assign':
                        $entrega = $detail->entrega ?? '';
                        $fechaentrega = $detail->fechaentrega ?? '';
                        $notaassign = $detail->nota ?? '';
                        break;
                }
            } else {
                $estado = (string)$detail;
            }
        }

        // Agregar datos según tipo de módulo.
        switch ($modname) {
            case 'scorm':
                $row[] = $estado;
                $row[] = format_empty_value($intentos);
                $row[] = format_empty_value($puntuacion);
                break;

            case 'quiz':
                $row[] = $estado;
                $row[] = format_empty_value($intentos);
                $row[] = format_empty_value($notaquiz);
                break;

            case 'assign':
                $row[] = $estado;
                $row[] = $entrega;
                $row[] = format_empty_value($fechaentrega);
                $row[] = format_empty_value($notaassign);
                break;

            default:
                $row[] = $estado;
                break;
        }
    }

    // Resumen final.
    $row[] = $user->certificado ?? '';
    $row[] = $user->porcentaje_avance ?? '';
    $row[] = $user->nota_final ?? '';
    $row[] = $user->fecha_finalizacion ?? '';
    $row[] = $user->estado_finalizacion ?? '';

    return $row;
}

/**
 * Formatea valores vacíos o nulos como '-'.
 *
 * @param mixed $value Valor a formatear.
 * @return string Valor formateado.
 */
function format_empty_value($value): string {
    if ($value === '' || $value === null || $value === 0 || $value === '0') {
        return '-';
    }
    return (string)$value;
}

// =========================================================================
// MODO PREVISUALIZACIÓN (preview=1)
// =========================================================================

if ($preview) {
    $PAGE->set_url(new moodle_url('/local/epicereports/export_course_excel.php', [
        'courseid' => $courseid,
        'preview'  => 1
    ]));
    $PAGE->set_title(get_string('pluginname', 'local_epicereports'));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_pagelayout('admin');

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('preview', 'local_epicereports'));

    if (empty($users)) {
        echo $OUTPUT->notification(get_string('nousers', 'local_epicereports'), 'info');
        echo $OUTPUT->footer();
        exit;
    }

    // Información del curso.
    echo html_writer::start_div('alert alert-info');
    echo html_writer::tag('strong', get_string('course', 'local_epicereports') . ': ');
    echo format_string($course->fullname);
    echo ' (' . get_string('enrolled', 'local_epicereports') . ': ' . $enroled . ')';
    echo html_writer::end_div();

    // Resumen estadístico.
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-header');
    echo html_writer::tag('h5', get_string('summary', 'local_epicereports'), ['class' => 'mb-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');

    $summary_table = new html_table();
    $summary_table->attributes['class'] = 'table table-sm table-bordered';
    $summary_table->head = [
        get_string('criteria', 'local_epicereports'),
        get_string('count', 'local_epicereports'),
        get_string('percentage', 'local_epicereports')
    ];
    $summary_table->data = [
        [get_string('enrolled', 'local_epicereports'), $enroled, '-'],
        [get_string('notstarted', 'local_epicereports'), $notStart, $notStartPercentStr],
        [get_string('started', 'local_epicereports'), $started, $startedPercentStr],
        [get_string('inprogress', 'local_epicereports'), $inProcess, $inProcessPercentStr],
        [get_string('completed', 'local_epicereports'), $completed, $completedPercentStr],
        [get_string('certificated', 'local_epicereports'), $certificated, $certificatedPercentStr],
    ];
    echo html_writer::table($summary_table);

    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::tag('p', get_string('previewdescription', 'local_epicereports'));

    // Botón para descargar directamente el Excel.
    $downloadurl = new moodle_url('/local/epicereports/export_course_excel.php', [
        'courseid' => $courseid
    ]);
    echo html_writer::div(
        html_writer::link($downloadurl, get_string('downloadexcel', 'local_epicereports'), [
            'class' => 'btn btn-primary mb-3'
        ]),
        'mb-3'
    );

    // Construimos la tabla de previsualización.
    $table = new html_table();
    $table->attributes['class'] = 'table table-striped table-bordered table-sm';
    $table->head = build_table_headers($modules);
    $table->data = [];

    foreach ($users as $user) {
        $table->data[] = build_user_row($user, $modules);
    }

    // Contenedor con scroll horizontal.
    echo html_writer::start_div('table-responsive');
    echo html_writer::table($table);
    echo html_writer::end_div();

    echo $OUTPUT->footer();
    exit;
}

// =========================================================================
// MODO EXPORTACIÓN (XLSX)
// =========================================================================

if (empty($users)) {
    $PAGE->set_url(new moodle_url('/local/epicereports/export_course_excel.php', [
        'courseid' => $courseid
    ]));
    $PAGE->set_title(get_string('pluginname', 'local_epicereports'));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_pagelayout('admin');

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('nousers', 'local_epicereports'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Creamos el Excel.
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título de hoja (máx 31 caracteres, sin caracteres especiales).
$sheetTitle = preg_replace('/[\\\\\\/*?:\\[\\]]/', '', $course->shortname);
$sheet->setTitle(mb_substr($sheetTitle, 0, 31));

// Metadatos del documento.
$spreadsheet->getProperties()
    ->setCreator('EpicE Reports')
    ->setLastModifiedBy('EpicE Reports')
    ->setTitle(get_string('coursereport', 'local_epicereports') . ' - ' . $course->fullname)
    ->setSubject(get_string('coursereport', 'local_epicereports'))
    ->setDescription(get_string('generatedby', 'local_epicereports') . ' ' . date('Y-m-d H:i:s'));

// =========================================================================
// ESTILOS
// =========================================================================

$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2E75B6']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
];

$summaryHeaderStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4']
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

$summaryLabelStyle = [
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

$summaryDataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

$dataHeaderStyle = [
    'font' => [
        'bold' => true,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'D9E2F3']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
];

$dataCellStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC'],
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

// =========================================================================
// SECCIÓN 1: INFORMACIÓN DEL CURSO (Filas 1-2)
// =========================================================================

$sheet->setCellValue('A1', get_string('course', 'local_epicereports'));
$sheet->setCellValue('B1', format_string($course->fullname));
$sheet->setCellValue('C1', get_string('courseid', 'local_epicereports'));
$sheet->setCellValue('D1', $courseid);
$sheet->setCellValue('E1', get_string('exportdate', 'local_epicereports'));
$sheet->setCellValue('F1', date('Y-m-d H:i:s'));

$sheet->getStyle('A1')->getFont()->setBold(true);
$sheet->getStyle('C1')->getFont()->setBold(true);
$sheet->getStyle('E1')->getFont()->setBold(true);

// =========================================================================
// SECCIÓN 2: RESUMEN ESTADÍSTICO (Filas 4-10)
// =========================================================================

// Título de la sección.
$sheet->setCellValue('A3', get_string('summary', 'local_epicereports'));
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);

// Encabezados de la tabla de resumen.
$sheet->setCellValue('A4', get_string('criteria', 'local_epicereports'));
$sheet->setCellValue('B4', get_string('count', 'local_epicereports'));
$sheet->setCellValue('C4', get_string('percentage', 'local_epicereports'));
$sheet->getStyle('A4:C4')->applyFromArray($summaryHeaderStyle);

// Datos del resumen.
$summaryData = [
    [get_string('enrolled', 'local_epicereports'), $enroled, '-'],
    [get_string('notstarted', 'local_epicereports'), $notStart, $notStartPercentStr],
    [get_string('started', 'local_epicereports'), $started, $startedPercentStr],
    [get_string('inprogress', 'local_epicereports'), $inProcess, $inProcessPercentStr],
    [get_string('completed', 'local_epicereports'), $completed, $completedPercentStr],
    [get_string('certificated', 'local_epicereports'), $certificated, $certificatedPercentStr],
];

$summaryRow = 5;
foreach ($summaryData as $data) {
    $sheet->setCellValue('A' . $summaryRow, $data[0]);
    $sheet->setCellValue('B' . $summaryRow, $data[1]);
    $sheet->setCellValue('C' . $summaryRow, $data[2]);

    $sheet->getStyle('A' . $summaryRow)->applyFromArray($summaryLabelStyle);
    $sheet->getStyle('B' . $summaryRow . ':C' . $summaryRow)->applyFromArray($summaryDataStyle);

    $summaryRow++;
}

// Ajustar anchos de columnas del resumen.
$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(15);

// =========================================================================
// SECCIÓN 3: DATOS DE USUARIOS (A partir de fila 12)
// =========================================================================

$dataStartRow = 12;

// Título de la sección.
$sheet->setCellValue('A' . ($dataStartRow - 1), get_string('userdata', 'local_epicereports'));
$sheet->getStyle('A' . ($dataStartRow - 1))->getFont()->setBold(true)->setSize(12);

// Cabeceras de la tabla.
$headers = build_table_headers($modules);
$col = 0;

foreach ($headers as $header) {
    $colLetter = get_excel_column($col);
    $sheet->setCellValue($colLetter . $dataStartRow, $header);
    $col++;
}

// Aplicar estilo a las cabeceras.
$lastColLetter = get_excel_column($col - 1);
$sheet->getStyle('A' . $dataStartRow . ':' . $lastColLetter . $dataStartRow)
    ->applyFromArray($dataHeaderStyle);

// Altura de fila para cabeceras.
$sheet->getRowDimension($dataStartRow)->setRowHeight(30);

// =========================================================================
// FILAS DE DATOS DE USUARIOS
// =========================================================================

$rownum = $dataStartRow + 1;

foreach ($users as $user) {
    $rowData = build_user_row($user, $modules);
    $col = 0;

    foreach ($rowData as $value) {
        $colLetter = get_excel_column($col);
        $sheet->setCellValue($colLetter . $rownum, $value);
        $col++;
    }

    // Aplicar estilo a la fila (bordes ligeros).
    $sheet->getStyle('A' . $rownum . ':' . $lastColLetter . $rownum)
        ->applyFromArray($dataCellStyle);

    // Alternar colores de fondo para mejor legibilidad.
    if ($rownum % 2 === 0) {
        $sheet->getStyle('A' . $rownum . ':' . $lastColLetter . $rownum)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFF5F5F5');
    }

    $rownum++;
}

// =========================================================================
// AJUSTES FINALES
// =========================================================================

// Autoajustar ancho de columnas (desde columna D en adelante para evitar
// que las primeras columnas queden demasiado anchas).
for ($i = 0; $i < $col; $i++) {
    $colLetter = get_excel_column($i);

    if ($i < 3) {
        // Primeras columnas: ancho fijo.
        $widths = [10, 15, 15]; // ID, Username, IDNumber.
        $sheet->getColumnDimension($colLetter)->setWidth($widths[$i] ?? 15);
    } else if ($i === 3) {
        // Nombre completo: más ancho.
        $sheet->getColumnDimension($colLetter)->setWidth(30);
    } else if ($i === 4) {
        // Email.
        $sheet->getColumnDimension($colLetter)->setWidth(35);
    } else {
        // Resto: autosize con límite.
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    }
}

// Congelar paneles (fijar cabeceras).
$sheet->freezePane('A' . ($dataStartRow + 1));

// Filtros automáticos.
$sheet->setAutoFilter('A' . $dataStartRow . ':' . $lastColLetter . ($rownum - 1));

// =========================================================================
// GENERAR Y DESCARGAR ARCHIVO
// =========================================================================

// Nombre de archivo seguro.
$filename = clean_filename('reporte_curso_' . $courseid . '_' . date('Ymd_His') . '.xlsx');

// Cabeceras HTTP para descarga.
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Limpiar cualquier salida previa.
if (ob_get_length()) {
    ob_end_clean();
}

// Escribir y enviar.
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Liberar memoria.
$spreadsheet->disconnectWorksheets();
unset($spreadsheet);

exit;