<?php
/**
 * Funciones locales para EpicE Reports
 *
 * @package    local_epicereports
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderiza el menú lateral del plugin local_epicereports.
 *
 * @param string $active Uno de: 'dashboard', 'courses', 'course_detail'.
 * @param stdClass|null $course Objeto curso (solo necesario para course_detail).
 */
function local_epicereports_render_sidebar(string $active = '', ?stdClass $course = null): void {
    global $CFG;
    require_once($CFG->libdir . '/weblib.php');

    $items = [];

    // Dashboard general del plugin.
    $items[] = [
        'id' => 'dashboard',
        'label' => get_string('dashboard', 'local_epicereports'),
        'url' => new moodle_url('/local/epicereports/dashboard.php'),
        'icon' => 'fa-tachometer-alt',
    ];

    // Listado de cursos.
    $items[] = [
        'id' => 'courses',
        'label' => get_string('courses', 'local_epicereports'),
        'url' => new moodle_url('/local/epicereports/courses.php'),
        'icon' => 'fa-book',
    ];

    // Detalle de curso (solo si estamos viendo un curso).
    if ($course && !empty($course->id)) {
        $items[] = [
            'id' => 'course_detail',
            'label' => get_string('coursedetail', 'local_epicereports'),
            'url' => new moodle_url('/local/epicereports/course_detail.php', ['id' => $course->id]),
            'icon' => 'fa-chart-bar',
        ];
    }

    // Separador visual.
    $items[] = [
        'id' => 'separator',
        'type' => 'separator',
    ];

    // Volver a la plataforma.
    $items[] = [
        'id' => 'plataforma',
        'label' => get_string('backtoplatform', 'local_epicereports'),
        'url' => new moodle_url('/my/'),
        'icon' => 'fa-home',
    ];

    // Renderizar el menú.
    echo html_writer::start_div('card shadow-sm');
    echo html_writer::start_div('card-header bg-primary text-white py-2');
    echo html_writer::tag('h6', get_string('pluginname', 'local_epicereports'), ['class' => 'mb-0']);
    echo html_writer::end_div();

    echo html_writer::start_div('list-group list-group-flush');

    foreach ($items as $item) {
        // Separador.
        if (isset($item['type']) && $item['type'] === 'separator') {
            echo html_writer::tag('div', '', ['class' => 'dropdown-divider my-0']);
            continue;
        }

        $classes = 'list-group-item list-group-item-action d-flex align-items-center py-2';

        if ($active === $item['id']) {
            $classes .= ' active';
        }

        // Icono.
        $icon = '';
        if (!empty($item['icon'])) {
            $icon = html_writer::tag('i', '', ['class' => 'fa ' . $item['icon'] . ' mr-2']);
        }

        echo html_writer::link(
            $item['url'],
            $icon . $item['label'],
            ['class' => $classes]
        );
    }

    echo html_writer::end_div(); // list-group
    echo html_writer::end_div(); // card
}