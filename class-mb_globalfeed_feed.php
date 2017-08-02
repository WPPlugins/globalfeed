<?php
/*
  Feed Name: Example Feed
  Feed URI: http://MichaelBlouin.ca
  Version: v0.10
  Author: <a href="http://www.michaelblouin.ca/">Michael Blouin</a>
  Description: This plugin can integrate nearly any type of feed into the WordPress feed on your blog pages.

  Copyright 2011 Michael Blouin  (email : michael blouin [a t ] mich ael blou in DOT ca)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class mb_globalfeed_feed {
    /**
     * The name of the current feed.
     * 
     * @var str
     */
    private $feed_name = 'Example Feed';
    
    /**
     * A url friendly feed slug. (Lacking spaces and special characters).
     * 
     * @var str
     */
    private $feed_slug = 'example_feed';
    
    /**
     * The type of feed that this is. See $mb_globalfeed->register_feed() documentation.
     * 
     * @var str
     */
    private $feed_type = 'request';
    
    /**
     * The pages to show this feed on. Defaults to 'all'
     * 
     * @var array
     */
    private $pages_to_show = array(
        'all'
    );
    
    /**
     * The pages that this feed should not be shown on. Defaults to a blank array.
     * @var array
     */
    private $pages_not_to_show = array(
        
    );
    
    /** 
     * This array contains any settings that should be saved for this feed.
     * 
     * Settings in this array are automatically saved when you use register_feed()
     * 
     * @var array $feed_options
     */
    private $feed_options = array(
        'auto_contain_feed_items' => true
    );
    
    /**
     * This contains the list of enabled actions for the current feed.
     * 
     * @var type array
     */
    private $allowed_actions = array(
        'update_feed',
        'do_maintenance'
    );

    
    /**
     * Echo's out a feed item for this feed. This function is called within the loop.
     * 
     * Global Feed automatically encapsulates the feed within a container. If
     * you don't want this functionality, change 'auto_contain_feed_items' in your
     * $feed_options to false. If you make your own container, make sure the class naming
     * conventions are the same as the default to make front end styling consistant.
     */
    function print_feed(){
        
    }
    
    /**
     * This function must be called by a WordPress action. Setup the WordPress 
     * setup the WordPress action at the bottom of your feeds main php file.
     * 
     * This function should register the feed, but not handle any  source authentication. 
     * Any required authentication or setup should be done when the user visits 
     * the feed options within Global Feed.
     * 
     * 
     * @uses $this->register_feed
     * @return type bool|WP_Error
     */
    function activate_feed() {
        return $this->register_feed( true );
    }
    
    /**
     * Called by GlobalFeed when the feed is deactivated. Removes feed settings, and feed items. 
     * 
     * @uses remove_feed_items()
     */
    function unregister_feed(){
        global $mb_globalfeed;
        
        // Delete the feed options
        $this->feed_options = null;
        //$this->register_feed(true);
        
        $this->remove_feed_items();
    }
    
    /** 
     * Removes all feed items stored by the feed. (But leaves feed settings intact.
     * 
     * @todo This needs serious optimizing and should not rely on a for loop. Instead, it should prepare and use a SQL query to do it all.
     * @param int $num_items The number of items to delete.
     */
    function remove_feed_items( $num_items = -1 ){
        global $mb_globalfeed;
        
        // Get the posts that need removing
        $posts = get_posts(array(
            'post_type' => $mb_globalfeed->post_type_namespace() . $this->get_slug(),
            'numberposts' => $num_items,
        ));
        
        // Remove the posts. (Needs optimizing).
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
    }
    
    /**
     * This funcion is called whenever a scheduled is called. It will be passed 
     * an array containing an action item that will tell you the action that should be performed.
     * 
     * These actions are typically:
     *      update_feed: This is called for feeds when they should manually check with a provider for updates.
     *      do_maintenance: This is called when any upkeep/maintenance should be done. This includes cleaning things up or removing old items.
     * 
     * 
     */
    function do_scheduled_action( $args ) {
        //Make sure the action is enabled, then call it.
        if ( in_array($args['action'],$this->allowed_actions) )
                $this->$action( $args );
        
    }
    
    /**
     * This function registers the feed options and information with Global Feed.
     * 
     * If called with the update option it can also be used to save feed settings.
     * 
     * @param bool $update If this feed is already registered, should the values here overwrite the existing ones?
     */
    function register_feed( $update = false ) {
        global $mb_globalfeed;
        
        $info = array(
            'feed_name' => $this->feed_name,
            'feed_slug' => $this->feed_slug,
            'feed_type' => $this->feed_type,
            'file_path' => __FILE__, 
            'update' => $update
        );
        
        $feed_options = $this->feed_options;
        $feed_options[ 'pages_to_show' ] = $this->pages_to_show;
        $feed_options[ 'pages_not_to_show' ] = $this->pages_not_to_show;
        
        if ( !$mb_globalfeed )
            $mb_globalfeed = mb_globalfeed::init();
        return $mb_globalfeed->register_feed();
    }
    
    /**
     * This function checks all the comatibility requirements for the feed.
     * 
     * @param bool $activate Whether the feed is currently being activated, or if compatibility is being checked.
     * @return array|bool If $activate is false, then the plugin should return an array containing key/value pairs of strings and bools for each requirement. If $activate is true, the plugin should return a boolean value indicating whether to go ahead with activation. 
     */
    function check_compatibility( $activate = false) {
        global $wp_version;
        $requirements = array(
            'Wordpress Version' => true,
            'Global Feed Version' => true
        );
        
        // Check that WordPress is >= v3.0
        $wpv = explode('.', $wp_version);
        if ( ((int) $wpv[0]) < 3 ) 
            $requirements[ 'WordPress Version' ] = __( 'Feed requires WordPress v3.0+' );
        unset( $wpv );
        
        // Check that Global Feed is v0.1 or greater
        $gfv = explode('.', $mb_globalfeed->version);
        if ( ((int) $gfv[1]) === 0 && ((int) $gfv[1]) < 1 ) 
            $requirements[ 'Global Feed Version' ] = __( 'Feed requires Global Feed v0.1+' );
        unset( $gfv );
        
        // If the feed is currently being activated, check that all requirements are met
        if ( $activate == true) {
            foreach ( $requirements as $requirement ) {
                if ( $requirement != true )
                    return $requirement;
                
                return true;
            }            
        }
        
        return $requirements;
    }
    
    /**
     * This function take the rawdata returned by the feed source (ie Titter, Facebook etc...) and converts it into
     * the array format expected by $mb_globalfeed->save_feed_items(), then it saves it using this function.
     * 
     * @uses $mb_globalfeed->save_feed_items()
     * @param array $feed_items An array containing the raw data from the feed source that should be parsed and saved.
     */
    function store_feed_items( $feed_items ){
        
    }
    
    /**
     * This function is called when you should contact the feed source for updates (manually).
     * 
     * @param array $args The arguments passed by the scheduler functions.
     */
    private function update_feed( $args ) {
        
    }
    
    /**
     * Feeds should have one admin page that is contained within the main globalfeed
     * tab. This function should should print out the page. It will be within 
     * a container in the GlobalFeed plugin page.
     * 
     * This page may be called via AJAX, and therefore any external requirements
     * should be included within the printed HTML. Make sure that your CSS/Javascript
     * is specific enough so as to not affect any other feeds/globalfeed functions.
     */
    function print_admin_page(){
        
    }
    
    /**
     * This function is called when feed maintenance should be done. This is any 
     * integrity checking or removal of old items that needs doing (or anything 
     * else that should be checked periodically).
     * 
     */
    private function do_maintenance() {
        
    }
}

?>
