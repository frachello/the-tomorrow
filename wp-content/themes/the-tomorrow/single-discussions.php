<?php get_header(); ?>
<!-- content -->
<div id="content">

<!-- main col -->
<section id="main_content">

<div id="post_col">

    <?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

		<?php $cur_id = get_the_id(); ?>
		<?php $cur_title = get_the_title(); ?>
		<?php $cur_post_type = get_post_type(); ?>

		<h2 class="post_<?php echo $cur_id; ?>"><?php echo $cur_title; ?></h2>
		
		<?php endwhile; ?>

	        <div class="pagination"> 
	            <span class="prev"><?php next_posts_link('precedenti') ?></span>
	            <span class="next"><?php previous_posts_link('successivi') ?></span> 
	        </div>

	    <?php else : ?>

	        <h2 class="page-title">Non ci sono post, spiacente.</h2>

	    <?php endif; ?>

<?php

$args = array(
   'post_type' => 'letters',
   'tax_query' => array (
      array (
         'taxonomy' => $cur_post_type,
         'field' => 'ID',
         'terms' => $cur_id,
         'operator' => 'IN'
      )
   )
);
query_posts($args);

if ( have_posts() ) : ?>

   <?php while ( have_posts() ) : the_post(); ?>

		<div class="post" id="post-<?php echo $cur_id; ?>">

			<h2 class="page-title"><?php the_title() ?></h2>
			<div class="postMetaTop">
				<span class="date"><?php the_time('j F Y') ?></span>
				<!-- in <span class="category"> <?php the_category(', '); ?></span> -->
			</div>
			
			<div class="entry">
			  <?php the_content('[leggi tutto]'); ?>
			</div>
			
			<!-- commenti -->
		
		</div> 

   <?php endwhile; ?>

<?php endif; ?>

</div>
<!-- chiusa post_col -->	

</section>
<!-- chiusa section#main_content -->	

<!-- sidebar -->
<?php get_sidebar(); ?>	

</div> <!-- chiuso content -->
<br class="clear" />

<?php get_footer(); ?>

