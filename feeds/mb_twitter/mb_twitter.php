<?php
/*
  Feed Name: Twitter Connect
  Feed Slug: twitter_connect
  Author: Michael Blouin
  Author URI: http://MichaelBlouin.ca/
  Feed URI: http://MichaelBlouin.ca.
  Version: v0.10
  GlobalFeed Version: 0.1
  WordPress Version: 3.0.0
  Author: <a href="http://www.michaelblouin.ca/">Michael Blouin</a>
  Description: This feed configures Twitter to automatically inform your site of any relevant updates.


  Copyright 2011 Michael Blouin  (email : michael blouin [a t ] mich ael blou in DOT ca)

  This program is free software; you can redsistribute it and/or modify
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

/**
 * At the moment the MB_Twitter Feed uses the Twitter REST API. It does not require
 * user authentication, and as such can show only public tweets by the given user. 
 */
class mb_globalfeed_twitter_connect extends mb_globalfeed_feed {
    /**
     * The name of the current feed.
     * 
     * @var str
     */
    private $feed_name = 'Twitter Connect';
    
    /**
     * A url friendly feed slug. (Lacking spaces and special characters).
     * 
     * @var str
     */
    private $feed_slug = 'twitter_connect';
    
    private $feed_version = '0.1.3';
    
    /**
     * The type of feed that this is. See $this->globalfeed->register_feed() documentation.
     * 
     * @var str
     */
    private $feed_type = 'request';
    
    /**
     * This decides whether the application automatically quits after GlobalFeed has finished executing.
     * @var boolean
     */
    private $auto_interupt_flow = true;
    
    /**
     * The pages to show this feed on. Defaults to 'all'
     * 
     * @var array
     */
    private $pages_to_show = array(
        'all' => 'all'
    );
    
    /**
     * The pages that this feed should not be shown on. Defaults to a blank array.
     * @var array
     */
    private $pages_not_to_show = array(
        
    );
    
    /**
     * The instance of Global Feed this plugin was created using.
     * @var type 
     */
    private $globalfeed;
    
    private $_apiurl = 'http://api.twitter.com/1/';
    
    /**
     * Gets the Twitter REST API url
     * 
     * @return type 
     */
    public function apiurl () {
        return $this->_apiurl;
    }
    
    /**
     * Returns the feeds (shortened) slug.
     * @return str The feed slug.
     */
    public function get_slug() {
        return $this->globalfeed->get_shortened_feed_slug( $this->feed_slug );
    }

    /** 
     * This array contains any settings that should be saved for this feed.
     * 
     * Settings in this array are automatically saved when you use register_feed()
     * 
     * @var array $feed_options
     */
    private $feed_defaults = array(
        'auto_contain_feed_items' => true,
        'user_info' => null,
        'feed_queryvar' => 'mbgf_twitter_connect',
        'tw_auth_status' => 'not_initiated', // can be 'not_initiated', 'authorizing', 'authorized'. 
        'object_to_subscribe' => '',
        'last_feed_update' => array( 'status' => 0, 'retweet'=> 0 ),
        'max_feed_items' => 0,
        'initial_setup_done' => false,
        'show_welcome_message' => false,
        'geotagging_enabled' => true,
        'allow_showing_retweets' => false,
        'override_post_time_on_timezone_discrepency' => false,
    );
    
    /** 
     * This array contains any settings that should be saved for this feed.
     * 
     * Settings in this array are automatically saved when you use register_feed()
     * 
     * @var array $feed_options
     */
    private $feed_options = null;

