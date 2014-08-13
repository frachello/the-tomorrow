<?php
/**
 * Events user is attending widget template
 *
 ***************** NOTICE: *****************
 *  Do not make changes to this file. Any changes made to this file
 * will be overwritten if the plug-in is updated.
 *
 * To overwrite this template with your own, make a copy of it (with the same name)
 * in your theme directory. See http://wp-event-organiser.com/documentation/editing-the-templates/ for more information
 *
 * WordPress will automatically prioritise the template in your theme directory.
 ***************** NOTICE: *****************
 *
 * @package Event Organiser Pro
 * @since 1.2
 */
global $eo_event_loop;

//Date % Time format for events
$date_format = get_option('date_format');
$time_format = get_option('time_format');

?>

<ul class="eo-user-attending-widget" > 

	<?php if( $eo_event_loop->have_posts() ): ?>
	
		<?php while( $eo_event_loop->have_posts() ): $eo_event_loop->the_post(); ?>

			<?php 
				//Generate HTML classes for this event
				$eo_event_classes = eo_get_event_classes(); 

				//For non-all-day events, include time format
				$format = ( eo_is_all_day() ? $date_format : $date_format.' '.$time_format );
			?>

			<li class="<?php echo esc_attr(implode(' ',$eo_event_classes)); ?>" >
				<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" ><?php the_title(); ?></a> <?php echo __('on','eventorganiser') . ' '.eo_get_the_start($format); ?>
			</li>

		<?php endwhile; ?>
		
	<?php else: ?>
	 
		<li class="eo-no-events" > 
			<?php _e( 'You are not currently registered for any events', 'eventorganiserp' ); ?>
		</li>
		
	<?php endif; ?>
</ul>

