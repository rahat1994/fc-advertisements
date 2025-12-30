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
            user_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'enabled',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Update table schema for existing installations
     */
    public function update_table_schema() {
        global $wpdb;
        
        // Check if user_id column exists
        $user_id_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name} LIKE 'user_id'");
        if (empty($user_id_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN user_id bigint(20) NOT NULL DEFAULT 0 AFTER url");
            $wpdb->query("ALTER TABLE {$this->table_name} ADD INDEX user_id (user_id)");
        }
        
        // Check if status column exists
        $status_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name} LIKE 'status'");
        if (empty($status_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN status varchar(20) DEFAULT 'enabled' AFTER user_id");
            $wpdb->query("ALTER TABLE {$this->table_name} ADD INDEX status (status)");
        }
    }

    /**
     * Get all advertisements with user information
     * 
     * @param array $args Optional. Array of arguments to filter results.
     *                    - 'status' (string) Filter by status (e.g., 'enabled', 'disabled')
     *                    - 'space' (string) Filter by space (e.g., 'content', 'sidebar')
     * @return array Array of advertisement objects
     */
    public function get_all($args = array()) {
        global $wpdb;
        $users_table = $wpdb->prefix . 'users';
        
        $where_clauses = array();
        
        // Filter by status if provided
        if (!empty($args['status'])) {
            $where_clauses[] = $wpdb->prepare("a.status = %s", $args['status']);
        }
        
        // Filter by space if provided
        if (!empty($args['space'])) {
            $where_clauses[] = $wpdb->prepare("a.space = %s", $args['space']);
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT a.*, u.display_name as user_name 
                FROM {$this->table_name} a 
                LEFT JOIN {$users_table} u ON a.user_id = u.ID 
                {$where_sql}
                ORDER BY a.created_at DESC";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Create advertisement
     */
    public function create($data) {
        global $wpdb;
        
        // Set defaults
        if (!isset($data['status'])) {
            $data['status'] = 'enabled';
        }
        if (!isset($data['user_id'])) {
            $data['user_id'] = get_current_user_id();
        }
        
        return $wpdb->insert(
            $this->table_name,
            $data,
            array('%s', '%s', '%s', '%s', '%d', '%s')
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
     * Get ads specifically for the feed (content space, enabled only, random order)
     */
    public function get_for_feed() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE space = 'content' AND status = 'enabled' ORDER BY RAND()");
    }

    /**
     * Get single advertisement by ID
     */
    public function get_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", intval($id)));
    }

    /**
     * Update advertisement status
     */
    public function update_status($id, $status) {
        global $wpdb;
        return $wpdb->update(
            $this->table_name,
            array('status' => $status),
            array('id' => intval($id)),
            array('%s'),
            array('%d')
        );
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
