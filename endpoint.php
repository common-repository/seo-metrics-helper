<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Handle everything in the init hook
add_action('init', 'seo_metrics_handle_custom_endpoint');

function seo_metrics_handle_custom_endpoint() {
    // Check if the current URL matches the custom endpoint pattern
    $url_path = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
    $url_path = sanitize_text_field(wp_parse_url($url_path, PHP_URL_PATH));

    // Sanitize home URL
    // Check if home_url() returns a non-empty value
    if ($home_url = home_url()) {
        // If home_url() is not empty, sanitize its path
        $home_url_path = sanitize_text_field(wp_parse_url($home_url, PHP_URL_PATH));
    } else {
        // If home_url() is empty, set $home_url_path to an empty string
        $home_url_path = '';
    }

    // Remove home URL from current URL
    $url_path = str_replace($home_url_path, '', $url_path);

    // Remove leading and trailing slashes, then split into segments
    $url_segments = explode('/', trim($url_path, '/'));

    // Check if the URL matches the pattern
    if ($url_segments[0] === 'utilseometricstempmagiclog' && !empty($url_segments[1])) {
        $temp_token = $url_segments[1];

        // Fetch the token record
        $token_record = seo_metrics_get_token_record($temp_token);

        if ($token_record) {
            // Automatically log in as user
            $user_id = $token_record->user_id;
                
            wp_clear_auth_cookie();
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            // Redirect to the admin dashboard
            wp_redirect(admin_url());
            exit();
        }

        // Redirect to 404 page if conditions are not met
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        include get_404_template();
        exit();
    }
}