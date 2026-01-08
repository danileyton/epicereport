<?php
namespace local_epicereports;

// die('Renderer cargado'); // LÍNEA DE PRUEBA CORRECTA

use plugin_renderer_base;

/**
 * Renderer class for epicereports
 *
 * @package    local_epicereports
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_renderer extends plugin_renderer_base {

    /**
     * Render dashboard page.
     *
     * @param array $data The data for the dashboard.
     * @return string HTML for the page.
     */
    public function render_dashboard($data) {
        return $this->render_from_template('local_epicereports/dashboard', $data);
    }
}