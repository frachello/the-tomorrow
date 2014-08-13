<?php
/**
 * Random functions - should find a better home for these.
 *
 * @package venue-functions
 */

/**
 * Retrieve a venue's thumbnail ID.
 *
 * @since 1.0
 *
 * @param int $venue_slug_or_id Venue ID (int) or slug (string)
 * @return int The venue's thumbnail ID. False if it does not have one 
 */
function eo_get_venue_thumbnail_id( $venue_slug_or_id = null ) {
	$venue_id = ( null === $venue_slug_or_id ) ? (int) eo_get_venue() : eo_get_venue_id_by_slugorid( $venue_slug_or_id );
	return eo_get_venue_meta( $venue_id, '_eventorganiser_thumbnail_id', true );
}


/**
 * Sets a venue thumbnail.
 *
 * @since 1.0
 *
 * @param int|object $post         Post ID or object where thumbnail should be attached.
 * @param int     $thumbnail_id Thumbnail to attach.
 * @return bool True on success, false on failure.
 */
function eo_set_venue_thumbnail( $venue_slug_or_id, $thumbnail_id ) {

	$venue_id = eo_get_venue_id_by_slugorid( $venue_slug_or_id );
	$thumbnail_id = absint( $thumbnail_id );

	if ( $venue_id && $thumbnail_id && get_post( $thumbnail_id ) ) {
		if ( $thumbnail_html = wp_get_attachment_image( $thumbnail_id, 'thumbnail' ) )
			return eo_update_venue_meta( $venue_id, '_eventorganiser_thumbnail_id', $thumbnail_id );
		else
			return eo_delete_venue_meta( $venue_id, '_eventorganiser_thumbnail_id' );
	}
	return false;
}

/**
 * Removes a venue thumbnail.
 *
 * @since 1.1
 *
 * @param int $venue_id Venue ID where thumbnail should be removed from.
 * @return bool True on success, false on failure.
 */
function eo_delete_venue_thumbnail( $venue_id ) {
	return eo_delete_venue_meta( $venue_id, '_eventorganiser_thumbnail_id' );
}

/**
 * Retrieve venue thumbnail.
 * 
 * @since 1.0
 * @param int|string $venue_slug_or_id Venue ID (int) or Slug (string)
 * @param string $size Optional. Image size. Defaults to 'post-thumbnail'
 * @param string|array $attr Optional. Query string or array of attributes
 * @return string HTML mark-up for thumbnail.
 */

function eo_get_venue_thumbnail( $venue_slug_or_id = null, $size = 'post-thumbnail', $attr = '' ) {
	$venue_id = ( null === $venue_slug_or_id ) ? (int) eo_get_venue() : eo_get_venue_id_by_slugorid( $venue_slug_or_id );
	$venue_thumbnail_id = eo_get_venue_thumbnail_id( $venue_id );
	//$size = apply_filters( 'post_thumbnail_size', $size );
	if ( $venue_thumbnail_id ) {
		//do_action( 'begin_fetch_post_thumbnail_html', $post_id, $post_thumbnail_id, $size ); // for "Just In Time" filtering of all of wp_get_attachment_image()'s filters

		$html = wp_get_attachment_image( $venue_thumbnail_id, $size, false, $attr );
		//do_action( 'end_fetch_post_thumbnail_html', $post_id, $post_thumbnail_id, $size );
	} else {
		$html = '';
	}
	return $html;
}
