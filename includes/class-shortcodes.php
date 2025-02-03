<?php
/**
 * Shortcodes
 *
 * @package    wp-job-board-pro-partners
 * @author     Your Name
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Job_Board_Pro_Partners_Shortcodes {

    public static function init() {
        // Remove original shortcodes and add our versions with partner support
        remove_shortcode('wp_job_board_pro_my_jobs');
        remove_shortcode('wp_job_board_pro_submission');
        remove_shortcode('wp_job_board_pro_job_applicants');
		remove_shortcode('wp_job_board_pro_change_profile');
        
        add_shortcode('wp_job_board_pro_my_jobs', array(__CLASS__, 'my_jobs'));
        add_shortcode('wp_job_board_pro_submission', array(__CLASS__, 'submission'));
        add_shortcode('wp_job_board_pro_job_applicants', array(__CLASS__, 'job_applicants'));
        add_shortcode('wp_job_board_pro_user_dashboard', array(__CLASS__, 'user_dashboard'), 5);
		add_shortcode('wp_job_board_pro_change_profile', array(__CLASS__, 'change_profile'));
    }

    public static function my_jobs($atts) {
        if (!is_user_logged_in()) {
            return WP_Job_Board_Pro_Template_Loader::get_template_part('misc/need-login');
        } elseif (WP_Job_Board_Pro_User::is_employer() || 
                  (WP_Job_Board_Pro_User::is_employee() && wp_job_board_pro_get_option('employee_view_my_jobs') == 'on') ||
                  WP_Job_Board_Pro_Partners_User::is_partner()) {  // Add partner check
            
            $user_id = WP_Job_Board_Pro_User::get_user_id();
            if (empty($user_id)) {
                return WP_Job_Board_Pro_Template_Loader::get_template_part('misc/not-allowed', array('need_role' => 'employer'));
            }

            if (!empty($_REQUEST['action'])) {
                $action = sanitize_title($_REQUEST['action']);
                
                if ($action == 'edit') {
                    return WP_Job_Board_Pro_Shortcodes::edit_form($atts);
                }
            }
            
            return WP_Job_Board_Pro_Template_Loader::get_template_part('submission/my-jobs');
        }

        return WP_Job_Board_Pro_Template_Loader::get_template_part('misc/not-allowed', array('need_role' => 'employer'));
    }

    public static function user_dashboard( $atts ) {
        BugFu::log("partners user_dashboard");
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/need-login' );
	    } else {
			$user_id = get_current_user_id();
			$user = wp_get_current_user();
        	$roles = ( array ) $user->roles;
			if ( !empty($roles) && in_array('administrator', $roles) ) {
				$user_id = !empty($_GET['user_id']) ? $_GET['user_id'] : $user_id;
			}
		    if ( WP_Job_Board_Pro_User::is_employer($user_id) ) {
				$employer_id = WP_Job_Board_Pro_User::get_employer_by_user_id($user_id);
				return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/employer-dashboard', array( 'user_id' => $user_id, 'employer_id' => $employer_id ) );
			} elseif ( WP_Job_Board_Pro_Partners_User::is_partner($user_id) ) {
            
				$partner_id = WP_Job_Board_Pro_Partners_User::get_partner_by_user_id($user_id);
                BugFu::log($partner_id);
				return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/employer-dashboard', array( 'user_id' => $user_id, 'partner_id' => $partner_id ) );
			} elseif ( WP_Job_Board_Pro_User::is_candidate($user_id) ) {
				$candidate_id = WP_Job_Board_Pro_User::get_candidate_by_user_id($user_id);
				return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/candidate-dashboard', array( 'user_id' => $user_id, 'candidate_id' => $candidate_id ) );
			} elseif ( WP_Job_Board_Pro_User::is_employee($user_id) && wp_job_board_pro_get_option('employee_view_dashboard') == 'on' ) {
				$user_id = WP_Job_Board_Pro_User::get_user_id($user_id);
				if ( empty($user_id) ) {
					return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'employer') );
				}
				$employer_id = WP_Job_Board_Pro_User::get_employer_by_user_id($user_id);
				return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/employer-dashboard', array( 'user_id' => $user_id, 'employer_id' => $employer_id ) );
			}
	    }

    	return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/not-allowed' );
	}

    public static function submission($atts) {
        if (!is_user_logged_in()) {
            return WP_Job_Board_Pro_Template_Loader::get_template_part('misc/need-login');
        } else {
            $user_id = get_current_user_id();
            if (WP_Job_Board_Pro_User::is_employee($user_id)) {
                if (!WP_Job_Board_Pro_User::is_employee_can_add_submission($user_id)) {
                    return WP_Job_Board_Pro_Template_Loader::get_template_part('misc/not-allowed', array('need_role' => 'employer'));
                }
            } elseif (!WP_Job_Board_Pro_User::is_employer($user_id) && !WP_Job_Board_Pro_Partners_User::is_partner($user_id)) {
                return WP_Job_Board_Pro_Template_Loader::get_template_part('misc/not-allowed', array('need_role' => 'employer'));
            }
        }
        
        $form = WP_Job_Board_Pro_Submit_Form::get_instance();
        return $form->output();
    }

    public static function job_applicants($atts) {
        if (!is_user_logged_in()) {
            return WP_Job_Board_Pro_Template_Loader::get_template_part('misc/need-login');
        } elseif (WP_Job_Board_Pro_User::is_employer() || 
                  (WP_Job_Board_Pro_User::is_employee() && wp_job_board_pro_get_option('employee_view_applications') == 'on') ||
                  WP_Job_Board_Pro_Partners_User::is_partner()) {  // Add partner check
           
            $user_id = WP_Job_Board_Pro_User::get_user_id();
            if (empty($user_id)) {
                return WP_Job_Board_Pro_Template_Loader::get_template_part('misc/not-allowed', array('need_role' => 'employer'));
            }

            $jobs_loop = new WP_Query(array(
                'post_type' => 'job_listing',
                'fields' => 'ids',
                'author' => $user_id,
                'orderby' => 'date',
                'order' => 'DESC',
                'posts_per_page' => -1,
            ));

            $job_ids = array();
            if (!empty($jobs_loop) && !empty($jobs_loop->posts)) {
                $job_ids = $jobs_loop->posts;
            }

            return WP_Job_Board_Pro_Template_Loader::get_template_part('misc/job-applicants', array('job_ids' => $job_ids));
        }
        return WP_Job_Board_Pro_Template_Loader::get_template_part('misc/not-allowed', array('need_role' => 'employer'));
    }

	public static function change_profile( $atts ) {
		\BugFu::log("change_profile");
		if ( ! is_user_logged_in() ) {
			return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/need-login' );
		}
		
		$metaboxes = apply_filters( 'cmb2_meta_boxes', array() );
		$metaboxes_form = array();
		$user_id = get_current_user_id();
	
		if ( WP_Job_Board_Pro_User::is_employer($user_id) ) {
			if ( ! isset( $metaboxes[ WP_JOB_BOARD_PRO_EMPLOYER_PREFIX . 'front' ] ) ) {
				return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-job-board-pro' );
			}
			$metaboxes_form = $metaboxes[ WP_JOB_BOARD_PRO_EMPLOYER_PREFIX . 'front' ];
			$post_id = WP_Job_Board_Pro_User::get_employer_by_user_id($user_id);
	
		} elseif ( WP_Job_Board_Pro_Partners_User::is_partner($user_id) ) {
			// Handle partners
			if ( ! isset( $metaboxes[ '_partner_front' ] ) ) {
				return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-job-board-pro' );
			}
			$metaboxes_form = $metaboxes[ '_partner_front' ];
			$post_id = WP_Job_Board_Pro_Partners_User::get_partner_by_user_id($user_id);
	
		} elseif( WP_Job_Board_Pro_User::is_candidate($user_id) ) {
			if ( ! isset( $metaboxes[ WP_JOB_BOARD_PRO_CANDIDATE_PREFIX . 'front' ] ) ) {
				return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-job-board-pro' );
			}
			$metaboxes_form = $metaboxes[ WP_JOB_BOARD_PRO_CANDIDATE_PREFIX . 'front' ];
			$post_id = WP_Job_Board_Pro_User::get_candidate_by_user_id($user_id);
	
		} elseif ( WP_Job_Board_Pro_User::is_employee($user_id) && wp_job_board_pro_get_option('employee_edit_employer_profile') == 'on' ) {
			$user_id = WP_Job_Board_Pro_User::get_user_id($user_id);
			if ( empty($user_id) ) {
				return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'employer') );
			}
	
			if ( ! isset( $metaboxes[ WP_JOB_BOARD_PRO_EMPLOYER_PREFIX . 'front' ] ) ) {
				return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-job-board-pro' );
			}
			$metaboxes_form = $metaboxes[ WP_JOB_BOARD_PRO_EMPLOYER_PREFIX . 'front' ];
			$post_id = WP_Job_Board_Pro_User::get_employer_by_user_id($user_id);
	
		} else {
			return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/not-allowed' );
		}
	
		if ( !$post_id ) {
			return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/not-allowed' );
		}
	
		wp_enqueue_script('google-maps');
		wp_enqueue_script('wpjbp-select2');
		wp_enqueue_style('wpjbp-select2');
	
		return WP_Job_Board_Pro_Template_Loader::get_template_part( 'misc/profile-form', array('post_id' => $post_id, 'metaboxes_form' => $metaboxes_form ) );
	}
	
}

WP_Job_Board_Pro_Partners_Shortcodes::init(); 