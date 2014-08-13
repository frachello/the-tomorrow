<?php
/**
 * Email functions
 *
 * @package email-functions
 */

/**
 * Wrapper for {@see wp_mail()}. Allows an additional argument 'template' to be passed
 *
 * **A true return value does not automatically mean that the user received the
 * email successfully**. It just only means that the method used was able to
 * process the request without any errors.
 *
 * Automatically adds HTML header/footer and ensures content type is text/HTML
 * via the `wp_mail_content_type` filter.
 *
 * Applies the filter `eventorganiser_email_body_header` to the header
 * Applies the filter `eventorganiser_email_body_footer` to the footer
 *
 * If template other than 'none' is selected, this template is applied to the `$body` of the
 * email via the `eventorganiser_email_template` hook.
 *
 * @since 1.0
 * @uses wp_mail()
 *
 * @param string|array $to          Array or comma-separated list of email addresses to send message.
 * @param string  $subject     Email subject
 * @param string  $message     Message contents
 * @param string|array $headers     Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 * @param string  $template    The template (filename) to use for the email (e.g. `eo-email-template-blue.php` or `eventorganiser_pro_get_option( 'email_template' )`)
 * @return bool Whether the email contents were sent successfully.
 */
function eventorganiser_mail( $email, $subject, $body, $headers = array(), $attachments = array(), $template = 'none' ) {

	$header = apply_filters( 'eventorganiser_email_body_header', '<html><head><style type="text/css">#outlook a{padding: 0;}</style></head><body>' );
	$footer = apply_filters( 'eventorganiser_email_body_header', '</body></html>' );
	add_filter( 'wp_mail_content_type', 'eventorganiser_email_html_content_type' );

	if ( $template && 'none' != $template )
		$body = apply_filters( 'eventorganiser_email_template', $body, $template );

	/* And send! */
	return wp_mail( $email, $subject, $header.$body.$footer, $headers, $attachments );
}


/**
 * Sets email content type to HTML
 *
 * Hooked onto `wp_mail_content_type` inside `eventorganiser_mail` and removes itself.
 *
 * @since 1.0
 * @see eventorganiser_mail()
 * @access private
 * @ignore
 *
 * @return string 'text/HTML'
 */
function eventorganiser_email_html_content_type() {
	remove_filter( current_filter(), __FUNCTION__ );
	return "text/html";
}


/**
 * Function which applies specified template to the given email body
 *
 * This function is hooked onto `eventorganiser_email_template` inside `eventorganiser_mail()`
 *
 * @since 1.0
 * @see `eventorganiser_mail()`
 * @access private
 * @ignore
 *
 * @param string  $body          The email body
 * @param string  $template_name The template filename
 * @return string The email body, after the template has been applied.
 */
function eventorganiser_apply_email_template( $body, $template_name ) {

	$template_file = eventorganiser_get_email_template( $template_name );

	if ( !$template_file )
		return $body;

	global $eventorganiser_email_content;
	$eventorganiser_email_content = $body;

	ob_start();
	include $template_file;
	$body = ob_get_contents(); // assign buffer contents to variable
	ob_end_clean(); // end buffer and remove buffer contents

	$body = apply_filters( 'eventorganiser_template_' . $template_name, $body );

	return $body;
}
add_action( 'eventorganiser_email_template', 'eventorganiser_apply_email_template', 10, 2 );

/**
 * Prints the e-mail content
 *
 * E-mail template function to print the e-mail body. This should only be used in
 * email templates.
 */
function eventorganiser_email_content() {
	global $eventorganiser_email_content;
	echo $eventorganiser_email_content;
}

/**
 * Returns the direct path to an e-mail template
 *
 * Given a template filename this looks in the child theme, then parent theme and
 * then finally the templates folder of Event Organiser Pro for the template.
 *
 * Multiple templates can be given to look for each in turn - the first found template is used.
 *
 * @since 1.0
 * @uses `locate_template()`
 * @access private
 * @ignore
 *
 * @param string|array $template Template filename or an array of template filenames to find
 * @return string The absolute path to the first found template
 */
function eventorganiser_get_email_template( $template ) {

	$templates = array( $template, 'eo-email-template.php' );

	foreach ( $templates as $_template ) {

		$template_file = locate_template( $_template );
		if ( !empty( $template_file ) )
			break;

		if ( file_exists( EVENT_ORGANISER_PRO_DIR . 'templates/' . $template ) ) {
			$template_file = EVENT_ORGANISER_PRO_DIR . 'templates/' . $template;
			break;
		}
	}

	return $template_file;
}


