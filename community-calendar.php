<?php

/*
   Plugin Name: Community Calendar    
   Description: Community Calendar
   Author: Milan Antonov
*/


session_start();    
$SI_DIR = WP_PLUGIN_DIR . '/simple-intranet/';
$CC_PLUGIN_DIR = plugin_dir_path(__FILE__);
$HR_EMAIL = "tvivian@signaturebankga.com";
$PTO_ANNUAL_HOURS = 208;
$same_event = 0;

include($CC_PLUGIN_DIR . 'lib/widgets/cc-events.php');
include($CC_PLUGIN_DIR . 'lib/widgets/pto-widget.php');
include($CC_PLUGIN_DIR . 'lib/vacation-form.php');
include($CC_PLUGIN_DIR . 'lib/event-form.php');
include($CC_PLUGIN_DIR . 'lib/bp-menu.php');
include($CC_PLUGIN_DIR . 'cc-em-activity.php');

require('lib/twilio-php/Services/Twilio.php');  

add_action( 'init', 'cc_init' );
function cc_init() {      
    global $CC_PLUGIN_DIR;
      //require_once $CC_PLUGIN_DIR . 'lib/tribe-date-utils.class.php';
      //require_once $CC_PLUGIN_DIR . 'lib/tribe-view-helpers.class.php';
      
    remove_action('admin_menu' , 'admin_vacation_settings');
      
    remove_filter('em_event_save','bp_em_record_activity_event_save', 10);      
    remove_filter('em_booking_set_status','bp_em_record_activity_booking_save', 100);
    remove_filter('em_booking_save','bp_em_record_activity_booking_save', 100);
    remove_filter('em_booking_delete','bp_em_record_activity_booking_save', 100);
}

