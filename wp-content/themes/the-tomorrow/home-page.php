<?php
/*
Template Name: Home Page
*/
?>

<?php get_header(); ?>
<!-- content -->
<div id="content">

    <!-- main col -->
    <section id="main_content">

		<div id="post_col">

		<?php query_posts(); ?>	 
		<?php while ( have_posts() ) : the_post(); ?>    
		    
			<div class="post" id="post-<?php the_ID(); ?>">
			<h2><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>"><?php the_title() ?></a></h2>
			
			<div class="entry">
			  <?php the_content('[leggi tutto]'); ?>
			</div>
			<br class="clear" />
			</div>

		<?php endwhile; ?>

		<div class="navigation"> 
		    <span class="previous-entries"><?php next_posts_link('&laquo; precedenti') ?></span>
		    <span class="next-entries"><?php previous_posts_link('successivi &raquo;') ?></span> 
		</div>

		</div>
		<!-- chiusa post_col -->	

	</div>
	<!-- chiusa main_content -->	

<!-- sidebar -->
<?php get_sidebar(); ?>	

</div> <!-- chiuso content -->
<br class="clear" />

<?php get_footer(); ?>
