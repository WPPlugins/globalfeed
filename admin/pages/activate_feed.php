<?php

/*
 * This is the feed activation template. It is called when a feed is selected in 
 * the admin but not yet active.
 */

global $mb_globalfeed;
?>

<div id="activate_feed">
    <img class="main_image" src="<?php echo plugins_url('images/lock.jpg', __FILE__) ?>" title="Feed Deactivated"/>
    <div class="content">
        <h1><?php _e('Activate Feed'); ?></h1>
        <p><?php _e('This feed has not yet been activated. In order to use it click on the activate button below.'); ?></p>
        <p class="disclaimer"><?php _e("Please note that GlobalFeed is open to 3rd party developers, and assumes no responsibility for performance or other issues created by third party feeds. Feeds are run with the same capabilities as plugins. Do not run code you don't trust.", 'mb_globalfeed'); ?></p>
        <form method="post" action='<?php menu_page_url('globalfeed') ?>&mbgf_page_type=feed&mbgf_feed_slug=<?php echo $mb_globalfeed->admin_instance()->current_feed_slug() ?>'>
            <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'globalfeed_admin' ) ?>" />
            <input type="submit" alt="Activate Feed" title="Activate Feed" class="button-primary" value="Activate Feed" name="activate_feed-<?php echo $mb_globalfeed->admin_instance()->current_feed_slug() ?>" />
        </form>
    </div>
</div>