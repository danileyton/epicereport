<?php
/**
 * Library functions for EpicE Reports
 *
 * @package    local_epicereports
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation with custom navigation items for this plugin
 *
 * @param global_navigation $navigation
 * @return void
 */
function local_epicereports_extend_navigation(global_navigation $navigation) {
    global $CFG;

    // Only let users with the appropriate capability see this navigation item.
    if (!has_capability('local/epicereports:view', context_system::instance())) {
        return;
    }

    $node = navigation_node::create(
        get_string('pluginname', 'local_epicereports'),
        new moodle_url('/local/epicereports/dashboard.php'),
        navigation_node::NODETYPE_LEAF,
        null,
        'epicereports',
        new pix_icon('i/report', '')
    );

    $navigation->add_node($node);
}