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
        if (!(check_admin_referer( 'mbgf-settings' ) && wp_verify_nonce( $_POST['_wpnonce'], 'mbgf-settings' )))
                die('Security check failed.');
        
        // An update has been submitted. Save the settings.
        $update = true;
        
        // Plugin update interval
        if (isset($_POST['settings-plugin_update_interval']) && in_array($_POST['settings-plugin_update_interval'], $settings['plugin_update_intervals'] ) ) {
            $settings['plugin_update_interval'] = $_POST['settings-plugin_update_interval'];
            $mb_globalfeed->save_settings();
            $mb_globalfeed->change_default_update_interval();
        }
        
    }
?>
<h1>Configuration Settings</h1>
<p>GlobalFeed controls some basic behaviour for its feeds, here you can configure<br />
some of this, however some feeds may override these settings.</p>
<form action="<?php  $_SERVER['REQUEST_URI'] ?>" method="post">
    <input type="hidden" name="_wpnonce" id="_wpnonce" value="<?php echo wp_create_nonce( 'mbgf-settings' ) ?>" />
    <fieldset class="optgrp"><legend> Feed Configuration</legend>
        <input type="hidden" name="_update" value="true" />
        <p><label for="settings-plugin_update_interval">Plugin update interval 
        <select name="settings-plugin_update_interval" id="settings-plugin_update_interval">
            <?php 
            foreach ( $settings['plugin_update_intervals'] as $key => $time ) 
                echo "<option" . ($settings['plugin_update_interval'] == $time ? ' selected="selected"' : '') . " value='$time'>$key</option>";
            ?>
        </select></label></p>
        
        <input type="submit" value="Save Settings" />
    </fieldset>
</form><small>Please note: If there are any settings you would like to see added to the GlobalFeed UI, please let us know!</small>

<?php 
    return $settings;
}