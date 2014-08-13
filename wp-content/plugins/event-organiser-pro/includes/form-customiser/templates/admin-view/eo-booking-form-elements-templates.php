<script type="text/template" id="tmpl-eo-form-element">
	<div id="eo-bfc-element-<%-id%>" class="eo-bfc-form-element" data-eo-form-element-id="<%-id%>" data-eo-form-element-type="<%-type%>">
		<div class="postbox">
			<h3 class="hndle"><span> <%-name%> - ID: <%-id%></span>
				<div class="eo-form-drag-icon" title="Drag form element"></div>
			</h3>
			<div class="inside">

				<div class="eo-bfc-example eo-bfc-example-<%-type%>"></div>

				<span class="eo-bfc-settings-toggle"> <span class="eo-bfc-settings-toggle-arrow">&#x25BC;</span> Edit </span>
				<div class="eo-bfc-settings eo-bfc-settings-<%-type%>"></div>

			</div>
		</div>
	</div>
	<ul class="eo-bfc-element-list"> </ul>
</script>

<script type="text/template" id="tmpl-eo-form-element-example-button">
	<p> <button><%-label%></button> </p>
</script>

<script type="text/template" id="tmpl-eo-form-element-example-gateway">
	<p><?php _e( 'This form element only appears if there there are multiple gateways enabled. It cannot be removed but can be placed anywhere in the booking form.', 'eventorganiserp'); ?></p>
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-ticketpicker">
	<p><?php _e( 'This form element allows the user to select their ticket(s). It cannot be removed but can be placed anywhere in the booking form.', 'eventorganiserp'); ?></p>
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-input">
	<% if( 'email' == id ){ %>
		<div><em><%- eo.gettext("This form element will not appear if the user is logged in.") %></em></div>
	<% } %>
	<label><%-label%></label><% if( required ){ %><span class="required">*</span><%}%><br>
	<input type="<% if( field_type ){ %><%-field_type%><% }else{ %>text<% } %>" 
		disabled="disabled" 
		<% if( (typeof placeholder != 'undefined') && placeholder ){ %>placeholder="<%-placeholder%>"<% } %>
		/>
	<% if( description ){ %><p class="description"><%-description%></p><%}%>
</script>

<script type="text/template" id="tmpl-eo-form-element-example-name">
	<div><em><?php _e( 'This form element will not appear if the user is logged in.', 'eventorganiserp'); ?></em></div>
	<label><%-label%></label><% if( required ){ %><span class="required">*</span><%}%><br>

	<% if( lname ){ %>
		<p style="overflow:hidden">
			<span style="float:left;">
				<input type="text" disabled="disabled" placeholder="<%- eo.gettext("First name") %>"/> <br>
				<label style="float:left;"> <%- eo.gettext("First name") %> </label> 
			</span>
			<span style="float:left;">
				<input type="text" disabled="disabled" placeholder="<%- eo.gettext("Last name") %>"/> <br>
				<label > <%- eo.gettext("Last name") %> </label>  
			</span> 
		</p>

	<% }else{ %>
		<p>
			<input type="text" disabled="disabled" placeholder="<%- eo.gettext("First name") %>"/>
		</p>
	
	<% } %>
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-select">
	<label><%-label%></label><% if( required ){ %><span class="required">*</span><%}%><br> 
	<select disabled="disabled" <% if( multiselect ){ %>multiple<%}%> >
		<% _.each( options, function( option, index ) { %> 
			<% if( multiselect ){ %>
				<option <% if( _.indexOf( selected, index ) > -1 ){ %>selected="selected"<%}%> ><%- option %></option>						
			<% }else{ %>
				<option <% if( selected == index ){ %>selected="selected"<%}%> ><%- option %></option>						
			 <% } %>
		<% }); %> 
	</select>
	<% if( description ){ %><p class="description"><%-description%></p><%}%>
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-radio">
	<label><%-label%></label><% if( required ){ %><span class="required">*</span><%}%><br>
	<ul>
		<% _.each(options, function( option, index ) { %> 
			<li><input type="radio" <% if( selected === index ){ %>checked="checked"<%}%> disabled="disabled"><label><%- option %></label></li>
		<% }); %> 
	</ul>
	<% if( description ){ %><p class="description"><%-description%></p><%}%>
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-checkbox">
	<label><%-label%></label><% if( required ){ %><span class="required">*</span><%}%><br>
	<ul>
		<% _.each(options, function( option, index ) { %> 
			<li><input type="checkbox" <% if( _.indexOf( selected, index ) > -1 ){ %>checked="checked"<%}%> disabled="disabled"><label><%- option %></label></li>
		<% }); %> 
	</ul>
	<% if( description ){ %><p class="description"><%-description%></p><%}%>
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-textarea">
	<label><%-label%></label><% if( required ){ %><span class="required">*</span><%}%><br>
	<textarea style="width:90%%" disabled="disabled"></textarea>
	<% if( description ){ %><p class="description"><%-description%></p><%}%>
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-html">
	<p><?php _e( 'This form element allows you to enter any HTML, to be displayed on the form', 'eventorganiserp'); ?></p>
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-section">
	<h2 class="field-header"> <label><%-label%></label> </h2>
	<hr class="eventorganiser-reg-section-break">
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-fieldset">
	<fieldset>
		<legend><label><%-label%></label></legend>
	</fieldset>
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-hook">
	<code><%- action %></code>
