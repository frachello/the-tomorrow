<?php
/**
 * Event Organiser Email Template: Green
 *
 * This template is used for sending e-mails to bookees.
 *
 * ********************** NOTICE: ***********************
 *  Do not make changes to this file. Any changes made to this file
 * will be overwritten if the plug-in is updated.
 * ********************** NOTICE: ***********************
 * 
 * 
 * ********** WANT TO CREATE YOUR OWN TEMPLATE? *********
 * Simply create a file just like this in your theme.
 * Make sure "Event Organiser Email Template: [template name]" resides in 
 * the comment header at the top.
 * ********** WANT TO CREATE YOUR OWN TEMPLATE? ********* 
 *
 * @package Event Organiser Pro (plug-in)
 * @since 1.0
 */
?>
<div style="background: #f0f0f0; padding: 8px 10px;">
	<div style="background: #0A7335; width: 550px;  border: 1px solid #ccc; margin: 0 auto;border-radius:5px;">
		<div id="eo-email-header" style="height:50px;"></div>
		<div id="eo-email-content" style="background: #fff;padding: 8px 15px;">
			<?php eventorganiser_email_content(); ?>
		</div>
		<div  id="eo-email-footer" style="background: #0A7335; height:50px;position:relative;">
			<div style="font-size:10px;vertical-align:text-bottom;width:100%;text-align:center;position:absolute;bottom:0;">
				<?php echo __( 'Powered by', 'eventorganiserp' ) .' <a href="http://wp-event-organiser.com">Event Organiser</a>'; ?>
			</div>
		</div>
	</div>
</div>