register_activation_hook(__FILE__,'cc_plugin_install');
register_deactivation_hook( __FILE__, 'cc_plugin_remove' );
function cc_plugin_install() {

    global $wpdb;

    
    update_option( 'wpfc_limit', 10 );     
    update_option( 'dbem_date_format_js', "mm/dd/yy" );     
    update_option( 'dbem_date_format', "m/d/Y" );
    update_option( 'hide_approver', "Yes" );
    
    $qtips_format = file_get_contents($CC_PLUGIN_DIR . "templates/calendar-tooltip.php");     
    update_option( 'dbem_emfc_qtips_format', $qtips_format );
          
    update_option( 'dbem_cp_events_formats', "1" );
    update_option( 'dbem_list_date_title', "Events/Activities - #F #j, #Y" );      
    
    $single_event_template = file_get_contents($CC_PLUGIN_DIR . "templates/single-event.php");     
    update_option( 'dbem_single_event_format', $single_event_template );
    
    update_option( 'dbem_cron_emails', "1" );    
    
    update_option( 'dbem_event_list_item_format_header', '<table cellpadding="0" cellspacing="0" class="events-table" >
          <thead>
              <tr>
                  <th class="event-time" width="150">Date/Time</th>
                  <th class="event-description" width="*">Event/Activity</th>
              </tr>
             </thead>
          <tbody>');
    update_option( 'vacation_text_label', "PTO" );
    
    // Events/Activities Page
    $the_page_title = 'Add Events/Activities';
    $the_page_name = 'add-events-activities';

    // the menu entry...
    delete_option("cc_add_events_page_title");
    add_option("cc_add_events_page_title", $the_page_title, '', 'yes');
    // the slug...
    delete_option("cc_add_events_page_name");
    add_option("cc_add_events_page_name", $the_page_name, '', 'yes');
    // the id...
    delete_option("cc_add_events_page_id");
    add_option("cc_add_events_page_id", '0', '', 'yes');

    $the_page = get_page_by_title( $the_page_title );

    if ( ! $the_page ) {

        // Create post object
        $_p = array();
        $_p['post_title'] = $the_page_title;
        $_p['post_content'] = "[cc_event_form]";
        $_p['post_status'] = 'publish';
        $_p['post_type'] = 'page';
        $_p['comment_status'] = 'closed';
        $_p['ping_status'] = 'closed';
        $_p['post_category'] = array(1); // the default 'Uncatrgorised'

        // Insert the post into the database
        $the_page_id = wp_insert_post( $_p );

    }
    else {
        // the plugin may have been previously active and the page may just be trashed...
        $the_page_id = $the_page->ID;

        //make sure the page is not trashed...
        $the_page->post_status = 'publish';
        $the_page_id = wp_update_post( $the_page );

    }
    
    delete_option( 'cc_add_events_page_id' );
    add_option( 'cc_add_events_page_id', $the_page_id );
    ////// ================================================= /////////////////
    
    // Community Calendar Page                                           
    $the_page_title = 'Community Calendar';
    $the_page_name = 'community-calendar';

    // the menu entry...
    delete_option("cc_community_calendar_page_title");
    add_option("cc_community_calendar_page_title", $the_page_title, '', 'yes');
    // the slug...
    delete_option("cc_community_calendar_page_name");
    add_option("cc_community_calendar_page_name", $the_page_name, '', 'yes');
    // the id...
    delete_option("cc_community_calendar_page_id");
    add_option("cc_community_calendar_page_id", '0', '', 'yes');

    $the_page = get_page_by_title( $the_page_title );

    if ( ! $the_page ) {

        // Create post object
        $_p = array();
        $_p['post_title'] = $the_page_title;
        $_p['post_content'] = '[fullcalendar post="events"]';
        $_p['post_status'] = 'publish';
        $_p['post_type'] = 'page';
        $_p['comment_status'] = 'closed';
        $_p['ping_status'] = 'closed';
        $_p['post_category'] = array(1); // the default 'Uncatrgorised'

        // Insert the post into the database
        $the_page_id = wp_insert_post( $_p );

    }
    else {
        // the plugin may have been previously active and the page may just be trashed...
        $the_page_id = $the_page->ID;

        //make sure the page is not trashed...
        $the_page->post_status = 'publish';
        $the_page_id = wp_update_post( $the_page );

    }

    delete_option( 'cc_community_calendar_page_id' );
    add_option( 'cc_community_calendar_page_id', $the_page_id );
    
    // Vacation Requestion Form
    $the_page_title = 'PTO Request';
    $the_page_name = 'pto-request';

    // the menu entry...
    delete_option("cc_vacation_request_page_title");
    add_option("cc_vacation_request_page_title", $the_page_title, '', 'yes');
    // the slug...
    delete_option("cc_vacation_request_page_name");
    add_option("cc_vacation_request_page_name", $the_page_name, '', 'yes');
    // the id...
    delete_option("cc_vacation_request_page_id");
    add_option("cc_vacation_request_page_id", '0', '', 'yes');

    $the_page = get_page_by_title( $the_page_title );

    if ( ! $the_page ) {

        // Create post object
        $_p = array();
        $_p['post_title'] = $the_page_title;
        $_p['post_content'] = '[vacation]';
        $_p['post_status'] = 'publish';
        $_p['post_type'] = 'page';
        $_p['comment_status'] = 'closed';
        $_p['ping_status'] = 'closed';
        $_p['post_category'] = array(1); // the default 'Uncatrgorised'

        // Insert the post into the database
        $the_page_id = wp_insert_post( $_p );

    }
    else {
        // the plugin may have been previously active and the page may just be trashed...
        $the_page_id = $the_page->ID;

        //make sure the page is not trashed...
        $the_page->post_status = 'publish';
        $the_page_id = wp_update_post( $the_page );

    }

    delete_option( 'cc_vacation_request_page_id' );
    add_option( 'cc_vacation_request_page_id', $the_page_id );
}

function cc_plugin_remove() {

    global $wpdb;

    $the_page_title = get_option( "cc_add_events_page_title" );
    $the_page_name = get_option( "cc_add_events_page_name" );

    //  the id of our page...
    $the_page_id = get_option( 'cc_add_events_page_id' );
    if( $the_page_id ) {
        wp_delete_post( $the_page_id ); // this will trash, not delete
    }

    delete_option("cc_add_events_page_title");
    delete_option("cc_add_events_page_name");
    delete_option("cc_add_events_page_id");
    
    $the_page_title = get_option( "cc_community_calendar_page_title" );
    $the_page_name = get_option( "cc_community_calendar_page_name" );

    //  the id of our page...
    $the_page_id = get_option( 'cc_community_calendar_id' );
    if( $the_page_id ) {
        wp_delete_post( $the_page_id ); // this will trash, not delete
    }

    delete_option("cc_community_calendar_page_title");
    delete_option("cc_community_calendar_page_name");
    delete_option("cc_community_calendar_page_id");
    
    $the_page_title = get_option( "cc_vacation_request_page_title" );
    $the_page_name = get_option( "cc_vacation_request_page_name" );

    //  the id of our page...
    $the_page_id = get_option( 'cc_community_calendar_id' );
    if( $the_page_id ) {
        wp_delete_post( $the_page_id ); // this will trash, not delete
    }

    delete_option("cc_vacation_request_page_title");
    delete_option("cc_vacation_request_page_name");
    delete_option("cc_vacation_request_page_id");
} 


add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');
function enqueue_admin_scripts($hook) {
    wp_enqueue_style( 'cc_admin_style', plugins_url('/css/admin-style.css', __FILE__) );     
    wp_enqueue_script( 'cc_admin_script', plugins_url('/js/cc_admin_script.js', __FILE__), array( 'jquery' ),'1.0.0', true);    
}
  
add_action('wp_enqueue_scripts', 'cc_enqueue_scripts');
function cc_enqueue_scripts() {  
    wp_enqueue_style( 'cc_style', plugins_url('/css/style.css', __FILE__) );
    wp_enqueue_script( 'cc_script', plugins_url('/js/cc_script.js', __FILE__), array( 'jquery' ),'1.0.0', true);    
}


add_filter('em_locate_template', 'cc_locate_template', 12, 4);
function cc_locate_template($located, $template_name, $load, $args)     
{
    global $CC_PLUGIN_DIR;
    
    $templates = array("placeholders/categories.php", "forms/event-editor.php", "forms/event/location.php", "forms/event/categories-public.php", "forms/event/bookings.php", "forms/event/bookings-ticket-form.php");
    
    if (in_array($template_name, $templates)) {
        $located = $CC_PLUGIN_DIR . "templates/" . $template_name;          
    }
    //echo $template_name;    
    return $located;
}


add_action('init','cc_vacation_add_post', 9); 
function cc_vacation_add_post() {   
    global $post,$carray;
    if(isset($_POST['vacation_event_cat']))
        $carray[] = array($_POST['vacation_event_cat']);
        
    if(isset($_POST['vacation2']) && $_POST['vacation2']=="submit" ) {
        $title2     = $_POST['title2'];
        $vacation_event_category = $_POST['vacation_event_cat'];  
        $vacation_type = $_POST['_simple_vacation_type'];  
        $vacation_start = $_POST['_EventStartDate']['datepicker']; 
        $vacation_end = $_POST['_EventEndDate']['datepicker'];        
        $vacation_approved = $_POST['_simple_approved'];

        $event_start_date = $_POST['_EventStartDate']['datepicker']; 
        $event_start_hour = $_POST['EventStartHour']; 
        $event_start_min = $_POST['EventStartMinute']; 
        $event_start_mer = $_POST['EventStartMeridian']; 
        $event_end_date = $_POST['_EventEndDate']['datepicker']; 
        $event_end_hour = $_POST['EventEndHour']; 
        $event_end_min = $_POST['EventEndMinute']; 
        $event_end_mer = $_POST['EventEndMeridian']; 

        $current_user = wp_get_current_user();
        //$author_id = $_POST['_simple_employee_id']; //$current_user->ID;
        $author_id = $current_user->ID;
        $vacation_author = esc_attr( get_the_author_meta( 'display_name', $author_id ) );
        $vacation_author_email = esc_attr( get_the_author_meta( 'user_email', $author_id ) );
        
        $vacation_approver = esc_attr( get_the_author_meta( 'display_name', $_POST["pto_approver"] ) );
        $vacation_approver_email = esc_attr( get_the_author_meta( 'user_email', $_POST["pto_approver"] ) );

        // Create post object
        $vacation_event_category_id = $_POST['vacation_event_cat'];
        $vacation_event_category = get_cat_name( $vacation_event_category_id );

        $title2 = $vacation_author.' : '.$title2;

        /*$new_vacation_event_post = array(
           'post_title' => $title2,   
           'post_content' => $vacation_type, 
           'post_status' => 'publish',
           'post_author' => 1,
           'EventHideFromUpcoming'=> true,
           'EventStartDate' => $vacation_start,
           'EventEndDate' => $vacation_end,
           'EventStartHour' => $event_start_hour,
           'EventStartMinute' => $event_start_min,
           'EventStartMeridian' => $event_start_mer,
           'EventEndHour' => $event_end_hour,
           'EventEndMinute' => $event_end_min,   
           'EventEndMeridian' => $event_end_mer,
           'EventShowMapLink'=> $event_map_link,
           'EventShowMap' => $event_map,
           'EventCost' => $event_cost,
           'front_email_users'=> $front_email_users,    
           'Organizer' => array(
            'Organizer' => $vacation_author,
            'Email' => $vacation_author_email
           )
        );*/

        //the array of arguments to be inserted with wp_insert_post

        $new_post = array(
            'post_title'    => $title2,
            'post_type'     =>'vacation',
            'post_status'   => 'publish',
            'post_content' => $vacation_type          
        );

        //insert the the post into database by passing $new_post to wp_insert_post
        $pid = wp_insert_post($new_post);

        //we now use $pid (post id) to help add our post meta data
        add_post_meta($pid, '_simple_vacation_type', $vacation_type, true);
        $dt1 = new DateTime($vacation_start);
        $dt2 = new DateTime($vacation_end);
        add_post_meta($pid, '_simple_start_date', $dt1->format("m-d-Y"), true);
        add_post_meta($pid, '_simple_end_date', $dt2->format("m-d-Y"), true);
        add_post_meta($pid, '_simple_employee_id', $author_id, true);
        add_post_meta($pid, '_simple_approver_id', $_POST['pto_approver'], true);
        add_post_meta($pid, '_simple_approver', $vacation_approver, true);
        add_post_meta($pid, '_simple_approver_email', $vacation_approver_email, true);
        add_post_meta($pid, '_simple_approved', $vacation_approved, true);
        add_post_meta($pid, '_simple_author', $vacation_author, true);
        add_post_meta($pid, '_simple_author_email', $vacation_author_email, true);        
        add_post_meta($pid, '_simple_start_time', $_POST['EventStartHour'] . ":" . $_POST['EventStartMinute'] . " " . $_POST['EventStartMeridian'], true);
        add_post_meta($pid, '_simple_end_time', $_POST['EventEndHour'] . ":" . $_POST['EventEndMinute'] . " " . $_POST['EventEndMeridian'], true);
        
        //wp_set_object_terms($idnew, $vacation_event_category, 'tribe_events_cat');
        wp_set_object_terms($pid, $vacation_event_category, 'category');

        // Insert the post into the events calendar
        /*if(get_option('add_vacation_calendar')=='Yes'){
            $idnew = tribe_create_event( $new_vacation_event_post );
            add_post_meta($pid, '_simple_event_id', $idnew, true);

            wp_set_object_terms($idnew, $vacation_event_category, 'tribe_events_cat');
            wp_set_object_terms($pid, $vacation_event_category, 'category');
        }       */
        // end of add to Events calendar

        // notify user submitted and person approving
        $post = get_post($pid);
        if ( ! empty( $_POST['action2'] ) && 'new_vacation' == $_POST['action2'] ) {
            $author_id=$post->post_author; 

            $admin_email = get_option('admin_email'); 
            $website_name = get_option('blogname');

            $p=$post_id;
            if ($p==''){
                $p=$post->ID;
            }
            $meta_approval_status = get_post_meta($p, '_simple_approved', true);
            $app_name = get_post_meta($p, '_simple_approver', true);
            $app_email = get_post_meta($p, '_simple_approver_email', true);
            if($app_email==''){
                $app_email = get_user_meta($author_id,'default_approver_email');    
            }

            $vacation_url = admin_url().'post.php?post=' . $pid . '&action=edit';                       
             
            $message = "Hi ".$vacation_author.",
";
            
            $message .= "Your PTO request \"".$title2."\" has been sent.
";
            if ($app_name!=''){
            $message .= $app_name." has been sent an e-mail to approve your request.
";
            }
            if ($meta_approval_status!=''){
            $message .= "Current approval status: ".$meta_approval_status." 
";
            }
            $message .= "Dates Requested: " . $dt1->format("m/d/Y") . " - " . $dt2->format("m/d/Y")."
";
            if ($app_name!=''){
            $message .= "Person approving: ".$app_name;
            }

            $message2 = "PTO request URL: ".$vacation_url."
";
            if ($vacation_author!=''){
            $message2 .= "Submitted by: ".$vacation_author." 
";
            }
            if ($meta_approval_status!=''){
            $message2 .= "Current approval status: Pending
";
            }
            $message2 .= "Dates Requested: " . $dt1->format("m/d/Y") . " - " . $dt2->format("m/d/Y") . "
";
            if ($app_name!=''){
            $message2 .= "Person approving: ".$app_name;
            }

            if ($app_email==''){
                $app_email=    get_option('admin_email');
            } 
            $ws_title = get_bloginfo('name');
            $ws_email = get_bloginfo('admin_email');
            $headers = 'From: '.$ws_title.' <'.$ws_email.'>' . "\r\n";

            
            wp_mail($vacation_author_email, "Your PTO request update has been submitted", $message, $headers);
            wp_mail($app_email, "Please review this PTO request update", $message2, $headers);
            if ($vacation_approver_email != "" && $vacation_approver_email != $app_email) {
                wp_mail($vacation_approver_email, "Please review this PTO request update", $message2, $headers);    
            }
            
            // sending email to HR
            //wp_mail($HR_EMAIL, "Please review this PTO request update", $message2, $headers);
                        
            // end of email stuff
        }        
    }
}

add_filter('em_event_save', 'cc_event_save', 99, 2);
function cc_event_save($result, $em_event) {
    if(isset($_POST['vacation2']) && $_POST['vacation2'] == "submit" ) { 
        return $em_event->post_id;
    } else {
        global $wpdb;     
        $events_table = EM_EVENTS_TABLE;
        $locations_table = EM_LOCATIONS_TABLE;
        
        $sql = "SELECT * FROM $events_table                
                WHERE event_start_date='" . $em_event->event_start_date . "' AND event_end_date='" . $em_event->event_end_date .
                "' AND event_start_time='" . $em_event->event_start_time . "' AND event_end_time='" . $em_event->event_end_time .
                "' AND location_id=" . $em_event->location_id  . " AND event_id != " . $em_event->event_id;

        $results = $wpdb->get_results(apply_filters('em_events_get_sql',$sql), ARRAY_A);
        
        if (count($results) > 0) {
            $event = new EM_Event($results[0]['event_id']);
            $cat1 = $event->get_categories();
            $cat2 = $em_event->get_categories();
           
            reset($cat1->categories);
            reset($cat2->categories);
            $cat1_id = key($cat1->categories);
            $cat2_id = key($cat2->categories);
            
            
            if ($cat1_id == $cat2_id) {
                $_SESSION["event_exist"] = 1;
                $same_event = 1;
                update_post_meta($em_event->post_id, '_event_rsvp', 0);
                $em_event->set_status(0, true);
            }
        }       
        
        return $result;
    }
}

add_filter('em_event_output_placeholder', 'cc_em_event_output_placeholder', 10, 4);
function cc_em_event_output_placeholder($replace, $this, $full_result, $target) {
  global $EM_Event;
  if ($full_result == "#_CCEVENTTIMES") {
    if ( $EM_Event->event_all_day == 2) {
      $sdate = $EM_Event->event_start_date;
      $edate = $EM_Event->event_start_date;
      
      $stime = get_post_meta($EM_Event->post_id, '_simple_start_time', true);    
      $etime = get_post_meta($EM_Event->post_id, '_simple_end_time', true);    
      $hours = get_working_hours($sdate . " " . $stime, $edate . " " . $etime);
            
      if ($hours < 8) {
        if ($hours == 4) return "Half Day";
        else  return $hours . " Hours";
      } else {
        return get_option('dbem_event_all_day_message');
      }      
    } else {
      return get_option('dbem_event_all_day_message');
    }
    
  }

  return $replace;
}

add_action('save_post', 'cc_update_vacation_visibility');
function cc_update_vacation_visibility($post_id) {
    
    if(get_post_type( $post_id ) != 'vacation') return;
  
    if ( ! empty( $_POST['post_type'] ) && 'vacation' == $_POST['post_type'] ) {
        remove_action('save_post', 'cc_update_vacation_visibility');
        
        $meta_approval_status = $_POST['_simple_approved'];        
        
        $sdate = $_POST['_simple_start_date'];
        $edate = $_POST['_simple_end_date'];
        $stime = $_POST['_simple_start_time'];
        $etime = $_POST['_simple_end_time'];
        
        $_start_date = DateTime::createFromFormat('m-d-Y', $sdate);   
        $_end_date = DateTime::createFromFormat('m-d-Y', $edate);           
        $_start_time = DateTime::createFromFormat('h:i A', $stime);   
        $_end_time = DateTime::createFromFormat('h:i A', $etime);   
        
        if ($meta_approval_status == "Approved") {             
            
            // adding vacation to event
            $event_pid = get_post_meta($post_id, '_simple_event_pid', true);           
            if( ! empty( $event_pid ) ) { // update
            
                $event = em_get_event($event_pid, 'post_id');
                $event->event_start_date = $_start_date->format("Y-m-d");
                $event->event_end_date = $_end_date->format("Y-m-d");
                $event->event_start_time = $_start_time->format("H:i:s");
                $event->event_end_time = $_end_time->format("H:i:s");
                $event->post_content = $_POST['_simple_vacation_type'];
                $event->save();
                $event->set_status(1, true);
                
            } else { // insert
            
                $EM_Event = new EM_Event();
                $author_id = $_POST['_simple_employee_id'];
                $vacation_author = esc_attr( get_the_author_meta( 'display_name', $author_id ) );
        
                $EM_Event->event_name = $vacation_author . " : PTO";
                $EM_Event->event_start_date = $_start_date->format("Y-m-d");
                $EM_Event->event_end_date = $_end_date->format("Y-m-d");
                $EM_Event->event_start_time = date('H:i:s', strtotime($stime));
                $EM_Event->event_end_time = date('H:i:s', strtotime($etime));                           
                
                $hours = get_working_hours($_start_date->format("Y-m-d") . " " . $stime, $_end_date->format("Y-m-d") . " " . $etime);
                if ($hours >= 8)
                  $EM_Event->event_all_day = 1;  
                else
                  $EM_Event->event_all_day = 2;               
                
                $EM_Event->post_content = $_POST['_simple_vacation_type'];
                $EM_Event->save();                         
                $EM_Event->set_status(1, true);
                $event_pid = $EM_Event->post_id;
                
                add_post_meta($event_pid, '_vacation_pid', $post_id, true);
                add_post_meta($post_id, '_simple_event_pid', $event_pid, true);       
                
            }                       
        } else {
            //$event->set_status(0, true);
            
            $event_pid = get_post_meta($post_id, '_simple_event_pid', true);           
            if( ! empty( $event_pid ) ) { // remove event
            
                $event = em_get_event($event_pid, 'post_id');
                $event->post_status = "trash";
                $event->set_status(-1, true);
            }
        }
    }
}

add_action('wp_trash_post', 'trash_vacation');
function trash_vacation($post_id){
    global $post;
    if ($post->post_type === 'vacation') {
        $event_pid = get_post_meta($post_id, '_simple_event_pid', true);           
        if( ! empty( $event_pid ) ) { // remove event
        
            $event = em_get_event($event_pid, 'post_id');
            $event->post_status = "trash";
            $event->set_status(-1, true);
        }
    }   
}

add_action('admin_bar_menu', 'cc_admin_bar_menu', 100);
function cc_admin_bar_menu($admin_bar){
    $admin_bar->add_menu( array(
        'id'    => 'submit-event-request',
        'parent' => 'my-em-events',
        'title' => 'Submit Event/Activity Request',
        'href'  => site_url() . '/add-events-activities',
        'meta'  => array(
            'title' => __('Submit Event/Activity Request'),
            'class' => 'my_menu_item_class'
        ),
    ));
    
    $admin_bar->add_menu( array(
        'id'    => 'pto-request',
        'parent' => 'my-em-events',
        'title' => 'PTO Request',
        'href'  => bp_loggedin_user_domain() . "events/pto-request/",
        'meta'  => array(
            'title' => __('PTO Request'),
            'class' => 'my_menu_item_class'
        ),
    ));
    
    $admin_bar->add_menu( array(
        'id'    => 'my-pto',
        'parent' => 'my-em-events',
        'title' => 'My PTO',
        'href'  => bp_loggedin_user_domain() . "events/my-pto/",
        'meta'  => array(
            'title' => __('My PTO'),
            'class' => 'my_menu_item_class'
        ),
    ));
}

add_filter('gettext', "cc_gettext", 99, 3);
function cc_gettext($translate_text, $text, $domain) {
    if ($text == "Event") return "Event/Activity";
    if ($text == "Events") return "Events/Activities";
    if ($text == "My Event Bookings") return "My Event/Activity Bookings";
    if ($text == "Future events") return "Future events/activities";
    if ($text == "Events With Bookings Enabled") return "Events/Activities With Bookings Enabled";
    if ($text == "My Events") return "My Events/Activities";
    if ($text == "Events I'm Attending") return "Events/Activities I'm Attending";    
    if ($text == "All events") return "All events/activities";
    if ($text == "Past events") return "Past events/activities";
    if ($text == "All Event Categories") return "All Event/Activity Categories";
    
    return $translate_text;
}

//echo ("2015-02-09 08:00 am", "2015-02-09 12:00 pm");
//echo get_working_hours("2014-12-06 08:00 am", "2014-12-06 05:00 pm");
function get_working_hours($date_begin, $date_end) {
    //define('DAY_WORK', 32400); // 9 * 60 * 60
    global $wpdb;
    define('DAY_WORK', 28800); // 8 * 60 * 60
    define('HOUR_START_DAY', '8:00:00');
    define('HOUR_END_DAY', '17:00:00');
    
    define('HOUR_START_BREAK', '12:00:00');
    define('HOUR_END_BREAK', '13:00:00');
    // get begin and end dates of the full period

    // keep the initial dates for later use
    $d1 = new DateTime($date_begin);
    $d2 = new DateTime($date_end);
    
    // and get the datePeriod from the 1st to the last day
    $period_start = new DateTime($d1->format('Y-m-d 00:00:00'));
    $period_end   = new DateTime($d2->format('Y-m-d 23:59:59'));
    $interval = new DateInterval('P1D');
    //$interval = new DateInterval('weekdays'); // 1 day interval to get all days between the period
    $period = new DatePeriod($period_start, $interval, $period_end);

    $worked_time = 0;
    $nb = 0;    
    
    $rows = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "cc_holidays where holiday_date>='" . $d1->format('m-d-Y') . "' AND holiday_date<='" . $d2->format('m-d-Y') . "'" );
    foreach ($rows as $row) {
        $holidays[] = $row->holiday_date;
    }
        
    // for every worked day, add the hours you want
    foreach($period as $date){
      $week_day = $date->format('w'); // 0 (for Sunday) through 6 (for Saturday)
      if (!empty($holidays) && in_array($date->format("m-d-Y"), $holidays)) continue;
      if (!in_array($week_day,array(0, 6)))
      {
        // if this is the first day or the last dy, you have to count only the worked hours 
        
        $start_break = new DateTime($date->format('Y-m-d '.HOUR_START_BREAK));
        $end_break = new DateTime($date->format('Y-m-d '.HOUR_START_BREAK));        
        $start_of_day = new DateTime($date->format('Y-m-d '.HOUR_START_DAY));
        $end_of_day = new DateTime($date->format('Y-m-d '.HOUR_END_DAY));

        if ($date->format('Y-m-d') == $d1->format('Y-m-d') && $date->format('Y-m-d') == $d2->format('Y-m-d')) {
            if ($d1 < $start_of_day) $d1 = $start_of_day;
            if ($d1 > $end_of_day) $d1 = $end_of_day;
            
            if ($d2 > $end_of_day) $d2 = $end_of_day;
            if ($d2 < $start_of_day) $d2 = $start_of_day;            
            if ($d1 < $start_break && $d2 > $end_break) $d1->add(new DateInterval('PT1H'));
            
            $diff = $d1->diff($d2)->format('%H:%I:%S');
            $diff = split(':', $diff);            
            $diff = $diff[0]*3600 + $diff[1]*60 + $diff[2];
            $worked_time += $diff;
        }
        else
        {
            if ($date->format('Y-m-d') == $d1->format('Y-m-d'))
            {                                
                if ($d1 < $start_of_day) $d1 = $start_of_day;
                if ($d1 > $end_of_day) $d1 = $end_of_day;
                if ($d1 < $start_break) $d1->add(new DateInterval('PT1H'));
                
                $diff = $end_of_day->diff($d1)->format("%H:%I:%S");
                $diff = split(':', $diff);                
                $diff = $diff[0]*3600 + $diff[1]*60 + $diff[2];                                
                $worked_time += $diff;
                
            }
            else if ($date->format('Y-m-d') == $d2->format('Y-m-d'))
            {
                if ($d2 > $end_of_day) $d2 = $end_of_day;
                if ($d2 < $start_of_day) $d2 = $start_of_day;
                if ($d2 > $end_break) $d2->sub(new DateInterval('PT1H'));
                
                $diff = $start_of_day->diff($d2)->format('%H:%I:%S');
                $diff = split(':', $diff);                                
                $diff = $diff[0]*3600 + $diff[1]*60 + $diff[2];                
                $worked_time += $diff;
                
            }
            else
            {
                // otherwise, just count the full day of work
                $worked_time += DAY_WORK;                
            }
        }
      }
      if ($nb> 10)
        die("die ".$nb);
    }
    
    return $worked_time/60/60;
}

