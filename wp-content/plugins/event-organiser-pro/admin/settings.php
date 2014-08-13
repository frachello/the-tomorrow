<?php

/**
 * Adds Bookings & Booking Form customiser settings tab
 * @param array $sections
 * @return array
 */
function eventorganiser_pro_add_settings( $sections ) {
	$sections['bookings']= __( 'Bookings', 'eventorganiserp' );
	$sections['booking-form']= __( 'Booking Form', 'eventorganiserp' );
	return $sections;
}

/**
 * Regsister the settings & settings section
 */
function eventorganiser_pro_register_settings( $tab_id ) {
	
	register_setting( 'eventorganiser_permissions', 'eventorganiser_pro_options' );
	register_setting( 'eventorganiser_booking-form', 'eventorganiser_pro_options', 'eventorganiser_pro_validate_settings' );
	register_setting( 'eventorganiser_bookings', 'eventorganiser_pro_options', 'eventorganiser_pro_validate_settings' );
	register_setting( 'eventorganiser_general', 'eventorganiser_pro_license' );
	
	add_settings_section( 'bookings', __( 'General', 'eventorganiserp' ), '__return_false',  'eventorganiser_'.$tab_id );
	add_settings_section( 'bookings_paypal', __( 'PayPal', 'eventorganiserp' ), '__return_false',  'eventorganiser_'.$tab_id );
	add_settings_section( 'bookings_offline', __( 'Offline Payment', 'eventorganiserp' ), '__return_false',  'eventorganiser_'.$tab_id );
	add_settings_section( 'bookings_email', __( 'E-mail', 'eventorganiserp' ), '__return_false',  'eventorganiser_'.$tab_id );

	add_action( "load-settings_page_event-settings", 'eventorganiser_pro_add_fields', 10, 0 );
	add_thickbox();
}
add_action( "eventorganiser_register_tab_bookings", 'eventorganiser_pro_register_settings' );

/**
 * Displays the options allowing admin to add/remove booking-related 
 * capabilities.
 * @since 1.5
 * @ignore
 */
function _eventorganiser_pro_booking_capability_options(){
	
	global $wp_roles;
	
	$caps = array(
		'manage_eo_bookings' => __( "Manage bookings", 'eventorganiserp' ),
		'manage_others_eo_bookings' => __( "Manage other events' bookings", 'eventorganiserp' ),
	);
	?>
	<h4><?php _e( 'Booking management permissions', 'eventorganiserp' ); ?></h4>
	<p> <?php _e( 'Set permissions for booking management', 'eventorganiser' ); ?> </p>
	
	<table class="widefat fixed posts">
		<thead>
			<tr>
				<th><?php _e( 'Role', 'eventorganiser' ); ?></th>
				
				<?php foreach ( $caps as $eo_role => $eo_role_display ): ?>
						<th><?php echo esc_html( $eo_role_display );?></th>
				<?php endforeach; ?>
				 
			</tr>		
		</thead>
				
		<tbody>
			<?php
			$array_index = 0;
			foreach ( get_editable_roles() as $role_name => $display_name ):
				$role = $wp_roles->get_role( $role_name ); 
				$role_name = isset( $wp_roles->role_names[$role_name] ) ? translate_user_role( $wp_roles->role_names[$role_name] ) : __( 'None' );
	
				printf( '<tr %s>', $array_index == 0 ? 'class="alternate"' : '' );
					printf( '<td> %s </td>',esc_html( $role_name ) );
	
					foreach ( $caps as $eo_role => $eo_role_display ):
						printf(
							'<td>
								<input type="checkbox" name="eventorganiser_pro_options[permissions][%s][%s]" value="1" %s %s  />
							</td>',
							esc_attr( $role->name ),
							esc_attr( $eo_role ),
							checked( '1', $role->has_cap( $eo_role ), false ),
							disabled( $role->name, 'administrator', false ) 
						);
					endforeach; //End foreach $eventRoles 
				echo '</tr>';
		
				$array_index = ( $array_index + 1) % 2;
			endforeach; //End foreach $editable_role ?>
		</tbody>
	</table>
	<?php 	
}
add_action( 'eventorganiser_event_settings_permissions', '_eventorganiser_pro_booking_capability_options' );


