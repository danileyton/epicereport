<?php
/**
 * Detalle del alumno - Reporte individual completo
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');

use local_epicereports\student_helper;

$userid = required_param('id', PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/epicereports:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/epicereports/student_detail.php', ['id' => $userid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('studentdetail', 'local_epicereports'));
$PAGE->set_heading(get_string('studentdetail', 'local_epicereports'));

// Get student data.
$student = student_helper::get_student_info($userid);

if (!$student) {
    throw new moodle_exception('invaliduserid', 'error');
}

// Get courses summary and enrolled courses.
$summary = student_helper::get_student_courses_summary($userid);
$courses = student_helper::get_student_enrolled_courses($userid);

// Chart.js configuration.
$chartdata = [
    'completed' => $summary['completed'],
    'in_progress' => $summary['in_progress'],
    'not_started' => $summary['not_started'],
    'labels' => [
        get_string('chartcompletedlabel', 'local_epicereports'),
        get_string('chartinprogresslabel', 'local_epicereports'),
        get_string('chartnotstartedlabel', 'local_epicereports')
    ]
];

// Load Chart.js via AMD.
$PAGE->requires->js_amd_inline("
require(['core/chartjs'], function(Chart) {
    var ctx = document.getElementById('studentProgressChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: " . json_encode($chartdata['labels']) . ",
                datasets: [{
                    data: [{$chartdata['completed']}, {$chartdata['in_progress']}, {$chartdata['not_started']}],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
    }
});
");

echo $OUTPUT->header();

// CSS embebido.
echo '<style>
:root {
    --epice-primary: #1e3a5f;
    --epice-primary-light: #2d5a8a;
    --epice-accent: #0ea5e9;
    --epice-success: #10b981;
    --epice-success-bg: rgba(16, 185, 129, 0.1);
    --epice-warning: #f59e0b;
    --epice-warning-bg: rgba(245, 158, 11, 0.1);
    --epice-danger: #ef4444;
    --epice-danger-bg: rgba(239, 68, 68, 0.1);
    --epice-info: #3b82f6;
    --epice-info-bg: rgba(59, 130, 246, 0.1);
    --epice-bg-card: #ffffff;
    --epice-bg-sidebar: linear-gradient(180deg, #1e3a5f 0%, #0f2744 100%);
    --epice-bg-header: linear-gradient(135deg, #1e3a5f 0%, #2d5a8a 100%);
    --epice-bg-table-header: #f1f5f9;
    --epice-bg-table-stripe: #f8fafc;
    --epice-text-primary: #1e293b;
    --epice-text-secondary: #64748b;
    --epice-text-muted: #94a3b8;
    --epice-text-inverse: #ffffff;
    --epice-border: #e2e8f0;
    --epice-border-light: #f1f5f9;
    --epice-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --epice-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --epice-radius: 8px;
    --epice-radius-md: 12px;
    --epice-radius-lg: 16px;
    --epice-transition: all 0.2s ease-in-out;
}

.epice-sidebar { background: var(--epice-bg-sidebar); border-radius: var(--epice-radius-lg); padding: 16px; box-shadow: var(--epice-shadow-md); }
.epice-sidebar-header { padding: 16px; margin-bottom: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); text-align: center; }
.epice-sidebar-logo { font-size: 1.5rem; margin-bottom: 4px; }
.epice-sidebar-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 700; margin: 0; }
.epice-sidebar-subtitle { color: rgba(255, 255, 255, 0.6); font-size: 0.7rem; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.1em; }

.epice-nav { list-style: none; padding: 0; margin: 0; }
.epice-nav-item { margin-bottom: 4px; }
.epice-nav-link { display: flex; align-items: center; padding: 10px 16px; color: rgba(255, 255, 255, 0.8) !important; text-decoration: none !important; border-radius: var(--epice-radius); transition: var(--epice-transition); font-size: 0.9rem; font-weight: 500; }
.epice-nav-link:hover { background-color: rgba(255, 255, 255, 0.1); color: var(--epice-text-inverse) !important; }
.epice-nav-link.active { background-color: var(--epice-accent); color: var(--epice-text-inverse) !important; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4); }
.epice-nav-icon { margin-right: 10px; width: 20px; text-align: center; }

.epice-card { background: var(--epice-bg-card); border-radius: var(--epice-radius-md); box-shadow: var(--epice-shadow); border: 1px solid var(--epice-border-light); margin-bottom: 24px; overflow: hidden; }
.epice-card-header { background: var(--epice-bg-header); padding: 16px 24px; }
.epice-card-title { color: var(--epice-text-inverse); font-size: 1.1rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
.epice-card-body { padding: 24px; }

.epice-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; font-size: 0.875rem; font-weight: 600; border-radius: var(--epice-radius); border: none; cursor: pointer; transition: var(--epice-transition); text-decoration: none !important; }
.epice-btn:hover { transform: translateY(-1px); }
.epice-btn-primary { background: var(--epice-primary); color: var(--epice-text-inverse) !important; }
.epice-btn-primary:hover { background: var(--epice-primary-light); box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3); }
.epice-btn-success { background: var(--epice-success); color: var(--epice-text-inverse) !important; }
.epice-btn-success:hover { background: #0d9668; }
.epice-btn-outline { background: transparent; color: var(--epice-text-secondary) !important; border: 1px solid var(--epice-border); }
.epice-btn-outline:hover { background: var(--epice-bg-table-header); color: var(--epice-text-primary) !important; }
.epice-btn-sm { padding: 6px 12px; font-size: 0.8rem; }

/* Student Profile */
.epice-student-profile { display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap; }
.epice-student-avatar { width: 100px; height: 100px; border-radius: 50%; background: var(--epice-bg-header); display: flex; align-items: center; justify-content: center; color: var(--epice-text-inverse); font-size: 2.5rem; flex-shrink: 0; }
.epice-student-info { flex: 1; min-width: 250px; }
.epice-student-name { font-size: 1.5rem; font-weight: 700; color: var(--epice-text-primary); margin: 0 0 8px 0; }
.epice-student-meta { display: flex; flex-direction: column; gap: 6px; }
.epice-student-meta-item { display: flex; align-items: center; gap: 8px; color: var(--epice-text-secondary); font-size: 0.9rem; }
.epice-student-meta-item i { width: 16px; color: var(--epice-accent); }