add_action('admin_menu' , 'cc_admin_vacation_settings');
function cc_admin_vacation_settings() {
    if(get_option('vacation_text_label')!=''){ 
        $vacation_text = get_option('vacation_text_label');                 
    }
    else {
        $vacation_text = 'Vacation';            
    }    
    add_submenu_page('edit.php?post_type=vacation', 'Your '.$vacation_text, 'Your '.$vacation_text, 'edit_posts', 'your-vacation-settings', 'cc_vacation_days_user_settings'); // 'edit_posts'    
    add_submenu_page('edit.php?post_type=vacation', 'Settings', 'Settings', 'edit_others_vacations', 'vacation-settings', 'cc_vacation_days_settings');    
    add_submenu_page('edit.php?post_type=vacation', 'Holidays', 'Holidays', 'edit_others_vacations', 'holiday-settings', 'cc_holiday_settings');    
}

function cc_holiday_settings() {
    global $wpdb;
?>
    <script type="text/javascript">
        var PLUGIN_URL = "<?= plugins_url('community-calendar/'); ?>";
        jQuery(document).ready(function($) {
            $('.vm_holiday_date').datepicker({
                dateFormat : 'mm-dd-yy',
                showOn: "both",
                buttonImageOnly: true,
                buttonImage: PLUGIN_URL + "images/calendar1.png"
            });    
        });
        
      
        
    </script>
    <div id="vm-pinner"></div>
<?php
    
    echo '<h3>Holidays</h3>'; 
    /////////// Holidays //////////////
    if ($_REQUEST['save'] == 'holiday')
    {
         $num = $_REQUEST['holiday_count'];
         for ($i = 1; $i <= $num; $i++)
         {
             if (isset($_REQUEST['holiday_id' . $i]))
             {
                 $wpdb->update($wpdb->prefix . "cc_holidays", array("holiday_name" => $_REQUEST['holiday_name' . $i], "holiday_date" => $_REQUEST['holiday_date' . $i]), array("id" => $_REQUEST['holiday_id' . $i]), array('%s', '%s'), array('%d'));
             }
             else
             {
                 $wpdb->insert($wpdb->prefix . "cc_holidays", array("holiday_name" => $_REQUEST['holiday_name' . $i], "holiday_date" => $_REQUEST['holiday_date' . $i]), array('%s', '%s'));
             }
         }
         
         echo '<div id="message" class="updated" style="margin-left: 0px; margin-bottom: 20px;"><p><strong>Holidays saved.</strong></p></div>';
    }    

    $holidays = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "cc_holidays order by holiday_date");    
    $holiday_count = count($holidays);
    
    echo '<form onsubmit="return add_holiday_validation()" action="edit.php?post_type=vacation&page=holiday-settings&save=holiday" method="post" name="createholiday" id="createholiday" class="validate">';
    echo '<input name="holiday_count" type="hidden" id="holiday_count" value="' . $holiday_count . '">';
    echo '<table id="holidays" width="650px" border="1px" cellspacing="0px" cellpadding="5px" style="border-collapse: collapse; border:1px solid #ccc">';
    echo '<tr><th style="width:300px">Holiday</th><th style="width:300px">Date</th><th style="width:50px;"></th>';
    
    $i = 1;
    if ($wpdb->num_rows > 0)
    {
        foreach ( $holidays as $row ) 
        {
            echo '<tr>        
                        <td>
                            <input name="holiday_id' . $i .'" type="hidden" id="holiday_id' . $i . '" value="' . $row->id . '" />
                            <input class="vm_holiday_name" name="holiday_name' . $i .'" type="text" id="holiday_name' . $i . '" value="' . $row->holiday_name . '"/>
                        </td>
                        <td>
                            <input class="vm_holiday_date" type="text" name="holiday_date' . $i .'" type="text" id="holiday_date' . $i . '" value="' . $row->holiday_date . '" readonly="readonly"/>
                        </td>
                        <td>
                            <img onclick="add_holiday()" src="' . plugins_url('community-calendar/images/add.png') . '" title="Add a row" alt="Add a row" style="cursor:pointer; margin:0 3px;">
                            <img id="remove_holiday' . $i . '" onclick="delete_holiday(' . $i . ')" src="' . plugins_url('community-calendar/images/remove.png') . '" title="Remove this row" alt="Remove this row" class="delete_list_item" style="cursor: pointer; visibility: visible;">
                        </td>
                  </tr>';
            $i++;
        }
    }
    else
    {
        echo '<tr>        
                <td>                    
                    <input class="vm_holiday_name" name="holiday_name' . $i .'" type="text" id="holiday_name' . $i . '" value="" style="width:100%;box-sizing: border-box;" aria-required="true"/>
                </td>
                <td>
                    <input class="vm_holiday_date" type="text" name="holiday_date' . $i .'" type="text" id="holiday_date' . $i . '" value="' . $row->holiday_date . '" readonly="readonly"/>
                </td>
                <td>
                    <img onclick="add_holiday()" src="' . plugins_url('community-calendar/images/add.png') . '" title="Add a row" alt="Add a row" style="cursor:pointer; margin:0 3px;">
                </td>
             </tr>';
    }
    
    echo '</table>';
    $other_attributes = array( 'style' => 'margin-top:5px;' );
    submit_button("Save Holidays", "primary", "save_holidays", false, $other_attributes);
    echo "</form>";    
    echo "<br/>";

}

