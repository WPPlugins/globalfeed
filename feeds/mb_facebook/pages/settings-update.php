<?php

/*
 * This is the main settings page for the plugin.
 */
function show_mb_facebook_page( &$feed_options ) {
    
// Import our requirements
/* @var $mb_globalfeed mb_globalfeed */
/* @var $mb_globalfeed_facebook_connect mb_globalfeed_facebook_connect */
/* @var $mbgfa_theme mbgfa_theme */
global $mb_globalfeed,$mb_globalfeed_facebook_connect,$mbgfa_theme;

wp_enqueue_script('jquery');
?>
<h2>Update Settings</h2>
<form>
    <input type="hidden" value="<?php echo wp_create_nonce( 'facebook-connect-settings_main' ) ?>" id="_wpnonce" />
    <div id="notices">
        <?php  // Show welcome Message
        if ( $feed_options['show_welcome_message'] == true ) :
            //$feed_options['show_welcome_message'] = false;
        ?>
            <div class="notice updated no-hide" ><?php _e('<p>Welcome to MB Facebook. You\'ll find the plugins\' main configuration here.</p><p>Please report any bugs you find by using the bug report feature at the top right of the page. Thank you for using MB GlobalFeed</p>') ?></div>
        <?php endif; // Welcome message ?>
    </div>
    <!-- Facebook Object Information  -->
    <fieldset id="fb_object_information" class="ajax_option optgrp"><legend><?php _e( 'Facebook ' . $feed_options['subscribe_object_type'] . ' Information' , $mb_globalfeed_facebook_connect->get_slug() ) ?></legend>
        <div class="leftopt">
            <?php $mbgfa_theme->ajaxIndicator(false, 'center'); ?>
        </div>
    </fieldset>
    
    <!-- Facebook App Information -->
    <fieldset id="fb_app_information" class="ajax_option optgrp"><legend><?php _e( 'Facebook App Information', $mb_globalfeed_facebook_connect->get_slug() ) ?></legend>
        <div class="leftopt">
            <?php $mbgfa_theme->ajaxIndicator(false, 'center'); ?>
        </div>
    </fieldset>    

    <!-- Reset Options -->
    <fieldset id="fb_reset_options" class="optgrp"><legend>Reset Feed</legend>
        <table>
        <tr id="reset_fb_auth_cont"><td><input type="button" value="Reset Facebook Authorization" id="reset_fb_auth" /></td><td><?php $mbgfa_theme->informative('This will re-request access codes from Facebook. Handy if you&apos;ve had to reset any or if authentication is failing.'); ?><?php $mbgfa_theme->ajaxIndicator(true); ?></td></tr>
        <tr id="redo_initial_setup_cont"><td><input type="button" value="Redo Initial Setup" id="redo_initial_setup" /></td><td><?php $mbgfa_theme->informative('This will bring up the initial setup wizard, however it will not reset any settings.'); ?><?php $mbgfa_theme->ajaxIndicator(true); ?></td></tr>
        <tr id="reset_feed_defaults_cont"><td><input type="button" value="Reset Feed Defaults" id="reset_feed_defaults" /></td><td><?php $mbgfa_theme->informative('This function completely resets the plugin to defaults.'); ?><?php $mbgfa_theme->ajaxIndicator(true); ?></td></tr>
        </table>
    </fieldset>
</form>
    <script type="text/javascript">
        jQuery(document).ready(function(){
            // Get ajax option groups
            get_fb_object( '<?php echo $feed_options['object_to_subscribe'] ?>' ,'#fb_object_information .leftopt' );
            get_fb_object( '<?php echo $feed_options['app_id'] ?>' ,'#fb_app_information .leftopt' );
            
            // Setup button events
            jQuery('#reset_fb_auth').click(function(){
                toggleAjaxIndicator('#reset_fb_auth_cont');
                jQuery.ajax({
                    url: ajaxurl,
                    data: {action:'mbgf_facebook_connect_renew_access_codes',_wpnonce:jQuery('#_wpnonce').val()},
                    statusCode: { 
                        200: function (response) {
                            if (response.responseText != undefined) 
                                response = jQuery.parseJSON(response.responseText);
                            else
                                response = jQuery.parseJSON(response);
                            
                            authWindow = window.open(response.activation_url,"fb_activation_window","location=0,status=0,scrollbars=0, width=750,height=500");
                            setAuthWindowChecker( '#reset_fb_auth_cont', function(){}, response.redirect_url );
                        }},
                    error: function(){
                        showMessage( 'Error: A network error occured.', 'network_error', true, 10000 );
                        toggleAjaxIndicator( element_id );
                    }
                });
            }); // reset_fb_auth.click()
            
            jQuery('#redo_initial_setup').click(function(){
                toggleAjaxIndicator('#redo_initial_setup_cont');
                jQuery.ajax({
                    url: ajaxurl,
                    data: {action:'mbgf_facebook_connect_redo_initial_setup',_wpnonce:jQuery('#_wpnonce').val()},
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
                toggleAjaxIndicator('#reset_feed_defaults_cont');
                
            }); // reset_feed_defaults.click()
        });
        
        function get_fb_object( object_id, element_id ) {
            jQuery.ajax({
                url: 'https://graph.facebook.com/' + object_id + "?metadata=1",
                data: {},
                statusCode: { 
                    200: function (response) {
                        // In some browsers response is on object, and in some its text...
                        if (response.responseText != undefined) 
                            response = jQuery.parseJSON(response.responseText);
                        else
                            response = jQuery.parseJSON(response);

                        toggleAjaxIndicator( element_id );
                        if (response == false){
                            showMessage( 'Error: The given Facebook ID was not found. Please try again.', 'fb_obj_not_found' , true);
                            setMsgRemove( 'fb_object_id','fb_obj_not_found' );
                            return false;
                        }

                        // Get the output and display it
                        jQuery( element_id ).css({display:'none'}).append( format_fb_obj( response ) );
                        jQuery( element_id ).fadeIn('fast');
                    },
                    404: function(){
                        showMessage( 'Error: The given Facebook ID was not found. Please try again.', 'fb_obj_not_found', true);
                        setMsgRemove( 'fb_object_id','fb_obj_not_found' );
                        toggleAjaxIndicator( element_id );
                    },
                    400: function(){
                        showMessage( 'Error: An error occured. Most likely you attempted to access a resource you don\'t have permission for.', 'fb_obj_permission_error', true );
                        setMsgRemove( 'fb_object_id','fb_obj_permission_error' );
                        toggleAjaxIndicator( element_id );
                    }}
            });
        }
        
    </script>
<?php  
    return false;
} // end show_mb_facebook_page
?>