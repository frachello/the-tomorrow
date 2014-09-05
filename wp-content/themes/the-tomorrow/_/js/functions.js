// remap jQuery to $
(function($){})(window.jQuery);


/* trigger when page is ready */
$(document).ready ( function () { //Work as soon as the DOM is ready for parsing

	toggle_share_hp_events();

	stick_footer();

	places_map_height();
	if($('.entry-map .current-city').length){
		current_city = $('.entry-map .current-city').html();
		$('.entry-map .current-city').hide();
	//	console.log(current_city);
		$('form#search_venues_map input.text').attr('value',current_city);
	}
	if($('.entry-map .no-results').length){
		no_results_msg = $('.entry-map .no-results').html();
		$('.entry-map .no-results').hide();
	//	console.log(no_results_msg);
		$('form#search_venues_map').addClass('larger');
		$('form#search_venues_map input.text').attr('value',no_results_msg);
	}

	// fix rightcol top position after scrolling
	if($('#rightcol').length){ fix_rightcol_pos(); }

	//Get the word after the hash from the url
	var current_url_hash  = location.hash.substr(1);

	/* -----------------------------------------------------------
	pagina singola conversazione
	*/

	if($('body.single-conversations').length){


		// jcarousel

		$('.jcarousel').jcarousel();
        $('.jcarousel-control-prev')
            .on('jcarouselcontrol:active', function() {
                $(this).removeClass('inactive');
            })
            .on('jcarouselcontrol:inactive', function() {
                $(this).addClass('inactive');
            })
            .jcarouselControl({
                target: '-=1'
            });
        $('.jcarousel-control-next')
            .on('jcarouselcontrol:active', function() {
                $(this).removeClass('inactive');
            })
            .on('jcarouselcontrol:inactive', function() {
                $(this).addClass('inactive');
            })
            .jcarouselControl({
                target: '+=1'
            });
        $('.jcarousel-pagination')
            .on('jcarouselpagination:active', 'a', function() {
                $(this).addClass('active');
            })
            .on('jcarouselpagination:inactive', 'a', function() {
                $(this).removeClass('active');
            })
            .jcarouselPagination();


        // jcarousel swipe
        // http://www.tom-maton.co.uk/blog/2013/february/jcarousel-with-swipe-gestures-using-touchswipe

//		var carousel = $('.jcarousel');
//	    carousel.swipe({
//	        swipeLeft: function(event, direction, distance, duration, fingerCount) {   
//	            carousel.jcarousel('scroll', '+=1');
//	        },
//	        swipeRight: function(event, direction, distance, duration, fingerCount) {
//	            carousel.jcarousel('scroll', '-=1');
//	        }
//	    })



		// expand collapse letters
		if (!current_url_hash){		
			current_url_hash = $('article.letter:first-child').attr('id');
		}
		window.location.hash = '#'+current_url_hash;
		var active_letter = '#'+current_url_hash;

		$(active_letter).addClass('active highlight');
		setTimeout( function() {
			$(active_letter).removeClass('highlight');
		}, 2000);

		$("article.letter:not(.active)").addClass("closed");

		$('html, body').animate({scrollTop: $(active_letter).offset().top-100}, 500);

		$('article.letter .meta').bind( "click", function(e){

			e.preventDefault();
			if ($(this).next('.entry').is(':visible')) {
				$(this).parent().addClass('closed');
				$(this).parent().removeClass('active');
				window.location.hash = '';
			}else{
				$(this).parent().addClass('active');
				window.location.hash = $(this).parent().attr('id');
				$(this).parent().removeClass('closed');
			}

			var letters_count = $(".letter").length;
		//	console.log('letters_count='+letters_count);
			var closed_letters_count = $(".letter.closed").length;
		//	console.log('closed_letters_count='+closed_letters_count);
			if (closed_letters_count){
			//	console.log('at least one letter is closed');
				$('#rightcol .toggle_letters').addClass('expand');
				$('#rightcol .toggle_letters').removeClass('collapse');
				$('#rightcol .toggle_letters a').html('expand all');
			}else{
			//	console.log('all letters are open');
				$('#rightcol .toggle_letters').removeClass('expand');
				$('#rightcol .toggle_letters').addClass('collapse');
				$('#rightcol .toggle_letters a').html('collapse all');
			}

		});
		$('#rightcol .toggle_letters a').bind( "click", function(e){
			e.preventDefault();
			if( $(this).parent().hasClass('expand') ){
				$('article.letter').removeClass('closed');
				$('#rightcol .toggle_letters').removeClass('expand');
				$('#rightcol .toggle_letters').addClass('collapse');
				$('#rightcol .toggle_letters a').html('collapse all');
			}else{
				$('article.letter').addClass('closed');
				$('#rightcol .toggle_letters').addClass('expand');
				$('#rightcol .toggle_letters').removeClass('collapse all');
				$('#rightcol .toggle_letters a').html('expand all');
				window.location.hash = '';
			}
		});

		$('#rightcol .next_letter a').bind( "click", function(e){
			e.preventDefault();
			var next_letter = '#'+$('.letter.active').next().attr('id');
		//	console.log(next_letter);
			if(next_letter !== '#undefined'){ // se è l'ultima lettera della conversazione
				console.log('go to '+next_letter);
			    $('html, body').animate({
			        scrollTop: $(next_letter).offset().top-100
			    }, 500);
			    $('.active').removeClass('active');
			    $(next_letter).removeClass('closed');
			    $(next_letter).addClass('active');
			    window.location.hash = next_letter;
			}
		});
		$('#rightcol .prev_letter a').bind( "click", function(e){
			e.preventDefault();
			var prev_letter = '#'+$('.letter.active').prev().attr('id');
		//	console.log(prev_letter);
			if(prev_letter !== '#undefined'){ // se è la prima lettera della conversazione
		//		console.log('go to '+prev_letter);
			    $('html, body').animate({
			        scrollTop: $(prev_letter).offset().top-100
			    }, 500);
			    $('.active').removeClass('active');
			    $(prev_letter).removeClass('closed');
			    $(prev_letter).addClass('active');
			    window.location.hash = prev_letter;
			}
		});
	}

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
            $('#main_search .form input.text').attr('value','');
        };
	});


	// toggle main search
	$('.nav_menu_search a').bind( "click",function(e){
		e.preventDefault();
        if ($('#main_search').is(':visible')) {
        	$('body').removeClass('open-filters');
            $('#main_search').slideUp('fast');
			$('#main_search .form input.text').blur();
        } else {
            $('body').addClass('open-filters');
            $('#main_search').slideDown('fast');
            $('#filter_nav').slideUp('fast');
			$('#main_search .form input.text').focus();
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
	var $iso_container = $('#home_grid');	
	if( $iso_container.length ){

		$iso_container.isotope({
			itemSelector: '.home_box',
			layoutMode:'masonry',
			sortBy : 'random',
			masonry: {
				columnWidth: 240
			}
		});

	    // Infinite Scroll
	    $('#home_grid').infinitescroll({
	        navSelector  : 'div.pagination', 
	        nextSelector : 'div.pagination a:first', 
	        itemSelector : '.home_box',
	        bufferPx     : 200,
	        loading: {
	            finishedMsg: 'All posts have been loaded.',
	                        //img: +templateUrl+'ajax-loader.gif'
	        }
	    },
	 
	    // Infinite Scroll Callback
	    function( newElements ) {
	        var $newElems = jQuery( newElements ).hide(); 
//	        $newElems.imagesLoaded(function(){
	            $newElems.fadeIn();
	            $iso_container.isotope( 'appended', $newElems );
//	            $iso_container.isotope({sortBy: 'random'});
//	        });

			// apply colors to event boxes
			$('#home_grid .home_box.event .top .cat a').each(function() {
				box_top_color = $(this).closest('.top').attr("data-color");
				$(this).css('color',box_top_color);
			});

			toggle_over_class_hp_events();

			// cycle from / to
			$('.bottom ul.authors', $newElems).each(function() {
				var $elem = $(this).children('li'), l = $elem.length, i = 0;
				function go() {
				    $elem.eq(i % l).animate({ opacity: "0", top: "11" }, 400, "easeInQuad", function() {
				    	$(this).css('z-index',1);
						$elem.eq(i % l).delay(1000).animate({ opacity: "0", top: "-11" }, 0, "easeInQuad", function(){
							$(this).css('z-index',2);
				        	$(this).animate({ opacity: "1", top: "0" }, 400, "easeOutQuad", go);
						})
				        i++;
				    })
				}
				go();
			});

			toggle_share_hp_events();

	    });

	}


	// apply colors to event boxes
	$('.event .top .cat a').each(function() {
		box_top_color = $(this).closest('.top').attr("data-color");
		$(this).css('color',box_top_color);
	});
//	$('#home_grid .home_box.event .top .cat a').each(function() {
//		$(this).closest('.top').append('<div class="bg" style="background: '+box_top_color+'">');
//	});
	
	toggle_over_class_hp_events();

	// cycle from / to
	$('#home_grid .home_box.conversations .bottom ul.authors').each(function() {
		var $elem = $(this).children('li'), l = $elem.length, i = 0;
		function go() {
		    $elem.eq(i % l).animate({ opacity: "0", top: "11" }, 400, "easeInQuad", function() {
		    	$(this).css('z-index',1);
				$elem.eq(i % l).delay(1000).animate({ opacity: "0", top: "-11" }, 0, "easeInQuad", function(){
					$(this).css('z-index',2);
		        	$(this).animate({ opacity: "1", top: "0" }, 400, "easeOutQuad", go);
				})
		        i++;
		    })
		}
		go();

	});

	$('.conversations').children('.more').hide();
	$('.conversations').bind({
	  mouseenter: function() {
		$(this).children('.more').stop().fadeIn(300,'easeInQuad');
	  },
	  mouseleave: function() {
		$(this).children('.more').stop().fadeOut(300,'easeOutQuad');
	  }
	});



});




// decrease header height on scroll
$(window).scroll(function () {
	stick_footer();
	resize_header();
	if($('#rightcol').length){ fix_rightcol_pos(); }
});

/* optional triggers

$(window).load(function() {
	
});
*/

$(window).resize(function() {
	stick_footer();
	places_map_height()	;
});




/* ----------------------------------------------------------------------------------------------------------------
resize header
*/

function resize_header() {
    
	if( $('body.tax-event-venue').length || $('body.single-authors').length ){

		var distanceY = window.pageYOffset || document.documentElement.scrollTop,
		open_header_h = 520;
	    shrinkOn = 10,
	    header = document.querySelector("header"),
	    body = document.querySelector("body");
	    new_header_height = open_header_h - distanceY;
//	    console.log('new_header_height: '+new_header_height);
//	    console.log('distanceY: '+distanceY);
	    if(new_header_height<90){
//			classie.add(header,"smaller");
//			classie.add(body,"scrolled");
	    }else{
//			classie.remove(header,"smaller");
//			classie.remove(body,"scrolled");
	    }
//		if(new_header_height>90){
		if(new_header_height>140){
			$('header.main').css('height',new_header_height+'px');
//			$('header.main').removeClass("smaller");
//			$('body').removeClass("scrolled");
	    }else{
//			$('header.main').addClass("smaller");
//			$('body').addClass("scrolled");
//			$('body').addClass("scrolled");
//			if (distanceY > shrinkOn) {
//			    classie.add(body,"scrolled");
//			    classie.add(header,"smaller");
//			} else {
//			    if (classie.has(header,"smaller")) {
//			    	classie.remove(body,"scrolled");
//			        classie.remove(header,"smaller");
//
//			    }
//			}
	    }

	//	var distanceY = window.pageYOffset || document.documentElement.scrollTop,
	//    shrinkOn = 10,
	//    header = document.querySelector("header"),
	//    body = document.querySelector("body");
	//	if (distanceY > shrinkOn) {
	//	    classie.add(body,"scrolled");
	//	    classie.add(header,"smaller");
	//	} else {
	//	    if (classie.has(header,"smaller")) {
	//	    	classie.remove(body,"scrolled");
	//	        classie.remove(header,"smaller");
	//	    }
	//	}

	//	$(window).scroll(function(){
	//	    if($(document).scrollTop() > 0)
	//	    {
	//	        if($('header').data('size') == 'big')
	//	        {
	//	            $('header').data('size','small');
	//	            $('header').stop().animate({
	//	                height:'40px'
	//	            },600);
	//	        }
	//	    }
	//	    else
	//	    {
	//	        if($('header').data('size') == 'small')
	//	        {
	//	            $('header').data('size','big');
	//	            $('header').stop().animate({
	//	                height:'100px'
	//	            },600);
	//	        }  
	//	    }
	//	});

	}else{

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

}


/* ----------------------------------------------------------------------------------------------------------------
fix rightcol pos
*/

function fix_rightcol_pos() {
    if($('body.page-template-default').length){
		var distanceY = window.pageYOffset || document.documentElement.scrollTop,
	    scroll_limit = 70,
	    rightcol = document.querySelector("#rightcol");
    }else{
		var distanceY = window.pageYOffset || document.documentElement.scrollTop,
	    scroll_limit = 225,
	    rightcol = document.querySelector("#rightcol");
    }
	if (distanceY > scroll_limit) {
	    classie.add(rightcol,"fixed");
	} else {
	    if (classie.has(rightcol,"fixed")) {
	        classie.remove(rightcol,"fixed");
	    }
	}

}

/* ----------------------------------------------------------------------------------------------------------------
stick footer
*/

function stick_footer(){
	if ( $('#content').length && $('.internal-page').length ){
		win_h = $( window ).height();
		content_h = $('#content').height();
//		console.log(content_h);
		footer_h = $('#content').height();
//		console.log(footer_h);
		content_margin_top = $('#content').css('padding-top');
//		console.log(content_margin_top);
		content_offset = $('#content').offset();
//		console.log(content_offset.top);
		content_h_total = content_offset.top + content_h;
//		console.log(content_h_total);
		if( (footer_h + content_offset.top + content_offset.top + content_h + 10) < win_h ){
			$('footer').addClass('sticky');
		}else{
			$('footer').removeClass('sticky');
		}
	//	console.log(win_h);
		$('.entry-map').css('height',win_h)
		$('.eo-venue-map').css('height',win_h)
	}
}



/* ----------------------------------------------------------------------------------------------------------------
places map
*/

function places_map_height(){
	if ( $('.entry-map').length ){
		win_h = $( window ).height()+'px';
	//	console.log(win_h);
		$('.entry-map').css('height',win_h)
		$('.eo-venue-map').css('height',win_h)
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


/* ----------------------------------------------------------------------------------------------------------------
toggle_share_hp_events */

function toggle_share_hp_events(){
	if( $('#home_grid .home_box.event').length ){
		$('#home_grid .home_box.event').each(function(){

//			debounce(function() {
			
				$(this).find('.share_wrap').bind({
				  mouseenter: function() {
				  	console.log('mouseenter');
					$(this).children('.share_baloon').stop().fadeIn(200,'easeInQuad');
				  },
				  mouseleave: function() {
				  	console.log('mouseenter');
					$(this).children('.share_baloon').stop().fadeOut(200,'easeOutQuad');
				  }
				})

//			}, 1000 )

		});
	}
}	


/* ----------------------------------------------------------------------------------------------------------------
toggle_over_class_hp_events */

function toggle_over_class_hp_events(){
	$('#home_grid .home_box.event').bind({
	  mouseenter: function() {
		$(this).children('.top').addClass( "over" );
	  },
	  mouseleave: function() {
		$(this).children('.top').removeClass( "over" );
	  }
	});
}