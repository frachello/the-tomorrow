<?php

add_action( 'eventorganiser_gateway_listener_paypal_ipn', 'eventorganiser_paypal_ipn_handler' );
function eventorganiser_paypal_ipn_handler(){
	if( defined( 'WP_DEBUG' ) && WP_DEBUG ){
		error_log( "Handle IPN init", 0 );		
	}
	$payment = new EO_Gateway_Paypal_Standard();
	$payment->handle_ipn();	
}

class EO_Gateway_Paypal_Standard {

	/* Live / Test mode */
	private $is_live = false;

	/* Credentials */
	private $username = false;
	private $password = false;
	private $signature = false;

	/* PayPal urls */
	private $live_url ='https://www.paypal.com/webscr';
	private $sandbox_url ='https://www.sandbox.paypal.com/cgi-bin/webscr';
	var $url=false;

	/* Error variable */
	private $error = false;

	private $listener = false;

	var $return_url = false;
	
	var $debug = false;
	
	/**
	 * Class constructor. Sets up credentials stored in database. Set live/sandbox mode.
	 */
	function __construct() {

		$this->email= eventorganiser_pro_get_option( 'paypal_email' );
		$this->is_live = eventorganiser_pro_get_option( 'paypal_live_status' );
		$this->listener = add_query_arg( array( 'eo-listener' => 'ipn', 'eo-gateway' => 'paypal' ), trailingslashit( site_url() ) );
		//trailingslashit( home_url( 'index.php' ) ).'?eo-listener=ipn&eo-gateway=paypal';
		$this->debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
			
		if ( $this->is_live ) {
			$this->url = $this->live_url;
		}else {
			$this->url = $this->sandbox_url;

		}
	}
	
	function setup_cart( $bookings ){
		
		$cart=array(
			'return' => $this->return_url,//TODO option?
			'custom' => array(//custom will be passed through build_query
				'booking_id' => array(), 
			)
		);
	
		$i=1;
		$ticket_quantity = 0;
		
		foreach( $bookings as $booking_id ){
		
			$event_id   = (int) eo_get_booking_meta( $booking_id, 'event_id' );
			$event_date = eo_get_booking_event_start_date( $booking_id );
			$event      = get_post( $event_id );
			$tickets    = eo_get_booking_tickets( $booking_id );	
		
			if( $tickets ){
				foreach ( $tickets as $ticket ) {
					$cart['item_name_'.$i] = esc_html( $event->post_title.' ('.$ticket->ticket_name.') - ' . $event_date );
					$cart['amount_'.$i]    = floatval( $ticket->ticket_price );
					$cart['quantity_'.$i]  = (int) $ticket->ticket_quantity;
					
					$ticket_quantity += (int) $ticket->ticket_quantity;
					$i++;
				}
			}
		
			//Handle discounts
			$discount = eo_get_booking_meta( $booking_id, 'discount', true );
			if( $discount ){
				if( !isset( $cart['discount_amount_cart'] ) ){
					$cart['discount_amount_cart'] = 0;
				}
				$cart['discount_amount_cart'] += $discount['amount'];
			}
		
			$cart['custom']['booking_id'][] = $booking_id;
			
		}

		$cart['custom']['booking_ids'] = $cart['custom']['booking_id'];
		$cart['custom'] = build_query( $cart['custom'] );
		
		//TODO How filter expects just one booking cart...
		//$cart = apply_filters( 'eventorganiser_pre_gateway_checkout_paypal', $cart, $booking );
		return $cart;
	}


	function booking_cart( $booking ) {

		$cart=array(
			'return'=> $this->return_url,
		);
		
		$i=1;
		$event = get_post( $booking['event_id'] );
		$tickets = eo_get_booking_tickets( $booking['booking_id'] );
		
		$ticket_quantity = 0;
		if( $tickets ){
			foreach ( $tickets as $ticket ) {
				$cart['item_name_'.$i]= esc_html( $event->post_title.' ('.$ticket->ticket_name.')' );
				$cart['amount_'.$i]= floatval( $ticket->ticket_price );
				$cart['quantity_'.$i]= (int) $ticket->ticket_quantity;
				$ticket_quantity += (int) $ticket->ticket_quantity;
				$i++;
			}
		}
		
		//Handle discounts
		$discount = eo_get_booking_meta( $booking['booking_id'], 'discount', true );
		if( $discount ){
			$cart['discount_amount_cart'] = $discount['amount'];
			$cart['custom']['discount'] = $discount;
		}
		
		//
		//$cart['tax_cart']
		
		$cart['custom'] = build_query( array(
							'booking_id' => $booking['booking_id'],
							'event_id' => $booking['event_id'],
							'occurrence_id' => $booking['occurrence_id'],
							'booking_user' => $booking['booking_user'],
							'ticket_quantity' => $ticket_quantity
						) );
		
		$cart = apply_filters( 'eventorganiser_pre_gateway_checkout_paypal', $cart, $booking );
		$this->post_to_gateway( $cart );
	}
	
