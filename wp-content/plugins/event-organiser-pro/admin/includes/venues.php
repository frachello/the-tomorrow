<?php
/**
* Registers custom fields & thumbnail metabox foor venue admin page
* Hooked onto add_meta_boxes_event_page_venues
 *@since 1.0
 *@ignore
 *@access private
*/
 function eventorganiser_pro_venue_custom_fields_metabox(){
 	add_meta_box('postcustom',__('Custom Fields'), 'eventorganiser_venue_custom_fields_form', 'event_page_venues');
 	add_meta_box('eo-featured-image', __( 'Featured Image','eventorganiserp' ), 'eventorganiser_venue_thumbnail_meta_box', 'event_page_venues', 'side');
 }
 add_action('add_meta_boxes_event_page_venues','eventorganiser_pro_venue_custom_fields_metabox');

/**
* Saves custom field values when venue is saved
* Hooked onto {@see `eventorganiser_save_venue`}
* @ignore
 *@since 1.0
 *@access private
*/
function _eventorganiser_custom_fields_save( $venue_id ){

	$post_data= $_POST;
	
	$tax = get_taxonomy( 'event-venue');
	if ( !current_user_can( $tax->cap->edit_terms ) )
		return;

	if ( isset($post_data['eo-venue-meta']) && $post_data['eo-venue-meta'] ) {
		foreach ( $post_data['eo-venue-meta'] as $mid => $the_meta ) {
			//Find meta and check 
			if ( ! ( $meta = get_metadata_by_mid( 'eo_venue', $mid ) ) )
				continue;
			if ( $meta->eo_venue_id != $venue_id )
				continue;

			update_metadata_by_mid( 'eo_venue', $mid, stripslashes_deep( $the_meta['value'] ),stripslashes( $the_meta['key'] ) );
		}
	}

	if ( isset($post_data['delete-eo-venue-meta']) && $post_data['delete-eo-venue-meta'] ) {
		foreach ( $post_data['delete-eo-venue-meta'] as $mid => $the_meta ) {
			//Find meta and check 
			if ( ! ( $meta = get_metadata_by_mid( 'eo_venue', $mid ) ) )
				continue;
			if ( $meta->eo_venue_id != $venue_id )
				continue;

			delete_metadata_by_mid( 'eo_venue' , $mid );
		}
	}
}
add_action ('eventorganiser_save_venue','_eventorganiser_custom_fields_save');

/**
* Helper function to get all venue meta for given venue
 *@since 1.0
 *@access private
 *@ignore
 *@param int $venue_id The venue ID
 *@return array Array of venue meta, each meta is an associative array
 */
