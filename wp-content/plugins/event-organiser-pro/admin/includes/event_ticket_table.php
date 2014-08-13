<?php
/**
 * Class used for displaying venue table and handling interations
 */

class EO_Event_Tickets{

    /**
     * Constructor.
     * 
     * @param int $post_id The event post ID for whose tickets we want to display  
     */
	function __construct( $post_id ){
		global $status, $page;
		//Set parent defaults
		$this->_args = array(
			'singular'  => 'tickets',
			'plural'    => 'tickets',
			'post_id'	=>	$post_id,
			'count'		=>	0
        );
		
		ob_start();
		include( EVENT_ORGANISER_PRO_DIR . 'admin/includes/ticket-row-view.php' );
		$this->template  = ob_get_contents();
		ob_end_clean();
		
	}

	function no_items(){
		echo __( 'No tickets', 'eventorganiserp' );
	}

	function setup_columns(){
		//Get the columns, the hidden columns an sortable columns
		$currency = eventorganiser_pro_get_option('currency','US');
		$columns = array(
			'ticket' => __( 'Ticket', 'eventorganiserp' ),
			'ticket_spaces' => __( 'Spaces', 'eventorganiserp' ),
			'ticket_price' => __( 'Price', 'eventorganiserp' ) .' ('.eventorganiser_get_currency_symbol( $currency ).')',
			//'ticket_availability' => __( 'Availablity', 'eventorganiserp' ),
			'ticket_actions'=>'',
        );

	//	if( eventorganiser_pro_get_option('book_series') )
		//	unset( $columns['ticket_occurrences'] );

		$this->_columns = $columns;
	}

