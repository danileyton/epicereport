<?php
/**
 * Local library functions for local_epicereports
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderiza el menú lateral del plugin local_epicereports.
 *
 * @param string      $active  Uno de: 'dashboard', 'courses', 'course_detail', 'schedules', 'logs'.
 * @param stdClass|null $course Objeto curso (solo necesario para páginas de contexto de curso).
 */
function local_epicereports_render_sidebar(string $active = '', ?stdClass $course = null): void {
    global $CFG;
    require_once($CFG->libdir . '/weblib.php');

    $items = [];

    // Dashboard general del plugin.
    $items[] = [
        'id'    => 'dashboard',
        'label' => 'Dashboard',
        'url'   => new moodle_url('/local/epicereports/dashboard.php'),
        'icon'  => 'fa-tachometer-alt',
    ];

    // Listado de cursos.
    $items[] = [
        'id'    => 'courses',
        'label' => 'Cursos',
        'url'   => new moodle_url('/local/epicereports/courses.php'),
        'icon'  => 'fa-book',
    ];

    // Opciones específicas del curso (solo si hay un curso en contexto).
    if ($course && !empty($course->id)) {
        // Separador visual.
        $items[] = [
            'id'    => 'separator1',
            'label' => '--- ' . mb_substr(format_string($course->shortname), 0, 15) . ' ---',
            'url'   => null,
            'icon'  => null,
            'disabled' => true,
        ];

        // Detalle de curso.
        $items[] = [
            'id'    => 'course_detail',
            'label' => 'Detalle del Curso',
            'url'   => new moodle_url('/local/epicereports/course_detail.php', ['id' => $course->id]),
            'icon'  => 'fa-chart-bar',
        ];

        // Reportes programados.
        $items[] = [
            'id'    => 'schedules',
            'label' => get_string('scheduledreports', 'local_epicereports'),
            'url'   => new moodle_url('/local/epicereports/schedule_reports.php', ['courseid' => $course->id]),
            'icon'  => 'fa-clock',
        ];

        // Historial de envíos.
        $items[] = [
            'id'    => 'logs',
            'label' => get_string('reportlogs', 'local_epicereports'),
            'url'   => new moodle_url('/local/epicereports/schedule_logs.php', ['courseid' => $course->id]),
            'icon'  => 'fa-history',
        ];

        // Probar reportes (para testing).
        $items[] = [
            'id'    => 'test_reports',
            'label' => 'Probar Reportes',
            'url'   => new moodle_url('/local/epicereports/test_reports.php', ['courseid' => $course->id]),
            'icon'  => 'fa-flask',
        ];
    }

    // Separador.
    $items[] = [
        'id'    => 'separator2',
        'label' => '---',
        'url'   => null,
        'icon'  => null,
        'disabled' => true,
    ];

    // Volver a la plataforma.
    $items[] = [
        'id'    => 'plataforma',
        'label' => 'Volver al Inicio',
        'url'   => new moodle_url('/my/'),
        'icon'  => 'fa-home',
    ];

    // Renderizar el menú.
    echo html_writer::start_div('list-group mb-4 epicereports-sidebar');

    foreach ($items as $item) {
        // Items deshabilitados (separadores).
        if (!empty($item['disabled'])) {
            echo html_writer::tag('div', $item['label'], [
                'class' => 'list-group-item list-group-item-light text-muted small py-1'
            ]);
            continue;
        }

        $classes = 'list-group-item list-group-item-action d-flex align-items-center';
        if ($active === $item['id']) {
            $classes .= ' active';
        }

        // Construir contenido con icono.
        $content = '';
        if (!empty($item['icon'])) {
            $content .= html_writer::tag('i', '', [
                'class' => 'fas ' . $item['icon'] . ' mr-2',
                'aria-hidden' => 'true',
            ]);
        }
        $content .= $item['label'];

        echo html_writer::link($item['url'], $content, ['class' => $classes]);
    }

    echo html_writer::end_div();

    // Estilos adicionales para el sidebar.
    echo html_writer::tag('style', '
        .epicereports-sidebar .list-group-item {
            border-radius: 0;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }
        .epicereports-sidebar .list-group-item:hover:not(.list-group-item-light) {
            background-color: #f8f9fa;
            border-left-color: #007bff;
        }
        .epicereports-sidebar .list-group-item.active {
            background-color: #007bff;
            border-color: #007bff;
            border-left-color: #0056b3;
        }
        .epicereports-sidebar .list-group-item-light {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    ');
}

/**
 * Obtiene el contexto del curso actual si está disponible.
 *
 * @return stdClass|null El objeto curso o null si no hay curso en contexto.
 */
function local_epicereports_get_current_course(): ?stdClass {
    global $COURSE;

    if ($COURSE && $COURSE->id > 1) {
        return $COURSE;
    }

    return null;
}