/* Stats Grid */
.epice-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-top: 20px; }
.epice-stat-box { background: var(--epice-bg-table-stripe); padding: 16px; border-radius: var(--epice-radius); text-align: center; }
.epice-stat-box.success { background: var(--epice-success-bg); }
.epice-stat-box.warning { background: var(--epice-warning-bg); }
.epice-stat-box.danger { background: var(--epice-danger-bg); }
.epice-stat-box.info { background: var(--epice-info-bg); }
.epice-stat-value { font-size: 1.75rem; font-weight: 700; color: var(--epice-text-primary); }
.epice-stat-box.success .epice-stat-value { color: var(--epice-success); }
.epice-stat-box.warning .epice-stat-value { color: var(--epice-warning); }
.epice-stat-box.danger .epice-stat-value { color: var(--epice-danger); }
.epice-stat-box.info .epice-stat-value { color: var(--epice-info); }
.epice-stat-label { font-size: 0.75rem; color: var(--epice-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; }

/* Chart Container */
.epice-chart-container { position: relative; height: 250px; max-width: 300px; margin: 0 auto; }

/* Table */
.epice-table-container { overflow-x: auto; }
table.epice-table { width: 100%; border-collapse: separate; border-spacing: 0; }
table.epice-table thead th { background: var(--epice-bg-table-header); color: var(--epice-text-primary); font-weight: 600; font-size: 0.8rem; padding: 14px 16px; text-align: left; border-bottom: 2px solid var(--epice-border); text-transform: uppercase; letter-spacing: 0.03em; }
table.epice-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--epice-border-light); color: var(--epice-text-primary); font-size: 0.875rem; vertical-align: middle; }
table.epice-table tbody tr:hover { background: rgba(14, 165, 233, 0.04); }
table.epice-table tbody tr:nth-child(even) { background: var(--epice-bg-table-stripe); }

