<?php
/**
 * Funciones locales del plugin EpicE Reports
 *
 * @package    local_epicereports
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderiza el menú lateral del plugin local_epicereports con diseño mejorado.
 *
 * @param string      $active  Uno de: 'dashboard', 'courses', 'course_detail'.
 * @param stdClass|null $course Objeto curso (solo necesario para course_detail).
 */
function local_epicereports_render_sidebar(string $active = '', ?stdClass $course = null): void {
    global $CFG;
    require_once($CFG->libdir . '/weblib.php');

    $items = [
        [
            'id'    => 'dashboard',
            'label' => get_string('dashboard', 'local_epicereports'),
            'url'   => new moodle_url('/local/epicereports/dashboard.php'),
            'icon'  => 'fa-tachometer-alt',
        ],
        [
            'id'    => 'courses',
            'label' => get_string('courses', 'local_epicereports'),
            'url'   => new moodle_url('/local/epicereports/courses.php'),
            'icon'  => 'fa-book',
        ],
    ];

    // Detalle de curso (solo si estamos viendo un curso).
    if ($course && !empty($course->id)) {
        $items[] = [
            'id'    => 'course_detail',
            'label' => get_string('coursedetail', 'local_epicereports'),
            'url'   => new moodle_url('/local/epicereports/course_detail.php', ['id' => $course->id]),
            'icon'  => 'fa-chart-bar',
        ];
    }

    // Volver a la plataforma.
    $items[] = [
        'id'    => 'plataforma',
        'label' => get_string('backtomoodle', 'local_epicereports'),
        'url'   => new moodle_url('/my/'),
        'icon'  => 'fa-arrow-left',
    ];

    // Renderizar sidebar con nuevo diseño.
    echo html_writer::start_div('epice-sidebar');
    
    // Header del sidebar.
    echo html_writer::start_div('epice-sidebar-header');
    echo html_writer::tag('div', '📊', ['class' => 'epice-sidebar-logo']);
    echo html_writer::tag('h2', 'EpicE', ['class' => 'epice-sidebar-title']);
    echo html_writer::tag('div', 'Reports', ['class' => 'epice-sidebar-subtitle']);
    echo html_writer::end_div();

    // Navegación.
    echo html_writer::start_tag('ul', ['class' => 'epice-nav']);

    foreach ($items as $item) {
        echo html_writer::start_tag('li', ['class' => 'epice-nav-item']);
        
        $linkclass = 'epice-nav-link';
        if ($active === $item['id']) {
            $linkclass .= ' active';
        }
        
        $icon = html_writer::tag('i', '', ['class' => 'fa ' . $item['icon'] . ' epice-nav-icon']);
        $label = html_writer::tag('span', $item['label']);
        
        echo html_writer::link($item['url'], $icon . $label, ['class' => $linkclass]);
        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
}

/**
 * Renderiza una tarjeta de estadística con el nuevo diseño.
 *
 * @param string $icon Clase del icono FontAwesome.
 * @param string $value Valor a mostrar.
 * @param string $label Etiqueta descriptiva.
 * @param string $type Tipo de color: primary, success, warning, danger.
 */
function local_epicereports_render_stat_card(string $icon, string $value, string $label, string $type = 'primary'): void {
    echo html_writer::start_div('epice-stat-card stat-' . $type);
    
    echo html_writer::start_div('epice-stat-icon icon-' . $type);
    echo html_writer::tag('i', '', ['class' => 'fa ' . $icon]);
    echo html_writer::end_div();
    
    echo html_writer::tag('div', $value, ['class' => 'epice-stat-value']);
    echo html_writer::tag('div', $label, ['class' => 'epice-stat-label']);
    
    echo html_writer::end_div();
}

/**
 * Renderiza una barra de progreso con el nuevo diseño.
 *
 * @param float $percentage Porcentaje de progreso (0-100).
 * @return string HTML de la barra de progreso.
 */
function local_epicereports_render_progress_bar(float $percentage): string {
    $type = 'danger';
    if ($percentage >= 100) {
        $type = 'success';
    } else if ($percentage >= 50) {
        $type = 'warning';
    } else if ($percentage >= 25) {
        $type = 'info';
    }
    
    $html = html_writer::start_div('epice-progress-wrapper');
    
    $html .= html_writer::start_div('epice-progress');
    $html .= html_writer::div('', 'epice-progress-bar epice-progress-' . $type, [
        'style' => 'width: ' . min(100, $percentage) . '%'
    ]);
    $html .= html_writer::end_div();
    
    $html .= html_writer::tag('span', round($percentage) . '%', ['class' => 'epice-progress-label']);
    
    $html .= html_writer::end_div();
    
    return $html;
}

/**
 * Renderiza un badge/etiqueta con el nuevo diseño.
 *
 * @param string $text Texto del badge.
 * @param string $type Tipo de color: success, warning, danger, info, secondary.
 * @return string HTML del badge.
 */
function local_epicereports_render_badge(string $text, string $type = 'secondary'): string {
    return html_writer::tag('span', $text, ['class' => 'epice-badge epice-badge-' . $type]);
}