/**
 * Add settings fields
 */
function eventorganiser_pro_add_fields( $tab_id ='bookings' ) {

	switch ( $tab_id ) {
		
	case 'bookings':

		add_settings_field( 'currency',  __( 'Currency', 'eventorganiserp' ), 'eventorganiser_select_field' , 'eventorganiser_'.$tab_id, $tab_id,
			array(
				'options'=> wp_list_pluck( eventorganiser_get_currencies(), 'name' ),
				'selected'=> eventorganiser_pro_get_option( 'currency' ),
				'label_for'=>'currency',
				'name'=>'eventorganiser_pro_options[currency]',
			) );
		$currency_symbol = eventorganiser_get_currency_symbol( eventorganiser_pro_get_option( 'currency' ) );
		add_settings_field( 'currency_position', __( 'Currency Position', 'eventorganiserp' ), 'eventorganiser_select_field', 'eventorganiser_'.$tab_id, $tab_id,
			array(
				'label_for'=>'currency_position',
				'name'=>'eventorganiser_pro_options[currency_position]',
				'selected'=> eventorganiser_pro_get_option( 'currency_position' ),
				'options'=>array(
					1=>__( 'Before', 'eventorganiserp' ).'  - '.$currency_symbol.'5',
					0=>__( 'After', 'eventorganiserp' ).'  - 5'.$currency_symbol,
				)
			) );
		add_settings_field( 'book_series',  __( 'Bookings', 'eventorganiserp' ), 'eventorganiser_radio_field' , 'eventorganiser_'.$tab_id, $tab_id,
			array(
				'options'=> array( __( 'Particular Occurrence', 'eventorganiserp' ), __( 'Entire series', 'eventorganiserp' ) ),
				'name'=>'eventorganiser_pro_options[book_series]',
				'checked'=>eventorganiser_pro_get_option( 'book_series' ),
				'label_for'=>'book_series',
				'help' => __( 'You can either sell tickets for individual dates of an event, or sell tickets for the entire series - for example, places on a course', 'eventorganiserp' )
			) );

		add_settings_field( 'reserve_pending_tickets',  __( 'Reserve Pending Tickets', 'eventorganiserp' ), 'eventorganiser_checkbox_field' , 'eventorganiser_'.$tab_id, $tab_id,
			array(
				'label_for'=>'reserve_pending_tickets',
				'name'=>'eventorganiser_pro_options[reserve_pending_tickets]',
				'options'=> 1,
				'checked'=>eventorganiser_pro_get_option( 'reserve_pending_tickets' ),
			) );
		
		add_settings_field( 'allow_guest_booking',  __( 'Allow Logged-out Users to place bookings?', 'eventorganiserp' ), 'eventorganiser_select_field' , 'eventorganiser_'.$tab_id, $tab_id,
		array(
				'options'=> array(
					0 => __( 'No', 'eventorganiserp' ),
					1 => __( 'Yes but register an account for them', 'eventorganiserp' ),
					2 => __( 'Yes, account is optional', 'eventorganiserp' ),
					3 => __( 'Yes but do not register an account', 'eventorganiserp' ),
				),
				'selected'=> eventorganiser_pro_get_option( 'allow_guest_booking' ),
				'label_for'=>'allow_guest_booking',
				'name'=>'eventorganiser_pro_options[allow_guest_booking]',
		) );
		
		add_settings_field( 'notify_bookings',  __( 'Notify me when', 'eventorganiserp' ), 'eventorganiser_checkbox_field' , 'eventorganiser_'.$tab_id, $tab_id,
			array(
				'label_for'=>'notify_bookings',
				'name'=>'eventorganiser_pro_options[notify_bookings]',
				'checked'=>eventorganiser_pro_get_option( 'notify_bookings' ),
				'options'=> array(
					'new'=> __( 'A new booking is made', 'eventorganiserp' ),
					'confirmed' =>  __( 'A booking is confirmed', 'eventorganiserp' ),
				),
			) );

		/*PayPal */
		add_settings_field( 'paypal_live_status',  __( 'Live Switch', 'eventorganiserp' ), 'eventorganiser_select_field' , 'eventorganiser_'.$tab_id, $tab_id.'_paypal',
			array(
				'label_for'=>'paypal_live_status',
				'name'=>'eventorganiser_pro_options[paypal_live_status]',
				'options'=>array(
					'1'=>__( 'Live', 'eventorganiser' ),
					'0'=>__( 'Sandbox Mode', 'eventorganiser' ),
					'-1'=>__( 'Disable', 'eventorganiser' ),
				),
				'selected'=>eventorganiser_pro_get_option( 'paypal_live_status' ),
			) );

		add_settings_field( 'paypal_email',  __( 'PayPal Email', 'eventorganiserp' ), 'eventorganiser_text_field' , 'eventorganiser_'.$tab_id, $tab_id.'_paypal',
			array(
				'value'=>eventorganiser_pro_get_option( 'paypal_email' ),
				'label_for'=>'paypal_email',
				'name'=>'eventorganiser_pro_options[paypal_email]',
				'class' => 'regular-text',
			) );
		
		add_settings_field( 'paypal_page_style',  __( 'PayPal page style', 'eventorganiserp' ), 'eventorganiser_text_field' , 'eventorganiser_'.$tab_id, $tab_id.'_paypal',
			array(
				'value' => eventorganiser_pro_get_option( 'paypal_page_style' ), 
				'label_for'=>'paypal_page_style',
				'name'=>'eventorganiser_pro_options[paypal_page_style]',
				'class' => 'regular-text',
				'inline_help' => eventorganiser_inline_help(
					__( 'Style your PayPal payment page', 'eventorganiserp' ),
					sprintf(
					__( 'PayPal allows you to tailor the booking payment page to you site. For more information, <a href="%s" target="_blank">see this tutorial</a>', 'eventorganiserp' ),
					'http://wp-event-organiser.com/blog/tutorial/style-your-paypal-payment-page/'
					)
				),
			) );

		add_settings_field( 'paypal_local_site',  __( 'PayPal Local Site', 'eventorganiserp' ), 'eventorganiser_select_field' , 'eventorganiser_'.$tab_id, $tab_id.'_paypal',
			array(
				'options'=>array(
					'AU' => __( 'Australia', 'eventorganiserp' ),
					'AT' => __( 'Austria', 'eventorganiserp' ),
					'BE' => __( 'Belgium', 'eventorganiserp' ),
					'CA' => __( 'Canada', 'eventorganiserp' ),
					'CN' => __( 'China', 'eventorganiserp' ),
					'FR' => __( 'France', 'eventorganiserp' ),
					'DE' => __( 'Germany', 'eventorganiserp' ),
					'IT' => __( 'Italy', 'eventorganiserp' ),
					'MX' => __( 'Mexico', 'eventorganiserp' ),
					'NL' => __( 'Netherlands', 'eventorganiserp' ),
					'PH' => __( 'Philippines', 'eventorganiserp' ),
					'PL' => __( 'Poland', 'eventorganiserp' ),
					'ES' => __( 'Spain', 'eventorganiserp' ),
					'CH' => __( 'Switzerland', 'eventorganiserp' ),
					'GB' => __( 'United Kingdom', 'eventorganiserp' ),
					'US' => __( 'United States', 'eventorganiserp' ),
				),
				'label_for'=>'paypal_local_site',
				'selected'=>eventorganiser_pro_get_option( 'paypal_local_site' ),
				'name'=>'eventorganiser_pro_options[paypal_local_site]',
			) );

		/*Offline */
		add_settings_field( 'offline_live_status',  __( 'Live Switch', 'eventorganiserp' ), 'eventorganiser_select_field' , 'eventorganiser_'.$tab_id, $tab_id.'_offline',
			array(
				'options'=>array(
					'1'=>__( 'Live', 'eventorganiser' ),
					'-1'=>__( 'Disable', 'eventorganiser' ),
				),
				'selected'=>eventorganiser_pro_get_option( 'offline_live_status' ),
				'label_for'=>'offline_live_status',
				'name'=>'eventorganiser_pro_options[offline_live_status]',
			) );
		add_settings_field( 'offline_instructions', __( 'Offline Payment Instructions', 'eventorganiserp' ), 'eventorganiser_textarea_field', 'eventorganiser_'.$tab_id, $tab_id.'_offline',
			array(
				'label_for'=>'offline_instructions',
				'value' => eventorganiser_pro_get_option( 'offline_instructions' ),
				'help' => __( 'This will appear on the booking form, informing the bookee on how to make payment.', 'eventorganiserp' ),
				'name'=>'eventorganiser_pro_options[offline_instructions]',
			) );

		/* Email */
		add_settings_field( 'email_template',  __( 'E-mail template', 'eventorganiserp' ), 'eventorganiser_select_field' , 'eventorganiser_'.$tab_id, $tab_id.'_email',
			array(
				'options'=>eventorganiser_get_email_templates(),
				'label_for'=>'email_template',
				'selected'=>eventorganiser_pro_get_option( 'email_template' ),
				'name'=>'eventorganiser_pro_options[email_template]',
				'inline_help' => eventorganiser_inline_help( 
								__( 'Custom Templates', 'eventorganiserp' ),
								sprintf( 
									__( 'You can <a href="%s" target="_blank">create your own e-mail templates</a> and add them to your theme.', 'eventorganiserp' ),
									'http://wp-event-organiser.com/pro-features/e-mailing-bookees'
								),
								false,
								'info'
							),
			) );
		add_settings_field( 'email_tickets_message_preview',  __( 'Preview E-mail', 'eventorganiserp' ), 'eventorganiser_pro_email_preview' , 'eventorganiser_'.$tab_id, $tab_id.'_email',
			array(
				'label_for'=>'email_tickets_message_preview',
			) );
		add_settings_field( 'email_tickets_message',  __( 'Booking email', 'eventorganiserp' ), 'eventorganiser_textarea_field' , 'eventorganiser_'.$tab_id, $tab_id.'_email',
			array(
				'value'=>eventorganiser_pro_get_option( 'email_tickets_message' ),
				'label_for'=>'email_tickets_message',
				'tinymce'=>true,
				'name'=>'eventorganiser_pro_options[email_tickets_message]',
				'help' => __( 'In the e-mail template you can use booking information placeholders', 'eventorganiserp' )
						.eventorganiser_inline_help(
							__(  'E-mailing Bookees', 'eventorganiserp' ),
							__( 'You can use the following placeholders', 'eventorganiserp' )
							.'<ul>'
							.'<li> <code>%first_name%</code></li>'
							.'<li> <code>%last_name%</code></li>'
							.'<li> <code>%booking_reference%</code></li>'
							.'<li> <code>%tickets%</code></li>'
							.'<li> <code>%event_date%</code></li>'
							.'<li> <code>%event_title%</code></li>'
							.'</ul>'
							.'<a href="http://wp-event-organiser.com/pro-features/e-mailing-bookees/#email-placeholders">'
							.__( 'See full list', 'eventorganiserp' ).'</a>'
						 ), 
			) );
		break;
	}

}