/* Badges */
.epice-badge { display: inline-flex; align-items: center; padding: 4px 10px; font-size: 0.75rem; font-weight: 600; border-radius: 4px; }
.epice-badge-success { background: var(--epice-success-bg); color: var(--epice-success); }
.epice-badge-warning { background: var(--epice-warning-bg); color: var(--epice-warning); }
.epice-badge-danger { background: var(--epice-danger-bg); color: var(--epice-danger); }
.epice-badge-info { background: var(--epice-info-bg); color: var(--epice-info); }

/* Progress Bar */
.epice-progress-wrapper { display: flex; align-items: center; gap: 10px; }
.epice-progress { flex: 1; height: 8px; background: var(--epice-border-light); border-radius: 4px; overflow: hidden; }
.epice-progress-bar { height: 100%; border-radius: 4px; transition: width 0.3s ease; }
.epice-progress-success { background: var(--epice-success); }
.epice-progress-warning { background: var(--epice-warning); }
.epice-progress-danger { background: var(--epice-danger); }
.epice-progress-info { background: var(--epice-info); }
.epice-progress-label { font-size: 0.8rem; font-weight: 600; color: var(--epice-text-primary); min-width: 45px; text-align: right; }

/* Actions row */
.epice-actions-row { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }

@media (max-width: 768px) {
    .epice-student-profile { flex-direction: column; align-items: center; text-align: center; }
    .epice-student-meta { align-items: center; }
    .epice-sidebar { margin-bottom: 24px; }
}
</style>';

// Layout principal.
echo html_writer::start_div('row');

// Sidebar.
echo html_writer::start_div('col-md-3 col-lg-2 mb-4');
local_epicereports_render_sidebar('students');
echo html_writer::end_div();

// Contenido principal.
echo html_writer::start_div('col-md-9 col-lg-10');

// Actions row.
echo html_writer::start_div('epice-actions-row');
$backurl = new moodle_url('/local/epicereports/students.php');
echo html_writer::link($backurl, 
    html_writer::tag('i', '', ['class' => 'fa fa-arrow-left']) . ' ' . get_string('back', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-outline']
);
$pdfurl = new moodle_url('/local/epicereports/export_student_pdf.php', ['id' => $userid]);
echo html_writer::link($pdfurl, 
    html_writer::tag('i', '', ['class' => 'fa fa-file-pdf']) . ' ' . get_string('exporttopdf', 'local_epicereports'),
    ['class' => 'epice-btn epice-btn-primary', 'target' => '_blank']
);
echo html_writer::end_div();

// Card: Student Info.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-user']) . ' ' . get_string('studentinfo', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');

// Student profile.
echo html_writer::start_div('epice-student-profile');

// Avatar.
$initials = strtoupper(substr($student->firstname, 0, 1) . substr($student->lastname, 0, 1));
echo html_writer::tag('div', $initials, ['class' => 'epice-student-avatar']);

// Info.
echo html_writer::start_div('epice-student-info');
echo html_writer::tag('h2', s($student->fullname), ['class' => 'epice-student-name']);

echo html_writer::start_div('epice-student-meta');

// Email.
echo html_writer::tag('div',
    html_writer::tag('i', '', ['class' => 'fa fa-envelope']) . ' ' . s($student->email),
    ['class' => 'epice-student-meta-item']
);

// ID Number.
if (!empty($student->idnumber)) {
    echo html_writer::tag('div',
        html_writer::tag('i', '', ['class' => 'fa fa-id-card']) . ' ' . s($student->idnumber),
        ['class' => 'epice-student-meta-item']
    );
}

// Company.
if (!empty($student->company)) {
    echo html_writer::tag('div',
        html_writer::tag('i', '', ['class' => 'fa fa-building']) . ' ' . s($student->company),
        ['class' => 'epice-student-meta-item']
    );
}

// Cohorts.
if (!empty($student->cohorts)) {
    echo html_writer::tag('div',
        html_writer::tag('i', '', ['class' => 'fa fa-users']) . ' ' . s($student->cohorts),
        ['class' => 'epice-student-meta-item']
    );
}

// Registration date.
$regdate = $student->timecreated ? userdate($student->timecreated, '%d/%m/%Y') : '-';
echo html_writer::tag('div',
    html_writer::tag('i', '', ['class' => 'fa fa-calendar-plus']) . ' ' . 
    get_string('registeredon', 'local_epicereports') . ': ' . $regdate,
    ['class' => 'epice-student-meta-item']
);

// Last access.
$lastaccess = $student->lastaccess ? userdate($student->lastaccess, '%d/%m/%Y %H:%M') : '-';
echo html_writer::tag('div',
    html_writer::tag('i', '', ['class' => 'fa fa-clock']) . ' ' . 
    get_string('lastlogin', 'local_epicereports') . ': ' . $lastaccess,
    ['class' => 'epice-student-meta-item']
);

echo html_writer::end_div(); // student-meta
echo html_writer::end_div(); // student-info
echo html_writer::end_div(); // student-profile

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Card: Courses Summary with Chart.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-chart-pie']) . ' ' . get_string('coursessummary', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');

