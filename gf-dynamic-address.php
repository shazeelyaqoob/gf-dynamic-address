<?php
/*
Plugin Name: Gravity Forms Dynamic Address
Description: Adds dynamic country, state, city fields with repeater functionality to Gravity Forms.
Version: 1.0.0
Author: Shazeel Yaqoob
License: GPL-2.0+
*/

if (!defined('ABSPATH')) {
    exit;
}

// Check if Gravity Forms is active
if (!class_exists('GFForms')) {
    add_action('admin_notices', function() {
        ?>
        <div class="error">
            <p><?php _e('Gravity Forms Dynamic Address requires Gravity Forms to be installed and active.', 'gf-dynamic-address'); ?></p>
        </div>
        <?php
    });
    return;
}

// Define constants
define('GF_DA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GF_DA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include classes
require_once GF_DA_PLUGIN_DIR . 'includes/class-gf-dynamic-address.php';
require_once GF_DA_PLUGIN_DIR . 'includes/admin-settings.php';

// Initialize the plugin
add_action('plugins_loaded', function() {
    GF_Dynamic_Address::get_instance();
    GF_DA_Admin_Settings::get_instance();
});

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('gf-da-dynamic-address', GF_DA_PLUGIN_URL . 'assets/js/dynamic-address.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('gf-da-frontend', GF_DA_PLUGIN_URL . 'assets/css/frontend-styles.css', [], '1.0.0');
    
    // Localize script for AJAX
    wp_localize_script('gf-da-dynamic-address', 'gf_da_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gf_da_nonce')
    ]);
});