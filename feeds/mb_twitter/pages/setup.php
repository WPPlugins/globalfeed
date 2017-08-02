<?php

/*
 * This page is displayed when the user is initially setting up the MB_Twitter Feed.
 * 
 * This page takes the user through the process of obtaining authorization to 
 * access their twitter, and then brings them to the settings page.
 * 
 * @todo Externalize the javascript on this page.
 */
/* @var $mb_globalfeed mb_globalfeed */
/* @var $mb_globalfeed_twitter_connect mb_globalfeed_twitter_connect */

function show_mb_twitter_page( $feed_options ) {
    
global $mb_globalfeed,$mb_globalfeed_twitter_connect;

?>
<h2><?php _e('Twitter Connect Initial Setup',$mb_globalfeed_twitter_connect->get_slug()); ?></h2>
<form id="setup-form">
    <input type="hidden" id="_wpnonce" value="<?php echo wp_create_nonce( 'twitter-connect-admin' ); ?>" />
    <input type="hidden" id="tw_api_url" value="<?php echo $mb_globalfeed_twitter_connect->apiurl(); ?>" />
    <div id="notices"></div>
    <div id="step-1">
        <p>Welcome to the initial setup for Twitter Connect. We need a bit of information before you get started:</p>
        <p>All we need to know is the Twitter object whose updates you would like to appear in Globalfeed.</p>
        <p>You can enter either the Twitter object ID, or the screen name.</p>
        <table class="form-table">
            <tr>
                <th><?php _e('Twitter Object ID:',$mb_globalfeed_twitter_connect->get_slug()) ?></th>
                <td><input type="text" name="tw_object_id" class="auth-form-input" id="tw_object_id" value="<?php echo $feed_options['object_to_subscribe']; ?>"/></td>
            </tr> 
            <tr id="validate_button_container">
                <th></th>
                <td><input type="button" class="button-primary" id="validate_button" value="Validate" /><img alt="" class="ajax-loading-icon" src="<?php bloginfo('wpurl') ?>/wp-admin/images/wpspin_light.gif" /></td>
            </tr>
        </table>
        <div id="tw_obj_confirm" class="hidden-step">
            <h4><?php _e('Is this the correct object below?'); ?></h4>
            <div id="confirm_tw_obj_id" class="fade notice"></div>
            <div id="confirm_buttons" >
                <input type="button" class="button-secondary" id="confirm_id_no" value="No" />
                <input type="button" class="button-primary" style="margin-left:40px" id="confirm_id_yes" value="Yes" /><img alt="" class="ajax-loading-icon" src="<?php bloginfo('wpurl') ?>/wp-admin/images/wpspin_light.gif" />
            </div>
        </div>
    </div>
    <p><small>If your username contains only numbers, prefix it with a question mark (?)</small></p>
</form>
<script type="text/javascript">
    var error_count = 0;
    var tw_obj_id = null;
    var tw_obj_type = null;
    
    jQuery(document).ready(function(){
        _currentStep = 1;
        in_wizard = true;
        setupStep(_currentStep);
    });
    
    function setupStep( step ) {
        switch( step ){
            case 1:
                if ( !jQuery('#step-1').hasClass('setup') ) {
                    jQuery('#validate_button').attr("disabled", false);
                    jQuery('#confirm_id_no').click(function(){
                        jQuery('#tw_obj_confirm').fadeOut('fast', function () {
                            jQuery(this).addClass('hidden-step');
                            showMessage('Please try again.', 'tryagain', false, 10000);
                            jQuery('#validate_button').attr("disabled", false);
                        });
                        jQuery('#step-1>table').show().fadeIn('fast');
                    });
                    jQuery('#confirm_id_yes').click(function(){
                        jQuery(this).attr('disabled', '');
                        toggleAjaxIndicator( '#confirm_buttons' );
                        
                        jQuery.post(ajaxurl, {action:'mbgf_twitter_connect_set_tw_obj_id',tw_object_id:mb_twitter_info.tw_obj_id, mb_twitter_finish_setup:1 ,_wpnonce:jQuery('#_wpnonce').val()}, function(response){
                            if (response.responseText != undefined) 
                                response = jQuery.parseJSON(response.responseText);
                            else
                                response = jQuery.parseJSON(response);
                            
                            if (response.num_feed_items_retrieved != undefined) {
                                // Setup is complete, go to main configuration.
                                window.location = window.location;
                                //window.location.reload();
                            } else {
                                // Setup failed somewhere along the way.
                                showMessage('Error: An error was encountered saving setup info.\n\n' + response, 'setupError', true);
                            }
                        });
                    });
                    
                    var validate = function (e) {
                        e.preventDefault();
                        // The user has begun object validation
                        jQuery('#validate_button').attr("disabled", true);
                        
                        // Get the object
                        
                        req = get_tw_object( jQuery('#tw_object_id').val(), '#confirm_tw_obj_id') 
                        req.success(function () {
                            jQuery('#step-1>table').hide('fast', function () {jQuery(this).hide()});
                            jQuery( '#tw_obj_confirm' ).removeClass('hidden-step').show( 'fast' );
                        });
                        req.error(function(){
                            showMessage( 'Error: The given Twitter ID or Screen Name was not found or failure connecting to Twitter. Please try again.', 'tw_obj_not_found', true);
                            setMsgRemove( 'tw_object_id','tw_obj_not_found' );
                            toggleAjaxIndicator( '#step-1' );
                            jQuery('#step-1>table').show('fast');
                            jQuery('#validate_button').attr("disabled", false);
                        })
                        return false;
                    }
                    
                    jQuery('#validate_button').click(validate);
                    jQuery('#setup-form').submit(validate);
                    jQuery('#step-1').addClass('setup');
                    
                    
                }
                break; // End of step 2
        }

    }
    
    function nextStep() {
        loadStep( _currentStep + 1 );
    }
    
    function loadStep( step ) {
        jQuery('#step-' + _currentStep).fadeOut('fast', function(){
            // Hide old visual elements
            jQuery(this).hide().addClass('hidden-step');
            hideAjaxIndicator( '#step-' + _currentStep );
            hideAllMessages();
            
            // Show next step
            _currentStep = step;
            setupStep( _currentStep );
            jQuery('#step-' + _currentStep).fadeIn('fast', function(){
                jQuery(this).removeClass('hidden-step');
            });
            //jQuery.cookie('mbgf_twitter_connect_curr_step', _currentStep, { path: '/wp-admin'});
        });
    }
    
    function saveStepStatus() {
        //jQuery.cookie('mbgf_twitter_connect_curr_step', params, { path: '/wp-admin'});
    }
    
    function lastStep() {
        jQuery('#step-' + _currentStep).fadeOut('fast', function(){
            // Hide old visual elements
            jQuery(this).hide().addClass('hidden-step');
            hideAjaxIndicator( '#step-' + _currentStep );
            hideAllMessages();
            
            // Show next step
            _currentStep--;
            jQuery('#step-' + _currentStep).fadeIn('fast', function(){
                jQuery(this).removeClass('hidden-step');
            });
        });
    }
</script>

<?php 
    // Clean up a bit...
    return false;
} // end show_mb_twitter_page
?>