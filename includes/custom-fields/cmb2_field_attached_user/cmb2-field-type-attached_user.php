<?php

class WP_Job_Board_Pro_Partners_CMB2_Field_Attached_User {

    public static function init() {
        add_filter( 'cmb2_render_wp_job_board_pro_attached_user', array( __CLASS__, 'render_map' ), 10, 5 );
    }

    public static function render_map( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		if ( $field_object_id ) {
			if ( get_post_type($field_object_id) == 'partner' ) {
				$prefix = WP_JOB_BOARD_PRO_PARTNER_PREFIX;
			}
			if ( !empty($prefix) ) {
				$user_id = get_post_meta( $field_object_id, $prefix.'user_id', true );
				$display_name = get_post_meta( $field_object_id, $prefix.'display_name', true );
				$email = get_post_meta( $field_object_id, $prefix.'email', true );
				if ( $user_id ) {
					$html = '<div><strong><a href="' . esc_url(admin_url('user-edit.php?user_id=' . $user_id)) . '">' . esc_html($display_name) . '</a></strong></div>';
					$html .= __('User email: ', 'wp-job-board-pro').$email;
				}
				if ( !empty($html) ) {
					echo wp_kses_post($html);
				}
			}
		}

	}
}

WP_Job_Board_Pro_Partners_CMB2_Field_Attached_User::init(); 