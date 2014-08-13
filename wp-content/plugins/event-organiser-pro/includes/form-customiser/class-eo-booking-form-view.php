<?php
/**
 * Handles the form customiser
 */

/**
 * The Form class
 */
class EO_Booking_Form_View implements iEO_Booking_Form_View{

	/**
	 * @var $form EO_Booking_Form instance for which to display the view.
	 */
	protected $form;

	/**
	 * Constructs and initialises the form
	 * @param EO_Booking_Form $form
	 */
	function __construct( $form ){
		$this->form = $form;
	}
	
	/**
	 * Returns EO_Booking_Form instance.
	 * @return EO_Booking_Form
	 */
	function get_form(){
		return $this->form;
	}

	/**
	 * Produces the HTML mark-up for the form
	 * 
	 * @return string HTML mark-up for form
	 */
	function render(){
		
		$event_id = $this->form->get('event_id');
		$occurrence_tickets = eo_get_the_occurrences_tickets( $event_id);
		
		//include form template
		$template = eo_locate_template( 'eo-booking-form.php', false );
		
		$booking_form = $this->form;
		ob_start();
		if( $template )
			include( $template );
		
		$html = ob_get_contents();
		ob_end_clean();
		

		if( !eventorganiser_get_option( 'disable_css' ) )
			wp_enqueue_style( 'eo_front' );
		
		
		$tickets = eo_get_event_tickets_on_sale( $event_id );
		if( isset( $_POST['eventorganiser']['booking']['tickets'] ) ){
			foreach( $_POST['eventorganiser']['booking']['tickets'] as $t_id => $quantity ){
				if( isset( $tickets[$t_id] ) ){
					$tickets[$t_id]['quantity'] = (int) $quantity;
				}
			}
		}
		
		if( $occurrence_tickets ){
			list( $set_spaces ) = array_slice( $occurrence_tickets, -1);		
			if( $set_spaces && ( eventorganiser_pro_get_option( 'book_series' ) || !eo_reoccurs( $event_id ) ) ){
				foreach( $tickets as $t_id => $ticket ){
				
					if( isset( $set_spaces['tickets'][$t_id] ) ) {
						$tickets[$t_id]['available'] = min( $ticket['spaces'], $set_spaces['tickets'][$t_id] );
					}else{
						$tickets[$t_id]['available'] = 0;
					}
				}
			}
		}
			
		wp_localize_script( 'eo_pro_occurrence_picker', 'eventorganiserpro', array(
			'assets' => array( 'loader' => EVENT_ORGANISER_PRO_URL . 'images/loader.gif' ),
			'occurrences' => ( !eventorganiser_pro_get_option( 'book_series' ) ) ? $occurrence_tickets : false,
			'ajaxurl' => admin_url('admin-ajax.php'),
			'tickets_obj' => $tickets,
			'book_series' => (bool) eventorganiser_pro_get_option( 'book_series' ),
			'event' => array(
				'id' => $event_id,
				'show_datepicker' => ( !eventorganiser_pro_get_option( 'book_series' ) && eo_reoccurs( $event_id ) ),
				'is_recurring' => eo_reoccurs( $event_id ),
				'occurrence_ids' => ( $occurrence_tickets && !eventorganiser_pro_get_option( 'book_series' ) ) ?  wp_list_pluck( $occurrence_tickets, 'id' ) : false,
			),
		) );
		
		wp_enqueue_script( 'eo_pro_occurrence_picker' );
		
		return apply_filters( 'eventorganiser_display_form_html', $html, $this->form->id );
	}
	
	/**
	 * Displays each form field. 
	 * Retrieves the EO_Booking_Form_Wrapper_View and applies its display method to each element. 
	 */
	function display_form_fields(){
		
		$has_fieldsets = false;

		$elements_view_class = 'EO_Booking_Form_Elements_View';
		$elements_view_class = apply_filters( 'eventorganiser_booking_form_elements_view', $elements_view_class, $this->form );
				
		$elements_view = new $elements_view_class( $this->form->_elements );
		echo $elements_view->render();
	}
	
	
	/**
	 * Returns the mark-up for the error messages to appear on the booking form.
	 * Applies filter `eventorganiser_booking_display_errors`
	 * @return string
	 */
	function render_errors(){
	
		$html = '';
		if( $this->form->has_errors() ){
				
			$html = sprintf( '<div class="%s">',  esc_attr( $this->form->get_form_error_classes() ) );
				
			foreach(  $this->form->get_error_codes() as $code ){
				$html .= sprintf(
					'<p class="eo-booking-error-message eo-booking-error-%s"> %s </p>',
					esc_attr( $code ),
					$this->form->get_error_message( $code )
				);
			}
	
			$html .= '</div>';
		}
		return apply_filters( 'eventorganiser_booking_display_errors', $html, $this->form->id );
	}
	
	/**
	 * Returns the mark-up for the notices to appear on the booking form.
	 * Applies filter `eventorganiser_booking_form_notices`
	 * @return string
	 */
	function render_notices(){
		
		$html = '';
	
		if( $this->form->has_errors() ){
			//Don't display messages if there are booking errors
				
		}elseif ( !empty( $_GET['booking-confirmation'] ) ) {
				
			$gateway = $_GET['booking-confirmation'];
			$message = eventorganiser_pro_get_booking_complete_message( $gateway );
				
			$html .= sprintf(
					'<div class="%s eo-booking-notice-booking-complete">
							<p>%s</p>
						</div>',
					esc_attr( $this->form->get_form_notice_classes() ),
					$message
			);
	
		}elseif ( eo_user_has_bookings( get_current_user_id(),  $this->form->get( 'event_id' ), 0, eo_get_booking_statuses( array( 'name' => 'cancelled' ), 'names', 'not' ) ) ) {
			$html .= sprintf(
					'<div class="%s eo-booking-notice-prio-booking">
							<p>%s</p>
						</div>',
					esc_attr( $this->form->get_form_notice_classes() ),
					esc_html__( 'You have already made a booking for this event.', 'eventorganiserp' )
	
			);
		}
	
	
		if( is_user_logged_in() ){
			$current_user = wp_get_current_user();
			$html .= sprintf(
					'<div class="eo-booking-notice-logged-in %s">
							<p>%s</p>
						</div>',
					esc_attr( $this->form->get_form_notice_classes() ),
					sprintf(
							__( 'You are logged in as <strong>%s</strong>. <a href="%s">Not you?</a>', 'eventorganiserp' ),
							$current_user->user_email,
							wp_logout_url( get_permalink() )
					)
			);
		}
	
		return apply_filters( 'eventorganiser_booking_form_notices', $html, $this->form->id );
	}
	
	function displaying_confirmation(){
		return !empty( $_GET['booking-confirmation'] );
	}
}