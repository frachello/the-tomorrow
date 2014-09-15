<?php
/*
Template Name: Generic single post
*/
?>

<?php get_header(); ?>


<!-- content -->
<div id="content">

	<div class="page-content">

		<div id="rightcol" class="col">
			
			<div class="follow_col">
				<p>follow us</p>
			    <div class="follow_ico">		    

				    <a class="follow_facebook" title="Facebook" href="#">
				    	Follow on Facebook</a>

				    <a class="follow_twitter" title="Tweet" href="#">
						Follow on Twitter</a>

				    <a class="follow_email" title="YouTube" href="#">
				    	Follow on YouTube</a>

			    </div>
			</div>
			<div class="li contact_us"><a href="<?php bloginfo('url'); ?>/contacts/">contact us</a></div>
			<div class="li credits"><a href="<?php bloginfo('url'); ?>/credits/">credits</a></div>

		</div>

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

			<h2 class="page-title">Errore.</h2>
			<p>Spiacenti, ma la pagina che stai cercando non esite</p>

		<?php endif; ?>

		</div>

	</div>

	<br class="clear" />

</div> <!-- chiuso content -->

<?php get_footer(); ?>
