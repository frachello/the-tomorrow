var eventorganiser = eventorganiser || {};
/**
 * Simply compares two string version values.
 * 
 * Example:
 * versionCompare('1.1', '1.2') => -1
 * versionCompare('1.1', '1.1') =>  0
 * versionCompare('1.2', '1.1') =>  1
 * versionCompare('2.23.3', '2.22.3') => 1
 * 
 * Returns:
 * -1 = left is LOWER than right
 *  0 = they are equal
 *  1 = left is GREATER = right is LOWER
 *  And FALSE if one of input versions are not valid
 *
 * @function
 * @param {String} left  Version #1
 * @param {String} right Version #2
 * @return {Integer|Boolean}
 * @author Alexey Bass (albass)
 * @since 2011-07-14
 */
eventorganiser.versionCompare = function(left, right) {
    if (typeof left + typeof right != 'stringstring')
        return false;
    
    var a = left.split('.'),   b = right.split('.'),   i = 0, len = Math.max(a.length, b.length);
        
    for (; i < len; i++) {
        if ((a[i] && !b[i] && parseInt(a[i], 10 ) > 0) || (parseInt(a[i], 10 ) > parseInt(b[i], 10 ))) {
            return 1;
        } else if ((b[i] && !a[i] && parseInt(b[i], 10 ) > 0) || (parseInt(a[i], 10 ) < parseInt(b[i], 10 ))) {
            return -1;
        }
    }
    
    return 0;
};

(function( $ ){
	  var methods = {
	     init : function( options ) {
	    	 this.hide();
	    	 var original = this;
	    	 var id = this.attr('id');
	    	 var groups = $.map( this.children('optgroup'), function(e) { 
	    	 var values = $.map( $(e).children('option'), function(e) { 
	    			 return { value: $(e).val(), label: $(e).text() };
		    	 });
	    		 return { values: values, label:$(e).attr('label') };
	    	 } );
	    	
	    	 var ul = $('<ul id="'+id+'-es" class="eo-multiselect-checkboxes ui-helper-reset"></ul>').insertAfter(this);
	    	 
	    	 $.each( groups, function(prop, group) {
	    		 	var subgroup = $('<ul></ul>');
	    			$("<li class='eo-multiselect-optgroup-label'><a class='ui-widget-header ui-corner-all eo-multiselect-header ui-helper-clearfix' href='#'>"+group.label+"</a></li>")
	    				.children('a').toggle(function() {
	    					 $(this).parent().find('li input').attr('checked',true).trigger('change');
	    				}, function() {
	    					$(this).parent().find('li input').attr('checked',false).trigger('change');
	    				})
	    				.parent().appendTo(ul).append(subgroup);
	    			
	    			$.each( group.values, function(index, option) {
	    				subgroup.append('<li class="eo-multiselect-option">'+
	    						'<label class="ui-corner-all">'
	    						+'<input type="checkbox" value="'+option.value+'" title="'+option.label+'">'
	    						+'<span> '+option.label+' </span>'
	    						+'</label>'
	    						+'</li>'
	    				);
	    			});
	    	});
	    	 
	    	 jQuery.expr[':'].containsInsensitive = function(a,i,m){
	    		 return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0;
	    	 };
	    	 
	    	 var filter =$('<input type="text" class="eo-multiselect-filter" placeholder="Filter">');
	    	 ul.wrap('<div class="eo-multiselect ui-widget ui-widget-content ui-corner-all" />').before(filter);
	    	 				
	    	 filter.keyup(function(event) {
	    		 event.preventDefault();
	    		 var filter = $(this).val();
	    		 if (filter) {
	    			 ul.find("li ul li label span:not(:containsInsensitive(" + filter + "))").parents('li.eo-multiselect-option').hide();
	    			 ul.find("li ul li label span:containsInsensitive(" + filter + ")").parents('li.eo-multiselect-option').show();
	    		 } else {
	    			 ul.find('li.eo-multiselect-option').show();
	    		 }
	    	 });
	
	    	 ul.find( 'li ul li').hover(function() {
	    	      $(this).addClass('ui-state-hover');
	    	   }, function() {
	    	      $(this).removeClass('ui-state-hover');
	    	   }).find('input:checkbox').change(function(){
	    		   	var value = $(this).val();
	    		   	original.find('option[value="'+value+'"]').attr("selected",this.checked);
	     		});
	    	 	   	
	       return this;
	     }
	  };

	  $.fn.easySelect = function( method ) {
	    if ( methods[method] ) {
	      return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
	    } else if ( typeof method === 'object' || ! method ) {
	      return methods.init.apply( this, arguments );
	    } else {
	      $.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	    }    
	  };
	})( jQuery );
	
