// remap jQuery to $
(function($){})(window.jQuery);


/* trigger when page is ready */
$(document).ready ( function () { //Work as soon as the DOM is ready for parsing

	var id  = location.hash.substr(1); //Get the word after the hash from the url
	console.log(id);
	if (id) $('#'+id).addClass('highlight'); // add class highlight to element whose id is the word after the hash


//	$('h2').bind( "click", function() {
//		$(this).next('div').toggle();
//	});

	// expand collapse letters
	$('article.letter:gt(0)').addClass('closed');
	$('article.letter .meta').bind( "click", function(e){
		e.preventDefault();
		if ($(this).next('.entry').is(':visible')) {
			console.log('article not visible');
			$(this).parent().addClass('closed');
		}else{
			$(this).parent().removeClass('closed');
			console.log('article visible');
		}

	});

	resize_header();

	// toggle megamenu
	$('.nav_menu_menu a').bind( "click", function(e){
		e.preventDefault();
		$('#megamenu').slideDown();
		// lock scroll position, but retain settings for later
		var scrollPosition = [
			self.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
			self.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop
		];
		var html = jQuery('html'); // it would make more sense to apply this to body, but IE7 won't have that
		html.data('scroll-position', scrollPosition);
		html.data('previous-overflow', html.css('overflow'));
		html.css('overflow', 'hidden');
		window.scrollTo(scrollPosition[0], scrollPosition[1]);
	});
	$('#megamenu a.close').bind( "click", function(e){
		e.preventDefault();
		$('#megamenu').slideUp();
		// un-lock scroll position
		var html = jQuery('html');
		var scrollPosition = html.data('scroll-position');
		html.css('overflow', html.data('previous-overflow'));
		window.scrollTo(scrollPosition[0], scrollPosition[1])
	});


	// toggle filters
	$('.nav_menu_filter a').bind( "click",function(e){
		e.preventDefault();
        if ($('#filter_nav').is(':visible')) {
        	$('body').removeClass('open-filters');
            $('#filter_nav').slideUp('fast');
        } else {
        	$('body').addClass('open-filters');
            $('#filter_nav').slideDown('fast');
            $('#main_search').slideUp('fast');
        };
	});


	// toggle main search
	$('.nav_menu_search a').bind( "click",function(e){
		e.preventDefault();
        if ($('#main_search').is(':visible')) {
        	$('body').removeClass('open-filters');
            $('#main_search').slideUp('fast');
        } else {
            $('body').addClass('open-filters');
            $('#main_search').slideDown('fast');
            $('#filter_nav').slideUp('fast');
        };
	});


	// calendar filter
  	$(function () {
        $(".calendar_date input").datepicker({
		//	constrainInput: true,
			showOn: 'button',
			buttonText: 'select start date'
        });
    });


	// home isotope
	$('#home_grid').isotope({
		itemSelector: '.home_box',
		layoutMode:'masonry',
		masonry: {
			columnWidth: 240
		}
	});


	// apply colors to event boxes
	$('#home_grid .home_box.event .top .cat a').each(function() {
		box_top_color = $(this).closest('.top').attr("data-color");
		$(this).css('color',box_top_color);
		$(this).closest('.top').append('<div class="bg" style="background: '+box_top_color+'">');
	});
	$('#home_grid .home_box.event').bind({
	  mouseenter: function() {
		$(this).children('.top').toggleClass( "over" );
	  },
	  mouseleave: function() {
		$(this).children('.top').toggleClass( "over" );
	  }
	});


});


// decrease header height on scroll
$(window).scroll(function () {
	resize_header();
});

/* optional triggers

$(window).load(function() {
	
});

$(window).resize(function() {
	
});

*/


/* ----------------------------------------------------------------------------------------------------------------
resize header
*/

function resize_header() {
    
	var distanceY = window.pageYOffset || document.documentElement.scrollTop,
	    shrinkOn = 10,
	    header = document.querySelector("header"),
	    body = document.querySelector("body");
	if (distanceY > shrinkOn) {
	    classie.add(body,"scrolled");
	    classie.add(header,"smaller");
	} else {
	    if (classie.has(header,"smaller")) {
	    	classie.remove(body,"scrolled");
	        classie.remove(header,"smaller");
	    }
	}

}
/* ----------------------------------------------------------------------------------------------------------------
debounce(function() { }, 1234 ) */

function debounce( fn, threshold ) {
  var timeout;
  return function debounced() {
    if ( timeout ) {
      clearTimeout( timeout );
    }
    function delayed() {
      fn();
      timeout = null;
    }
    timeout = setTimeout( delayed, threshold || 100 );
  }
}



