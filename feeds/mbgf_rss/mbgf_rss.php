<?php
error_reporting(E_ALL);
/*
  Feed Name: MB RSS
  Feed Slug: mbgf_rss
  Author: Michael Blouin
  Author URI: http://MichaelBlouin.ca/
  Feed URI: http://MichaelBlouin.ca.
  Version: v0.10
  GlobalFeed Version: 0.1
  WordPress Version: 3.0.0
  Author: <a href="http://www.michaelblouin.ca/">Michael Blouin</a>
  Description: This feed fetches a remote RSS feed and integrates it with GlobalFeed.


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
 * @todo Allow for choosing of an avatar for each feed.
 * @todo 
 * 
 * @package MB RSS
 */
class mbgf_rss extends mb_globalfeed_feed {
    /**
     * The name of the current feed.
     * 
     * @var str
     */
    private $feed_name = 'MB RSS';
    
    /**
     * A url friendly feed slug. (Lacking spaces and special characters).
     * 
     * @var str
     */
    private $feed_slug = 'mbgf_rss';
    
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
     *  
     */
    private $medium_post_format_conversions = array(
        'image'      => 'image',
        'video'      => 'video',
        'document'   => 'standard',
        'audio'      => 'audio',
        'executable' => 'link',
    );
    
    /**
     * The instance of Global Feed this plugin was created using.
     * @var type 
     */
    private $globalfeed;
    
    /**
     * Returns the feeds (shortened) slug.
     * @return str The feed slug.
     */
    public function get_slug() {
        return $this->globalfeed->get_shortened_feed_slug( $this->feed_slug );
    }

    /** 
     * This array contains the default feed settings.
     * 
     * Settings in this array are automatically saved when you use register_feed()
     * 
     * @var array $feed_options
     */
    private $feed_defaults = array(
        'auto_contain_feed_items' => true,
        'feed_queryvar' => 'mbgf_rss',
        'feeds_to_subscribe' => array(),
        'max_feed_items' => 0,
        'initial_setup_done' => true,
        'show_welcome_message' => true,
        'errors' => array(),
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
     * @todo Remove the setup_rss var. Setup should be called from admin.
     * 
     * @param mb_globalfeed $globalfeed 
     */
    function __construct( $globalfeed ){
        $this->globalfeed = $globalfeed;
        
        $globalfeed->print_debug_info('MB RSS Plugin Loaded.', 'mbgf_rss');
        
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
            add_action( 'wp_ajax_mbgf_rss_add_rss_feed', array( &$this, 'add_rss_feed' ) );
            add_action( 'wp_ajax_mbgf_rss_manual_feed_update', array( &$this, 'ajax_do_update' ) );
            add_action( 'wp_ajax_mbgf_rss_remove_rss_feed', array( &$this, 'remove_rss_feed' ) );
            add_action( 'wp_ajax_mbgf_rss_reset_feed_defaults', array( &$this, 'reset_feed_defaults' ) );
            add_action( 'mbgf_unregister_feed-' . $this->get_slug(), array( &$this, 'unregister_feed') );
        }
    }
    
    /**
     * Adds the specified RSS feed to the list of feeds.
     * 
     * (Called via ajax) 
     * @todo This should present the user with a choice of which RSS feed should be presented if the supplied URL is an HTML doc that contains links to RSS Feeds.
     */
    public function add_rss_feed(){
        check_admin_referer( 'mb-rss-admin' );
        wp_verify_nonce( 'mb-rss-admin' );
        
        $feed_url = str_replace(' ','+',urldecode($_POST['rss_feed']));

        $feed = $this->parse_rss_feed($feed_url);
        
        if ( !$feed || is_wp_error($feed) )
            die( json_encode(array('Error reading rss feed.', $feed)));
        
        if ( isset($feed['feed_list']) ) {
            // The function found an html page linking to a list of feeds. Ask the 
            // client which feed is desired
            $feed_list = array();
            foreach ($feed['feed_list'] as $feed_url => $feed_info) {
                $feed_list[] = array(
                    'title' => $feed_info['title'],
                    'url' => $feed_url
                );
            }
            
            die(json_encode(array( 'feeds_found' => $feed_list )));
        }
        
        if ( isset($feed['url']) && !empty($feed['url']) )
            $feed_url = $feed['url'];
        
        $feed = $feed['feed'];
        
        // Save the feed data
        $this->feed_options['feeds_to_subscribe'][$feed_url] = array(
            'title' => (string) trim( $feed->title ),
            'description' => (string) trim( $feed->description ),
            'generator' => (string) trim( $feed->generator ),
            'link' => (string) trim($feed->link),
            'feed_url' => $feed_url,
            'last_update' => 0,
        );
        //die( json_encode($this->feed_options['feeds_to_subscribe'][$_POST['rss_feed']]) );
        
        // Fetch feed updates. This will save feed_options if it succeeds.
        if ( $this->update_feed($feed_url) === false )
            die( json_encode('Error reading rss feed. Stage 2.') );
        
        // Update and return the feed items retrieved.
        die(json_encode(
            $this->feed_options['feeds_to_subscribe'][$feed_url]
        ));
    }
    
