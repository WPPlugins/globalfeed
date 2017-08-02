<?php

/*
 * This file outputs the main GlobalFeed admin page when called.
 * 
 */
function show_mbgf_admin_page( &$settings ) {
// We should already be in a suitable sized wrapper element.
// However, check that were in the Globalfeed Admin
global $mb_globalfeed;
if ( ! $mb_globalfeed->admin_instance() ) // Should only be set if were in plugin admin
    return;

$mb_globalfeed->include_admin_theming();
global $mbgfa_theme;
?>
<h1><?php _e('Welcome to GlobalFeed', 'mb_globalfeed') ?></h1>
<p>Thank you for using this software. Please report any bugs you may find.</p>
<h1><?php _e('Getting Started', 'mb_globalfeed') ?></h1>
<p>In order to get started with GlobalFeed, begin by clicking on a feed in the <br />
menu on the left and activating it. Then follow the feeds' specific configuration <br />
options.</p>
<p>If you would like to change the way that GlobalFeed presents itself on your site,<br />
try going over to the Presentations options panel above. (Ie if you want to stop<br />
GlobalFeed from inserting feeds into the main loop etc...)</p>
<h1><?php _e('Bug Reports/Feature Requests', 'mb_globalfeed'); ?></h1>
<p>To report a bug or submit a feature request, simply click on the <?php $mbgfa_theme->bug(null, 16); ?> icon<br />
in the top right-hand corner and fill out the form.</p>
<p>You may also submit a report on the WordPress plugin page. </p>
<small>Please note: If there are any settings you would like to see added to the GlobalFeed UI, please let us know!</small>
<?php } ?>