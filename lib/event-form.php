<?php

add_shortcode ( 'cc_event_form', 'cc_get_event_form_shortcode'); 
function cc_get_event_form_shortcode($args = array()) {
    
    ob_start();
    cc_event_form($args);   
    return ob_get_clean();   
}

function cc_event_form($args = array()){
    global $EM_Event;    
    
    if( get_option('dbem_css_editors') ) echo '<div class="css-event-form">';
    if( !is_user_logged_in() && get_option('dbem_events_anonymous_submissions') && em_locate_template('forms/event-editor-guest.php') ){
        em_locate_template('forms/event-editor-guest.php',true, array('args'=>$args));
    }else{
        if( !empty($_REQUEST['success']) ){
            $EM_Event = new EM_Event(); //reset the event
        }
        if( empty($EM_Event->event_id) ){
            $EM_Event = ( is_object($EM_Event) && get_class($EM_Event) == 'EM_Event') ? $EM_Event : new EM_Event();
            //Give a default location & category
            $default_cat = get_option('dbem_default_category');
            $default_loc = get_option('dbem_default_location');
            if( is_numeric($default_cat) && $default_cat > 0 && !empty($EM_Event->get_categories()->categories) ){
                $EM_Category = new EM_Category($default_cat);
                $EM_Event->get_categories()->categories[] = $EM_Category;
            }
            if( is_numeric($default_loc) && $default_loc > 0 && ( empty($EM_Event->get_location()->location_id) && empty($EM_Event->get_location()->location_name) && empty($EM_Event->get_location()->location_address) && empty($EM_Event->get_location()->location_town) ) ){
                $EM_Event->location_id = $default_loc;
                $EM_Event->location = new EM_Location($default_loc);
            }
        }
        em_locate_template('forms/event-editor.php',true, array('args'=>$args));
    }
    if( get_option('dbem_css_editors') ) echo '</div>';
}
  
?>
