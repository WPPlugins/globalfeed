<?php
/*
  Feed Name: YouTube Connect
  Feed Slug: youtube_connect
  Author: Michael Blouin
  Author URI: http://MichaelBlouin.ca/
  Feed URI: http://MichaelBlouin.ca.
  Version: v0.10
  GlobalFeed Version: 0.1
  WordPress Version: 3.0.0
  Author: <a href="http://www.michaelblouin.ca/">Michael Blouin</a>
  Description: This feed fetches videos for the given YouTube user.


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
 * At the moment the MB_YouTube Feed uses the YouTube REST API. It does not require
 * user authentication, and as such can show only public yteets by the given user. 
 */
class mb_globalfeed_youtube_connect extends mb_globalfeed_feed {
    /**
     * The name of the current feed.
     * 
     * @var str
     */
    private $feed_name = 'YouTube Connect';
    
    /**
     * A url friendly feed slug. (Lacking spaces and special characters).
     * 
     * @var str
     */
    private $feed_slug = 'youtube_connect';
    
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
    
    private $youtube_media_types = array(
        'application/x-shockwave-flash' => 'Flash Video',
        'video/3gpp' => '3GPP Format'
    );
    
    /**
     * The instance of Global Feed this plugin was created using.
     * @var type 
     */
    private $globalfeed;
    
    private $_apiurl = 'http://gdata.youtube.com/';
    
    /**
     * Gets the YouTube REST API url
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
     * This array contains the default settings for the feed and should not be changed.
     * 
     * Settings in this array are automatically saved when you use register_feed()
     * 
     * @var array $feed_options
     */
    private $feed_defaults = array(
        'auto_contain_feed_items' => true,
        'user_info' => null,
        'feed_queryvar' => 'mbgf_youtube_connect',
        'yt_auth_status' => 'not_initiated', // can be 'not_initiated', 'authorizing', 'authorized'. 
        'object_to_subscribe' => '',
        'last_feed_update' => 0,
        'max_feed_items' => 0,
        'initial_setup_done' => false,
        'show_welcome_message' => false,
        'use_https' => true,
        'override_post_time_on_timezone_discrepency' => false
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
     * @todo Remove the setup_yt var. Setup should be called from admin.
     * 
     * @param mb_globalfeed $globalfeed 
     */
    function __construct( $globalfeed ){
        $this->globalfeed = $globalfeed;
        
        $globalfeed->print_debug_info('MB YouTube Plugin Loaded.', 'mb_youtube');
        
        // Load the saved settings from Global Feed. (First detecting if the settings have already been saved.)
        $feed_options = $globalfeed->get_feed_options( $this->feed_slug );
        $this->feed_slug = !empty($feed_options['feed_slug']) ? $feed_options['feed_slug'] : $this->feed_slug;
        $feed_options = $this->feed_options = $feed_options ? $feed_options['feed_options'] : $this->feed_defaults;
        
        // Setup feed filters
        $this->setup_filters();
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
        }
        
        // These action are related to the setup while were in admin...
        if (is_admin()) {
            add_action( 'wp_ajax_mbgf_youtube_connect_set_yt_usr_id', array( &$this, 'set_yt_usr_id' ) );
            add_action( 'wp_ajax_mbgf_youtube_connect_redo_initial_setup', array( &$this, 'redo_initial_setup' ) );
            add_action( 'wp_ajax_mbgf_youtube_connect_reset_feed_defaults', array( &$this, 'reset_feed_defaults' ) );
            add_action( 'wp_ajax_mbgf_youtube_connect_manual_feed_update', array( &$this, 'ajax_do_update' ) );
            add_action( 'mbgf_unregister_feed-' . $this->get_slug(), array( &$this, 'unregister_feed') );
        }
    }
    