/**
 * Returns all available email templates
 *
 * The default templates are hardcoded in. It searches the child and parent theme for additional
 * templates with the following header:
 *
 *      Event Organiser Email Template: [template name]
 *
 * Applies the filter `eventorganiser_get_email_templates` to the list of templates
 *
 * @since 1.0
 * @uses get_themes()
 * @uses get_current_theme()
 * @access private
 * @ignore
 *
 * @return array Array of e-mail templates of the form array( filename => name ).
 */
function eventorganiser_get_email_templates() {

	$email_templates = array(
		'none'=>'None',
		'eo-email-template-distinct.php' => 'Distinct',
		'eo-email-template-event-organiser.php' => 'Event Organiser',
		'eo-email-template-orange.php' => 'Orange',
		'eo-email-template-blue.php' => 'Blue',
		'eo-email-template-green.php' => 'Green',
	);
	
	if( eventorganiser_blog_version_is_atleast( '3.4' ) ){	
		$email_templates += eo_get_theme_email_templates( wp_get_theme() );

	}else{
		//Pre-3.4 support
		$themes = get_themes();
		$theme = get_current_theme();
		$templates = $themes[$theme]['Template Files'];

		if ( is_array( $templates ) ) {
			$base = array( trailingslashit(get_template_directory()), trailingslashit(get_stylesheet_directory()) );

			foreach ( $templates as $template ) {
				$basename = str_replace($base, '', $template);

				// don't allow template files in subdirectories
				if ( false !== strpos($basename, '/') )
					continue;

				if ( 'functions.php' == $basename )
					continue;

				$template_data = implode( '', file( $template ));

				$name = '';
				if ( preg_match( '|Event Organiser Email Template:(.*)$|mi', $template_data, $name ) )
					$name = _cleanup_header_comment($name[1]);

				if ( !empty( $name ) ) {
					$email_templates[$basename] = trim($name);
				}
			}
		}
	}
	
	return apply_filters( 'eventorganiser_get_email_templates', $email_templates );
}

/**
 * Retrieves the Event Organiser e-mail templates from the theme.
 * 
 * This function can only be used on version 3.4+. A work-around is used for earlier versions.
 * The function scans the theme filters for .php template files with the header
 * "Event Organiser Email Template: [template name]"
 * It also looks in the parent theme, if it exists.
 * 
 * Returns the templates as an array of the form array( template path => tepmlate name ) 
 * 
 * @ignore
 * @param WP_Theme $theme
 * @return array Theme (and parent theme)templates
 */
function eo_get_theme_email_templates( $theme ){

	$theme_email_templates = array();
	
	$files = (array) $theme->get_files( 'php', 1 );

	foreach ( $files as $file => $full_path ) {
		if ( ! preg_match( '|Event Organiser Email Template:(.*)$|mi', file_get_contents( $full_path ), $header ) )
			continue;
		$theme_email_templates[ $file ] = _cleanup_header_comment( $header[1] );
	}

	if ( $theme->parent() )
		$theme_email_templates += eo_get_theme_email_templates( $theme->parent() );
		
	return $theme_email_templates;
}

/**
 * Replace template tags inside the e-mail body with the appropriate content
 *
 * Current available tags include (but not limited to):
 *
 * `%display_name%` - The bookee's 'display name'
 * `%booking_reference%` - The booking reference
 * `%tickets%` - List of the purchased tickets
 * `%event_title%` - The title of the booked event
 * `%event_date%` - The start date of the booked event
 * `%form_submission%` - Information recieved from custom forms fields on the booking form
 *
 *
 * @since 1.0
 * @access private
 * @ignore
 */
function eventorganiser_email_template_tags( $message, $booking_id, $template = 'none' ) {

	$tag_parser = new EO_Email_Template_Tag_Parser( $booking_id, $template );
	$_message = $tag_parser->parse( $message );
	$_message = apply_filters( 'eventorganiser_email_template_tags', $_message, $booking_id, $message );

	return $_message;
}

/**
 * A class responsible for parsing an email message and replacing the template tags with the appropriate values.
 * 
 * @since 1.5
 * @ignore
 */
class EO_Email_Template_Tag_Parser{
	
	/**
	 * @var int $booking_id The booking ID associated with meail
	 */
	protected $booking_id = false;
	
	/**
	 * @var string $template Email template used for email 
	 */
	protected $template = false;
	
