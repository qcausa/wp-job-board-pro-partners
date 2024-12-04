<?php
/**
 * Plugin Name: WP Job Board Pro - Partners Extension
 * Plugin URI: http://yourwebsite.com/
 * Description: Adds a Partners post type and user role to WP Job Board Pro
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: http://yourwebsite.com/
 * Text Domain: wp-job-board-pro-partners
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Job_Board_Pro_Partners_Extension {

    public static $prefix;

    public static function init() {
        // Check if WP Job Board Pro plugin is active
        if ( ! class_exists( 'WP_Job_Board_Pro' ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
            return; // Don't initialize if dependency is missing
        }

        self::setup_constants();
        self::includes();
        //add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        //add_action( 'init', array( __CLASS__, 'register_user_role' ) );
        add_action( 'wp_job_board_pro_custom_field_partner_display_hooks', array( __CLASS__, 'partner_display_hooks' ) );
    }

    public static function setup_constants() {
        if (!defined('WP_JOB_BOARD_PRO_PARTNER_PREFIX')) {
            define('WP_JOB_BOARD_PRO_PARTNER_PREFIX', '_partner_');
        }
        self::$prefix = WP_JOB_BOARD_PRO_PARTNER_PREFIX;
        define( 'WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'WP_JOB_BOARD_PRO_PARTNER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

        add_filter( 'wp_job_board_pro_user_roles', array( __CLASS__, 'add_partner_role' ) );
        add_filter( 'wp_job_board_pro_candidate_user_role_excludes', array( __CLASS__, 'exclude_partner_default_registration' ), 10, 3 );
        add_action( 'user_register', array( __CLASS__, 'partner_registration_save' ), 5, 1 );
    }

    public static function includes() {
        // post type
		require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/post-types/class-post-type-partner.php';

        // Include the custom fields and other files for this plugin
        require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/custom-fields/class-fields-manager.php';
        require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/custom-fields/class-custom-fields.php';
    
        require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/class-user.php';
    }

    public static function dependency_notice() {
        echo '<div class="error"><p><strong>WP Job Board Pro - Partners Extension</strong> requires WP Job Board Pro to be installed and active.</p></div>';
    }

    public static function partner_display_hooks($hooks) {
        $hooks[] = array(
            'id' => 'wp-job-board-pro-single-partner-details',
            'title' => __('Single Partner Details', 'wp-job-board-pro-partners'),
            'priority' => 10
        );
        return $hooks;
    }

    public static function register_post_type() {
        $singular = __( 'Partner', 'wp-job-board-pro-partners' );
        $plural   = __( 'Partners', 'wp-job-board-pro-partners' );

        $labels = array(
            'name'                  => $plural,
            'singular_name'         => $singular,
            'add_new'               => sprintf(__( 'Add New %s', 'wp-job-board-pro-partners' ), $singular),
            'add_new_item'          => sprintf(__( 'Add New %s', 'wp-job-board-pro-partners' ), $singular),
            'edit_item'             => sprintf(__( 'Edit %s', 'wp-job-board-pro-partners' ), $singular),
            'new_item'              => sprintf(__( 'New %s', 'wp-job-board-pro-partners' ), $singular),
            'all_items'             => sprintf(__( 'All %s', 'wp-job-board-pro-partners' ), $plural),
            'view_item'             => sprintf(__( 'View %s', 'wp-job-board-pro-partners' ), $singular),
            'search_items'          => sprintf(__( 'Search %s', 'wp-job-board-pro-partners' ), $singular),
            'not_found'             => sprintf(__( 'No %s found', 'wp-job-board-pro-partners' ), $plural),
            'not_found_in_trash'    => sprintf(__( 'No %s found in Trash', 'wp-job-board-pro-partners' ), $plural),
            'parent_item_colon'     => '',
            'menu_name'             => $plural,
        );

        register_post_type( 'partner',
            array(
                'labels'            => $labels,
                'supports'          => array( 'title', 'editor', 'thumbnail', 'comments' ),
                'public'            => true,
                'has_archive'       => true,
                'rewrite'           => array( 'slug' => 'partner' ),
                'menu_position'     => 52,
                'categories'        => array(),
                'menu_icon'         => 'dashicons-groups',
                'show_in_rest'      => true,
            )
        );
    }

    public static function register_user_role() {
        add_role(
            'wp_job_board_pro_partner',
            __( 'Partner', 'wp-job-board-pro-partners' ),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'upload_files' => true,
            )
        );
    }

    public static function add_partner_role( $roles ) {
        $roles['wp_job_board_pro_partner'] = esc_html__('Partner', 'wp-job-board-pro-partners');
        return $roles;
    }

    public static function exclude_partner_default_registration( $exclude, $user_role, $user_id ) {
        if ( $user_role === 'wp_job_board_pro_partner' ) {
            return false;
        }
        return $exclude;
    }

    public static function partner_registration_save( $user_id ) {
        BugFu::log('partner_registration_save');
        $user = get_userdata( $user_id );
        if ( in_array( 'wp_job_board_pro_partner', (array) $user->roles ) ) {
            // Dynamically remove the WP_Job_Board_Pro_User::registration_save method
            remove_action( 'user_register', array( 'WP_Job_Board_Pro_User', 'registration_save' ), 10 );

            $post_title = $user->display_name;
            $post_content = '';
            $post_status = 'publish';

            $partner_data = array(
                'post_title'   => $post_title,
                'post_content' => $post_content,
                'post_status'  => $post_status,
                'post_type'    => 'partner'
            );

            $partner_id = wp_insert_post( $partner_data );

            if ( ! is_wp_error( $partner_id ) ) {
                update_post_meta( $partner_id, self::$prefix . 'user_id', $user_id );
                update_post_meta( $partner_id, self::$prefix . 'display_name', $user->display_name );
                update_post_meta( $partner_id, self::$prefix . 'email', $user->user_email );
                
                update_user_meta( $user_id, 'partner_id', $partner_id );
            }
        }
    }
}

add_action( 'plugins_loaded', array( 'WP_Job_Board_Pro_Partners_Extension', 'init' ), 20 );
