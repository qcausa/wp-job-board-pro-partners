<?php
/**
 * Fields Manager
 *
 * @package    wp-job-board-pro-partners-extension
 * @author     Your Name
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Pro_Partners_Fields_Manager extends WP_Job_Board_Pro_Fields_Manager {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_page' ), 1 );
        add_action( 'init', array(__CLASS__, 'init_hook'), 10 );
	}

    public static function register_page() {

        add_submenu_page( 'edit.php?post_type=partner', __( 'Fields Manager', 'wp-job-board-pro' ), __( 'Fields Manager', 'wp-job-board-pro' ), 'manage_options', 'partner-manager-fields-manager', array( __CLASS__, 'output_partner_fields' ), 9 );
    }

    public static function init_hook() {
        // // Ajax endpoints.
        // add_action( 'wjbp_ajax_wp_job_board_pro_custom_field_html', array( __CLASS__, 'custom_field_html' ) );

        // add_action( 'wjbp_ajax_wp_job_board_pro_custom_field_available_html', array( __CLASS__, 'custom_field_available_html' ) );

        // // compatible handlers.
        // // custom fields
        // add_action( 'wp_ajax_wp_job_board_pro_custom_field_html', array( __CLASS__, 'custom_field_html' ) );
        // add_action( 'wp_ajax_nopriv_wp_job_board_pro_custom_field_html', array( __CLASS__, 'custom_field_html' ) );

        // add_action( 'wp_ajax_wp_job_board_pro_custom_field_available_html', array( __CLASS__, 'custom_field_available_html' ) );
        // add_action( 'wp_ajax_nopriv_wp_job_board_pro_custom_field_available_html', array( __CLASS__, 'custom_field_available_html' ) );

        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'scripts' ), 1 );
    }

    public static function scripts() {
        //wp_enqueue_style('wp-job-board-pro-custom-field-css', WP_JOB_BOARD_PRO_PLUGIN_URL . 'assets/admin/style.css');
        
        // icon
        if ( !empty($_GET['page']) && ($_GET['page'] == 'partner-manager-fields-manager' ) ) {
            wp_enqueue_style('jquery-fonticonpicker', WP_JOB_BOARD_PRO_PLUGIN_URL. 'assets/admin/jquery.fonticonpicker.min.css', array(), '1.0');
            wp_enqueue_style('jquery-fonticonpicker-bootstrap', WP_JOB_BOARD_PRO_PLUGIN_URL. 'assets/admin/jquery.fonticonpicker.bootstrap.min.css', array(), '1.0');
            wp_enqueue_script('jquery-fonticonpicker', WP_JOB_BOARD_PRO_PLUGIN_URL. 'assets/admin/jquery.fonticonpicker.min.js', array(), '1.0', true);

            
            wp_register_script('wp-job-board-pro-custom-field', WP_JOB_BOARD_PRO_PLUGIN_URL.'assets/admin/functions.js', array('jquery', 'wp-color-picker'), '', true);

            $args = array(
                'plugin_url' => WP_JOB_BOARD_PRO_PLUGIN_URL,
                'ajax_url' => admin_url('admin-ajax.php'),
            );
            wp_localize_script('wp-job-board-pro-custom-field', 'wp_job_board_pro_customfield_common_vars', $args);
            wp_enqueue_script('wp-job-board-pro-custom-field');

            wp_enqueue_script('jquery-ui-sortable');
        }
    }

    public static function output_partner_fields() {
        self::output_html(WP_JOB_BOARD_PRO_PARTNER_PREFIX);
    }

    public static function output_html($prefix = WP_JOB_BOARD_PRO_PARTNER_PREFIX) {
        
        self::save($prefix);
        $rand_id = rand(123, 9878787);
        $default_fields = self::get_all_field_types();
        $post_type = '';
        if ( $prefix == WP_JOB_BOARD_PRO_PARTNER_PREFIX ) {
            //$available_fields = self::get_all_types_job_listing_fields_available();
            $required_types = self::get_all_types_partner_fields_required();
            $post_type = 'partner';
        }
        BugFu::log($required_types);

        $custom_all_fields_saved_data = self::get_custom_fields_data($prefix);

        ?>
        <h1><?php echo esc_html__('Fields manager', 'wp-job-board-pro'); ?></h1>

        <form class="job_listing-manager-options" method="post" action="" data-prefix="<?php echo esc_attr($prefix); ?>">
            
            <button type="submit" class="button button-primary" name="updateWPJBFieldManager"><?php esc_html_e('Update', 'wp-job-board-pro'); ?></button>
            
            <div class="custom-fields-wrapper clearfix">
                            
                <div class="wp-job-board-pro-custom-field-form" id="wp-job-board-pro-custom-field-form-<?php echo esc_attr($rand_id); ?>">
                    <div class="box-wrapper">
                        <h3 class="title"><?php echo esc_html('List of Fields', 'wp-job-board-pro'); ?></h3>
                        <ul id="foo<?php echo esc_attr($rand_id); ?>" class="block__list block__list_words"> 
                            <?php

                            $count_node = 1000;
                            $output = '';
                            $all_fields_name_count = 0;
                            $disabled_fields = array();

                            if (is_array($custom_all_fields_saved_data) && sizeof($custom_all_fields_saved_data) > 0) {
                                $field_names_counter = 0;
                                $types = self::get_all_field_type_keys();
                                foreach ($custom_all_fields_saved_data as $key => $custom_field_saved_data) {
                                    $all_fields_name_count++;
                                    
                                    $li_rand_id = rand(454, 999999);

                                    $output .= '<li class="custom-field-class-' . $li_rand_id . '">';

                                    $fieldtype = $custom_field_saved_data['type'];

                                    $delete = true;
                                    $drfield_values = self::get_field_id($fieldtype, $required_types);
                                    //$dvfield_values = self::get_field_id($fieldtype, $available_fields);

                                    if ( !empty($drfield_values) ) {
                                        $count_node ++;
                                        
                                        $delete = false;
                                        $field_values = wp_parse_args( $custom_field_saved_data, $drfield_values);
                                        if ( in_array( $fieldtype, array( $prefix.'title', $prefix.'expiry_date', $prefix.'featured', $prefix.'urgent', $prefix.'filled', $prefix.'posted_by', $prefix.'attached_user' ) ) ) {
                                            $output .= apply_filters('wp_job_board_pro_custom_field_available_simple_html', $fieldtype, $count_node, $field_values, $prefix);
                                        } elseif ( in_array( $fieldtype, array( $prefix.'description' ) ) ) {
                                            $output .= apply_filters('wp_job_board_pro_custom_field_available_description_html', $fieldtype, $count_node, $field_values, $prefix);
                                        } else {
                                            $output .= apply_filters('wp_job_board_pro_custom_field_available_'.$fieldtype.'_html', $fieldtype, $count_node, $field_values, $prefix);
                                        }
                                    } 
                                    // elseif ( !empty($dvfield_values) ) {
                                    //     $count_node ++;
                                    //     $field_values = wp_parse_args( $custom_field_saved_data, $dvfield_values);

                                    //     $dtypes = apply_filters( 'wp_job_board_pro_list_simple_type', array( $prefix.'featured', $prefix.'urgent', $prefix.'address', $prefix.'salary', $prefix.'max_salary', $prefix.'application_deadline_date', $prefix.'apply_url', $prefix.'apply_email', $prefix.'video', $prefix.'profile_url', $prefix.'email', $prefix.'founded_date', $prefix.'website', $prefix.'phone', $prefix.'video_url', $prefix.'socials', $prefix.'team_members', $prefix.'employees', $prefix.'show_profile', $prefix.'job_title', WP_JOB_BOARD_PRO_CANDIDATE_PREFIX.'experience', $prefix.'education', $prefix.'award', $prefix.'skill', $prefix.'tag', $prefix.'company_size' ) );

                                    //     if ( in_array( $fieldtype, $dtypes) ) {
                                    //         $output .= apply_filters('wp_job_board_pro_custom_field_available_simple_html', $fieldtype, $count_node, $field_values, $prefix);
                                    //     } elseif ( in_array( $fieldtype, array( $prefix.'category', $prefix.'type' ) ) ) {
                                    //         $output .= apply_filters('wp_job_board_pro_custom_field_available_tax_html', $fieldtype, $count_node, $field_values, $prefix);
                                    //     } elseif ( in_array($fieldtype, array( $prefix.'featured_image', $prefix.'logo', $prefix.'gallery', $prefix.'attachments', $prefix.'cover_photo', $prefix.'profile_photos', $prefix.'portfolio_photos', $prefix.'cv_attachment', $prefix.'photos' ) )) {
                                    //         $output .= apply_filters('wp_job_board_pro_custom_field_available_files_html', $fieldtype, $count_node, $field_values, $prefix);
                                    //     }  elseif ( in_array($fieldtype, array( $prefix.'experience_time', $prefix.'experience', $prefix.'gender', $prefix.'industry', $prefix.'qualification', $prefix.'career_level', $prefix.'age', $prefix.'languages') )) {
                                    //         $output .= apply_filters( 'wp_job_board_pro_custom_field_available_select_option_html', $fieldtype, $count_node, $field_values, $prefix );
                                    //     } elseif ( in_array( $fieldtype, array( $prefix.'salary_type') ) ) {
                                    //         $output .= apply_filters('wp_job_board_pro_custom_field_available_salary_type_html', $fieldtype, $count_node, $field_values, $prefix);
                                    //     } elseif ( in_array( $fieldtype, array( $prefix.'apply_type') ) ) {
                                    //         $output .= apply_filters('wp_job_board_pro_custom_field_available_apply_type_html', $fieldtype, $count_node, $field_values, $prefix);
                                    //     } elseif ( in_array($fieldtype, array( $prefix.'location' ) )) {
                                    //         $output .= apply_filters( 'wp_job_board_pro_custom_field_available_location_html', $fieldtype, $count_node, $field_values, $prefix );
                                    //     } else {
                                    //         $output .= apply_filters('wp_job_board_pro_custom_field_available_'.$fieldtype.'_html', $fieldtype, $count_node, $field_values, $prefix);
                                    //     }
                                    //     $disabled_fields[] = $fieldtype;
                                    // } 
                                    elseif ( in_array($fieldtype, $types) ) {
                                        $count_node ++;
                                        if ( in_array( $fieldtype, array('text', 'textarea', 'wysiwyg', 'number', 'url', 'email', 'checkbox') ) ) {
                                            $output .= apply_filters('wp_job_board_pro_custom_field_text_html', $fieldtype, $count_node, $custom_field_saved_data, $prefix);
                                        } elseif ( in_array( $fieldtype, array('select', 'multiselect', 'radio') ) ) {
                                            $output .= apply_filters('wp_job_board_pro_custom_field_opts_html', $fieldtype, $count_node, $custom_field_saved_data, $prefix);
                                        } else {
                                            $output .= apply_filters('wp_job_board_pro_custom_field_'.$fieldtype.'_html', $fieldtype, $count_node, $custom_field_saved_data, $prefix);
                                        }
                                    }

                                    $output .= apply_filters('wp_job_board_pro_custom_field_actions_html', $li_rand_id, $count_node, $fieldtype, $delete);
                                    $output .= '</li>';
                                }
                            } else {
                                foreach ($required_types as $field_values) {
                                    $count_node ++;
                                    $li_rand_id = rand(454, 999999);
                                    $output .= '<li class="custom-field-class-' . $li_rand_id . '">';
                                    $output .= apply_filters('wp_job_board_pro_custom_field_available_simple_html', $field_values['id'], $count_node, $field_values, $prefix);

                                    $output .= apply_filters('wp_job_board_pro_custom_field_actions_html', $li_rand_id, $count_node, $field_values['id'], false);
                                    $output .= '</li>';
                                }
                            }
                            echo force_balance_tags($output);
                            ?>
                        </ul>

                        <button type="submit" class="button button-primary" name="updateWPJBFieldManager"><?php esc_html_e('Update', 'wp-job-board-pro'); ?></button>

                        <div class="input-field-types">
                            <h3><?php esc_html_e('Create a custom field', 'wp-job-board-pro'); ?></h3>
                            <div class="input-field-types-wrapper">
                                <select name="field-types" class="wp-job-board-pro-field-types">
                                    <?php foreach ($default_fields as $group) { ?>
                                        <optgroup label="<?php echo esc_attr($group['title']); ?>">
                                            <?php foreach ($group['fields'] as $value => $label) { ?>
                                                <option value="<?php echo esc_attr($value); ?>"><?php echo $label; ?></option>
                                            <?php } ?>
                                        </optgroup>
                                    <?php } ?>
                                </select>
                                <button type="button" class="button btn-add-field" data-randid="<?php echo esc_attr($rand_id); ?>"><?php esc_html_e('Create', 'wp-job-board-pro'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wp-job-board-pro-form-field-list wp-job-board-pro-list">
                    <h3 class="title"><?php esc_html_e('Available Fields', 'wp-job-board-pro'); ?></h3>
                    <?php if ( !empty($available_fields) ) { ?>
                        <ul>
                            <?php foreach ($available_fields as $field) { ?>
                                <li class="<?php echo esc_attr($field['id']); ?> <?php echo esc_attr(in_array($field['id'], $disabled_fields) ? 'disabled' : ''); ?>">
                                    <a class="wp-job-board-pro-custom-field-add-available-field" data-fieldtype="<?php echo esc_attr($field['id']); ?>" data-randid="<?php echo esc_attr($rand_id); ?>" href="javascript:void(0);" data-fieldlabel="<?php echo esc_attr($field['name']); ?>">
                                        <span class="icon-wrapper">
                                            <i class="dashicons dashicons-plus"></i>
                                        </span>
                                        <?php echo esc_html($field['name']); ?>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>
                <div class="clearfix" style="clear: both;"></div>
            </div>

            <script>
                var global_custom_field_counter = <?php echo intval($all_fields_name_count); ?>;
                jQuery(document).ready(function () {
                    
                    jQuery('#foo<?php echo esc_attr($rand_id); ?>').sortable({
                        group: "words",
                        animation: 150,
                        handle: ".field-intro",
                        cancel: ".form-group-wrapper"
                    });
                });
            </script>
        </form>
        <?php
    }

    public static function get_all_types_partner_fields_required() {
        BugFu::log('get_all_types_partner_fields_required');
        $prefix = WP_JOB_BOARD_PRO_PARTNER_PREFIX;
        $fields = array(
            array(
                'name'              => __( 'Partner name', 'wp-job-board-pro' ),
                'id'                => $prefix . 'title',
                'type'              => 'text',
                'default'           => '',
                'attributes'        => array(
                    'required'          => 'required'
                ),
                'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_input'),
            ),
            array(
                'name'              => __( 'Description', 'wp-job-board-pro' ),
                'id'                => $prefix . 'description',
                'type'              => 'wysiwyg',
                'options' => array(
                    'media_buttons' => false,
                    'textarea_rows' => 8,
                    'wpautop' => true,
                    'tinymce'       => array(
                        'plugins'                       => 'lists,paste,tabfocus,wplink,wordpress',
                        'paste_as_text'                 => true,
                        'paste_auto_cleanup_on_paste'   => true,
                        'paste_remove_spans'            => true,
                        'paste_remove_styles'           => true,
                        'paste_remove_styles_if_webkit' => true,
                        'paste_strip_class_attributes'  => true,
                    ),
                ),
            ),
            array(
                'name'              => __( 'Featured Partner', 'wp-job-board-pro' ),
                'id'                => $prefix . 'featured',
                'type'              => 'checkbox',
                'description'       => __( 'Featured partner will be sticky during searches, and can be styled differently.', 'wp-job-board-pro' ),
                'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_checkbox'),
                'disable_check' => true,
                'show_in_submit_form' => '',
                'show_in_admin_edit' => 'yes',
            ),
            array(
                'name'              => __( 'Attached User', 'wp-job-board-pro' ),
                'id'                => $prefix . 'attached_user',
                'type'              => 'wp_job_board_pro_attached_user',
                'disable_check' => true,
                'show_in_submit_form' => '',
                'show_in_admin_edit' => 'yes',
                'disable_check_register' => true,
            ),
        );
        BugFu::log($fields);
        return apply_filters( 'wp-job-board-pro-partner-type-required-fields', $fields );
    }
}

WP_Job_Board_Pro_Partners_Fields_Manager::init();