    /**
     * This function requires a reference to MB_GlobalFeed in order to start. This is because the feed is loaded before
     * $this->globalfeed is available in the global scope.
     * 
     * @todo Remove the setup_tw var. Setup should be called from admin.
     * 
     * @param mb_globalfeed $globalfeed 
     */
    function __construct( $globalfeed ){
        $this->globalfeed = $globalfeed;
        
        $globalfeed->print_debug_info('MB Twitter Plugin Loaded.', 'mb_twitter');
        
        // Load the saved settings from Global Feed. (First detecting if the settings have already been saved.)
        $feed_options = $globalfeed->get_feed_options( $this->feed_slug );
        
        // Will be false if not already registered with GlobalFeed
        if ( $feed_options === false ) {
            $this->feed_options = $this->feed_defaults;
            return;
        }
        
        $this->feed_slug = !empty($feed_options['feed_slug']) ? $feed_options['feed_slug'] : $this->feed_slug;
        
        // Check if the feed needs updating
        // Feed version check introduced in 0.1.2
        if ( !isset($feed_options['version']) )
            $feed_options['version'] = '0.1.2';
            
        // Check if version data needs updating
        if ( $feed_options['version'] !== $this->feed_version ) {
            $globalfeed->print_debug_info("Updating Twitter Feed Data To Latest Version", 'mb_twitter');
            
            //$feed_options = $feed_options['feed_options'];
            // Upgrade from v0.1.2
            if ( $feed_options['version'] === '0.1.2' ) {
                $globalfeed->print_debug_info('Upgrading from v0.1.2 ', 'mb_twitter');
                
                if ( !is_array($feed_options['feed_options']['last_feed_update']) )
                    $feed_options['feed_options']['last_feed_update'] = array( 
                        'status' => $feed_options['feed_options']['last_feed_update'],
                        'retweet'=> 0
                    );

                // New settings
                $feed_options['feed_options']['allow_showing_retweets'] = $this->feed_defaults['allow_showing_retweets'];
                $feed_options['feed_options']['override_post_time_on_timezone_discrepency'] = $this->feed_defaults['override_post_time_on_timezone_discrepency'];
            } // Upgrade from v0.1.2
            
            $this->feed_options = $feed_options['feed_options'];
            $this->register_feed(true);
        } else
            $this->feed_options = (!empty($feed_options) ? $feed_options['feed_options'] : $this->feed_defaults);
        
        // Setup feed filters
        $this->setup_filters();
    }
    
    /**
     * This function is called when WordPress receives a request to the callback url. 
     * 
     * This is usually because data is being transmitted from Twitter.
     */
    private function handle_feed_call() {
        $this->globalfeed->print_debug_info('MB_Twitter received a request at the callback URL and interrupted application flow. '.get_bloginfo( 'wpurl' ).'?'.$this->feed_options['feed_queryvar'], 'mb_twitter');
        if ( $this->feed_options['tw_auth_status'] != 'authorized')
            $this->get_authorization();
    }
    
    function setup_filters() {
        //add_filter( 'query_vars', array( &$this, 'add_listener_vars' ));
        if (!is_admin()) {
            add_filter( 'get_avatar', array( &$this, 'get_avatar'));
            add_filter( 'author_link', array( &$this, 'author_link' ));
            add_filter( 'the_permalink', array( &$this, 'the_permalink' ));
            add_filter( 'post_link', array( &$this, 'the_permalink' ));
            add_filter( 'post_type_link', array( &$this, 'the_permalink' ));
            add_filter( 'the_author', array( &$this, 'the_author' ));
            add_action( 'mbgf-fb-theme_show_user_actions', array( &$this, 'show_user_actions' ) );
        }
        
        // These action are related to the setup while were in admin...
        if (is_admin()) {
            add_action( 'wp_ajax_mbgf_twitter_connect_set_tw_obj_id', array( &$this, 'set_tw_obj_id' ) );
            add_action( 'wp_ajax_mbgf_twitter_connect_redo_initial_setup', array( &$this, 'redo_initial_setup' ) );
            add_action( 'wp_ajax_mbgf_twitter_connect_reset_feed_defaults', array( &$this, 'reset_feed_defaults' ) );
            add_action( 'wp_ajax_mbgf_twitter_connect_manual_feed_update', array( &$this, 'ajax_do_update' ) );
            add_action( 'mbgf_unregister_feed-' . $this->get_slug(), array( &$this, 'unregister_feed') );
        }
    }
    
    function show_user_actions() {
        if (get_post_type() == ( $this->globalfeed->post_type_namespace() . $this->get_slug())) {
            $rttext = "RT: @" . get_post_meta(get_the_ID(), 'author_name', true) . ' ' . get_the_excerpt();
            $rttext = urlencode(substr($rttext, 0, 140));
            echo "<a href='https://twitter.com/home?status=$rttext' class='retweet link' target='_blank'>" . __('ReTweet', $this->get_slug()) . "</a><span class='sep'></span>";
        }
    }
    