/**
 * Displays preview button for the e-mail content & template
 */
function eventorganiser_pro_email_preview( ) {

	$body = wpautop( eventorganiser_pro_get_option( 'email_tickets_message' ) );
	$body = eventorganiser_get_email_preview( $body, eventorganiser_pro_get_option( 'email_template' ) );

	printf( 
		'<a href="#email-preview" id="view-email-preview" class="button-secondary" title="%1$s">%1$s</a>
			<div id="email-preview-wrap" style="display:none;"> <div id="email-preview">%2$s</div></div>',
		__( 'Preview Email', 'eventorganiserp' ),
		$body
	);
}

function eventorganiser_pro_validate_settings( $settings = array() ) {
	
	$checkboxes = array( 'reserve_pending_tickets', 'notify_bookings', 'allow_guest_booking' );
	if( is_null( $settings ) )
		$settings = array();

	$tab = isset( $_REQUEST['eventorganiser_options']['tab'] ) ? $_REQUEST['eventorganiser_options']['tab'] : false;
	
	if ( $tab == 'booking-form' ){
		
		$fields = !empty( $_POST['eventorganiser_field'] ) ?  $_POST['eventorganiser_field'] : array();
		$form_settings = !empty( $_POST['eventorganiser_form_settings'] ) ?  $_POST['eventorganiser_form_settings'] : array();
		$form_name = !empty( $_POST['eo-form-name'] ) ?  sanitize_text_field( $_POST['eo-form-name'] ) : '';
		$form_id = (int) $_POST['eventorganiser-form-id'];

		if( $form_id && $fields ){
			wp_update_post( array( 'ID' => $form_id, 'post_name' => $form_name, 'post_title' => $form_name ) );
			update_post_meta( $form_id, '_eo_booking_form_fields', $fields );
			
			//Checkboxes
			$form_settings['simple_mode'] = isset( $form_settings['simple_mode'] ) ? $form_settings['simple_mode'] : 0; 
			foreach( $form_settings as $form_setting => $value ){
				update_post_meta( $form_id, '_eventorganiser_booking_'.$form_setting, $value );
			}
		}
		
	}elseif( $tab == 'permissions' ){
		
		global $wp_roles;
		
		$caps = array( 'manage_eo_bookings', 'manage_others_eo_bookings' );
		$permissions = !empty( $settings['permissions'] ) ? $settings['permissions'] : array(); 

		if( isset( $settings['permissons'] ) )
			unset( $settings['permissons'] );
		
		foreach ( get_editable_roles() as $role_name => $display_name ):
			$role = $wp_roles->get_role( $role_name );

			//Don't edit the administrator
			if ( $role_name == 'administrator' )
				continue;

			//Foreach custom role, add or remove option.
			foreach ( $caps as $cap ):
				if ( isset( $permissions[$role_name][$cap] ) && $permissions[$role_name][$cap] == 1 ){
					$role->add_cap( $cap );
				} else {
					$role->remove_cap( $cap );
				}
			endforeach;
			
		endforeach; //End foreach $editable_roles
		
	}else{

		foreach ( $checkboxes as $cb ) {
			$settings[$cb] = isset( $settings[$cb] ) ? $settings[$cb] : 0;
		}

		foreach ( $settings as $id => $value ) {
			switch ( $id ) {

				//Integer
				case 'reserve_pending_tickets':
				case 'element_id':
				case 'field_id':
				case 'book_series':
				case 'currency_position':
				case 'paypal_live_status':
				case 'offline_live_status':
				case 'allow_guest_booking':
					$value = intval( $value );
				break;

				//Text
				case 'currency':
				case 'paypal_local_site':
				case 'email_template':
				case 'paypal_page_style':
					$value = sanitize_text_field( $value );
				break;

				//Email
				case 'paypal_email':
					$value = sanitize_email( $value );
				break;

				//HTML
				case 'offline_instructions':
				case 'email_tickets_message':
				break;

				//Checkbox array
				case 'notify_bookings':
					if ( !is_array( $value ) )
						$value = array( $value );

					$value = array_map( 'sanitize_text_field', $value );
				break;
			}
			$settings[$id] = $value;
		}
	}//Endif/else - tab==booking-form

	$existing_options = get_option( 'eventorganiser_pro_options', array() );
	$settings = array_merge( $existing_options, $settings );

	return $settings;
}



