<?php
/**
 * Class used for displaying venue table and handling interations
 */

/*
 *The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class EO_Booking_Table extends WP_List_Table{

    /*
     * Constructor. Set some default configs.
     */
	function __construct(){
		global $status, $page;
		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'booking',     //singular name of the listed records
			'plural'    =>  'bookings',   //plural name of the listed records
			'ajax'      => true        //does this table support ajax?
        	) );
	    }

	function no_items() {
		_e( 'No bookings found.', 'eventorganiserp' );
	}

    /*
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     */
    function column_default($item, $column_name){
    	
    	$user_can_manage = current_user_can( 'manage_eo_booking', $item->ID );
    	
		 switch($column_name){
			case 'booking':
        			
				//Build row actions
				
				if( $item->post_status !== 'cancelled' ){
					$actions = array(
						'amend'    => sprintf('<a href="%s">'.__('View','eventorganiserp').'</a>',  admin_url('edit.php?post_type=event&page=bookings&action=edit&booking_id='.$item->ID)),
						'cancel' => sprintf(
						"<a class='submitdelete' title='%s' href='%s'> %s </a>",
							esc_attr( __( 'Cancel booking', 'eventorganiserp' ) ),
							eventorganiser_cancel_booking_url( $item->ID ),
							__( 'Cancel Booking', 'eventorganiserp' )
						)
					);
				}else{
					$url = add_query_arg( 'booking_id', $item->ID, admin_url( 'edit.php?post_type=event&page=bookings' ) );
					
					$uncancel_url = wp_nonce_url( add_query_arg( 'action', 'uncancel' , $url ), 'eo-uncancel-booking-'.$item->ID );
					$delete_url = wp_nonce_url( add_query_arg( 'action', 'delete', $url ), 'eo-delete-booking-'.$item->ID );
					
					$actions = array(
							'uncancel' => sprintf(
								"<a class='submitdelete' title='%s' href='%s'> %s </a>",
								esc_attr( __( 'Restore booking', 'eventorganiserp' ) ),
								$uncancel_url,
								__( 'Restore Booking', 'eventorganiserp' )
							),
							'cancel' => sprintf(
								"<a class='submitdelete' title='%s' href='%s'> %s </a>",
								esc_attr( __( 'Cancel booking', 'eventorganiserp' ) ),
								$delete_url,
								__( 'Delete Booking', 'eventorganiserp' )
							)
					);
				}

				
        
				$transaction_id = eo_get_booking_meta( $item->ID,'transaction_id' );
			        
				//Return the title contents
				if( $user_can_manage ){
					
					return $this->get_inline_data($item) . sprintf('<a href="%1$s" class="row-title">%2$s</a> %3$s',
						/*$1%s*/ admin_url('edit.php?post_type=event&page=bookings&action=edit&booking_id='.intval($item->ID)),
						/*$2%s*/ '#'.$item->ID.' </br><small>'. ( $transaction_id ? esc_html( $transaction_id ) : '' ).'</small>',
						/*$3%s*/ $this->row_actions($actions)
					);
					
				}else{
					return '<strong>#'.$item->ID.'</strong>';
				}
				break;

			case 'booking_bookee':
				$user_id = (int) eo_get_booking_meta( $item->ID, 'bookee' );
				$user_data = get_userdata($user_id);
				$url = remove_query_arg( array( 'paged', 'event_id', 'bookee_id', 'occurrence_id' ) );
				
				if( !$user_data ){
					return sprintf('<span class="row-title"> %s</span> <br><small> %s </small>',
						eo_get_booking_meta( $item->ID, 'bookee_display_name' ),
						eo_get_booking_meta( $item->ID, 'bookee_email' )
		        	);
					
				}else{
					return sprintf('<a href="%s" class="row-title"> %s</a> <br><small> %s </small>',
						add_query_arg( 'bookee_id', $user_id, $url ),
						eo_get_booking_meta( $item->ID, 'bookee_display_name' ),
						eo_get_booking_meta( $item->ID, 'bookee_email' )
		        	);
				}
				
				break;

			case 'booking_event':
				$event_id = eo_get_booking_meta($item->ID,'event_id');
				$occurrence_id = eo_get_booking_meta($item->ID,'occurrence_id');
				$title = esc_html(get_the_title($event_id));
				$url = remove_query_arg( array( 'event_id', 'bookee_id', 'occurrence_id', 'paged' ) );
				$url = add_query_arg( 'event_id', $event_id, $url );

				if( ! empty($occurrence_id )  ){
					$url2 = add_query_arg( 'occurrence_id', $occurrence_id, $url);
					$date=sprintf('<br><small><a href="%1$s">%2$s</a></small>',
			       	     /*$1%s*/ $url2,
			       	     /*$2%s*/eo_get_the_occurrence_start('d-m-Y',$occurrence_id)
        				);
				}else{
					$date ='';
				}

				$title=sprintf('<a href="%1$s" class="row-title">%2$s</a>%3$s',
			            /*$1%s*/ $url,
			            /*$2%s*/ $title,
			            /*$3%s*/ $date
        			);
				return $title;
				break;
				

			case 'booking_date':
				if ( '0000-00-00 00:00:00' == $item->post_date && 'date' == $column_name ) {
					$t_time = $h_time = __( 'Unpublished' );
					$time_diff = 0;
				} else {
					$t_time = get_the_time( __( 'Y/m/d g:i:s A' ), $item );
					$m_time = $item->post_date;

					$time = get_post_time( 'G', true, $item );
					$time_diff = time() - $time;

					if ( $time_diff > 0 && $time_diff < 24*60*60 )
						$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
					else
						$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
				}

				return $h_time;
				break;

			case 'booking_tickets':
				$tickets = eo_get_booking_tickets( $item->ID );
				$_tickets='';
				if( $tickets ){
					foreach( $tickets as $ticket){
						$_tickets .= esc_html($ticket->ticket_name).' : '.eo_format_price($ticket->ticket_price).' (x'.$ticket->ticket_quantity.')<br>';
					}
				}		
				return $_tickets;
				break;

			case 'booking_price':
				$total = eo_get_booking_meta($item->ID,'booking_amount');
				return  eo_format_price($total);

			case 'booking_status':
				$status = eo_get_booking_status( $item );
				$status_obj = get_post_status_object( $status );

				if( $status_obj && $status_obj->eventorganiser_include_in_confirmed ){
					return sprintf( '<span style="color:green;"> <strong> %s </strong> </span>', esc_html( $status_obj->label ) );
					
				}elseif( $status_obj ){
					return esc_html( $status_obj->label );
				
				}else{
					return esc_html( $status );
				}
				break;

			default:
				do_action( 'eventorganiser_booking_table_column', $column_name, $item ); 
				//return print_r($item,true); //Show the whole array for troubleshooting purposes
		}
    }


	/**
	 * adds hidden fields with the data for use in the inline editor for posts and pages
	 * @since 1.0
	 * @param unknown_type $user
	 */
	function get_inline_data($booking) {

		return '<div class="hidden" id="inline_' . $booking->ID . '"><div class="username">' . eo_get_booking_meta( $booking->ID, 'bookee_display_name' ). '</div></div>';
	}
	
    /**
     * Checkbox column for Bulk Actions.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     */
    function column_cb($item){
    	if( current_user_can( 'manage_eo_booking', $item->ID ) ){
        	return sprintf(
            	'<input type="checkbox" name="%1$s[]" value="%2$s" />',
            	/*$1%s*/ 'booking_id',  
            	/*$2%s*/ $item->ID       //The value of the checkbox should be the record's id
        	);
    	}
    }

    /**
     * Set columns sortable
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     */
    function get_sortable_columns() {
        $sortable_columns = array(
            'booking_date'     => array('date',true),     //true means its sorted by default
            'booking_price'     => array('price',false),     //true means its sorted by default
            'booking_bookee'     => array('bookee',false),     //true means its sorted by default
        );
        return  $sortable_columns;
    }


    /**
     * Returns array of views ( e.g. 'all', 'confirmed', 'pending' )
     */
	function get_views(){
		//TODO include count
		$occurrence_id =( !empty( $_REQUEST['occurrence_id'] )  ? intval($_REQUEST['occurrence_id']) : 0);
		$event_id =( !empty( $_REQUEST['event_id'] )  ? intval($_REQUEST['event_id']) : 0);
		$bookee_id =( !empty( $_REQUEST['bookee_id'] )  ? intval($_REQUEST['bookee_id']) : 0);
		$status =( !empty( $_REQUEST['status'] )  ? $_REQUEST['status'] : '');
		
		$num_bookings = wp_count_posts( 'eo_booking', 'readable' );
		
		//Reset query args
		$query_args = array( 'paged' => false, 'emailed' => false, 'cancelled' => false );
		
		$all = ( empty($event_id) && empty($occurrence_id) && empty($bookee_id) && empty($status) ? 'current' : '');
		$views = array(
			'all'=>sprintf('<a href="%s"  class="%s"> %s </a>', admin_url('edit.php?post_type=event&page=bookings'), $all,__('All','eventorganiserp')),
		);
		
		$stati = eo_get_booking_statuses();
		foreach( $stati as $status_id => $status_obj ){
			
			if( !$status_obj->show_in_admin_status_list )
				continue;
			
			if( empty( $num_bookings->$status_id ) )
				continue;
			
			$query_args['status'] = $status_id;
			$views[$status_id] = sprintf( '<a href="%s"  class="%s"> %s </a>', 
				add_query_arg( $query_args ), 
				( $status_id == $status ? 'current' : ''),
				sprintf( translate_nooped_plural( $status_obj->label_count, $num_bookings->$status_id ), number_format_i18n( $num_bookings->$status_id ) )
			);
			
		}
		
		return $views;
	}

	/**
	 * Displays links to the views ( e.g. 'all', 'confirmed', 'pending', .. )
	 */
	function views() {
		$screen = get_current_screen();

		$views = $this->get_views();
		$views = apply_filters( 'views_' . $screen->id, $views );

		if ( empty( $views ) )
			return;

		echo "<ul class='subsubsub'>\n";
		foreach ( $views as $class => $view ) {
			$views[ $class ] = "\t<li class='$class'>$view";
		}
		echo implode( " |</li>\n", $views ) . "</li>\n";
		echo "</ul>";
	}

     /*
     * Echos the row, after assigning it an ID based ont eh venue being shown. Assign appropriate class to alternate rows.
     */       
	function single_row( $item ) {
		static $row_class = '';
		$row_id = 'id="booking-'.$item->ID.'"';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );
		echo '<tr' .$row_class.' '.$row_id.'>' . $this->single_row_columns( $item ) . '</tr>';
	}

	function extra_tablenav( $which = '' ) { 
		$occurrence_id =( !empty( $_REQUEST['occurrence_id'] )  ? intval($_REQUEST['occurrence_id']) : 0);
		$event_id =( !empty( $_REQUEST['event_id'] )  ? intval($_REQUEST['event_id']) : 0);
		
		$status =( !empty( $_REQUEST['status'] )  ? $_REQUEST['status'] : false );
		
		if( 'cancelled' !== $status ){
		
			printf('<div class="alignleft actions" style="line-height: 200%%;">
					<a id="download-bookings-trigger" class="button" href="%s"> %s </a>
				</div>',
				add_query_arg('eo-action','export-bookings'),
				__('Download bookings','eventorganiserp')
			);

			printf('<div class="alignleft actions" style="line-height: 200%%;">
					<a class="button" href="%s"> %s </a>
				</div>',
				add_query_arg('eo-action','export-tickets'),
				__('Download tickets','eventorganiserp')
			);
			
		}else{
			
			printf(
				'<div class="alignleft actions" style="line-height: 200%%;"><a id="empty-cancelled-bookings" class="button" href="%s"> %s </a></div>',
				add_query_arg('action','delete-cancelled'),
				__('Delete cancelled bookings','eventorganiserp')
			);
		}
		
		
		

	}

    /*
     * Prepare venues for display
     * 
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     */
    function prepare_items() {

        //Retrieve page number for pagination
       $current_page = (int) $this->get_pagenum();
		$per_page = $this->get_per_page();

		//Get the columns, the hidden columns an sortable columns
		$columns = $this->setup_columns();

		$orderby =( !empty( $_REQUEST['orderby'] )  ? trim( stripslashes($_REQUEST['orderby'])) : '');
		$order =( !empty( $_REQUEST['order'] )  ? trim( stripslashes($_REQUEST['order'])) : '');
		$occurrence_id =( !empty( $_REQUEST['occurrence_id'] )  ? intval($_REQUEST['occurrence_id']) : 0);
		$event_id =( !empty( $_REQUEST['event_id'] )  ? intval($_REQUEST['event_id']) : 0);
		$bookee_id =( !empty( $_REQUEST['bookee_id'] )  ? intval($_REQUEST['bookee_id']) : 0);
		$status =( !empty( $_REQUEST['status'] )  ? $_REQUEST['status'] :  eo_get_booking_statuses( array( 'show_in_admin_all_list' => true ), 'names', 'and' ) );
		$search = ( !empty( $_REQUEST['search'] )  ? $_REQUEST['search'] : '');

		$args = array(
			'bookee_id'=>$bookee_id,
			'occurrence_id'=>$occurrence_id,
			'event_id'=>$event_id,
			'orderby'=>$orderby ,
			'order'=>$order,
			'search' => $search,
			'status'=>$status,
			'numberposts'=>$per_page,
			'offset'=>$per_page*($current_page-1),
		);

		$bookings = eventorganiser_get_bookings($args, true);

		$args['fields']='count_attending';
		$args['update_ticket_cache']=false;
		$attending = eventorganiser_get_bookings($args);

		$this->items = $bookings->posts;

		$this->set_pagination_args( array(
			'total_items' => $bookings->found_posts,
			'per_page' => $per_page,
			'attending' => $attending,
		) );     
		$this->_args['attending'] = intval($attending);
    }


	function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		extract( $this->_pagination_args );

		$output = '<span class="displaying-num">';
		if( $attending == 0 ){
			$output .= __( 'No confirmed attendees', 'eventorganiserp' );
		}else{
			$output .= sprintf( _n( '%s confirmed attendee', '%s confirmed attendees', $attending, 'eventorganiserp' ), number_format_i18n( $attending ) );
		}
		$output .= '</span>';
		
		$output .= '<span class="displaying-num">&#124;</span>';
		
		$output .= '<span class="displaying-num">';
		if( $total_items == 0 ){
			$output .= __( 'No bookings', 'eventorganiserp' );
		}else{
			$output .= sprintf( _n( '%s booking', '%s bookings', $total_items, 'eventorganiserp' ), number_format_i18n( $total_items ) );
		}
		$output .= '</span>';

		$current = $this->get_pagenum();

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

		$page_links = array();

		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			'&laquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='%s' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				esc_attr( 'paged' ),
				$current,
				strlen( $total_pages )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
			'&raquo;'
		);

		$output .= "\n<span class='pagination-links'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

    /*
     * Set bulk actions
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     */
    function get_bulk_actions() {
    	$status =( !empty( $_REQUEST['status'] )  ? $_REQUEST['status'] : false );
    	
    	if( 'cancelled' !== $status ){
    		$actions = array(
            	'cancel'    => __( 'Cancel Bookings','eventorganiserp' ),
            	'email'    => __( 'Email Bookees','eventorganiserp' ),
				'confirm' => __( 'Confirm bookings', 'eventorganiserp' )
        	);
    	}else{
    		$actions = array(
            	'uncancel'    => __( 'Restore Bookings','eventorganiserp' ),
            	'delete'    => __( 'Delete Bookings','eventorganiserp' ),
        	);
    	}
    	
        return $actions;
    }
	function get_per_page(){
		$screen = get_current_screen();
		$per_page = (get_user_option($screen->id.'_per_page') ?  (int) get_user_option($screen->id.'_per_page') : 20);
		return $per_page;
	}
	function get_post_id(){
		return ( !empty( $_REQUEST['post_id'] )  ? intval($_REQUEST['post_id']) : 0);
	}

	/**
	 * Display the search box.
	 * @param string $text The search button text
	 * @param string $input_id The search input id
	 */
	function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['search'] ) && !$this->has_items() )
			return;
	
		$input_id = $input_id . '-search-input';
		$search = ! empty( $_REQUEST['search'] ) ? esc_attr( $_REQUEST['search'] ) : '';
		
		if ( ! empty( $_REQUEST['orderby'] ) )
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		if ( ! empty( $_REQUEST['order'] ) )
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		if ( ! empty( $_REQUEST['occurrence_id'] ) )
			echo '<input type="hidden" name="occurrence_id" value="' . esc_attr( $_REQUEST['occurrence_id'] ) . '" />';
		if ( ! empty( $_REQUEST['event_id'] ) )
			echo '<input type="hidden" name="event_id" value="' . esc_attr( $_REQUEST['event_id'] ) . '" />';
		if ( ! empty( $_REQUEST['status'] ) )
			echo '<input type="hidden" name="status" value="' . esc_attr( $_REQUEST['status'] ) . '" />';
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo $input_id ?>" name="search" value="<?php echo $search; ?>" placeholder ="e.g. #123 / j.smith@example.com / jsmith" />
			<?php submit_button( $text, 'button', false, false, array( 'id' => 'search-submit') ); ?>
		</p>
		<?php
	}
		
	function setup_columns(){
		$columns = get_column_headers('event_page_bookings');
		$hidden = get_hidden_columns('event_page_bookings');
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
	}


	/**
	 * Outputs a hidden table that is used for 'bulk email'
	 */
	function inline_edit() {

		$screen = get_current_screen();
		?>
		<form method="get" action="<?php echo add_query_arg('action',''); ?>">
			<table style="display: none">
				<tbody id="bulk-download-">
					<tr id="bulk-download" class="inline-edit-row inline-edit-row-bookees bulk-edit-row bulk-edit-row-post" style="display: none">
						<?php  
							$params = $_GET;
							$params['eo-action'] = 'export-bookings';
							foreach( $params as $key => $value ){
								printf( '<input type="hidden" name="%s" value="%s">', $key, $value );
							}
						?>
						<td>
						
							<fieldset class="inline-edit-col-left">
								<h4> <?php esc_html_e( '1. Select custom fields', 'eventorganiserp' ); ?> </h4>
								<span class="description"> <?php esc_html_e( 'Select booking fields to be included in export', 'eventorganiserp' );?> </span>
								<?php echo eventorganiser_booking_meta_multiselect(); ?>
							</fieldset>
							
							<fieldset class="inline-edit-col-left" style="">
							
								<h4> <?php esc_html_e( '2. CSV Export Settings', 'eventorganiserp' ); ?> </h4>
								<table class="eo-booking-export-settings">
									<?php /*
									<tr>
										<td><label> File-name </label> </td>
										<td><input type="text" value="test">.csv</td>
									</tr>
									*/?>
									<tr>
										<td><label> <?php esc_html_e( 'Cell delimiter ', 'eventorganiserp' ); ?></label></td>  
										<td><select name="delimiter"> 
											<option value="comma">,</option>
											<option value="\t">tab</option>
											<option value=" ">space</option> 
										</select></td>
									</tr>
									<tr>
										<td><label> <?php esc_html_e( 'Text delimiter ', 'eventorganiserp' ); ?></label></td> 
										<td><select name="text_delimiter"> 
											<option value='"'>"</option>
											<option value="'">'</option> 
										</select></td>
									</tr>
								</table>
								</div>
							</fieldset>
							
							<fieldset class="inline-edit-col-left" style="">
								<h4> <?php esc_html_e( '3. Download', 'eventorganiserp' ); ?> </h4>
								<p class="submit inline-edit-save">
									<a accesskey="c" href="#inline-edit" title="<?php esc_attr_e( 'Cancel' ); ?>" class="button-secondary cancel alignleft"><?php _e( 'Cancel' ); ?></a>
									<?php submit_button( __( 'Download' ), 'button-primary alignright', 'bulk_email', false, array( 'accesskey' => 's' ) ); ?>
									<br class="clear" />
								</p>
								</div>
							</fieldset>
							
						</td>
					</tr>
				</tbody>
			</table>
		</form>		

		<form method="get" action="">
			<table style="display: none">
				<tbody id="inlineedit">
					<tr id="bulk-email" class="inline-edit-row inline-edit-row-bookees bulk-edit-row bulk-edit-row-post" style="display: none">
						<td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">

						<fieldset class="inline-edit-col-left">
							<div class="inline-edit-col">
								<h4><?php _e( 'Email Bookees' ); ?></h4>
								<div id="bulk-email-title-div"><div id="bulk-email-titles"></div></div>
							</div>
						</fieldset>

						<fieldset class="inline-edit-col-right">
							<div class="inline-edit-col">
								<div class="inline-edit-group">
									<label style="max-width:100%;"><span class="title"><?php _e( 'Subject' ,'eventorganiserp'); ?></span>
										<input type="text" name="eventorganiser[subject]" />
									</label>
									<label style="max-width:100%;"><span class="title"><?php _e( 'Message','eventorganiserp'); ?></span>
										<?php
											eventorganiser_inline_help(
											 __(  'E-mailing Bookees', 'eventorganiserp' ), 
											__( 'In the e-mail subject & body you can use the following tags', 'eventorganiserp' )
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
											.'<p>'.sprintf(
												__( '<strong>Note:</strong> your hosting provider may limit the number of e-mails you can send in a 24 hour period. Plug-ins are available that <a href="%s" target="_blank">work around these restrictions.</a>', 'eventorganiserp' ),
												'http://wp-event-organiser.com/pro-features/e-mailing-bookees/'
												).'</p>',
											true
										 ); ?>
										<textarea style="height:10em;" name="eventorganiser[email_message]"> </textarea>
									</label>
								</div>
							</div>
						</fieldset>

						<p class="submit inline-edit-save">
							<a accesskey="c" href="#inline-edit" title="<?php esc_attr_e( 'Cancel' ); ?>" class="button-secondary cancel alignleft"><?php _e( 'Cancel' ); ?></a>
							<?php submit_button( __( 'Email' ), 'button-primary alignright', 'bulk_email', false, array( 'accesskey' => 's' ) ); ?>
							<?php  wp_nonce_field( 'eventorganiser_bulk_email', '_eononce');?>
							<input type="hidden" name="screen" value="<?php echo esc_attr( $screen->id ); ?>" />
							<span class="error" style="display:none"></span>
							<br class="clear" />
						</p>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
<?php
	}
    
} 

function eventorganiser_booking_meta_multiselect(){
	
	$html = '<select multiple id="eo-meta-labels" name="meta[]">';
	
	$forms = get_posts( array( 'post_type' => 'eo_booking_form', 'numberposts' => -1 ) );
	
	if( $forms ){
		foreach( $forms as $form ){
			
			$_form = EO_Booking_Form_Controller::get_form( $form->ID );
			
			$html .= sprintf( '<optgroup label="%s">', $form->post_name );
			$elements = $_form->get_elements();
			$ignore = array( 'ticketpicker', 'fieldset', 'section', 'html', 'antispam', 'hook' );
			
			foreach( $elements as $element ){
				
				if( !$element->get('label') || in_array( $element->get_type(), $ignore ) )
					continue;
				
				$html .= sprintf( 
							'<option value="%s" data-eo-booking-field-type="%s" ">%s</option>', 
							esc_attr( $form->ID.'-form-'.$element->id ),
							esc_attr( $element->get_type() ), 
							esc_attr( $element->get( 'label' ) ) 
						);
			}
			$html .= '</optgroup>';
		}
		$html .= '</select>';
	}
	
	return $html;
}
?>