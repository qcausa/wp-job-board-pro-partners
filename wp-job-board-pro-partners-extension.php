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

if (! defined('ABSPATH')) {
    exit;
}

class WP_Job_Board_Pro_Partners_Extension
{

    public static $prefix;

    public static function init()
    {
        // Check if WP Job Board Pro plugin is active
        if (! class_exists('WP_Job_Board_Pro')) {
            add_action('admin_notices', array(__CLASS__, 'dependency_notice'));
            return; // Don't initialize if dependency is missing
        }

        self::setup_constants();
        self::includes();
        self::register_nav_menus();

        add_action('init', array(__CLASS__, 'register_post_type'));
        add_action('init', array(__CLASS__, 'register_user_role'));

        add_action('wp_job_board_pro_custom_field_partner_display_hooks', array(__CLASS__, 'partner_display_hooks'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_scripts'));
        add_action('wp_ajax_wp_job_board_pro_partners_ajax_change_status', array(__CLASS__, 'ajax_change_status'));
    }

    public static function register_nav_menus()
    {
        register_nav_menus(array(
            'partner-menu' => esc_html__('Partner Menu', 'superio'),
        ));
    }

    public static function setup_constants()
    {
        if (!defined('WP_JOB_BOARD_PRO_PARTNER_PREFIX')) {
            define('WP_JOB_BOARD_PRO_PARTNER_PREFIX', '_partner_');
        }
        self::$prefix = WP_JOB_BOARD_PRO_PARTNER_PREFIX;
        define('WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('WP_JOB_BOARD_PRO_PARTNER_PLUGIN_URL', plugin_dir_url(__FILE__));

        add_filter('wp_job_board_pro_user_roles', array(__CLASS__, 'add_partner_role'));
        add_filter('wp_job_board_pro_candidate_user_role_excludes', array(__CLASS__, 'exclude_partner_default_registration'), 10, 3);
        add_action('user_register', array(__CLASS__, 'partner_registration_save'), 5, 1);
    }

    public static function includes()
    {
        // post type
        require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/post-types/class-post-type-partner.php';

        // Include the custom fields and other files for this plugin
        require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/custom-fields/class-fields-manager.php';
        require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/custom-fields/class-custom-fields.php';
        require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/custom-fields/cmb2_field_attached_user/cmb2-field-type-attached_user.php';
        require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/class-user.php';
        require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . 'includes/admin/class-post-types-admin.php';
    }

    public static function dependency_notice()
    {
        echo '<div class="error"><p><strong>WP Job Board Pro - Partners Extension</strong> requires WP Job Board Pro to be installed and active.</p></div>';
    }

    public static function partner_display_hooks($hooks)
    {
        $hooks[] = array(
            'id' => 'wp-job-board-pro-single-partner-details',
            'title' => __('Single Partner Details', 'wp-job-board-pro-partners'),
            'priority' => 10
        );
        return $hooks;
    }

    public static function register_post_type()
    {
        $singular = __('Partner', 'wp-job-board-pro-partners');
        $plural   = __('Partners', 'wp-job-board-pro-partners');

        $labels = array(
            'name'                  => $plural,
            'singular_name'         => $singular,
            'add_new'              => sprintf(__('Add New %s', 'wp-job-board-pro-partners'), $singular),
            'add_new_item'         => sprintf(__('Add New %s', 'wp-job-board-pro-partners'), $singular),
            'edit_item'            => sprintf(__('Edit %s', 'wp-job-board-pro-partners'), $singular),
            'new_item'             => sprintf(__('New %s', 'wp-job-board-pro-partners'), $singular),
            'all_items'            => sprintf(__('All %s', 'wp-job-board-pro-partners'), $plural),
            'view_item'            => sprintf(__('View %s', 'wp-job-board-pro-partners'), $singular),
            'search_items'         => sprintf(__('Search %s', 'wp-job-board-pro-partners'), $singular),
            'not_found'            => sprintf(__('No %s found', 'wp-job-board-pro-partners'), $plural),
            'not_found_in_trash'   => sprintf(__('No %s found in Trash', 'wp-job-board-pro-partners'), $plural),
            'parent_item_colon'    => '',
            'menu_name'            => $plural,
        );

        register_post_type(
            'partner',
            array(
                'labels'            => $labels,
                'supports'          => array('title', 'editor', 'thumbnail', 'comments'),
                'public'            => true,
                'has_archive'       => true,
                'rewrite'           => array('slug' => 'partner'),
                'menu_position'     => 52,
                'categories'        => array(),
                'menu_icon'         => 'dashicons-groups',
                'show_in_rest'      => true,
                'capability_type'   => 'post',
                'capabilities'      => array(
                    'create_posts'  => 'edit_posts',
                    'edit_post'     => 'edit_post',
                    'read_post'     => 'read_post',
                    'delete_post'   => 'delete_post',
                    'edit_posts'    => 'edit_posts',
                    'edit_others_posts' => 'edit_others_posts',
                    'publish_posts' => 'publish_posts',
                    'read_private_posts' => 'read_private_posts',
                ),
                'map_meta_cap'      => true,
            )
        );
    }

    public static function register_user_role()
    {
        add_role(
            'wp_job_board_pro_partner',
            __('Partner', 'wp-job-board-pro-partners'),
            array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'upload_files' => true,
                'publish_posts' => true,
                'edit_published_posts' => true,
                'delete_published_posts' => true,
            )
        );
    }

    public static function add_partner_role($roles)
    {
        $roles['wp_job_board_pro_partner'] = esc_html__('Partner', 'wp-job-board-pro-partners');
        return $roles;
    }

    public static function exclude_partner_default_registration($exclude, $user_role, $user_id)
    {
        if ($user_role === 'wp_job_board_pro_partner') {
            return false;
        }
        return $exclude;
    }

    public static function partner_registration_save($user_id)
    {
        BugFu::log('partner_registration_save');
        $user = get_userdata($user_id);
        if (in_array('wp_job_board_pro_partner', (array) $user->roles)) {
            // Dynamically remove the WP_Job_Board_Pro_User::registration_save method
            remove_action('user_register', array('WP_Job_Board_Pro_User', 'registration_save'), 10);

            $post_title = $user->display_name;
            $post_content = '';
            $post_status = 'publish';

            $partner_data = array(
                'post_title'   => $post_title,
                'post_content' => $post_content,
                'post_status'  => $post_status,
                'post_type'    => 'partner'
            );

            $partner_id = wp_insert_post($partner_data);

            if (! is_wp_error($partner_id)) {
                update_post_meta($partner_id, self::$prefix . 'user_id', $user_id);
                update_post_meta($partner_id, self::$prefix . 'display_name', $user->display_name);
                update_post_meta($partner_id, self::$prefix . 'email', $user->user_email);

                update_user_meta($user_id, 'partner_id', $partner_id);
            }
        }
    }

    public static function admin_scripts()
    {
        $screen = get_current_screen();
        if ($screen->post_type == 'partner') {

            wp_register_script('partner-status-change', WP_JOB_BOARD_PRO_PARTNER_PLUGIN_URL . 'js/partner-status-change.js', array('jquery'), '1.0.0', true);

            wp_localize_script('partner-status-change', 'wp_job_board_pro_partners', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce('wp_job_board_pro_partners_ajax_change_status'),
            ));

            wp_enqueue_script('partner-status-change');
        }
    }

    public static function ajax_change_status()
    {
        BugFu::log('ajax_change_status');
        $return = array();
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_job_board_pro_partners_ajax_change_status')) {
            $return = array('status' => false, 'msg' => esc_html__('Security check failed', 'wp-job-board-pro-partners'));
            wp_send_json($return);
            exit;
        }

        $post_id = !empty($_POST['post_id']) ? $_POST['post_id'] : '';
        $status = !empty($_POST['status']) ? $_POST['status'] : '';

        if (empty($post_id) || empty($status)) {
            $return = array('status' => false, 'msg' => esc_html__('Data is not valid', 'wp-job-board-pro-partners'));
            wp_send_json($return);
            exit;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'partner') {
            $return = array('status' => false, 'msg' => esc_html__('Partner not found', 'wp-job-board-pro-partners'));
            wp_send_json($return);
            exit;
        }

        $update_post = array(
            'ID' => $post_id,
            'post_status' => $status,
        );
        wp_update_post($update_post);

        // Send email notification to partner
        $user_id = WP_Job_Board_Pro_Partners_User::get_user_id($post_id);
        if ($user_id) {
            $user = get_user_by('ID', $user_id);
            if ($status == 'publish') {
                $email_subject = sprintf(__('Your partner profile has been approved - %s', 'wp-job-board-pro-partners'), get_bloginfo('name'));
                $email_content = sprintf(__('Hi %s,<br><br>Your partner profile has been approved.<br><br>Thanks,<br>%s', 'wp-job-board-pro-partners'), $user->display_name, get_bloginfo('name'));
            } else {
                $email_subject = sprintf(__('Your partner profile needs review - %s', 'wp-job-board-pro-partners'), get_bloginfo('name'));
                $email_content = sprintf(__('Hi %s,<br><br>Your partner profile has been set to pending review.<br><br>Thanks,<br>%s', 'wp-job-board-pro-partners'), $user->display_name, get_bloginfo('name'));
            }
            WP_Job_Board_Pro_Email::send($user->user_email, $email_subject, $email_content);
        }

        $return = array(
            'status' => true,
            'status_label' => get_post_status_object($status)->label,
            'msg' => sprintf(esc_html__('Partner status changed to %s', 'wp-job-board-pro-partners'), get_post_status_object($status)->label)
        );
        wp_send_json($return);
        exit;
    }
}

