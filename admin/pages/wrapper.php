<?php

/*
 * This is the main wrapper for the GlobalFeed plugin. It outputs the structure
 * of the GlobalFeed admin page.
 * 
 * @todo Add logic that determines the settings/configuration page status's. (IE any config problems)
 */
// Import our requirements
/* @var $mb_globalfeed mb_globalfeed */
/* @var $mbgfa_theme mbgfa_theme */
global $mb_globalfeed;
$current_page_slug = $mb_globalfeed->admin_instance()->current_admin_page();
$mb_globalfeed->include_admin_theming();
global $mbgfa_theme;

?>
<div class="wrap">
    <div id="bug_report_top"><a href="http://globalfeed.michaelblouin.ca/bug-reports" id="bug_report" target="_blank" title="Report a Bug" ><?php $mbgfa_theme->bug(null, 24); ?></a></div>
    <div id="mbgf_logo"><?php $mbgfa_theme->globalfeed('', 32) ?></div><h1><?php _e('MB GlobalFeed', 'mb_globalfeed'); ?></h1>
    <div class="mbgf_main round-3">
        <div class="mbgf_top_menu"></div>
        <div class="mbgf_sidebar">
            <h3><?php _e('Settings', 'mb_globalfeed') ?></h3>
                <?php $settings_status = 'ok' ?>
            <a href="<?php menu_page_url('globalfeed') ?>" class="mbgf_status_<?php echo $settings_status.' '; echo in_array($mb_globalfeed->admin_instance()->current_admin_page(), $mb_globalfeed->admin_instance()->mbgf_admin_pages()) ? 'active' : '' ?>">GlobalFeed Settings</a>
            <h3><?php _e('Feeds', 'mb_globalfeed') ?></h3>
            <?php 

            // Get the list of available feeds, and print them out.
            foreach ($mb_globalfeed->admin_instance()->get_feed_list() as $feed) {
                
                // Get the class for the feed
                $compatible = $mb_globalfeed->feed_compatible($feed);
                if ( $compatible !== true ) {
                    $class = 'incompatible';
                    $title = implode(' ', $compatible);
                } else {
                    $class = $mb_globalfeed->feed_activated($feed['Slug']) ? 'activated' : 'deactivated';
                    $title = str_replace('"', "'", $feed['Description']);
                }

                if ( $mb_globalfeed->admin_instance()->current_feed_slug() == $feed['Slug'] )
                    $class .= ' active';
                ?>
            <a href="<?php menu_page_url('globalfeed') ?>&mbgf_page_type=feed&mbgf_feed_slug=<?php echo $feed['Slug']; ?>" title="<?php _e($title) ?>" class="mbgf_feed_<?php echo $class ?>"><?php _e($feed['Name'], 'mb_globalfeed'); ?></a>
                <?php
            }
            ?>
        </div>
        <div class="mbgf_wrap round-3">
            <div class="mbgf_wrap_header top-round-3">
                <?php 
                if ($mb_globalfeed->admin_instance()->current_page_type() != 'default' && $mb_globalfeed->feed_activated($mb_globalfeed->admin_instance()->current_feed_slug())) {
                ?>
                <form action="<?php menu_page_url('globalfeed') ?>" method="post">
                    <input type="hidden" value="<?php echo wp_create_nonce( 'globalfeed_admin' ); ?>" name="_wpnonce" />
                    <input type="hidden" value="<?php echo $mb_globalfeed->admin_instance()->current_feed_slug(); ?>" name="feed_slug" />
                    <input type="submit" value="<?php _e('Deactivate'); ?>" name="deactivate_feed" class="deactivate_button button-secondary" />
                </form>
                <?php
                }
                ?>
                <?php
                    if ($mb_globalfeed->admin_instance()->current_feed_slug())
                        $menu_items = (array) apply_filters( 'mbgf_' . $mb_globalfeed->admin_instance()->current_page_type() . '_menu-' . $mb_globalfeed->admin_instance()->current_feed_slug(), array() );
                    else
                        $menu_items = (array) apply_filters( 'mbgf_' . $mb_globalfeed->admin_instance()->current_page_type() . '_menu', array() );
                    
                    $menu_url = menu_page_url('globalfeed', false)  ;
                    if ( $mb_globalfeed->admin_instance()->current_page_type() == 'feed' )
                        $menu_url .= "&mbgf_feed_slug={$mb_globalfeed->admin_instance()->current_feed_slug()}&mbgf_page_type=feed&mbgf_admin_page=";
                    else
                        $menu_url .= '&mbgf_page_type=' . $mb_globalfeed->admin_instance()->current_page_type() . '&mbgf_admin_page=';
                    
                    //var_dump(  'mbgf_' . $mb_globalfeed->admin_instance()->current_page_type() . '_menu-' . $current_page_slug );
                    foreach ($menu_items as $page_slug => $page_name) {
                        echo "<h3><a href='{$menu_url}{$page_slug}' title='$page_name'>$page_name</a></h3>";
                    }
                ?>
            </div>
            <?php
            
            // Hand over page execution to the more specific page template
            $mb_globalfeed->admin_instance()->show_admin_page_content(); 
            
            ?>
        </div>
    </div>
    <div class="clear"></div>
    <?php  
    if (class_exists('mbgfa_theme')) {
        global $mbgfa_theme;
        $mbgfa_theme->footer();
    }
        
    ?>
</div><!-- Thats a wrap! -->