/**
 * Admin action listener
 *
 * Handles admin page requests to edite/create/delete forms. Hooked onto admin_init.
 * @TODO check nonces
 */
function _eventorganiser_form_action_listener(){

	if( isset($_REQUEST['action']) && 'eo-create-form' == $_REQUEST['action'] ){
		$id = wp_insert_post( array( 'post_status' => 'publish', 'post_type' => 'eo_booking_form', 'post_title' => 'New-Form' ) );
		$redirect = add_query_arg( 'form_id', $id, admin_url( 'options-general.php?page=event-settings&tab=booking-form' ) );
		wp_redirect( $redirect );
		exit();

	}elseif( isset( $_POST['eo-go-to'] ) && !empty($_POST['eo-edit-form']) ){
		$id = (int) $_POST['eo-edit-form'];
		$redirect = add_query_arg( 'form_id', $id, admin_url( 'options-general.php?page=event-settings&tab=booking-form' ) );
		wp_redirect( $redirect );
		exit();

	}elseif( !empty($_GET['eo-delete-form']) ){
		$id = (int) $_GET['eo-delete-form'];
		wp_delete_post( $id, true );
		wp_redirect( admin_url( 'options-general.php?page=event-settings&tab=booking-form' ) );
		exit();
	}
}
add_action( 'admin_init', '_eventorganiser_form_action_listener');


