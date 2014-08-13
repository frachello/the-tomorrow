<?php
/**
 * Event Search: Standard List
 *
 * The template is used for displaying the results of the event search [event_search] shortcode
 *
 * For a documentation of available functions (outputting dates, venue details etc) see http://wp-event-organiser.com/documentation/function-reference/
 *
 * **************** NOTICE: *****************
 *  Do not make changes to this file. Any changes made to this file
 * will be overwritten if the plug-in is updated.
 *
 * To overwrite this template with your own, make a copy of it (with the same name)
 * in your theme directory. See http://wp-event-organiser.com/documentation/editing-the-templates/ for more information
 *
 * WordPress will automatically prioritise the template in your theme directory.
 * **************** NOTICE: *****************
 *
 * @package Event Organiser Pro (plug-in)
 * @since 1.0
 */
global $eo_event_loop;

//Date % Time format for events
$date_format = get_option( 'date_format' );
$time_format = get_option( 'time_format' );
?>

 <?php if ( $eo_event_loop->have_posts() ): ?>

 	<?php if ( $eo_event_loop->max_num_pages > 1 ) :
		//See http://codex.wordpress.org/Function_Reference/paginate_links
		$big = 999999999; // need an unlikely integer
		echo paginate_links( array(
			'base' => str_replace( $big, '%#%', get_pagenum_link( $big ) ),
			'current' => max( 1, get_query_var( 'paged' ) ),
			'total' => $eo_event_loop->max_num_pages
		) );
	endif; ?>

	<?php while ( $eo_event_loop->have_posts() ): $eo_event_loop->the_post(); ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<h1 class="entry-title" style="display: inline;">
					<a href="<?php the_permalink(); ?>">
					<?php
					//If it has one, display the thumbnail
					if ( has_post_thumbnail() )
						the_post_thumbnail( 'thumbnail', array( 'style'=>'float:left;margin-right:20px;' ) );

					//Display the title
						the_title();
					?>
					</a>
				</h1>

				<div class="event-entry-meta">

					<!-- Output the date of the occurrence-->
					<?php
					//Format date/time according to whether its an all day event.
					//Use microdata http://support.google.com/webmasters/bin/answer.py?hl=en&answer=176035
					if ( eo_is_all_day() ) {
						$format = 'd F Y';
						$microformat = 'Y-m-d';
					}else {
						$format = 'd F Y '.get_option( 'time_format' );
						$microformat = 'c';
					}?>
					<time itemprop="startDate" datetime="<?php eo_the_start( $microformat ); ?>"><?php eo_the_start( $format ); ?></time>

					<!-- Display event meta list -->
					<?php echo eo_get_event_meta_list(); ?>

					<!-- Event excerpt -->
					<?php the_excerpt(); ?>

				</div><!-- .event-entry-meta -->

				<div style="clear:both;"></div>
			</header><!-- .entry-header -->

		</article><!-- #post-<?php the_ID(); ?> -->

	<?php endwhile; ?>
	
	<?php if ( $eo_event_loop->max_num_pages > 1 ) :
		//See http://codex.wordpress.org/Function_Reference/paginate_links
		$big = 999999999; // need an unlikely integer
		echo paginate_links( array(
			'base' => str_replace( $big, '%#%', get_pagenum_link( $big ) ),
			'current' => max( 1, get_query_var( 'paged' ) ),
			'total' => $eo_event_loop->max_num_pages
		) );
	endif; ?>
	
<?php elseif ( ! empty( $eo_event_loop_args['no_events'] ) ): ?>

	<ul id="<?php echo esc_attr( $id );?>" class="<?php echo esc_attr( $classes );?>" >
		<li class="eo-no-events" > <?php echo $eo_event_loop_args['no_events']; ?> </li>
	</ul>

<?php endif; ?>