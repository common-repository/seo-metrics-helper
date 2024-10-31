<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Create new tokens table
function seo_metrics_create_tokens_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . SEO_METRICS_TABLE_TOKENS;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        auth_token varchar(32) NOT NULL,
        user_id bigint(20) NOT NULL,
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE (auth_token)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Find the token record
function seo_metrics_get_token_record($temp_token) {
    global $wpdb;

    $table_name = $wpdb->prefix . SEO_METRICS_TABLE_TOKENS;
    $cache_key = "seo_metrics_get_token";
    $cached_data = wp_cache_get($cache_key);
    if (false === $cached_data) {
        // Review Required: passing table name as parameter doesn't work
        $cached_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE auth_token = %s", $temp_token));
        wp_cache_set($cache_key, $cached_data);
    }

    return $cached_data;
}

// Create new clicks table
function seo_metrics_create_clicks_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . SEO_METRICS_TABLE_CLICKS;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        link_url varchar(255) NOT NULL,
        anchor_text varchar(255) NOT NULL,
        page_url varchar(255) NOT NULL,
        click_count mediumint(9) NOT NULL DEFAULT 0,
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// AJAX handler to create a new click entry
add_action('wp_ajax_seo_metrics_create_click_entry', 'seo_metrics_create_click_entry_callback');
add_action('wp_ajax_nopriv_seo_metrics_create_click_entry', 'seo_metrics_create_click_entry_callback');

function seo_metrics_create_click_entry_callback() {
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : "";
    // Verify the nonce
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'seo_metrics_track_anchor_click_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce.' );
    }

    $link_url = isset($_POST['link_url']) ? sanitize_url($_POST['link_url']) : '';
    $anchor_text = isset($_POST['anchor_text']) ? sanitize_text_field($_POST['anchor_text']) : '';
    $page_url = isset($_POST['page_url']) ? sanitize_url($_POST['page_url']) : '';

    // Validate link_url
    if ($link_url && !filter_var($link_url, FILTER_VALIDATE_URL)) {
        $link_url = '';
    }

    // Validate anchor_text
    if ($anchor_text && !preg_match('/^[a-zA-Z0-9\s]+$/', $anchor_text)) {
        $anchor_text = '';
    }

    // Validate page_url
    if ($page_url && !filter_var($page_url, FILTER_VALIDATE_URL)) {
        $page_url = '';
    }

    global $wpdb;
    $table_name = $wpdb->prefix . SEO_METRICS_TABLE_CLICKS;

    $wpdb->insert(
        $table_name,
        array(
            'link_url' => $link_url,
            'anchor_text' => $anchor_text,
            'page_url' => $page_url,
            'click_count' => 1,
        ),
        array('%s', '%s', '%s', '%d')
    );

    wp_send_json_success();
    wp_die();
}

// Callback to verify authorization token
function seo_metrics_verify_authorization_token() {
    // Get Authorization header from $_SERVER and sanitize
    $authorization_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? sanitize_text_field($_SERVER['HTTP_AUTHORIZATION']) : '';

    // Extract the token value from the "Bearer" format
    $token_parts = explode(' ', $authorization_header);
    $request_token = isset($token_parts[1]) ? sanitize_text_field($token_parts[1]) : '';

    // Trim any extra spaces from the token value
    $request_token = trim($request_token);

    // Validate token format
    if ($request_token !== '') {
        // Check if token length meets a certain criteria
        if (strlen($request_token) !== 32) {
            $request_token = '';
        }
    }

    // Check if the token exists in the tokens table
    return seo_metrics_token_exists($request_token);
}

// Function to check if the token exists in the tokens table
function seo_metrics_token_exists($token) {
    global $wpdb;
    $tokens_table_name = $wpdb->prefix . SEO_METRICS_TABLE_TOKENS;
    $cache_key = "seo_metrics_check_token";
    $cached_data = wp_cache_get($cache_key);
    if (false === $cached_data) {
        // Review Required: passing table name as parameter doesn't work
        $cached_data = $wpdb->get_var($wpdb->prepare("SELECT auth_token FROM $tokens_table_name WHERE auth_token = %s", $token));
        wp_cache_set($cache_key, $cached_data);
    }

    return !empty($cached_data);
}