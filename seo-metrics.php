<?php
/**
 * Plugin Name: SEO Metrics Helper
 * Description: Utility plugin for various functionalities.
 * Version: 1.0.5
 * Plugin URI: https://www.seometrics.net/
 * Author: SEO Metrics
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define table tokens constant
define('SEO_METRICS_TABLE_TOKENS', 'seo_metrics_tokens');
// Define table clicks constant
define('SEO_METRICS_TABLE_CLICKS', 'seo_metrics_clicks');

// Include common functions
require_once plugin_dir_path(__FILE__).'common-functions.php';

// Plugin activation hook
register_activation_hook(__FILE__, 'seo_metrics_activation_function');

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'seo_metrics_deactivation_function');

// Activation function
function seo_metrics_activation_function() {
    // Step 1: Create the tokens table
    seo_metrics_create_tokens_table();

    // Step 2: Create the clicks table
    seo_metrics_create_clicks_table();
}

// Deactivation function
function seo_metrics_deactivation_function() {
}

// Redirect to the welcome page on plugin activation
function seo_metrics_redirect_on_activation($plugin) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        wp_redirect(admin_url('admin.php?page=seo_metrics_welcome_page'));
        exit;
    }
}
add_action( 'activated_plugin', 'seo_metrics_redirect_on_activation' );

// Enqueue the script for tracking anchor link clicks
function seo_metrics_enqueue_scripts() {
    wp_enqueue_script('track-anchor-clicks', plugin_dir_url(__FILE__) . 'js/track-anchor-clicks.js', array('jquery'), '1.0', true);

    // Localize the script with new data
    wp_localize_script('track-anchor-clicks', 'seo_metrics_ajax_object',
        array('ajaxurl' => admin_url('admin-ajax.php'), 'ajaxnonce' => wp_create_nonce('seo_metrics_track_anchor_click_nonce'))
    );
}

add_action('wp_enqueue_scripts', 'seo_metrics_enqueue_scripts');

// Enqueue the style and script for welcome page
function seo_metrics_admin_enqueue_scripts() {
    global $pagenow;
    if ($pagenow == 'admin.php') {
        wp_register_style( 'seo_metrics_welcome_page_style', plugin_dir_url(__FILE__) . 'css/welcome-page-style.css', false, '1.0.0' );
        wp_enqueue_style( 'seo_metrics_welcome_page_style' );
    }

    wp_enqueue_script('enable-disable-connect-button', plugin_dir_url(__FILE__) . 'js/enable-disable-connect-button.js', array('jquery'), '1.0', true);

    wp_enqueue_script('connect-plugin', plugin_dir_url(__FILE__) . 'js/connect-plugin.js', array('jquery'), '1.0', true);

    // Localize the script with new data
    wp_localize_script('connect-plugin', 'seo_metrics_ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'ajax_nonce' => wp_create_nonce('seo_metrics_connect_plugin_nonce')));
}
add_action('admin_enqueue_scripts', 'seo_metrics_admin_enqueue_scripts');

// Include Token REST API
require_once plugin_dir_path(__FILE__).'token-rest-api.php';

// Include Endpoint
require_once plugin_dir_path(__FILE__).'endpoint.php';

// Include Clicks REST API
require_once plugin_dir_path(__FILE__).'clicks-rest-api.php';

// Include Page Details REST API
require_once plugin_dir_path(__FILE__).'page-details-rest-api.php';

// Include Post Details REST API
require_once plugin_dir_path(__FILE__).'post-details-rest-api.php';

// Include Welcome Page
require_once plugin_dir_path(__FILE__).'welcome-page.php';

// Include Plugin Info REST API
require_once plugin_dir_path(__FILE__).'plugin-info-rest-api.php';