	/**
	 * @var int $event_id Taken from the booking ID 
	 */
	protected $event_id = false;
	
	/**
	 * @var int $occurrence_id Taken from the booking ID
	 */
	protected $occurrence_id = false;
	
	/**
	 * @var int $venue_id Taken from the event ID
	 */
	protected $venue_id = false;

	/**
	 * Supported tags and the 'preview' data
	 * @var array Keys are the supported tags
	 */
	protected $tags = array(
		/* Bookee data */
		'display_name' => 'John Smith',
		'first_name' => 'John',
		'last_name' => 'Smith',
		'second_name' => 'Smith',
		'username' => 'John.Smith',
			
		/* Booking data */
		'booking_date' => false,
		'booking_reference' => '1234',
		'transaction_id' => 'ABCDEF012345678',
		'booking_amount' => false,
		'ticket_quantity' => 3,
		'form_submission' => false,
		'tickets' => false,
			
		/* Event data */
		'event_title' => 'Hello World Event',
		'event_date' => false,
		'event_url' => false,
			
		/* Event Venue data */
		'event_venue' => 'Some Venue',
		'event_venue_address' => '1 Longton Road',
		'event_venue_city' => 'Edinburgh',
		'event_venue_postcode' => 'EH17 1XY',
		'event_venue_state' => 'West Lothian',
		'event_venue_country' => 'Scotland',
		'event_venue_url' => 'http://www.google.com'
	);
	
	function __construct( $booking_id, $template ){
		
		$this->booking_id = $booking_id;
		$this->event_id = (int) eo_get_booking_meta( $booking_id, 'event_id' );
		$this->occurrence_id = eo_get_booking_meta( $booking_id, 'occurrence_id' );
		$this->venue_id = eo_get_venue( $this->event_id );
		$this->template = $template;
		
		//'Dynamic' fake data
		$this->tags['event_date'] = eo_format_date( '+1 week', get_option( 'date_format' ) );
		$this->tags['event_url'] = site_url();
		$this->tags['booking_date'] = eo_format_date( 'now', get_option( 'date_format' ) );
		$this->tags['booking_amount'] =  eo_format_price( 34 );
		$this->tags['form_submission'] = eventorganiser_email_form_submission_list( -1 );
		$this->tags['tickets'] = eventorganiser_email_ticket_list( -1, $this->template );
	}
	
	
	function parse( $message ){
		
		$pattern = array();
		
		foreach ( $this->tags as $tag => $default_value )
			$pattern[] = '/%(' . $tag . ')({([^{}]*)}{([^{}]*)}|{[^{}]*})?%/';
		
		return preg_replace_callback( $pattern, array(__CLASS__,'parse_tag'), $message );
	}
	
	
	function parse_tag( $matches ){
		$tag = $matches[1];
		
		if( $this->booking_id == -1  ) {
			return isset( $this->tags[$tag] ) ? $this->tags[$tag] : false;
		}
		
		switch( $tag ):
		
			/* Bookee details */
			case 'display_name':
				return eo_get_booking_meta( $this->booking_id, 'bookee_display_name' );
			break;
			
			case 'first_name':
				return eo_get_booking_meta( $this->booking_id, 'bookee_first_name' );
			break;

			case 'second_name':
			case 'last_name':
				return eo_get_booking_meta( $this->booking_id, 'bookee_last_name' );
			break;
			
			case 'username':
				$bookee = eo_get_booking_meta( $this->booking_id, 'bookee' );
				$bookee_data = get_userdata( $bookee );
				$username  = ( $bookee_data ? $bookee_data->user_login : false );
				return $username;
			break;
			
			/* Booking details */
			case 'booking_date':
				return get_the_time( get_option( 'date_format' ), $this->booking_id );
			break;
			
			case 'booking_reference':
				return $this->booking_id;
			break;
			
			case 'transaction_id':
				return eo_get_booking_meta( $this->booking_id, 'transaction_id' );
			break;
			
			case 'booking_amount':
				return eo_format_price( eo_get_booking_meta( $this->booking_id, 'booking_amount' ) );
			break;

			case 'ticket_quantity':
				return eo_get_booking_meta( $this->booking_id, 'ticket_quantity' );
			break;
			
			case 'tickets':
				return eventorganiser_email_ticket_list( $this->booking_id, $this->template );
			break;
			
			case 'form_submission':
				return eventorganiser_email_form_submission_list( $this->booking_id );
			break;
			
			/* Event details */
			case 'event_title':
				 return get_the_title( $this->event_id );
			break;
			
			case 'event_date':
				
				switch( count($matches ) ):
					case 2:
						$dateFormat = get_option('date_format');
						$dateTime   = '';
						break;
					case 3:
						$dateFormat = $this->_clean_input( $matches[2] );
						$dateTime   = '';
						break;
					case 5:
						$dateFormat = $this->_clean_input( $matches[3] );
						$dateTime   = $this->_clean_input( $matches[4] );
						break;
				endswitch;
				
				$format = eo_is_all_day( $this->event_id ) ? $dateFormat : $dateFormat . $dateTime;
				
				if ( empty( $this->occurrence_id ) ) {
					$event_date = eo_get_schedule_start( $format, $this->event_id );
				}else {
					$event_date = eo_get_the_start( $format, $this->event_id, null, $this->occurrence_id );
				}
				return $event_date;
			break;
			
			case 'event_url':
				return get_permalink( $this->event_id );
			break;
			
			/* Venue details */
			case 'event_venue':
				return eo_get_venue_name( $this->venue_id );
			break;
		
			case 'event_venue_url':
				$venue_link = eo_get_venue_link( $this->venue_id );
				return ( !is_wp_error($venue_link) ? $venue_link : '');
			break;
			
			case 'event_venue_address':
			case 'event_venue_city':
			case 'event_venue_state':
			case 'event_venue_postcode':
			case 'event_venue_country':	
				$address = eo_get_venue_address( $this->venue_id );
				$part = str_replace( 'event_venue_', '', $tag );
				return $address[$part];
			break;
		
		endswitch;	
	}
	

