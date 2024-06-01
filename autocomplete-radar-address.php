<?php
/*
Plugin Name: Autocomplete Radar Address
Description: A WordPress plugin that uses the Radar API for address autocomplete functionality.
Version: 1.0
Author: Sunil and Jay
Author URI: https://nephilainc.com/
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue scripts and styles for frontend and backend
function ar_enqueue_scripts() {
    $plugin_version = '1.0';
    
    wp_enqueue_style('radar-css', 'https://js.radar.com/v4.2.1/radar.css', array(), $plugin_version);
    wp_enqueue_script('radar-js', 'https://js.radar.com/v4.2.1/radar.min.js', array(), $plugin_version, true);
    wp_enqueue_script('ar-custom-js', plugin_dir_url(__FILE__) . 'js/autocomplete-radar.js', array('jquery', 'radar-js'), $plugin_version, true);
    
    // Localize script to pass the API key and field IDs to the JS file
    $api_key = get_option('ar_radar_api_key');
    $frontend_field_ids = get_option('ar_frontend_field_ids');
    $backend_field_ids = get_option('ar_backend_field_ids');
    wp_localize_script('ar-custom-js', 'ar_settings', array(
        'api_key' => $api_key,
        'frontend_field_ids' => array_map('trim', explode(',', $frontend_field_ids)),
        'backend_field_ids' => array_map('trim', explode(',', $backend_field_ids))
    ));
}
add_action('wp_enqueue_scripts', 'ar_enqueue_scripts');
add_action('admin_enqueue_scripts', 'ar_enqueue_scripts');

// Add settings page
function ar_add_settings_page() {
    add_menu_page('Autocomplete Radar Address', 'Autocomplete Radar Address', 'manage_options', 'autocomplete-radar-address', 'ar_settings_page', 'dashicons-location-alt', 26);
}
add_action('admin_menu', 'ar_add_settings_page');

// Display settings page
function ar_settings_page() {
    ?>
    <div class="wrap">
        <h1>Autocomplete Radar Address Settings</h1>
        <p>Register at <a href="https://radar.com/login" target="_blank">Radar</a> to get the API keys.</p>
        <form method="post" action="options.php">
            <?php
            settings_fields('ar_settings_group');
            do_settings_sections('ar_settings_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Radar API Key</th>
                    <td><input type="text" name="ar_radar_api_key" value="<?php echo esc_attr(get_option('ar_radar_api_key')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Frontend Field IDs (comma-separated)</th>
                    <td><input type="text" name="ar_frontend_field_ids" value="<?php echo esc_attr(get_option('ar_frontend_field_ids')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Backend Field IDs (comma-separated)</th>
                    <td><input type="text" name="ar_backend_field_ids" value="<?php echo esc_attr(get_option('ar_backend_field_ids')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register settings
function ar_register_settings() {
    register_setting('ar_settings_group', 'ar_radar_api_key');
    register_setting('ar_settings_group', 'ar_frontend_field_ids');
    register_setting('ar_settings_group', 'ar_backend_field_ids');
}
add_action('admin_init', 'ar_register_settings');

// Helper function to sanitize field IDs
function ar_sanitize_field_id($field_id) {
    return preg_replace('/[^a-zA-Z0-9_]/', '_', trim($field_id));
}

// Output the Radar initialization script in the footer for frontend
function ar_initialize_radar() {
    $api_key = get_option('ar_radar_api_key');
    $frontend_field_ids = get_option('ar_frontend_field_ids');
    if ($api_key && $frontend_field_ids) {
        $field_ids_array = explode(',', $frontend_field_ids);
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Radar.initialize('<?php echo esc_js($api_key); ?>');
                <?php foreach ($field_ids_array as $field_id): ?>
                (function() {
                    var originalFieldId = '<?php echo esc_js(trim($field_id)); ?>';
                    var sanitizedFieldId = '<?php echo esc_js(ar_sanitize_field_id($field_id)); ?>';
                    var field = document.getElementById(originalFieldId);
                    if (field) {
                        var dataListId = sanitizedFieldId + '-suggestions';
                        var dataList = document.getElementById(dataListId);
                        if (!dataList) {
                            dataList = document.createElement('datalist');
                            dataList.id = dataListId;
                            document.body.appendChild(dataList);
                            field.setAttribute('list', dataListId);
                        }

                        field.addEventListener('input', function(event) {
                            const query = event.target.value;
                            if (query.length > 2) {
                                Radar.autocomplete({ query: query, limit: 5 }).then((result) => {
                                    const suggestions = result.addresses;
                                    dataList.innerHTML = '';
                                    suggestions.forEach((address) => {
                                        const option = document.createElement('option');
                                        option.value = address.formattedAddress;
                                        dataList.appendChild(option);
                                    });
                                }).catch((err) => {
                                    console.error(err);
                                });
                            }
                        });
                    }
                })();
                <?php endforeach; ?>
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'ar_initialize_radar');

// Output the Radar initialization script in the footer for admin
function ar_initialize_radar_admin() {
    $api_key = get_option('ar_radar_api_key');
    $backend_field_ids = get_option('ar_backend_field_ids');
    if ($api_key && $backend_field_ids) {
        $field_ids_array = explode(',', $backend_field_ids);
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Radar.initialize('<?php echo esc_js($api_key); ?>');
                <?php foreach ($field_ids_array as $field_id): ?>
                (function() {
                    var originalFieldId = '<?php echo esc_js(trim($field_id)); ?>';
                    var sanitizedFieldId = '<?php echo esc_js(ar_sanitize_field_id($field_id)); ?>';
                    var field = document.getElementById(originalFieldId);
                    if (field) {
                        var dataListId = sanitizedFieldId + '-suggestions';
                        var dataList = document.getElementById(dataListId);
                        if (!dataList) {
                            dataList = document.createElement('datalist');
                            dataList.id = dataListId;
                            document.body.appendChild(dataList);
                            field.setAttribute('list', dataListId);
                        }

                        field.addEventListener('input', function(event) {
                            const query = event.target.value;
                            if (query.length > 2) {
                                Radar.autocomplete({ query: query, limit: 5 }).then((result) => {
                                    const suggestions = result.addresses;
                                    dataList.innerHTML = '';
                                    suggestions.forEach((address) => {
                                        const option = document.createElement('option');
                                        option.value = address.formattedAddress;
                                        dataList.appendChild(option);
                                    });
                                }).catch((err) => {
                                    console.error(err);
                                });
                            }
                        });
                    }
                })();
                <?php endforeach; ?>
            });
        </script>
        <?php
    }
}
add_action('admin_footer', 'ar_initialize_radar_admin');
?>
