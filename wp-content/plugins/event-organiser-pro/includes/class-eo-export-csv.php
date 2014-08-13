<?php
/**
 * Ticket / Booking Export
 *
 * @package ticket-booking-export
 */
if ( ! function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a part of plugin, not much I can do when called directly.";
	exit;
}

/**
 * @ignore
 */
abstract class EO_Export_CSV {

	/**
	 * File name for the export file (excluding extension)
	 * @var string
	 */
	public $file_name;
	
	/**
	 * File type
	 * @var string
	 */
	public $file_type = 'text/csv';
	
	/**
	 * Additional arguments which are expected to be parsed to the constructor.
	 * @var array
	 */
	public $args = array();
	
	/**
	 * Delimiter used.
	 * @var string
	 */
	protected $delimiter = ',';
	
	/**
	 * Text delimiter
	 * @var string
	 */
	protected $text_delimiter = '"';

	/**
	 * Constructor
	 */
	public function __construct( $args=array() ) {
		
		$this->args = $args;
		$this->file_name = sanitize_file_name( $this->file_name() ) . '.csv';
		
		$this->init();
	}
	
	function set_delimiter( $delimiter ){
		$allowed_delimiters = array( 
			'comma' => ",",  "," => ",",
			'space' => " ",  " " => " ",
			'tab' 	=> "\t", "\t" => "\t", '\t' => "\t" );
		if( isset( $allowed_delimiters[ $delimiter ] ) ) {
			$this->delimiter = $allowed_delimiters[ $delimiter ];
			return true;
		}		
		return false;
	}
	
	function set_text_delimiter( $text_delimiter ){
		$allowed_delimiters = array( '"', "'" );
		if( in_array( $text_delimiter, $allowed_delimiters) ){
			$this->text_delimiter = $text_delimiter;
			return true;
		}
		return false;
	}

	abstract function file_name();

	function file_type() {
		return 'text/csv';
	}

	abstract function get_headers();

	abstract function get_items();

	abstract function get_cell(  $header, $item );
	
	/**
	 * Set headers, items and export file
	 * @since 1.0.0
	 */
	public function init() {
		
		$this->headers = $this->get_headers();
		$this->items = $this->get_items( $this->args );
	
	}

	/** 
	 * Returns the content of the CSV file
	 *  
	 */
	public function render(){
		
		$headers = $this->headers;
		$items = $this->items;
		
		$csv = '';

		//Headers
		$csv .= implode( $this->delimiter, $headers ) . "\r\n";
		
		//Data
		foreach ( $items as $item ) {
			$data = array();
			foreach ( $headers as $hid => $header ) {
				$value = $this->get_cell( $hid, $item );
				$data[] = $this->text_delimiter . str_replace( $this->text_delimiter, $this->text_delimiter.$this->text_delimiter, $value ) . $this->text_delimiter;
			}
			$csv .= implode( $this->delimiter, $data ) . "\r\n";
		}

		return $csv;
	}
	/**
	 * Creates a CVS file
	 *
	 * @since 1.0.0
	 */
	public function export() {
		global $wpdb;

		$headers = $this->headers;
		$items = $this->items;

		if ( ! $items ) {
			exit;
		}

		//File header
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $this->file_name );
		header( 'Content-Type: '.$this->file_type.'; charset=' . get_option( 'blog_charset' ), true );

		echo $this->render();
		exit();
	}
}
// end class


/**
 * @ignore
 */
class EO_Export_Bookings_CSV extends EO_Export_CSV  {

	function get_headers() {
		$headers = array(
			'booking_ref'=>__( 'Booking Reference', 'eventorganiserp' ),
			'event' =>__( 'Event', 'eventorganiserp' ),
			'occurrence'=>__( 'Date', 'eventorganiserp' ),
			'bookee'=>__( 'Bookee', 'eventorganiserp' ),
			'bookee_email'=>__( 'E-mail', 'eventorganiserp' ),
			'booking_ticket_qty'=>__( 'Ticket Quantity', 'eventorganiserp' ),
			'booking_total_price'=>__( 'Total Price', 'eventorganiserp' ),
			'booking_notes'=>__( 'Booking Notes', 'eventorganiserp' ),
			'booking_date'=>__( 'Booking Date (UTC)', 'eventorganiserp' ),
			'booking_status'=>__( 'Booking Status', 'eventorganiserp' ),
		);

		if ( eventorganiser_pro_get_option( 'book_series' ) )
			unset( $headers['occurrence'] );
		
		if( !empty( $this->args['meta'] ) ){
			foreach( $this->args['meta']  as $meta ){
				
				$parts = explode( '-form-', $meta );
				if( !isset($parts['0']) || !isset($parts['1']) )
					continue;

				//TODO improve way of getting field label. Possibly cache.
				$form_id = (int) array_shift($parts);
				$form = EO_Booking_Form_Controller::get_form( $form_id );
				
				$meta_id = implode( '-form-', $parts );
				$elements = $form->get_elements();
				
				if( !isset( $elements[$meta_id] ) ){
					continue;
				}
				
				$label = $elements[$meta_id]->get('label');
				$headers['meta_'.$meta_id] = $label;
			}

		}

		return apply_filters_ref_array( 'eventorganiser_export_bookings_headers', array( $headers, &$this ) ); 
	}

	function file_name() {
		$filename = 'eo-bookings-';
		if ( !empty( $this->args['event_id'] ) ) {
			$filename .= sanitize_file_name( get_the_title( $this->args['event_id'] ) ).'-';
			if (  $this->args['occurrence_id'] )
				$filename .= sanitize_file_name( eo_get_the_start( 'Y-m-d', $this->args['event_id'], null, $this->args['occurrence_id'] ) ).'-';
		}
		return $filename.date( 'Ymd' );
	}