	function _clean_input( $input ){
		$input = trim( $input, "{}" ); //remove { }
		$input = str_replace( array( "'",'"',"&#8221;","&#8216;", "&#8217;" ), '', $input ); //remove quotations
		return $input;
	}
}


/**
 * Handles the %form_submission% e-mail tag, returns the list of form data submitted
 *
 * @since 1.5
 * @ignore
 */
function eventorganiser_email_form_submission_list( $booking_id ){
	
	$form_data = array();
	
	if( $booking_id == -1 ){
		//Preview, fake data
		$form_data = array(
				array(
					'label' => 'Select a payment gateway',
					'values' => array( 'PayPal' ),
				),
				array(
						'label' => 'Select a meal option',
						'values' => array( 'Moroccan-spiced lamb' ),
				),
				array(
						'label' => 'Contact telephone number',
						'values' => array( '01234 567 891' ),
				),
				array(
						'label' => 'Interests',
						'values' => array( 'Photography', 'Technology', 'Football' ),
				)
		);
		
	}else{
		//Set up variables
		$meta = get_post_meta( $booking_id );
		$form_id = get_post_meta( $booking_id, '_eo_booking_form', true );
		
		if( $form_id ){
			$form = new EO_Booking_Form( array( 'id' => $form_id ) );
			$elements = ( $form->fetch() ? $form->get_elements() : array() );
		}else{
			$elements = array();
		}
		
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
			
			$form_data[] = array( 'label' => $label, 'values' => $meta_values );
		}
	}
	
	$html = '<table bgcolor="#FFFFFF" style="border: 1px solid #ececec;" border="0" cellpadding="5" cellspacing="0" width="100%">' . "\n";
	$html .= "\t<tbody>\n";
	
	foreach( $form_data as $data ){
		$label = $data['label'];
		$meta_values = $data['values'];
		
		$html .= sprintf(
			"\t\t" . "<tr bgcolor='#ececec' >" . "\n"
				. "\t\t\t" . "<th colspan='2' style='text-align:left;vertical-align:top;'>%s</th>" . "\n"
			."\t\t" . "</tr>" . "\n"
			."\t\t" . "<tr bgcolor='#FFFFFF'>" . "\n"
				."\t\t\t" . "<td width='20' rowspan=%d > </td>" . "\n"
				."\t\t\t" . "<td>%s</td>" . "\n"
			."\t\t" . "</tr>" . "\n",
			esc_html( $label ),
			count( $meta_values ),
			array_shift( $meta_values )
		);
	
		foreach ( $meta_values as $meta_value )
			$html .= sprintf( "\t\t<tr bgcolor='#FFFFFF'>\n\t\t\t<td>%s</td>\n\t\t</tr>\n", esc_html( $meta_value ) );
		
	}
	
	$html .= "\t</tbody>\n</table>";

	return $html;
}

