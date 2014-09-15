<?php
/**
 * The template is used for displaying a single event details.
 *
 * You can use this to edit how the details re displayed on your site. (see notice below).
 *
 * Or you can edit the entire single event template by creating a single-event.php template
 * in your theme.
 *
 * For a list of available functions (outputting dates, venue details etc) see http://codex.wp-event-organiser.com
 *
 ***************** NOTICE: *****************
 *  Do not make changes to this file. Any changes made to this file
 * will be overwritten if the plug-in is updated.
 *
 * To overwrite this template with your own, make a copy of it (with the same name)
 * in your theme directory. See http://docs.wp-event-organiser.com/theme-integration for more information
 *
 * WordPress will automatically prioritise the template in your theme directory.
 ***************** NOTICE: *****************
 *
 * @package Event Organiser (plug-in)
 * @since 1.7
 */
?>


	<?php
		$venue_id = eo_get_venue();
		$venue_name = eo_get_venue_name();
		$venue_url = eo_get_venue_link();
		$address_details = eo_get_venue_address($venue_id);
	?>

	<div class="col col_2 left">

		<div class="top" style=" border-top: 8px solid <?php echo eo_get_event_color(); ?>; " data-color="<?php echo eo_get_event_color(); ?>">

			<?php if( eo_is_all_day() ){ // Choose a different date format depending on whether we want to include time
				$date_format = 'j F Y'; 
			}else{
				$date_format = 'j F Y ' . get_option('time_format'); 
			} ?>
			<?php // Is event recurring or a single event ?>
			<?php // if( eo_reoccurs() ):?>
				<?php // Event reoccurs - is there a next occurrence? ?>
				<?php $next =   eo_get_next_occurrence($date_format);?>

				<?php if($next): ?>
					<?php // If the event is occurring again in the future, display the date ?>
					<?php
						$day = eo_get_schedule_start('d');
						$ordinal = eo_get_schedule_start('S');
						$month = eo_get_schedule_start('M');
					?>
					<p class="date">
						<span class="day"><?php echo $day; ?></span>
						<span class="ordinal-month"><?php echo $ordinal; ?>, <?php echo $month; ?></span>
					</p>
				<?php else: ?>
					<?php // Otherwise the event has finished (no more occurrences) ?>
					<?php printf('<p class="date">'.__('This event finished on %s').'.</p>', eo_get_schedule_last('d F Y',''));?>
				<?php endif; ?>
			<?php // endif; ?>

			<?php $categories_list = get_the_term_list( $post->ID, 'event-category', '', ', ',''); ?>
			<p class="cat" data-color="<?php echo eo_get_event_color(); ?>">
				<?php echo $categories_list; ?>
			</p>

		</div> <!-- /close .top -->

		<?php include (TEMPLATEPATH . '/inc/addthis-rightcol.inc.php'); ?>	
		
		<p class="title venue_<?php echo $venue_id; ?>">
			<strong><a href="<?php echo $venue_url; ?>"><?php echo $venue_name ?></a></strong> <br />
			<a href="<?php the_permalink() ?>" title="<?php the_title(); ?>"><?php the_title() ?></a>
		</p>

		<p class="address"><?php echo $address_details['state']; ?>, <?php echo $address_details['country']; ?></p>

	</div>

	<div class="col col_2 right">
	
		<!-- Does the event have a venue? -->
		<?php if( eo_get_venue() ): ?>
			<div class="eo-event-venue-map">
				<?php echo eo_get_venue_map(eo_get_venue(),array('width'=>'100%','height'=>'100%')); ?>
			</div>
		<?php endif; ?>

	</div>