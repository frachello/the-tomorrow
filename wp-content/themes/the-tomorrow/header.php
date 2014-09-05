<?php
	global $options;
//	foreach ($options as $value) {
//	    if (get_settings( $value['id'] ) === FALSE) { $$value['id'] = $value['std']; } else { $$value['id'] = get_settings( $value['id'] ); } }
?>
<!doctype html>

<!--[if lt IE 7 ]> <html class="ie ie6 no-js" lang="en"> <![endif]-->
<!--[if IE 7 ]>    <html class="ie ie7 no-js" lang="en"> <![endif]-->
<!--[if IE 8 ]>    <html class="ie ie8 no-js" lang="en"> <![endif]-->
<!--[if IE 9 ]>    <html class="ie ie9 no-js" lang="en"> <![endif]-->
<!--[if gt IE 9]><!--><html class="no-js" lang="en"><!--<![endif]-->
<!-- the "no-js" class is for Modernizr. -->

<head id="www-thetomorrow-net" data-template-set="html5-reset">

	<meta charset="utf-8">
	
	<!-- Always force latest IE rendering engine (even in intranet) & Chrome Frame -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	
	<title>
	<?php if (is_home () ) { bloginfo('name'); echo " | "; bloginfo('description'); } elseif ( is_category() ) {
		single_cat_title(); echo " - "; bloginfo('name');
	} elseif (is_single() || is_page() ) {
		single_post_title();
	} elseif (is_search() ) {
		bloginfo('name'); echo " Risultati di ricerca per: "; echo wp_specialchars($s);
	} else { wp_title('',true); } ?>
	</title>

	<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" />
	<meta name="description" content="<?php bloginfo('description'); ?>" />

	<meta name="google-site-verification" content="">
	
	<script type="text/javascript" src="//use.typekit.net/isf6qgj.js"></script>
	<script type="text/javascript">try{Typekit.load();}catch(e){}</script>

	<link rel="stylesheet" href="<?php bloginfo('template_directory') ?>/_/css/style.css">
	
	<script src="<?php bloginfo('template_directory') ?>/_/js/modernizr-1.7.min.js"></script>
	
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
	
	<!-- wp_head() -->
	<?php wp_head(); ?>

	<script type="text/javascript">
	var disqus_shortname = 'frachello';
	var disqus_identifier;
	var disqus_url;

	function loadDisqus(source, identifier, url) {

	if (window.DISQUS) {
	   jQuery('#disqus_thread').insertAfter(source);
	   /** if Disqus exists, call it's reset method with new parameters **/

	    DISQUS.reset({
	  reload: true,
	  config: function () { 
	   this.page.identifier = identifier.toString();    //important to convert it to string
	   this.page.url = url;
	  }
	 });
	} else {
	//insert a wrapper in HTML after the relevant "show comments" link

	   jQuery('<div id="disqus_thread"></div>').insertAfter(source);
	   disqus_identifier = identifier; //set the identifier argument
	   disqus_url = url; //set the permalink argument
	   //append the Disqus embed script to HTML
	   var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
	   dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
	   jQuery('head').append(dsq);
	}
	};

	</script>

</head>

