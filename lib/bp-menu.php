<?php
add_action('bp_setup_nav', 'cc_setup_nav', 302 );
function cc_setup_nav() {
    global $bp;    

    $em_link = trailingslashit( bp_displayed_user_domain() . em_bp_get_slug() );
    bp_core_new_subnav_item( array( 
            'name' => __( 'Submit Event/Activity Request', 'dbem' ),
            'slug' => 'event-request',
            'parent_slug' => em_bp_get_slug(),
            'parent_url' => $em_link,
            'screen_function' => 'cc_event_request',
            'position' => 15
        ) 
    );
    
    bp_core_new_subnav_item( array( 
            'name' => __( 'PTO Request', 'dbem' ),
            'slug' => 'pto-request',
            'parent_slug' => em_bp_get_slug(),
            'parent_url' => $em_link,
            'screen_function' => 'cc_pto_request',
            'position' => 60
        ) 
    );
    
    bp_core_new_subnav_item( array( 
            'name' => __( 'My PTO', 'dbem' ),
            'slug' => 'my-pto',
            'parent_slug' => em_bp_get_slug(),
            'parent_url' => $em_link,
            'screen_function' => 'cc_my_pto',
            'position' => 60
        ) 
    );
}

function cc_my_pto_title() {
    _e( 'My PTO', 'dbem' );
}
function cc_my_pto_content() {
    global $post, $HR_EMAIL, $CC_PLUGIN_DIR;
    $user_id = get_current_user_id();
    
    if ($_GET["cancelled"] == "true" && isset($_GET["id"])) {
        update_post_meta($_GET["id"], '_simple_approved', "Cancelled");
        $event_pid = get_post_meta($_GET["id"], '_simple_event_pid', true);
        $sdate = get_post_meta($_GET["id"], '_simple_start_date', true);    
        $edate = get_post_meta($_GET["id"], '_simple_end_date', true);    
        $stime = get_post_meta($_GET["id"], '_simple_start_time', true);    
        $etime = get_post_meta($_GET["id"], '_simple_end_time', true);        
        $approver_email = get_post_meta($_GET["id"], '_simple_approver_email', true);
        
        if( ! empty( $event_pid ) ) { // remove event            
            $event = em_get_event($event_pid, 'post_id');
            $event->post_status = "trash";
            $event->set_status(-1, true);
        }
        
        $ws_title = get_bloginfo('name');
        $ws_email = get_bloginfo('admin_email');
        $headers = 'From: '.$ws_title.' <'.$ws_email.'>' . "\r\n"; 
        $headers .= "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
                
        $user_info = get_userdata($user_id);        
        $first_name = $user_info->first_name;
        $last_name = $user_info->last_name;
        
        ob_start();
        include($CC_PLUGIN_DIR . '/email/cancel_pto.php');
        $message = ob_get_clean();        
        
        wp_mail($HR_EMAIL, "Please review this PTO request update", $message, $headers);
        
        if ($approver_email != "") 
            wp_mail($approver_email, "Please review this PTO request update", $message, $headers);    
        
        echo "<p style='color: red;font-weight: bold; text-align: center;'>Your PTO is cancelled.</p>";
    }
    $pto_table = "<table width='800px' class='pto-table' cellspacing='0px' cellpadding='3px'>
    <tr><th>No</th><th>Request Date</th><th>PTO Reason</th><th>From - To</th><th>Hours</th><th>Approval Status</th><th>Approver</th><th></th></tr>";
    $pto_no = 0;
    
    //$loop = new WP_Query( array( 'post_type' => 'vacation') ); 
    $loop = new WP_Query( array( 'post_type' => 'vacation', 'posts_per_page' => -1, 'author' => $user_id) ); 
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
            $approver = get_post_meta($post->ID, '_simple_approver', true);
            $_start_date = DateTime::createFromFormat('m-d-Y', $sdate);
            $_end_date = DateTime::createFromFormat('m-d-Y', $edate);
            $hours = get_working_hours($_start_date->format("Y-m-d") . " " . $stime, $_end_date->format("Y-m-d") . " " . $etime);        
                
            if($pto_approval_status == "Cancelled") {
                $pto_table .= "<tr><td>{$pto_no}</td><td>{$pto_date->format("m-d-Y")}</td><td>{$post->post_content}</td><td>" . $sdate . ' ' . $stime . ' - ' . $edate . ' ' . $etime . 
                "</td><td>{$hours} hours</td><td>{$pto_approval_status}</td><td>{$approver}</td><td style='text-align:center;'> - </td></tr>";
                
            } else {
                $pto_table .= "<tr><td>{$pto_no}</td><td>{$pto_date->format("m-d-Y")}</td><td>{$post->post_content}</td><td>" . $sdate . ' ' . $stime . ' - ' . $edate . ' ' . $etime . 
                "</td><td>{$hours} hours</td><td>{$pto_approval_status}</td><td>{$approver}</td><td style='text-align:center;'><a class='cancel-pto' href='?id=" . $post->ID . "&cancelled=true'>Cancel</a></td></tr>";
            }
            
        }
    endwhile;
    
    if ($pto_no == 0) $pto_table .= "<tr><td colspan='8' style='text-align:center'>No results</td></tr>";
    $pto_table .= "</table>";
    
    echo $pto_table;

}
function cc_my_pto() {
    global $bp, $EM_Notices;

    add_action( 'bp_template_title', 'cc_my_pto_title' );
    add_action( 'bp_template_content', 'cc_my_pto_content' );
    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );    
}

function cc_pto_request_title() {
    _e( 'PTO Request', 'dbem' );
}
function cc_pto_request_content() {    
   do_shortcode("[cc_vacation]");
}
function cc_pto_request() {
    global $bp, $EM_Notices;

    add_action( 'bp_template_title', 'cc_pto_request_title' );
    add_action( 'bp_template_content', 'cc_pto_request_content' );
    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );    
}

function cc_event_request() {
    global $bp, $EM_Notices;

    add_action( 'bp_template_title', 'cc_event_request_title' );
    add_action( 'bp_template_content', 'cc_event_request_content' );
    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );    
}

function cc_event_request_title() {
    _e( 'Submit Event/Activity Request', 'dbem' );
}
function cc_event_request_content() {    
    cc_event_form();
}
?>
