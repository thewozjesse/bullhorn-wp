<?php
/**
 * Plugin Name: Simple Bullhorn WP
 * Plugin URI: https://github.com/thewozjesse/bullhorn-wp
 * Description: This plugin grabs job postings from the Bullhorn Staffing API
 * Version: 1.0.0
 * Author: Jesse Wozniak
 * Author URI: http://thewozjesse.com
 */
 
/*
* Register constants
*/ 
define('SBWP_PLUGIN_NAME', 'Simple Bullhorn WP');
 
 
/*
*  Register a menu page
*/
function sbwp_register_menu_page() 
{
    add_menu_page(
        __( SBWP_PLUGIN_NAME, 'textdomain' ),
            'Bullhorn WP',
            'manage_options',
            'sbwp/sbwp-admin.php',
            'show_sbwp_options_admin_menu',
            '',
            81
    );
}
add_action( 'admin_menu', 'sbwp_register_menu_page' );
 
/*
* Show the menu page in admin
*/
function show_sbwp_options_admin_menu() 
{
    if ( !current_user_can( 'manage_options' ) )  { // reject a user without the correct permissions
        wp_die( __( 'Insufficient permissions' ) );
    }
    
    require_once('assets/admin/templates/api_credentials.php');
}
 
 ?>