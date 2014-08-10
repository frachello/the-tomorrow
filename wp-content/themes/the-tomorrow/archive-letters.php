<?php
/*
Template Name: Letters Archive
*/
?>

<?php get_header(); ?>
<?php $curr_page_term_name = get_queried_object()->name;  ?>

<!-- content -->
<div id="content">

	<div class="page-content">

	<h2><?php echo $curr_page_term_name; ?></h2>
	<p class="rules-link"><a href="/rules/">how does it work?</a></p>

	<?php $rows_count = 1; ?>

	<?php if (have_posts()) : ?>

	<div class="archive-boxes">
	<?php $current_month = ''; ?>
	<?php while (have_posts()) : the_post(); ?>
		<?php
			$post_month = get_the_date('F Y');
		    if ( $post_month != $current_month ) {
		        $current_month = $post_month;
		        echo "<h3>$current_month</h3>";
				$rows_count = 1;
		    }
	    ?>
		<?php
			$conversation_slugs = wp_get_post_terms($post->ID, 'conversations', array("fields" => "slugs"));
			$hash_permalink = get_bloginfo('url').'/conversations/'.$conversation_slugs[0].'/#letter-'.$post->ID;
		?>

		<div class="archive-box post-<?php the_ID(); ?> counter_<?php echo $rows_count; ?>">

			<h4><a href="<?php echo $hash_permalink; ?>" title="<?php the_title(); ?>">
				<?php the_title() ?>
			</a></h4>
			<div class="entry">
			  <?php the_excerpt('[leggi tutto]'); ?>
			</div>
			<div class="bottom">
				<p class="date"><?php echo date_ago(); ?></p>
			</div>

		</div>
	<?php
	if($rows_count==4){
		$rows_count=0;
	}
	$rows_count++;
	endwhile;
	?>

	</div>

    <?php else : ?>

        <h2>Non ci sono post, spiacente.</h2>

    <?php endif; ?>

	</div>

	<br class="clear" />

</div> <!-- chiuso content -->

<?php get_footer(); ?>
