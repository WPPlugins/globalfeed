<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 
 * @global mb_globalfeed $mb_globalfeed
 * @param type $settings
 * @return type 
 */
function show_mbgf_admin_page( &$settings ) {
    global $mb_globalfeed;
    
    $update = false;
    if (isset($_POST['_update']) && $_POST['_update'] == 'true'){
        if (!(check_admin_referer( 'mbgf-settings_presentation' ) && wp_verify_nonce( $_POST['_wpnonce'], 'mbgf-settings_presentation' )))
                die('Security check failed.');
        
        // An update has been submitted. Save the settings.
        $update = true;
        
        $settings['auto_print_feed_items_in_loop'] = isset($_POST['presentation-auto_print_feed_items_in_loop']) && $_POST['presentation-auto_print_feed_items_in_loop'] == 'on';
        $settings['post_content']['detect_content_links'] = isset($_POST['presentation-auto_detect_content_links']) && $_POST['presentation-auto_detect_content_links'] == 'on';
        $settings['media_display_mode'] = (isset($_POST['presentation-media_display_mode']) && $_POST['presentation-media_display_mode'] == 'on') ? 'embed' : 'smart';
        $settings['theme_show_avatar'] = isset($_POST['presentation-theme_show_avatar']) && $_POST['presentation-theme_show_avatar'] == 'on';
        $settings['autodetect_link_open_new_window'] = (isset($_POST['presentation-autodetect_link_open_new_window']) && $_POST['presentation-autodetect_link_open_new_window']  == 'on');
    }
?>
<h1>Presentation Settings</h1>
<p>GlobalFeed can present itself by integrating into the WordPress loop, or by using<br />
a widget or shortcode. Here you can change how it does this.</p>
<form action="<?php  $_SERVER['REQUEST_URI'] ?>" method="post">
    <input type="hidden" name="_wpnonce" id="_wpnonce" value="<?php echo wp_create_nonce( 'mbgf-settings_presentation' ) ?>" />
    <fieldset class="optgrp"><legend> Feed Display Settings</legend>
        <input type="hidden" name="_update" value="true" />
        <p><label for="presentation-auto_print_feed_items_in_loop"><input type="checkbox" <?php echo $settings['auto_print_feed_items_in_loop'] ? 'checked="checked"' : ''; ?> id="presentation-auto_print_feed_items_in_loop" name="presentation-auto_print_feed_items_in_loop" /> Enable feeds in the WordPress loop</label></p>
        <p><label for="presentation-auto_detect_content_links"><input type="checkbox" <?php echo $settings['post_content']['detect_content_links'] ? 'checked="checked"' : ''; ?> id="presentation-auto_detect_content_links" name="presentation-auto_detect_content_links" /> Attempt to detect and link to urls and names in post content (urls, twitter usernames, hashtags etc..)</label></p>
        <p><label for="presentation-theme_show_avatar"><input type="checkbox" <?php echo $settings['theme_show_avatar'] ? 'checked="checked"' : ''; ?> id="presentation-theme_show_avatar" name="presentation-theme_show_avatar" /> Show avatar in GlobalFeed themes</label></p>
        <p><label for="presentation-media_display_mode"><input type="checkbox" <?php echo $settings['media_display_mode'] == 'embed' ? 'checked="checked"' : ''; ?> id="presentation-media_display_mode" name="presentation-media_display_mode" /> Embed media in post content (images, videos etc..)</label></p>
        <p><label for="presentation-autodetect_link_open_new_window"><input type="checkbox" <?php echo $settings['autodetect_link_open_new_window'] ? 'checked="checked"' : ''; ?> id="presentation-autodetect_link_open_new_window" name="presentation-autodetect_link_open_new_window" /> Open GlobalFeed-generated links in a new window</label></p>
        
        <input type="submit" value="Save Settings" />
    </fieldset><fieldset class="optgrp"><legend> Shortcode Settings</legend>
        <p>To use GlobalFeed as a shortcode simply use the shortcode [globalfeed].</p>
        <p>The shortcode also has the following options:
        <ul class="ul-disc">
            <li>num_items: An integer number of feed items to show.</li>
            <li>feeds: A comma separated list of feeds, by feed slug.</li>
            <li>container_class: The class to apply to the containing div.</li>
            <li>include_blog_posts: Whether posts from the WordPress blog should be displayed.</li>
            <li>use_globalfeed_theme: The GlobalFeed theme to use. Currently only option is 'wide', 'fb' or 'default'.</li>
        </ul>
        
        </p>
    </fieldset>
</form><small>Please note: If there are any settings you would like to see added to the GlobalFeed UI, please let us know!</small>

<?php 
    return $settings;
}