	function get_items( $args=array() ) {
		$bookings = eventorganiser_get_bookings( $args );
		return $bookings;
	}

	function get_cell( $header, $item ) {

		switch ( $header ) {
		case 'booking_ref':
			$value = $item->ID;
		break;
		
		case 'bookee':
			$value = eo_get_booking_meta( $item->ID, 'bookee_display_name' );
		break;

		case 'booking_ticket_qty':
			$value = eo_get_booking_meta( $item->ID, 'ticket_quantity' );
		break;
		
		case 'bookee_email':
			$value = eo_get_booking_meta( $item->ID, 'bookee_email' );
		break;
		
		case 'event':
			$event_id = eo_get_booking_meta( $item->ID, 'event_id' );
			$value = get_the_title( $event_id );
		break;

		case 'occurrence':
			$occurrence_id = eo_get_booking_meta( $item->ID, 'occurrence_id' );
			if ( empty( $occurrence_id ) )
				return false;

			$value = eo_get_the_occurrence_start( 'Y-m-d', $occurrence_id );
		break;

		case 'booking_date':
			$value = get_the_time( __( 'Y/m/d g:i:s A' ), $item );
		break;

		case 'booking_status':
			$value = get_post_status( $item );
		break;

		case 'booking_total_price':
			$total = eo_get_booking_meta( $item->ID, 'booking_amount' );
			$value = eo_format_price( $total, false );
		break;

		case 'booking_notes':
			$value = eo_get_booking_meta( $item->ID, 'booking_notes' );
		break;
		
		default:
			$value = '';
			if( 'meta_' == substr( $header, 0, 5) ){
				$meta_id = substr( $header, 5 );
				$value = eo_get_booking_meta( $item->ID, 'meta_'.$meta_id, false );
				if( is_array( $value ) )
					$value = implode( ', ', $value );
			}	
		}
	
		return apply_filters_ref_array('eventorganiser_export_bookings_cell_'.$header, array( $value, $item, &$this) );
	}

} // end class

/**
 * @ignore
 */
class EO_Export_Tickets_CSV extends EO_Export_CSV  {

	function get_headers() {
		$headers = array(
			'booking_ref'=>__( 'Booking Reference', 'eventorganiserp' ),
			'event' =>__( 'Event', 'eventorganiserp' ),
			'occurrence'=>__( 'Date', 'eventorganiserp' ),
			'bookee'=>__( 'Bookee', 'eventorganiserp' ),
			'bookee_email'=>__( 'E-mail', 'eventorganiserp' ),
			'ticket'=>__( 'Ticket', 'eventorganiserp' ),
			'ticket_ref'=>__( 'Ticket Reference', 'eventorganiserp' ),
			'ticket_price'=>__( 'Ticket Price', 'eventorganiserp' ),
			'booking_date'=>__( 'Booking Date (UTC)', 'eventorganiserp' ),
			'booking_status'=>__( 'Booking Status', 'eventorganiserp' ),
		);

		if ( eventorganiser_pro_get_option( 'book_series' ) )
			unset( $headers['occurrence'] );

		return apply_filters_ref_array( 'eventorganiser_export_tickets_headers', array( $headers, &$this ) );
	}

	function file_name() {
		$filename = 'eo-tickets-';
		if ( !empty( $this->args['event_id'] ) ) {
			$filename .= sanitize_file_name( get_the_title( $this->args['event_id'] ) ).'-';
			if (  $this->args['occurrence_id'] )
				$filename .= sanitize_file_name( eo_get_the_start( 'Y-m-d', $this->args['event_id'], null, $this->args['occurrence_id'] ) ).'-';
		}
		return $filename.date( 'Ymd' );
	}


	function get_items( $args=array() ) {
		$tickets =eventorganiser_get_tickets( $args );
		return $tickets;
	}
	
	function get_cell( $header, $item ) {

		switch ( $header ) {
			case 'booking_ref':
				$value = $item->booking_id;
			break;
		
			case 'transaction_ref':
				$value = eo_get_booking_meta( $item->booking_id, 'transaction_id' );
			break;
		
			case 'bookee':
				$value = eo_get_booking_meta( $item->booking_id, 'bookee_display_name' );
			break;
		
			case 'bookee_email':
				$value = eo_get_booking_meta( $item->booking_id, 'bookee_email' );
			break;

			case 'event':
				$event_id =(int) $item->event_id;
				$value = get_the_title( $event_id );
			break;
		
			case 'occurrence':
				$occurrence_id =(int) $item->occurrence_id;
				if ( empty( $occurrence_id ) )
					$value = $occurrence_id;
				else
					$value = eo_get_the_occurrence_start( 'Y-m-d', $occurrence_id );
			break;
		
			case 'booking_date':
				$value = get_the_time( __( 'Y/m/d g:i:s A' ), $item->booking_id );
			break;
		
			case 'booking_status':
				$value = get_post_status( $item->booking_id );
			break;
		
			case 'ticket_price':
				$ticket_price = $item->ticket_price;
				$value =  eo_format_price( $ticket_price, false );
			break;
		
			case 'ticket_ref':
				$value = $item->ticket_reference;
			break;
		
			case 'ticket':
				$value = $item->ticket_name;
			break;
		
			default:
				$value = $header;
		}
		
		return apply_filters_ref_array('eventorganiser_export_tickets_cell_'.$header, array( $value, $item, &$this) );
	}

} // end class
