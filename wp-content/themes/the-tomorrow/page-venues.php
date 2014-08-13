<?php
/*
Template Name: Places map
*/
?>
<!-- template file: venues-map.php -->

<?php get_header(); ?>
<!-- content 
<div id="content"> -->

		<div id="fullscreen_map">

			<?php if (have_posts()) : ?><?php while (have_posts()) : the_post(); ?>

					<!-- <h2 class="page-title"><?php the_title(); ?></h2> -->

					<div class="entry-map">

						<?php
							$city = $_GET['city'];
							$venue_query = array(
							    array(
							       'key' => '_city',
							       'value' => $city,
							    )
							 );
							$venues = eo_get_venues( array( 'meta_query' => $venue_query ) );

							foreach ($venues as $key => $object) {
								$venues_slugs_arr[] = $object->slug;
							}
							$venues_slugs_list = implode("<br />", $venues_slugs_arr);
							// echo $city.' - '.$venues_slugs_list ;
							// http://docs.wp-event-organiser.com/shortcodes/venue-map/
							echo eo_get_venue_map($venues_slugs_arr,array('width'=>'100%'));
							
						?>
				</div>			

				<?php endwhile; ?>

			<?php else : ?>

				<h2>Errore.</h2>
				<p>Spiacenti, ma la pagina che stai cercando non esite</p>

			<?php endif; ?>	

		</div> <!-- close fullscreen map -->

 <!-- </div> chiuso content -->

<?php get_footer(); ?>
