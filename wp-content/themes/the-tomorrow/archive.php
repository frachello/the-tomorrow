<?php
/*
Template Name: Archivio
*/
?>
<!-- template file: archive.php -->
<?php get_header(); ?>
<!-- content -->
<div id="content">

<!-- main col -->
<section id="main_content">

	<?php if (have_posts()) : ?>

 	  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
 	  <?php /* If this is a category archive */ if (is_category()) { ?>
		<h2 class="page-title">Archivio per la categoria <?php single_cat_title(); ?></h2>
 	  <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
		<h2 class="page-title">Post taggati <?php single_tag_title(); ?></h2>
 	  <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
		<h2 class="page-title">Archivio di <?php the_time('j F Y'); ?></h2>
 	  <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		<h2 class="page-title">Archivio di <?php the_time('F, Y'); ?></h2>
 	  <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		<h2 class="page-title">Archivio di <?php the_time('Y'); ?></h2>
	  <?php /* If this is an author archive */ } elseif (is_author()) { ?>
		<h2 class="page-title">Archivio autori</h2>
 	  <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
		<h2 class="page-title">Archivi del blog</h2>
 	  <?php } ?>

		<!-- template file: archive.php -->
	
		<?php if (have_posts()) : ?>

		<ul>

		<?php while (have_posts()) : the_post(); ?>

			<li class="post post-<?php the_ID(); ?>">
				<a href="<?php the_permalink() ?>" title="<?php the_title(); ?>">
					<?php the_title() ?>
				</a>
			</li>

			<!--
			<div class="postMetaTop">
			<span class="autor">Scritto da  <strong><?php the_author(); ?></strong> </span>
			<span class="date">il <?php the_time('j F Y') ?></span> in
			<span class="category"> <?php the_category(', '); ?></span>
			</div>
			<div class="entry">
			  <?php the_content('[leggi tutto]'); ?>
			</div>
			<br class="clear" />
			<div class="postMeta">
			<span class="tag">TAG: <?php the_tags(' ',', '); ?></span> 
			<span class="comments"> <?php comments_popup_link('0 commenti', '1 commento', '% commenti'); ?></span>
			</div>
			-->
		<?php endwhile; ?>

		</ul>
			<!-- <div class="navigation"> 
                <span class="previous-entries"><?php next_posts_link('precedenti') ?></span>
                <span class="next-entries"><?php previous_posts_link('successivi') ?></span> 
            </div> -->
    
        <?php else : ?>
    
            <h2>Non ci sono post, spiacente.</h2>
    
        <?php endif; ?>


	<?php else :

		if ( is_category() ) { // If this is a category archive
			printf("<h2 class='center'>Spiacenti, non ci sono post nella categoria %s.</h2>", single_cat_title('',false));
		} else if ( is_date() ) { // If this is a date archive
			echo("<h2>Spiacenti, non ci sono post per questa data</h2>");
		} else if ( is_author() ) { // If this is a category archive
			$userdata = get_userdatabylogin(get_query_var('author_name'));
			printf("<h2 class='center'>Spiacenti, non ci sono ancora post di %s.</h2>", $userdata->display_name);
		} else {
			echo("<h2 class='center'>Nessun post trovato.</h2>");
		}
		get_search_form();

	endif;
	?>


</section>
<!-- chiusa section#main_content -->	

</div> <!-- chiuso content -->

<br class="clear" />

<?php get_footer(); ?>