(function($) {
inlineEditBooking = {

	init : function(){
		var t = this,emailRow=$('#bulk-email'),downloadSettings=$('#bulk-download');
		t.what = '#booking-';

		//revert on escape or clicking cancel
		emailRow.keyup(function(e){
			if (e.which == 27)
				return inlineEditBooking.revert();
		});
		downloadSettings.keyup(function(e){
			if (e.which == 27)
				return inlineEditBooking.revert();
		});

		//Deleting booking
		$('#eo-bookings-table').on('click','.delete .submitdelete',function(e){
			var r=confirm("Are you sure you wish to delete this booking?\n\n This action cannot be undone.");
			if ( r!== true ){	
				e.preventDefault();	
			  }
		});

		//Cancel bulk email
		$('a.cancel', emailRow).click(function(){
			return inlineEditBooking.revert();
		});
		//Cancel bulk email
		$('a.cancel', downloadSettings).click(function(){
			return inlineEditBooking.revert();
		});

		$('#eo-meta-labels').easySelect();
		//On bulk actions drop-down selecting edit, trigger bulk edit
		$('#download-bookings-trigger').click(function(e){
			e.preventDefault();
			if ( $('form#posts-filter tr.inline-editor').length > 0 ) {
				t.revert();
			}else{
				t.showOptions();
			}
		});

		//On bulk actions drop-down selecting edit, trigger bulk edit
		$('#doaction, #doaction2').click(function(e){
			var n = $(this).attr('id').substr(2);
			 if ( $( 'select[name="'+n+'"]' ).val() == 'email' ){
				e.preventDefault();
				t.setEmail();
			}else if (  $( 'select[name="'+n+'"]' ).val() == 'delete' ) {
				var r=confirm("Are you sure you wish to delete these bookings?\n\n This action cannot be undone.");
				if ( r !== true ){	
					e.preventDefault();	
	 			 }
				t.revert();

			} else if ( $('form#posts-filter tr.inline-editor').length > 0 ) {
				t.revert();
			}
		});

		//On filter, revert quick/bulk edit and remove action.
		$('#post-query-submit').mousedown(function(e){
			t.revert();
			$('select[name^="action"]').val('-1');
		});
	},

	toggle : function(el){
		var t = this;
		if( $(t.what+t.getId(el)).css('display') == 'none' ){
			t.revert();
		}else{
			t.edit(el);
		}
	},

	showOptions : function(){
		var c = true;
		this.revert();
		
		//Show the bulk editor
		$('#bulk-download td').attr('colspan', $('.widefat:first thead th:visible').length);
		$('table.widefat tbody').prepend( $('#bulk-download') );
		$('#bulk-download').addClass('inline-editor').show();

		//Add the checked users to the edit list
		c = false;

		if ( c )
			return this.revert();
		
		$('html, body').animate( { scrollTop: 0 }, 'fast' );
	},
	
	setEmail : function(){
		var te = '', c = true;
		this.revert();

		//Show the bulk editor
		$('#bulk-email td').attr('colspan', $('.widefat:first thead th:visible').length);
		$('table.widefat tbody').prepend( $('#bulk-email') );
		$('#bulk-email').addClass('inline-editor').show();

		//Add the checked users to the edit list
		var eo_mail_list = [];
		$('tbody th.check-column input[type="checkbox"]').each(function(i){
			if ( $(this).prop('checked') ) {
				c = false;
				var id = $(this).val(), bookee;
				bookee = $('#inline_'+id+' .username').text() || false;
				if( bookee ){
					te += '<div id="ttleemail'+id+'"><a id="_'+id+'" class="ntdelbutton" title="click to remove">X</a>'+bookee+'</div>';
				      eo_mail_list.push(bookee);
				}
			}
		});

		if ( c )
			return this.revert();

		$('#bulk-email-titles').html(te);

		//When a user is removed from the bulk edit list,uncheck them.
		$('#bulk-email-titles a').click(function(){
			var id = $(this).attr('id').substr(1);
			$('table.widefat input[value="' + id + '"]').prop('checked', false);
			$('#ttleemail'+id).remove();
		});

		$('html, body').animate( { scrollTop: 0 }, 'fast' );

		},

	revert : function(){
		var id = $('table.widefat tr.inline-editor').attr('id');

		if ( id ) {
			$('table.widefat .inline-edit-save .waiting').hide();

			if ( 'bulk-edit' == id ) {
				$('table.widefat #bulk-edit').removeClass('inline-editor').hide();
				$('#bulk-titles').html('');
				$('#inlineedit').append( $('#bulk-edit') );
			}else if ('bulk-email' == id ){
				$('table.widefat #bulk-email').removeClass('inline-editor').hide();
				$('#bulk-email-titles').html('');
				$('#inlineedit').append( $('#bulk-email') );
			}else if( 'bulk-download' == id ){ 		
				$('table.widefat .inline-edit-save .waiting').hide();
				$('table.widefat #bulk-download').removeClass('inline-editor').hide();
				$('#bulk-download-edit').append( $('#bulk-email') );
			} else {
				$('#'+id).remove();
				id = id.substr( id.lastIndexOf('-') + 1 );
				$(this.what+id).show();
			}
		}

		return false;
	},

	getId : function(o) {
		var id = $(o).closest('tr').attr('id'),
		parts = id.split('-');
		return parts[parts.length - 1];
	}
};

$(document).ready(function(){
	inlineEditBooking.init();
	
	if( $('#booking-search-input').length > 0 ){
	
	$( "#booking-search-input" ).autocomplete({
		delay: 0,
		source: function(req, response) {
			$.getJSON(ajaxurl + "?callback=?&action=eo-search-bookings", req, function(data) {
				response($.map(data, function(item) {
					item.label = item.event;
					item.value = '#' + item.booking_id;
					return item;
				}));
			});
		},
		select: function( event, ui ) {  
	        window.location.href = ui.item.edit_link;  
	 }
	}).addClass("ui-widget-content ui-corner-left");
	
	
	/* Backwards compat with WP 3.3-3.5 (UI 1.8.16-1.8.2)*/ 
	var jquery_ui_version = $.ui ? $.ui.version || 0 : -1;
	var namespace = ( eventorganiser.versionCompare( jquery_ui_version, '1.9' ) >= 0 ? 'ui-autocomplete' : 'autocomplete' );
	var itemNamespace = ( eventorganiser.versionCompare( jquery_ui_version, '1.9' ) >= 0 ? 'ui-autocomplete-item' : 'item.autocomplete' );
	
	$('#booking-search-input').data(namespace)._renderItem = function(ul, item) {
		if( item.booking_id === 0 ){
			return $("<li></li>").data( itemNamespace, item ).append( item.label).appendTo(ul);
		}
		
		var term = (this.term+'').replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\\<\>\|\:])/g, "\\$1");
		if( term.charAt(0) == '#' ){
			term = term.substr(1);
		}
		var re = new RegExp( "(" + term+ ")" , 'gi' );
		
		item.booking_id = item.booking_id.replace( re, "<span style='font-weight:bold;'>$1</span>" );
		item.bookee = item.bookee.replace( re, "<span style='font-weight:bold;'>$1</span>" );
		item.event = item.event.replace( re, "<span style='font-weight:bold;'>$1</span>" );
		item.bookee_email = item.bookee_email.replace( re, "<span style='font-weight:bold;'>$1</span>" );
		
		return $("<li></li>").data("item.autocomplete", item).append( 
					"<a style='overflow:auto'>"
						+ "<span style='float:left; margin-right:10px;'>" 
							+ "<span style='color:#333;line-height:50px;font-size:14px;'>#" + item.booking_id + "</span>"
						+ "</span>"
						+ "<span style='float:left'>" 
							+ item.event + "</br>"
							+ "<span style='font-size: 0.8em'><em>" + item.bookee + " <br/>"+ item.bookee_email + "</em></span>"
						+ "</span>"
						+ "<span style='clear:both'></span>"
					+ "</a>"
				).appendTo(ul);
	};
	
	}
});
})(jQuery);