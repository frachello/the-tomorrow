<?php
global $eventorganiser_booking_history;
$bookings = $eventorganiser_booking_history;


do_action('eventorganiser_pre_booking_history', $bookings );

if ( $bookings->have_posts() ) : ?>

	<table id="eo-booking-history" class="table">
		<thead>
			<tr class="eo-booking-history-row">
				<?php do_action('eventorganiser_booking_history_header_before'); ?>
				
				<th class="eo-booking-reference"><?php _e('Booking ref', 'eventorganiserp'); ?></th>
				<th class="eo-booking-date"><?php _e('Date', 'eventorganiserp'); ?></th>
				<th class="eo-booking-event"><?php _e('Event', 'eventorganiserp'); ?></th>
				<th class="eo-booking-tickets"><?php _e('Tickets', 'eventorganiserp'); ?></th>
				<th class="eo-booking-amount"><?php _e('Amount', 'eventorganiserp'); ?></th>
				<th class="eo-booking-status"><?php _e('Status', 'eventorganiserp'); ?></th>
				
				<?php do_action('eventorganiser_booking_history_header_after'); ?>
			</tr>
		</thead>
		
		<?php while ( $bookings->have_posts() ) : $bookings->the_post(); ?>
		
			<tr class="eo-booking-history-row">
				<?php do_action( 'eventorganiser_booking_history_header_row_start', $post->ID ); ?>
				<td class="eo-booking-reference">#<?php echo absint( $post->ID ); ?></td>
				<td class="eo-booking-date"><?php echo eo_get_booking_date( $post->ID, get_option('date_format') ); ?></td>
				<td class="eo-booking-event">
					<?php 
						$event_id = eo_get_booking_meta( $post->ID, 'event_id' );
						$occurrence_id = eo_get_booking_meta( $post->ID, 'occurrence_id' );
						$event_title = get_the_title( eo_get_booking_meta( $post->ID, 'event_id' ) );
						$format = eo_is_all_day( $event_id ) ? get_option('date_format') : get_option('date_format') . ' ' . get_option('time_format');
						
						if( $occurrence_id ){
							//Booking individual date
							printf( 
								'%1$s </br> <small> %2$s </small>',
								$event_title,
								eo_get_the_start( $format, $event_id, null, $occurrence_id ) 
							);
						}elseif( !eo_reoccurs( $event_id ) ){
							//Booking series, but nonrecurring event
							printf(
								'%1$s </br> <small> %2$s </small>',
								$event_title,
								eo_get_schedule_start( $format, $event_id )
							);
						}else{
							//Booking series and recurring event - show first & last occurrence
							printf(
								'%1$s </br> <small> %2$s - %3$s </small>',
								$event_title,
								eo_get_schedule_start( $format, $event_id ),
								eo_get_schedule_last( get_option('date_format'), $event_id )
							);
						}
					?>
				</td>
				<td class="eo-booking-tickets">
					<?php
						// Show a list of downloadable files
						$tickets = eo_get_booking_tickets( $post->ID );		
					
						if( $tickets ){
							foreach( $tickets as $ticket){
								printf( ' %1$s <small>x%2$d</small><br/>', $ticket->ticket_name, $ticket->ticket_quantity );
							}
						}
					?>
				</td>
				<td class="eo-booking-amount"><?php echo eo_format_price( eo_get_booking_meta( $post->ID, 'booking_amount' ) ); ?></td>
				<td class="eo-booking-status"><?php echo eo_get_booking_status( $post ); ?></td>
				
				<?php do_action( 'eventorganiser_booking_history_header_row_end', $post->ID ); ?>
			</tr>
			
		<?php endwhile; ?>
	</table>
	
	<div id="eo-booking-history-pagination" class="eo-pagination">
		<?php
		$big = 999999;
		echo paginate_links( array(
			'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'  => '?paged=%#%',
			'current' => max( 1, get_query_var( 'paged' ) ),
			'total'   => $bookings->max_num_pages
		) );
		?>
	</div>
	
<?php else : ?>
	<p class="eo-no-bookings"><?php _e( 'You have not made any bookings', 'eventorganiserp' ); ?></p>
<?php endif;

do_action('eventorganiser_post_booking_history', $bookings );