</script>
								
<script type="text/template" id="tmpl-eo-form-element-example-terms_conditions">
	<label><%-label%></label><span class="required">*</span><br>
	<div class="eo-bfc-terms-conditions-example-terms"><%- terms %></div>
	<input type="checkbox" disabled="disabled"> <label> <%- terms_accepted_label %> </label>
</script>
		
<script type="text/template" id="tmpl-eo-form-element-example-antispam">
	<p><?php _e( 'This form element asks the user a simple random maths question in order to help prevent spam', 'eventorganiserp'); ?></p>
	<label><%-label%></label><span class="required">*</span><br>
	<input type="text" 
		disabled="number" 
		<% if( placeholder ){ %>placeholder="<%-placholder%>"<% } %>
	/>
	<% if( description ){ %><p class="description"><%-description%></p><%}%>
</script>
				
<script type="text/template" id="tmpl-eo-form-element-example-address">
	<label><%-label%></label><% if( required ){ %><span class="required">*</span><%}%><br>
	<% if( description ){ %><p class="description"><%-description%></p><%}%>

	<span class="example-address-components">

		<% if( _.indexOf( components, 'street-address' ) > -1 ){ %>
			<p>
				<label>  
					<input type="text" disabled="disabled" placeholder="Street Address"/> </br> 					
					<%- eo.gettext("Street Address ") %>	
				</label>
			</p>
		<% } %> 

		<% if( _.indexOf( components, '2nd-line' ) > -1 ){ %>
			<p>
				<label>  
					<input type="text" disabled="disabled" placeholder="Address Line 2"/> </br> 
					<%- eo.gettext("Second line") %>	
				</label>
			</p>
		<% } %> 

		<% if( _.indexOf( components, 'city' ) > -1 ){ %>
			<p>
				<label>  
					<input type="text" disabled="disabled" placeholder="City"/> </br> 
					<%- eo.gettext("City") %>
				</label>
			</p>
		<% } %> 
		
		<p style="overflow:hidden">
			<% if( _.indexOf( components, 'state' ) > -1 ){ %>
				<label style="float:left;"> 
					<input type="text" disabled="disabled" placeholder="State/Province"/> </br> 
					<%- eo.gettext("State /Province") %>
				</label>
			<% } %> 

			<% if( _.indexOf( components, 'postcode' ) > -1 ){ %>
				<label style="float:left;"> 
					<input type="text" disabled="disabled" placeholder="Postcode"/> </br> 
					<%- eo.gettext("Postcode") %>
				</label>
			<% } %> 
		</p>
		
		<% if( _.indexOf( components, 'country' ) > -1 ){ %>
			<p>
				<label>  
					<input type="text" disabled="disabled" placeholder="Country"/> </br> 
					Country 
				</label>
			</p>
		<% } %> 

	</span>
</script>