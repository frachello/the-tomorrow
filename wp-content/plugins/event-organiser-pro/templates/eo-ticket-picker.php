<?php
//Get tickets
$tickets = eo_get_event_tickets_on_sale( $booking_form->get('event_id') );

//Check if occurrence (date) picker is required
$display_occurrence_picker = !eventorganiser_pro_get_option( 'book_series' ) && eo_reoccurs( $booking_form->get('event_id') );

//Collect user's input
$input = isset( $_POST['eventorganiser']['booking']) ?  $_POST['eventorganiser']['booking'] : array();

/* For booking by series / single events we can explictly set the max #tickets here as it is independent of date.*/
$set_spaces = false;
if ( eventorganiser_pro_get_option( 'book_series' ) || !eo_reoccurs( $booking_form->get('event_id') ) ) {
	//This should return an array with just one element - corresponding to the series, or the single occurrence.
	$set_spaces = array_pop( $occurrence_tickets );
}
?>

<table class="eo-booking-ticket-picker">

	<thead>
		<tr>
			<?php if ( $display_occurrence_picker ): ?>
				<td class='eo-booking-date'> <?php esc_html_e( 'Date', 'eventorganiserp' ); ?> </td>
			<?php endif; ?>
			<td class='eo-booking-ticket-name'> <?php esc_html_e( 'Ticket', 'eventorganiserp' ); ?> </td>
			<td class='eo-booking-ticket-price'> <?php esc_html_e( 'Price', 'eventorganiserp' ); ?> </td>
			<td class='eo-booking-ticket-quantity'> <?php esc_html_e( 'Quantity', 'eventorganiserp' ); ?> </td>
		</tr>
	</thead>
	
	<tbody>
		<?php if ( $display_occurrence_picker ): ?>
			<tr class='eo-booking-date'>
				<td rowspan=<?php echo count( $tickets ) + 4; ?>>
					<?php echo eo_get_booking_occurrence_picker( $booking_form->get( 'event_id' ), !$this->element->get('use_select') ); ?>
				</td>

				<td id='eo-booking-select-date' colspan='4' style='display:none' class='eo-show-if-js'>
					<center><?php esc_html_e( 'Please select a date', 'eventorganiserp' );?></center>
				</td>
			</tr>
		<?php endif; ?>

		<?php 		
		//Ticket rows
		foreach ( $tickets as $ticket_id => $ticket ) {
			
			//Get the #spaces (if it makes sense to - set maximum here)
			if ( $set_spaces && isset( $set_spaces['tickets'][$ticket_id] ) ) {
				$spaces = min( $ticket['spaces'], $set_spaces['tickets'][$ticket_id] );
			}else {
				$spaces = $ticket['spaces'];
			}

			if ( $spaces < 1 )
				continue;
			
			//If displaying ticket picker, hide until date selected
			$class = ( $display_occurrence_picker ) ? 'eo-hide-if-js' : ''
			?>
			
			<tr id="eo-booking-ticket-<?php echo $ticket_id;?>" style="border-top:none" 
				class="eo-booking-ticket-row <?php echo $class;?>" 
				<?php eo_booking_form_ticket_data_attr( $ticket); //Add ticket data attributes - required for JS ?>
				>
				
				<td class="eo-booking-ticket-name"> <?php echo esc_html( $ticket['name'] ); ?> </td>
				<td class="eo-booking-ticket-price"> <?php echo eo_format_price( $ticket['price'], true ); ?> </td>
				<td class="eo-booking-ticket-qty">
					<?php  $value = ( isset( $input['tickets'][$ticket_id] ) ? $input['tickets'][$ticket_id] : 0 ); ?>
					<input type="number"
						class="<?php echo esc_attr( $this->element->get_class() );?>" 
						data-eo-ticket-qty="<?php echo esc_attr( $ticket_id );?>" 
						name="eventorganiser[booking][tickets][<?php echo esc_attr( $ticket_id );?>]" 
						max="<?php echo $spaces; ?>" style="width:auto;" 
						min="0" 
						value="<?php echo esc_attr( $value );?>" />
				</td>
				
			</tr>
			<?php 
		}

		do_action( 'eventorganiser_booking_pre_total_row', $booking_form->get( 'event_id' ), $booking_form );

		//'Total' row
		?>
		<tr class="eo-booking-total-row" style="visibility:hidden;">
			
			<td><strong> <?php esc_html_e( 'Total', 'eventorganiserp' ); ?></strong></td>
			
			<td class="eo-booking-total"> 
				<strong> <?php echo eo_get_booking_form_total_placholder(); ?>  </strong>
			</td>
			
			<td class="eo-booking-qty"> 
				<strong><?php echo eo_get_booking_form_quantity_placholder(); ?> </strong> 
			</td>
			
		</tr>
		
	</tbody>
	
</table>
<?php 