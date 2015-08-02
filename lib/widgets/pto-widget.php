<?php
    
class pto_widget extends WP_Widget {

    function __construct() {
        parent::__construct (        
            'pto_widget',         
            'PTO Widget',         
            array( 'description' => 'PTO Widget' ) 
        );
    }
    
    // This is where the action happens
    function widget( $args, $instance ) {       
        global $wpdb, $post;
        
        $user_id = get_current_user_id();
        
        echo $args['before_widget'];        
        $title = apply_filters( 'widget_title', $instance['title'] );        
        if ( ! empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title'];
        
        echo '<div class="menu-sidebar-menu-container">';                    
        echo '<ul id="menu-sidebar-menu" class="menu">';
            
        $args3 = array(
            'post_type' => 'vacation',
            'order'     => 'DESC',            
            'orderby'   => 'author',
            'post_status' => array( 'publish' ),
            'posts_per_page' => -1,
            'author__in' => array($user_id),
            'date_query' => array(
                array(
                    'year' => date( 'Y' ),                    
                ),
            ),
            'meta_query' => array(
                array(
                    'key'     => '_simple_approved',
                    'value'   => "Approved", 
                    'compare' => '=',
                ),
            ),
        );
        $query3 = new WP_Query($args3);
        $author_info = get_userdata($user_id); 
        if ($query3->found_posts == 0) {            
            $hours_remaining = get_the_author_meta('vacation_user_hours_available', $user_id );
            echo '<li><a href="'. bp_core_get_user_domain( $user_id ) . '">' . $author_info->display_name .'</a><label style="color: #666666"> - ' . $hours_remaining . ' hours remaining</label></li>';
        } else {
            while ( $query3->have_posts() ) : $query3->the_post();
                $sdate = get_post_meta($post->ID, '_simple_start_date', true);    
                $edate = get_post_meta($post->ID, '_simple_end_date', true);    
                $stime = get_post_meta($post->ID, '_simple_start_time', true);    
                $etime = get_post_meta($post->ID, '_simple_end_time', true);
                //$pto_approval_status = get_post_meta($post->ID, '_simple_approved', true);    
                if (empty($data[$post->post_author])) $data[$post->post_author] = 0;                
                
                $_start_date = DateTime::createFromFormat('m-d-Y', $sdate);
                $_end_date = DateTime::createFromFormat('m-d-Y', $edate);
                $hours = get_working_hours($_start_date->format("Y-m-d") . " " . $stime, $_end_date->format("Y-m-d") . " " . $etime);                                                                    
                $hours_used += $hours;
            endwhile;
            
            $hours_remaining = get_the_author_meta('vacation_user_hours_available', $key ) - $hours_used;  
            echo '<li><b><a href="'. bp_core_get_user_domain( $user_id ) . '">' . $author_info->display_name .'</a><label style="color: #666666"> - ' . $hours_remaining . ' hours remaining</label></b></li>';
        }
        
        
        $query = "SELECT post_author FROM wp_posts LEFT JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id WHERE meta_key = '_simple_approver_id' AND meta_value = '" . $user_id . "'";
        $results = $wpdb->get_results($query);
        $pto_users = array();
        foreach ($results as $result) {            
            if (!in_array($result->post_author, $pto_users) && $result->post_author != $user_id)
                $pto_users[] = $result->post_author;
        }        
        
        if (!empty($pto_users)) {
            $args2 = array(
                'post_type' => 'vacation',
                'order'     => 'DESC',            
                'orderby'   => 'author',
                'post_status' => array( 'publish' ),
                'posts_per_page' => -1,
                'author__in' => $pto_users,
                'date_query' => array(
                    array(
                        'year' => date( 'Y' ),                    
                    ),
                ),
                'meta_query' => array(
                    array(
                        'key'     => '_simple_approved',
                        'value'   => "Approved", 
                        'compare' => '=',
                    ),
                ),
            );
            $query = new WP_Query( $args2 );
                
            while ( $query->have_posts() ) : $query->the_post();
                $sdate = get_post_meta($post->ID, '_simple_start_date', true);    
                $edate = get_post_meta($post->ID, '_simple_end_date', true);    
                $stime = get_post_meta($post->ID, '_simple_start_time', true);    
                $etime = get_post_meta($post->ID, '_simple_end_time', true);
                //$pto_approval_status = get_post_meta($post->ID, '_simple_approved', true);    
                if (empty($data[$post->post_author])) $data[$post->post_author] = 0;                
                
                $_start_date = DateTime::createFromFormat('m-d-Y', $sdate);
                $_end_date = DateTime::createFromFormat('m-d-Y', $edate);
                $hours = get_working_hours($_start_date->format("Y-m-d") . " " . $stime, $_end_date->format("Y-m-d") . " " . $etime);                                                                    
                $data[$post->post_author] += $hours;
            endwhile;

            foreach ($data as $key => $value) {
                if ($key == $user_id) continue;
                $author_info = get_userdata($key);
                $hours_remaining = get_the_author_meta('vacation_user_hours_available', $key ) - $value;  
                echo '<li><a href="'. bp_core_get_user_domain( $key ) . '">' . $author_info->display_name .'</a><label style="color: #666666"> - ' . $hours_remaining . ' hours remaining</label></li>';
            }
        }
        
        echo '</ul>';
        echo '</div>';    
        echo $args['after_widget'];
        
    }
            
    // Widget Backend 
    function form( $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : "PTO";       
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p> 
        <?php 
    }
        
    // Updating widget replacing old instances with new
    function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }
}

add_action( 'widgets_init', 'wpb_load_widget' );          
function wpb_load_widget() {
    register_widget( 'pto_widget' );
}


?>