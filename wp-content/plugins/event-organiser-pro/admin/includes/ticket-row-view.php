<tbody id="eo-ticket-row-%%rows%%" class="eo-ticket-row" data-eo-ticket-row-id="%%rows%%" data-eo-ticket-id="%%ticket_id%%">

	<tr id="ticket-%%rows%%" class="eo-ticket" data-eo-ticket-row-id="%%rows%%">
		
		<td class="ticket column-ticket">
			<input type="hidden" value="update" class="eo-ticket-action" name="eventorganiser_pro[ticket][%%rows%%][action]">
			<input type="hidden" value="%%rows%%" class="eo-ticket-order" name="eventorganiser_pro[ticket][%%rows%%][order]">
			<input type="hidden" value="%%ticket_id%%" class="eo-ticket-id" name="eventorganiser_pro[ticket][%%rows%%][ticket_id]"> 
			<span class="eo-ticket-name">%%name%%</span>
		</td>
		
		<td class="ticket_spaces column-ticket_spaces">
			<span class="eo-ticket-spaces">%%spaces%%</span>
		</td>
		
		<td class="ticket_price column-ticket_price">
			<span class="eo-ticket-price">%%price%%</span>
		</td>
		
		<td class="ticket_actions column-ticket_actions">
			<span class="eo-ticket-actions-wrap">
				<a href="#" class="eo-edit-ticket"> 
					<?php esc_html_e('Edit', 'eventorganiserp' );?> 
					<span class="eo-settings-toggle-arrow">&#x25BC;</span>
				</a>
				<a href="#" class="eo-delete-ticket" style="color:#b94a48;">
					<?php esc_html_e('Delete', 'eventorganiserp' );?> 
					<span class="eo-delete-ticket-symbol">&#10006;</span>
				</a>
				<a href="#" class="eo-move-ticket-up" style="font-weight:bold;font-size: 20px;vertical-align: middle;"> &#11014; </a>
				<a href="#" class="eo-move-ticket-down" style="font-weight:bold;font-size: 20px;vertical-align: middle;"> &#11015; </a>
			</span>
		</td>
	</tr>
	
	<tr id="ticket-%%rows%%-settings" class="eo-ticket-settings" style="display:none">
		<td colspan="5">			
			<div id="eo-ticket-form-dialog" style="width: 50%;float: left;">

				<table class="ticket-settings-table form-table">
				<tbody>
					<tr>
						<th> <?php esc_html_e('Ticket name:', 'eventorganiserp' );?> </th>
						<td>
							<input type="text" placeholder="Concession" name="eventorganiser_pro[ticket][%%rows%%][name]" class="eo-ticket-input-name ui-autocomplete-input ui-widget-content ui-corner-all" value="%%name%%">
						</td>
					</tr>
					<tr>
						<th> <?php esc_html_e('Price:', 'eventorganiserp' );?> </th>
						<td>
							<span class="eo-ticket-input-currency">
								<?php echo eventorganiser_get_currency_symbol( eventorganiser_pro_get_option( 'currency' ) ); ?>
							</span>
							<input type="text" placeholder="0.00" name="eventorganiser_pro[ticket][%%rows%%][price]" class="eo-ticket-input-price ui-autocomplete-input ui-widget-content ui-corner-all" value="%%price%%">
						</td>
					</tr>
					<tr>
						<th> <?php esc_html_e('Spaces:', 'eventorganiserp' );?> </th>
						<td>
							<input type="number" placeholder="20" name="eventorganiser_pro[ticket][%%rows%%][spaces]" class="eo-ticket-input-spaces" value="%%spaces%%" style="width:auto;" max="999999" min="0">
							<?php eventorganiser_inline_help( 
								__('Spaces', 'eventorganiserp' ),
								__( 'This indicates the quanity available of this ticket type. If you are selling tickets for individual occurrences, then this limit applies to each occurrence.', 'eventorganiserp')
								.sprintf(
									'<p><a href="%s">%s</a>.</p>',
									'http://wp-event-organiser.com/pro-features/flexible-ticket-options/',
									__('See this page for more details', 'eventorganiserp' )
 								),
 								true
							); ?>
						</td>
					</tr>
					<tr>
						<th> <?php esc_html_e('Tickets on sale:', 'eventorganiserp' );?> </th>
						
						<td>
							<div>
								<div> 
									<?php _e( 'Sale starts', 'eventorganiserp' ); ?>
									<?php eventorganiser_inline_help( 
									__('Ticket availability', 'eventorganiserp' ),
									__( 'You can specify a date when this ticket will go on sale, and when it is no longer available for purchase. Leave blank for no restriction. Tickets will automatically come off sale when the event starts', 'eventorganiserp')
									.sprintf(
										'<p><a href="%s">%s</a>.</p>',
										'http://wp-event-organiser.com/pro-features/flexible-ticket-options/',
										__('See this page for more details', 'eventorganiserp' )
 									),
 									true
									); ?> 
								</div>
						
								<input type="text" name="eventorganiser_pro[ticket][%%rows%%][from]" value="%%from%%" size="10" class="eo-ticket-input-from ui-autocomplete-input ui-widget-content ui-corner-all">
								<input type="text" name="eventorganiser_pro[ticket][%%rows%%][from_time]" value="%%from_time%%" size="5" class="eo-ticket-input-from-time ui-autocomplete-input ui-widget-content ui-corner-all">
							</div>
				
							<div>
								<div> <?php _e( 'Sale ends', 'eventorganiserp' ); ?> </div>
								<input type="text" name="eventorganiser_pro[ticket][%%rows%%][to]" value="%%to%%" size="10" class="eo-ticket-input-to ui-autocomplete-input ui-widget-content ui-corner-all">
								<input type="text" name="eventorganiser_pro[ticket][%%rows%%][to_time]" value="%%to_time%%" size="5" class="eo-ticket-input-to-time ui-autocomplete-input ui-widget-content ui-corner-all">
							</div>
				
							<p class="description">
								<?php _e( 'Bookings will be closed when an occurrences starts.', 'eventorganiserp' ); ?>
							</p>

						</td>
					</tr>
				</tbody>
				</table>
			</div>
	
			<div style="width: 50%;float: left;<?php if( eventorganiser_pro_get_option('book_series') ){ echo "display:none;"; } ?>">
					
				<div class="eo-ticket-occurrences-input"></div>
				
				<textarea style="display:none;" id="eop_ticket_selected_%%rows%%" name="eventorganiser_pro[ticket][%%rows%%][selected_dates]">%%selected%%</textarea>
				<textarea style="display:none;" id="eop_ticket_deselected_%%rows%%" name="eventorganiser_pro[ticket][%%rows%%][deselected_dates]">%%deselected%%</textarea>
				
				<a href="#" class="eo-select-all"><?php esc_html_e('Select all', 'eventorganiserp' );?></a> &nbsp;|&nbsp; <a href="#" class="eo-deselect-all"><?php esc_html_e('Deselect all', 'eventorganiserp' );?></a>
				
				<p class="description">
					<?php esc_html_e('Select the dates of the event for which you want this ticket to be sold. (Default: all dates).', 'eventorganiserp' );?> 
					
					<?php eventorganiser_inline_help( 
						__('Ticket dates', 'eventorganiserp' ),
						__( 'You have the option of selling tickets for only specific dates, for instance you may wish to make a cheaper tickets available for only the first occurrence.', 'eventorganiserp')
						.sprintf(
							'<p><a href="%s">%s</a>.</p>',
							'http://wp-event-organiser.com/pro-features/flexible-ticket-options/',
							__('See this page for more details', 'eventorganiserp' )
						),
						true
					); ?>
				</p>				
			</div>
			
		</td>
	</tr>
	
</tbody>