// function child_register_user_short_profile_widget() {
//     BugFu::log("Registering partner widget");
//     BugFu::log(WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR);


//     // Optionally, you might want to unregister the original widget:
//     unregister_widget( 'Superio_Widget_User_Short_Profile' );

//     // Include the new widget file.
//     require_once WP_JOB_BOARD_PRO_PARTNER_PLUGIN_DIR . '/includes/widgets/child-user-short-profile.php';

//     // Register your new extended widget.
//     register_widget( 'Child_Superio_Widget_User_Short_Profile' );
// }
// add_action( 'widgets_init', 'child_register_user_short_profile_widget', 11 );

add_action('in_widget_form', 'extension_add_partner_menu_field', 10, 3);
function extension_add_partner_menu_field($widget, $return, $instance)
{
    // Check if this is the base widget you want to extend.
    if ('Superio_Widget_User_Short_Profile' !== get_class($widget)) {
        return;
    }

    // Define a unique key for the new field.
    $key = 'nav_menu_partner';

    // Ensure a default value.
    if (! isset($instance[$key])) {
        $instance[$key] = '';
    }

    // Retrieve available menus.
    $custom_menus = array('' => esc_html__('Choose a menu', 'superio'));
    $menus = get_terms('nav_menu', array('hide_empty' => false));
    if (is_array($menus) && ! empty($menus)) {
        foreach ($menus as $menu) {
            if (is_object($menu) && isset($menu->name, $menu->slug)) {
                $custom_menus[$menu->slug] = $menu->name;
            }
        }
    }

    // Output the additional field.
?>
    <p>
        <label for="<?php echo esc_attr($widget->get_field_id($key)); ?>">
            <?php esc_html_e('Partner Menu:', 'superio'); ?>
        </label>
        <select id="<?php echo esc_attr($widget->get_field_id($key)); ?>"
            name="<?php echo esc_attr($widget->get_field_name($key)); ?>">
            <?php foreach ($custom_menus as $menu_key => $menu_name) : ?>
                <option value="<?php echo esc_attr($menu_key); ?>" <?php selected($instance[$key], $menu_key); ?>>
                    <?php echo esc_html($menu_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
<?php
}

add_filter('widget_update_callback', 'extension_update_partner_menu_field', 10, 4);
function extension_update_partner_menu_field($instance, $new_instance, $old_instance, $widget)
{
    // Check if this is the desired widget.
    if ('Superio_Widget_User_Short_Profile' !== get_class($widget)) {
        return $instance;
    }

    // Use a unique key for the partner menu field.
    $key = 'nav_menu_partner';

    // If the new value is set, sanitize and save it.
    $instance[$key] = ! empty($new_instance[$key]) ? strip_tags($new_instance[$key]) : '';

    return $instance;
}


// Add this to your custom plugin or theme's functions.php

// Add this to your custom plugin or theme's functions.php

function custom_assign_job_listing_type($post_id, $post, $update)
{
    BugFu::log('custom_assign_job_listing_type');
    BugFu::log($post_id);
    BugFu::log($post);
    BugFu::log($update);
    // Check if the post type is 'job_listing'
    if ('job_listing' !== $post->post_type) {
        return;
    }

    // Check if this is a new post, not an update
    if ($update) {
        return;
    }

    // Get the current user
    $current_user = wp_get_current_user();

    // Determine the taxonomy term based on the user role
    $taxonomy_term = '';
    if (in_array('partner', (array) $current_user->roles)) {
        BugFu::log('partner');
        $taxonomy_term = 'partners';
    } elseif (in_array('employer', (array) $current_user->roles)) {
        BugFu::log('employer');
        $taxonomy_term = 'employers';
    }

    // Check if the taxonomy term exists, and create it if it doesn't
    if (! empty($taxonomy_term) && ! term_exists($taxonomy_term, 'job_listing_type')) {
        wp_insert_term(
            $taxonomy_term, // The term
            'job_listing_type', // The taxonomy
            array(
                'description' => 'Jobs created by ' . $taxonomy_term,
                'slug'        => $taxonomy_term
            )
        );
    }

    // Assign the taxonomy term if it's set
    if (! empty($taxonomy_term)) {
        wp_set_object_terms($post_id, $taxonomy_term, 'job_listing_type', true);
        BugFu::log('assign_job_listing_type');
    }
}
add_action('wp_insert_post', 'custom_assign_job_listing_type', 10, 3);

// Add this to your custom plugin or theme's functions.php

// Add this to your custom plugin

function exclude_partner_jobs_from_candidates($query_args)
{
    // Check if the current user is a candidate
    if (is_user_logged_in() && WP_Job_Board_Pro_User::is_candidate()) {
        // Add a tax query to exclude jobs with the 'partners' term
        $tax_query = isset($query_args['tax_query']) ? $query_args['tax_query'] : array();
        $tax_query[] = array(
            'taxonomy' => 'job_listing_type',
            'field'    => 'slug',
            'terms'    => 'partners',
            'operator' => 'NOT IN',
        );
        $query_args['tax_query'] = $tax_query;
    } else if (is_user_logged_in() && WP_Job_Board_Pro_User::is_employer()) {
        $tax_query[] = array(
            'taxonomy' => 'job_listing_type',
            'field'    => 'slug',
            'terms'    => 'partners',
            'operator' => 'IN',
        );
        $query_args['tax_query'] = $tax_query;
    } else if (is_user_logged_in() && WP_Job_Board_Pro_Partners_User::is_partner()) {
        $tax_query[] = array(
            'taxonomy' => 'job_listing_type',
            'field'    => 'slug',
            'terms'    => 'partners',
            'operator' => 'IN',
        );
        $query_args['tax_query'] = $tax_query;
    }

    return $query_args;
}
add_filter('wp-job-board-pro-check-view-job_listing-listing-query-args', 'exclude_partner_jobs_from_candidates');



add_action('plugins_loaded', array('WP_Job_Board_Pro_Partners_Extension', 'init'), 20);