	/**
	 * Posts the cart to the payment gateway
	 * $cart should be an array of of the form:
	 * 
	 *	* item_name_1 => Name of event (and date) and ticket
	 *	* amount_1 => cost of ticket
	 *	* quantity_1 => #tickets,
	 *	* item_name_2 => ....etc
	 *	* email => bookees email
	 *	* custom => custom booking data
	 *	* return'=> where to return to
	 *
	 */
	function post_to_gateway( $cart ) {
		
		$paypal_args = array_merge( $cart, array(
				'cmd'           => '_cart',
				'upload'		=> '1',
				'business'      => $this->email,
				'no_shipping'   => '1',
				'shipping'      => '0',
				'no_note'       => '1',
				'currency_code' => eventorganiser_pro_get_option( 'currency' ),
				'charset'       => get_bloginfo( 'charset' ),
				'page_style'    => eventorganiser_pro_get_option( 'paypal_page_style' ),
				'rm'            => '2',
				'notify_url'	=> $this->listener 
			) );
		
		// get the PayPal  uri
		$paypal_url = $this->url.'?'.http_build_query( $paypal_args );
		$paypal_url = apply_filters( 'eventorganiser_gateway_checkout_paypal', $paypal_url, $paypal_args );
		
		if( $this->debug ){
			error_log( "Post to paypal (notify_url): " . addslashes( $this->listener ), 0 );
			error_log( "Post to paypal: " . addslashes( $paypal_url ), 0 );
		}
		
		wp_redirect( $paypal_url );
		exit();
	}
	
	/**
	 * A method that detals with paypal IPN handling. This is called by the gateway listener when a message is sent from PayPal
	 * It validates the message with PayPal. If it is not valid, or there is otherwise an error - this is logged.
	 * A valid response is logged. Transactions & Recurring profile payments are checked to see if there weren't already logged (they should be)
	 * A recurring payment updates the user's last payment, and expire date
	 * A skipped or expired recurring payment updates the user's status to 'expired'.
	 *
	 * @uses wp_remote_post
	 */
	function handle_ipn() {

		if( $this->debug ){
			error_log( "PayPal handle_ipn called", 0 );
		}
		$received_values = array( 'cmd' => '_notify-validate' );
		$received_values += stripslashes_deep( $_REQUEST );

		$raw_data = $_POST;

		// Send the message back to PayPal just as we received it
		$params = array(
			'body' => $received_values,
			'timeout' => 45,
			'sslverify'=>false,
		);
		$resp = wp_remote_post( $this->url, $params );
		
		if( $this->debug ){
			error_log( "Recieved values: " . addslashes( print_r( $received_values, true ) ), 0 );
			if( is_wp_error( $resp ) ){
				error_log( "Error confirming IPN receipt: ".addslashes( $resp->get_error_message() ), 0 );
			}else{
				error_log( "IPN receipt response: ".addslashes( wp_remote_retrieve_response_code( $resp )." ".wp_remote_retrieve_body( $resp ) ), 0 );
			}
		}
		
		//Check if its verified
		if ( trim( wp_remote_retrieve_body( $resp ) ) == 'VERIFIED' ) {
			$log=array();
			$type = 'unknown';

			//If the transaction ID is known, set accordingly
			if ( !empty( $raw_data['txn_id'] ) )
				$log['id'] = $raw_data['txn_id'];

			//If the transaction type is set, set the log details accordingly
			if ( !empty( $raw_data['txn_type'] ) ) {
				switch ( $raw_data['txn_type'] ) {
				case 'cart':
				case 'express_checkout':
					$log['transaction_id'] = $raw_data['txn_id'];
					$log['log_message']='A payment has been made';
					break;
				case 'new_case':
				case 'adjustment':
				default:
					$type = 'unknown';
					break;
				}
			}

			//Payment status - change message according to status
			switch ( $raw_data['payment_status'] ) {
			case 'Completed':
			case 'Created':
			case 'Processed';
				$type ='transaction';
				$log['type']= 'transaction';
				$log['log_message']= 'A payment has been made';
				break;
			case 'Pending':
				$type ='pending';
				$log['type']= 'pending';
				$log['log_message']= 'Payment pending. ('.$raw_data['pending_reason'].')';
				break;
			case 'Denied':
			case 'Failed':
			case 'Refunded':
			case 'Reversed':
			case 'Canceled_Reversal':
				break;
			}

			//Custom data should contain data as a query string
			if ( !empty( $raw_data['custom'] ) ) {
				$log = wp_parse_args( $raw_data['custom'], $log );
			}

			//mc_gross contains the amount charged (i.e. BEFORE PayPal take their cut)
			if ( !empty( $raw_data['mc_gross'] ) )
				$log['amount'] = $raw_data['mc_gross'];

			if( $this->debug ){
				error_log( "IPN log: " . addslashes( print_r( $log, true ) ), 0 );
			}

			//Serialize the raw data for reference
			$log['raw_data'] = $raw_data;
			$log['log_stored'] = time();
			$log['gateway'] = 'paypal';

			//Type should be 'transaction' or 'pending'
			if ( !empty( $type ) ) {

				//Cast as array to support cart extension
				$booking_ids = is_array( $log['booking_id'] ) ? $log['booking_id']: array( $log['booking_id'] );
				
				if( $this->debug ){
					error_log( "IPN action triggered. Type: " . $type, 0 );
					error_log( "Booking ID: ".addslashes( print_r( $booking_ids, true ) ), 0 );
				}

				//This action might be deprecated in future...
				foreach( $booking_ids as $booking_id ){
					do_action( 'eventorganiser_gateway_notification_'.$type, $booking_id, $log );	
				}
				
				/* This hook may be removed without notice. Do not use. 
				 * @ignore 
				 */
				do_action( 'eventorganiser_gateway_paypal_ipn', $log );
				
			}
			
		}else {//Else Message could not be validated - log error
			if( $this->debug ){
				error_log( addslashes( "IPN not verified: ".wp_remote_retrieve_response_code( $resp )." ".wp_remote_retrieve_body( $resp ) ), 0 );
			}
			wp_die( "IPN Request Failure" );
		}
	}
}//END class
