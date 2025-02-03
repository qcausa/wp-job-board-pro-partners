<?php
/**
 * Child Widget: Extended User Short Profile with Partner Menu option.
 */
class Child_Superio_Widget_User_Short_Profile extends Superio_Widget_User_Short_Profile {
    
    // Optionally set a new widget name for identification
    public function __construct() {
        // Change the widget ID and name so that this widget is separate.
        parent::__construct(); // This registers the basic widget setup

        // Override the widget options.
        $this->id_base = 'child_superio_user_short_profile';
        $this->name    = __('Partner User Short Profile', 'wp-job-board-pro-partners');
    }

    /**
     * Override the widget form to add Partner Menu option.
     */
    public function form( $instance ) {
        $defaults = array(
            'title' => 'Menu',
            'employer_menu' => '',
            'candidate_menu' => '',
            'partner_menu' => '',
        );
        $instance = wp_parse_args((array) $instance, $defaults);

        // Get menus
        $menus = wp_get_nav_menus();
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'superio'); ?></label>
            <input class="widefat" type="text" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" value="<?php echo esc_attr($instance['title']); ?>" />
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('employer_menu')); ?>"><?php esc_html_e('Employer Menu:', 'superio'); ?></label>
            <select id="<?php echo esc_attr($this->get_field_id('employer_menu')); ?>" name="<?php echo esc_attr($this->get_field_name('employer_menu')); ?>">
                <option value=""><?php esc_html_e('- Select Menu -', 'superio'); ?></option>
                <?php foreach ($menus as $menu) { ?>
                    <option value="<?php echo esc_attr($menu->term_id); ?>" <?php selected($instance['employer_menu'], $menu->term_id); ?>>
                        <?php echo esc_html($menu->name); ?>
                    </option>
                <?php } ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('candidate_menu')); ?>"><?php esc_html_e('Candidate Menu:', 'superio'); ?></label>
            <select id="<?php echo esc_attr($this->get_field_id('candidate_menu')); ?>" name="<?php echo esc_attr($this->get_field_name('candidate_menu')); ?>">
                <option value=""><?php esc_html_e('- Select Menu -', 'superio'); ?></option>
                <?php foreach ($menus as $menu) { ?>
                    <option value="<?php echo esc_attr($menu->term_id); ?>" <?php selected($instance['candidate_menu'], $menu->term_id); ?>>
                        <?php echo esc_html($menu->name); ?>
                    </option>
                <?php } ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('partner_menu')); ?>"><?php esc_html_e('Partner Menu:', 'superio'); ?></label>
            <select id="<?php echo esc_attr($this->get_field_id('partner_menu')); ?>" name="<?php echo esc_attr($this->get_field_name('partner_menu')); ?>">
                <option value=""><?php esc_html_e('- Select Menu -', 'superio'); ?></option>
                <?php foreach ($menus as $menu) { ?>
                    <option value="<?php echo esc_attr($menu->term_id); ?>" <?php selected($instance['partner_menu'], $menu->term_id); ?>>
                        <?php echo esc_html($menu->name); ?>
                    </option>
                <?php } ?>
            </select>
        </p>
        <?php
    }

    /**
     * Override the update function to save the new field.
     */
    public function update( $new_instance, $old_instance ) {
        error_log("Widget Update Called");
        BugFu::log("Widget Update Called");
        BugFu::log($new_instance);
        BugFu::log($old_instance);
        
        $instance = array();  // Don't call parent update yet, let's see what's happening
        $instance['title'] = !empty($new_instance['title']) ? strip_tags($new_instance['title']) : '';
        $instance['employer_menu'] = !empty($new_instance['employer_menu']) ? strip_tags($new_instance['employer_menu']) : '';
        $instance['candidate_menu'] = !empty($new_instance['candidate_menu']) ? strip_tags($new_instance['candidate_menu']) : '';
        $instance['partner_menu'] = !empty($new_instance['partner_menu']) ? strip_tags($new_instance['partner_menu']) : '';
        
        BugFu::log("Returning instance:");
        BugFu::log($instance);
        
        return $instance;
    }
    
    /**
     * Optionally, if you need to adjust the widget output (front-end), override widget() here.
     */
    public function widget( $args, $instance ) {
        BugFu::log("Widget Display Called");
        BugFu::log($instance);
        
        if ( !is_user_logged_in() ) {
            return;
        }
        $user_id = get_current_user_id();

        extract($args);
        extract($instance);

        echo trim($before_widget);
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $title = apply_filters('widget_title', $title);

        if ($title) {
            echo trim($before_title . $title . $after_title);
        }

        $menu = '';
        if ( WP_Job_Board_Pro_User::is_employer($user_id) ) {
            $menu = !empty($instance['employer_menu']) ? $instance['employer_menu'] : '';
        } elseif ( WP_Job_Board_Pro_User::is_candidate($user_id) ) {
            $menu = !empty($instance['candidate_menu']) ? $instance['candidate_menu'] : '';
        } elseif ( WP_Job_Board_Pro_Partners_User::is_partner($user_id) ) {
            $menu = !empty($instance['partner_menu']) ? $instance['partner_menu'] : '';
        }

        if ( $menu ) {
            $args = array(
                'menu'    => $menu,
                'container_class' => 'navbar-collapse no-padding',
                'menu_class' => 'menu-dashboard-menu',
                'fallback_cb' => '',
                'walker' => new Superio_Nav_Menu()
            );
            wp_nav_menu($args);
        }
        echo trim($after_widget);
    }
}