    /**
     * Attempts to grab a valid RSS feed from a url, or get the feeds pointed to
     * by the page at that url. (Ie: A wordpress blog that has links to several feeds)
     * 
     * @param str $feed_url The url of the feed, or the page that links to the feed
     * @return boolean|array Array on success, false on fail
     * 
     * @todo Get rid of the @ warning blockers by filtering the content.
     */
    private function parse_rss_feed( $feed_url ) {
        $rawxml = wp_remote_get( $feed_url );
        if ( is_wp_error($rawxml) )
            return $rawxml;
        
        try {
            @$feed = simplexml_load_string($rawxml['body']);
            
            // If this was not XML, check and see if it contains a link to an XML doc
            if ( $feed === false )  {
                // Things aren't okay. Check and see if we maybe got a webpage with a link to a feed
                if ( is_array($rawxml) ) {
                    $feed_list = array();
                    if ( isset( $rawxml['response'] ) && ((int) $rawxml['response']['code']) === 200) {
                        // We got served a page okay...
                        $dom = new DOMDocument;
                        if ( @!$dom->loadHTML($rawxml['body']) )
                            return false;
                        
                        foreach (  $dom->getElementsByTagName('link') as $link ) {
                            if ( $link->attributes->getNamedItem('rel')->nodeValue != 'alternate' || $link->attributes->getNamedItem('type')->nodeValue != 'application/rss+xml'
                                    || is_null($link->attributes->getNamedItem('href')->nodeValue))
                                continue;
                            
                            $feed_url = str_replace(' ','+',$link->attributes->getNamedItem('href')->nodeValue);
                            $rawxml = wp_remote_get( $feed_url );
                            
                            if ( is_wp_error($rawxml) )
                                return false;
                            
                            @$feed = simplexml_load_string($rawxml['body']);

                            if ( $feed !== false )
                                // This seems to be a legit feed, add it to the list of those found
                                $feed_list[ $feed_url ] = array( 'title' => (string) trim( !empty($feed->channel->title) ? $feed->channel->title[0] : $link->attributes->getNamedItem('title')->nodeValue[0] ), 'url' => $feed_url, 'contents' => $feed );
                        }
                        
                        $feed_count = count($feed_list);
                        if ( $feed_count === 0 )
                            // No feeds found, send error
                            return false;
                        else if ( $feed_count > 1 )
                            // Several feeds found, send list
                            return array( 'feed_list' => $feed_list );
                        else {
                            // One feed found, return that feed
                            $feed = array_pop( $feed_list );
                            $feed_url = $feed['url'];
                            $feed = $feed['contents'];
                        }
                    }
                } else {
                    return false;
                }
            }
            
            if ( $feed === false )
                return false;
            
            // Things seem to be okay
            return array( 
                'url' => $feed_url,
                'namespaces' => $feed->getNamespaces(true),
                'feed' => $feed->channel
                    );
        } catch (Exception $exc) {
            return false;
        }
    }
    
    public function remove_rss_feed() {
        check_admin_referer( 'mb-rss-admin' );
        wp_verify_nonce( 'mb-rss-admin' );
        global $wpdb;
        
        $feed_url = str_replace(' ','+',urldecode($_POST['rss_feed']));
        
        // Dont use any wordpress posts querying interfaces here, they're garbage with meta keys.
        // Get the posts that need removing
        $posts = $wpdb->get_results($wpdb->prepare( "SELECT posts.ID FROM $wpdb->postmeta meta, $wpdb->posts posts WHERE meta.meta_key = 'source' AND meta.meta_value = %s AND meta.post_id = posts.ID AND posts.post_type = '{$this->globalfeed->post_type_namespace()}{$this->feed_slug}'", $feed_url));
        
        // Remove the posts. (Needs optimizing).
        foreach ($posts as $post) {
            $this->globalfeed->print_debug_info("Deleting post: $post->ID", 'mbgf_rss');
            wp_delete_post($post->ID, true);
        }
        
        // Remove from the list of feeds
        unset( $this->feed_options['feeds_to_subscribe'][$feed_url] );
        $this->register_feed(true);
        
        die( json_encode(true) );
    }
    