    public function set_tw_obj_id(){
        check_admin_referer( 'twitter-connect-admin' );
        wp_verify_nonce( 'twitter-connect-admin' );
        
        $obj_id = $_POST['tw_object_id'] ;
        
        // Validate the values we've received
        if ( preg_match('/^[0-9]*$/', $obj_id ) == 0 ){
            die(json_encode('Invalid'));
        }
        
        // If we're receveing a new ID, kill the last update time.
        foreach ($this->feed_options['last_feed_update'] as $key => $value)
            $this->feed_options['last_feed_update'][$key] = 0;
        
        // The object ID appears valid. Save.
        $this->feed_options['object_to_subscribe'] = intval( $obj_id );
        
        // If this var is set, then mark the initial setup complete.
        if (isset($_POST['mb_twitter_finish_setup']) && ((bool) $_POST['mb_twitter_finish_setup']) == true ) {
            $this->globalfeed->print_debug_info('Marking initial setup complete: Twitter', 'mb_twitter');
            $this->feed_options['initial_setup_done'] = true;
            $this->feed_options['show_welcome_message'] = true;
        }
        
        // Save changes
        $this->register_feed(true);
        
        // Update and return the feed items retrieved.
        die(json_encode(
                array( 'num_feed_items_retrieved' => $this->update_feed(array()) )
        ));
    }
            
    /**
     * Removes all saved feed items, then marks the initial setup to be done.
     * 
     * (Called via ajax) 
     */
    public function redo_initial_setup() {
        check_admin_referer( 'twitter-connect-admin' );
        wp_verify_nonce( 'twitter-connect-admin' );
        
        $this->feed_options['initial_setup_done'] = false;
        $this->register_feed(true);
        
        $this->remove_feed_items();
        
        die(json_encode(true));
    }
    
    /** 
     * This function is called VIA Ajax to reset the feed to its defaults. 
     */
    public function reset_feed_defaults() {
        check_admin_referer( 'twitter-connect-admin' );
        wp_verify_nonce( 'twitter-connect-admin' );
        
        $this->feed_options = $this->feed_defaults;
        $this->pages_to_show = array( 'all' );
        $this->pages_not_to_show = array();
        
        $this->remove_feed_items();
        $this->register_feed(true);
        
        die(json_encode(true));
    }
    
