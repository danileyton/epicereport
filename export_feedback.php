<?php
/**
 * Exportar respuestas de feedback a Excel
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
    
    // Crear hoja para este feedback.
    $sheet = $spreadsheet->createSheet($sheetIndex);
    $sheetTitle = preg_replace('/[\\\\\\/*?:\\[\\]]/', '', $feedback->name);
    $sheet->setTitle(mb_substr($sheetTitle, 0, 31));
    $sheetIndex++;
    
    // =========================================================================
    // CABECERAS
    // =========================================================================
    
    $headers = [
        get_string('responsesnumber', 'local_epicereports'),
    ];
    
    // Añadir cabeceras de preguntas (formato similar al de Moodle).
    foreach ($question_items as $item) {
        // Formato: "(N. Pregunta) Pregunta"
        $header_text = '(' . $item->position . '. ' . format_string($item->name) . ')';
        // Truncar si es muy largo.
        if (strlen($header_text) > 100) {
            $header_text = substr($header_text, 0, 97) . '...';
        }
        $headers[] = $header_text;
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
        
        // Número de respuesta.
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $response_number);
        
        // Respuestas a cada pregunta.
        foreach ($question_items as $item) {
            $value_record = $DB->get_record('feedback_value', [
                'completed' => $completed->id,
                'item' => $item->id
            ]);
            
            $display_value = '';
            
            if ($value_record && $value_record->value !== null && $value_record->value !== '') {
                $raw_value = $value_record->value;
                
                // Procesar según el tipo de pregunta.
                switch ($item->typ) {
                    case 'multichoicerated':
                        // Formato almacenado: "valor<<<<<indice" (ej: "5<<<<<1")
                        // Necesitamos extraer solo el valor (la parte antes de <<<<<)
                        $display_value = extract_rated_value($raw_value);
                        break;
                        
                    case 'multichoice':
                        // Para multichoice normal, el valor es el índice de la opción.
                        // Podemos mostrar el índice o el texto de la opción.
                        $display_value = extract_multichoice_value($item, $raw_value);
                        break;
                        
                    case 'numeric':
                        // Valor numérico directo.
                        $display_value = $raw_value;
                        break;
                        
                    case 'textarea':
                    case 'textfield':
                        // Texto libre.
                        $display_value = $raw_value;
                        break;
                        
                    default:
                        $display_value = $raw_value;
                        break;
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
    
    // Autoajustar columnas.
    // Primera columna (número de respuesta) - ancho fijo.
    $sheet->getColumnDimension('A')->setWidth(15);
    
    // Columnas de preguntas.
    for ($i = 2; $i < $col; $i++) {
        $colLetter = Coordinate::stringFromColumnIndex($i);
        // Ancho fijo para preguntas de escala, más ancho para texto.
        $item_index = $i - 2;
        if (isset($question_items[$item_index])) {
            $item_type = array_values($question_items)[$item_index]->typ ?? '';
            if (in_array($item_type, ['textarea', 'textfield'])) {
                $sheet->getColumnDimension($colLetter)->setWidth(50);
            } else {
                $sheet->getColumnDimension($colLetter)->setWidth(12);
            }
        } else {
            $sheet->getColumnDimension($colLetter)->setWidth(15);
        }
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
$filename = clean_filename('feedback_curso_' . $courseid . '_' . date('Ymd_His') . '.xlsx');

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

// =========================================================================
// FUNCIONES AUXILIARES
// =========================================================================

/**
 * Extrae el valor numérico de una respuesta multichoicerated.
 * 
 * El formato almacenado es "valor<<<<<indice" (ej: "5<<<<<1")
 * Necesitamos extraer solo el valor (la parte antes de <<<<<).
 *
 * @param string $raw_value El valor raw almacenado.
 * @return string El valor limpio.
 */
function extract_rated_value(string $raw_value): string {
    // Si contiene el separador <<<<<, extraer la primera parte.
    if (strpos($raw_value, '<<<<<') !== false) {
        $parts = explode('<<<<<', $raw_value);
        return trim($parts[0]);
    }
    
    // Si no tiene el separador, devolver el valor tal cual.
    return $raw_value;
}

/**
 * Extrae el valor de una respuesta multichoice.
 * 
 * Para multichoice, el valor almacenado es el índice de la opción (1-based).
 * Podemos devolver el índice o buscar el texto de la opción.
 *
 * @param object $item El ítem de feedback.
 * @param string $raw_value El valor raw almacenado.
 * @return string El valor procesado.
 */
function extract_multichoice_value(object $item, string $raw_value): string {
    // Si está vacío, devolver vacío.
    if (empty($raw_value)) {
        return '';
    }
    
    // Si contiene el separador <<<<<, es un multichoicerated mal clasificado.
    if (strpos($raw_value, '<<<<<') !== false) {
        return extract_rated_value($raw_value);
    }
    
    // Para multichoice simple, el valor es el índice.
    // Podríamos devolver el texto de la opción, pero por consistencia
    // con el reporte de Moodle, devolvemos el valor numérico.
    return $raw_value;
}

/**
 * Obtiene el texto de una opción de multichoice en feedback.
 * (Función auxiliar por si se necesita mostrar texto en lugar de número)
 *
 * @param object $item El ítem de feedback.
 * @param string $value El valor almacenado (índice de la opción).
 * @return string El texto de la opción o el valor original.
 */
function get_feedback_choice_text(object $item, string $value): string {
    if (empty($value) || !is_numeric($value)) {
        return $value;
    }
    
    // El campo 'presentation' contiene las opciones.
    // Formato típico: "r>>>>>opcion1|opcion2|opcion3" o "c>>>>>opcion1|opcion2"
    $presentation = $item->presentation;
    
    // Remover prefijos como "r>>>>>" o "c>>>>>" o "d>>>>>"
    $presentation = preg_replace('/^[rcd]>{1,}/', '', $presentation);
    
    // Separar opciones.
    $options = explode('|', $presentation);
    
    // El valor es el índice (1-based).
    $index = (int)$value - 1;
    
    if (isset($options[$index])) {
        return trim($options[$index]);
    }
    
    return $value;
}