echo html_writer::start_div('row');

// Chart column.
echo html_writer::start_div('col-md-5 mb-4');
if ($summary['total'] > 0) {
    echo html_writer::start_div('epice-chart-container');
    echo html_writer::tag('canvas', '', ['id' => 'studentProgressChart']);
    echo html_writer::end_div();
} else {
    echo html_writer::tag('p', get_string('nocourses', 'local_epicereports'), 
        ['class' => 'text-center text-muted py-5']);
}
echo html_writer::end_div();

// Stats column.
echo html_writer::start_div('col-md-7');
echo html_writer::start_div('epice-stats-grid');

// Total enrolled.
echo html_writer::start_div('epice-stat-box info');
echo html_writer::tag('div', $summary['total'], ['class' => 'epice-stat-value']);
echo html_writer::tag('div', get_string('totalenrolled', 'local_epicereports'), ['class' => 'epice-stat-label']);
echo html_writer::end_div();

// Completed.
echo html_writer::start_div('epice-stat-box success');
echo html_writer::tag('div', $summary['completed'], ['class' => 'epice-stat-value']);
echo html_writer::tag('div', get_string('coursescompleted', 'local_epicereports'), ['class' => 'epice-stat-label']);
echo html_writer::end_div();

// In progress.
echo html_writer::start_div('epice-stat-box warning');
echo html_writer::tag('div', $summary['in_progress'], ['class' => 'epice-stat-value']);
echo html_writer::tag('div', get_string('coursesinprogress', 'local_epicereports'), ['class' => 'epice-stat-label']);
echo html_writer::end_div();

// Not started.
echo html_writer::start_div('epice-stat-box danger');
echo html_writer::tag('div', $summary['not_started'], ['class' => 'epice-stat-value']);
echo html_writer::tag('div', get_string('coursesnotstarted', 'local_epicereports'), ['class' => 'epice-stat-label']);
echo html_writer::end_div();

// Completion rate.
echo html_writer::start_div('epice-stat-box');
echo html_writer::tag('div', $summary['completion_rate'] . '%', ['class' => 'epice-stat-value']);
echo html_writer::tag('div', get_string('completionrate', 'local_epicereports'), ['class' => 'epice-stat-label']);
echo html_writer::end_div();

echo html_writer::end_div(); // stats-grid
echo html_writer::end_div(); // col