function eventorganiser_has_venuemeta( $venue_id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare("SELECT meta_key, meta_value, meta_id, eo_venue_id
			FROM $wpdb->eo_venuemeta WHERE eo_venue_id = %d
			ORDER BY meta_key,meta_id", $venue_id), ARRAY_A );
}

/**
* Helper function to get all venue meta keys (across all veneus)
 *@since 1.0
 *@access private
 *@ignore
 *@return array A 'natcasesort' sorted array of meta keys used, which don't start with '_'.
 */
function _eventorganiser_get_venuemeta_keys(){

	global $wpdb;
	$keys = $wpdb->get_col( "
		SELECT meta_key
		FROM {$wpdb->eo_venuemeta}
		GROUP BY meta_key
		HAVING meta_key NOT LIKE '\_%'
		ORDER BY meta_key
		LIMIT 30" );

	if ( !$keys )
		return false;

	natcasesort($keys);
	return $keys;
}


function eventorganiser_venue_custom_fields_form( $venue ) {
	wp_enqueue_script( 'eo-venue-custom-fields');
?>
	<div id="postcustomstuff">
	<div id="ajax-response"></div>
	<?php 
		$venue_id = isset($venue->term_id) ? (int) $venue->term_id : 0;
		$venuemeta = eventorganiser_has_venuemeta($venue_id);
	
		foreach ( $venuemeta as $i => $meta ) {
			if ( '_' == substr( $meta[ 'meta_key' ] , 0 ,1 ) )
				unset( $venuemeta[ $i ] );
		}
		
		//Display table of existing venue meta
		eventorganiser_list_meta($venuemeta);
		
		//Get a 'natcase'-sorted array of previously used venue meta keys
		$keys = _eventorganiser_get_venuemeta_keys();
	?>
		<p><strong><?php _e( 'Add New Custom Field:' ) ?></strong></p>
		<table id="newmeta">
		<thead>
			<tr>
				<th class="left"><label for="metakeyselect"><?php _ex( 'Name', 'meta name' ) ?></label></th>
				<th><label for="metavalue"><?php _e( 'Value' ) ?></label></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td id="newmetaleft" class="left">
				<?php if ( $keys ) { ?>
					<select id="metakeyselect" name="metakeyselect" tabindex="7">
						<option value="#NONE#"><?php _e( '&mdash; Select &mdash;' ); ?></option>
					<?php
						foreach ( $keys as $key ) {
							printf( "\n<option value='%s'> %s </option>", esc_attr( $key ), esc_html( $key ) );
						}
					?>
					</select>
					<input class="hide-if-js" type="text" id="metakeyinput" name="metakeyinput" tabindex="7" value="" />
	
					<a href="#postcustomstuff" class="hide-if-no-js" onclick="jQuery('#metakeyinput, #metakeyselect, #enternew, #cancelnew').toggle();return false;">
						<span id="enternew"><?php _e('Enter new'); ?></span>
						<span id="cancelnew" class="hidden"><?php _e('Cancel'); ?></span>
					</a>
				<?php } else { ?>
					<input type="text" id="metakeyinput" name="metakeyinput" tabindex="7" value="" />
				<?php } ?>
				</td>
				<td><textarea id="metavalue" name="metavalue" rows="2" cols="25" tabindex="8"></textarea></td>
			</tr>
			<tr>
				<td colspan="2" class="submit">
					<?php submit_button( __( 'Add Custom Field' ), 'add:the-list:newmeta', 'addmeta', false, array( 'id' => 'newmeta-submit', 'tabindex' => '9','data-wp-lists'=>"add:the-list:newmeta" ) ); ?>
					<?php wp_nonce_field( 'add-eo-venue-meta', '_ajax_nonce', false ); ?>
				</td>
			</tr>
		
		</tbody>
		</table>	
	</div>
	<?php 
}


/**
 * Helper function that creates a table of pre-existing venue meta (given by $meta).
 * @uses _eventorganiser_list_venuemeta_row
 * @since 1.0
 * @ignore
 * @param array $meta Two dimensional array, the inner arrays have keys 'meta_value, 'meta_key', 'meta_id' 
 */
function eventorganiser_list_meta( $meta ) {
	// Exit if no meta
	
	if ( ! $meta ) {?>
		<table id="list-table" style="display: none;">
			<thead>
				<tr>
					<th class="left"> <?php _e( 'Name', 'eventorganiserp' ); ?> '</th>
					<th><?php _e( 'Value' ); ?></th>
				</tr>
			</thead>

			<tbody id="the-list" class="list:eo-venue-meta" data-wp-lists="list:eo-venue-meta" >
				<tr><td></td></tr>
			</tbody>
		</table> 
		<?php //TBODY needed for list-manipulation JS 
		return;
	}

	$count = 0;
?>
	<table id="list-table">
		<thead>
			<tr>
				<th class="left"><?php _ex( 'Name', 'meta name' ) ?></th>
				<th><?php _e( 'Value' ) ?></th>
			</tr>
		</thead>
		<tbody id='the-list' class='list:eo-venue-meta' data-wp-lists="list:eo-venue-meta">
		<?php
			foreach ( $meta as $entry )
				echo _eventorganiser_list_venuemeta_row( $entry, $count );
		?>
		</tbody>
	</table>
<?php
}



/**
 * Outputs a row in the venue meta table
 * @since 1.0
 * @access private
 * @ignore
 * @used-by eventorganiser_list_meta
 * @param array $entry Array with  keys 'meta_value, 'meta_key', 'meta_id'
 * @param int $count The current row number (used for alternate styling)
 * @return string HTML for the given row.
 */
function _eventorganiser_list_venuemeta_row( $entry, &$count ) {

	static $update_nonce = false;

	if ( !$update_nonce )
		$update_nonce = wp_create_nonce( 'add-eo-venue-meta' );

	if ( is_serialized( $entry['meta_value'] ) ) {
		if ( is_serialized_string( $entry['meta_value'] ) ) {
			// this is a serialized string, so we should display it
			$entry['meta_value'] = maybe_unserialize( $entry['meta_value'] );
		} else {
			// this is a serialized array/object so we should NOT display it
			return;
		}
	}

	++ $count;

	$entry['meta_key'] = esc_attr($entry['meta_key']);
	$entry['meta_value'] = esc_textarea( $entry['meta_value'] ); // using a <textarea />
	$entry['meta_id'] = (int) $entry['meta_id'];
	$delete_nonce = wp_create_nonce( 'delete-eo-venue-meta_' . $entry['meta_id'] );
	$id = $entry['meta_id'];

	$delete_class = "delete:the-list:eo-venue-meta-{$id}::_ajax_nonce={$delete_nonce} deletemeta";
	$update_class = "add:the-list:eo-venue-meta-{$id}::_ajax_nonce-add-meta={$update_nonce} updatemeta";

	$r = sprintf(
			'<tr id="eo-venue-meta-%1$d" class="%2$s">
				<td class="left">
					<label class="screen-reader-text" for="eo-venue-meta-%1$d-key"> %3$s </label>
					<input name="eo-venue-meta[%1$d][key]" id="eo-venue-meta-%1$d-key" tabindex="6" type="text" size="20" value="%4$s" />
					<div class="submit"> %5$s %6$s</div>
				</td>
				<td>
					<label class="screen-reader-text" for="eo-venue-meta-%1$d-value">  %7$s </label>
					<textarea name="eo-venue-meta[%1$d][value]" id="eo-venue-meta-%1$d-value" tabindex="6" rows="2" cols="30">%8$s</textarea>
				</td>
			</tr>',
			$entry['meta_id'],
			( $count % 2 ? 'alternate' : '' ),
			 __( 'Key' ),
			$entry['meta_key'],
			get_submit_button( __( 'Delete' ), $delete_class , "delete-eo-venue-meta[{$id}]", false, array( 'tabindex' => '6', 'data-wp-lists'=>$delete_class ) ),
			get_submit_button( __( 'Update' ), $update_class , "eo-venue-meta-{$id}-submit", false, array( 'tabindex' => '6',  'data-wp-lists'=>$update_class ) ),
			__( 'Value' ),
			$entry['meta_value']
		);
	return $r;
}

/**
 * Display venue thumbnail meta box.
 *
 * @since 1.0
 * @ignore
 * @param object Venue (term) object of the venue, or null if the venue doesn't exist yet (creating new).
*/
function eventorganiser_venue_thumbnail_meta_box( $venue ) {

	$venue_id = $venue ? (int) $venue->term_id : 0;
	$thumbnail_id = eo_get_venue_thumbnail_id( $venue_id );

	if( eventorganiser_blog_version_is_atleast( '3.5' ) ){
		wp_enqueue_media();
	}else{
		//Legacy support for pre-3.5
		wp_enqueue_script( 'media-upload' );
		add_thickbox();
	}
	wp_enqueue_script('eo-venue-featured-image');
	wp_localize_script('eo-venue-featured-image', 'eventorganiser_featured_img', array( 'id' => $thumbnail_id,'nonce' => wp_create_nonce( 'set-venue-thumbnail-'.$venue_id ) ) );
	echo _eo_venue_thumbnail_html( $thumbnail_id, $venue_id );
}

/**
 * Output HTML for the venue thumbnail meta-box.
 *
 * @since 1.0
 * @ignore
 *
 * @param int $thumbnail_id ID of the attachment used for thumbnail
 * @param int $venue_id The ID of the venue associated with the thumbnail.
 * @return string html
 */
function _eo_venue_thumbnail_html( $thumbnail_id = null, $venue_id ) {
	global $content_width, $_wp_additional_image_sizes;

	$upload_iframe_src = esc_url( get_upload_iframe_src('image', 0 ) );
	$set_thumbnail_link = '<p class="hide-if-no-js"><a title="' . esc_attr__( 'Set featured image' ) . '" href="%s" id="eventorganiser-set-venue-thumbnail">%s</a></p>';
	$content = sprintf( $set_thumbnail_link, $upload_iframe_src, esc_html__( 'Set featured image' ) );

	if ( $thumbnail_id && get_post( $thumbnail_id ) ) {
		$old_content_width = $content_width;
		$content_width = 266;
		if ( !isset( $_wp_additional_image_sizes['post-thumbnail'] ) )
			$thumbnail_html = wp_get_attachment_image( $thumbnail_id, array( $content_width, $content_width ) );
		else
			$thumbnail_html = wp_get_attachment_image( $thumbnail_id, 'post-thumbnail' );
		if ( !empty( $thumbnail_html ) ) {
			$content = sprintf( $set_thumbnail_link, $upload_iframe_src, $thumbnail_html );
			$content .= '<p class="hide-if-no-js"><a href="#" id="eventorganiser-remove-venue-thumbnail" >' . esc_html__( 'Remove featured image' ) . '</a></p>';
		}
		$content_width = $old_content_width;
	}

	return $content;
}
?>