    /**
     * 
     */
	function column_ticket( $item ){
		
		$i = $this->_args['count'];
		$ticket = ( isset($item['name']) ? $item['name'] : '' );
		$action = 'update';
		
		$included_ids = $item['occurrence_ids'];
		$schedule = eo_get_event_schedule($this->_args['post_id']);
		$occurrences = array_map('eo_format_datetime', $schedule['_occurrences']);
		
		$selected_dates = array_intersect_key($occurrences,array_flip($included_ids));
		$selected = esc_textarea(implode(',',$selected_dates));
		
		$mid = $item['mid'];
		
		//Return the title contents
		$html = "<input type='hidden' value='{$ticket}' name='eventorganiser_pro[ticket][{$i}][name]'/>";
		$html .= "<input type='hidden' value='{$action}' class='eo-ticket-action' name='eventorganiser_pro[ticket][{$i}][action]'/>";
		$html .= "<input type='hidden' value='{$mid}' class='eo-ticket-id' name='eventorganiser_pro[ticket][{$i}][ticket_id]'/>";
		$html .= sprintf('<textarea  style="display:none;" id="eop_ticket_selected_%1$d" name="eventorganiser_pro[ticket][%1$d][selected_dates]" >%2$s</textarea>
									<textarea style="display:none;" id="eop_ticket_deselected_%1$d" name="eventorganiser_pro[ticket][%1$d][deselected_dates]" ></textarea> ',$i,$selected);
		$html .= apply_filters( 'eventorganiser_event_ticket_inline_data', '', $item, $i );
		$html .= '<input type="hidden" value="'.$i.'" class="eo-ticket-order" name="eventorganiser_pro[ticket]['.$i.'][order]">';
		$html .= '<span class="eo-ticket-name">'.esc_html($ticket).'</span>';
		return $html;
	}
	
	/**
	 *
	 */
	function column_ticket_price( $item ){
		$i = $this->_args['count'];
		$price = ( isset( $item['price'] ) ? $item['price'] : '' );
		return "<input type='hidden' value='{$price}' name='eventorganiser_pro[ticket][{$i}][price]' size='5'/>". '<span>'.eo_format_price($price,false).'</span>';
	}
	
	/**
	 *
	 */
	function column_ticket_spaces( $item ){
		$i = $this->_args['count'];
		$spaces = ( isset($item['spaces']) ? intval($item['spaces']) : 0 );
		return sprintf('<input type="hidden" name="eventorganiser_pro[ticket][%d][spaces]" value="%d" style="width:auto;" max="9999" min="0"/>',
					$i,
					$spaces
				). '<span>'.esc_html($spaces).'</span>';
	}
	
	/**
	 *
	 */
	function column_ticket_availability( $item ){
		$i = $this->_args['count'];
		//Sets the format as php understands it, and textual.
		$format = eventorganiser_get_option( 'dateformat' );
		$from = ( $item['from'] ? $item['from']->format($format) : '' );
		$to = ( $item['to'] ? $item['to']->format($format) : '' );
		return sprintf('<span> %2$s  -  %3$s  </span>',
					$i, $from, $to
				);
	}
	
	function column_ticket_actions( $item ){
		return '<a href="#" class="button eo-edit-ticket" style="margin-right:5px;"> Edit <span class="eo-settings-toggle-arrow">&#x25BC;</span></a>
				<a href="#" class="button eo-delete-ticket"> Delete <span style="color:#b94a48;" class="eo-delete-ticket-symbol">&#10006;</a>
				<a class="hide-if-no-js eo-move-ticket-up" style="font-weight:bold;font-size: 20px;vertical-align: middle;"> &#11014; </a>
				<a class="hide-if-no-js eo-move-ticket-down" style="font-weight:bold;font-size: 20px;vertical-align: middle;"> &#11015; </a>';
	}
	
    function column_default( $item, $column_name ){
		return print_r($item,true); //Show the whole array for troubleshooting purposes		
    }


    
     /*
     * Echos the row, after assigning it an ID based ont eh venue being shown. Assign appropriate class to alternate rows.
     */       
	function single_row( $item ) {
		$row_class = 'class="eo-ticket"';
		$row_id = 'id="ticket-'.$this->_args['count'].'"';
		
		$format = eventorganiser_get_option( 'dateformat' );
		$time_format = eo_blog_is_24() ? 'H:i' : 'g:ia';
		
		$included_ids = $item['occurrence_ids'];
		$schedule = eo_get_event_schedule($this->_args['post_id']);
		$occurrences = array_map('eo_format_datetime', $schedule['_occurrences']);
		$selected_dates = array_intersect_key($occurrences,array_flip($included_ids));
		
		$tags = array(
			'%%rows%%' => $this->_args['count'],
			'%%ticket_id%%' => $item['mid'],
			'%%ticket_id%%' => $item['mid'],
			'%%name%%' => ( isset($item['name']) ? $item['name'] : '' ),
			'%%spaces%%' => ( isset($item['spaces']) ? intval($item['spaces']) : 0 ),	
			'%%price%%' => ( isset( $item['price'] ) ? $item['price'] : '' ),
			'%%to%%' => ( $item['to'] ? $item['to']->format($format) : '' ),
			'%%to_time%%' => ( $item['to'] ? $item['to']->format($time_format) : '' ),
			'%%from%%' => ( $item['from'] ? $item['from']->format($format) : '' ),
			'%%from_time%%' => ( $item['from'] ? $item['from']->format($time_format) : '' ),
			'%%selected%%' => esc_textarea(implode(',',$selected_dates)),
			'%%deselected%%' => '',
		);
		
		echo str_replace( array_keys( $tags ), array_values( $tags ), $this->template );
		
		$this->_args['count']++;
	}


		
	function extra_tablenav( $which ) {

		if( 'bottom' != $which)
			return;
		
		printf( 
			'<div class="alignleft actions"><a href="#" class="button" id="eo-add-ticket">%s<span class="eo-add-ticket-plus">&#10010;</span></a></div>',#
			__( 'Add Ticket', 'eventorganiserp' )
		);

		$i = $this->_args['tickets'];
		$post_id = (int) $this->_args['post_id'];

	}

	function display_tablenav( $which ) {
		if( 'bottom' == $which){
			echo "<div class='tablenav bottom'>";
				echo "<div class='alignleft actions'>";
					$this->extra_tablenav( $which );
				echo "</div >";
			echo "</div >";
		}
	}

    /*
     * Prepare event tickets for display
     * 
     * @uses $this->set_up_columns()
     * @uses eo_get_event_tickets
     */

    function prepare_items() {

		//Get the columns, the hidden columns an sortable columns
		$columns = $this->setup_columns();
		$post_id = $this->_args['post_id'];
		$this->items = eo_get_event_tickets($post_id);
		$this->_args['tickets'] = count($this->items);
    }


	function display() {
	  	$this->prepare_items();    
		extract( $this->_args );

		$this->display_tablenav( 'top' );
		?>
		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
			<thead>
				<tr><?php $this->print_column_headers(); ?></tr>
			</thead>
			
			<?php $this->display_rows_or_placeholder(); ?>
			
		</table>

		<?php
		$this->display_tablenav( 'bottom' );
	}


	/**
	 * Whether the table has items to display or not
	 *
	 * @since 1.0
	 * @access public
	 * @return bool
	 */
	function has_items() {
		return !empty( $this->items );
	}


	/**
	 * Print column headers, accounting for hidden and sortable columns.
	 *
	 * @since 1.0
	 * @access protected
	 *
	 * @param bool $with_id Whether to set the id attribute or not
	 */
	function print_column_headers( $with_id = true ) {
		$screen = get_current_screen();

		$columns = $this->_columns;

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( 'paged', $current_url );

		if ( isset( $_GET['orderby'] ) )
			$current_orderby = $_GET['orderby'];
		else
			$current_orderby = '';

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] )
			$current_order = 'desc';
		else
			$current_order = 'asc';

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
				. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			if ( 'cb' == $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<th scope='col' $id $class >$column_display_name</th>";
		}
	}

	
	/**
	 * Get a list of CSS classes for the <table> tag
	 *
	 * @since 1.0
	 * @access protected
	 *
	 * @return array
	 */
	function get_table_classes() {
		return array('eo-event-ticket-table eo-ticket-table',$this->_args['plural'] );
	}


	/**
	 * Generate the <tbody> part of the table
	 *
	 * @since 1.0
	 * @access protected
	 */
	function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows();
		} else {
			$columns = $this->_columns;
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . count( $columns ) . '">';
			$this->no_items();
			echo '</td></tr>';
		}
	}

	/**
	 * Generate the table rows
	 *
	 * @since 1.0
	 * @access protected
	 */
	function display_rows() {
		foreach ( $this->items as $item )
			$this->single_row( $item );
	}


	/**
	 * Generates the columns for a single row of the table
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @param object $item The current item
	 */
	function single_row_columns( $item ) {
					
		$columns = $this->_columns;

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class='$column_name column-$column_name'";

			if ( 'cb' == $column_name ) {
				echo '<th scope="row" class="check-column">';
				echo $this->column_cb( $item );
				echo '</th>';
			}
			elseif ( method_exists( $this, 'column_' . $column_name ) ) {
				echo "<td $class>";
				echo call_user_func( array( &$this, 'column_' . $column_name ), $item );
				echo "</td>";
			}
			else {
				echo "<td $class>";
				echo $this->column_default( $item, $column_name );
				echo "</td>";
			}
		}
	}
}


