<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Register the REST API endpoint
add_action('rest_api_init', 'seo_metrics_register_rest_endpoint');

function seo_metrics_register_rest_endpoint() {
    register_rest_route(
        'seo-metrics',
        '/get-auth-token/',
        array(
            'methods'  => 'POST',
            'callback' => 'seo_metrics_rest_endpoint_handler',
            'permission_callback' => '__return_true', // Adjust permissions as needed
        )
    );
}

// REST API handler
function seo_metrics_rest_endpoint_handler($request) {
    // Get parameters from the request and sanitize
    $email_or_username = sanitize_text_field($request->get_param('email_or_username'));
    $password = sanitize_text_field($request->get_param('password'));

    if (!empty($email_or_username) && !empty($password)) {
        // Check if the user exists
        $user = get_user_by('login', $email_or_username);
        if (!$user) {
            $user = get_user_by('email', $email_or_username);
        }

        if ($user && user_can($user->ID, 'administrator')) {
            // User is an administrator, check password
            if (wp_check_password($password, $user->user_pass, $user->ID)) {
                global $wpdb;
                $tokens_table_name = $wpdb->prefix . SEO_METRICS_TABLE_TOKENS;

                $cache_key = "seo_metrics_existing_token";
                $cached_token = wp_cache_get($cache_key);
                if (false === $cached_token) {
                    // Check if the user_id is already in the tokens table
                    // Review Required: passing table name as parameter doesn't work
                    $cached_token = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tokens_table_name WHERE user_id = %d", $user->ID));
                    wp_cache_set($cache_key, $cached_token);
                }

                if ($cached_token) {
                    // Update the existing token
                    $token = wp_generate_password(32, false);
                    $wpdb->update(
                        $tokens_table_name,
                        array('auth_token' => $token, 'created_at' => current_time('mysql', 1)),
                        array('user_id' => $user->ID),
                        array('%s', '%s'),
                        array('%d')
                    );
                } else {
                    // Generate a new token and store in the tokens table
                    $token = wp_generate_password(32, false);
                    $wpdb->insert(
                        $tokens_table_name,
                        array('auth_token' => $token, 'user_id' => $user->ID, 'created_at' => current_time('mysql', 1)),
                        array('%s', '%d', '%s')
                    );
                }

                // Return success response with token
                return rest_ensure_response(array('auth_token' => $token, 'message' => 'Token generated successfully.'));
            }
        }
    }

    // Invalid credentials
    return rest_ensure_response(array('message' => 'Invalid credentials.'));
}