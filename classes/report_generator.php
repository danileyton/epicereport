<?php
/**
 * Report Generator class for local_epicereports
 *
 * Generates Excel files for course reports and feedback surveys
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_epicereports;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/phpspreadsheet/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Class report_generator
 *
 * Generates Excel report files for scheduled email attachments
 */
class report_generator {

    /** @var string Base path for temporary report files */
    const TEMP_PATH = 'local_epicereports/reports';

    /**
     * Generate course report Excel file.
     *
     * @param int $courseid The course ID.
     * @param string|null $filename Optional custom filename (without extension).
     * @return array ['success' => bool, 'filepath' => string, 'filename' => string, 'error' => string]
     */
    public static function generate_course_excel(int $courseid, ?string $filename = null): array {
        global $CFG;

        try {
            // Get course data.
            $excel_data = helper::get_course_data_for_excel($courseid);

            $course  = $excel_data['course']  ?? null;
            $modules = $excel_data['modules'] ?? [];
            $users   = $excel_data['users']   ?? [];

            if (!$course) {
                return [
                    'success'  => false,
                    'filepath' => '',
                    'filename' => '',
                    'error'    => 'Curso no encontrado',
                ];
            }

            // Calculate summary statistics.
            $stats = self::calculate_course_stats($users);

            // Create spreadsheet.
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(mb_substr(clean_filename($course->shortname), 0, 31));

            // Build the Excel content.
            self::build_course_excel_content($sheet, $course, $modules, $users, $stats);

            // Generate filename.
            if (!$filename) {
                $filename = 'reporte_curso_' . $courseid . '_' . date('Ymd_His');
            }
            $filename = clean_filename($filename) . '.xlsx';

            // Save to temp directory.
            $filepath = self::save_spreadsheet($spreadsheet, $filename);

            if (!$filepath) {
                return [
                    'success'  => false,
                    'filepath' => '',
                    'filename' => '',
                    'error'    => 'Error al guardar el archivo',
                ];
            }

            return [
                'success'  => true,
                'filepath' => $filepath,
                'filename' => $filename,
                'error'    => '',
            ];

        } catch (\Exception $e) {
            return [
                'success'  => false,
                'filepath' => '',
                'filename' => '',
                'error'    => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate feedback/survey report Excel file.
     *
     * @param int $courseid The course ID.
     * @param string|null $filename Optional custom filename (without extension).
     * @return array ['success' => bool, 'filepath' => string, 'filename' => string, 'error' => string]
     */
    public static function generate_feedback_excel(int $courseid, ?string $filename = null): array {
        global $DB, $CFG;

        try {
            $course = get_course($courseid);

            // Get all feedback activities in the course.
            $feedbacks = $DB->get_records('feedback', ['course' => $courseid]);

            if (empty($feedbacks)) {
                return [
                    'success'  => false,
                    'filepath' => '',
                    'filename' => '',
                    'error'    => 'No hay actividades de feedback en este curso',
                ];
            }

            // Create spreadsheet.
            $spreadsheet = new Spreadsheet();
            $sheetindex = 0;

            foreach ($feedbacks as $feedback) {
                // Get feedback items (questions).
                $items = $DB->get_records('feedback_item', [
                    'feedback' => $feedback->id,
                ], 'position ASC');

                if (empty($items)) {
                    continue;
                }

                // Get completed responses.
                $completeds = $DB->get_records('feedback_completed', [
                    'feedback' => $feedback->id,
                ]);

                // Create or get sheet.
                if ($sheetindex > 0) {
                    $sheet = $spreadsheet->createSheet();
                } else {
                    $sheet = $spreadsheet->getActiveSheet();
                }

                $sheettitle = mb_substr(clean_filename($feedback->name), 0, 31);
                $sheet->setTitle($sheettitle);

                // Build feedback content.
                self::build_feedback_excel_content($sheet, $feedback, $items, $completeds);

                $sheetindex++;
            }

            if ($sheetindex === 0) {
                return [
                    'success'  => false,
                    'filepath' => '',
                    'filename' => '',
                    'error'    => 'No hay preguntas configuradas en las encuestas',
                ];
            }

            // Generate filename.
            if (!$filename) {
                $filename = 'reporte_encuestas_' . $courseid . '_' . date('Ymd_His');
            }
            $filename = clean_filename($filename) . '.xlsx';

            // Save to temp directory.
            $filepath = self::save_spreadsheet($spreadsheet, $filename);

            if (!$filepath) {
                return [
                    'success'  => false,
                    'filepath' => '',
                    'filename' => '',
                    'error'    => 'Error al guardar el archivo',
                ];
            }

            return [
                'success'  => true,
                'filepath' => $filepath,
                'filename' => $filename,
                'error'    => '',
            ];

        } catch (\Exception $e) {
            return [
                'success'  => false,
                'filepath' => '',
                'filename' => '',
                'error'    => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate all reports for a schedule.
     *
     * @param object $schedule The schedule object.
     * @return array Array of generated files with 'course' and 'feedback' keys.
     */
    public static function generate_schedule_reports(object $schedule): array {
        $results = [
            'course'   => null,
            'feedback' => null,
            'files'    => [],
            'errors'   => [],
        ];

        $course = get_course($schedule->courseid);
        $datestr = date('Ymd');

        // Generate course report if enabled.
        if (!empty($schedule->include_course_report)) {
            $filename = 'reporte_' . clean_filename($course->shortname) . '_' . $datestr;
            $result = self::generate_course_excel($schedule->courseid, $filename);

            $results['course'] = $result;

            if ($result['success']) {
                $results['files'][] = [
                    'type'     => 'course',
                    'filepath' => $result['filepath'],
                    'filename' => $result['filename'],
                ];
            } else {
                $results['errors'][] = 'Reporte de curso: ' . $result['error'];
            }
        }

        // Generate feedback report if enabled.
        if (!empty($schedule->include_feedback_report)) {
            $filename = 'encuestas_' . clean_filename($course->shortname) . '_' . $datestr;
            $result = self::generate_feedback_excel($schedule->courseid, $filename);

            $results['feedback'] = $result;

            if ($result['success']) {
                $results['files'][] = [
                    'type'     => 'feedback',
                    'filepath' => $result['filepath'],
                    'filename' => $result['filename'],
                ];
            } else {
                $results['errors'][] = 'Reporte de encuestas: ' . $result['error'];
            }
        }

        return $results;
    }

    /**
     * Clean up old temporary report files.
     *
     * @param int $maxage Maximum age in seconds (default: 24 hours).
     * @return int Number of files deleted.
     */
    public static function cleanup_old_files(int $maxage = 86400): int {
        global $CFG;

        $tempdir = $CFG->tempdir . '/' . self::TEMP_PATH;

        if (!is_dir($tempdir)) {
            return 0;
        }

        $deleted = 0;
        $now = time();

        $files = glob($tempdir . '/*.xlsx');
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxage) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Get the temporary directory path for reports.
     *
     * @return string Full path to temp directory.
     */
    public static function get_temp_dir(): string {
        global $CFG;
        return $CFG->tempdir . '/' . self::TEMP_PATH;
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Calculate course statistics from user data.
     *
     * @param array $users Array of user objects.
     * @return array Statistics array.
     */
    private static function calculate_course_stats(array $users): array {
        $stats = [
            'enrolled'     => count($users),
            'not_started'  => 0,
            'started'      => 0,
            'in_progress'  => 0,
            'completed'    => 0,
            'certificated' => 0,
        ];

        foreach ($users as $user) {
            $hasstarted     = !empty($user->primer_acceso);
            $iscompleted    = !empty($user->estado_finalizacion) && $user->estado_finalizacion === 'Completado';
            $hascertificate = !empty($user->certificado) && $user->certificado !== '-';

            if ($hasstarted) {
                $stats['started']++;
                if ($iscompleted) {
                    $stats['completed']++;
                } else {
                    $stats['in_progress']++;
                }
            } else {
                $stats['not_started']++;
            }

            if ($hascertificate) {
                $stats['certificated']++;
            }
        }

        // Calculate percentages.
        $stats['not_started_pct']  = $stats['enrolled'] > 0
            ? round(($stats['not_started'] / $stats['enrolled']) * 100, 2) : 0;
        $stats['started_pct']      = $stats['enrolled'] > 0
            ? round(($stats['started'] / $stats['enrolled']) * 100, 2) : 0;
        $stats['in_progress_pct']  = $stats['started'] > 0
            ? round(($stats['in_progress'] / $stats['started']) * 100, 2) : 0;
        $stats['completed_pct']    = $stats['started'] > 0
            ? round(($stats['completed'] / $stats['started']) * 100, 2) : 0;
        $stats['certificated_pct'] = $stats['started'] > 0
            ? round(($stats['certificated'] / $stats['started']) * 100, 2) : 0;

        return $stats;
    }

    /**
     * Build course Excel content.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param object $course
     * @param array $modules
     * @param array $users
     * @param array $stats
     */
    private static function build_course_excel_content($sheet, $course, $modules, $users, $stats): void {
        // Styles.
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];

        $dataStyle = [
            'font' => ['bold' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // Summary section (rows 1-7).
        $sheet->setCellValue('A1', 'Criterio');
        $sheet->setCellValue('B1', 'N°');
        $sheet->setCellValue('C1', 'Porcentaje');
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

        $summaryData = [
            ['Matriculados', $stats['enrolled'], ''],
            ['Sin iniciar', $stats['not_started'], $stats['not_started_pct'] . '%'],
            ['Iniciados', $stats['started'], $stats['started_pct'] . '%'],
            ['En proceso', $stats['in_progress'], $stats['in_progress_pct'] . '%'],
            ['Completado', $stats['completed'], $stats['completed_pct'] . '%'],
            ['Certificados', $stats['certificated'], $stats['certificated_pct'] . '%'],
        ];

        $row = 2;
        foreach ($summaryData as $data) {
            $sheet->setCellValue('A' . $row, $data[0]);
            $sheet->setCellValue('B' . $row, $data[1]);
            $sheet->setCellValue('C' . $row, $data[2]);
            $row++;
        }

        $sheet->getStyle('A2:A7')->applyFromArray($dataStyle);
        $sheet->getStyle('B2:C7')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);

        // User data section (starting row 9).
        $headerRow = 9;
        $col = 0;

        // Base headers.
        $baseHeaders = [
            'ID usuario', 'Usuario', 'ID interno / RUT', 'Nombre completo',
            'Email', 'Primer acceso', 'Último acceso', 'Grupos'
        ];

        foreach ($baseHeaders as $h) {
            $sheet->setCellValue(self::excel_col($col++) . $headerRow, $h);
        }

        // Module headers.
        foreach ($modules as $module) {
            $modname = $module['modname'] ?? '';
            $name = $module['name'];

            if ($modname === 'scorm') {
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (estado)');
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (intentos)');
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (puntuación)');
            } else if ($modname === 'quiz') {
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (estado)');
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (intentos)');
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (nota más alta)');
            } else if ($modname === 'assign') {
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (estado)');
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (entrega)');
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (fecha entrega)');
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (nota)');
            } else {
                $sheet->setCellValue(self::excel_col($col++) . $headerRow, $name . ' (estado)');
            }
        }

        // Final headers.
        $sheet->setCellValue(self::excel_col($col++) . $headerRow, 'Certificado');
        $sheet->setCellValue(self::excel_col($col++) . $headerRow, 'Avance');
        $sheet->setCellValue(self::excel_col($col++) . $headerRow, 'Nota final (%)');
        $sheet->setCellValue(self::excel_col($col++) . $headerRow, 'Fecha finalización');
        $sheet->setCellValue(self::excel_col($col++) . $headerRow, 'Estado finalización');

        // Style header row.
        $lastCol = self::excel_col($col - 1);
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFEFEF');

        // User data rows.
        $dataRow = $headerRow + 1;

        foreach ($users as $user) {
            $col = 0;

            // Base data.
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->userid ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->username ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->idnumber ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->fullname ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->email ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->primer_acceso ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->ultimo_acceso ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->grupos ?? '');

            // Module data.
            foreach ($modules as $module) {
                $modname = $module['modname'] ?? '';
                $key = 'mod_' . $module['id'];

                $estado = $intentos = $puntuacion = $nota = $entrega = $fechaentrega = '';

                if (isset($user->$key)) {
                    $detail = $user->$key;
                    if (is_object($detail)) {
                        $estado = $detail->estado ?? '';
                        if ($modname === 'scorm') {
                            $intentos = $detail->intentos ?? '';
                            $puntuacion = $detail->puntuacion ?? '';
                        } else if ($modname === 'quiz') {
                            $intentos = $detail->intentos ?? '';
                            $nota = $detail->nota ?? '';
                        } else if ($modname === 'assign') {
                            $entrega = $detail->entrega ?? '';
                            $fechaentrega = $detail->fechaentrega ?? '';
                            $nota = $detail->nota ?? '';
                        }
                    } else {
                        $estado = (string)$detail;
                    }
                }

                if ($modname === 'scorm') {
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $estado);
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $intentos ?: '-');
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $puntuacion ?: '-');
                } else if ($modname === 'quiz') {
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $estado);
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $intentos ?: '-');
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $nota ?: '-');
                } else if ($modname === 'assign') {
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $estado);
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $entrega);
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $fechaentrega ?: '-');
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $nota ?: '-');
                } else {
                    $sheet->setCellValue(self::excel_col($col++) . $dataRow, $estado);
                }
            }

            // Final data.
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->certificado ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->porcentaje_avance ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->nota_final ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->fecha_finalizacion ?? '');
            $sheet->setCellValue(self::excel_col($col++) . $dataRow, $user->estado_finalizacion ?? '');

            $dataRow++;
        }

        // Auto-size columns.
        for ($i = 0; $i < $col; $i++) {
            $sheet->getColumnDimension(self::excel_col($i))->setAutoSize(true);
        }
    }

    /**
     * Build feedback Excel content.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param object $feedback
     * @param array $items
     * @param array $completeds
     */
    private static function build_feedback_excel_content($sheet, $feedback, $items, $completeds): void {
        global $DB;

        // Header style.
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        // Build headers.
        $col = 0;
        $sheet->setCellValue(self::excel_col($col++) . '1', 'Usuario');
        $sheet->setCellValue(self::excel_col($col++) . '1', 'Email');
        $sheet->setCellValue(self::excel_col($col++) . '1', 'Fecha respuesta');

        // Question headers.
        $itemmap = [];
        foreach ($items as $item) {
            // Skip labels and pagebreaks.
            if (in_array($item->typ, ['label', 'pagebreak'])) {
                continue;
            }

            $itemmap[$item->id] = $col;
            $sheet->setCellValue(self::excel_col($col++) . '1', $item->name);
        }

        // Apply header style.
        $lastCol = self::excel_col($col - 1);
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

        // Data rows.
        $row = 2;

        foreach ($completeds as $completed) {
            $col = 0;

            // User info.
            $user = $DB->get_record('user', ['id' => $completed->userid]);
            $sheet->setCellValue(self::excel_col($col++) . $row, $user ? fullname($user) : 'Anónimo');
            $sheet->setCellValue(self::excel_col($col++) . $row, $user ? $user->email : '-');
            $sheet->setCellValue(self::excel_col($col++) . $row, userdate($completed->timemodified, '%d/%m/%Y %H:%M'));

            // Get values for this completion.
            $values = $DB->get_records('feedback_value', ['completed' => $completed->id]);
            $valuebyitem = [];
            foreach ($values as $value) {
                $valuebyitem[$value->item] = $value->value;
            }

            // Fill in responses.
            foreach ($items as $item) {
                if (in_array($item->typ, ['label', 'pagebreak'])) {
                    continue;
                }

                $itemcol = $itemmap[$item->id] ?? null;
                if ($itemcol === null) {
                    continue;
                }

                $rawvalue = $valuebyitem[$item->id] ?? '';
                $displayvalue = self::format_feedback_value($item, $rawvalue);

                $sheet->setCellValue(self::excel_col($itemcol) . $row, $displayvalue);
            }

            $row++;
        }

        // Summary section.
        $summaryRow = $row + 2;
        $sheet->setCellValue('A' . $summaryRow, 'RESUMEN');
        $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);

        $summaryRow++;
        $sheet->setCellValue('A' . $summaryRow, 'Total respuestas:');
        $sheet->setCellValue('B' . $summaryRow, count($completeds));

        // Auto-size columns.
        for ($i = 0; $i < $col; $i++) {
            $sheet->getColumnDimension(self::excel_col($i))->setAutoSize(true);
        }
    }

    /**
     * Format feedback value for display.
     *
     * @param object $item The feedback item.
     * @param string $rawvalue The raw value.
     * @return string Formatted value.
     */
    private static function format_feedback_value($item, $rawvalue): string {
        if ($rawvalue === '' || $rawvalue === null) {
            return '-';
        }

        switch ($item->typ) {
            case 'multichoice':
            case 'multichoicerated':
                // For multichoice, the value is the option index.
                // We need to parse the presentation to get the actual text.
                $options = self::parse_multichoice_options($item->presentation);
                $index = (int)$rawvalue;
                if ($index > 0 && isset($options[$index - 1])) {
                    return $options[$index - 1];
                }
                return $rawvalue;

            case 'numeric':
                return is_numeric($rawvalue) ? number_format((float)$rawvalue, 2) : $rawvalue;

            case 'textarea':
            case 'textfield':
                return $rawvalue;

            default:
                return $rawvalue;
        }
    }

    /**
     * Parse multichoice options from presentation string.
     *
     * @param string $presentation The presentation string.
     * @return array Array of option texts.
     */
    private static function parse_multichoice_options(string $presentation): array {
        // Format: "r>>>>>option1|option2|option3" or "d>>>>>option1|option2"
        // The prefix indicates the type (r=radio, d=dropdown, c=checkbox)
        $parts = explode('>>>>>', $presentation);
        $optionsstr = $parts[1] ?? $parts[0];

        return explode('|', $optionsstr);
    }

    /**
     * Save spreadsheet to temporary directory.
     *
     * @param Spreadsheet $spreadsheet
     * @param string $filename
     * @return string|null Full path to saved file, or null on failure.
     */
    private static function save_spreadsheet(Spreadsheet $spreadsheet, string $filename): ?string {
        global $CFG;

        // Ensure temp directory exists.
        $tempdir = $CFG->tempdir . '/' . self::TEMP_PATH;

        if (!is_dir($tempdir)) {
            if (!mkdir($tempdir, 0777, true)) {
                return null;
            }
        }

        $filepath = $tempdir . '/' . $filename;

        try {
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            if (file_exists($filepath)) {
                return $filepath;
            }
        } catch (\Exception $e) {
            debugging('Error saving spreadsheet: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return null;
    }

    /**
     * Convert column index to Excel letter(s).
     *
     * @param int $index 0-based column index.
     * @return string Column letter(s) (A, B, ..., Z, AA, AB, ...).
     */
    private static function excel_col(int $index): string {
        $index++;
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = (int)(($index - $mod) / 26);
        }
        return $letters;
    }
}
