<?php
/****** Booking Page ******/
//If standard isn't installed, then the class EventOrganiser_Admin_Page won't exist
if ( !defined( 'EVENT_ORGANISER_DIR' ) )
	return;

if ( !class_exists( 'EventOrganiser_Admin_Page' ) ) {
	require_once EVENT_ORGANISER_DIR.'classes/class-eventorganiser-admin-page.php';
}

class EventOrganiser_Bookings_Page extends EventOrganiser_Admin_Page
{
	/*
	 * Error object containing any errors when a booking is confirmed (fully booked)
	 */
	static $confirmation_error;

	/**
	 * Set the variables of the admin page, and initialise the self::$confirmation_error
	 */
	function set_constants() {
		$this->hook = 'edit.php?post_type=event';
		$this->menu = __( 'Bookings', 'eventorganiserp' );
		$this->permissions ='manage_eo_bookings';
		$this->slug ='bookings';
		$this->title =  __( 'Bookings', 'eventorganiserp' );
		self::$confirmation_error = new WP_Error();
	}

	/**
	* Actions to be taken prior to page loading. Hooked on to load-{page}
	*/
	function page_actions() {
		
		global $wpdb;
		
		$action = $this->current_action();
		$booking_ids = ( !empty( $_GET['booking_id'] ) ? $_GET['booking_id'] : false );
	
		if( 'delete-cancelled' == $action ){
			$booking_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'eo_booking' AND post_status = 'cancelled'" );
			$action = 'delete';
		}
		
		if ( $booking_ids ) {
			switch ( $action ) {
				
			//Viewing/Editing a booking (display booking's admin page)
			case 'edit':
				if ( empty( $_GET['booking_id'] ) || ( 'eo_booking' != get_post_type( $_GET['booking_id'] ) ) )
					wp_die( 'Booking not found' );
				
				if( !current_user_can( 'manage_eo_booking', $_GET['booking_id'] ) ){
					wp_die( 'You do not have permission to edit this booking' );
				}
				
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			break;

			//Cancel a booking
			case 'cancel':
				
				if ( is_array( $booking_ids ) ) {
					check_admin_referer( 'bulk-bookings', '_wpnonce' );
				}else {
					check_admin_referer( 'eo-cancel-booking-'.$booking_ids );
					$booking_ids = array( $booking_ids );
				}
				
				$cancelled = 0;
				
				foreach( $booking_ids as $booking_id ) {
					
					if ( ! current_user_can( 'manage_eo_booking', $booking_id ) )
						wp_die( __('You are not allowed to cancel this booking.', 'eventorganiserp') );
						
					if ( !eo_cancel_booking( $booking_id ) )
						wp_die( __('Error in cancelling the booking.', 'eventorganiserp' ) );
				
					$cancelled++;
				}
				
				$redirect = esc_url_raw( add_query_arg( array(
						'post_type' => 'event',
						'page' => 'bookings',
						'cancelled' => $cancelled,
				), admin_url( 'edit.php' ) ) );
				
				wp_redirect( $redirect );
				exit();
				
			break;
			
			
			//Restore cancelled bookings
			case 'uncancel':
				$restored = 0;
				foreach( (array) $booking_ids as $booking_id ) {
					
					if ( ! current_user_can( 'manage_eo_booking', $booking_id ) )
						wp_die( __('You are not allowed to restore this booking.', 'eventorganiserp' ) );

					if ( !eo_restore_booking( $booking_id ) )
						wp_die( __('Error in restoring the booking.', 'eventorganiserp' ) );

					$restored++;
				}
				
				$redirect = esc_url_raw( add_query_arg( array(
						'post_type' => 'event',
						'page' => 'bookings',
						'restored' => $restored,
				), admin_url( 'edit.php' ) ) );
				wp_redirect( $redirect );
				exit();
			break;
				
			case 'delete':
				
				$booking_ids = (array) $booking_ids;
				
				if( empty( $booking_ids ) )
					return;
			
				$deleted = 0;
				foreach( $booking_ids as $booking_id ) {
				
					if ( ! current_user_can( 'manage_eo_booking', $booking_id ) ){
						wp_die('you cant do that');
					}
						//continue;
					
					if ( !eo_delete_booking( $booking_id ) ){
							wp_die( __('Error in deleting.') );
					}
					$deleted++;
				}
							
				$redirect = esc_url_raw( add_query_arg( array(
							'post_type' => 'event',
							'page' => 'bookings',
							'deleted' => $deleted,
						), admin_url( 'edit.php' ) ) );
				wp_redirect( $redirect );
				exit();
				
			break;
			
			//Bulk-Confirm emails
			case 'confirm':
				if ( !$booking_ids )
					return;
								
				check_admin_referer( 'bulk-bookings', '_wpnonce' );
				
				$booking_ids = array_reverse( $booking_ids );
				
				$confirmed = 0;
				$failed_confirmed = array();
				
				foreach( $booking_ids  as $booking_id ){
					
					if ( ! current_user_can( 'manage_eo_booking', $booking_id ) )
						continue;
					
					$response = eo_confirm_booking( $booking_id, false );
					
					if( is_wp_error( $response ) )
						$failed_confirmed[] = $booking_id;
					else
						$confirmed++;
				}
				
				$redirect =  add_query_arg( array(
						'post_type' => 'event',
						'page' => 'bookings',
				), admin_url( 'edit.php' ) );
				
				if( $confirmed ){
					$redirect = add_query_arg( 'confirmed', $confirmed, $redirect );
				}
				if( $failed_confirmed  ){
					$redirect = add_query_arg( 'failed_confirmed', implode(',',$failed_confirmed), $redirect );
				}
				
				wp_redirect( esc_url_raw( $redirect ) );
				exit();
			break;

			//E-mail the bookees
			case 'email':

				check_admin_referer( 'eventorganiser_bulk_email', '_eononce' );

				$input = isset( $_GET['eventorganiser'] ) ? $_GET['eventorganiser'] : '';
				$message = isset( $input['email_message'] ) ? $input['email_message'] : array();
				$message = str_replace( "\n", "<br/>", $message );
				$subject = isset( $input['subject'] ) ? $input['subject'] : '';
				$template = eventorganiser_pro_get_option( 'email_template' );
				
				$emailed = 0;
				foreach ( $booking_ids as $booking_id ) {
					
					if ( ! current_user_can( 'manage_eo_booking', $booking_id ) )
						continue;
					
					$parsed_message = eventorganiser_email_template_tags( $message, $booking_id, $template );
					$parsed_subject = eventorganiser_email_template_tags( $subject, $booking_id, $template );
					$email = eo_get_booking_meta( $booking_id, 'bookee_email' );

					if ( $email = sanitize_email( $email ) ){
						$emailed += (int) eventorganiser_mail( $email, $parsed_subject, $parsed_message, null, array(), $template );
					}
				}

				$redirect = esc_url_raw( add_query_arg( array(
							'post_type'=>'event',
							'page'=>'bookings',
							'emailed' =>  $emailed ,
						), admin_url( 'edit.php' ) ) );
				wp_redirect( $redirect );
				exit();
			break;

			//Emailing a booking - booking_ids should contain one ID.
			case 'email-booking':
				
				if ( ! current_user_can( 'manage_eo_booking', $booking_ids ) )
					return;
				
				if( 'eo_booking' != get_post_type( $booking_ids ) )
					wp_die( 'Booking not found' );
				
				check_admin_referer( 'eo-email-booking-'.$booking_ids );
				
				/* Get email details */
				$template = eventorganiser_pro_get_option( 'email_template' );
				$from_name = get_bloginfo( 'name' );
				$from_email = eo_get_admin_email( $booking_ids );
				
				/* Get messgage from the options */
				$message = eventorganiser_email_template_tags( eventorganiser_pro_get_option( 'email_tickets_message' ), $booking_ids, $template );
				$message = wpautop( $message );
				
				/* Set headers */
				$headers = array(
						'from:' . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>",
						'reply-to:' . $from_email
				);
				
				$bookee_email = eo_get_booking_meta( $booking_ids, 'bookee_email' );
				
				$subject = apply_filters( 'eventorganiser_booking_confirmed_email_subject', __( 'Thank you for your booking', 'eventorganiserp' ), $booking_ids );
				$message = apply_filters( 'eventorganiser_booking_confirmed_email_body', $message, $booking_ids );
				$headers = apply_filters( 'eventorganiser_booking_confirmed_email_headers', $headers, $booking_ids );
				$attachments = apply_filters( 'eventorganiser_booking_confirmed_email_attachments', array(), $booking_ids );
				$template = apply_filters( 'eventorganiser_booking_confirmed_email_template', $template, $booking_ids );
				
				eventorganiser_mail( $bookee_email, $subject, $message, $headers, $attachments, $template );
				
				wp_redirect( add_query_arg( 'emailed', 1, eventorganiser_edit_booking_url( $booking_ids ) ) );
				exit();
				
				break;
				
				
			case 'update':

				if ( ! current_user_can( 'manage_eo_booking', $booking_ids ) )
					return;
				
				if( 'eo_booking' != get_post_type( $booking_ids ) )
					wp_die( 'Booking not found' );

				check_admin_referer( 'event_organiser_edit_booking_'.$booking_ids, '_eventorganiser_pro_nonce' );

				$delete = 0;

				//First - are there any booking tickets marked for deletion
				if ( !empty( $_POST['eo_delete_ticket'] ) ) {
					$delete_tickets = array_map( 'intval', $_POST['eo_delete_ticket'] );

					foreach ( $delete_tickets as $b_t_id ) {
						$delete = $delete + (int) eventorganiser_delete_booking_ticket( $b_t_id );
					}
				}

				if ( $delete > 0 ) {
					//Tickets deleted - clear booking cache
					eventorganiser_clear_cache( 'eo_booking', $booking_id );
					eventorganiser_clear_cache( 'eo_booking_tickets', $booking_id );
				}

				//Check if we have changed occurrence date:
				if( isset( $_POST['eo_booking']['occurrence_id'] ) ){
					$new_occurrence_id = (int) $_POST['eo_booking']['occurrence_id'];
					eo_change_booking_occurrence( $booking_ids, $new_occurrence_id );
				}

				//Get booking info & update
				$booking = $_POST['eo_booking'];
				
				//Are we confirming a pending booking?
				if ( ( $booking['post_status'] == 'confirmed' ) && eo_get_booking_status( $booking_ids ) != 'confirmed' ) {
					$force_confirmation = !empty( $_POST['force_confirmation'] ) ? true : false;
					$response = eo_confirm_booking( $booking_ids, $force_confirmation );
				}
				
				//Display notices for confirmation of action (edit, delete, email).
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
				
				if ( isset($response) && is_wp_error( $response ) ) {
					self::$confirmation_error = $response;
					unset( $booking['post_status'] );
					eo_update_booking( $booking_ids, $booking );
					
					//Booking not automatically confirmed, so remain on page to ask for confirmation from user.
					return;
				}
				
				eo_update_booking( $booking_ids, $booking );
				wp_redirect( add_query_arg( 'updated', 1, eventorganiser_edit_booking_url( $booking_ids ) ) );
				exit();
				break;
			}
		}

		//If booking ID is not provided, or not 'edit'-ing then show booking admin table
		if ( empty( $_GET['booking_id'] ) || 'edit' != $this->current_action() ) {
			
			require_once EVENT_ORGANISER_PRO_DIR.'admin/includes/event_booking_table.php';

			add_filter( 'manage_event_page_bookings_columns', array( $this, 'bookings_columns' ) );
			add_screen_option( 'per_page', array( 'label' => __( 'Bookings', 'eventorganiserp' ), 'default' => 20 ) );
			
		}

		//Maybe update screen options
		if ( isset( $_POST['screen-options-apply'] )&& $_POST['screen-options-apply'] == 'Apply' ) {
			
			if ( check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' ) ):

				global $current_user;
				$option = $_POST['wp_screen_options']['option'];
				$value = intval( $_POST['wp_screen_options']['value'] );
				update_user_option( $current_user->ID, $option, $value );
				
			endif;
		}

		//Maybe display notices
		$notices = array( 'emailed', 'cancelled', 'updated', 'confirmed', 'failed_confirmed', 'restored', 'deleted' );
		if ( array_intersect_key( array_flip( $notices ) , $_GET ) ){
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
		
	}

	function bookings_columns( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'booking'=>__( 'Booking', 'eventorganiserp' ),
			'booking_bookee'=>__( 'Bookee', 'eventorganiserp' ),
			'booking_event'=>__( 'Event', 'eventorganiserp' ),
			'booking_tickets'=>__( 'Tickets', 'eventorganiserp' ),
			'booking_price'=>__( 'Price', 'eventorganiserp' ),
			'booking_date'=>__( 'Date', 'eventorganiserp' ),
			'booking_status'=>__( 'Status', 'eventorganiserp' ),
		);
		return $columns;
	}

	function alert_message( $message ){
		echo '<div class="updated"><p>' . $message . '</p></div>';
	}

	function error_message( $message ){
		echo '<div class="error"><p>' . $message . '</p></div>';
	}
	
	function admin_notices( ) {
		
		$action_counts = array(
			'updated'   => isset( $_GET['updated'] )   ? absint( $_GET['updated'] )   : 0,
			'deleted'   => isset( $_GET['deleted'] )   ? absint( $_GET['deleted'] )   : 0,
			'cancelled' => isset( $_GET['cancelled'] ) ? absint( $_GET['cancelled'] ) : 0,
			'restored'  => isset( $_GET['restored'] )  ? absint( $_GET['restored'] )  : 0,
			'emailed' 	=> isset( $_GET['emailed'] )   ? absint( $_GET['emailed'] )   : 0,
			'confirmed' => isset( $_GET['confirmed'] ) ? absint( $_GET['confirmed'] ) : 0,
		);
		
		$action_message = array(
			'updated'   => _n_noop( '1 booking updated.', 	'%d bookings updated.', 	'eventorganiserp' ),
			'deleted'   => _n_noop( '1 booking deleted.', 	'%d bookings deleted.', 	'eventorganiserp' ),
			'cancelled' => _n_noop( '1 booking cancelled.', '%d bookings cancelled.',	'eventorganiserp' ),
			'restored'  => _n_noop( '1 booking restored.', 	'%d bookings restored.',	'eventorganiserp' ),
			'emailed'   => _n_noop( '1 booking emailed.', 	'%d bookings emailed.',		'eventorganiserp' ),
			'confirmed' => _n_noop( '1 booking confirmed.', '%d bookings confirmed.',	'eventorganiserp' ),
		);
		
		$action_counts = array_filter( $action_counts );
		
		foreach( $action_counts as $action => $count ){
			$this->alert_message( sprintf( translate_nooped_plural( $action_message[$action], $count ), $count ) );
		}

		
		$failed_confirmed = isset( $_GET['failed_confirmed'] ) ?  explode( ',', $_GET['failed_confirmed'] ) : false;

		if( $failed_confirmed ){
			$message = __( 'The following bookings could not be automatically confirmed. Please confirm these manually:', 'eventorganiserp' );

			$message .= '<ul>';
			foreach( $failed_confirmed as $booking_id ){
				$message .= '<li><a href="'.get_edit_post_link( $booking_id ).'">#'.intval( $booking_id ).'</a></li>';
			}
			$message .= '</ul>';
				 
			$this->error_message( $message );
		}
		
		
		//When editing a booking if date and/or occurrence exists.
		if( $this->current_action() == 'edit' && !empty( $_GET['booking_id'] ) ){
			
			$booking_id = (int) $_GET['booking_id'];
			$event_id = eo_get_booking_meta( $booking_id, 'event_id' );
			$occurrence_id = eo_get_booking_meta( $booking_id, 'occurrence_id' );
			$date = eo_get_the_occurrence_start( get_option( 'date_format' ), $occurrence_id );
			
			if( 'event' != get_post_type( $event_id ) ){
				printf( '<div class="error"><p>%s</p></div>', __( 'The event associated with this booking could not be found. It may have been deleted.', 'eventorganiserp' ) );
			}elseif( $occurrence_id && !$date ){
				printf( '<div class="error"><p>%s</p></div>', __( 'The occurrence date for which this booking was made no longer exists.', 'eventorganiserp' ) );
			}
		}
		

		$codes = self::$confirmation_error->get_error_codes();
		if ( $codes ) {
			echo '<div class="error"><p>';
				_e( 'This booking could not be confirmed for the following reasons', 'eventorganiserp' );
				foreach ( $codes as $code ) {
					$ticket =  self::$confirmation_error->get_error_data( $code );
					echo '<li>'.self::$confirmation_error->get_error_message( $code ).'</li>';
				}
				echo '</p>';
			
				echo '<form name="eventorganiser" id="eventorganiser_confirm_booking" method="post">';
					echo '<input type="hidden" name="eo_booking[post_status]" value="confirmed">';
					wp_nonce_field( 'event_organiser_edit_booking_'.absint( $_GET['booking_id'] ), '_eventorganiser_pro_nonce', false, true );
					echo '<input type="hidden" name="action" value="update" >';
					echo '<input type="hidden" name="force_confirmation" value="1" >';
					submit_button( __( 'I want to confirm anyway', 'eventorganiserp' ), 'small secondary', 'submit', true );
				echo '</form>';			
			echo '</div>';
		}
	}

	/**
	 * Load page scripts
	 */
	function page_scripts() {
		global $wp_locale;

		$action = $this->current_action();
		wp_enqueue_script( 'postbox' );
		wp_enqueue_style( 'eo_pro_admin' );
		wp_enqueue_style( 'eventorganiser-jquery-ui-style' );
		wp_enqueue_script( 'eo-pro-edit-booking' );

		if( isset( $_GET['booking_id'] ) && $booking_id = $_GET['booking_id'] ){
			//If editing a booking, load scripts/variables for changing occurrence date
			$event_id = eo_get_booking_meta( $booking_id, 'event_id' );
			if( $occurrences = eo_get_the_occurrences( $event_id ) ){
				$dates = array_map( 'eo_format_datetime', $occurrences );
				wp_localize_script( 'eo-pro-edit-booking', 'EO_Event', array( 
					'dates' => $dates, 
					'format' => eventorganiser_php2jquerydate( get_option( 'date_format' ) ),
					'startday'=>intval( get_option( 'start_of_week' ) ),
					'locale' => array(
						'locale' => substr( get_locale(), 0, 2 ),
						'monthNames' => array_values( $wp_locale->month ),
						'monthAbbrev' => array_values( $wp_locale->month_abbrev ),
						'dayNames' => array_values( $wp_locale->weekday ),
						'dayAbbrev' => array_values( $wp_locale->weekday_abbrev ),
					),
				) );
			}
		}
	}


	function display() {?>
		<div class="wrap">
			<?php screen_icon( 'edit' );

			if ( !empty( $_GET['booking_id'] ) && in_array( $this->current_action(), array( 'update', 'edit' ) ) ) {
				$booking_id = absint( $_GET['booking_id'] );			
				add_meta_box( 'eop_booking_detail', __( 'Booking Details', 'eventorganiserp' ), array( $this, 'booking_metabox' ),$this->page, 'normal' );
				add_meta_box( 'submitdiv', __( 'Booking', 'eventorganiserp' ), array( $this, 'submit_metabox' ), $this->page, 'side' );
				add_meta_box( 'eop_bookee_detail', 'Bookee', array( $this, 'bookee_metabox' ), $this->page, 'side' );
				$this->display_booking( $booking_id );
			}else {
				$this->display_table();
			}?>
		</div>
		<?php
	}

	
	function display_booking( $booking_id ) {

		$booking = get_post( $booking_id );?>
		
		<h2><?php printf( __( 'Booking (#%d)', 'eventorganiserp' ), $booking_id ); ?> </h2>

		<form name="eventorganiser" id="eventorganiser_booking" method="post">
			<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>

			<div id="poststuff">
				<?php do_action( 'add_meta_boxes_'.$this->page, null ); ?>
				<?php do_action( 'add_meta_boxes', $this->page, null ); ?>

				<div id="post-body" class="metabox-holder columns-2">

					<div id="postbox-container-1" class="postbox-container"> <?php do_meta_boxes( '', 'side', $booking ); ?> </div>

					<div id="postbox-container-2" class="postbox-container">
						<?php do_meta_boxes( '', 'normal', $booking );  ?>
						<?php do_meta_boxes( '', 'advanced', $booking ); ?>
				 	</div>

				 </div><!-- #post-body -->
				<br class="clear">

			</div><!-- #poststuff -->
			<?php printf( 
					'<p><a href="%s"> %s </a></p>',
					admin_url( 'edit.php?post_type=event&page=bookings' ),
					esc_html__( 'Back to table view', 'eventorganiserp' )
				); ?>
		</form>
		<?php
	}


	function submit_metabox( $booking ) {
		$booking_id = (int) $booking->ID;

		echo '<div class="submitbox" id="submitpost">';

		//Misc actions: change status
		echo '<div id="misc-publishing-actions">';
		echo '<div class="misc-pub-section">';
		printf( '<p> <label> %s:', __( 'Status' ) );
			eventorganiser_select_field( array(
				'label_for'=>'post_status',
				'name' => 'eo_booking[post_status]',
				'options' => wp_list_pluck( eo_get_booking_statuses(), 'label' ),
				'selected'=>get_post_status( $booking_id )
			) );
		echo '</label></p>';
		echo '</div>';
		echo '</div>';
		
		if( in_array( get_post_status( $booking_id ), eo_get_confirmed_booking_statuses() ) ){
			echo '<div class="misc-pub-section">';
				$url = admin_url( 'edit.php?post_type=event&page=bookings' );
				printf( 
					'<a class="secondary button" href="%s"> %s </a>',
						wp_nonce_url( add_query_arg( array( 'action'=>'email-booking', 'booking_id'=>$booking_id ), $url ), 'eo-email-booking-'.$booking_id ),
					__( 'Resend tickets to bookee', 'eventorganiserp' )
					);
			echo '</div>';
		}
		
		do_action( 'eventorganiser_booking_actions_misc', $booking_id );
		
		//Major actions: update / delete
		echo '<div id="major-publishing-actions">';

		printf( 
			'<div id="delete-action"><a class="submitdelete deletion" href="%s">%s</a></div>',
			eventorganiser_cancel_booking_url( $booking_id ), __( 'Cancel Booking', 'eventorganiserp' )
		);

		printf( 
			'<div id="publishing-action">%s</div>',
			get_submit_button( __( 'Update' ), 'primary', 'publish', false ).' <input type="hidden" name="action" value="update" >'
		);
		wp_nonce_field( 'event_organiser_edit_booking_'.$booking_id, '_eventorganiser_pro_nonce', false, true );

		echo '<div class="clear"></div>';
		echo '</div>';
		echo '</div>';
	}


	function bookee_metabox( $booking ) {

		$bookee_id = eo_get_booking_meta( $booking->ID, 'bookee' );
		$avatar = get_avatar( $bookee_id, 64 );
		$user = get_userdata( $bookee_id );

		echo $avatar;

		if ( $bookee_id != 0 && ( !$user || is_wp_error( $user ) ) ) {
			printf( '<p> %s </p>', __( 'User not found', 'eventorganiserp' ) );
			return;
		}
		
		$account = ( $user && !is_wp_error( $user )  ? $user->user_login : __( 'No account', 'eventorganiser' )  );
	

		echo '<table class="eo-bookee-info form-table"><tbody>';

			printf( '<tr><th> %s: </th><td> %s <em>(%s)</em> </td>',
				esc_html__( 'Bookee', 'eventorganiserp' ),
				eo_get_booking_meta( $booking->ID, 'bookee_display_name' ),
				$account
			);

			printf( '<tr><th> %1$s: </th><td> <a href="mailto:%2$s">%3$s</a> </td>',
				esc_html__( 'Email', 'eventorganiserp' ),
				sanitize_email( eo_get_booking_meta( $booking->ID, 'bookee_email' ), true ), 
				sanitize_email( eo_get_booking_meta( $booking->ID, 'bookee_email' ), true )
			);
			
		echo '</tbody></table>';
		
		$recent_bookings = eo_get_bookings(array(
				'post__not_in' => array( $booking->ID ),
				'bookee_id' => $bookee_id,
				'order' => 'DESC',
				'numberposts' => 5,
		));
			
		if( $bookee_id && $recent_bookings ){
			printf( '<h4 style="font-size:1.1em"> %1$s: </h4>', __( 'Other Recent bookings', 'eventorganiserp' ) );
			 
			echo '<ul id="eo-recent-bookings">';
			foreach( $recent_bookings as $recent_booking ){
				$event_id = eo_get_booking_meta( $recent_booking->ID, 'event_id' );
				$occurrence_id = eo_get_booking_meta( $recent_booking->ID, 'occurrence_id' );
				?>
				<li class="eo-booking-row">
					<span>
						<a href="<?php echo get_edit_post_link( $recent_booking->ID ); ?>">
						<?php echo '#'.$recent_booking->ID; ?></a> 
					</span> 
					<span> 
						<strong><?php echo get_the_title( $event_id ); ?></strong> 
						<?php if( $occurrence_id ){?>
							<br/><small> 
							<?php echo eo_get_the_start( get_option('date_format'), $event_id, null, $occurrence_id ); ?>
							</small></br>
						<?php }?>
						
					</span>
				</li>
				<?php 
			}
			echo '</ul>'; 
		}
		
	}

	function booking_metabox( $booking ) {
		$booking_id = (int) $booking->ID;
		$amount = eo_get_booking_meta( $booking_id, 'booking_amount' );
		$event_title = get_the_title( eo_get_booking_meta( $booking_id, 'event_id' ) );

		echo '<table class="form-table"><tbody>';
		
		// Booking Date
		printf( '<tr><th> %s: </th><td> %s </td>',
			esc_html__( 'Booking Date', 'eventorganiserp' ),
			eo_get_booking_date( $booking->ID, get_option( 'date_format' ).' '.get_option( 'time_format' ) )
		);

		// Booking Reference
		printf( '<tr><th> %s: </th><td> %s </td>',
			esc_html__( 'Booking Reference', 'eventorganiserp' ),
			"#$booking_id"
		);

		// Booking occurrence date (allows occurrence to be changed)
		if ( eo_get_booking_meta( $booking_id, 'occurrence_id' ) ) {
			$occurrence_id = eo_get_booking_meta( $booking_id, 'occurrence_id' );
			$date = eo_get_the_occurrence_start( get_option( 'date_format' ), $occurrence_id );

			if ( $occurrence_id && $date ){
				$event_title .= sprintf(
					' ( <span id="eo-booking-occurrence-date"> %s </span> <a id="booking-event-date" class="hide-if-no-js" href="#">%s</a>
						<input type="text" style="display:none;" id="datepicker" value="%s" > )',
					$date,
					__( 'Change', 'eventorganiserp' ),
					eo_get_the_occurrence_start( 'd-m-Y', $occurrence_id )
				);
			}elseif( !$date ){
				$event_title .= sprintf(
					' ( <span id="eo-booking-occurrence-date"> %s </span> <a id="booking-event-date" class="hide-if-no-js" href="#">%s</a>
						<input type="text" style="display:none;" id="datepicker" value="%s" > )',
					__('Orphaned booking', 'eventorganiserp' ),
					__( 'Select date', 'eventorganiserp' ),
					eo_get_the_occurrence_start( 'd-m-Y', $occurrence_id )
				).eventorganiser_inline_help( __('Orphaned booking', 'eventorganiserp' ), 
					'This booking is for an event occurrence which no longer exists.'.
					'It may have been deleted. If you have selected <em>book occurrence</em> in the plug-in settings, you can fix this
					by manually selecting a date' );
			}
			
			printf( '<tr><th> %s: </th><td>%s<input type="hidden" id="eo-booking-occurrence-id" name="eo_booking[occurrence_id]" value="%d"> </td>',
				esc_html__( 'Event', 'eventorganiserp' ),
				$event_title,
				$occurrence_id
			);
		}

		// Booking amount
		printf( '<tr><th> %s: </th><td> %s </td>',
			esc_html__( 'Amount', 'eventorganiserp' ),
			eo_format_price( $amount )
		);

		if ( $amount > 0 ) {
			$gateway = eo_get_booking_meta( $booking_id, 'gateway' );
			$transaction_id =  eo_get_booking_meta( $booking_id, 'transaction_id' );
			printf( '<tr><th> %s: </th><td> %s %s </td>',
				esc_html__( 'Payment Gateway', 'eventorganiserp' ),
				$gateway,
				!empty( $transaction_id ) ? "($transaction_id)" : ''
			);
		}

		//Booking Tickets
		printf( '<tr><th> %s: </th><td>', esc_html__( 'Tickets', 'eventorganiserp' ) );
			$ticket_table = new EO_Booking_Tickets_Table( $booking->ID );
			$ticket_table->display();
		echo '</td></tr>';

		// Booking Notes 
		printf( '<tr><th> %s: </th><td>', esc_html__( 'Booking Notes', 'eventorganiserp' ) );
		eventorganiser_textarea_field( array(
				'label_for'=>'post_content',
				'name' => 'eo_booking[post_content]',
				'value'=>$booking->post_content,
			) );
		echo '</td></tr>';
		
		// Booking Meta 
		printf( '<tr><th> %s: </th><td>', esc_html__( 'Booking Meta', 'eventorganiserp' ) );
		$meta = get_post_meta( $booking_id );
		$form_id = get_post_meta( $booking_id, '_eo_booking_form', true );
		$form = new EO_Booking_Form( array( 'id' => $form_id ) );
		$elements = ( $form->fetch() ? $form->get_elements() : array() );
		
		// Display Meta data table 
		echo '<table><tbody>';
		
		foreach ( $meta as $meta_key => $meta_values ) {
			if ( '_eo_booking_meta_' != substr( $meta_key, 0, 17 ) )
				continue;

			$element_id = substr( $meta_key, 17 );
			
			
			//Address details are duplicated as {element_id}_street-address,{element_id}_city, {element_id}_postcode etc. Ignore these.
			if( preg_match('/^[^_]+_[^_]+$/', $element_id ) )
				continue;
			
			if ( !isset( $elements[$element_id] ) ){
				$label = get_post_meta( $booking_id, '_eo_booking_label_meta_'.$element_id, true );

			}else{
				$element = $elements[$element_id];
				$label = $element->get('label');				
			}
			
			printf( 
				"<tr id='booking-meta-%d'><th rowspan=%d> %s </th><td>%s<td></tr>",
				$element_id, 
				count( $meta_values ), 
				esc_html( $label ), 
				array_shift( $meta_values ) 
			);
			foreach ( $meta_values as $meta_value )
				printf( '<tr><td> %s </td></tr>', $meta_value );
		}
		echo '</table><tbody>';
		echo '</td></tr>'; 
		

		/*Gateway Reponse */
		$log = get_post_meta( $booking_id, '_eo_booking_gateway_log' );
		if( $log ){
			printf( '<tr><th> %s: </th><td>', esc_html__( 'Gateway Response', 'eventorganiserp' ) );
			eventorganiser_textarea_field( array(
				'label_for'=>'eo-gateway-response',
				'value'=> print_r( $log, true ),
				'readonly' => true,
				'class' => 'hide-if-js'
			) );
			printf( 
				'<a href="#" class="hide-if-no-js" onclick="jQuery(\'#eo-hide-gateway, #eo-show-gateway, #eo-gateway-response\').toggle();return false;"> 
						<span id="eo-show-gateway" class="hide-if-no-js"> %s </span>
						<span id="eo-hide-gateway" class="hidden"> %s </span>
				</a>', 
				__( 'Show gateway response', 'eventorganiserp'),
				__( 'Hide gateway response', 'eventorganiserp')
			);
			echo '</td></tr>';
		}

	echo '</tbody></table>';//.form-table

	}

