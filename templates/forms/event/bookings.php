<?php
global $EM_Event, $post, $allowedposttags, $EM_Ticket, $col_count;
?>
<div id="event-rsvp-box">
	<input id="event-rsvp" name='event_rsvp' value='1' type='checkbox' <?php echo ($EM_Event->event_rsvp) ? 'checked="checked"' : ''; ?> />
	&nbsp;&nbsp;
	<?php _e ( 'Enable registration for this event', 'dbem' )?>
</div>
<div id="event-rsvp-options" style="<?php echo ($EM_Event->event_rsvp) ? '':'display:none;' ?>">
	<?php do_action('em_events_admin_bookings_header', $EM_Event); ?>
	<div id="em-tickets-form">
	<?php
	//get tickets here and if there are none, create a blank ticket
	$EM_Tickets = $EM_Event->get_tickets();
	if( count($EM_Tickets->tickets) == 0 ){
		$EM_Tickets->tickets[] = new EM_Ticket();
		$delete_temp_ticket = true;
	}
	if( get_option('dbem_bookings_tickets_single') && count($EM_Tickets->tickets) == 1 ){
		?>
		<h4><?php esc_html_e('Ticket Options','dbem'); ?></h4>
		<?php
		$col_count = 1;	
		$EM_Ticket = $EM_Tickets->get_first();				
		include( em_locate_template('forms/ticket-form.php') ); //in future we'll be accessing forms/event/bookings-ticket-form.php directly
	}else{
		?>
		<table class="form-table">
			<thead>
				<tr valign="top">
					<th colspan="2"><?php esc_html_e('Event/Activity Name','dbem'); ?></th>
					<th><?php esc_html_e('Min/Max','dbem'); ?></th>
					<th><?php esc_html_e('Start/End','dbem'); ?></th>
					<th><?php esc_html_e('Avail. Spaces','dbem'); ?></th>
					<th><?php esc_html_e('Booked Spaces','dbem'); ?></th>
					<th>&nbsp;</th>
				</tr>
			</thead>    
			<tfoot>
				<tr valign="top">
					<td colspan="8">
						<!-- <a href="#" id="em-tickets-add"><?php esc_html_e('Event/Activity','dbem'); ?></a> -->
					</td>
				</tr>
			</tfoot>
			<?php
				$EM_Ticket = new EM_Ticket();
				$EM_Ticket->event_id = $EM_Event->event_id;
				array_unshift($EM_Tickets->tickets, $EM_Ticket); //prepend template ticket for JS
				$col_count = 0;
				foreach( $EM_Tickets->tickets as $EM_Ticket){
					/* @var $EM_Ticket EM_Ticket */
                    $EM_Ticket->ticket_name = "Event/Activity";
					?>
					<tbody id="em-ticket-<?php echo $col_count ?>" <?php if( $col_count == 0 ) echo 'style="display:none;"' ?>>
						<tr class="em-tickets-row">
							<td class="ticket-status"><span class="<?php if($EM_Ticket->ticket_id && $EM_Ticket->is_available()){ echo 'ticket_on'; }elseif($EM_Ticket->ticket_id > 0){ echo 'ticket_off'; }else{ echo 'ticket_new'; } ?>"></span></td>													
							<td class="ticket-name">
								<span class="ticket_name"><?php if($EM_Ticket->ticket_members) echo '* ';?><?php echo wp_kses_data($EM_Ticket->ticket_name); ?></span>
								<div class="ticket_description"><?php echo wp_kses($EM_Ticket->ticket_description,$allowedposttags); ?></div>
								<div class="ticket-actions">
									<a href="#" class="ticket-actions-edit"><?php esc_html_e('Edit','dbem'); ?></a> 
									<?php if( count($EM_Ticket->get_bookings()->bookings) == 0 ): ?>
									| <a href="<?php bloginfo('wpurl'); ?>/wp-load.php" class="ticket-actions-delete"><?php esc_html_e('Delete','dbem'); ?></a>
									<?php else: ?>
									| <a href="<?php echo EM_ADMIN_URL; ?>&amp;page=events-manager-bookings&ticket_id=<?php echo $EM_Ticket->ticket_id ?>"><?php esc_html_e('View Bookings','dbem'); ?></a>
									<?php endif; ?>
								</div>
							</td>
							<td class="ticket-limit">
								<span class="ticket_min">
									<?php  echo ( !empty($EM_Ticket->ticket_min) ) ? esc_html($EM_Ticket->ticket_min):'-'; ?>
								</span> / 
								<span class="ticket_max"><?php echo ( !empty($EM_Ticket->ticket_max) ) ? esc_html($EM_Ticket->ticket_max):'-'; ?></span>
							</td>
							<td class="ticket-time">
								<span class="ticket_start"><?php echo ( !empty($EM_Ticket->ticket_start) ) ? date(get_option('dbem_date_format'), $EM_Ticket->start_timestamp):''; ?></span>
								<span class="ticket_start_time"><?php echo ( !empty($EM_Ticket->ticket_start) ) ? date( em_get_hour_format(), $EM_Ticket->start_timestamp):''; ?></span>
								<br />
								<span class="ticket_end"><?php echo ( !empty($EM_Ticket->ticket_end) ) ? date(get_option('dbem_date_format'), $EM_Ticket->end_timestamp):''; ?></span>
								<span class="ticket_end_time"><?php echo ( !empty($EM_Ticket->ticket_end) ) ? date( em_get_hour_format(), $EM_Ticket->end_timestamp):''; ?></span>
							</td>
							<td class="ticket-qty">
								<span class="ticket_available_spaces"><?php echo $EM_Ticket->get_available_spaces(); ?></span>/
								<span class="ticket_spaces"><?php echo $EM_Ticket->get_spaces() ? $EM_Ticket->get_spaces() : '-'; ?></span>
							</td>
							<td class="ticket-booked-spaces">
								<span class="ticket_booked_spaces"><?php echo $EM_Ticket->get_booked_spaces(); ?></span>
							</td>
							<?php do_action('em_event_edit_ticket_td', $EM_Ticket); ?>
						</tr>
						<tr class="em-tickets-row-form" style="display:none;">
							<td colspan="<?php echo apply_filters('em_event_edit_ticket_td_colspan', 7); ?>">
								<?php include( em_locate_template('forms/event/bookings-ticket-form.php')); ?>
								<div class="em-ticket-form-actions">
								<button type="button" class="ticket-actions-edited"><?php esc_html_e('Close Editor','dbem')?></button>
								</div>
							</td>
						</tr>
					</tbody>
					<?php
					$col_count++;
				}
				array_shift($EM_Tickets->tickets);
			?>
		</table>
	<?php 
	}
	?>
	</div>
	<div id="em-booking-options">
	<?php if( !get_option('dbem_bookings_tickets_single') || count($EM_Ticket->get_event()->get_tickets()->tickets) > 1 ): ?>
	<h4><?php esc_html_e('Event/Activity Options','dbem'); ?> - <span style="color: red;">To be filled out by Administrator only</span></h4>
	<p>
		<label><?php esc_html_e('Total Spaces','dbem'); ?></label>
		<input type="text" name="event_spaces" value="<?php if( $EM_Event->event_spaces > 0 ){ echo $EM_Event->event_spaces; } ?>" /><br />
		<em><?php esc_html_e('Individual events/activities with remaining spaces will not be available if total event/activity spaces reach this limit. Leave blank for no limit.','dbem'); ?></em>
	</p>
	<p>
		<label><?php esc_html_e('Maximum Spaces Per Event/Activity','dbem'); ?></label>
		<input type="text" name="event_rsvp_spaces" value="<?php if( $EM_Event->event_rsvp_spaces > 0 ){ echo $EM_Event->event_rsvp_spaces; } ?>" /><br />
		<em><?php esc_html_e('If set, the total number of spaces for a single event/activity to this event cannot exceed this amount.','dbem'); ?><?php esc_html_e('Leave blank for no limit.','dbem'); ?></em>
	</p>
	<?php if( !$EM_Event->is_recurring() ): ?>
	<!--<p>
		<label><?php esc_html_e('Booking Cut-Off Date','dbem'); ?></label>
		<span class="em-date-single">
			<input id="em-bookings-date-loc" class="em-date-input-loc" type="text" />
			<input id="em-bookings-date" class="em-date-input" type="hidden" name="event_rsvp_date" value="<?php echo $EM_Event->event_rsvp_date; ?>" />
		</span>
		<input type="text" name="event_rsvp_time" class="em-time-input" maxlength="8" size="8" value="<?php echo date( em_get_hour_format(), $EM_Event->rsvp_end ); ?>">
		<br />
		<em><?php esc_html_e('This is the definite date after which bookings will be closed for this event, regardless of individual ticket settings above. Default value will be the event start date.','dbem'); ?></em>
	</p>-->
	<?php endif; ?>
	<?php endif; ?>
	</div>
	<?php
		if( !empty($delete_temp_ticket) ){
			array_pop($EM_Tickets->tickets);
		}
		//do_action('em_events_admin_bookings_footer', $EM_Event); 
	?>
</div>