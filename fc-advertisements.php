<?php
/**
 * Plugin Name: FC Advertisements
 * Description: A simple plugin to create and manage advertisements
 * Version: 1.0.0
 * Author: FC
 * Text Domain: fc-advertisements
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('FC_ADS_FILE')) {
    define('FC_ADS_FILE', __FILE__);
}

require_once plugin_dir_path(__FILE__) . 'includes/FC_Advertisements.php';

// Initialize the plugin
FC_Advertisements::get_instance();
