<?php
/* Get availble tickets */
$occurrence_tickets = eo_get_the_occurrences_tickets( $booking_form->get('event_id') );
$event_id = $booking_form->get('event_id');
?>

<div id="eo-bookings">

	<h3 id="eo-booking-title"><?php echo $booking_form->get_form_title(); ?></h3>
	
	<?php echo $this->render_notices(); ?>
	
	<?php echo $this->render_errors(); ?>
	
	<?php 
	//Check if event has finished.
	$now = new DateTime( 'now', eo_get_blog_timezone() );
	if ( eo_get_schedule_last( DATETIMEOBJ, $booking_form->get('event_id') ) < $now ) {
		echo apply_filters( 'eventorganiser_booking_closed', '<p>'.__( 'Bookings are no longer available for this event.', 'eventorganiserp' ).'</p>' );
	
	//Check if tickets are available
	}elseif(  !$occurrence_tickets || !eventorganiser_list_sum( $occurrence_tickets, 'available' ) ) {
		if( !$this->displaying_confirmation() ){
			echo apply_filters( 'eventorganiser_booking_tickets_sold_out', '<p>'.__( 'This event has sold out.', 'eventorganiserp' ).'</p>' );
		}
	
	//If logged out and guess cannot book
	}elseif( !is_user_logged_in() && !eventorganiser_pro_get_option( 'allow_guest_booking' ) ){

		//User is logged out, guests cannot book. Show login form and don't display booking form
		echo apply_filters( 'eventorganiser_booking_login_required', '<p>'.__( 'Only logged in users can place bookings', 'eventorganiserp' ).'</p>' );
		eo_login_form( array( 'form_id' => 'eo-booking-login-form-login-required', 'label_username' => __( 'Username / Email', 'eventorganiserp' ) )  );
			
	}else{

		//Non-logged in users prompted to log-in
		if ( !is_user_logged_in() && 3 != eventorganiser_pro_get_option( 'allow_guest_booking' ) ) {
			eo_login_form( array( 'form_id' => 'eo-booking-login-form', 'label_username' => __( 'Username / Email', 'eventorganiserp' ) )  );
		}

		//Display the booking form
		?>
		<form method="post" action="<?php echo get_permalink().'#eo-bookings';?>" id="eo-booking-form" autocomplete="off">

			<?php 
				//Display custom fields
				$this->display_form_fields();
			?>
			
		</form>
	<?php 
	} ?>
</div>
