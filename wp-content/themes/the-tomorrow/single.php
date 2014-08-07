<?php get_header(); ?>
<!-- content -->
<div id="content">
    
    <?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post(); ?>
			
			<div class="post" id="post-<?php the_ID(); ?>">
			<h2><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>"><?php the_title() ?></a></h2>
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
			
			
			 <?php comments_template(); ?>
			
			</div> 
		
		<?php endwhile; ?>
    
            <div class="navigation"> 
                <span class="previous-entries"><?php next_posts_link('precedenti') ?></span>
                <span class="next-entries"><?php previous_posts_link('successivi') ?></span> 
            </div>
    
        <?php else : ?>
    
            <h2>Non ci sono post, spiacente.</h2>
    
        <?php endif; ?>

</div> <!-- chiuso content -->

<?php get_footer(); ?>
