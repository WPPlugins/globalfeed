<?php

/*
 * This page is displayed when the user is initially setting up the MB_Facebook Feed.
 * 
 * This page takes the user through the process of obtaining authorization to 
 * access their facebook, and then brings them to the settings page.
 * 
 * @todo Externalize the javascript on this page.
 */
/* @var $mb_globalfeed mb_globalfeed */
/* @var $mb_globalfeed_facebook_connect mb_globalfeed_facebook_connect */

function show_mb_facebook_page( $feed_options ) {
    
global $mb_globalfeed,$mb_globalfeed_facebook_connect;

?>
<h2><?php _e('Facebook Connect Initial Setup',$mb_globalfeed_facebook_connect->get_slug()); ?></h2>
<form id="setup-form">
    <input type="hidden" id="activation_url" value="<?php echo $mb_globalfeed_facebook_connect->get_client_auth_redirect_url(); ?>" />
    <input type="hidden" id="_wpnonce" value="<?php echo wp_create_nonce( 'facebook-connect-settings_main' ); ?>" />
    <input type="hidden" id="activation_redirection_url" value="<?php get_bloginfo('wpurl').'/?'.$mb_globalfeed_facebook_connect->get_queryvar(); ?>" />
    <input type="hidden" id="fb_graph_url" value="<?php echo $mb_globalfeed_facebook_connect->graphurl(); ?>" />
    <div id="notices"></div>
    <div id="step-1">
        <p>Welcome to the initial setup for Facebook Connect. We need a bit of information before you get started:</p>
        <p>First, you'll need to setup a Facebook app, and get its app id and app secret.</p>
        <table class="form-table">
            <tr>
                <th><?php _e('Facebook App ID:',$mb_globalfeed_facebook_connect->get_slug()) ?></th>
                <td><input type="text" name="app_id" class="auth-form-input" id="app_id" value="<?php echo $feed_options['app_id']; ?>"/></td>
            </tr>    
            <tr>
                <th><?php _e('Facebook App Secret:',$mb_globalfeed_facebook_connect->get_slug()) ?></th>
                <td><input type="text" name="app_secret" class="auth-form-input" id="app_secret" value="<?php echo $feed_options['app_secret']; ?>" /></td>
            </tr>    
            <tr>
                <th></th>
                <td><input type="button" class="button-primary" id="authorize_button" value="Authorize" /><img alt="" class="ajax-loading-icon" src="<?php bloginfo('wpurl') ?>/wp-admin/images/wpspin_light.gif" /></td>
            </tr>
        </table>
    </div>
    <div id="step-2" class="hidden-step">
        <p><strong>Authorization Complete!</strong></p>
        <p>Now we need to know the Facebook object whose updates you would like to appear in Globalfeed.</p>
        <p>Here you can enter a Facebook Object ID, a persons username, a pages' username etc... </p>
        <table class="form-table">
            <tr>
                <th><?php _e('Facebook Object ID:',$mb_globalfeed_facebook_connect->get_slug()) ?></th>
                <td><input type="text" name="fb_object_id" class="auth-form-input" id="fb_object_id" value="<?php echo $feed_options['object_to_subscribe']; ?>" /></td>
            </tr> 
            <tr id="validate_button_container">
                <th><input type="button" class="button-secondary last_step_button" value="Back" /></th>
                <td><input type="button" class="button-primary" id="validate_button" value="Validate" /><img alt="" class="ajax-loading-icon" src="<?php bloginfo('wpurl') ?>/wp-admin/images/wpspin_light.gif" /></td>
            </tr>
        </table>
        <div id="fb_obj_confirm" class="hidden-step">
            <h4><?php _e('Is this the correct Facebook object below?'); ?></h4>
            <div id="confirm_fb_obj_id" class="fade notice"></div>
            <div id="confirm_buttons" >
                <input type="button" class="button-secondary" id="confirm_id_no" value="No" />
                <input type="button" class="button-primary" style="margin-left:40px" id="confirm_id_yes" value="Yes" /><img alt="" class="ajax-loading-icon" src="<?php bloginfo('wpurl') ?>/wp-admin/images/wpspin_light.gif" />
            </div>
        </div>
    </div>
</form>
<script type="text/javascript">
    var error_count = 0;
    var fb_obj_id = null;
    var fb_obj_type = null;
    var fb_obj_is_error = false;
    
    jQuery(document).ready(function(){
        _currentStep = 1;
        in_wizard = true;
        setupStep(_currentStep);
    });
    
    function authorize(){
        // Begin authorization
        var app_id = jQuery('#app_id').val();
        var app_secret = jQuery('#app_secret').val();
        var app_id_match = new RegExp(/^[0-9]+$/);
        var app_secret_match = new RegExp(/^[a-zA-Z0-9]+$/);
        if (app_id_match.exec( app_id ) == null){
            showMessage('You must provide a valid app_id (must be only numbers)', 'app_id', true);
            setMsgRemove( 'app_id', 'app_id' );
            return false;
        } else if (app_secret_match.exec( app_secret ) == null){
            showMessage('You must provide a valid app_secret (only letters and numbers)', 'app_secret', true);
            setMsgRemove( 'app_secret', 'app_secret' );
            return false;
        } else {
            hideMessage( 'app_id' );
            hideMessage( 'app_secret' );
        }

        // Disable the button and begin auth
        toggleAjaxIndicator( '#step-1' );
        hideAllMessages();
        jQuery('#authorize_button').attr("disabled", true);

        // In order to get passed popup blockers, we open the AuthWindow now. In the future, this should pop up a temporary loading page, however we aren't that fancy yet.
        authWindow = window.open('',"fb_activation_window","location=0,status=0,scrollbars=0, width=750,height=500");

        // Post the app_id and secret to the server for use in the auth process.
        jQuery.post(ajaxurl, {action:'mbgf_facebook_connect_set_app_info',app_id:app_id,app_secret:app_secret,_wpnonce:jQuery('#_wpnonce').val()}, function(response){
            //authWindow = window.open(jQuery('#activation_url').val() + '&client_id=' + jQuery('#app_id').val(), );
            authWindow.location.href = jQuery('#activation_url').val() + '&client_id=' + jQuery('#app_id').val();
            setAuthWindowChecker( '#step-1', function(){jQuery('#authorize_button').attr("disabled", false)}, jQuery('#activation_redirection_url').val() );
        });
    }
    
    function validate() {
        jQuery('#validate_button').attr("disabled", true);
        // Check input
        var fb_object_id_match = new RegExp(/^[a-zA-Z0-9]*$/);
        var fb_object_id = jQuery('#fb_object_id').val();
        if (fb_object_id_match.exec( fb_object_id ) == null){
            showMessage('You must provide a valid object id (must be only numbers)', 'fb_object_id', true);
            setMsgRemove( 'fb_object_id', 'fb_object_id' );
            jQuery('#validate_button').attr("disabled", false);
            return false;
        } else {
            hideMessage( 'fb_object_id' );
        }

        jQuery('#fb_obj_confirm #confirm_fb_obj_id').children().fadeOut('fast', function () {jQuery(this).empty();});
        toggleAjaxIndicator( '#step-2' );
        hideAllMessages();

        req = get_fb_object( fb_object_id, '#confirm_fb_obj_id' );
        req.complete(function(response){
            console.log(response);
            if ( response.status != 200 ) {
                    toggleAjaxIndicator( '#step-2' );
                    jQuery('#validate_button').attr("disabled", false);
                    return false;
            }

            jQuery('#step-2>table').fadeOut('fast', function () {
                jQuery(this).hide();
                jQuery('#confirm_fb_obj_id').show()
                hideAjaxIndicator( '#step-2' );
                jQuery('#fb_obj_confirm').removeClass('hidden-step').show('fast');
            });
        });
    }
    
    function setupStep( step ) {
        switch( step ){
            case 1:
                // Setup the authorization button for step 1
                jQuery('#authorize_button').attr("disabled", false).click(authorize);
                break; // End of step 1
            case 2:
                if ( !jQuery('#step-2').hasClass('setup') ) {
                    jQuery('#validate_button').attr("disabled", false);
                    jQuery('#confirm_id_no').click(function(){
                        jQuery('#fb_obj_confirm').fadeOut('fast', function () {
                            jQuery(this).addClass('hidden-step');
                            showMessage('Please try again.', 'tryagain', false, 10000);
                            jQuery('#validate_button').attr("disabled", false);
                            hideAjaxIndicator( '#step-2' );
                        });
                        jQuery('#step-2>table').show().fadeIn('fast');
                    });
                    jQuery('#confirm_id_yes').click(function(){
                        jQuery(this).attr('disabled', '');
                        toggleAjaxIndicator('#fb_obj_confirm');
                        jQuery.post(ajaxurl, {
                            action:'mbgf_facebook_connect_set_fb_obj_id',
                            fb_object_id:fb_obj_id, 
                            fb_object_type:fb_obj_type,
                            mb_facebook_finish_setup:1 ,
                            _wpnonce:jQuery('#_wpnonce').val()
                        }, function(response){
                            if (response.responseText != undefined) 
                                response = jQuery.parseJSON(response.responseText);
                            else
                                response = jQuery.parseJSON(response);
                            
                            if (response.num_feed_items_retrieved != undefined) {
                                // Setup is complete, go to main configuration.
                                window.location = window.location;
                            } else {
                                // Setup failed somewhere along the way.
                                showMessage('<p>Error: An error was encountered along the way while connecting with Facebook.</p><p>Please ensure that you have enterred the correct information and you have permission to access this object.', 'setupError', true);
                                toggleAjaxIndicator('#fb_obj_confirm');
                                jQuery('#confirm_id_yes').removeAttr('disabled');
                            }
                        });
                    });
                    jQuery('#validate_button').click(validate);
                    jQuery('#step-2').addClass('setup');
                }
                break; // End of step 2
        }
    }
    
    jQuery('#setup-form').submit(function (e) {
        //e.preventDefault();
        console.log(_currentStep);
        switch (_currentStep){
            case 1:
                authorize();
                break;
            case 2:
                validate();
                break;
        }
    });

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
            //jQuery.cookie('mbgf_facebook_connect_curr_step', _currentStep, { path: '/wp-admin'});
        });
    }
    
    function saveStepStatus() {
        //jQuery.cookie('mbgf_facebook_connect_curr_step', params, { path: '/wp-admin'});
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
} // end show_mb_facebook_page
?>