<?php

if (!defined('ABSPATH')) {
    exit;
}

class FC_Advertisements_Admin {

    private $db;

    public function __construct($db) {
        $this->db = $db;
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_init', array($this, 'handle_form'));
    }

    /**
     * Add admin menu
     */
    public function add_menu() {
        add_menu_page(
            'FC Advertisements',
            'Advertisements',
            'manage_options',
            'fc-advertisements',
            array($this, 'render_page'),
            'dashicons-megaphone',
            30
        );
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if ($hook !== 'toplevel_page_fc-advertisements') {
            return;
        }
        
        wp_add_inline_style('wp-admin', '
            .fc-ads-wrap { max-width: 800px; margin-top: 20px; }
            .fc-ads-form { background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
            .fc-ads-form .form-field { margin-bottom: 15px; }
            .fc-ads-form label { display: block; font-weight: 600; margin-bottom: 5px; }
            .fc-ads-form input[type="text"], .fc-ads-form input[type="url"], .fc-ads-form select { width: 100%; max-width: 400px; }
            .fc-ads-table { margin-top: 30px; }
            .fc-ads-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; }
            .fc-ads-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; }
        ');
    }

    /**
     * Handle form submission
     */
    public function handle_form() {
        if (!isset($_POST['fc_ads_nonce']) || !wp_verify_nonce($_POST['fc_ads_nonce'], 'fc_ads_create')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $title = sanitize_text_field($_POST['fc_ads_title']);
        $space = sanitize_text_field($_POST['fc_ads_space']);
        $position = sanitize_text_field($_POST['fc_ads_position']);
        $url = esc_url_raw($_POST['fc_ads_url']);
        
        if (empty($title) || empty($space) || empty($position) || empty($url)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>All fields are required.</p></div>';
            });
            return;
        }
        
        $result = $this->db->create(array(
            'title' => $title,
            'space' => $space,
            'position' => $position,
            'url' => $url
        ));
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Advertisement created successfully!</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Failed to create advertisement.</p></div>';
            });
        }
    }

    /**
     * Render admin page
     */
    public function render_page() {
        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'fc_ads_delete_' . $_GET['id'])) {
                $this->db->delete($_GET['id']);
                echo '<div class="notice notice-success"><p>Advertisement deleted successfully!</p></div>';
            }
        }

        $fcom_spaces = $this->db->get_fcom_spaces();
        $advertisements = $this->db->get_all();
        ?>
        <div class="wrap fc-ads-wrap">
            <h1>FC Advertisements</h1>
            
            <div class="fc-ads-form">
                <h2>Create New Advertisement</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('fc_ads_create', 'fc_ads_nonce'); ?>
                    
                    <div class="form-field">
                        <label for="fc_ads_title">Title</label>
                        <input type="text" name="fc_ads_title" id="fc_ads_title" placeholder="Enter advertisement title" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="fc_ads_space">Space</label>
                        <select name="fc_ads_space" id="fc_ads_space" required>
                            <option value="">Select Space</option>
                            <?php foreach ($fcom_spaces as $space) : ?>
                                <option value="<?php echo esc_attr($space->slug); ?>"><?php echo esc_html($space->title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-field">
                        <label for="fc_ads_position">Position</label>
                        <select name="fc_ads_position" id="fc_ads_position" required>
                            <option value="">Select Position</option>
                            <option value="top">Top</option>
                            <option value="middle">Middle</option>
                            <option value="bottom">Bottom</option>
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                    
                    <div class="form-field">
                        <label for="fc_ads_url">URL</label>
                        <input type="url" name="fc_ads_url" id="fc_ads_url" placeholder="https://example.com" required>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button button-primary" value="Create Advertisement">
                    </p>
                </form>
            </div>
            
            <div class="fc-ads-table">
                <h2>Existing Advertisements</h2>
                <?php if (empty($advertisements)) : ?>
                    <p>No advertisements found. Create your first one above!</p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Space</th>
                                <th>Position</th>
                                <th>URL</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($advertisements as $ad) : ?>
                                <tr>
                                    <td><?php echo esc_html($ad->id); ?></td>
                                    <td><?php echo esc_html($ad->title); ?></td>
                                    <td><?php echo esc_html(ucfirst($ad->space)); ?></td>
                                    <td><?php echo esc_html(ucfirst($ad->position)); ?></td>
                                    <td><a href="<?php echo esc_url($ad->url); ?>" target="_blank"><?php echo esc_html($ad->url); ?></a></td>
                                    <td><?php echo esc_html(date('M j, Y', strtotime($ad->created_at))); ?></td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fc-advertisements&action=delete&id=' . $ad->id), 'fc_ads_delete_' . $ad->id); ?>" 
                                           class="button button-small" 
                                           onclick="return confirm('Are you sure you want to delete this advertisement?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
