<?php
/**
 * Plugin Name: Quill
 * Description: Frontend content editing for AI-built WordPress themes. The theme owns the design, your client edits the words and images.
 * Version: 0.9.2
 * Author: Rank Rocket Co (based on Pencil by Rino de Boer)
 * Text Domain: quill
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Quill
 */

defined( 'ABSPATH' ) || exit;

define( 'QUILL_VERSION', '0.9.2' );
define( 'QUILL_PLUGIN_FILE', __FILE__ );
define( 'QUILL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'QUILL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Issue tracker used for feedback and bug reports.
define( 'QUILL_ISSUES_URL', 'https://github.com/PenZenMaster/quill/issues' );

require_once QUILL_PLUGIN_PATH . 'includes/class-quill-fields.php';
require_once QUILL_PLUGIN_PATH . 'includes/class-quill-activity.php';
require_once QUILL_PLUGIN_PATH . 'includes/class-quill-admin.php';
require_once QUILL_PLUGIN_PATH . 'includes/class-quill-plugin.php';
require_once QUILL_PLUGIN_PATH . 'includes/template-functions.php';

register_activation_hook( QUILL_PLUGIN_FILE, array( 'Quill_Activity', 'install' ) );

Quill_Activity::init();
Quill_Admin::init();
Quill_Plugin::init();