	function display_table() {

		$action = $this->current_action();

		//Else we are not creating or editing. Display table
		$bookings_table = new EO_Booking_Table();

		//Fetch, prepare, sort, and filter our data...
		$bookings_table->prepare_items();
?>
		<h2> <?php echo  esc_html( $this->title );

		$occurrence_id =( !empty( $_REQUEST['occurrence_id'] )  ? intval( $_REQUEST['occurrence_id'] ) : 0 );
		$event_id =( !empty( $_REQUEST['event_id'] )  ? intval( $_REQUEST['event_id'] ) : 0 );
		$bookee_id =( !empty( $_REQUEST['bookee_id'] )  ? intval( $_REQUEST['bookee_id'] ) : 0 );
		$search = ! empty( $_REQUEST['search'] ) ? trim( $_REQUEST['search'] ) : '';

		if ( !empty( $event_id ) && !empty( $occurrence_id ) ) {
			$sub_title = sprintf( __( 'Bookings for event &#8220;%s&#8221; (%s)', 'eventorganiserp' ),
				esc_html( get_the_title( $event_id ) ),
				eo_get_the_occurrence_start( get_option( 'date_format' ), $occurrence_id )
			);
		}elseif ( !empty( $event_id ) ) {
			$sub_title = sprintf( __( 'Bookings for event &#8220;%s&#8221;', 'eventorganiserp' ), esc_html( get_the_title( $event_id ) ) );

		}elseif ( !empty( $bookee_id ) ) {
			$user_data = get_userdata( $bookee_id );
			$sub_title = sprintf( __( 'Bookings for user &#8220;%s&#8221;', 'eventorganiserp' ), esc_html( $user_data->display_name ) );
			
		}elseif( !empty( $search ) ){
			if( $search[0] == '#' ){
				$search = trim( $search, '#' );
				$sub_title = sprintf( __( 'Searching for booking ID &#8220;%s&#8221;', 'eventorganiserp' ), esc_html( $search ) );
			}elseif ( strpos( $search,'@') !== false) {
				$sub_title = sprintf( __( 'Searching for email address &#8220;%s&#8221;', 'eventorganiserp' ), esc_html( $search ) );
			}else{
				$sub_title = sprintf( __( 'Searching for bookee &#8220;%s&#8221;', 'eventorganiserp' ), esc_html( $search ) );
			}
		}
		

		if ( !empty( $sub_title ) )
			printf( '<span class="subtitle"> %s </span>', $sub_title );
?>
		</h2>

		<?php $bookings_table->views(); ?>

		<form id="eo-bookings-table" method="get">
			<?php 
			$post_type_object = get_post_type_object( 'eo_booking' );
			$bookings_table->search_box( $post_type_object->labels->search_items, 'booking' ); ?>
			<!-- Ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="bookings" />
			<input type="hidden" name="post_type" value="event" />

       	     <!-- Now we can render the completed list table -->
       	     <?php $bookings_table->display(); ?>
		 </form>
		<?php $bookings_table->inline_edit(); ?>
    <?php
	}
}
$bookings_page = new EventOrganiser_Bookings_Page();