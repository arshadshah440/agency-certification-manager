<?php
/**
 * Plugin Name: NM Applications
 * Description: A simple plugin that initializes multiple classes using static methods.
 * Version: 1.01
 * Author: Arshad Shah
 * Author URI: https://arshadwebstudio.com/
 * Text Domain: nm-applications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin path constant.
define( 'NM_APPS_PATH', plugin_dir_path( __FILE__ ) );
define( 'NM_APPS_URL', plugin_dir_url( __FILE__ ) );
    define( 'NM_PLUGIN_FILE', __FILE__ );



// Autoload includes.
require_once NM_APPS_PATH . 'includes/class-nm-applications-init.php';
require_once NM_APPS_PATH . 'includes/class-nm-helpers.php';
require_once NM_APPS_PATH . 'includes/class-formidable-entries-cache.php';

// Initialize the plugin.
NM_Applications_Init::init();
