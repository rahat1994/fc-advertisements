<?php

if (!defined('ABSPATH')) {
    exit;
}

class FC_Advertisements_Api {

    private $db;

    public function __construct($db) {
        $this->db = $db;
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('fc-advertisements/v1', '/ads', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_ads_for_feed'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Get ads for feed (REST API callback)
     */
    public function get_ads_for_feed() {
        $ads = $this->db->get_for_feed();
        return rest_ensure_response($ads);
    }
}
