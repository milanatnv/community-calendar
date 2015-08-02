<?php

add_shortcode( 'cc_vacation', 'cc_vacation_shortcode' ); 

function cc_vacation_shortcode() {
    global $pid;
    
    wp_enqueue_script('jquery-ui-datepicker');    
    wp_enqueue_script( 'nonuk_js', plugins_url( 'simple-vacation/js/nonuk.js' )); 
    wp_enqueue_style('jquery-style', plugins_url( 'simple-vacation/css/datepicker.css' ) );         

    
    // add to Events calendar
    require_once(  ABSPATH .'wp-content/plugins/simple-intranet/lib/the-events-calendar.class.php' );
    require_once(  ABSPATH .'wp-content/plugins/simple-intranet/lib/tribe-view-helpers.class.php' );
    require_once(  ABSPATH .'wp-content/plugins/simple-intranet/lib/tribe-event-api.class.php' );    

    $_EventStartDate = (isset($_EventStartDate)) ? $_EventStartDate : null;
    $_EventEndDate = (isset($_EventEndDate)) ? $_EventEndDate : null;
    $_EventAllDay = isset($_EventAllDay) ? $_EventAllDay : false;               
    //$isEventAllDay = ( $_EventAllDay == 'yes' || ! TribeDateUtils::dateOnly( $_EventStartDate ) ) ? 'checked="checked"' : ''; // default is all day for new posts
    $startMonthOptions         = TribeEventsViewHelpers::getMonthOptions( $_EventStartDate );
    $endMonthOptions         = TribeEventsViewHelpers::getMonthOptions( $_EventEndDate );
    $startYearOptions         = TribeEventsViewHelpers::getYearOptions( $_EventStartDate );
    $endYearOptions            = TribeEventsViewHelpers::getYearOptions( $_EventEndDate );         
    $startMinuteOptions     = TribeEventsViewHelpers::getMinuteOptions( $_EventStartDate, true );
    $endMinuteOptions        = TribeEventsViewHelpers::getMinuteOptions( $_EventEndDate );
    $startHourOptions        = TribeEventsViewHelpers::getHourOptions( $_EventAllDay == 'yes' ? null : $_EventStartDate, true );
    $endHourOptions            = TribeEventsViewHelpers::getHourOptions( $_EventAllDay == 'yes' ? null : $_EventEndDate );
    $startMeridianOptions     = TribeEventsViewHelpers::getMeridianOptions( $_EventStartDate, true );
    $endMeridianOptions        = TribeEventsViewHelpers::getMeridianOptions( $_EventEndDate );
    $_EventHideFromUpcoming = true;    
                            
    if(!empty($_POST['vacation2']) && $_POST['vacation2']=="submit" && !empty( $_POST['action2'] )) {
        echo '<div style="margin-bottom: 12px;"><font color="red"><strong>Thanks for submitting your PTO!</strong></font></div>';
    }
?>
    <form method="post" name="pto_form" action="<?php echo esc_url(add_query_arg(array('success'=>null))); ?>">
        <input type="hidden" name="title2" id="title2" value="PTO" />
       <tr><td>PTO category:
<?php 
     $customPostTaxonomies = get_object_taxonomies('vacation');
         foreach($customPostTaxonomies as $tax)
         {
            $args = array(
                  'show_option_all'    => '',
                  'show_option_none'   => 'None',              
                  'name' => 'vacation_event_cat',
                  'show_count' => 0,
                  'hide_empty'=>0,
                  'pad_counts' => 0,
                  'hierarchical' => 1,
                  'taxonomy' => $tax,
                  'title_li' => '',
                );
            wp_dropdown_categories( $args );        
            
            } 
?>
           <br /><br /></td>
       </tr>
    Reason(s) for PTO:<br /> <textarea name="_simple_vacation_type" id="_simple_vacation_type" rows="5" cols="70"></textarea><br />
    Start date: <input name="_EventStartDate[datepicker]"  type="date"  id="datepicker" value="Click here" />
    <span class='timeofdayoptions'>
                        <?php _e('@','tribe-events-calendar'); ?>
                        <label>
                        <select name='EventStartHour' id='EventStartHour'>
                            <?php echo $startHourOptions; ?>
                        </select>
                        <select name='EventStartMinute' id='EventStartMinute'>
                            <?php echo $startMinuteOptions; ?>
                        </select>
                        <?php if ( !strstr( get_option( 'time_format', TribeDateUtils::TIMEFORMAT ), 'H' ) ) : ?>
                            <select name='EventStartMeridian' id='EventStartMeridian' >
                                <?php echo $startMeridianOptions; ?>
                            </select></label>
                        <?php endif; ?>
                    </span><br /><br />
    End date: <input  name="_EventEndDate[datepicker]" type="date" cid="datepicker" value="Click here" />
    <span class='timeofdayoptions'>
                        <?php _e('@','tribe-events-calendar'); ?>
                        <label>
                        <select class="tribeEventsInput"  name='EventEndHour' id='EventEndHour'>
                            <?php echo $endHourOptions; ?>
                        </select>
                        <select name='EventEndMinute' id='EventEndMinute'>
                            <?php echo $endMinuteOptions; ?>
                        </select>
                        <?php if ( !strstr( get_option( 'time_format', TribeDateUtils::TIMEFORMAT ), 'H' ) ) : ?>
                            <select name='EventEndMeridian' id='EventEndMeridian'>
                                <?php echo $endMeridianOptions; ?>
                            </select></label>
                        <?php endif; ?>
                    </span><br /><br />
                    <tr><td>PTO All Day? </td><td><input  type='checkbox' id='_EventAllDay' name='_EventAllDay' value='yes' /></td></tr><br /><br/>
    <?php if(get_option('hide_approver')!='Yes'){ ?>
    Approver name: <input type="text" name="_simple_approver" id="_simple_approver" value="" /><br />
    Approver e-mail: <input type="text" name="_simple_approver_email" id="_simple_approver_email" value="" /><br /><?php } ?>
    
    Approver name: <?php wp_dropdown_users(array('name' => 'pto_approver')); ?><br/>
    <input type="hidden" name="_simple_approved" id="_simple_approved" value="Pending" /><br />
    <input type="hidden" name="vacation2" id="vacation2" value="submit" />
    <input type="hidden" name="action2" value="new_vacation" />
    <input type="submit" value="Submit Request">
    </form>
<?php 

}