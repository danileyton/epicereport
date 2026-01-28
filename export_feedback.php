<?php
/**
 * Exportar respuestas de feedback a Excel
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Parámetros.
$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$feedbackid = optional_param('feedbackid', 0, PARAM_INT);

// Validar.
if ($courseid <= 0) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// Cargar curso y contexto.
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/epicereports:view', $context);

// Verificar que el módulo feedback existe.
global $DB;
$dbman = $DB->get_manager();

if (!$dbman->table_exists('feedback') || !$dbman->table_exists('feedback_completed')) {
    throw new moodle_exception('error', 'local_epicereports', '', null,
        'El módulo feedback no está instalado en esta plataforma.');
}

// Obtener instancias de feedback en el curso.
if ($feedbackid > 0) {
    $feedbacks = $DB->get_records('feedback', ['id' => $feedbackid, 'course' => $courseid]);
} else if ($cmid > 0) {
    $cm = get_coursemodule_from_id('feedback', $cmid, $courseid);
    if ($cm) {
        $feedbacks = [$cm->instance => $DB->get_record('feedback', ['id' => $cm->instance])];
    } else {
        $feedbacks = [];
    }
} else {
    $feedbacks = $DB->get_records('feedback', ['course' => $courseid]);
}

if (empty($feedbacks)) {
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/epicereports/export_feedback.php', ['courseid' => $courseid]));
    $PAGE->set_pagelayout('report');
    $PAGE->set_title(get_string('pluginname', 'local_epicereports'));
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('nofeedback', 'local_epicereports'), 'warning');
    echo $OUTPUT->single_button(
        new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]),
        get_string('back', 'local_epicereports'),
        'get'
    );
    echo $OUTPUT->footer();
    exit;
}

// Crear Excel.
$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);

// Metadatos.
$spreadsheet->getProperties()
    ->setCreator('EpicE Reports')
    ->setLastModifiedBy('EpicE Reports')
    ->setTitle(get_string('feedbackreport', 'local_epicereports') . ' - ' . $course->fullname)
    ->setSubject(get_string('feedbackreport', 'local_epicereports'));

$sheetIndex = 0;

foreach ($feedbacks as $feedback) {
    // Obtener ítems (preguntas) del feedback.
    $items = $DB->get_records('feedback_item', ['feedback' => $feedback->id], 'position ASC');
    
    if (empty($items)) {
        continue;
    }
    
    // Filtrar solo ítems que son preguntas.
    $question_items = [];
    foreach ($items as $item) {
        // Tipos de preguntas válidos en mod_feedback.
        if (in_array($item->typ, ['multichoice', 'multichoicerated', 'textarea', 'textfield', 'numeric'])) {
            $question_items[] = $item;
        }
    }
    
    if (empty($question_items)) {
        continue;
    }
    
    // Obtener respuestas completadas.
    $completeds = $DB->get_records('feedback_completed', ['feedback' => $feedback->id], 'timemodified DESC');
    
    if (empty($completeds)) {
        continue;
    }
    
    // Verificar si la encuesta es anónima.
    // En Moodle, feedback->anonymous = 1 significa anónimo, 2 significa no anónimo.
    $is_anonymous = !empty($feedback->anonymous) && $feedback->anonymous == 1;
    
    // Crear hoja para este feedback.
    $sheet = $spreadsheet->createSheet($sheetIndex);
    $sheetTitle = preg_replace('/[\\\\\\/*?:\\[\\]]/', '', $feedback->name);
    $sheet->setTitle(mb_substr($sheetTitle, 0, 31));
    $sheetIndex++;
    
    // =========================================================================
    // CABECERAS
    // =========================================================================
    
    $headers = [];
    
    // Si NO es anónima, incluir datos del usuario.
    if (!$is_anonymous) {
        $headers[] = get_string('user', 'local_epicereports');
        $headers[] = get_string('email', 'local_epicereports');
        $headers[] = get_string('responsedate', 'local_epicereports');
    } else {
        // Si es anónima, solo número de respuesta.
        $headers[] = get_string('responsesnumber', 'local_epicereports');
    }
    
    // Añadir cabeceras de preguntas.
    foreach ($question_items as $item) {
        $headers[] = format_string($item->name);
    }
    
    // Escribir cabeceras.
    $col = 1;
    foreach ($headers as $header) {
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . '1', $header);
        $col++;
    }
    
    // Estilo de cabeceras.
    $lastCol = Coordinate::stringFromColumnIndex($col - 1);
    $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4']
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
    ]);
    
    $sheet->getRowDimension(1)->setRowHeight(40);
    
    // =========================================================================
    // DATOS
    // =========================================================================
    
    $row = 2;
    $response_number = 1;
    
    foreach ($completeds as $completed) {
        $col = 1;
        
        if (!$is_anonymous) {
            // Si NO es anónima, mostrar datos del usuario.
            $user = $DB->get_record('user', ['id' => $completed->userid]);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $user ? fullname($user) : '-');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $user ? $user->email : '-');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, userdate($completed->timemodified, '%d/%m/%Y %H:%M'));
        } else {
            // Si es anónima, solo número de respuesta.
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $response_number);
        }
        
        // Respuestas a cada pregunta.
        foreach ($question_items as $item) {
            $value_record = $DB->get_record('feedback_value', [
                'completed' => $completed->id,
                'item' => $item->id
            ]);
            
            $display_value = '';
            
            if ($value_record && $value_record->value !== null && $value_record->value !== '') {
                $raw_value = $value_record->value;
                
                // IMPORTANTE: Si el valor contiene <<<<<, SIEMPRE extraer solo la primera parte.
                // Esto maneja multichoicerated independientemente del tipo reportado.
                if (strpos($raw_value, '<<<<<') !== false) {
                    $parts = explode('<<<<<', $raw_value);
                    $display_value = trim($parts[0]);
                } else {
                    $display_value = $raw_value;
                }
            }
            
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $display_value);
        }
        
        // Estilo de fila.
        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        
        // Alternar colores.
        if ($row % 2 === 0) {
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFF5F5F5');
        }
        
        $row++;
        $response_number++;
    }
    
    // =========================================================================
    // RESUMEN
    // =========================================================================
    
    $summaryRow = $row + 1;
    $sheet->setCellValue('A' . $summaryRow, 'RESUMEN');
    $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
    
    $summaryRow++;
    $sheet->setCellValue('A' . $summaryRow, 'Total respuestas:');
    $sheet->setCellValue('B' . $summaryRow, count($completeds));
    
    if ($is_anonymous) {
        $summaryRow++;
        $sheet->setCellValue('A' . $summaryRow, 'Tipo de encuesta:');
        $sheet->setCellValue('B' . $summaryRow, 'Anónima');
    }
    
    // =========================================================================
    // FORMATO DE COLUMNAS
    // =========================================================================
    
    // Autoajustar columnas.
    $colIndex = 1;
    
    if (!$is_anonymous) {
        // Usuario, Email, Fecha.
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex++))->setWidth(25);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex++))->setWidth(30);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex++))->setWidth(18);
    } else {
        // Número de respuesta.
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex++))->setWidth(12);
    }
    
    // Columnas de preguntas.
    foreach ($question_items as $item) {
        if (in_array($item->typ, ['textarea', 'textfield'])) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setWidth(50);
        } else {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setWidth(15);
        }
        $colIndex++;
    }
    
    // Congelar paneles.
    $sheet->freezePane('B2');
    
    // Filtros automáticos.
    $sheet->setAutoFilter('A1:' . $lastCol . ($row - 1));
}

// Verificar que hay al menos una hoja con datos.
if ($spreadsheet->getSheetCount() === 0) {
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/epicereports/export_feedback.php', ['courseid' => $courseid]));
    $PAGE->set_pagelayout('report');
    $PAGE->set_title(get_string('pluginname', 'local_epicereports'));
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('nofeedbackresponses', 'local_epicereports'), 'info');
    echo $OUTPUT->single_button(
        new moodle_url('/local/epicereports/course_detail.php', ['id' => $courseid]),
        get_string('back', 'local_epicereports'),
        'get'
    );
    echo $OUTPUT->footer();
    exit;
}

// Activar la primera hoja.
$spreadsheet->setActiveSheetIndex(0);

// Nombre del archivo.
$filename = clean_filename('encuestas_' . $course->shortname . '_' . date('Ymd_His') . '.xlsx');

// Cabeceras HTTP.
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Limpiar buffer.
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
