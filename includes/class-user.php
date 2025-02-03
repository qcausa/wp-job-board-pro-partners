<?php
/**
 * User
 *
 * @package    wp-job-board-pro-partners
 * @author     Your Name
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Job_Board_Pro_Partners_User {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'add_user_role'));
    }

    public static function add_user_role() {
        $role = 'wp_job_board_pro_partner';
        if ( !get_role($role) ) {
            add_role($role, __('Partner', 'wp-job-board-pro-partners'), array('read' => true));
        }
    }

    public static function is_partner($user_id = 0) {
        if ( !$user_id ) {
            $user_id = get_current_user_id();
        }
        $user = get_userdata($user_id);
        if ( !empty($user) && in_array('wp_job_board_pro_partner', (array)$user->roles) ) {
            return true;
        }
        return false;
    }

    public static function get_partner_by_user_id($user_id = 0) {
        if ( !$user_id ) {
            $user_id = get_current_user_id();
        }
        $partner_id = get_user_meta($user_id, 'partner_id', true);
        if ( $partner_id ) {
            return $partner_id;
        }
        $args = array(
            'post_type' => 'partner',
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key'     => WP_JOB_BOARD_PRO_PARTNER_PREFIX . 'user_id',
                    'value'   => $user_id,
                    'compare' => '='
                )
            )
        );
        $partners = get_posts($args);
        if ( !empty($partners) && count($partners) == 1 ) {
            update_user_meta($user_id, 'partner_id', $partners[0]);
            return $partners[0];
        }
        return false;
    }

    public static function get_user_id($partner_id = 0) {
        if ( !$partner_id ) {
            return false;
        }
        $user_id = get_post_meta($partner_id, WP_JOB_BOARD_PRO_PARTNER_PREFIX . 'user_id', true);
        
        return $user_id;
    }
}

WP_Job_Board_Pro_Partners_User::init(); 