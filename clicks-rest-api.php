<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Register the custom REST API endpoint for clicks insights
add_action('rest_api_init', function () {
    register_rest_route('seo-metrics', '/clicks-insights', array(
        'methods' => 'GET',
        'callback' => 'seo_metrics_rest_clicks_insights_handler',
        'permission_callback' => 'seo_metrics_verify_authorization_token', // Callback to verify authorization token
    ));
});

// REST API handler for clicks insights
function seo_metrics_rest_clicks_insights_handler($request) {
    // Get the optional parameters 'start_date' and 'end_date'
    $start_date = $request->get_param('start_date');
    $end_date = $request->get_param('end_date');

    // Build the query to fetch entries from the tokens table based on optional parameters
    $query_args = array();
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

    global $wpdb;
    $clicks_table_name = $wpdb->prefix . SEO_METRICS_TABLE_CLICKS;

    // Review Required: passing table name as parameter doesn't work
    $sql = "SELECT link_url, anchor_text, page_url, COUNT(click_count) as click_count FROM $clicks_table_name";
    $where_clause = '';
    $query_params = [];

    if ($start_date && $start_date !== "") {
        $sql .= " WHERE created_at >= %s";
        $query_params[] = $start_date;
    }
    if ($end_date && $end_date !== "") {
        if($start_date && $start_date !== "") {
            $sql .= " AND created_at <= %s";
            $query_params[] = $end_date;
        } else {
            $sql .= " WHERE created_at <= %s";
            $query_params[] = $end_date;
        }
    }

    $sql .= ' GROUP BY anchor_text';

    $cache_key = "seo_metrics_clicks";
    $cached_data = wp_cache_get($cache_key);
    if (false === $cached_data) {
        // Prepare and execute the query
        $cached_data = $wpdb->get_results($wpdb->prepare($sql, $query_params));
        wp_cache_set($cache_key, $cached_data);
    }

    // Return the insights data
    return rest_ensure_response($cached_data);
}