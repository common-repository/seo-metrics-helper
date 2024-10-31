<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Register the custom REST API endpoint for plugin info
add_action('rest_api_init', function () {
    register_rest_route('seo-metrics', '/plugin-info', array(
        'methods' => 'GET',
        'callback' => 'seo_metrics_rest_plugin_info_handler',
        'permission_callback' => 'seo_metrics_verify_authorization_token', // Callback to verify authorization token
    ));
});

// REST API handler for plugin info
function seo_metrics_rest_plugin_info_handler($request) {
    // Get plugin version dynamically
    $plugin_data = get_plugin_data(dirname(__FILE__) . '/seo-metrics.php');
    $version = $plugin_data['Version'];

    $return_data = array('version' => $version);
    // Return the plugin info
    return rest_ensure_response($return_data);
}
