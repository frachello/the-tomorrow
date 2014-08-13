<script  type="text/template" id="tmpl-eo-form-controller">
	<p>
		<?php _e( 'You can add additional fields to your booking form using the buttons to the right, and then arrange them by dragging the fields. Click the added form field to reveal its options ', 'eventorganiserp' );?>
	</p>

	<div id="eo-bfc-manage-forms">
		<a title="<?php esc_attr_e( 'Create a new form', 'eventorganiserp' );?>" class="eo-bfc-add-form-btn eo-dashicon eo-dashicon-add-form" href="#"> <?php _e( 'Create New Form', 'eventorganiserp' );?> </a>
		<input type="submit" class="button eo-bfc-edit-form-btn" value="<?php esc_attr_e( 'Edit Form', 'eventorganiserp' );?>">				
		<select id="eo-bfc-edit-form" class="eo-bfc-edit-form" name="eo-edit-form"></select>
	</div>

	<div id="eventorganiser-form-customiser-wrap" 
		style="margin-right: 300px;min-width: 600px;min-width:600px;border-radius:3px;border: 1px solid #DFDFDF;">
			
		<div style="float: right;margin-right: -300px;width: 280px;">
			<div id="eventorganiser-form-fixed-mb" style="position: fixed; top: 185px; margin-right: 30px;">
				<div id="side-sortables" class="meta-box-sortables"></div>
			</div>
		</div>
				
		<div id="eo-bfc-form"
			class="eo-bfc-form"
			style="min-width:600px;border-radius:3px;">
		</div>
			
	</div>
</script>


<script type="text/template" id="tmpl-eo-form">
	<div id="eo-bfc-header">
	
		<button class="button button-primary eo-bfc-save-form-btn eo-dashicon eo-dashicon-save-form" style="float:right;"> 
			<?php _e( 'Save Form', 'eventorganiserp' ); ?> 
		</button>
		<span class="spinner" style="float:right;"></span>

		<ul id="eo-bfc-form-tabs">
			<li class="eventorganiser-form-tab active">
				<a href="#" class="eo-dashicon eo-dashicon-form" aria-controls="eventorganiser-form-fields">
					<?php _e( 'Form', 'eventorganiserp' ); ?>  
				</a>
			</li>
			<li class="eventorganiser-form-tab">
				<a href="#" class="eo-dashicon eo-dashicon-settings" aria-controls="eventorganiser-form-settings">
					<?php _e( 'Settings', 'eventorganiserp' ); ?>  
				</a>
			</li>
		</ul>		
	</div>

	<input type="hidden" name="eventorganiser-form-id" value="<%-id%>">
	<div class="eo-bfc-root">
		<ul id="eventorganiser-form-fields" 
			class="eo-bfc-element-list eo-booking-form-tabbed-area eo-bfc-element-root-list">
		</ul>
	</div>

	<table id="eventorganiser-form-settings" 
		class="eo-booking-form-tabbed-area form-table" 
		style="display: none;">		
	</table>

	<div id="eo-bfc-footer">
	
		<a class="eo-bfc-delete-form eo-dashicon eo-dashicon-delete-form" title="<?php esc_attr_e( 'Delete this form', 'eventorganiserp' );?>" href="#"> 
			<?php _e( 'Delete Form', 'eventorganiserp' );?>
		</a>

		<button class="button button-primary eo-bfc-save-form-btn eo-dashicon-save-form" style="float:right;"> 
			<?php _e( 'Save Form', 'eventorganiserp' ); ?> 
		</button>
		<span class="spinner" style="float:right;"></span>
		<div style="clear:both"></div>

	</div>
</script>

<script type="text/template" id="tmpl-eo-form-element-setting-input">
		<td><%-label%></td>
		<td>
			<input class='eo-bfc-setting-<%-id%>' type='text' value='<%-value%>'/>
			<% if( inline_help ) { %> 
				<a href="#" class="eo-bfc-inline-help">
					<img src="<?php echo esc_url(EVENT_ORGANISER_URL."css/images/help-14.png");?>" width="16" height="16">
				</a> 
			<% } %>
		</td>
</script>

<script type="text/template" id="tmpl-eo-form-element-setting-checkbox">
		<td><%-label%></td>

		<% if( typeof options !== "undefined" ){ %>
			<td>
			<% for ( val in options ) { %>
  						<% label = options[val]; %>
				<label> 
					<input type='checkbox' <% if( _.indexOf( checked, val ) > -1 ){ %> checked='checked' <% } %> value='<%-val%>'/> 
					<%-label%> 
				</label> <br>
			<% }; %>
			</td>

		<% }else{ %>
			<% if( checked ){ %> checked='checked' <% } %>
			<td>
				<input type='checkbox' <% if( checked ){ %> checked='checked' <% } %> value='1'/>
				<% if( inline_help ) { %> 
					<a href="#" class="eo-bfc-inline-help">
						<img src="<?php echo esc_url(EVENT_ORGANISER_URL."css/images/help-14.png");?>" width="16" height="16">
					</a> 
				<% } %>
			</td>
		<% } %>
</script>
<script type="text/template" id="tmpl-eo-form-element-setting-textarea">
		<td><%-label%></td>
		<td>
			<textarea rows='4' class='large-text'><%-value%></textarea>
			<% if( inline_help ) { %> 
				<a href="#" class="eo-inline-help">
					<img src="<?php echo esc_url(EVENT_ORGANISER_URL."css/images/help-14.png");?>" width="16" height="16">
				</a> 
			<% } %>
		</td>
</script>

<script type="text/template" id="tmpl-eo-form-element-setting-range">
		<td><%-label%></td>
		<td> 
			<%-label_min%>: <input type="number" data-type="min" value="<%-value_min%>"><br>
			<%-label_max%>: <input type="number" data-type="max" value="<%-value_max%>"><br>
		</td>
</script>

<script type="text/template" id="tmpl-eo-form-element-setting-options">
	<td><%-label%></td>
	<td>
		<ul class="field-options field-options-checkbox"></ul>
	</td>
</script>

<script type="text/template" id="tmpl-eo-form-element-setting-options-option">
	<li>
		<% if( option_type == 'radio' ){ %>
			<input type="radio" data-option-checked="1" <% if( selected ){ %> checked="checked" <% } %>  name="<%-group%>" value="<%-index%>">
		<% }else{ %>
			<input type="checkbox" data-option-checked="1" <% if( selected ){ %> checked="checked" <% } %> value="<%-index%>">
		<% } %>
		<input type="text" data-option-value="1" value="<%-option%>">
		<span class="eo-bfc-add-btn eo-bfc-option-add" alt="add another option">+</span>
		<% if( show_remove_option ){ %>
			<span class="eo-bfc-remove-btn eo-bfc-option-remove" alt="remove another option">-</span>
		<% } %>
	</li>
</script>
			
<script type="text/template" id="tmpl-eo-form-element-bin">
	<div class="postbox ">
		<div class="handlediv" title="Click to toggle"><br></div>
		<h3 class="hndle"><span><%-label %></span></h3>
		<div class="inside">
			<ul id="eo_booking_form_field_<%-metabox %>_bin" class="eventorganiser-field-bin">
				<% _.each( element_types, function( element ){ %>
					<li data-type="<%-element.type %>" class="button">
						<span class="item-title"><%-element.name %></span>
					</li>
				<% }); %>
			</ul>
		</div>
	</div>
</script>