<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Register the custom REST API endpoint for page details
add_action('rest_api_init', function () {
    register_rest_route('seo-metrics', '/page-details', array(
        'methods' => 'GET',
        'callback' => 'seo_metrics_rest_page_details_handler',
        'permission_callback' => 'seo_metrics_verify_authorization_token', // Callback to verify authorization token
    ));
});

// REST API handler for page details
function seo_metrics_rest_page_details_handler($request) {
    // Get the optional parameters 'start_date' and 'end_date'
    $start_date = $request->get_param('start_date');
    $end_date = $request->get_param('end_date');

    if ($start_date) {
        $start_date = sanitize_text_field($start_date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
            $start_date = "";
        }
    }
    if ($end_date) {
        $end_date = sanitize_text_field($end_date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            $end_date = "";
        }
    }

    // Prepare arguments for WP_Query
    $args = array(
        'post_type' => 'page', // Limit to 'page' post type
    );

    // Add date conditions if provided
    if ($start_date) {
        $args['date_query'] = array(
            'after' => $start_date,
        );
    }
    if ($end_date) {
        if (!isset($args['date_query'])) {
            $args['date_query'] = array();
        }
        $args['date_query']['before'] = $end_date;
    }

    // Query posts
    $query = new WP_Query($args);
    $cache_key = "seo_metrics_page_details";
    $cached_data = wp_cache_get($cache_key);
    if (false === $cached_data) {
        // Execute the query
        $cached_data = $query->get_posts();
        wp_cache_set($cache_key, $cached_data);
    }

    // Return the 'page' details
    return rest_ensure_response($cached_data);
}