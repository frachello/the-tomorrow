
<footer>

  <div class="inner">

    <div class="info">
      <a class="logo" href="<?php bloginfo('url'); ?>"><?php bloginfo('name') ?></a>
      <p class="desc">the Tomorrow is a daily web journal that reports the untiring generation of ideas, events, conversations that unite the thinkers who live in the mental and geographical space of Europe.</p>
    </div>

    <div class="secondary-nav">
      <?php
        if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar("secondary menu") ) : ?>     
      <?php endif; ?>
    </div>

    <div class="bottom">
      <p class="address">the Tomorrow, via Donizetti 4 — 20122 Milan, Italy</p>
      <p class="disclaimer"><a href="#">Some rights reserved - </a></p>
    </div>

  </div>

</footer>
    
</div> <!-- chiudo #wrapper -->



<script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<!-- <script>window.jQuery || document.write("<script src='<?php bloginfo('template_directory') ?>/_/js/jquery-1.5.1.min.js'>\x3C/script>")</script> -->
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php bloginfo('template_directory') ?>/_/js/jquery.ui.datepicker-it.js"></script>

<script src="<?php bloginfo('template_directory') ?>/_/js/isotope.pkgd.min.js"></script>
<?php wp_footer(); ?>
<script src="<?php bloginfo('template_directory') ?>/_/js/classie.js"></script>
<script src="<?php bloginfo('template_directory') ?>/_/js/functions.js"></script>

<!-- Asynchronous google analytics; this is the official snippet.
	 Replace UA-XXXXXX-XX with your site's ID and uncomment to enable.
	 
<script>

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-XXXXXX-XX']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
-->
  
</body>
</html>