/**
 * Handles the %tickets% e-mail tag, returns the list of tickets
 *
 * @since 1.0
 * @access private
 * @ignore
 */
function eventorganiser_email_ticket_list( $booking_id, $template ) {

	if ( -1 == $booking_id ) {
		//Preview, fake data
		$booking_tickets = array(
			(object) array( 'ticket_name' => 'Standard', 'ticket_price' => 12, 'ticket_reference' => 'abc123' ),
			(object) array( 'ticket_name' => 'Standard', 'ticket_price' => 12, 'ticket_reference' => 'def456' ),
			(object) array( 'ticket_name' => 'Student', 'ticket_price' => 10, 'ticket_reference' => 'a1b2c3' ),
		);
		$total_price = 34;

	}else {
		$booking_tickets = eo_get_booking_tickets( $booking_id, false );
		$total_price = eo_get_booking_meta( $booking_id, 'booking_amount' );
	}

	$booking_table = sprintf(
		'<table style="width:100%%;text-align:center;">
		<thead style="font-weight:bold;"><tr> <th>%s</th><th> %s </th> <th>%s</th></tr></thead>
		<tbody>',
		__( 'Ticket' ),
		__( 'Price' ),
		__( 'Ref.' )
	);

	foreach ( $booking_tickets as $ticket ) {
		$booking_table .= sprintf(
			'<tr> <td>%s<td> %s </td> <td>%s</td></tr>',
			esc_html( $ticket->ticket_name ),
			eo_format_price( $ticket->ticket_price ),
			$ticket->ticket_reference
		);
	}

	$booking_table .= apply_filters( 'eventorganiser_email_ticket_list_pre_total', '', $booking_id );
	$booking_table .= sprintf( '<tr> <td>%s</td><td> %s </td> <td></td></tr></tbody></table>', __( 'Total' ), eo_format_price( $total_price ) );

	return apply_filters( 'eventorganiser_email_ticket_list', $booking_table, $booking_tickets, $booking_id, $template );
}


/**
 * Generates an example email using the specified `$body` and
 * specified `$template`.
 *
 * @since 1.0
 * @access private
 * @ignore
 *
 * @param string  $body     The e-mail body
 * @param string  $template The e-mail template
 * @return string Example email
 */
function eventorganiser_get_email_preview( $body, $template ) {
	$body = eventorganiser_email_template_tags( $body, -1, $template );

	if ( 'none' == $template )
		return $body;

	return apply_filters( 'eventorganiser_email_template', $body, $template );
}





/* CUSTOM STYLING FOR TEMPLATES */

/**
 * Distinct Template custom styling & mark-up for event list
 *
 * @since 1.0
 * @access private
 * @ignore
 */
function eventorganiser_email_ticket_list_distinct_template( $tickets, $booking_tickets, $booking_id, $template ) {

	if ( 'eo-email-template-distinct.php' != $template || empty( $booking_tickets ) )
		return $tickets;

	$tickets = '<ul id="eo-tickets-list" style="padding:0px;position:relative;list-style:none;margin-left:15px;">';
	if ( $booking_tickets ) {
		$class='even';
		foreach ( $booking_tickets as $ticket ) {

			$class = ( $class == 'even' ? 'odd' : 'even' );
			$style = (  $class == 'odd' ? 'margin: 30px 30px 30px -30px;' : 'margin: 30px -15px 30px 30px;' );
			$style .= 'background:#8D2036;padding:25px;color:#E4E4C5;';
			$tickets .= sprintf( '<li class="%s eo-ticket" style="%s"> %s %s <span style="float:right"><em>Ticket Ref:</em> %s </span></li>',
				$class,
				$style,
				$ticket->ticket_name,
				eo_format_price( $ticket->ticket_price ),
				$ticket->ticket_reference
			);
		}
	}
	$tickets .= '</ul>';

	return $tickets;
}
add_filter( 'eventorganiser_email_ticket_list', 'eventorganiser_email_ticket_list_distinct_template', 10, 4 );


/**
 * Distinct Template custom styling & mark-up
 *
 * @since 1.0
 * @access private
 * @ignore
 */
function eventorgnaiser_distinct_template_styling( $body ) {

	$first_p = strpos( $body, '<p>' );
	if ( $first_p ) {
		$body = substr_replace( $body, '<p style="margin-top:50px;">', $first_p, 3 );
	}
	return $body;
}
add_filter( 'eventorganiser_template_eo-email-template-distinct.html', 'eventorgnaiser_distinct_template_styling' );
?>