    /**
     * Saves the YouTube username that was specified during the setup wizard.
     * 
     * (Called via ajax) 
     */
    public function set_yt_usr_id(){
        check_admin_referer( 'youtube-connect-admin' );
        wp_verify_nonce( 'youtube-connect-admin' );
        
        $username = $_POST['yt_username'];

        // Validate the values we've received
        if ( !preg_match('/^[A-Za-z0-9]{1,20}$/', $username ) ) 
            die(json_encode ('Invalid'));


        // Fetch the user data from YouTube
        $userinfo = wp_remote_get("https://gdata.youtube.com/feeds/api/users/{$username}?alt=json");
        $this->globalfeed->print_debug_info($userinfo);
        if (is_wp_error($userinfo)) {
            // Try without https in case they have no https transports installed
            $userinfo = wp_remote_get("http://gdata.youtube.com/feeds/api/users/{$username}?alt=json");
            $this->globalfeed->print_debug_info($userinfo);
            
            if (is_wp_error($userinfo)) 
                die(json_encode ($userinfo->get_error_message()));
            else
                $this->feed_options['use_https'] = false;
        }
        
        $userinfo = json_decode($userinfo['body'], true);
        $userinfo = $userinfo['entry'];

        // Save the user data
        $this->feed_options['user_info'] = array(
            'username' => $userinfo['yt$username']['$t'],
            'title' => $userinfo['title']['$t'],
            'picture' => $userinfo['media$thumbnail']['url'],
        );

        // If we're receveing a new ID, kill the last update time.
        $this->feed_options['last_feed_update'] = 0;

        // The object ID appears valid. Save.
        $this->feed_options['object_to_subscribe'] = $username;

        // If this var is set, then mark the initial setup complete.
        if (isset($_POST['mb_youtube_finish_setup']) && ((bool) $_POST['mb_youtube_finish_setup']) == true ) {
            $this->globalfeed->print_debug_info('Marking initial setup completE: YouTube');
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
        check_admin_referer( 'youtube-connect-admin' );
        wp_verify_nonce( 'youtube-connect-admin' );
        
        $this->feed_options['initial_setup_done'] = false;
        $this->register_feed(true);
        
        $this->remove_feed_items();
        
        die(json_encode(true));
    }
    
    /** 
     * This function is called VIA Ajax to reset the feed to its defaults. 
     */
    public function reset_feed_defaults() {
        check_admin_referer( 'youtube-connect-admin' );
        wp_verify_nonce( 'youtube-connect-admin' );
        
        $this->feed_options = $this->feed_defaults;
        $this->pages_to_show = array( 'all' );
        $this->pages_not_to_show = array();
        
        $this->remove_feed_items();
        $this->register_feed(true);
        
        die(json_encode(true));
    }
    
    /**
     * This function will switch the avatar displayed by posts to the user youtube profile picture.
     */
    public function get_avatar($avatar) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->get_slug())) {   
            $avatar = "<img alt='" . get_the_title() . "' src='{$this->feed_options['user_info']['picture']}' class='avatar photo avatar-default'/>";
        }

        return $avatar;
    }
    
    /**
     * This function will switch the post permalink to a link pointing to the original social media site.
     */
    public function the_permalink($permalink) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->feed_slug)) {
            $youtube_id = get_post_meta ( get_the_ID(), 'youtube_id', true );
            $permalink = "https://www.youtube.com/watch?v=$youtube_id" ;
        }
        
        return $permalink;
    }
    
    /**
     * This function will gets the link to the authors' YouTubes page.
     */
    public function author_link($link) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->feed_slug)) {
            $link = "https://www.youtube.com/{$this->feed_options['user_info']['username']}";
        }

        return $link;
    }
    
     /**
     * This function gets the authors name for the current post.
     */
    public function the_author($author_info) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->feed_slug)) {
            $author_info = $this->feed_options['user_info']['title'] . $this->globalfeed->show_byline() ? __(' via YouTube', 'mb_youtube_connect') : '';
        }

        return $author_info;
    }
    
    
    /**
     * Called by WordPress when the feed is activated. Registers initial feed settings.
     * 
     * @return \WP_Error 
     */
    function activate_feed(){
        // @TODO:  Make feed check compatibility with the current WordPress environment and other requirements.
        // Register this feed with Global Feed.
        if ( !$this->register_feed() )
            return new WP_Error ('MB_YouTube_Feed_Activation_Failed', 'Feed registration failed for an unknown reason.');
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
        $this->globalfeed->print_debug_info('MB_YouTube Register Feed Started.', 'mb_youtube');
        
        $info = array(
            'feed_name'  => $this->feed_name,
            'feed_slug'  => $this->feed_slug,
            'feed_type'  => $this->feed_type,
            'file_path'  => __FILE__, 
            'class_name' => 'mb_globalfeed_youtube_connect',
            'update'     => $update,
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
        check_admin_referer( 'youtube-connect-admin' );
        wp_verify_nonce( 'youtube-connect-admin' );
        
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
     * @todo There should be an admin setting to control whether categories are imported as categories, tags, or not at all.
     * @todo There should be an admin setting to embed the video directly into the post content or not.
     * 
     * @param array $args 
     */
    function update_feed( $args = null ) {
        $globalfeed = &$this->globalfeed;
        $feed_options = &$this->feed_options;
        $globalfeed->print_debug_info('Update YouTube Feed Called.', 'mb_youtube');
        
        $api_url = ($feed_options['use_https'] ? 'https://' : 'http://') ."gdata.youtube.com/feeds/api/users/{$feed_options['object_to_subscribe']}/uploads?alt=json&orderby=published";
        if ( $feed_options['max_feed_items'] != 0 )
            $api_url .="&max-results={$feed_options['max_feed_items']}";
        if ( $feed_options['last_feed_update'] != 0 ) // Note: date('c') does not simply work here, YouTube is a picky little... thing...
            $api_url .= str_replace(' ','+',str_replace ('+', '.', "&fields=entry[xs:dateTime(updated) > xs:dateTime('" . gmdate(DATE_ISO8601, $feed_options['last_feed_update']) . "Z')]"));
        
        $request = wp_remote_get( $api_url );

        if (is_wp_error($request) )
            return $request;
        
        $updates = json_decode( $request['body'], true );
        
        if ( $updates == NULL )
            return false;
        
        $last_update = 0;
        $feed_items = array();
        $gmt_timezone = new DateTimeZone('GMT');
        $time_offset = get_option( 'gmt_offset' ) * 3600;
        foreach ( $updates['feed']['entry'] as $update ) {
            $modified =  strtotime( $update['updated']['$t'] );
            
            $post_date = new DateTime((string) trim($update['published']['$t']), $gmt_timezone);
            
            // Generate the post attributes
            $post_args = array(
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_author' => 0,
                'post_title' => $update['title']['$t'],
                //'post_date' => date('Y-m-d H:i:s', strtotime( $update['published']['$t'] ) ),
                'post_date' => $post_date->format('U') + $time_offset,
                'post_modified' => date('Y-m-d H:i:s', $modified ),
                'post_format' => 'video',
                'meta' => array(
                    'youtube_id' => substr( $update['id']['$t'], strripos( $update['id']['$t'], '/' ) + 1 ),
                ),
            );
            
            // Process the post date
            if ( $post_args['post_date'] <= current_time('timestamp') || !$feed_options['override_post_time_on_timezone_discrepency'] )
                $post_args['post_date'] = date('Y-m-d H:i:s', $post_args['post_date']);
            else
                $post_args['post_date'] = current_time('mysql'); // current_time() is a WP function
            
            // The 100% width should help this video display in nearly any theme nicely
            $media_info = array('youtube_video_id'=>$post_args['meta']['youtube_id'], 'height' => 390, 'width' => '100%');
            
            $post_args['post_content'] = $globalfeed->post_content( $update['content']['$t'], array('media_format' => 'video', 'media_info' => $media_info) );
            $post_args['post_excerpt'] = $globalfeed->post_excerpt( $update['content']['$t'], array('media_format' => 'video', 'media_info' => $media_info) );
            
            // Check if this item is already stored in the DB, and update if so...
            $posts_query = array(
                'post_type' => $globalfeed->post_type_namespace() . $this->get_slug(),
                'meta_query' => array(
                    'key' => 'youtube_id',
                    'value' => $post_args['meta']['youtube_id'],
                ),
            );
            $posts = get_posts($posts_query);
            
            if ( count($posts) >= 1 ) { 
                // Item is already in DB, set ID so that content is updated
                $post_args['ID'] = $posts[0]->ID;
            } 
            
            // Save the different media sources.
            foreach ($update['media$group']['media$content'] as $media) {
                $post_args['meta'][ $this->youtube_media_types[$media['type']] ] = $media['url'];
            }
            
            // Track the last modification date...
            if ( $modified > $last_update )
                $last_update = $modified;
            
            // Append this item to the list
            $feed_items[] = $post_args;
        }
            
        
        if ( count($feed_items) <= 0 )
            return 0;
        
        // Set the last status update received...
        //$feed_options['last_feed_update'] = $last_update;
        $this->register_feed(true);
        
        // This is so that the iframe is not remove from the content during scheduled updates
        global $allowedposttags;
        $pre_kses = $allowedposttags;
        $allowedposttags['iframe'] = array(
            'class' => array(),
            'type' => array(),
            'width' => array(),
            'height' => array(),
            'src' => array(),
            'frameborder' => array()
        );
        
        // Save all of the feed items.
        // 
        //$globalfeed->print_debug_info(
            $globalfeed->save_feed_items( $this->feed_slug, $feed_items );
        //);
        
        $allowedposttags = $pre_kses;
        unset($pre_kses);
            
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
    
    /**
     * This function take the rawdata returned by the feed source (ie Titter, YouTube etc...) and converts it into
     * the array format expected by $this->globalfeed->save_feed_items(), then it saves it using this function.
     * 
     * @uses $this->globalfeed->save_feed_items()
     * @param array $feed_items An array containing the raw data from the feed source that should be parsed and saved.
     */
    function store_feed_items( $feed_items ){
        $this->globalfeed->print_debug_info( "Store YouTube Feed Items: \n" . var_export($feed_items, true) );
    }
    
    function print_admin_page(){
        $mb_globalfeed = $this->globalfeed;
        $mb_globalfeed->include_admin_theming();
        /* @var $mbgfa_theme mbgfa_theme */
        global $mbgfa_theme;
        //Detect whether the plugin has been setup and configured yet. If not, run the user through the setup.
        //If yes, serve up plugin options.
        
        // Include javascript client tools etc..
        $mbgfa_theme->queue_js_client_tools();
        wp_enqueue_script('mb_youtube', plugins_url( 'feeds/mb_youtube/pages/js/mb_youtube.js', $mb_globalfeed->plugin_location() ), array('jquery'));
        wp_enqueue_style('mb_youtube', plugins_url( 'feeds/mb_youtube/pages/style.css', $mb_globalfeed->plugin_location() ));
        
        // If the initial setup is done, show that. Otherwise show the settings page.
        if ( $this->feed_options['initial_setup_done'] )
            require_once('pages/settings-main.php');
        else
            require_once('pages/setup.php');
        
        // Once the page has been loaded, call the function to display it, and save any modifications to feed information.
        if ( show_mb_youtube_page( $this->feed_options ) == true )
            $this->register_feed (true);
    }
    
    function launchBackgroundProcess($call) {
 
        // Windows
        if(is_windows()){
            pclose(popen('start /b '.$call.'', 'r'));
        }

        // Some sort of UNIX
        else {
            pclose(popen($call.' /dev/null &', 'r'));
        }
        return true;
    }

    /**
     *
     * @param string $url The url of the file to be called asynchronously.
     * @param mixed|array $params An array of parameters to be posted to the script.
     * @param string $type The type of request to be sent. (POST/GET)
     */
    private function curl_request_async($url, $params, $type='GET') {
        $parts=parse_url($url);

        $fp = fsockopen($parts['host'],
            isset($parts['port'])?$parts['port']:80,
            $errno, $errstr, 30);


        $out = "POST ".$parts['path']." HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";
        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out.= "Content-Length: ".strlen($post_string)."\r\n";
        $out.= "Connection: Close\r\n\r\n";
        if (isset($post_string)) $out.= $post_string;

        fwrite($fp, $out);
        fclose($fp);
    }

}

global $mb_globalfeed;

// If theres no GlobalFeed, this feed probably has not been activated.
if (isset( $mb_globalfeed )) {
    function mb_globalfeed_youtube_connect_activate_initial () {
        global $mb_globalfeed;
        $mb_globalfeed->print_debug_info('Activate initial: YouTube', 'mb_youtube');
        $activation_instance = new mb_globalfeed_youtube_connect($mb_globalfeed);
        $activation_instance->activate_feed();
    }

    add_action('mbgf_activate_feed-' . $mb_globalfeed->get_shortened_feed_slug('youtube_connect'), 'mb_globalfeed_youtube_connect_activate_initial');
    $mb_globalfeed->print_debug_info('mbgf_activate_feed-' . $mb_globalfeed->get_shortened_feed_slug('youtube_connect'));
}