<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Renderiza el menú lateral del plugin local_epicereports.
 *
 * @param string      $active  Uno de: 'dashboard', 'courses', 'course_detail'.
 * @param stdClass|null $course Objeto curso (solo necesario para course_detail).
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
    ];

    // Listado de cursos.
    $items[] = [
        'id'    => 'courses',
        'label' => 'Cursos',
        'url'   => new moodle_url('/local/epicereports/courses.php'),
    ];
    
    // Detalle de curso (solo tiene sentido si estamos viendo un curso).
    if ($course && !empty($course->id)) {
        $items[] = [
            'id'    => 'course_detail',
            'label' => 'Detalle del curso',
            'url'   => new moodle_url('/local/epicereports/course_detail.php', ['id' => $course->id]),
        ];
    }
    
    // Listado de cursos.
    $items[] = [
        'id'    => 'plataforma',
        'label' => 'Volver',
        'url'   => new moodle_url('/my/'),
    ];


    // Pintamos el menú como una list-group vertical (Bootstrap).
    echo html_writer::start_div('list-group mb-4');

    foreach ($items as $item) {
        $classes = 'list-group-item list-group-item-action';
        if ($active === $item['id']) {
            $classes .= ' active';
        }
        echo html_writer::link($item['url'], $item['label'], ['class' => $classes]);
    }

    echo html_writer::end_div();
}
