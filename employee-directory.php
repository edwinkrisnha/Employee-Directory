<?php
/**
 * Plugin Name: Employee Directory
 * Description: Internal employee directory for company intranet. Searchable staff profiles stored as user meta.
 * Version:     1.0.0
 * Author:      Your Company
 * License:     GPL-2.0-or-later
 * Text Domain: employee-directory
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EMPLOYEE_DIR_VERSION',     '1.0.0' );
define( 'EMPLOYEE_DIR_PLUGIN_FILE', __FILE__ );
define( 'EMPLOYEE_DIR_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'EMPLOYEE_DIR_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

require_once EMPLOYEE_DIR_PLUGIN_DIR . 'includes/profile.php';
require_once EMPLOYEE_DIR_PLUGIN_DIR . 'includes/directory.php';
require_once EMPLOYEE_DIR_PLUGIN_DIR . 'includes/admin.php';
