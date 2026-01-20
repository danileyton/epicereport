<?php
/**
 * Exportar reporte del alumno a PDF
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tcpdf/tcpdf.php');

use local_epicereports\student_helper;

$userid = required_param('id', PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/epicereports:view', $context);

// Get student data.
$student = student_helper::get_student_info($userid);

if (!$student) {
    throw new moodle_exception('invaliduserid', 'error');
}

// Get courses summary and enrolled courses.
$summary = student_helper::get_student_courses_summary($userid);
$courses = student_helper::get_student_enrolled_courses($userid);

// Create PDF.
class StudentReportPDF extends TCPDF {
    
    protected $studentName = '';
    protected $reportTitle = '';
    
    public function setStudentName($name) {
        $this->studentName = $name;
    }
    
    public function setReportTitle($title) {
        $this->reportTitle = $title;
    }
    
    // Page header.
    public function Header() {
        // Logo/Brand.
        $this->SetFillColor(30, 58, 95); // #1e3a5f
        $this->Rect(0, 0, $this->getPageWidth(), 25, 'F');
        
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetXY(15, 8);
        $this->Cell(0, 10, 'EpicE Reports', 0, 0, 'L');
        
        $this->SetFont('helvetica', '', 10);
        $this->SetXY(15, 15);
        $this->Cell(0, 5, $this->reportTitle, 0, 0, 'L');
        
        // Date.
        $this->SetXY(-50, 10);
        $this->Cell(35, 5, userdate(time(), '%d/%m/%Y'), 0, 0, 'R');
        
        $this->SetTextColor(0, 0, 0);
        $this->Ln(20);
    }
    
    // Page footer.
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(100, 116, 139);
        
        // Page number.
        $this->Cell(0, 10, 
            get_string('page', 'local_epicereports') . ' ' . $this->getAliasNumPage() . 
            ' ' . get_string('of', 'local_epicereports') . ' ' . $this->getAliasNbPages(), 
            0, 0, 'C');
    }
}

// Initialize PDF.
$pdf = new StudentReportPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->setStudentName($student->fullname);
$pdf->setReportTitle(get_string('studentreportpdf', 'local_epicereports'));

// Set document information.
$pdf->SetCreator('EpicE Reports');
$pdf->SetAuthor('Moodle');
$pdf->SetTitle(get_string('studentreportpdf', 'local_epicereports') . ' - ' . $student->fullname);
$pdf->SetSubject(get_string('studentreportpdf', 'local_epicereports'));

// Set margins.
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(15);

// Set auto page breaks.
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page.
$pdf->AddPage();

// Colors.
$primaryColor = [30, 58, 95];      // #1e3a5f
$accentColor = [14, 165, 233];     // #0ea5e9
$successColor = [16, 185, 129];    // #10b981
$warningColor = [245, 158, 11];    // #f59e0b
$dangerColor = [239, 68, 68];      // #ef4444
$grayColor = [100, 116, 139];      // #64748b
$lightGray = [241, 245, 249];      // #f1f5f9

// =====================================================
// SECTION 1: Student Information
// =====================================================

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
$pdf->Cell(0, 10, get_string('studentinfo', 'local_epicereports'), 0, 1, 'L');

$pdf->SetDrawColor(226, 232, 240);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// Student details.
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

$studentDetails = [
    [get_string('fullname', 'local_epicereports'), $student->fullname],
    [get_string('email', 'local_epicereports'), $student->email],
    [get_string('idnumber', 'local_epicereports'), $student->idnumber ?: '-'],
    [get_string('company', 'local_epicereports'), $student->company ?: '-'],
    [get_string('cohort', 'local_epicereports'), $student->cohorts ?: '-'],
    [get_string('registeredon', 'local_epicereports'), $student->timecreated ? userdate($student->timecreated, '%d/%m/%Y') : '-'],
    [get_string('lastlogin', 'local_epicereports'), $student->lastaccess ? userdate($student->lastaccess, '%d/%m/%Y %H:%M') : '-'],
];

foreach ($studentDetails as $detail) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor($grayColor[0], $grayColor[1], $grayColor[2]);
    $pdf->Cell(50, 7, $detail[0] . ':', 0, 0, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 7, $detail[1], 0, 1, 'L');
}

$pdf->Ln(8);

// =====================================================
// SECTION 2: Courses Summary
// =====================================================

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
$pdf->Cell(0, 10, get_string('coursessummary', 'local_epicereports'), 0, 1, 'L');

$pdf->SetDrawColor(226, 232, 240);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// Summary boxes.
$boxWidth = 40;
$boxHeight = 20;
$startX = 15;
$currentX = $startX;

// Helper function to draw stat box.
function drawStatBox($pdf, $x, $y, $width, $height, $value, $label, $bgColor) {
    // Background.
    $pdf->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
    $pdf->RoundedRect($x, $y, $width, $height, 2, '1111', 'F');
    
    // Value.
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->SetXY($x, $y + 2);
    $pdf->Cell($width, 10, $value, 0, 0, 'C');
    
    // Label.
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->SetXY($x, $y + 12);
    $pdf->Cell($width, 6, strtoupper($label), 0, 0, 'C');
}

$boxY = $pdf->GetY();

// Total.
drawStatBox($pdf, $currentX, $boxY, $boxWidth, $boxHeight, 
    $summary['total'], 
    get_string('totalenrolled', 'local_epicereports'), 
    [219, 234, 254]); // blue-100
$currentX += $boxWidth + 5;

// Completed.
drawStatBox($pdf, $currentX, $boxY, $boxWidth, $boxHeight, 
    $summary['completed'], 
    get_string('coursescompleted', 'local_epicereports'), 
    [209, 250, 229]); // green-100
$currentX += $boxWidth + 5;

// In Progress.
drawStatBox($pdf, $currentX, $boxY, $boxWidth, $boxHeight, 
    $summary['in_progress'], 
    get_string('coursesinprogress', 'local_epicereports'), 
    [254, 243, 199]); // yellow-100
$currentX += $boxWidth + 5;

// Not Started.
drawStatBox($pdf, $currentX, $boxY, $boxWidth, $boxHeight, 
    $summary['not_started'], 
    get_string('coursesnotstarted', 'local_epicereports'), 
    [254, 226, 226]); // red-100

$pdf->SetY($boxY + $boxHeight + 10);

// Completion rate bar.
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor($grayColor[0], $grayColor[1], $grayColor[2]);
$pdf->Cell(40, 7, get_string('completionrate', 'local_epicereports') . ':', 0, 0, 'L');

$barWidth = 100;
$barHeight = 6;
$barX = $pdf->GetX();
$barY = $pdf->GetY() + 1;

// Background bar.
$pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
$pdf->RoundedRect($barX, $barY, $barWidth, $barHeight, 1, '1111', 'F');

// Progress bar.
$progressWidth = ($summary['completion_rate'] / 100) * $barWidth;
if ($progressWidth > 0) {
    $pdf->SetFillColor($successColor[0], $successColor[1], $successColor[2]);
    $pdf->RoundedRect($barX, $barY, $progressWidth, $barHeight, 1, '1111', 'F');
}

// Percentage text.
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetX($barX + $barWidth + 5);
$pdf->Cell(20, 7, $summary['completion_rate'] . '%', 0, 1, 'L');

$pdf->Ln(8);

// =====================================================
// SECTION 3: Enrolled Courses
// =====================================================

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
$pdf->Cell(0, 10, get_string('enrolledcourses', 'local_epicereports'), 0, 1, 'L');

$pdf->SetDrawColor(226, 232, 240);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

if (empty($courses)) {
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->SetTextColor($grayColor[0], $grayColor[1], $grayColor[2]);
    $pdf->Cell(0, 10, get_string('nocourses', 'local_epicereports'), 0, 1, 'C');
} else {
    // Table header.
    $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->SetFont('helvetica', 'B', 8);
    
    $colWidths = [70, 25, 25, 20, 25, 25];
    $headers = [
        get_string('coursename', 'local_epicereports'),
        get_string('status', 'local_epicereports'),
        get_string('progress', 'local_epicereports'),
        get_string('finalgrade', 'local_epicereports'),
        get_string('completiondate', 'local_epicereports'),
        get_string('certificate', 'local_epicereports')
    ];
    
    $pdf->SetX(15);
    for ($i = 0; $i < count($headers); $i++) {
        $pdf->Cell($colWidths[$i], 8, $headers[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Table rows.
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    
    $rowCount = 0;
    foreach ($courses as $course) {
        // Alternate row colors.
        if ($rowCount % 2 == 0) {
            $pdf->SetFillColor(255, 255, 255);
        } else {
            $pdf->SetFillColor(248, 250, 252);
        }
        
        $pdf->SetX(15);
        
        // Course name (truncate if too long).
        $courseName = $course->fullname;
        if (strlen($courseName) > 35) {
            $courseName = substr($courseName, 0, 32) . '...';
        }
        $pdf->Cell($colWidths[0], 7, $courseName, 1, 0, 'L', true);
        
        // Status.
        $statusText = get_string('notstarted', 'local_epicereports');
        if ($course->status === 'completed') {
            $statusText = get_string('completed', 'local_epicereports');
        } else if ($course->status === 'in_progress') {
            $statusText = get_string('inprogress', 'local_epicereports');
        }
        $pdf->Cell($colWidths[1], 7, $statusText, 1, 0, 'C', true);
        
        // Progress.
        $pdf->Cell($colWidths[2], 7, round($course->progress ?? 0) . '%', 1, 0, 'C', true);
        
        // Grade.
        $pdf->Cell($colWidths[3], 7, $course->finalgrade ?? '-', 1, 0, 'C', true);
        
        // Completion date.
        $completiondate = '-';
        if (!empty($course->completiondate)) {
            $completiondate = userdate($course->completiondate, '%d/%m/%Y');
        }
        $pdf->Cell($colWidths[4], 7, $completiondate, 1, 0, 'C', true);
        
        // Certificate.
        $certText = '-';
        if (!empty($course->certificate)) {
            $certText = get_string('yes', 'local_epicereports');
        }
        $pdf->Cell($colWidths[5], 7, $certText, 1, 0, 'C', true);
        
        $pdf->Ln();
        $rowCount++;
        
        // Check for page break.
        if ($pdf->GetY() > 260) {
            $pdf->AddPage();
            
            // Repeat header.
            $pdf->SetFillColor($lightGray[0], $lightGray[1], $lightGray[2]);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('helvetica', 'B', 8);
            
            $pdf->SetX(15);
            for ($i = 0; $i < count($headers); $i++) {
                $pdf->Cell($colWidths[$i], 8, $headers[$i], 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(0, 0, 0);
        }
    }
}

// =====================================================
// Footer note
// =====================================================
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor($grayColor[0], $grayColor[1], $grayColor[2]);
$pdf->Cell(0, 5, get_string('generatedon', 'local_epicereports') . ': ' . userdate(time(), '%d/%m/%Y %H:%M'), 0, 1, 'R');

// Output PDF.
$filename = 'reporte_alumno_' . $student->id . '_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');
exit;
