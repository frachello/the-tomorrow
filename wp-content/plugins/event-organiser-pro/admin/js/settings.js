jQuery(document).ready(function($) {
	
	$('[data-eo-gateway-live-switch]').change(function(){
		
		var gateway = $(this).data('eo-gateway-live-switch');
		var value = parseInt( $(this).val(), 10 );
		
		//Show all options
		$( '[data-eo-gateway-'+gateway+'-setting]' ).parents('tr').show();
		
		if( value == 1 || value == -1 ){
			//Hide sandbox options);
			$( '[data-eo-gateway-'+gateway+'-setting="sandbox"]' ).parents('tr').hide();
		}
		
		if( value === 0 || value == -1 ){
			//Hide live options 
			$( '[data-eo-gateway-'+gateway+'-setting="live"]' ).parents('tr').hide();
		}
		
	}).trigger('change');
	
	if( $('#email-preview-wrap').length ) {
		$('#view-email-preview').click(function(e){
			e.preventDefault();

			if( typeof tinyMCE == 'undefined' 
					|| tinyMCE === null 
					|| tinyMCE.get('email_tickets_message') === null
					|| ( typeof tinyMCE.get('email_tickets_message') == 'undefined' )
					|| tinyMCE.get('email_tickets_message').isHidden() ){
				content = $('#email_tickets_message').val();
			}else{
				content = tinyMCE.get('email_tickets_message').getContent();
			}
			$('#email-preview').css({ 'opacity' : 0.7 }).text('loading...');
			$.ajax({
			  type: 'POST',
			  url: ajaxurl,
			  data: {
				action: 'eo-preview-email',
				template: $('#email_template').val(),
				message: content
				},
				success: function(data){
					$('#email-preview').html(data).css({ 'opacity' : 1 });
				},
 				dataType: 'html'
			});
			tb_show('Preview', "#TB_inline?height=500&amp;width=700&inlineId=email-preview-wrap", null);
		});
	}
	
	$('#offline_live_status, #paypal_live_status').change(function(){
		if( $(this).val() == -1 ){	
			$(this).parents('table').find('tr:not(:first-child)').fadeOut('slow');
		}else{
			$(this).parents('table').find('tr').fadeIn('slow');
		} 
	}).trigger('change');
});