add_action( 'wp_ajax_vm_ajax_delete_holiday', 'vm_ajax_delete_holiday' );
function vm_ajax_delete_holiday(){
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . "cc_holidays", array( 'id' => $_REQUEST['id'] ), array( '%d' ) );
    die();
}

function cc_vacation_days_user_settings() {
    global $user_id;
    global $PTO_ANNUAL_HOURS;
    
    $user_id = get_current_user_id();        
    $pto_hours = get_the_author_meta( 'vacation_user_hours_available', $user_id );
    
    if ($pto_annual_hours == "") {
        update_user_meta($user_id, 'vacation_user_hours_available', $PTO_ANNUAL_HOURS);
        $pto_hours = $PTO_ANNUAL_HOURS;
    }

    if(get_option('vacation_text_label')!=''){ 
        $vacation_text = get_option('vacation_text_label');                 
    }
    else {
        $vacation_text = 'Vacation';            
    }
?>
    <div class="wrap">
        <h2>Your <?php echo $vacation_text; ?> Hours Available</h2>
        <table width="500px" border="0" cellspacing="0" cellpadding="3">                      
            <tr>
                <td><span>Date you were hired: </span></td>
                <td>
                <?php 
                    $dt = new DateTime(array_shift(get_the_author_meta( 'anniversary', $user_id )));
                    echo $dt->format("m-d-Y");
                ?>
                </td>
            </tr>            
            <tr>
                <td><span>Company fiscal year start date: </span></td>
                <td>
                <?php if(get_option( 'fiscal_month')) {
                    echo date("F jS", mktime(0, 0, 0, get_option( 'fiscal_month'), get_option( 'fiscal_day'))); 
                } ?>
                </td>
            </tr>
            <tr>
                <td><span>Your PTO hours available per period: </span></td>
                <td><?php echo $pto_hours; ?> hours</td>
            </tr>
            <tr>
                <td> <span>Your vacation hours used this period: </span></td>
                <td><?php echo get_the_author_meta('vacation_user_hours_used', $user_id );  ?> hours</td>
            </tr>            
            <tr>
                <td> <span>Your vacation hours left this period: </span></td>
                <td><?php echo get_the_author_meta( 'vacation_user_hours_available', $user_id ) - get_the_author_meta('vacation_user_hours_used', $user_id );  ?> hours</td>
            </tr>         
        </table>
    </div>
<?php  
}

