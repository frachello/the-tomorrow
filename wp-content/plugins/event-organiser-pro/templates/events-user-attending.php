<?php
global $eventorganiser_user_attending;
$events = $eventorganiser_user_attending;

if ( is_user_logged_in() && $events->have_posts() ):

	while ( $events->have_posts() ) : $events->the_post(); ?>
		
		<article id="event-<?php the_ID(); ?>-<?php echo $post->occurrence_id ;?>" <?php post_class(); ?>>

			<h1 class="entry-title" style="display: inline;">			
				<?php the_post_thumbnail('thumbnail', array('style'=>'float:left;margin-right:20px;')); ?>
				<a href="<?php the_permalink(); ?>"><?php the_title();?></a>
			</h1>

			<div class="event-entry-meta">

				<!-- Output the date of the occurrence-->
				<?php
					//Format date/time according to whether its an all day event.
					//Use microdata http://support.google.com/webmasters/bin/answer.py?hl=en&answer=176035
 					if( eo_is_all_day() ){
						$format = 'd F Y';
						$microformat = 'Y-m-d';
					}else{
						$format = 'd F Y '.get_option('time_format');
						$microformat = 'c';
					}?>
					<time itemprop="startDate" datetime="<?php eo_the_start($microformat); ?>"><?php eo_the_start($format); ?></time>

				<!-- Display event meta list -->
				<?php echo eo_get_event_meta_list(); ?>

				<!-- Event excerpt -->
				<?php the_excerpt(); ?>
			
			</div><!-- .event-entry-meta -->			
		
			<div style="clear:both;"></div>

		</article><!-- #post-<?php the_ID(); ?> -->


	<?php endwhile; ?><!--The Loop ends-->

	<?php
		$big = 999999;
		echo paginate_links( array(
			'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'  => '?paged=%#%',
			'current' => max( 1, get_query_var( 'paged' ) ),
			'total'   => $events->max_num_pages
		) );
	?>

<?php else : ?>

<?php endif; ?>