echo html_writer::end_div(); // row

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Card: Enrolled Courses Table.
echo html_writer::start_div('epice-card');
echo html_writer::start_div('epice-card-header');
echo html_writer::tag('h5', 
    html_writer::tag('i', '', ['class' => 'fa fa-book']) . ' ' . get_string('enrolledcourses', 'local_epicereports'), 
    ['class' => 'epice-card-title']
);
echo html_writer::end_div();

echo html_writer::start_div('epice-card-body');

if (empty($courses)) {
    echo html_writer::tag('p', get_string('nocourses', 'local_epicereports'), 
        ['class' => 'text-center text-muted py-4']);
} else {
    echo html_writer::start_div('epice-table-container');
    
    echo html_writer::start_tag('table', ['class' => 'epice-table']);
    
    // Headers.
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('coursename', 'local_epicereports'));
    echo html_writer::tag('th', get_string('status', 'local_epicereports'));
    echo html_writer::tag('th', get_string('progress', 'local_epicereports'));
    echo html_writer::tag('th', get_string('finalgrade', 'local_epicereports'));
    echo html_writer::tag('th', get_string('completiondate', 'local_epicereports'));
    echo html_writer::tag('th', get_string('certificate', 'local_epicereports'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    
    foreach ($courses as $course) {
        echo html_writer::start_tag('tr');
        
        // Course name.
        $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
        echo html_writer::tag('td', 
            html_writer::link($courseurl, s($course->fullname), ['target' => '_blank']),
            ['style' => 'font-weight: 500;']
        );
        
        // Status badge.
        $statusclass = 'danger';
        $statustext = get_string('notstarted', 'local_epicereports');
        if ($course->status === 'completed') {
            $statusclass = 'success';
            $statustext = get_string('completed', 'local_epicereports');
        } else if ($course->status === 'in_progress') {
            $statusclass = 'warning';
            $statustext = get_string('inprogress', 'local_epicereports');
        }
        echo html_writer::tag('td', 
            html_writer::tag('span', $statustext, ['class' => 'epice-badge epice-badge-' . $statusclass])
        );
        
        // Progress bar.
        $progress = $course->progress ?? 0;
        $progressclass = 'danger';
        if ($progress >= 100) {
            $progressclass = 'success';
        } else if ($progress >= 50) {
            $progressclass = 'warning';
        } else if ($progress >= 25) {
            $progressclass = 'info';
        }
        
        $progressbar = html_writer::start_div('epice-progress-wrapper');
        $progressbar .= html_writer::start_div('epice-progress');
        $progressbar .= html_writer::div('', 'epice-progress-bar epice-progress-' . $progressclass, 
            ['style' => 'width: ' . min(100, $progress) . '%']);
        $progressbar .= html_writer::end_div();
        $progressbar .= html_writer::tag('span', round($progress) . '%', ['class' => 'epice-progress-label']);
        $progressbar .= html_writer::end_div();
        
        echo html_writer::tag('td', $progressbar);
        
        // Final grade.
        $grade = $course->finalgrade ?? '-';
        echo html_writer::tag('td', $grade);
        
        // Completion date.
        $completiondate = '-';
        if (!empty($course->completiondate)) {
            $completiondate = userdate($course->completiondate, '%d/%m/%Y');
        }
        echo html_writer::tag('td', $completiondate);
        
        // Certificate.
        if (!empty($course->certificate)) {
            $certbtn = html_writer::link(
                $course->certificate->downloadurl,
                html_writer::tag('i', '', ['class' => 'fa fa-download']) . ' ' . 
                get_string('downloadcertificate', 'local_epicereports'),
                ['class' => 'epice-btn epice-btn-success epice-btn-sm', 'target' => '_blank']
            );
            echo html_writer::tag('td', $certbtn);
        } else {
            echo html_writer::tag('td', 
                html_writer::tag('span', get_string('nocertificate', 'local_epicereports'), 
                    ['class' => 'text-muted'])
            );
        }
        
        echo html_writer::end_tag('tr');
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div(); // table-container
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
