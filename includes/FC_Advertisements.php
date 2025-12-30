<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Api.php';
require_once __DIR__ . '/AdminArea.php';
require_once __DIR__ . '/Frontend.php';

class FC_Advertisements {
    
    private static $instance = null;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize Database Helper
        $this->db = new FC_Advertisements_Database();
        
        // Hooks
        register_activation_hook(FC_ADS_FILE, array($this, 'activate'));
        
        // Initialize Modules
        if (is_admin()) {
            new FC_Advertisements_Admin($this->db);
        }
        
        new FC_Advertisements_Frontend($this->db);
        new FC_Advertisements_Api($this->db);
    }
    
    /**
     * Plugin activation - create database table
     */
    public function activate() {
        if (!defined('FLUENT_COMMUNITY_PLUGIN_VERSION')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            deactivate_plugins(plugin_basename(FC_ADS_FILE));
            wp_die('Fluent Community is required to activate this plugin.');
        }
        
        $this->db->create_table();
        $this->db->update_table_schema(); // Update existing tables with new columns
    }
}
