<?php
/**
 * Custom Fields
 *
 * @package    wp-job-board-pro-partners-extension
 * @author     Your Name
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Pro_Partners_Custom_Fields extends WP_Job_Board_Pro_Custom_Fields {

    public static function init() {
        // Partner
		// submit admin
		add_filter( 'wp-job-board-pro-partner-fields-admin', array( __CLASS__, 'admin_partner_custom_fields' ), 10 );

		// profile frontend
		add_filter( 'wp-job-board-pro-partner-fields-front', array( __CLASS__, 'front_partner_custom_fields' ), 100, 2 );

		// filter fields
		add_filter( 'wp-job-board-pro-default-employer-filter-fields', array( __CLASS__, 'filter_employer_custom_fields' ), 100 );
    }

    public static function admin_partner_custom_fields() {
        BugFu::log('admin_partner_custom_fields');
		$prefix = WP_JOB_BOARD_PRO_PARTNER_PREFIX;
		$init_fields = self::get_custom_fields(array(), true, 0, $prefix);
		BugFu::log($init_fields);
		$fields = array();
		$key_tab = 'tab-heading-start'.rand(100,1000);
		$tab_data = array(
			'id' => $key_tab,
			'icon' => 'dashicons-admin-home',
			'title'  => esc_html__( 'General', 'wp-job-board-pro' ),
			'fields' => array(),
		);
		$i = 0;
		foreach ($init_fields as $key => $field) {
			if ( $i == 0 && (empty($field['type']) || $field['type'] !== 'title') ) {
				$fields[$key_tab] = $tab_data;
			} elseif ( !empty($field['type']) && $field['type'] == 'title' ) {
				$key_tab = $field['id'];
				$fields[$key_tab] = array(
					'id' => $key_tab,
					'icon' => !empty($field['icon']) ? $field['icon'] : '',
					'title'  => !empty($field['name']) ? $field['name'] : '',
					'fields' => array(),
				);
			}

			$fields[$key_tab]['fields'][] = $field;
			$i++;
		}
		

		$box_options = array(
			'id'           => 'partner_metabox',
			'title'        => esc_html__( 'Partner Data', 'wp-job-board-pro' ),
			'object_types' => array( 'partner' ),
			'show_names'   => true,
		);
		
		// Setup meta box
		$cmb = new_cmb2_box( $box_options );

		// Set tabs
		$cmb->add_field( [
			'id'   => '__tabs',
			'type' => 'tabs',
			'tabs' => array(
				'config' => $box_options,
				'layout' => 'vertical', // Default : horizontal
				'tabs'   => apply_filters('wp-job-board-pro-partner-admin-custom-fields', $fields),
			),
		] );

		return true;
	}

    public static function get_custom_fields($old_fields, $admin_field = true, $post_id = 0, $prefix = WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX, $form_type = 'all') {
		BugFu::log('get_custom_fields');
		$fields = array();

		$package_id = 0;
		
		$custom_all_fields = WP_Job_Board_Pro_Fields_Manager::get_custom_fields_data($prefix);
        BugFu::log($custom_all_fields);
		if (is_array($custom_all_fields) && sizeof($custom_all_fields) > 0) {

			$dtypes = WP_Job_Board_Pro_Fields_Manager::get_all_field_type_keys();
	        
	        if ( $prefix == WP_JOB_BOARD_PRO_PARTNER_PREFIX ) {
	            // $available_types = WP_Job_Board_Pro_Fields_Manager::get_all_types_partner_fields_available();
	        	$required_types = WP_Job_Board_Pro_Partners_Fields_Manager::get_all_types_partner_fields_required();
                BugFu::log($required_types);

	        }

			$i = 1;
			foreach ($custom_all_fields as $key => $custom_field) {
				$check_package_field = true;
				if ( $prefix == WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX && !$admin_field ) {
					$check_package_field = self::check_package_field($custom_field, $package_id);
				}
				$check_package_field = apply_filters('wp-job-board-pro-check-package-field', $check_package_field, $old_fields, $admin_field, $post_id, $prefix, $form_type, $custom_field, $package_id);

				$fieldkey = !empty($custom_field['type']) ? $custom_field['type'] : '';
				if ( !empty($fieldkey) && $check_package_field ) {
					$type = '';
					$required_values = WP_Job_Board_Pro_Fields_Manager::get_field_id($fieldkey, $required_types);
					// $available_values = WP_Job_Board_Pro_Fields_Manager::get_field_id($fieldkey, $available_types);
					if ( !empty($required_values) ) {
						$field_data = wp_parse_args( $custom_field, $required_values);
						$fieldtype = isset($required_values['type']) ? $required_values['type'] : '';
					}
                    // elseif ( !empty($available_values) ) {
					// 	$field_data = wp_parse_args( $custom_field, $available_values);
					// 	$fieldtype = isset($available_values['type']) ? $available_values['type'] : '';
					// } 
					elseif ( in_array($fieldkey, $dtypes) ) {
						$fieldkey = isset($custom_field['key']) ? $custom_field['key'] : '';
						$fieldtype = isset($custom_field['type']) ? $custom_field['type'] : '';
						$field_data = $custom_field;
					}
					
					if ( !$admin_field && (!empty($field_data['show_in_submit_form']) || $fieldtype == 'heading') && $fieldkey !== $prefix.'featured' ) {
						if ( $prefix == WP_JOB_BOARD_PRO_CANDIDATE_PREFIX && $form_type == 'profile' && $field_data['show_in_submit_form_candidate'] !== 'profile' ) {
							continue;
						} elseif ( $prefix == WP_JOB_BOARD_PRO_CANDIDATE_PREFIX && $form_type == 'resume' && $field_data['show_in_submit_form_candidate'] !== 'resume' ) {
							continue;
						}
						$fields[] = self::render_field($field_data, $fieldkey, $fieldtype, $i, false, '', $prefix);
					} elseif( $admin_field && (!empty($field_data['show_in_admin_edit']) || $fieldtype == 'heading') && !in_array($fieldkey, apply_filters( 'wp-job-board-exclude-fields-admin', array( $prefix.'title', $prefix.'description', $prefix.'category', $prefix.'type', $prefix.'tag', $prefix.'location', $prefix.'featured_image' )))) {

						$fields[] = self::render_field($field_data, $fieldkey, $fieldtype, $i, $admin_field, '', $prefix);
					}
				}
				$i++;
			}
		} else {
			$fields = $old_fields;
		}
		return $fields;
	}

    public static function front_partner_custom_fields($fields) {
        // Add your custom fields here
        // Example:
        // $fields[] = array(
        //     'name'              => __( 'Custom Field', 'wp-job-board-pro-partners' ),
        //     'id'                => WP_JOB_BOARD_PRO_PARTNER_PREFIX . 'custom_field',
        //     'type'              => 'text',
        //     'default'           => '',
        // );
        return $fields;
    }

   

    public static function custom_fields_manager_page($prefix, $post_type, $custom_fields) {
        parent::custom_fields_display($prefix, $post_type, $custom_fields);
    }
}

WP_Job_Board_Pro_Partners_Custom_Fields::init();
