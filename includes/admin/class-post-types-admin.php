<?php
/**
 * Post Types Admin
 *
 * @package    wp-job-board-pro-partners
 * @author     Your Name
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Job_Board_Pro_Partners_Post_Types_Admin {

    public static function init() {
        add_filter('manage_partner_posts_columns', array(__CLASS__, 'custom_columns'));
        add_action('manage_partner_posts_custom_column', array(__CLASS__, 'custom_columns_content'), 10, 2);

        add_action('admin_init', array(__CLASS__, 'process_approve_post'));
        add_action('admin_init', array(__CLASS__, 'process_pending_post'));

        add_filter('post_row_actions', array(__CLASS__, 'custom_row_actions'), 10, 2);
    }

    public static function custom_columns($columns) {
        $columns['partner_actions'] = __('Actions', 'wp-job-board-pro-partners');
        return $columns;
    }

    public static function custom_columns_content($column, $post_id) {
        switch ($column) {
            case 'partner_actions':
                
                $post_status = get_post_status($post_id);
        
                // Define the statuses and their corresponding icons
                $statuses = array(
                    'publish' => array('label' => __('Approve', 'wp-job-board-pro-partners'), 'icon' => 'dashicons-yes'),
                    'pending_approve' => array('label' => __('Pending Approve', 'wp-job-board-pro-partners'), 'icon' => 'dashicons-no-alt'),
                );
        
                echo '<div class="partner-status-buttons">';
                foreach ($statuses as $status => $details) {
                    if ($post_status != $status) {
                        echo '<button class="button partner-status-change" data-post-id="' . $post_id . '" data-status="' . $status . '" title="' . esc_attr($details['label']) . '">';
                        echo '<span class="dashicons ' . esc_attr($details['icon']) . '"></span>';
                        echo '<span class="status-label">' . esc_html($details['label']) . '</span>';
                        echo '</button>';
                    }
                }
                echo '</div>';
                
                break;
        }
    }

    public static function custom_row_actions($actions, $post) {
        if ($post->post_type === 'partner') {
            $post_status = get_post_status($post->ID);
            $admin_url = admin_url('admin.php');

            if ($post_status == 'pending_approve') {
                $approve_url = add_query_arg(array(
                    'action' => 'wp_job_board_pro_partners_approve_post',
                    'post_id' => $post->ID,
                    'nonce' => wp_create_nonce('wp-job-board-pro-partners-approve-post')
                ), $admin_url);

                $actions['approve'] = '<a href="' . esc_url($approve_url) . '">' . esc_html__('Approve', 'wp-job-board-pro-partners') . '</a>';
            } elseif ($post_status == 'publish') {
                $pending_url = add_query_arg(array(
                    'action' => 'wp_job_board_pro_partners_pending_post',
                    'post_id' => $post->ID,
                    'nonce' => wp_create_nonce('wp-job-board-pro-partners-pending-post')
                ), $admin_url);

                $actions['pending'] = '<a href="' . esc_url($pending_url) . '">' . esc_html__('Set Pending', 'wp-job-board-pro-partners') . '</a>';
            }
        }
        return $actions;
    }

    public static function process_approve_post() {
        if (!empty($_GET['action']) && $_GET['action'] == 'wp_job_board_pro_partners_approve_post') {
            $post_id = !empty($_GET['post_id']) ? $_GET['post_id'] : '';
            $nonce = !empty($_GET['nonce']) ? $_GET['nonce'] : '';

            if (!wp_verify_nonce($nonce, 'wp-job-board-pro-partners-approve-post')) {
                return;
            }

            $post = get_post($post_id);
            if ($post && $post->post_type === 'partner') {
                $update_post = array(
                    'ID' => $post_id,
                    'post_status' => 'publish',
                );
                wp_update_post($update_post);

                // Send email notification to partner
                $user_id = WP_Job_Board_Pro_Partners_User::get_user_id($post_id);
                if ($user_id) {
                    $user = get_user_by('ID', $user_id);
                    $email_subject = sprintf(__('Your partner profile has been approved - %s', 'wp-job-board-pro-partners'), get_bloginfo('name'));
                    $email_content = sprintf(__('Hi %s,<br><br>Your partner profile has been approved.<br><br>Thanks,<br>%s', 'wp-job-board-pro-partners'), $user->display_name, get_bloginfo('name'));
                    
                    WP_Job_Board_Pro_Email::send($user->user_email, $email_subject, $email_content);
                }

                wp_redirect(admin_url('edit.php?post_type=partner'));
                exit();
            }
        }
    }

    public static function process_pending_post() {
        if (!empty($_GET['action']) && $_GET['action'] == 'wp_job_board_pro_partners_pending_post') {
            $post_id = !empty($_GET['post_id']) ? $_GET['post_id'] : '';
            $nonce = !empty($_GET['nonce']) ? $_GET['nonce'] : '';

            if (!wp_verify_nonce($nonce, 'wp-job-board-pro-partners-pending-post')) {
                return;
            }

            $post = get_post($post_id);
            if ($post && $post->post_type === 'partner') {
                $update_post = array(
                    'ID' => $post_id,
                    'post_status' => 'pending_approve',
                );
                wp_update_post($update_post);

                // Send email notification to partner
                $user_id = WP_Job_Board_Pro_Partners_User::get_user_id($post_id);
                if ($user_id) {
                    $user = get_user_by('ID', $user_id);
                    $email_subject = sprintf(__('Your partner profile needs review - %s', 'wp-job-board-pro-partners'), get_bloginfo('name'));
                    $email_content = sprintf(__('Hi %s,<br><br>Your partner profile has been set to pending review.<br><br>Thanks,<br>%s', 'wp-job-board-pro-partners'), $user->display_name, get_bloginfo('name'));
                    
                    WP_Job_Board_Pro_Email::send($user->user_email, $email_subject, $email_content);
                }

                wp_redirect(admin_url('edit.php?post_type=partner'));
                exit();
            }
        }
    }
}

WP_Job_Board_Pro_Partners_Post_Types_Admin::init(); 