    /**
     * This function will switch the avatar displayed by posts to the user twitter profile picture.
     */
    public function get_avatar($avatar) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->get_slug())) {
            $author_image = get_post_meta(get_the_ID(), 'author_profile_image_url_https', true);            
            $avatar = "<img alt='" . get_the_title() . "' src='{$author_image}' class='avatar photo avatar-default'/>";
        }

        return $avatar;
    }
    
    /**
     * This function will switch the post permalink to a link pointing to the original social media site.
     */
    public function the_permalink($permalink) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->feed_slug)) {
            $twitter_id = get_post_meta ( get_the_ID(), 'twitter_id', true );
            $author_name = get_post_meta( get_the_ID(), 'screen_name', true );
            $permalink = 'http://twitter.com/#!/' . $author_name . '/status/' . $twitter_id ;
        }
        
        return $permalink;
    }
    
    /**
     * This function will gets the link to the authors' Twitters page.
     */
    public function author_link($link) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->feed_slug)) {
            $author = get_post_meta(get_the_ID(), 'screen_name', true);
            $link = "https://twitter.com/#!/{$author}";
        }

        return $link;
    }
    
     /**
     * This function gets the authors name for the current post.
     */
    public function the_author($author_info) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->feed_slug)) {
            $author = get_post_meta(get_the_ID(), 'screen_name', true);
            $author_info = "@$author"  . ($this->globalfeed->show_byline() ? __(' via Twitter', 'mb_twitter_connect') : '');
        }

        return $author_info;
    }
    
    
    /**
     * Called by WordPress when the feed is activated. Registers initial feed settings.
     * 
     * @return WP_Error 
     */
    function activate_feed(){
        // @TODO:  Make feed check compatibility with the current WordPress environment and other requirements.
        // Register this feed with Global Feed.
        if ( !$this->register_feed() )
            return new WP_Error ('MB_Twitter_Feed_Activation_Failed', 'Feed registration failed for an unknown reason.');
    }
    
    /**
     * This function registers the feed options and information with Global Feed.
     * 
     * If called with the update option it can also be used to save feed settings.
     * @todo Fix request frequency. (User selectable)
     * 
     * @param bool $update If this feed is already registered, should the values here overwrite the existing ones?
     */
    function register_feed( $update = false ) {
        $this->globalfeed->print_debug_info('MB_Twitter Register Feed Started.', 'mb_twitter');
        
        $info = array(
            'feed_name'  => $this->feed_name,
            'feed_slug'  => $this->feed_slug,
            'feed_type'  => $this->feed_type,
            'file_path'  => __FILE__, 
            'class_name' => 'mb_globalfeed_twitter_connect',
            'update'     => $update,
            'version'    => '0.1.3',
            'request_frequency' => 'mbgf_default',
        );
        
        $feed_options = $this->feed_options;
        $feed_options[ 'pages_to_show' ] = $this->pages_to_show;
        $feed_options[ 'pages_not_to_show' ] = $this->pages_not_to_show;
        
        return $this->globalfeed->register_feed($info, $feed_options);
    }
    
    /**
     * This calls the update_feed function and is accessible from the WP Admin page
     * 
     */
    public function ajax_do_update() {
        check_admin_referer( 'twitter-connect-admin' );
        wp_verify_nonce( 'twitter-connect-admin' );
        
        $res = $this->update_feed();
        
        if ( is_int($res) )
            die(json_encode(array('status' => 'success', 'update_count' => $res)));
        else
            die(json_encode(array('status' => 'failure', 'error' => $res)));
    }

    /**
     * This function manually updates the feed and is usually called by a scheduler
     * when the feed is in request mode, or by async when the feed is in callback
     * mode.
     * 
     * @todo Give the user the option whether to show the user screen name, or name as the title.
     * @param type $args 
     */
    function update_feed() {
        $globalfeed = &$this->globalfeed;
        $feed_options = &$this->feed_options;
        $globalfeed->print_debug_info('Update Twitter Feed Called.', 'mb_twitter');
        $globalfeed->print_debug_info($feed_options, 'mb_twitter');
        
        $endpoints = array(
            "status" => "http://api.twitter.com/1/statuses/user_timeline.json?user_id={$feed_options['object_to_subscribe']}&count={$feed_options['max_feed_items']}" . ($feed_options['last_feed_update']['status'] ? "&since_id={$feed_options['last_feed_update']['status']}" : '')
        );
            
        if ( $feed_options['allow_showing_retweets'] )
            $endpoints["retweet"] = "http://api.twitter.com/1/statuses/retweeted_by_user.json?user_id={$feed_options['object_to_subscribe']}&count={$feed_options['max_feed_items']}" . ($feed_options['last_feed_update']['retweet'] ? "&since_id={$feed_options['last_feed_update']['retweet']}" : '');
        
        $feed_items = array();
        $gmt_timezone = new DateTimeZone('GMT');
        $time_offset = get_option( 'gmt_offset' ) * 3600;
        
        foreach ( $endpoints as $endpoint_id => $endpoint ) {
            $request = wp_remote_get( $endpoint );

            if ( is_wp_error($request) )
                return $request;

            // Use the regex to replace numerical values to strings for older versions of php
            $updates = json_decode( preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $request['body']), true );

            if ( $updates == NULL || empty($updates) )
                continue;
           
            // Mark the last feed update item
            $feed_options['last_feed_update'][$endpoint_id] = $updates[0]['id'];
            
            foreach ($updates as $update) {
                if ( $update['user']['id'] != $feed_options['object_to_subscribe'] ) {
                    //$globalfeed->print_debug_info($update);
                    continue;
                }

                $post_date = new DateTime((string) trim($update['created_at']), $gmt_timezone);

                $post_args = array(
                    'post_content' => $globalfeed->post_content($update['text']),
                    'post_excerpt' => '',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_author' => $update['user']['id'],
                    'post_title' => "@{$update['user']['screen_name']}",
                    //'post_date' => date('Y-m-d H:i:s', strtotime( $update['created_at'] ) ),
                    'post_date' => $post_date->format('U') + $time_offset,
                    'meta' => array(
                        'author_name' => $update['user']['name'],
                        'screen_name' => $update['user']['screen_name'],
                        'twitter_id' => $update['id'],
                        'author_profile_image_url' => $update['user']['profile_image_url'],
                        'author_profile_image_url_https' => $update['user']['profile_image_url_https'],
                        'in_reply_to_status_id' => $update['in_reply_to_status_id'],
                        'in_reply_to_user_id' => $update['in_reply_to_user_id'],
                        'in_reply_to_screen_name' => $update['in_reply_to_screen_name'],
                    ),
                );
                    
                //If this post is a retweet, then set the author to the original author
                if ( !empty($update['retweeted_status']) ) {
                    $post_args['meta'] = array(
                        'author_name' => $update['retweeted_status']['user']['name'],
                        'screen_name' => $update['retweeted_status']['user']['screen_name'],
                        'twitter_id' => $update['retweeted_status']['id'],
                        'author_profile_image_url' => $update['retweeted_status']['user']['profile_image_url'],
                        'author_profile_image_url_https' => $update['retweeted_status']['user']['profile_image_url_https'],
                        'in_reply_to_status_id' => $update['retweeted_status']['in_reply_to_status_id'],
                        'in_reply_to_user_id' => $update['retweeted_status']['in_reply_to_user_id'],
                        'in_reply_to_screen_name' => $update['retweeted_status']['in_reply_to_screen_name'],
                    );
                }

                // Process the post date
                if ( $post_args['post_date'] <= current_time('timestamp') || !$feed_options['override_post_time_on_timezone_discrepency'] )
                    $post_args['post_date'] = date('Y-m-d H:i:s', $post_args['post_date']);
                else
                    $post_args['post_date'] = current_time('mysql'); // current_time() is a WP function

                // Check if this item is already stored in the DB, and update if so...
                $posts_query = array(
                    'post_type' => $globalfeed->post_type_namespace() . $this->get_slug(),
                    'post_date' => $post_args['post_date'],
                    'meta_key'  => 'twitter_id',
                    'meta_value'=> $post_args['meta']['twitter_id'],
                );
                $posts = query_posts($posts_query);

                if ( count($posts) >= 1 ) // Check if this item is already in the DB...
                    $post_args['ID'] = $posts[0]->ID;

                if ( isset($this->feed_options['geotagging_enabled']) && $this->feed_options['geotagging_enabled'] && $update['user']['geo_enabled'] ){
                    $post_args['meta']['geo'] = array('unique' => true, 'value' => $update['geo']);
                    $post_args['meta']['coordinates'] = array('unique' => true, 'value' => $update['coordinates']);
                    $post_args['meta']['place'] = array('unique' => true, 'value' => $update['place']);
                }

                $feed_items[] = $post_args;
            }
        }
        
        if ( count($feed_items) <= 0 )
            return 0;
        
        // Set the last status update received...
       // $feed_options['last_feed_update'] = $feed_items[0]['meta']['twitter_id'];
        $this->register_feed(true);
        
        // Save all of the feed items.
        // $globalfeed->print_debug_info($feed_items);
        $globalfeed->save_feed_items( $this->feed_slug, $feed_items );
        
        return count( $feed_items );
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
        $gfv = explode('.', $mb_globlfeed->version);
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
    
    function print_admin_page(){
        $mb_globalfeed = $this->globalfeed;
        $mb_globalfeed->include_admin_theming();
        
        global $mbgfa_theme;
        //Detect whether the plugin has been setup and configured yet. If not, run the user through the setup.
        //If yes, serve up plugin options.
        
        // Include javascript client tools etc..
        $mbgfa_theme->queue_js_client_tools();
        wp_enqueue_script('mb_twitter', plugins_url( 'feeds/mb_twitter/pages/js/mb_twitter.js', $mb_globalfeed->plugin_location() ), array('jquery'));
        wp_enqueue_style('mb_twitter', plugins_url( 'feeds/mb_twitter/pages/style.css', $mb_globalfeed->plugin_location() ));
        
        // If the initial setup is done, show that. Otherwise show the settings page.
        if ( $this->feed_options['initial_setup_done'] )
            require_once('pages/settings-main.php');
        else
            require_once('pages/setup.php');
        
        // Once the page has been loaded, call the function to display it, and save any modifications to feed information.
        if ( show_mb_twitter_page( $this->feed_options ) )
            $this->register_feed (true);
    }
}

global $mb_globalfeed;

// If theres no GlobalFeed, this feed probably has not been activated.
if (isset( $mb_globalfeed )) {
    function mb_globalfeed_twitter_connect_activate_initial () {
        global $mb_globalfeed;
        $mb_globalfeed->print_debug_info('Activate initial: Twitter', 'mb_twitter');
        $activation_instance = new mb_globalfeed_twitter_connect($mb_globalfeed);
        $activation_instance->activate_feed();
    }

    add_action('mbgf_activate_feed-' . $mb_globalfeed->get_shortened_feed_slug('twitter_connect'), 'mb_globalfeed_twitter_connect_activate_initial');
    $mb_globalfeed->print_debug_info('mbgf_activate_feed-' . $mb_globalfeed->get_shortened_feed_slug('twitter_connect'));
}