<body <?php if(is_home()){ body_class(); }else{ body_class('internal-page'); }?>> 

	<div id="wrapper">

		<div class="header_container">

			<header class="main" <?php
				$venue_id = eo_get_venue();
				$post_type = get_post_type();
				
				if ( is_singular( 'authors' ) ){
					if ( has_post_thumbnail() ) {
						$post_thumbnail_id = get_post_thumbnail_id($post->ID);
						$post_thumbnail_url = wp_get_attachment_url( $post_thumbnail_id );
				?>style=" background-image:url(<?php echo $post_thumbnail_url; ?>); " <?php
					}
				}
				if( $venue_id && !is_singular() ){
					$venue_header_img = eo_get_venue_meta($venue_id, '_header_img', true);
				?>style=" background-image:url(<?php echo $venue_header_img; ?>); " <?php
				} ?>>

				<div class="inner">

				<form method="get" action="<?php bloginfo('url'); ?>/places/" id="search_venues_map">
					<input class="text" type="text" name="city" value="" />
					<input class="submit" type="submit" />
				</form>
				
					<h1><a href="<?php bloginfo('url'); ?>" title="" accesskey="1">
					<?php bloginfo('name') ?></a></h1>
					<?php if(is_home()): ?><h2><?php bloginfo('description') ?></h2><?php endif; ?>

					<?php /* Our navigation menu.  If one isn't filled out, wp_nav_menu falls back to wp_page_menu.  The menu assiged to the primary position is the one used.  If none is assigned, the menu with the lowest ID is used.  */ ?>
					<?php // wp_nav_menu( array( 'container_class' => 'menu-header', 'theme_location' => 'primary' ) ); ?>

					<ul class="nav_menu">
						<li class="nav_menu_search"><a href="#">search</a></li>
<?php if(is_home()): ?><li class="nav_menu_filter"><a href="#">filter</a></li><?php endif; ?>
						<li class="nav_menu_menu"><a href="#">menu</a></li>
					</ul>

				</div>

			</header> 

			<div id="filter_nav"><div class="form">

				<form method="get" action="<?php bloginfo('url'); ?>">
					
					

					<div class="filter show">
						
						<p class="title">filter events <br />by location <br />or date range</p>
<!--
						<p>

							<input name="type[]" type="checkbox" value="event" id="event" />
							<label for="event">events</label>

							<input name="type[]" type="checkbox" value="conversations" id="conversations" />
							<label for="conversations">letters</label>

						</p>
-->
					</div>


					<div class="filter city">
						
						<p class="title">choose your city</p>

						<p>
							<label for="city">city</label>
							<input type="text city" name="city" value="<?php echo $_GET['city']; ?>" placeholder="" maxlength="50" class="text city" id="city_search" />
						</p>

					</div>

					<div class="filter date">
						
						<?php
							$today = date("d/m/Y"); // current date
							if(!isset($from_date)){
								$from_date = $today;
							}
						?>

						<p class="title">set a date</p>

						<p class="calendar_date">
							<label>from</label>
							<input name="from_date" class="text from" type="text" value="<?php echo $from_date; ?>" />
							
						</p>

						<p class="calendar_date">
							<label>to</label>
							<input name="to_date" class="text to" type="text" value="<?php echo $_GET['to_date']; ?>" />
							
						</p>

					</div>

					<?php // http://docs.wp-event-organiser.com/querying-events/querying-venues ?>
					<input class="submit" type="submit" value="apply" />

				</form>

			</div></div>

			<div id="main_search"><div class="form">

				<form method="get" action="<?php bloginfo('url'); ?>/">

					<input id="header_search" name="search" class="text" type="text" value="" />
					<input class="submit" type="submit" value="search" />

				</form>

			</div></div>

		</div> <!-- close .header_container -->

		<nav id="megamenu">

			<div class="inner">

				<div class="megahead">
					<a class="logotipo" href="/">theTomorrow</a>
					<a class="close" href="#">close</a>
				</div>

				<!-- ######################## main menu ######################## -->
				<?php
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar("main menu") ) : ?>			
				<?php endif; ?>
				<!-- ######################## / main menu ######################## -->

				<!-- ######################## nav 2 ######################## -->
				<div class="secondary-nav">
					<?php
						if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar("megamenu sub nav") ) : ?>			
					<?php endif; ?>
				</div>
				<!-- ######################## / nav 2 ######################## -->

			    <div class="megamenu-footer">
			      <p class="address">the Tomorrow, via Donizetti 4 — 20122 Milan, Italy</p>
			      <p class="disclaimer"><a href="#">Some rights reserved - </a></p>
			    </div>

			</div>

		</nav>



