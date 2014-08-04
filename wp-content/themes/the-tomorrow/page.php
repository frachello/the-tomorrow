<?php get_header(); ?>
<!-- content -->
<div id="content">

<!-- main col -->
<section id="main_content">

<div id="page_col">


<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

		<div class="post">

			<h2 class="page-title"><?php the_title(); ?></h2>

			<div class="entry">
				<?php the_content('leggi tutto'); ?>
			</div>
			
			<br class="clear" />
			<!-- commenti -->
			
		</div>
		
		

		<?php endwhile; ?>

	<?php else : ?>

		<h2>Errore.</h2>
		<p>Spiacenti, ma la pagina che stai cercando non esite</p>

	<?php endif; ?>

</section>
<!-- chiusa section#main_content -->	

</div> <!-- chiuso content -->

<br class="clear" />

<?php get_footer(); ?>
