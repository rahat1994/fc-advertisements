<?php

if (!defined('ABSPATH')) {
    exit;
}

class FC_Advertisements_Database {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'fc_advertisements';
    }

    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Create database table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            space varchar(100) NOT NULL,
            position varchar(100) NOT NULL,
            url varchar(500) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get all advertisements
     */
    public function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");
    }

    /**
     * Create advertisement
     */
    public function create($data) {
        global $wpdb;
        return $wpdb->insert(
            $this->table_name,
            $data,
            array('%s', '%s', '%s', '%s')
        );
    }

    /**
     * Delete advertisement
     */
    public function delete($id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => intval($id)), array('%d'));
    }

    /**
     * Get ads specifically for the feed (content space, random order)
     */
    public function get_for_feed() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE space = 'content' ORDER BY RAND()");
    }

    /**
     * Get FCOM spaces
     */
    public function get_fcom_spaces() {
        if (class_exists('\FluentCommunity\App\Models\BaseSpace')) {
            return \FluentCommunity\App\Models\BaseSpace::select(['id', 'title', 'slug'])->get();
        }
        return [];
    }
}
