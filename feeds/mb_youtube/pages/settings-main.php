<?php

/*
 * This is the main settings page for the plugin.
 */
function show_mb_youtube_page( &$feed_options ) {
    
// Import our requirements
/* @var $mb_globalfeed mb_globalfeed */
/* @var $mb_globalfeed_youtube_connect mb_globalfeed_youtube_connect */
/* @var $mbgfa_theme mbgfa_theme */
global $mb_globalfeed,$mb_globalfeed_youtube_connect,$mbgfa_theme;

$update = false;

if (isset($_POST['_update'])){
    if (!(check_admin_referer( 'youtube-connect-admin' ) && wp_verify_nonce( $_POST['_wpnonce'], 'youtube-connect-admin' )))
            die('Security check failed.');

    // An update has been submitted. Save the settings.
    $update = true;

    $feed_options['override_post_time_on_timezone_discrepency'] = isset($_POST['general-override_post_time_on_timezone_discrepency']) && $_POST['general-override_post_time_on_timezone_discrepency'] == 'on';
}

wp_enqueue_script('jquery');
?>
<h2><?php _e('MB YouTube Connect') ?></h2>
<form action="<?php $_SERVER['REQUEST_URI'] ?>" method="post">
    <?php if (function_exists('wp_nonce_field')){
        echo wp_nonce_field('youtube-connect-admin');
    } ?>
    <div id="notices">
        <?php  // Show welcome Message
        if ( $feed_options['show_welcome_message'] == true ) :
            //$feed_options['show_welcome_message'] = false;
        ?>
            <div class="notice updated no-hide" ><?php _e('<p>Welcome to MB YouTube. You\'ll find the feeds\' main configuration here.</p><p>Please report any bugs you find by using the bug report feature at the top right of the page. Thank you for using MB GlobalFeed!</p>') ?></div>
        <?php endif; // Welcome message 
        // Show saved message
        if ( $update ) : ?>
            <div class="notice updated no-hide" ><?php _e('<p>You\'re settings were successfully saved.</p>') ?></div>
        <?php endif; // Welcome message ?>
    </div>
    
    <!-- YouTube Object Information  -->
    <fieldset id="yt_username_information" class="ajax_option optgrp"><legend><?php _e( 'YouTube User Information' , $mb_globalfeed_youtube_connect->get_slug() ) ?></legend>
        <div class="leftopt">
            <?php $mbgfa_theme->ajaxIndicator(false, 'center'); ?>
        </div>
    </fieldset>
    <fieldset id="yt_general_options" class="optgrp"><legend>General Options</legend>
        <table>
            <p>Note: If you are encountering an issue wherein posts are showing up late with an incorrect date/time and/or WordPress is marking posts as "future", enabling the option below may fix any timezone handling problems.</p>
            <p><label for="general-override_post_time_on_timezone_discrepency"><input type="checkbox" <?php echo $feed_options['override_post_time_on_timezone_discrepency'] ? 'checked="checked"' : ''; ?> id="general-override_post_time_on_timezone_discrepency" name="general-override_post_time_on_timezone_discrepency" /> Override post date/time if the time is in the future</label></p>
            
            <input name="_update" type="submit" value="Save Settings" />
        </table>
    </fieldset>
    <!-- Reset Options -->
    <fieldset id="yt_reset_options" class="optgrp"><legend>Reset Feed</legend>
        <table>
            <tr id="manual_update_cont"><td><input type="button" value="Update Feed Manually" id="manual_update" /></td><td><?php $mbgfa_theme->informative('This will tell the feed to update, typically usefull if the feed is not updating automatically. <p>P.s. If things arent working right, make sure to let us know so we can fix it!</p>'); ?><?php $mbgfa_theme->ajaxIndicator(true); ?></td></tr>
            <tr id="redo_initial_setup_cont"><td><input type="button" value="Redo Initial Setup" id="redo_initial_setup" /></td><td><?php $mbgfa_theme->informative('This will bring up the initial setup wizard and remove all saved feed items, however it will not reset any settings.'); ?><?php $mbgfa_theme->ajaxIndicator(true); ?></td></tr>
            <tr id="reset_feed_defaults_cont"><td><input type="button" value="Reset Feed Defaults" id="reset_feed_defaults" /></td><td><?php $mbgfa_theme->informative('This will completely reset the feed to its default settings, removing all stored feed items.'); ?><?php $mbgfa_theme->ajaxIndicator(true); ?></td></tr>
        </table>
    </fieldset>
</form>
    <script type="text/javascript">
        jQuery(document).ready(function(){
            // Get ajax option groups
            get_yt_object( '<?php echo $feed_options['object_to_subscribe'] ?>' ,'#yt_username_information .leftopt' );
            
            jQuery('#redo_initial_setup').click(function(){
                jQuery(this).attr('disabled', '');
                toggleAjaxIndicator('#redo_initial_setup_cont');
                jQuery.ajax({
                    url: ajaxurl,
                    data: {action:'mbgf_youtube_connect_redo_initial_setup',_wpnonce:jQuery('#_wpnonce').val()},
                    statusCode: { 
                        200: function (response) {
                            window.location.reload();
                        }},
                    error: function(){
                        showMessage( 'Error: A network error occured.', 'network_error', true, 10000 );
                        toggleAjaxIndicator( element_id );
                    }
                });
            }); // redo_initial_setup.click()
            
            jQuery('#reset_feed_defaults').click(function(){
                jQuery(this).attr('disabled', '');
                toggleAjaxIndicator('#reset_feed_defaults_cont');
                jQuery.ajax({
                    url: ajaxurl,
                    data: {action:'mbgf_youtube_connect_reset_feed_defaults',_wpnonce:jQuery('#_wpnonce').val()},
                    statusCode: { 
                        200: function (response) {
                            window.location.reload();
                        }},
                    error: function(){
                        showMessage( 'Error: A network error occured.', 'network_error', true, 10000 );
                        toggleAjaxIndicator( element_id );
                    }
                });
            }); // redo_initial_setup.click()
            
            jQuery('#manual_update').click(function(){
                toggleAjaxIndicator('#manual_update_cont');
                jQuery.ajax({
                    url: ajaxurl,
                    data: {action:'mbgf_youtube_connect_manual_feed_update',_wpnonce:jQuery('#_wpnonce').val()},
                    statusCode: { 
                        200: function (response) {
                            json = jQuery.parseJSON(response);
                            
                            hideAjaxIndicator('#manual_update_cont');
                            if ( typeof json.status !== 'undefined' && json.status === 'success' )
                                showMessage( 'The feed updated successfully.<p>If you triggered a manual update because of an error or something does not appear to be working properly, please be sure to let us know so we can fix it!</p>', 'manual_update', false, 10000 );
                            else
                                showMessage( 'The feed did not update successfully. The update method returned ' + json.error + '<p>If you triggered a manual update because of an error or something does not appear to be working properly, please be sure to let us know so we can fix it!</p>', 'manual_update_error', true, 10000 );
                        }},
                    error: function(){
                        showMessage( 'Error: A network error occured.', 'network_error', true, 10000 );
                        hideAjaxIndicator('#manual_update_cont');
                    }
                });
            }); // manual_update.click()
        });

        
    </script>
<?php  
    return $update;
} // end show_mb_youtube_page