class EO_Booking_Tickets_Table extends EO_Event_Tickets{

    /*
     * Constructor. Set some default configs.
     */
	function __construct( $booking_id ){
		global $status, $page;
		//Set parent defaults
		$this->_args = array(
			'singular'  => 'booking-ticket',
			'plural'    => 'booking-tickets',
			'booking_id'=> $booking_id,
			'count'		=> 0,
        	);
	}
	
	function setup_columns(){
		//Get the columns, the hidden columns an sortable columns
		$currency = eventorganiser_pro_get_option('currency','US');
		$currencies = eventorganiser_get_currencies();
		$columns = array(
			'ticketref'      =>__( 'Ticket Reference', 'eventorganiserp' ),
			'ticket'         =>__( 'Ticket', 'eventorganiserp' ),
			'ticket_price'   => __( 'Price', 'eventorganiserp' ),
			'ticket_actions hide-if-no-js' => '',
        );

		$this->_columns = apply_filters( 'eventorganiser_booking_tickets_table', $columns );
	}

    /*
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text or HTML to be placed inside the column <td>
     */
	function column_ticketref( $item ){
		return $item->ticket_reference;
	}
	
	/*
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text or HTML to be placed inside the column <td>
	*/
	function column_ticket( $item ){
		return $item->ticket_name;
	}
	
	/*
	 * @param array $item A singular item (one full row's worth of data)
	* @return string Text or HTML to be placed inside the column <td>
	*/
	function column_ticket_price( $item ){
		return eo_format_price( $item->ticket_price, true );
	}
	
    function column_default( $item, $column_name ){

		if( $column_name == 'ticket_actions hide-if-no-js' ){
				return sprintf('<div class="row-actions">
									<span class="delete"><a href="%s" class="submitdelete deletion"> %s </a></span>
									<input type="checkbox" style="display:none" class="eo-delete-ticket-cb" name="eo_delete_ticket[]" value="%d">
								</div>', 
								'#', __('Remove','eventorganiserp'), $item->booking_ticket_id );
		}else{
			do_action( 'eventorganiser_booking_tickets_table_column', $column_name, $item ); 
		}
    }

     /*
     * Echos the row, after assigning it an ID based ont eh venue being shown. Assign appropriate class to alternate rows.
     */       
	function single_row( $item ) {
		static $row_class = '';
		$row_id = 'id="ticket-'.$item->booking_ticket_id.'"';
		echo '<tr '.$row_id.' >';
		echo $this->single_row_columns( $item );
		echo '</tr>';
		$this->_args['count']++;
	}

    /*
     * Prepare booing tickets for display
     * @uses eo_get_booking_tickets()1
     */
    function prepare_items() {

		//Get the columns, the hidden columns an sortable columns
		$columns = $this->setup_columns();
		$booking_id = $this->_args['booking_id'];
		$this->items = eo_get_booking_tickets( $booking_id, false );
		$this->_args['tickets'] = count($this->items);
    }

	/**
	 * Get a list of CSS classes for the <table> tag
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @return array
	 */
	function get_table_classes() {
		return array('eo-ticket-table eo-bookings-ticket-table',$this->_args['plural']);
	}

	function display_tablenav( $which ){}
	
	function display() {
		$this->prepare_items();
		extract( $this->_args );
	
		$this->display_tablenav( 'top' );
		?>
			<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
				<thead>
					<tr><?php $this->print_column_headers(); ?></tr>
				</thead>
				<tbody id="eo-ticket-list"<?php if ( $singular ) echo " class='list:$singular'"; ?>>
					<?php $this->display_rows_or_placeholder(); ?>
				</tbody>
			</table>
	
			<?php
			$this->display_tablenav( 'bottom' );
		}
		
		/**
		 * Generate the table rows
		 *
		 * @since 1.0
		 * @access protected
		 */
		function display_rows() {
			foreach ( $this->items as $item )
				$this->single_row( $item );
		}
		
		
} 
?>