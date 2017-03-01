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
 
require_once('assets/classes/bullhorn_request.php');
require_once('assets/classes/bullhorn_client.php');

/*
*  Method to register the menu page
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

/*
*   Register admin plugin settings
*/
if ( is_admin() )
{
    add_action( 'admin_menu', 'sbwp_register_menu_page' );
    add_action( 'admin_init', 'sbwp_register_api_credential_settings' );
}

function sbwp_register_api_credential_settings()
{
    // register API credentials
    register_setting( 'sbwp-api-credentials', 'sbwp_bullhorn_client_id' );
    register_setting( 'sbwp-api-credentials', 'sbwp_bullhorn_client_secret' );
    register_setting( 'sbwp-api-credentials', 'sbwp_bullhorn_username' );
    register_setting( 'sbwp-api-credentials', 'sbwp_bullhorn_password' );
    
    // register OAuth credentials
    register_setting( 'sbwp-oauth-credentials', 'sbwp_bullhorn_refresh_token' );
    register_setting( 'sbwp-oauth-credentials', 'sbwp_bullhorn_rest_token' );
    register_setting( 'sbwp-oauth-credentials', 'sbwp_bullhorn_rest_url' );
}
 
/*
* Show the menu page in admin
*/
function show_sbwp_options_admin_menu() 
{
    if ( !current_user_can( 'manage_options' ) || !is_admin() )  { // reject a user without the correct permissions
        wp_die( __( 'Insufficient permissions' ) );
    }
    
    require_once('assets/admin/templates/api_credentials.php');
}

/**
 * Register shortcode for displaying all jobs
 */
function sbwp_display_all_jobs( $atts )
{
    $bullhorn = new BullhornClient($_GET);
    $jobs = $bullhorn->getAllJobs();
    var_dump($jobs);
}
add_shortcode( 'sbwp-bullhorn-jobs', 'sbwp_display_all_jobs' );

/**
 * Register shortcode for displaying a single job
 */
function sbwp_display_single_job( $atts )
{
    $bullhorn = new BullhornClient($_GET);
    $job = $bullhorn->getJob();
    var_dump($job);
}
add_shortcode( 'sbwp-bullhorn-single', 'sbwp_display_single_job' );
?>
