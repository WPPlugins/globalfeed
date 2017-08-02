<?php

/*
 * This is the main settings page for the plugin.
 */
function show_mbgf_rss_page( &$feed_options ) {
    
// Import our requirements
/* @var $mb_globalfeed mb_globalfeed */
/* @var $mbgf_rss mb_globalfeed_rss_connect */
/* @var $mbgfa_theme mbgfa_theme */
global $mb_globalfeed,$mbgf_rss,$mbgfa_theme;

$update = false;
if (isset($_POST['_update'])){
    if (!(check_admin_referer( 'mb-rss-admin' ) && wp_verify_nonce( $_POST['_wpnonce'], 'mb-rss-admin' )))
            die('Security check failed.');

    // An update has been submitted. Save the settings.
    $update = true;

    $feed_options['override_post_time_on_timezone_discrepency'] = isset($_POST['general-override_post_time_on_timezone_discrepency']) && $_POST['general-override_post_time_on_timezone_discrepency'] == 'on';
}

$mbgfa_theme->queue_js_client_tools();
?>
<h2><?php _e('MB RSS') ?></h2>
<form action="<?php $_SERVER['REQUEST_URI'] ?>" method="post" id="settings-form">
    <?php if (function_exists('wp_nonce_field')){
        echo wp_nonce_field('mb-rss-admin');
    } ?>
    <div id="notices">
<?php  // Show welcome Message
if ( $feed_options['show_welcome_message'] == true ) :
    //$feed_options['show_welcome_message'] = false;
?>
    <div class="notice updated no-hide" ><?php _e('<p>Welcome to MB RSS. To start, simply enter in the URL to an RSS feed and click the + button.</p><p>Please report any bugs you find by using the bug report feature at the top right of the page. Thank you for using MB GlobalFeed!</p>') ?></div>
<?php endif; // Welcome message 
        // Show saved message
        if ( $update ) : ?>
            <div class="notice updated no-hide" ><?php _e('<p>You\'re settings were successfully saved.</p>') ?></div>
        <?php endif; // Welcome message ?>
    </div>
    <!-- RSS Object Information  -->
    <fieldset id="rss_username_information" class="ajax_option optgrp"><legend><?php _e( 'RSS Feeds' , $mbgf_rss->get_slug() ) ?></legend>
        <div class="leftopt">
            <table id="feed_list">
                <tbody>
                    <?php
                    foreach ($feed_options['feeds_to_subscribe'] as $url => $feed) {
                        echo "<tr><td class='feed_description'><strong>{$feed['title']}</strong><br />{$feed['description']}</td><td class='icons'><a href='$url' class='remove_feed'>{$mbgfa_theme->minus('Remove Feed',16,true)}</a>{$mbgfa_theme->ajaxIndicator('Working...', true)}</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <p class="add_feed"><?php _e('Add Feed:') ?><br /><input type="text" name="feed_to_add" /><a href='#'><?php $mbgfa_theme->add( __('Add Feed', 'mb_globalfeed'), 16 );$mbgfa_theme->ajaxIndicator('Working...'); ?></a></p>
        </div>
    </fieldset>
    <fieldset id="rss_general_options" class="optgrp"><legend>General Options</legend>
        <table>
            <p>Note: If you are encountering an issue wherein posts are showing up late with an incorrect date/time and/or WordPress is marking posts as "future", enabling the option below may fix any timezone handling problems.</p>
            <p><label for="general-override_post_time_on_timezone_discrepency"><input type="checkbox" <?php echo $feed_options['override_post_time_on_timezone_discrepency'] ? 'checked="checked"' : ''; ?> id="general-override_post_time_on_timezone_discrepency" name="general-override_post_time_on_timezone_discrepency" /> Override post date/time if the time is in the future</label></p>
            
            <input name="_update" type="submit" value="Save Settings" />
        </table>
    </fieldset>
    <!-- Reset Options -->
    <fieldset id="rss_reset_options" class="optgrp"><legend>Reset Feed</legend>
        <table>
            <tr id="manual_update_cont"><td><input type="button" value="Update Feed Manually" id="manual_update" /></td><td><?php $mbgfa_theme->informative('This will tell the feed to update, typically usefull if the feed is not updating automatically. <p>P.s. If things arent working right, make sure to let us know so we can fix it!</p>'); ?><?php $mbgfa_theme->ajaxIndicator(true); ?></td></tr>
            <tr id="reset_feed_defaults_cont"><td><input type="button" value="Reset Feed Defaults" id="reset_feed_defaults" /></td><td><?php $mbgfa_theme->informative('This function completely resets the plugin to defaults.'); ?><?php $mbgfa_theme->ajaxIndicator(true); ?></td></tr>
        </table>
    </fieldset>
    
    <div id="multiple_feeds_selector" style="display:none;">
        <h3>Multiple feeds were found at that url...</h3>
        <p>Which would you like to import?</p>
        <ul class="feed_list"></ul>
    </div>
</form>
    <script type="text/javascript">
        jQuery(document).ready(function(){
            var submit = function(e){
                e.preventDefault();
                // Disable the element for checking...
                jQuery(this).attr('disabled','');
                jQuery('.add_feed input').attr('disabled', true);
                toggleAjaxIndicator('.add_feed');
                
                urlcheck = new RegExp(/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/i);
                checkUrl = function ( url ) {
                    return urlcheck.exec(url);
                }
                
                // Apply the checking pattern...
                
                if ( !checkUrl(jQuery('.add_feed input').val()) ){
                    showMessage('The url entered does not appear correct.', 'badUrl', false, 10000);
                    jQuery('.add_feed input').removeAttr('disabled');
                    hideAjaxIndicator('.add_feed');
                    return;
                }
                var do_add_feed = function ( feed_url, cb ) {
                    if ( typeof feed_url === 'undefined' ) 
                        feed_url = jQuery('.add_feed input');
                    
                    feed_info = add_feed( feed_url );
                    if ( feed_info != false ) {
                        feed_info.success(function (iresponse){
                            try {
                                if (iresponse.responseText != undefined) 
                                    response = jQuery.parseJSON(iresponse.responseText);
                                else
                                    response = jQuery.parseJSON(iresponse);
                            } catch(err) {
                                showMessage('Error: An error was encountered saving the feed. ' + iresponse, 'saveError', true);
                                hideAjaxIndicator('.add_feed');
                                return;
                            }

                            if ( typeof(response) == "undefined" || response == null || (response.title == null && response.feeds_found == null) ){
                                showMessage('Error: An error was encountered saving the feed. ' + response, 'saveError', true);
                                hideAjaxIndicator('.add_feed');
                                return;
                            }

                            if ( response.title != null ) {
                                // The feed was successfully parsed
                                feed_item = jQuery("<tr><td class='feed_description'><strong>" + response.title + "</strong><br />" + response.description + "</td><td class='icons'><a href='" + response.feed_url + "' class='remove_feed'><?php $mbgfa_theme->minus('Remove Feed',16);?></a><?php $mbgfa_theme->ajaxIndicator('Working...');?></td></tr>");
                                jQuery('#feed_list tbody').append(feed_item);
                                
                                setupRemoveHandler(jQuery('#feed_list tbody tr:last-child a'));
                                hideAjaxIndicator('.add_feed');
                                jQuery('.add_feed input').val('');
                                
                                if ( typeof cb !== 'undefined' )
                                    cb();
                            } else {
                                // Multiple feeds were found -- ask the user which they want
                                jQuery('#multiple_feeds_selector .feed_list').empty();
                                
                                var c = 0;
                                for ( i in response.feeds_found ) {
                                    jQuery('#multiple_feeds_selector .feed_list').append(
                                        '<li><label for="found_feed_' + c + '"><input id="found_feed_' + c + '" name="found_feed_' + c + '" type="checkbox" value="' + response.feeds_found[i].url + '" /> ' + response.feeds_found[i].title + "</label><?php echo $mbgfa_theme->ajaxIndicator('Getting Feed...', true); ?></li>"
                                    );
                                    
                                    c++;
                                }
                                
                                jQuery('#multiple_feeds_selector .feed_list li input').change(function () {
                                    var parent = jQuery(this).parents('li');
                                    if ( checkUrl(jQuery(this).val()) ) {
                                        jQuery(this).attr('disabled', true);
                                        toggleAjaxIndicator(jQuery(this).parents('li'));
                                        do_add_feed( jQuery(this).val(), function () {
                                            parent.fadeOut(function () {
                                                jQuery(this).remove();
                                                if ( jQuery('#multiple_feeds_selector .feed_list').children('li').length === 0 )
                                                    jQuery.fancybox.close();
                                            });
                                        } );
                                    } else
                                        parent.remove();
                                });
                                
                                jQuery.fancybox.open({ content: jQuery('#multiple_feeds_selector'), beforeClose: function () {hideAjaxIndicator('.add_feed');} });
                            }
                        });
                    }
                };
                
                do_add_feed( jQuery('.add_feed input').val() );
                jQuery('.add_feed input').removeAttr('disabled');
                return false;
            }
            
            jQuery('.add_feed a').click(submit);
            jQuery('#settings-form').submit(function (e) {
                if ( jQuery('.add_feed input').is(':focus') ) {
                    e.preventDefault();
                    submit(e);
                    return false;
                }
            })
            setupRemoveHandler();

            jQuery('#reset_feed_defaults').click(function(){
                toggleAjaxIndicator('#reset_feed_defaults_cont');
                jQuery(this).attr('disabled', true);
                
                jQuery.ajax({
                    url: ajaxurl,
                    data: {action:'mbgf_rss_reset_feed_defaults',_wpnonce:jQuery('#_wpnonce').val()},
                    statusCode: { 
                        200: function (response) {
                            window.location.reload();
                        }},
                    error: function(){
                        showMessage( 'Error: A network error occured.', 'network_error', true, 10000 );
                        toggleAjaxIndicator( element_id );
                    }
                });
            }); // reset_feed_defaults.click()
            
            jQuery('#manual_update').click(function(){
                toggleAjaxIndicator('#manual_update_cont');
                jQuery.ajax({
                    url: ajaxurl,
                    data: {action:'mbgf_rss_manual_feed_update',_wpnonce:jQuery('#_wpnonce').val()},
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

        function setupRemoveHandler( item ) {
            if ( typeof(item) === 'undefined' ) {
                jQuery('.remove_feed').click(function(){
                    jQuery(this).hide();
                    toggleAjaxIndicator(jQuery(this).parent());
                    req = remove_feed(jQuery(this).attr('href'), jQuery(this));
                    return false;
                });    
            } else {
                item.click(function(){
                    jQuery(this).hide();
                    toggleAjaxIndicator(jQuery(this).parent());
                    req = remove_feed(jQuery(this).attr('href'), jQuery(this));
                    return false;
                });    
            }
        }
        
    </script>
<?php  
    return $update;
} // end show_mb_rss_page
