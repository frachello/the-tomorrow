<?php
/*
Template Name: Generic page no right col
*/
?>

<?php get_header(); ?>

<!-- content -->
<div id="content">

	<div class="page-content">

		<div class="col content_col">

		<?php if (have_posts()) : ?>

			<?php while (have_posts()) : the_post(); ?>

				<h2 class="page-title"><?php the_title(); ?></h2>

				<article id="post-<?php echo $post->ID; ?>" class="post">

					<div class="entry">
						<?php the_content('leggi tutto'); ?>
					</div>

				</article>
				
				<br class="clear" />
				<!-- commenti -->

			<?php endwhile; ?>

		<?php else : ?>

			<h2>Errore.</h2>
			<p>Spiacenti, ma la pagina che stai cercando non esite</p>

		<?php endif; ?>

		</div>

	</div>

	<br class="clear" />

</div> <!-- chiuso content -->

<?php get_footer(); ?>