    /** 
     * This function is called VIA Ajax to reset the feed to its defaults. 
     */
    public function reset_feed_defaults() {
        check_admin_referer( 'mb-rss-admin' );
        wp_verify_nonce( 'mb-rss-admin' );
        
        $this->feed_options = $this->feed_defaults;
        
        $this->pages_to_show = array('all');
        $this->pages_not_to_show = array();
        $this->remove_feed_items();
        
        $this->register_feed(true);
        
        die(json_encode(true));
    }
    
    /**
     * This function will switch the avatar displayed by posts to a user selected avatar.
     * 
     * @todo Implement this function in the UI
     */
    public function get_avatar($avatar) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->get_slug())) {   
            return '';
            $avatar = "<img alt='" . get_the_title() . "' src='{$this->feed_options['user_info']['picture']}' class='avatar photo avatar-default'/>";
        }

        return $avatar;
    }
    
    /**
     * This function will switch the post permalink to a link pointing to the original social media site.
     */
    public function the_permalink($permalink) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->feed_slug)) {
            //$this->globalfeed->print_debug_info($this->feed_options['feeds_to_subscribe'][get_post_meta ( get_the_ID(), 'source', true )]);
            //$this->globalfeed->print_debug_info($this->feed_options['feeds_to_subscribe'][get_post_meta ( get_the_ID(), 'source', true )]['link']);
            $permalink = get_post_meta( get_the_ID(), 'content_link', true );
        }
        
        return $permalink;
    }
    
    /**
     * This function will gets the link to the authors' RSSs page.
     */
    public function author_link($link) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->feed_slug)) {
            $link = $this->feed_options['feeds_to_subscribe'][ get_post_meta ( get_the_ID(), 'source', true ) ]['link'];
        }

        return $link;
    }
    
     /**
     * This function gets the authors name for the current post.
     */
    public function the_author($author_info) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->feed_slug)) {
            $author_info = $this->feed_options['feeds_to_subscribe'][ get_post_meta ( get_the_ID(), 'source', true ) ]['title']  . ($this->globalfeed->show_byline() ? __(' via RSS', 'mbgf_rss') : '');
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
            return new WP_Error ('mbgf_rss_Feed_Activation_Failed', 'Feed registration failed for an unknown reason.');
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
        $this->globalfeed->print_debug_info('mbgf_rss Register Feed Started.', 'mbgf_rss');
        
        $info = array(
            'feed_name'  => $this->feed_name,
            'feed_slug'  => $this->feed_slug,
            'feed_type'  => $this->feed_type,
            'file_path'  => __FILE__, 
            'class_name' => 'mbgf_rss',
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
        check_admin_referer( 'mb-rss-admin' );
        wp_verify_nonce( 'mb-rss-admin' );
        
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
     * @param str $feed The feed to update. If not specified, all feeds are updated.
     */
    function update_feed( $feed = '' ) {
        global $wpdb;
        $globalfeed = &$this->globalfeed;
        $feed_options = &$this->feed_options;
        $globalfeed->print_debug_info('Update RSS Feed Called: ' . $feed, 'mbgf_rss');
        
        // Do we need to update just one feed, or all?
        $feeds_to_update = array();
        if ( $feed == '' || !is_string($feed) )
            $feeds_to_update = $feed_options['feeds_to_subscribe'];
        else
            $feeds_to_update[$feed] = $feed_options['feeds_to_subscribe'][$feed];
        
        // Begin looping through the feeds.
        $feed_items = array();
        $time_offset = get_option( 'gmt_offset' ) * 3600;
        $gmt_timezone = new DateTimeZone('GMT');
        foreach ($feeds_to_update as $feed_url => $feed) {
            $request = wp_remote_get( $feed_url );

            // If the request did not succeed, log the error and continue.
            if (is_wp_error($request) ) {
                $feed_options['errors'][] = array(
                    'error' => 'Error updating feed: ' . (empty($feed->title)) ? $feed_url : $feed->title,
                    'feed_url' => $feed_url,
                    'error_obj' => $request,
                    'time' => time(),
                );
                continue;
            }

            // Attempt to parse the xml feed
            try {
                @$updates = simplexml_load_string($request['body']);
                if ($updates == NULL && empty($updates))
                    throw new Exception('Could not parse XML. Invalid structure or not XML/RSS.');
                
                $namespaces = $updates->getNamespaces(true);
                $updates = $updates->channel;
            } catch (Exception $exc) {
                $feed_options['errors'][] = array(
                    'error' => 'Error parsing feed: ' . (empty($feed->title)) ? $feed_url : $feed->title,
                    'feed_url' => $feed_url,
                    'error_obj' => $exc,
                    'time' => time(),
                );
                continue;
            }
            
            // Loop through the updates and convert to WP Posts.
            foreach ( $updates->item as $update ) {
                
                // Check if the feed date is less than the processed date and that were supposed to check that.
                if ( strtotime( (string) trim($update->pubDate) ) < $feed['last_update'] && $feed['enforce_dates'] )
                    continue;
                
                $post_date = new DateTime((string) trim($update->pubDate), $gmt_timezone);
                
                // Generate the post attributes
                $post_args = array(
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_status' => 'publish',
                    'post_author' => 0,
                    'post_title' => (string) trim($update->title),
                    'post_date' => $post_date->format('U') + $time_offset,
                    'post_format' => 'standard',
                    'guid' => (string) trim($update->link),
                    'categories' => array(),
                    'tags' => array(),
                    'meta' => array(
                        'source' => $feed_url,
                        'content_link' => (string) trim($update->link),
                    ),
                );
                
                // Process the post date
                if ( $post_args['post_date'] <= current_time('timestamp') || !$feed_options['override_post_time_on_timezone_discrepency'] )
                    $post_args['post_date'] = date('Y-m-d H:i:s', $post_args['post_date']);
                else
                    $post_args['post_date'] = current_time('mysql'); // current_time() is a WP function
                
                // Check if this item is already stored in the DB, and update if so...
                $posts = $wpdb->get_results($wpdb->prepare( "SELECT posts.ID FROM $wpdb->posts posts WHERE posts.guid = %s AND posts.post_type = '{$this->globalfeed->post_type_namespace()}{$this->feed_slug}' LIMIT 1", (string) trim($update->link)));
                if ( count($posts) >= 1 ) 
                    $post_args['ID'] = $post_args['import_id'] = $posts[0]->ID;
                
                // Add the categories to the category variable.
                foreach ($update->category as $cat)
                    $post_args['categories'][(string) trim($cat)] = $this->get_xml_attr($cat, 'domain') != '' ? $this->get_xml_attr($cat, 'domain') : (string) trim($cat);
                
                // Get the post content if its supported
                if ( isset($namespaces['content']) ) {
                    $content = $update->children($namespaces['content']);
                    $post_args['post_content'] = (string) trim($content->encoded);
                } else if ( isset($update->description) ) {
                    $post_args['post_content'] = (string) $update->description[0];
                }
                
                // Get media information if its supported
                if ( isset($namespaces['media']) ) {
                    // Get the content from the media namespace
                    $content = $update->children($namespaces['media']);
                    if ( $content->content[0] ) {
                        // Set the post format based on the medium type
                        $post_args['post_format'] = (string) $this->medium_post_format_conversions[$this->get_xml_attr($content->content[0], 'medium')];$this->globalfeed->print_debug_info('ere');
                        $post_args['post_content'] = (string) trim($content->description);
                        $post_args['meta']['media'] = array( 'unique' => false, 'value' => array(
                            'url' => $this->get_xml_attr($content->content[0], 'url'),
                            'medium' => $this->get_xml_attr($content->content[0], 'medium'),
                            'height' => (int) $this->get_xml_attr($content->content[0], 'height'),
                            'width' => (int) $this->get_xml_attr($content->content[0] ,'width'),
                        ) );
                        $post_args['meta']['rating'] = (string) trim($content->rating);
                        $post_args['meta']['copyright'] = (string) trim($content->copyright);
                        $post_args['meta']['copyright_link'] = (string) $this->get_xml_attr($content->copyright, 'url');
                        $post_args['meta']['thumbnail'] = (string) $this->get_xml_attr($content->thumbnail[0], 'url');
                        $post_args['meta']['thumbnail_size'] = array( 'unique' => false, 'value' => array(
                                'width' => (int) $this->get_xml_attr($content->thumbnail[0], 'width'),
                                'height' => (int) $this->get_xml_attr($content->thumbnail[0], 'height'),
                            ) );

                        // Save the category as an array
                        if ( $this->get_xml_attr($content->category, 'label') != '')
                            $post_args['categories'][(string) trim($content->category)] =  $this->get_xml_attr($content->category, 'label');
                        else
                            $post_args['categories'][(string) trim($content->category)] = (string) trim($content->category);
                    }
                }

                // Append this item to the list
                $feed_items[] = $post_args;
            }

            // Set the last status update received...
            $feed_options['last_update'] = strtotime( $feed_items[0]['post_date'] );       
        }
        
        if ( count($feed_items) <= 0 )
            return 0;
        
        $this->register_feed(true);
        
        // Save all of the feed items.
        
        //$globalfeed->print_debug_info(
            $globalfeed->save_feed_items( $this->feed_slug, $feed_items );
        //);
        
        return count( $feed_items );
    }
    
    /**
     * Gets the given attribute for a simpleXMLElement
     * 
     * @param simpleXMLElement $obj
     * @param str $attr 
     * @return str
     */
    function get_xml_attr( $obj, $attr ){
        $atts = $obj->attributes();
        return (string) trim( $atts[$attr] );
    }

    function process_updates( $initial = false ){
        $globalfeed = &$this->globalfeed;
        $feed_options = &$this->feed_options;
        
        $globalfeed->print_debug_info("async request called. Waiting 10 seconds.");
        sleep(10);
        $globalfeed->print_debug_info('ssync_finished');
        exit;
    }



    /**
    * Makes an HTTP request. This method can be overriden by subclasses if
    * developers want to do fancier things or use something other than curl to
    * make the request.
    *
    * @param String $url the URL to make the request to
    * @param Array $params the parameters to use for the POST body
    * @param CurlHandler $ch optional initialized curl handle
    * @return String the response text
    */

    protected function makeRequest($url, $params, $ch=null) {
          $globalfeed = &$this->globalfeed;
        if (!$ch) {
          $ch = curl_init();
        }
        $globalfeed->print_debug_info($params, 'mbgf_rss');
        $opts = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'rss-php-3.0',
          );
        //if ($this->useFileUploadSupport()) {
          $opts[CURLOPT_POSTFIELDS] = $params;
        //} else {
          $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
        //    }
        $opts[CURLOPT_URL] = $url;

        // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
        // for 2 seconds if the server does not support this header.
        if (isset($opts[CURLOPT_HTTPHEADER])) {
          $existing_headers = $opts[CURLOPT_HTTPHEADER];
          $existing_headers[] = 'Expect:';
          $opts[CURLOPT_HTTPHEADER] = $existing_headers;
        } else {
          $opts[CURLOPT_HTTPHEADER] = array('Expect:');
        }

        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);

        if (curl_errno($ch) == 60) { // CURLE_SSL_CACERT
          $globalfeed->print_error_info('Invalid or no certificate authority found, '.
                         'using bundled information');
          curl_setopt($ch, CURLOPT_CAINFO,
                      dirname(__FILE__) . '/rss_ca_chain_bundle.crt');
          $result = curl_exec($ch);
        }
        $globalfeed->print_debug_info(curl_errno($ch));
        curl_close($ch);
        return $result;
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
     * This function take the rawdata returned by the feed source (ie Titter, RSS etc...) and converts it into
     * the array format expected by $this->globalfeed->save_feed_items(), then it saves it using this function.
     * 
     * @uses $this->globalfeed->save_feed_items()
     * @param array $feed_items An array containing the raw data from the feed source that should be parsed and saved.
     */
    function store_feed_items( $feed_items ){
        $this->globalfeed->print_debug_info( "Store RSS Feed Items: \n" . var_export($feed_items, true) );
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
        wp_enqueue_script('mbgf_rss', plugins_url( 'feeds/mbgf_rss/pages/js/mbgf_rss.js', $mb_globalfeed->plugin_location() ), array('jquery'));
        wp_enqueue_style('mbgf_rss', plugins_url( 'feeds/mbgf_rss/pages/style.css', $mb_globalfeed->plugin_location() ));
        
        // If the initial setup is done, show that. Otherwise show the settings page.
        if ( $this->feed_options['initial_setup_done'] )
            require_once('pages/settings-main.php');
        else
            require_once('pages/setup.php');
        
        // Once the page has been loaded, call the function to display it, and save any modifications to feed information.
        if ( show_mbgf_rss_page( $this->feed_options ) == true )
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
    function mbgf_rss_activate_initial () {
        global $mb_globalfeed;
        $mb_globalfeed->print_debug_info('Activate initial: MB RSS', 'mbgf_rss');
        $activation_instance = new mbgf_rss($mb_globalfeed);
        $activation_instance->activate_feed();
    }

    add_action('mbgf_activate_feed-' . $mb_globalfeed->get_shortened_feed_slug('mbgf_rss'), 'mbgf_rss_activate_initial');
}