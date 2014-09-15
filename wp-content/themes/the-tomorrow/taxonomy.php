<?php
/*
Template Name: Taxonomy
*/
?>

<?php get_header(); ?>
<!-- content -->
<div id="content">

	<div class="page-content">

	<?php

	
	//	$current_tax = get_taxonomy( get_query_var( 'discussione' ) );
	//	$current_tax_name = $current_tax->labels->name;
	//	$current_tax_slug = $current_tax->labels->slug;

		$current_tax = get_query_var('discussione');
	?>

	<?php if (have_posts()) : ?>

		<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post(); ?>
			<?php $post_slug = $slug = basename(get_permalink()); ?>
			<div class="post" id="<?php echo $post_slug; ?>">
			<h2 class="page-title"><?php the_title() ?></h2>
			<!--
			<p class="postMetaTop">
				<span class="autor">Scritto da  <strong><?php the_author(); ?></strong> </span>
				<span class="date">il <?php the_time('j F Y') ?></span> in
				<span class="category"> <?php the_category(', '); ?></span>
			</p>
			-->
			<div class="entry">
			  <?php the_content('[leggi tutto]'); ?>
			</div>
			<!--
			<p class="postMeta">
			<span class="tag">tag: <?php the_tags(' ',', '); ?></span> 
			<span class="comments"> <?php comments_popup_link('0 commenti', '1 commento', '% commenti'); ?></span>
			</p>
			-->
			<br class="clear" />
			</div>
		
		<?php endwhile; ?>
    
        <?php else : ?>
    
            <h5>Non ci sono post, spiacente.</h5>
    
        <?php endif; ?>

	<?php else :

		if ( is_category() ) { // If this is a category archive
			printf("<h5 class='center'>Spiacenti, non ci sono post nella categoria %s.</h5>", single_cat_title('',false));
		} else if ( is_date() ) { // If this is a date archive
			echo("<h5>Spiacenti, non ci sono post per questa data</h5>");
		} else if ( is_author() ) { // If this is a category archive
			$userdata = get_userdatabylogin(get_query_var('author_name'));
			printf("<h5 class='center'>Spiacenti, non ci sono ancora post di %s.</h5>", $userdata->display_name);
		} else {
			echo("<h5 class='center'>Nessun post trovato.</h5>");
		}
		get_search_form();

	endif;
?>



</div> <!-- chiudo page_col -->
 
</section>
<!-- chiusa section#main_content -->	

</div> <!-- chiuso content -->
<br class="clear" />

<?php get_footer(); ?>
