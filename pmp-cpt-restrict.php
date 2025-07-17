<?php
/**
 * Plugin Name: PMP Custom Post Type Access Restriction
 * Description: Connects Paid Memberships Pro with custom post types to restrict access
 * Version: 1.0.0
 * Author: Mian Shahzad Raza
 * Author URI: https://www.wpacademy.pk
 * License: GPL2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PMP_CPT_Access_Restriction {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Check if PMP is active
        if (!function_exists('pmpro_hasMembershipLevel')) {
            add_action('admin_notices', array($this, 'pmp_required_notice'));
            return;
        }
        
        // Add meta box to custom post types
        add_action('add_meta_boxes', array($this, 'add_membership_meta_box'));
        
        // Save meta box data
        add_action('save_post', array($this, 'save_membership_meta'));
        
        // Restrict content access
        add_action('template_redirect', array($this, 'restrict_cpt_access'));
        
        // Filter content on archive pages
        add_action('pre_get_posts', array($this, 'filter_cpt_archives'));
    }
    
    /**
     * Show admin notice if PMP is not active
     */
    public function pmp_required_notice() {
        echo '<div class="notice notice-error"><p>PMP Custom Post Type Access Restriction requires Paid Memberships Pro to be installed and activated.</p></div>';
    }
    
    /**
     * Add membership level meta box to custom post types
     */
    public function add_membership_meta_box() {
        $post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'pmp_cpt_membership_levels',
                'Membership Access',
                array($this, 'membership_meta_box_callback'),
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Meta box callback function
     */
    public function membership_meta_box_callback($post) {
        // Add nonce for security
        wp_nonce_field('pmp_cpt_meta_box', 'pmp_cpt_meta_nonce');
        
        // Get current membership levels
        $membership_levels = pmpro_getAllLevels(false, true);
        $selected_levels = get_post_meta($post->ID, '_pmp_cpt_membership_levels', true);
        $selected_levels = is_array($selected_levels) ? $selected_levels : array();
        
        echo '<p><strong>Select membership levels that can access this content:</strong></p>';
        echo '<div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 5px;">';
        
        // Option for all members
        $all_members_checked = in_array('all', $selected_levels) ? 'checked' : '';
        echo '<label><input type="checkbox" name="pmp_cpt_levels[]" value="all" ' . $all_members_checked . '> All Members</label><br>';
        
        // Individual membership levels
        foreach ($membership_levels as $level) {
            $checked = in_array($level->id, $selected_levels) ? 'checked' : '';
            echo '<label><input type="checkbox" name="pmp_cpt_levels[]" value="' . esc_attr($level->id) . '" ' . $checked . '> ' . esc_html($level->name) . '</label><br>';
        }
        
        echo '</div>';
        
        // Custom redirect URL
        $redirect_url = get_post_meta($post->ID, '_pmp_cpt_redirect_url', true);
        echo '<p style="margin-top: 15px;"><strong>Custom Redirect URL (optional):</strong></p>';
        echo '<input type="url" name="pmp_cpt_redirect_url" value="' . esc_attr($redirect_url) . '" style="width: 100%;" placeholder="Leave empty to use default PMP redirect">';
        
        // Show excerpt option
        $show_excerpt = get_post_meta($post->ID, '_pmp_cpt_show_excerpt', true);
        $excerpt_checked = $show_excerpt ? 'checked' : '';
        echo '<p style="margin-top: 15px;"><label><input type="checkbox" name="pmp_cpt_show_excerpt" value="1" ' . $excerpt_checked . '> Show excerpt to non-members</label></p>';
    }
    
    /**
     * Save meta box data
     */
    public function save_membership_meta($post_id) {
        // Check nonce
        if (!isset($_POST['pmp_cpt_meta_nonce']) || !wp_verify_nonce($_POST['pmp_cpt_meta_nonce'], 'pmp_cpt_meta_box')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save membership levels
        $membership_levels = isset($_POST['pmp_cpt_levels']) ? array_map('sanitize_text_field', $_POST['pmp_cpt_levels']) : array();
        update_post_meta($post_id, '_pmp_cpt_membership_levels', $membership_levels);
        
        // Save redirect URL
        $redirect_url = isset($_POST['pmp_cpt_redirect_url']) ? esc_url_raw($_POST['pmp_cpt_redirect_url']) : '';
        update_post_meta($post_id, '_pmp_cpt_redirect_url', $redirect_url);
        
        // Save show excerpt option
        $show_excerpt = isset($_POST['pmp_cpt_show_excerpt']) ? 1 : 0;
        update_post_meta($post_id, '_pmp_cpt_show_excerpt', $show_excerpt);
    }
    
    /**
     * Restrict access to custom post type single pages
     */
    public function restrict_cpt_access() {
        if (!is_single()) {
            return;
        }
        
        global $post;
        
        // Check if this is a custom post type
        $post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
        if (!in_array($post->post_type, $post_types)) {
            return;
        }
        
        // Get required membership levels
        $required_levels = get_post_meta($post->ID, '_pmp_cpt_membership_levels', true);
        
        if (empty($required_levels)) {
            return; // No restrictions set
        }
        
        // Check if user has access
        if (!$this->user_has_access($required_levels)) {
            $this->handle_restricted_access($post);
        }
    }
    
    /**
     * Filter custom post type archives
     */
    public function filter_cpt_archives($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Only filter on archive pages for custom post types
        if (!$query->is_archive() && !$query->is_home()) {
            return;
        }
        
        $post_type = $query->get('post_type');
        $post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
        
        if (!in_array($post_type, $post_types)) {
            return;
        }
        
        // Get posts user can access
        $accessible_posts = $this->get_accessible_posts($post_type);
        
        if (!empty($accessible_posts)) {
            $query->set('post__in', $accessible_posts);
        } else {
            // No accessible posts, show none
            $query->set('post__in', array(0));
        }
    }
    
    /**
     * Check if current user has access
     */
    private function user_has_access($required_levels) {
        // If "all" is selected, any member has access
        if (in_array('all', $required_levels)) {
            return pmpro_hasMembershipLevel();
        }
        
        // Check specific levels
        foreach ($required_levels as $level_id) {
            if (pmpro_hasMembershipLevel($level_id)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Handle restricted access
     */
    private function handle_restricted_access($post) {
        $redirect_url = get_post_meta($post->ID, '_pmp_cpt_redirect_url', true);
        $show_excerpt = get_post_meta($post->ID, '_pmp_cpt_show_excerpt', true);
        
        if ($show_excerpt) {
            // Show excerpt instead of redirecting
            add_filter('the_content', array($this, 'replace_content_with_excerpt'));
            return;
        }
        
        if (!empty($redirect_url)) {
            wp_redirect($redirect_url);
            exit;
        }
        
        // Use default PMP redirect
        $levels_page = pmpro_getOption('levels_page_id');
        if ($levels_page) {
            wp_redirect(get_permalink($levels_page));
            exit;
        }
        
        // Fallback to login page
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }
    
    /**
     * Replace content with excerpt and membership message
     */
    public function replace_content_with_excerpt($content) {
        global $post;
        
        $excerpt = get_the_excerpt($post);
        $levels_page = pmpro_getOption('levels_page_id');
        $levels_url = $levels_page ? get_permalink($levels_page) : wp_login_url();
        
        $restricted_content = '<div class="pmp-cpt-restricted">';
        $restricted_content .= '<p>' . $excerpt . '</p>';
        $restricted_content .= '<p><strong>This content is available to members only.</strong></p>';
        $restricted_content .= '<p><a href="' . esc_url($levels_url) . '" class="button">View Membership Options</a></p>';
        $restricted_content .= '</div>';
        
        return $restricted_content;
    }
    
    /**
     * Get posts current user can access
     */
    private function get_accessible_posts($post_type) {
        global $wpdb;
        
        // Get all posts of this type
        $all_posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        
        $accessible_posts = array();
        
        foreach ($all_posts as $post_id) {
            $required_levels = get_post_meta($post_id, '_pmp_cpt_membership_levels', true);
            
            // If no restrictions, include it
            if (empty($required_levels)) {
                $accessible_posts[] = $post_id;
                continue;
            }
            
            // Check if user has access
            if ($this->user_has_access($required_levels)) {
                $accessible_posts[] = $post_id;
            }
        }
        
        return $accessible_posts;
    }
}

// Initialize the plugin
new PMP_CPT_Access_Restriction();

/**
 * Helper function to check if user can access a specific post
 */
function pmp_cpt_user_can_access($post_id) {
    $required_levels = get_post_meta($post_id, '_pmp_cpt_membership_levels', true);
    
    if (empty($required_levels)) {
        return true;
    }
    
    if (in_array('all', $required_levels)) {
        return pmpro_hasMembershipLevel();
    }
    
    foreach ($required_levels as $level_id) {
        if (pmpro_hasMembershipLevel($level_id)) {
            return true;
        }
    }
    
    return false;
}
?>