function cc_vacation_days_settings() {
    global $post, $user_id, $PTO_ANNUAL_HOURS;

    if($_POST['author']) {
        $user_id = $_POST['author'];
        update_option('current_author', $_POST['author'] );    
    }
    $user_id=get_option('current_author');

    if($_POST['anniversary']) {
        update_user_meta( $user_id, 'anniversary', $_POST['anniversary'] );    
    }
    if($_POST['vacation_user_hours_available']) {
        update_user_meta( $user_id, 'vacation_user_hours_available', $_POST['vacation_user_hours_available'] );    
    }
    if($_POST['vacation_user_days_available']) {
        update_user_meta( $user_id, 'vacation_user_days_available', $_POST['vacation_user_days_available'] );    
    }
    if($_POST['fiscal_month']) {
        update_option('fiscal_month', $_POST['fiscal_month'] );    
    }
    if($_POST['fiscal_day']) {
        update_option('fiscal_day', $_POST['fiscal_day'] );    
    }
    if($_POST['uk_date']) {
        update_option('vacation_uk_date', $_POST['uk_date'] );    
    }
    if($_POST['manual_vacation']) {
        update_option('manual_vacation', $_POST['manual_vacation'] );    
    }
    if($_POST['exclude_weekends']) {
        update_option('exclude_weekends', $_POST['exclude_weekends'] );    
    }
    if($_POST['hide_approver']) {
        update_option('hide_approver', $_POST['hide_approver'] );    
    }
    if($_POST['add_vacation_calendar']) {
        update_option('add_vacation_calendar', $_POST['add_vacation_calendar'] );    
    }
    if($_POST['default_approver_email']) {
        $user_id=get_option('current_author');
        update_user_meta($user_id, 'default_approver_email', $_POST['default_approver_email'] );    
    }
    if($_POST['vacation_text_label']) {
        update_option('vacation_text_label', $_POST['vacation_text_label'] );    
    }

    if(get_option('vacation_text_label')!='') { 
        $vacation_text = get_option('vacation_text_label');                 
    }
    else {
        $vacation_text = 'Vacation';            
    }
    
    $pto_hours = get_the_author_meta( 'vacation_user_hours_available', $user_id );
    if ($pto_hours == "") {
        update_user_meta($user_id, 'vacation_user_hours_available', $PTO_ANNUAL_HOURS);
        $pto_hours = $PTO_ANNUAL_HOURS;
    }

?>
    <div class="wrap">
        <h2><?php echo $vacation_text; ?> Settings (Admins only)</h2>        
            <table width="500px" border="0" cellspacing="0" cellpadding="3">
                <tr><td colspan="2"><h4>Settings for Selected Users</h4>NOTE: Be sure to click "Save/Update" below after you select a user and change settings.</td></tr> 
                <tr>            
                    <td>
                        <form method="post" action="">
                            <?php wp_dropdown_users(array('name' => 'author', 'selected' => $user_id)); ?>
                            <input type="submit" name="submit_vacation_settings" value="Select User" />
                        </form>
                    </td>
                    <td></td>
                </tr>                 
                
                <form method="post" action="">
                <tr>
                    <td><?php _e('Select employee start date:', 'simpleintranet'); ?></td>
                    <td>
                    <?php 
                        $user_id=get_option('current_author');
                        if($_POST['anniversary']) {
                            $aday=$_POST['anniversary'];
                        } else {
                            $aday= get_the_author_meta( 'anniversary', $user_id ); 
                        }
                        if($aday) {
                            $aday=array_shift($aday);
                        }

                        echo '<input type="date" id="datepicker" name="anniversary[datepicker]" value="'.$aday.'" class="example-datepicker" />';
                    ?>
                    <br />
                    <span class="description"><?php _e('Enter employee start date.', 'simpleintranet'); ?></span>
                    </td>  
                </tr>                   
                <tr>
                    <td><?php _e('Select fiscal period start date:', 'simpleintranet'); ?></td>
                    <td>
                    <?php
                        if($_POST['fiscal_month']) {
                            $fiscal_month = $_POST['fiscal_month'];
                        } else {
                            $fiscal_month= get_option( 'fiscal_month'); 
                        }
                        if($_POST['fiscal_day']) {
                            $fiscal_day=$_POST['fiscal_day'];
                        } else {                            
                            $fiscal_day= get_option( 'fiscal_day'); 
                        }
                        
                        // build months menu
                        echo '<select name="fiscal_month">' . PHP_EOL;
                        if ($fiscal_month!='') {
                            echo '  <option value="' . $fiscal_month . '" selected>' . date('F', mktime(0,0,0,$fiscal_month)) . '</option>' . PHP_EOL;
                        }

                        for ($m=1; $m<=12; $m++) {
                            echo '  <option value="' . $m . '">' . date('F', mktime(0,0,0,$m)) . '</option>' . PHP_EOL;
                        }

                        echo '</select>' . PHP_EOL;
                        
                        // build days menu
                        echo '<select name="fiscal_day">' . PHP_EOL;
                        if ($fiscal_day!='') {
                            echo '  <option value="' . $fiscal_day . '" selected>' . $fiscal_day . '</option>' . PHP_EOL;    
                        }
                        
                        for ($d=1; $d<=31; $d++) {
                            echo '  <option value="' . $d . '">' . $d . '</option>' . PHP_EOL;
                        }
                        echo '</select>' . PHP_EOL;
                    ?>
                    <br />
                    <span class="description"><?php _e('Enter fiscal start date.', 'simpleintranet'); ?></span>
                    </td>
                </tr>                       
                <tr>
                    <td><span><?php echo $vacation_text; ?> hours available per period: </span></td>
                    <td><input style="width: 70px;" type="text" name="vacation_user_hours_available" value="<?php echo $pto_hours; ?>" /> annual</td>
                    <?php 

                        // Calculate current months and days from employee start date
                        $employee_year = date("Y",strtotime($aday));
                        $employee_month = date("m",strtotime($aday));
                        $employee_day = date("d",strtotime($aday));

                        // Calculate total days from employee start date                                                                                                                   
                        $employee_total_days=(($employee_month-1)*30.41666) + $employee_day; 
                        
                        // Calculate total days from fiscal start date
                        $fiscal_total_days=(($fiscal_month-1)*30.41666) + $fiscal_day;
                        
                        // Calculate difference between employee and fiscal start dates in days
                        $available_days = get_the_author_meta( 'vacation_user_days_available', $user_id );
                        $time_year=current_time("Y");
                        $time_month=current_time("m");
                        $time_day=current_time("d");
                        $total_days=(($time_month-1)*30.41666) + $time_day;

                        if($employee_year == $time_year && ($fiscal_total_days >= $employee_total_days)) {
                            $net_days=$total_days - $employee_total_days;
                            $accrued_days = (($net_days) / 365) * $available_days;
                        }
                        if($employee_year == $time_year && ($fiscal_total_days < $employee_total_days)) {
                            $net_days = $total_days - $employee_total_days;
                            $accrued_days = (($net_days) / 365) * $available_days;
                        }
                        if ($employee_year!=$time_year && ($fiscal_total_days >= $employee_total_days)) {
                            $net_days=$fiscal_total_days - $employee_total_days;
                            $accrued_days=(($total_days - abs($net_days)) / 365) * $available_days;
                        }
                        if ($employee_year!=$time_year && ($fiscal_total_days < $employee_total_days)) {
                            $net_days=$employee_total_days - $fiscal_total_days;
                            $accrued_days=(($total_days-abs($net_days)) / 365) * $available_days;
                        }

                        update_user_meta( $user_id, 'vacation_user_days_accrued', round($accrued_days,1) );    

                        // Calculate days used/approved
                        $loop = new WP_Query( array( 'post_type' => 'vacation', 'posts_per_page' => -1, 'author' => $user_id) ); 
                        //print_r($loop);
                        $working_hours = 0;
                        update_user_meta( $user_id, 'vacation_user_hours_used', 0 );
                        $pto_table = "<h2>PTO History</h2><table width='auto' class='pto-table' cellspacing='0px' cellpadding='3px'>
                            <tr><th>No</th><th>Request Date</th><th>PTO Reason</th><th>From - To</th><th>Hours</th><th>Approval Status</th><th>Approver</th></tr>";
                        $pto_no = 0;
                        while ( $loop->have_posts() ) : $loop->the_post(); 
                            $author_id = $post->post_author;  
                            if ($user_id == $author_id) {                                    
                                $pto_no++;
                                $pto_date = new DateTime($post->post_date);                                    
                                $pto_approval_status = get_post_meta($post->ID, '_simple_approved', true);    
                                                                
                                $sdate = get_post_meta($post->ID, '_simple_start_date', true);    
                                $edate = get_post_meta($post->ID, '_simple_end_date', true);    
                                $stime = get_post_meta($post->ID, '_simple_start_time', true);    
                                $etime = get_post_meta($post->ID, '_simple_end_time', true);
                                $approver_id = get_post_meta($post->ID, '_simple_approver_id', true);                                
                                $approver_info = get_userdata($approver_id);
                                
                                $_start_date = DateTime::createFromFormat('m-d-Y', $sdate);
                                $_end_date = DateTime::createFromFormat('m-d-Y', $edate);                                
                                
                                $hours = get_working_hours($_start_date->format("Y-m-d") . " " . $stime, $_end_date->format("Y-m-d") . " " . $etime);                                            
                                
                                $pto_table .= "<tr><td>{$pto_no}</td><td>{$pto_date->format("m-d-Y")}</td><td>{$post->post_content}</td><td>" . $sdate . ' ' . $stime . ' - ' . $edate . ' ' . $etime . 
                                    "</td><td>{$hours} hours</td><td>{$pto_approval_status}</td><td>{$approver_info->display_name}</td></tr>";
                                    
                                if($pto_approval_status == "Approved") {
                                    $event_pid  = get_post_meta($post->ID, '_simple_event_pid', true);

                                    if ($_start_date->format("Y") == $time_year) {
                                        $working_hours += $hours;
                                    }
                                    
                                    $si_holidays = '';

                                    $usdate = strtotime($sdate); // Unix start date
                                    $uedate = strtotime($edate); // Unix end date
                                    $fiscal_start_unix = mktime(0, 0, 0, $fiscal_month, $fiscal_day, $time_year);
                                    $fiscal_end_unix = mktime(0, 0, 0, $fiscal_month, $fiscal_day, $time_year) + (60*60*24*365);

                                    // ensure start date of approved vacation days used is within the current fiscal year and is the author
                                    if(get_option('manual_vacation') == 'No') { // check for manual vacation setting
                                        if($usdate <= $fiscal_end_unix && $usdate >= $fiscal_start_unix) { 
                                            //$updated_days_used=(($uedate-$usdate + (60*60*24))/(60*60*24));
                                            $si_work_days= si_getWorkingDays($sdate,$edate,$si_holidays);
                                            if(get_option('exclude_weekends')=='No'){
                                                $si_work_days=(strtotime($edate)-strtotime($edate))/86400;
                                            }
                                            $cumulative_days=$cumulative_days + $si_work_days; //$updated_days_used;
                                            update_user_meta( $user_id, 'vacation_user_days_used', $cumulative_days );    
                                        }
                                    }// end of check for manual setting of vacation
                                    if(get_option('manual_vacation')=='Yes') { 
                                        //$updated_days_used=(($uedate-$usdate + (60*60*24))/(60*60*24));
                                        $si_work_days= si_getWorkingDays($sdate,$edate,$si_holidays);
                                        if(get_option('exclude_weekends')=='No') {
                                            $si_work_days=(strtotime($edate)-strtotime($edate))/86400;
                                        }
                                        $cumulative_days=$cumulative_days + $si_work_days; //$updated_days_used;
                                        update_user_meta( $user_id, 'vacation_user_days_used', $cumulative_days );            
                                    }    
                                }
                            }
                        endwhile;         
                        if ($pto_no == 0) $pto_table .= "<tr><td colspan='7' style='text-align:center'>No results</td></tr>";
                        $pto_table .= "</table>";
                        
                        if(get_option('manual_vacation')=='No') {
                            $updated_days_left = round($accrued_days,1)-($cumulative_days );
                            if ($updated_days_left <= 0) {
                                $updated_days_left = 0;
                            }
                        }
                        
                        if(get_option('manual_vacation')=='Yes') { 
                            $updated_days_left = round($available_days-$cumulative_days);
                            if ($updated_days_left <= 0) {
                                $updated_days_left = 0;
                            }
                        }

                        update_user_meta( $user_id, 'vacation_user_days_left', $updated_days_left );    
                        update_user_meta( $user_id, 'vacation_user_hours_used', $working_hours );
                    ?> 
                </tr>
                <tr>
                    <td><span><?php echo $vacation_text; ?> hours used this period: </span></td>
                    <td><?php echo get_the_author_meta('vacation_user_hours_used', $user_id ) ? : '0'; ?> used to date</td>
                </tr>  
                <tr>
                    <td><span><?php echo $vacation_text; ?> hours left this period: </span></td>
                    <td><?php echo get_the_author_meta('vacation_user_hours_available', $user_id ) - get_the_author_meta('vacation_user_hours_used', $user_id ); ?> remaining</td>
                </tr>
                <tr>
                    <td><span>Set approver email (admin is default);</span></td>
                    <td>
                        <input type="text"  name="default_approver_email" id="default_approver_email" <?php if(get_user_meta($user_id,'default_approver_email')!='') { 
                            $user_id=get_option('current_author');
                            $user_approver = get_user_meta($user_id,'default_approver_email');    
                            $user_approver1 = array_shift($user_approver);
                            echo 'value="'. $user_approver1.'"';
                        }?>/>
                    </td>
                </tr>
                <tr><td><input type="submit" name="submit_vacation_settings" value="Save/Update" /></td><td></td></tr>
                </form>
            </table>
             
        <?= $pto_table; ?>
        <a href="<?php echo plugins_url('simple-vacation/download.php?user_id=' . $user_id);?>">Download vacations to an Excel XLS file</a>            
    </div>
<?php     
}

?>
