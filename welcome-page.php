<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Add a menu item for the welcome page in the admin menu
function seo_metrics_add_welcome_page() {
    add_menu_page(
        'SEO Metrics',
        'SEO Metrics',
        'manage_options',
        'seo_metrics_welcome_page',
        'seo_metrics_welcome_page_content',
        'dashicons-admin-generic',
        80 // Adjust the position as needed
    );
}
add_action('admin_menu', 'seo_metrics_add_welcome_page');

// Callback function to display content on the welcome page
function seo_metrics_welcome_page_content() {
    // Make API call to check if the site is connected
    $domain = urlencode(home_url());
    $api_url = 'https://app.seometrics.net/is-wordpress-plugin-connected?domain=' . $domain;
    $response = wp_remote_get($api_url);
    $logo_url = plugin_dir_url(__FILE__)."images/seo-metrics-logo.png";
    $first_time_option_value = get_option( 'seo_metrics_first_time_connecting' );
    
    ?>
<div class="wrap">
    <div class="seo-metrics-welcome-page">
        <p class="seo-metrics-title">Welcome to</p>
        <img src=<?php echo esc_url($logo_url) ?> />
        <p>Welcome to SEO Metrics! Here you can check the status of your plugin connection.</p>
        <?php
            // Validate the option value
            if ( ! $first_time_option_value || $first_time_option_value === false ) {
                ?>
        <div class="seo-metrics-first-time-notes">
            <p>Connecting to SEO Metrics will create a default user in your WordPress with username 'seo-mterics' and a
                random secured password.</p>
            <div>
                <label for="seo-metrics-welcome-privacy-terms-check">
                    <input type="checkbox" name="seo-metrics-welcome-privacy-terms-check"
                        id="seo-metrics-welcome-privacy-terms-check" />
                    You fully agree to the <a href="https://www.seometrics.net/privacy-policy/">privacy policy</a> and
                    <a href="https://www.seometrics.net/terms-and-conditions/">terms & conditions</a> of SEO metrics
                </label>
            </div>
        </div>
        <?php 
            } 
            ?>
        <div class="seo-metrics-status-content">
            <?php
            if (is_array($response) && !is_wp_error($response)) {
                $status_code = wp_remote_retrieve_response_code($response);
                if ($status_code === 200) {
                    ?>
            <div class="seo-metrics-status-connected-icon"></div>
            <p class="seo-metrics-status-connected">Site Connected</p>
            <?php
                } else {
                    ?>
            <button id="seo-metrics-connect-button" class="button"
                <?php if ( ! $first_time_option_value || $first_time_option_value === false ) echo 'disabled'; ?>>Connect</button>
            <?php
                }
            } else {
                ?>
            <div class="seo-metrics-status-error-icon"></div>
            <p class="seo-metrics-status-error">Error in checking connection</p>
            <?php
            }
            ?>
        </div>
    </div>
</div>
<?php
}

// AJAX handler for connecting button click
function seo_metrics_handle_connect_button_click() {
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : "";
    // Verify the nonce
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'seo_metrics_connect_plugin_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce.' );
    }

    // Check if the option exists
    if ( ! get_option( 'seo_metrics_first_time_connecting' ) ) {
        // If option doesn't exist, create it and save the value
        add_option( 'seo_metrics_first_time_connecting', true );
    }

    // Check if the user 'seo-metrics' exists
    $user = get_user_by('login', 'seo-metrics');
    if (!$user) {
        // If the user does not exist, create a new user
        $new_pass = wp_generate_password( 24, true, true );
        $user_id = wp_create_user('seo-metrics', $new_pass, 'helper@seometrics.net');
        if (!is_wp_error($user_id)) {
            // Set the user role to 'administrator'
            $user = new WP_User($user_id);
            $user->set_role('administrator');
        }
    } else {
        // If the user exists, get the user ID
        $user_id = $user->ID;
    }

    // Generate or update the token for the user
    global $wpdb;
    $tokens_table_name = $wpdb->prefix . SEO_METRICS_TABLE_TOKENS;

    $cache_key = "seo_metrics_existing_token";
    $cached_token = wp_cache_get($cache_key);
    if (false === $cached_token) {
        // Check if the user_id is already in the tokens table
        // Review Required: passing table name as parameter doesn't work
        $cached_token = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tokens_table_name WHERE user_id = %d", $user_id));
        wp_cache_set($cache_key, $cached_token);
    }

    if ($cached_token) {
        // Update the existing token
        $token = wp_generate_password(32, false);
        $wpdb->update(
            $tokens_table_name,
            array('auth_token' => $token, 'created_at' => current_time('mysql', 1)),
            array('user_id' => $user_id),
            array('%s', '%s'),
            array('%d')
        );
    } else {
        // Generate a new token and store in the tokens table
        $token = wp_generate_password(32, false);
        $wpdb->insert(
            $tokens_table_name,
            array('auth_token' => $token, 'user_id' => $user_id, 'created_at' => current_time('mysql', 1)),
            array('%s', '%d', '%s')
        );
    }

    // Return the token in the AJAX response
    wp_send_json_success(array('token' => $token, 'domain' => home_url()));
    wp_die();
}

// Register the AJAX action for connecting button click
add_action('wp_ajax_seo_metrics_handle_connect_button_click', 'seo_metrics_handle_connect_button_click');