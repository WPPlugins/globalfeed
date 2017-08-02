<?php
/*
  Plugin Name: GlobalFeed
  Plugin URI: http://GlobalFeed.MichaelBlouin.ca
  Version: 0.1.3
  Author: <a href="http://www.michaelblouin.ca/">Michael Blouin</a>
  Description: This plugin can integrate nearly any type of feed into the WordPress feed on your blog pages.

  Copyright 2012 Michael Blouin  (email : michael blouin [a t ] mich ael blou in DOT ca)

  Note: This is not a full release of the software, and as such may contain development
        tools and backdoors. Not to be used in a production environment.

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

if ( !class_exists( 'mb_globalfeed' ) ) {

    /**
     * This class contains the core classes that allow the extension of the MB Global Feed plugin.
     * 
     * Note on translation. This plugin uses the text domain 'mb_globalfeed'. 
     *
     * @author Michael Blouin
     * @link www.MichaelBlouin.ca
     */
    class mb_globalfeed {
        private $registered_feeds = array();
        private $settings = array();
        private $saved_options_name = array( 'settings' => 'mb_globalfeed_settings', 'feeds' => 'mb_globalfeed_feeds' );
        private static $_instance;
        private $_scheduler_instance;
        private $_admin_instance = false;
        private $_loaded = false;
        private $current_page = false;
        private $globalfeed_version = '0.1.3';
        private $_in_widget = false;
        private $_in_shortcode = false;
        private $stylesheets_to_load  = array();
        private $_output_buffer = null;
        private $_shortcode_atts = null;
        private $debug = array(
            'notification',
            'debug',
            'mb_youtube',
            'mb_twitter',
            'register_feeds',
            'request_modify', 
            'save_feed_items',
        );
        
        /**
         * Returns true if GlobalFeed is fully initialized.
         * @return type 
         */
        public function loaded() {
            return $this->_loaded;
        }
        
        /**
         * Gets the post_type namespace for GlobalFeed. This is set by default,
         * by users may opt to change it.
         * 
         * @return type 
         */
        public function post_type_namespace() {
            return $this->settings['post_type_namespace'];
        }
        
        /**
         * Returns the current instance of the GlobalFeed admin class.
         * @return type 
         */
        public function admin_instance() {
            return $this->_admin_instance;
        }
        
        /**
         * Returns the path the main GlobalFeed file.
         * @return path The path to the GlobalFeed file
         */
        public function plugin_location() {
            return __FILE__;
        }
        
        /**
         * Returns the current Global Feed version string.
         * 
         * @return bool The version string for the current Global Feed installation.
         */
        function version() {
            return $this->globalfeed_version;
        }
        
        /**
         * Gets the media display mode. Can be either 'embed', 'smart' or 'off'.
         * 
         * When the mode is 'embed', feeds should embed any media into the post content.
         * The location where you should embed it can be gotten using media_display_location().
         * 
         * When the mode is 'smart', feeds should utilize WordPress actions and filters
         * to insert the post content before or after the post_content.
         * 
         * When the mode is 'off', feeds should save media information with the post
         * as usual, but should not embed it or use actions and filters to display it.
         * 
         * @return str embed|smart|off
         */
        function media_display_mode() {
            return $this->settings['media_display_mode'];
        }
        
        /**
         * Gets the media display location. Can either be 'before' or 'after'.
         * 
         * Indicates where the media should show up relative to the post content.
         * @return str before|after
         */
        function media_display_location() {
            return $this->settings['media_display_location'];
        }
    
        /** 
        * Gets the classes that should be applied to each post format. The classes here
        * should only be applied to the top-most element containing the media item.
        * 
        * @param str $post_format The post format to fetch classes for
        * @return str The classes to be applied to the object.
        */
        function post_format_classes( $post_format ) {
            return isset($this->settings['post_format_classes'][$post_format]) ? $this->settings['post_format_classes'][$post_format] : '';
        }
        
        /**
         * Returns whether bylines are to be shown. Can be disabled in the admin.
         * @return bool
         */
        function show_byline() {
            return $this->settings['show_byline'];
        }
        
        /**
         * Returns whether the current theme should show an avatar
         * 
         * @return bool
         */
        function theme_show_avatar() {
            if ( $this->_in_widget || $this->_in_shortcode )
                return $this->_shortcode_atts['theme_show_avatar'];
            else
                return $this->settings['theme_show_avatar'];
        }

        /**
         * Returns whether GlobalFeed is being shown via widget
         * 
         * @return bool
         */
        function in_widget() {
            return $this->_in_widget;
        }
        
        /**
         * Returns whether GlobalFeed is being shown via shortcode
         * 
         * @return bool
         */
        function in_shortcode() {
            return $this->_in_shortcode;
        }

                //This is a debug var.
        var $first_run = false;

        /* Class initialization ------------------------------------------------*/
        
        /**
         * Initializes the GlobalFeed plugin.
         * 
         * This function is private to enforce this class as a singleton. 
         */
        private function __construct() {            
            //Print the debug information
            $this->print_debug_info('---------------------------------------------------------', 'notification');
            $this->print_debug_info("Request by {$_SERVER['REQUEST_METHOD']}" . ' at ' . (string) time() . ' at ' . $_SERVER['REQUEST_URI'], 'notification');
            
            //If the debug var is set to clear... Clear...
            if (isset($_GET['mbgf_reset'])){
                $this->save_registered_feeds();
                $this->save_settings();
            }

            //Load environment
            $this->registered_feeds = get_option( $this->saved_options_name[ 'feeds' ] );
            $this->load_settings();
            
            //Load dependencies
            $this->load_preferred_schedule_interface();    
            require_once( 'mb_globalfeed_widget.php' );
            
            $this->add_actions_and_filters();
            
            //Everythings loaded, load the feeds now.
            $this->load_feeds(); 

            //_set_cron_array(array_diff(_get_cron_array(), $this->call_preferred_scheduler_func('get_scheduled_events')));
            $this->_loaded = true;
            
            $this->print_debug_info($this->registered_feeds, 'registered_feeds');
        }
        
        /**
         * Stops this class from being cloned. (singleton)
         * @return mb_globalfeed
         */
        private function __clone() {
            if (!self::$_instance) {
                self::$_instance = new mb_globalfeed();
            }
            return self::$_instance; 
        }
        
        /**
         * Initializes the class, adding all actions and filters to WP, loading settings, and generally doing what we expect when we initialize!
         * 
         * (Enforces this class as a singleton)
         * 
         * @param boolean $force Forces the creation of a new class, which will re-register all actions and filters -- removing the previous class.
         */
        static function init( $force = false ) {
            if (!self::$_instance) {
                self::$_instance = new mb_globalfeed();
            } elseif ( $force == true ) {
                return new mb_globalfeed();
            }
            return self::$_instance; 
        }
        
        /**
         * Loads GlobalFeed's saved settings from WP options database.
         * 
         * @return array GlobalFeeds settings.
         */
        private function load_settings() {
            $defaults = array(
                'settings_version' => '0.1.3',                          // The settings version that GlobalFeeds settings were at previously. This is how GlobalFeed knows it has been upgraded
                'pages_and_feeds' => array(),                           // The array containing a list of feeds to be displayed on pages.
                'cache_custom_feed_shows' => true,                      // Whether custom_feed_show answers should be cached during execution.
                'post_type_namespace' => 'mbgf_',                       // Prefixed to post types for feeds.
                'auto_print_feed_items_in_loop' => true,                // Automatically print feed items while in the loop.
                'auto_skip_feed_items_in_loop' => true,                 // Automatically jump the loop to the next post without allowing code in the loop to run after printing a feed item.
                'completely_remove_feed_items_from_main_loop' => true,  // Removes feed items from the loop completely -- including counts of posts returned by queries.
                'allow_category_mapping' => false,                      // Whether feed categories should be mapped to WordPress categories
                'allow_tag_mapping' => true,                            // Whether feed tags should be mapped to WordPerss tags.
                'media_display_mode'=> 'embed',                         // How GlobalFeed manages displaying of media from feeds. Defaults to 'embed' can also be 'smart'
                'media_display_location' => 'after',                    // Where the media content is inserted within the feed. 'Defaults to before'. Can also be 'after'.
                'remove_plugin_data_on_deactivate' => true,             // Whether all data should be removed when GlobalFeed is deactivated.
                'show_byline' => true,                                  // Whether to show the GlobalFeed byline. (... via Facebook, etc...)
                'shortcode_skin' => 'fb',                               // The GlobalFeed skin to use on shortcodes.
                'widget_skin' => 'fb',                                  // The GlobalFeed sking to use on widgets.
                'plugin_update_interval' => 600,                        // The default for how often feeds update 
                'autodetect_link_open_new_window' => true,
                'plugin_update_intervals' => array( 
                    'One Minute'  => '60',
                    'Ten Minutes' => '600', 
                    'Thirty Minutes' => '1800', 
                    'Hourly' => '3600', 
                    'Twice a Day' => '43200', 
                    'Once a Day' => '86400', 
                    'Once a Week' => '604800' 
                ),
                'theme_show_avatar' => true,
                'preferred_scheduler' => array(
                    'class_name' => 'mb_globalfeed_wp_scheduler',
                    'class_file_path' => 'wp-schedule-interface.php',
                ),
                'post_format_classes' => array(
                    'video' => 'media-video mbgf-video',
                    'image' => 'media-image mbgf-image',
                    'link' => 'media-link mbgf-link',
                    'quote' => 'meida-quote mbgf-quote',
                ),
                'post_content' => array(
                    'word_count' => false,
                    'character_count' => false,
                    'detect_content_links' => true,
                    'include_media' => true,
                ),
                'post_excerpt' => array(
                    'word_count' => false,
                    'character_count' => false,
                    'detect_content_links' => true,
                    'include_media' => true,
                )
            );
            
            $settings = get_option( $this->saved_options_name[ 'settings' ] );
            $settings = isset( $settings ) ? $settings : add_option($this->saved_options_name[ 'feeds' ], array());
            
            $this->settings = wp_parse_args( $settings, $defaults );
            $this->settings['plugin_update_intervals']['One Minute'] = 60;
            return $this->settings;
        }

        /**
         * Saves GlobalFeed's saved settings in the WP options database.
         * @return boolean 
         */
        public function save_settings() {            
            update_option( $this->saved_options_name[ 'settings' ], $this->settings ) ;
            return true;
        }
        
        /**
         * This function includes the mbgf_theme class for use in themeing feed admin pages.
         * 
         * It is not loaded by default on feed pages.
         *  
         */
        function include_admin_theming() {
            require_once('admin/mbgfa_theme.php');
            global $mbgfa_theme;
            $mbgfa_theme = new mbgfa_theme();
        }
        
        /**
         * Includes the file for the preferred scheduler interface.
         */
        private function load_preferred_schedule_interface() {
            //Get the scheduler that has been chosen for use.
            $preferred_scheduler = $this->settings[ 'preferred_scheduler' ];
            
            if ( strstr( $preferred_scheduler[ 'class_file_path' ], '/' ) === 0) 
                require_once( $preferred_scheduler[ 'class_file_path' ] );
            else
                require_once( dirname(__FILE__) . '/event_scheduler_interfaces/' . $preferred_scheduler[ 'class_file_path' ] );
            
            if ( !$this->_scheduler_instance )
                $this->_scheduler_instance = new $preferred_scheduler['class_name'](&$this);
        }
        
        /**
         * Calls a function with the specified name from the preferred scheduler interface. Passes on all additional
         * arguments provided to this function.
         * 
         * @param string The name of the function to from the preferred scheduler to call
         * @return mixed The return value of the function
         */
        private function call_preferred_scheduler_func( $func_name ){
            $this->load_preferred_schedule_interface();
            
            // Get the arguments this function was passed, and pass them to the function we call.
            $args = func_get_args();
            unset( $args[0] );
            $args = array_values($args);

            // Call the function via the scheduler instance.
            return $this->_scheduler_instance->$func_name($args);
            //return call_user_func_array( array( $preferred_scheduler[ 'class_name' ], $func_name ), array_values($args) );
        }
        
        /**
         * Schedules an event to be automatically run on a given time interval -- preferably using Cron Jobs, but will
         * use other methods if Cron Jobs are unavailable.
         * 
         * @TODO Find a means of scheduling updates.
         * 
         * @uses $this->call_preferred_scheduler_func()
         * @param string $caller The slug for the item that wants the action. (Usually the feed slug).
         * @param string $action The action to be performed.
         * @param int $period How often the action should be performed. (In seconds)
         * @param timestamp $first (optional) When the first execution should be. (Not always supported)
         * @param bool $update Whether if the action already has an event it should be updated.
         */
        function schedule_event( $caller, $action, $period, $first = 0, $update = false ) {
            return $this->call_preferred_scheduler_func( 'schedule_event', $caller, $action, $period, $first, $update );
        }
        
        /**
         * Removes a scheduled event. If just the caller is supplied then all events for the caller will be removed.
         * 
         * @uses $this->call_preferred_scheduler_func()
         * @param string $caller The slug of the caller.
         * @param type $action (optional) The slug of the action to be performed.
         */
        function unschedule_event( $caller, $action = ''){
            return $this->call_preferred_scheduler_func( 'unschedule_event', $caller, $action );
        }
        
        /**
         * Called when the default feed update interval is changed. Re-schedules events.
         *  
         */
        function change_default_update_interval() {
            foreach ($this->registered_feeds as $slug => $feed) {
                if ( $feed['request_frequency'] !== 'mbgf_default' )
                    continue;
                
                // Unschedule and re-schedule feed update
                $this->unschedule_event( $feed['class_name'], 'update_feed' );
                if ( !$this->schedule_event( $feed['class_name'], 'update_feed', 'mbgf_default' ) )
                        $this->print_debug_info ('Recreating scheduled event did not succeed');
            }
        }
        
        /**
         * Gets the post formats supported by the current WordPress theme.
         * 
         * @return array|bool An array containing the post_formats supported by the current theme or false if post formats aren't currently supported.
         */
        private function detect_post_formats() {
            if ( !current_theme_supports( 'post-formats' ) )
                return false;
            
            $post_formats = (array) get_theme_support( 'post_formats' );
            
            return $post_formats;
        }
        
        /**
         * Prints a debug message to the debug log. Will only print debug messages
         * for catagories that are in the $debug variable. This function is to aid 
         * developers. 
         * 
         * If you are having problems working with a functionality of GlobalFeed,
         * find its debug namespace and manually add that to the debug variable.
         * Then messages from that codes execution will appear in the debug log.
         * 
         * @param string $message The message/variable to print out in the debug log.
         * @param string $debug_catagory The catagory of the debug message.
         */
        function print_debug_info( $message , $debug_catagory = 'debug'){
            if ( in_array($debug_catagory, $this->debug )) {
                $logfile = dirname(__FILE__)."/log.txt";
                
                if ( !is_writable($logfile) )
                    return $message;
                
                $fh = fopen( $logfile, 'a' ) or die('Cant open logfile.');
                if (is_string($message))
                    fwrite($fh, "\n\n".((string) $message)) or die('Cant write logfile.');
                else
                    fwrite($fh, "\n\n".((string) var_export($message,true))) or die('Cant write logfile.');
                fclose($fh) or die( 'Cant close logfile' );
            }
            
            return $message;
        }
        
        /* WordPress Actions and Filters ---------------------------------------*/
        
        /**
         * Add the actions and filters that GlobalFeed uses to WordPress.
         * 
         * Also registers shortcode and widget
         * 
         * (Usually called when a new mb_globalfeed class is instantiated.)
         */
        private function add_actions_and_filters() {
            $instance = &self::$_instance;
            
            // Filters
            // This filter will add the needed parameters to the query to inject additional feeds into the loop.
            if ( $this->settings['auto_print_feed_items_in_loop'])
                add_filter( 'request', array( &$instance, 'modify_request' ) , 1);
            
            // Add cron schedules
            add_filter( 'cron_schedules', array( &$this, 'add_cron_schedules' ));
            
            // Actions
            register_activation_hook( __FILE__, array( &$this, 'activate' ) );
            register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ));
            
            // Register our text domain for translators
            add_action('init', array( &$this, 'register_text_domain' ));
            
            // Shortcode
            add_shortcode('globalfeed', array( &$this, 'do_shortcode'));
            
            // Widget
            add_action( 'widgets_init', array( &$this, 'register_widget' ) );
            
            //Register our administration menus
            if ( is_admin() ){
                add_action( 'admin_menu', array( &$this, 'register_admin_menu') );
            }
        }
        
        /** Registers the plugin widget
         * 
         */
        function register_widget() {
            register_widget( 'mb_globalfeed_widget' );
        }
        
        /**
         * Called when MB_Globalfeed is activated in WordPress
         */
        function activate() {
            global $mb_globalfeed;
            
            $this->first_run = true;
            
            $mb_globalfeed = mb_globalfeed::init(true);
            
            $mb_globalfeed->create_options();
            
            //Load dependencies
            require_once( dirname(__FILE__).'/event_scheduler_interfaces/mb_globalfeed_event_scheduler_interface.php');
            
            if ($this->registered_feeds === false){
                $this->registered_feeds = array();
                $this->save_registered_feeds();
            }
            
            $mb_globalfeed->print_debug_info('Plugin activated.', 'notification');
        }
        
         /**
         * Creates plugin options
         */
        function create_options(){
            // Create plugin options
            $this->load_settings();
            add_option( $this->saved_options_name['settings'], $this->settings );
            add_option( $this->saved_options_name['feeds'], array() );
        }
        
        /**
         * Called when MB_GlobalFeed is deactivated in WordPress 
         */
        function deactivate() {
            $settings = $this->settings;
            if ($settings['remove_plugin_data_on_deactivate']) {
                $success = true;
                
                // Unregister and deactivate all feeds -- removing feed data.
                foreach ($this->registered_feeds as $slug => $feed) {
                    $this->unregister_feed($slug);
                }
                
                //Remove schedules
                _set_cron_array(array_diff(_get_cron_array(), $this->call_preferred_scheduler_func('get_scheduled_events')));
                
                //This should remove all plugin data.
                $success = $success && delete_option($this->saved_options_name['settings']);
                $success = $success && delete_option($this->saved_options_name['feeds']);
                
                //Also remove logfile.
                if ( count($this->debug) === 0 )
                    unlink ( dirname(__FILE__) . '/log.txt' );
                
                if ($success)
                    $this->print_debug_info ('Plugin successfully deactivated with full data removal.', 'notification');
                else
                    $this->print_debug_info ('Plugin deactivation failed.', 'notification');
            }
                
        }
        
        /**
         * Adds GlobalFeed admin item to the menu if the user is in the administration section.
         * 
         * (Called by WordPress filter) 
         */
        function register_admin_menu() {
            require_once(dirname(__FILE__).'/admin/mb_globalfeed_admin.php');
            //If we're in the administration add GlobalFeed to the menu
            if ( is_admin() ) {
                $page = add_menu_page('MB GlobalFeed', 'GlobalFeed', 'manage_options', 'globalfeed', array( &$this, 'show_admin_page' ), plugins_url( 'admin/pages/images/GlobalFeed-16x16.png', __FILE__ ));
               
                add_action( 'admin_print_styles-' . $page, array( 'mb_globalfeed_admin', 'admin_styles' ) );
            }
        }
        
        /**
         * Registers the plugin text domain for translators
         * 
         * (Called by WordPress filter) 
         */
        function register_text_domain() {
            load_plugin_textdomain('mb_globalfeed');
        }
        
        /**
         * Adds the cron schedules to the list of available schedules in WordPress.
         * 
         * (Called by WordPress filter) 
         */
        function add_cron_schedules( $schedules ) {
            if ( !isset($schedules) || !is_array($schedules))
                $schedules = array();
            
            $schedules['mbgf_default'] = array(
                'interval' => (int) $this->settings['plugin_update_interval'],
                'display' => 'GlobalFeed default plugin update interval'
            );
            
            $schedules['tenminutes'] = array(
                'interval' => 600,
                'display' => 'Every 10 Minutes'
            );
            
            return $schedules;
        }

        /**
         * This function is called by WordPress when the GlobalFeed admin page
         * is supposed to be shown.
         * 
         * Function loads and instantiates the admin handler, and sets it on its way. 
         * 
         * (Called by WordPress filter)
         */
        public function show_admin_page() {
            require_once( dirname(__FILE__) . '/admin/mb_globalfeed_admin.php' );
            
            $this->_admin_instance = new mb_globalfeed_admin( &$this->settings );
            $this->_admin_instance->show_current_admin_page();
        }
        
        /**
         * Injects feeds into the loop depending on the current page.
         * 
         * Called using the 'request' filter.
         * 
         * @param type $request The current request options.
         */
        function modify_request( $request ){
            $this->print_debug_info("Request Modification Started.",'request_modify');

            if ( !is_array($request) )
                $request = array();
            
            $fake_query = new WP_Query();
            $fake_query->parse_query( $request );
            
            // Global Feed does not currently support admin sections. (Although this can be overriden.)
            // In addition, adding 'mb_globalfeed' = false to the request variable will stop the plugin for the current request;
            if ( ($fake_query->is_admin && false == apply_filters( 'mb_globalfeed_allow_admin_request_mod', false ) ) 
                    || ( isset( $request[ 'mb_globalfeed' ] ) && $request[ 'mb_globalfeed' ] == false ) ) 
                return $request;
            
            // Do not overwrite the query on pages, or else the page won't be displayed. (Unless mb_globalfeed=true is specified
            if ( $fake_query->is_page && !( isset( $request[ 'mb_globalfeed' ] ) && $request[ 'mb_globalfeed' ] == true ) )
                return $request;
            
            $current_page = '';
            $pages = $this->settings[ 'pages_and_feeds' ];
            $registered_feeds = $this->registered_feeds;

            // Determine the page type
            if ( $fake_query->is_admin ) 
                $current_page = 'home';
            elseif ( $fake_query->is_home )
                $current_page = 'home';
            elseif ( $fake_query->is_posts_page )
                $current_page = 'posts_page';
            elseif ( $fake_query->is_archive ) 
                $current_page = 'archive';
            elseif ( $fake_query->is_author )
                $current_page = 'author';
            elseif ( $fake_query->is_search )
                $current_page = 'search';
            elseif ( $fake_query->is_404 )
                $current_page = '404';
            elseif ( $fake_query->is_tag )
                $current_page = 'tag';
            elseif ( $fake_query->is_feed )
                $current_page = 'feed';   
            elseif ( $fake_query->is_category )
                $current_page = 'category';
            elseif ( $fake_query->is_tax )
                $current_page = $fake_query->taxonomy;
            
            $this->current_page = $current_page;
            
            // Get the info for the current page
            $current_page_info = array(
                'page' => $current_page,
                'category' => (isset($fake_query->category__in) ? $fake_query->category__in : array()),
                'tag' => (isset($fake_query->tag__in) ? $fake_query->tag__in : array()),
                'request' => $request,
                'query' => &$fake_query
            );
            
            // For tags and categories, make the tag/category number the array key
            $cats = array();
            if ( $current_page_info[ 'category' ] )
                foreach ($current_page_info[ 'category' ] as $category) {
                    $cats[ $category ] = $category;
                };

            $tags = array();
            if ($current_page_info[ 'tag' ] )
                foreach ($current_page_info[ 'tag' ] as $tag) {
                    $tags[ $tag ] = $tag;
                };
            
            $current_page_info[ 'category' ] = $cats;
            $current_page_info[ 'tag' ] = $tags;
            
            $current_page_info = apply_filters( 'mb_globalfeed_page_info-' . $current_page, $current_page_info );
           
            if ( !isset( $pages[ $current_page ] ) )
                $pages[ $current_page ] = array();
            
            // Prepare the list of feeds that should be shown. (Only for request or callback feeds by default.)
            if ( in_array( $current_page, array( 'category', 'tag' ) ) ) {
                $feeds = array_intersect_key( $current_page_info[ $current_page ], $pages[ $current_page ] );
            } else {
                $feeds = $pages[ $current_page ];
            }
            
            // Feeds that use the custom type can use this filter to choose which feeds are shown, and register themselves.
            $feeds = apply_filters( 'mb_globalfeed_feeds_to_show', $feeds, $current_page_info);
            $feeds = apply_filters( 'mb_globalfeed_feeds_to_show-' . $current_page, $feeds, $current_page_info);
            
            // Prepare the post types to fetch
            $post_types = array('post');
            foreach ( $feeds as $feed_slug ) {
                $post_type = $this->settings[ 'post_type_namespace' ] . $this->get_shortened_feed_slug( $feed_slug );
                //Don't register feeds that aren't activated.
                if ( !isset( $registered_feeds[ $feed_slug ] ) )
                    continue;
                
                // DB post_type field is VARCHAR(20)
                $post_types[] = $post_type;
                
                $this->register_feed_post_type( $feed_slug );
            }
            
            // Add the post types to the query and return
            if ( count( $post_types ) > 0 ) {
                if ( isset($request[ 'post_type' ]) )
                    $request[ 'post_type' ] = array_unique( array_merge( (array) $request[ 'post_type' ], $post_types ) );
                else
                    $request[ 'post_type' ] = $post_types;
            }
            
            $this->print_debug_info("Request Modified.", 'notifications');
            
            return $request;
        }
        
        /** 
         * Registers a post type for a feed...
         * 
         * @param type $feed_slug 
         */
        function register_feed_post_type( $feed_slug ){
            $feed = $this->settings['post_type_namespace'] . $this->get_shortened_feed_slug($feed_slug);
            register_post_type ( $feed, 
                array(
                    'labels' => array(
                            'name' => $feed
                        ),
                    'public' => true,
                    'publicly_queryable' => true,
                    'show_ui' => false, 
                    'show_in_menu' => false, 
                    'query_var' => true,
                    'rewrite' => false,
                    'capability_type' => 'post',
                    'has_archive' => true, 
                    'hierarchical' => false,
                    'menu_position' => null,
                    'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'custom_fields', 'post-formats' )
                ));
        }
        
        /**
         * Effectuates the plugins' shortcode. Prints out feed items as per user settings.
         * 
         * Also called by the widget.
         * 
         * @param type $atts 
         */
        public function do_shortcode( $atts ) {
            if ( !$this->_in_widget )
                $this->_in_shortcode = true;
            
            $this->_shortcode_atts = shortcode_atts(array(
                'num_items' => 10,
                'feeds' => 'all',
                'container_class' => 'globalfeed-container',
                'include_blog_posts' => false,
                'use_globalfeed_theme' => 'default',
                'theme_show_avatar' => $this->settings['theme_show_avatar'],
            ), $atts);
            
            extract( $this->_shortcode_atts );
            
            if ( $feeds == 'all' ) {
                // Load all of the registered feeds
                $feeds = array_keys($this->registered_feeds);
            } else {
                // Load the feeds the user specified as a comma-delimited list.
                $feeds_set = explode(',', $feeds);
                $feeds = array();
                
                // Ensure that each feed name is valid...
                foreach ($feeds_set as $feed) {
                    if (array_key_exists($this->get_shortened_feed_slug($feed), $this->registered_feeds) )
                        $feeds[] = $feed;
                }
            }
            
            // Get the database post_type string for each feed
            $post_types = array();
            foreach ($feeds as $feed) {
                $post_types[] = $this->post_type_namespace() . $this->get_shortened_feed_slug($feed);
                $this->register_feed_post_type( $feed );
            }
            
            // If were to include WordPress posts...
            if ($include_blog_posts)
                $post_types[] = 'post';
            
            // Preserve the original query...
            global $wp_query;
            $old_query = $wp_query;
            
            // Prepare the posts query...
            $query_vars = array(
                'post_type' => $post_types,
                'post_status' => 'publish',
                'posts_per_page' => $num_items,
                'numberposts' => $num_items,
            );
            
            // Execute the posts query
            query_posts($query_vars);
            
            // Create an output buffer to contain the themed output
            ob_start();
            $this->_output_buffer = "<div id='globalfeed-container' class='$container_class'>";
            
            if ( $use_globalfeed_theme != false ) {
                // Use GlobalFeed templates to theme the output. Pretty nifty.
                while ( have_posts() ) : the_post();
                    $this->get_template_part( 'content', substr(get_post_type(), 5), get_post_format(), $use_globalfeed_theme );
                endwhile;
            } else {    
                // Use the WordPress theme files to style stuff.. Also pretty nifty.
                while ( have_posts() ) : the_post();
                    get_template_part( 'content', get_post_format() );;
                endwhile;
            }
            
            // Save the output buffer contents and remove it.
            $this->_output_buffer .= ob_get_contents() . '</div>';
            ob_end_clean();
            
            // Clean and reset the WordPress loop
            wp_reset_query();
            $ob = $this->_output_buffer;
            $this->_output_buffer = null;
            // Return the formatted feed items...
            return $ob;
        }
        
        /**
         * Gets the list of available themes.
         * 
         * @todo Should detect available themes by looking in the templates directory.
         * 
         * @return array The list of available themes in array( slug => name ) format.
         */
        public function available_themes() {
            return array(
                'wide' => 'Wide',
                'fb' => 'FB',
            );
        }
        
        /* Feed Loading/Utility functions --------------------------------------*/
        
        /**
         * Returns the list of currently registered feeds.
         * 
         * @return array The list of registered feeds.
         */
        public function get_registered_feeds() {
            $ret = array();
            
            foreach ($this->registered_feeds as $slug => $feed) {
                $ret[ $slug ] = $feed['feed_name'];
            }
            
            return $ret;
        }
        
        /** 
         * Loads a feeds' template for displaying of a post item. 
         * 
         * Very similar to the WordPress get_template_part() and locate_template() functions.
         * 
         * @uses load_template()
         * @param str $template_slug The template file slug.
         * @param str $feed_slug The feed to load the template file for
         * @param str $name The specialized name of the template file
         */
        function get_template_part( $template_slug, $feed_slug, $name = null, $globalfeed_theme = null ) {
            do_action( "mbgf_get_template_part_{$template_slug}", $template_slug, $feed_slug, $name );

            // Get a list of file names
            $templates = array();
            if ( !empty($name) )
                    $templates[] = "{$template_slug}-{$name}.php";

            $templates[] = "{$template_slug}.php";
            
            // Define template file locations (for the feed and globalfeed default
            if ( !empty($feed_slug) )
                $templates_path = dirname($this->registered_feeds[$feed_slug]['file_path']) . '/templates/';
            else
                $templates_path = '';
            
            $feed_templates_path = dirname(__FILE__) . "/templates/";
            
            // Load the template path for the currently selected template
            if ( $globalfeed_theme != null && $globalfeed_theme != 'default' )
                $feed_templates_path .= $globalfeed_theme . '/';
            elseif ( $this->_in_shortcode )
                $feed_templates_path .= $this->settings['shortcode_skin'] . '/';
            elseif ( $this->_in_widget )
                $feed_templates_path .= $this->settings['widget_skin'] . '/';

            // Locate the best template to use..
            $located = '';
            foreach ( (array) $templates as $template_name ) {
                    if ( !$template_name )
                        continue;
                    if ( file_exists($feed_templates_path . $template_name)) {
                        $this->load_theme_stylesheet($feed_templates_path);
                        $located = $feed_templates_path . $template_name;
                        break;
                    } else if ( !empty($templates_path) && file_exists($templates_path . $template_name) ) {
                        $this->load_theme_stylesheet($templates_path);
                        $located = $templates_path . $template_name;
                        break;
                    }
            }

            // Load the template with require()
            if (  '' != $located )
		load_template( $located, false );
        }
        
        /**
         * Attempts to load the stylesheet in the given path. Will use the current
         * output buffer if on is set.
         * 
         * @param str $path The path to search
         */
        function load_theme_stylesheet( $path ) {
            if ( file_exists( $path . 'style.css')  && !in_array($path . 'style.css', $this->stylesheets_to_load) ){
                $incl = '<link rel="stylesheet" type="text/css" href="' . plugins_url( str_replace(dirname(__FILE__), '', $path) . 'style.css', __FILE__ ) . '" />';
                if ( $this->_output_buffer != null )
                    $this->_output_buffer = $incl . $this->_output_buffer;
                else
                    echo $incl;
                
                $this->stylesheets_to_load[] = $path . 'style.css';
            }
        }
        
        /**
         * This function tells a feed to show its administrative page. Called from
         * the admin page handler. Also ensures that the feed is loaded first.
         * 
         * @param string $feed_slug The slug for the feed that should be loaded.
         * @return bool Returns true if the feed admin was loaded, or false if it failed.
         */
        public function show_feed_admin( $feed_slug ) {
            //Sanity check
            if ( empty( $feed_slug ) || !$this->feed_activated( $feed_slug ) )
                return false;
            
            $feed_class = $this->load_feeds( $feed_slug );
            if ( $feed_class === false )
                return false;
            
            call_user_func( array( $feed_class, 'print_admin_page' ) );
            
            return true;
        }
        
        /** 
         * Loads the feeds that are marked to be auto loaded on run. Will only
         * load feeds that have already been registered.
         * 
         * Alternatively, can be called with a feed slug to load a specific feed.
         * 
         * @param string $feed_slug The slug for the feed to be loaded.
         */
        private function load_feeds( $feed_slug = false ){
            $feeds = &$this->registered_feeds; 
            
            if ( empty($feed_slug) ){
                // Include the file for all feeds requiring autoloading.
                if ( !empty($feeds) ) {
                    foreach ( $feeds as $slug => &$feed ) {
                        if ( !isset($feed['autoload']) || $feed['autoload'] !== false )
                            require_once($feed['file_path']);

                        // Instantiate the class
                        global $$feed['class_name'];
                        $$feed['class_name'] = new $feed['class_name'](&$this);

                        $feed['loaded'] = class_exists( $feed[ 'class_name' ] );
                        
                        $this->print_debug_info("Feed: {$slug} loaded...", 'register_feeds');
                    }
                }
            } else { // A feed has been specified to be loaded.
                $valid = false;
                foreach ($feeds as $slug => $feed) {
                    if ( $slug == $feed_slug ){
                        $valid = true;
                        break;
                    }
                }
                
                // It appears that the feed requested has not been registered.
                if ( !$valid )
                    return;
                
                $feed = &$feeds[ $feed_slug ];
                
                if ( isset($feed['loaded']) && $feed['loaded'] ) {
                    global $$feed['class_name'];
                    return $$feed['class_name'];
                }
                require_once $feed['file_path'];
                
                global $$feed['class_name'];
                // Instantiate the class
                $$feed['class_name'] = new $feed['class_name'](&$this);

                $feed['loaded'] = class_exists( $feed['class_name'] );

                $this->print_debug_info("Feed: {$slug} loaded.", 'register_feeds');
                
                return $$feed['class_name'];
            }
        }
        
        /**
         * Checks that the feed is compatible based off of versioning information 
         * retrieved from the feed headers
         * 
         * @param array $feed The headers from the feed to check compatibility for.
         * @return array|bool Returns an array of requirements or true if they are met
         */
        function feed_compatible( $feed ) {
            global $wp_version;
            $requirements = array(
                'Wordpress Version' => true,
                'GlobalFeed Version' => true
            );
            
            // Check that WordPress is >= v3.0
            $wpv = explode('.', $wp_version);
            $wp_required =  explode('.', $feed['WordPressVersion']);
            foreach ($wp_required as $i => $value) {
                if ( !isset($wpv[$i]) || ((int) $wpv[$i]) < ((int) $value) ) 
                    $requirements[ 'WordPress Version' ] = sprintf( __("{$feed['Name']} requires WordPress v%s+.", $feed['Slug']), $feed['WordPressVersion'] );
            }

            // Check that Global Feed is v0.1 or greater
            $gfv = explode('.', $this->version());
            $globalfeed_required =  explode('.', $feed['GlobalFeedVersion']);
            foreach ($globalfeed_required as $i => $value) {
                if ( !isset($gfv[$i]) || ((int) $gfv[$i]) < ((int) $globalfeed_required[$i]) ) 
                    $requirements[ 'GlobalFeed Version' ] = sprintf( __("{$feed['Name']} requires GlobalFeed v%s+.", $feed['Slug']), $feed['GlobalFeedVersion'] );
            }

            unset( $gfv, $globalfeed_required, $wpv, $wp_required );
            
            // Check that all requirements are met
            foreach ( $requirements as $requirement ) {
                if ( $requirement != true )
                    return $requirements;

                return true;
            }
        }
        
        /**
         * Checks whether the given feed is activated in Globalfeed.
         * 
         * @param str $feed_slug The feed slug to check
         * @return bool True if activated, false if not.
         */
        function feed_activated( $feed_slug ){
            return isset( $this->registered_feeds[ $this->get_shortened_feed_slug($feed_slug) ] );
        }
        
        /**
         * Returns the registered feed's options if the given feed is activated
         * or false if not.
         * 
         * @param str $feed_slug The feed slug to shorten
         * @return str|bool The shortened feed slug, or false if the feed is not registered.
         */
        function get_feed_options( $feed_slug ){
            return $this->feed_activated( $feed_slug ) ? 
                    $this->registered_feeds[ $this->get_shortened_feed_slug($feed_slug) ] : false;
        }
        
        /**
         * Gets the effective shortened feed slug given a string. Essentially, returns
         * a slug whose length is database compatible.
         * 
         * @param string $slug The string to shorten
         * @return string The shortened string
         */
        function get_shortened_feed_slug( $slug ){
            return substr( $slug, 0, 20 - strlen( $this->post_type_namespace() ) );
        }
        
        /**
         * This function registers a feed to be processed, and registers the type of feed.
         * 
         * Feed Types:
         *      request: The server should send requests to the source server periodically to get feed updates.
         *      callback: After registering, the feed will automatically be notified by the source of updates.
         *      custom: For when plugins need more control than the other two types as to how it updates its feed. Also gives the feed more control over where it shows up.
         * 
         * Options:
         *      feed_name: The user-friendly name of the feed that will be displayed on configuration pages.
         *      feed_slug: A slug version of the feed name. (Should not contain spaces or special characters)
         *      feed_type: The feed type as defined above.
         *      file_path: The path to the code that provides an interface for the feed. (Relative to the globalfeed main class or the server root - In most cases - your plugin)
         *      request_frequency: How often the feed should request an update from the source (in seconds). Only for request feeds.
         *      update: If true then if a feed with the same name has already been registered its options will be updated 
         *                  with the other options provided here.
         * 
         * Feed Options:
         *      pages_to_show: An array of pages where the plugin should be shown. Defaults to "all"
         *      pages_not_to_show: An array of pages where the plugin should not be shown. Empty by default. Overrides pages_to_show
         * 
         * @param array $options The feed options. (feed_name, feed_slug, feed_type, file_path, update)
         * @param array $feed_options The options/settings that are specific to your feed. This includes required info like where your feed can be shown, but can contain any setting.
         * 
         * @todo Optmization: This should check if the pages_to_show or pages_not_to_show has changed, or any factor that would effect this that may have changed. If it hasn't, don't try recalculating feeds. It's not needed.
         * 
         * @return boolean Will return true if the feed is registered and false if not.
         */
        function register_feed( $options, $feed_options ) {
            $registered_feeds = $this->registered_feeds;
            $this->print_debug_info("Register_feeds", 'register_feeds');
            
            if ( empty( $options ) || !is_array( $options ) )
                return;
            
            $default_feed_options = array(
                'pages_to_show' => array( 'all' ),      //The pages to show this feed on.
                'pages_not_to_show' => array(),         //The pages where this feed should not be shown. Overrides pages_to_show.
                'custom_feed_show' => false,            //Whether this feed decides during runtime (Using a filter) whether it should be shown.
                'feed_show_action' => false             //Whether the function that displays an individual feed item should by called be an action instead of the default 'print_feed_item' function.
            );
            
            $feed_options = wp_parse_args( $feed_options, $default_feed_options );
            
            $defaults = array(
                'feed_name' => '',
                'feed_slug' => '',
                'feed_type' => 'request',
                'feed_class_name' => '',
                'file_path' => '',
                'activated' => false,
                'update' => false,
                'autoload' => true,
                'request_frequency' => 'mbgf_default'
            );
            
            $options = wp_parse_args( $options, $defaults );
            
            //The post type plus the Global Feed namespace cannot be more than 20 characters.
            $options[ 'feed_slug' ] = $this->get_shortened_feed_slug( $options[ 'feed_slug' ] ); 
            
            if ( isset($options['feed_options']) )
                unset($options['feed_options']);
            
            extract( $options );

            //Check if everything that is required has been given
            if ( empty( $feed_name ) || empty( $feed_slug ) || empty( $feed_type ) || empty( $file_path ) )
                return false;
            
            //Check if this feed has already been registered, and if so are we being told to update it.
            if ( $update == false && !empty( $registered_feeds[ $feed_slug ] ) ) {
                $this->print_debug_info('Feed Not Registered.', 'register_feeds');
                return false;
            } else {
                unset( $options[ 'update' ] );
                    
                $options[ 'feed_options' ] = $feed_options;
                
                $options = apply_filters( 'mb_globalfeed_register_feed-' . $feed_slug, $options );
                
                //Do not allow feeds to force their own activation
                if ( !empty( $registered_feeds[ $feed_slug ] ) && isset($registered_feeds[ $feed_slug ][ 'activated' ]) )
                    $options[ 'activated' ] = $registered_feeds[ $feed_slug ][ 'activated' ];
                else
                    $options[ 'activated' ] = true;
                
                // We're good to save feed settings
                $this->registered_feeds[ $options[ 'feed_slug' ] ] = $registered_feeds[ $options[ 'feed_slug' ] ] = $options;
                
                $success = $this->save_registered_feeds() && $this->calculate_feeds_on_pages();
                
                // Make sure everything saved okay
                if ( $success !== true )
                    return $success;
                
                // If this feed is a request type, schedule its update unless it had the dont_schedule_update_event option set.
                if ( $feed_type == 'request' && empty( $dont_schedule_update_event ) ) {
                    $events = $this->call_preferred_scheduler_func('get_scheduled_events');
                    $schedule = true; // Should this be scheduled?
                    //$this->print_debug_info($events);
                    foreach ($events as $value) {
                        $value = array_values($value);
                        $value = array_values($value[0]);
                        
                        //$this->print_debug_info(($value[0]['args']['caller'] == $class_name && $value[0]['args']['action'] == 'update_feed'));
                        if ($value[0]['args']['caller'] == $class_name && $value[0]['args']['action'] == 'update_feed')
                            $schedule = false;
                    }
                    
                    if ($schedule) $success = $this->schedule_event( $class_name, 'update_feed', $request_frequency, 0, $update );
                }
                $this->print_debug_info("Feed Registered.");
                
                return $success;
            }
        }

        /**
         * This removes a feed from the list of registered feeds, so that it no longer appears in options menus
         * and its callbacks are no longer processed. If the destroy option is set to true, this function also destroys
         * all saved feed data.
         * 
         * Note: Feed options registered using register_feed() are always destroyed.
         * 
         * 
         * @param type $feed_slug The slug for the feed that should be unregistered.
         * @param tpye $destroy Whether all feed data should be destroyed or just feed options.
         * @return type boolean Returns true if saving succeeded and false if not.
         */
        function unregister_feed( $feed_slug, $destroy = false ) {
            $feed_slug = $this->get_shortened_feed_slug($feed_slug);
            
            $this->print_debug_info($this->registered_feeds);
            
            // Remove the scheduled events
            $this->unschedule_event( !empty($this->registered_feeds[ $feed_slug ]['feed_class_name']) ? $this->registered_feeds[ $feed_slug ]['feed_class_name'] : $this->registered_feeds[ $feed_slug ]['class_name']);
            
            // Let the feed do anything special it needs doing.
            do_action('mbgf_unregister_feed-' . $feed_slug);
            
            // Force destroy feed settings.
            unset( $this->registered_feeds[ $feed_slug ] );
            $this->print_debug_info('Feed UnRegistered.', 'register_feeds');
            return $this->save_registered_feeds();
        }

        /**
         * Saves the feeds in the $registered_feeds variable.
         * 
         * @return boolean Returns true if saving succeeded and false if not.
         */
        private function save_registered_feeds(){
            //Remove any state settings
            $feeds = $this->registered_feeds;
            //$this->print_debug_info(var_export($feeds, true));
            if (!empty($feeds))
                foreach ($feeds as &$feed) {
                    unset( $feed[ 'activated' ] );
                };
            
            //Save feeds
            $result =  update_option( $this->saved_options_name['feeds'], $this->registered_feeds );
            //$this->print_debug_info("Feeds Saved.", 'save_feeds');
            return true;
        }

        /**
         * Saves the provided feed items to the Database. This function expects items to be in the same format as
         * wp_insert_post()
         * 
         * @todo Add the ability to map client post types to WP post types in GlobalFeed UI.
         * @todo Add the ability to map categories to WP Categories.
         * 
         * @uses wp_insert_post()
         * @param array $feed_items The feed items that should be saved.
         * @return false|WP_Error|array Returns false if nothing was done, WP_Error if an error was raised, or an array of post_ids if success.
         */
        function save_feed_items( $feed_slug, $feed_items ) {            
            $this->print_debug_info('Save feed items started.', 'mb_globalfeed');
            
            // Check that the specified feed is registered
            if ( !array_key_exists( $this->get_shortened_feed_slug($feed_slug), $this->registered_feeds ) )
                return;
            
            // Check that $feed_items is an array
            if ( !is_array($feed_items) ) 
                return new WP_Error( 'Unexpected Format', 'MB GlobalFeed: An array was expected, but an item of type ' . gettype ( $feed_items ). 'was given.');
            
            //Check that there are items in the array
            if ( count($feed_items) <= 0)
                return false;
            
            //Check that the array is an array of arrays
            if ( !is_array($feed_items[0]) )
                return false;
            
            //Okay, everythings checked, start looping through items.
            $results = array();
            foreach ( $feed_items as $feed_item ) {
                // Check if categories are to be mapped...
                if ( isset($feed_item['categories']) )
                    if ( $this->settings['allow_category_mapping'] ==  true ){
                        // This is not implemented yet...
                    } else { // Save categories as meta instead
                        if ( !isset($feed_item['meta']) )
                            $feed_item['meta'] = array();
                        
                        $feed_item['meta']['categories'] = array( 'unique' => true, 'value' => $feed_item['categories'] );
                        unset($feed_item['categories']);
                    };
                    
                if ( isset($feed_item['tags']) )
                    if ( $this->settings['allow_tag_mapping'] ==  true ){
                        // This is not implemented yet...
                    } else { // Save tags as meta instead
                        if ( !isset($feed_item['meta']) )
                            $feed_item['meta'] = array();
                        
                        $feed_item['meta']['tags'] = array( 'unique' => true, 'value' => $feed_item['tags']);
                        unset($feed_item['tags']);
                    };

                //Set a couple defaults
                $feed_item[ 'post_status' ] = !empty( $feed_item[ 'post_status' ] ) ? $feed_item[ 'post_status' ] : 'publish';
                $feed_item[ 'post_type' ] = $this->settings[ 'post_type_namespace' ] . $feed_slug;
                
                //Insert the post, return if we encounter an error.
                $result = wp_insert_post( $feed_item, true );
                
                if (is_array($result))
                    $result = $result[0];
                
                //$this->print_debug_info($result, 'save_feed_items');
                
                if ( !is_int($result) )
                    return $result;
                
                //Set the correct post format
                set_post_format($result, !empty( $feed_item[ 'post_format' ] ) ? $feed_item[ 'post_format' ] : 'status' );
                
                
                //Create an array containing the WordPress ID's
                $results[] = $result;
                
                //Check if theres meta items to be added...
                if ( !isset( $feed_item[ 'meta'] ) ) 
                    continue;
                
                if ( !is_array( $feed_item[ 'meta' ] ) )
                    continue;
                
                //Okay, we have some meta items to save..
                foreach ( $feed_item[ 'meta' ] as $key => $value ) {
                    // Return an error if save does not succeed.
                    if ( is_array($value) ) {
                        if ( !add_post_meta( $result, $key, $value[ 'value' ], $value[ 'unique' ] === true ) )
                            return new WP_Error ( 'Meta save error', 'MB GlobalFeed: An error was encountered while attempting to save feed meta item' . ((string) $key) );
                    } else {
                        if ( !add_post_meta( $result, $key, $value, true ) )
                            return new WP_Error ( 'Meta save error', 'MB GlobalFeed: An error was encountered while attempting to save feed meta item' . ((string) $key) );
                    }
                } // End meta items foreach
            } // End save items foreach
            
            $this->print_debug_info("Save feed items finished." , 'register_feeds');
            return $results;
        }
        
        /**
         * Figures out when particular feeds should be shown now instead of at runtime.
         * 
         */
        private function calculate_feeds_on_pages() {
            $settings = (array) $this->settings;
            $this->print_debug_info("Calculate Feed on Pages Started.");
            
            $registered_feeds = $this->registered_feeds;
            $pages = array(
                'home' => array(),
                'archive' => array(),
                'tag' => array(),
                'category' => array(),
            );
            
            foreach ( $registered_feeds as $feed ) {
                extract( $feed );
                // If this feed uses a custom_feed_show don't process it now.
                if ( $feed_options[ 'custom_feed_show' ] == true )
                    continue;
                
                // Calculate where pages should be shown.
                foreach ( $feed_options[ 'pages_to_show' ] as $key => $value ) {
                    if ( in_array( $key, array( 'tag', 'category' ) ) && is_array($value) ) { 
                        foreach ( $value as $tax ) {
                            if ( is_array( $pages[$key][$tax] ) ) 
                                $pages[ $key ][ $tax ][] = $feed_slug;
                            else
                                $pages[ $key ][ $tax ] = array( $feed_slug );
                        }
                    } else {
                        if ( $key == 'all' ) {
                            foreach ( $pages as $page => $page_array ) {
                                if ( !in_array( $page, array( 'tag', 'category' ) ) ) {
                                    if ( is_array( $pages[ $page ] ) )
                                        $pages[ $page ][] = $feed_slug;
                                    else 
                                        $pages[ $page ] = array( $feed_slug );
                                }
                            }
                        } else {
                            if ( is_array( $pages[ $key ] ) )
                                $pages[ $key ][] = $feed_slug;
                            else
                                $pages[ $key ] = array( $feed_slug );
                        }
                    }
                }
                
                // Calculate where pages should not be shown
                if ( !empty($feed_options[ 'pages_not_to_show' ])){
                    foreach ( $feed_options[ 'pages_not_to_show' ] as $key => $value) {
                        if ( in_array( $key, array( 'tag', 'category' ) ) && is_array($value) ) { 
                            foreach ( $value as $tax ) {
                                if ( is_array( $pages[ $key ][ $tax ] ) ) 
                                    $this->remove_item_by_value ( $feed_slug, $pages[ $key ][ $tax ] );
                            }
                        } else {
                            if ( $key == 'all' ) {
                                foreach ( $pages as $page => $page_array ) {
                                    if ( $page != 'tag' && $page != 'category' ) {
                                        if ( is_array( $pages[ $page ] ) )
                                            $this->remove_item_by_value( $feed_slug, $pages[ $page ] );
                                    }
                                }
                            } else {
                                if ( is_array( $pages[ $key ] ) )
                                    $this->remove_item_by_value( $feed_slug, $pages[ $key ] );
                            }
                        }
                    }
                }
            }
            
            $this->print_debug_info("Feeds per page calculated.", 'register_feeds');
            $settings[ 'pages_and_feeds' ] = $pages;
            $this->settings = $settings;
            
            return $this->save_settings();
        }
        
        public function embed_media_item( $post_content, $media_format, $media_info ) {
            // Embed in the post content if required.
            
            if ( $this->media_display_location() == 'before' )
                $post_content = $this->format_media_item($media_format, $media_info) . $post_content;
            else
                $post_content = $post_content . $this->format_media_item($media_format, $media_info);
            
            return $post_content;
        }
        
        /**
         * Formats a media item to be either displayed or embedded.
         * 
         * Can format:
         *      -Images
         *      -Videos (Flash, 3gpp -- html5)
         *      -Links
         *      
         * @todo This could really use some filters...
         * @param str $media_format The media format (video|image|link)
         * @param array $media_info Media information in key => value format. View this functions code to see possible options.
         */
        public function format_media_item( $media_format, $media_info ) {
            $info_defaults = array(
                'source_url'            => '',          // The source url. Not applicable for video. If we are formatting a link, then this can optionally be the source of a picture to accompany it.
                'link'                  => '',          // If set for images, the image will be wrapped in a link tag.
                'link_text'             => '',          // The text that appears for the link. Will appear as the title attribute for images.
                'link_target'           => ($this->settings['autodetect_link_open_new_window'] ? '_blank' : ''),    // The links' target attribute.
                'css'                 => '',          // CSS Classes to add to the element
                'height'                => '',          // The height of the produced element
                'width'                 => '',          // The width of the produced element
                'video_formats_source'  => array(       // The sources for different video types. Good when HTML5 support is enabled.
                    'mp4'   => array( 'url' => '', 'type' => 'video/mp4', 'codec' => 'avc1.43.E01E, mp4a.40.2'),
                    '3gpp'  => array( 'url' => '', 'type' => 'video/3gpp', 'codec' => 'mp4v.20.8, samr'),
                    'ogg'   => array( 'url' => '', 'type' => 'video/pgg', 'codec' => 'theora, vorbis'),
                    'flash' => '',
                ),
                'youtube_video_id'      => '',          // If set, then all information in 'video_formats_source' is disregarded, and the youtube video is loaded using the iframe method.
                'facebook_embed_html'   => '',          // If set, then the this will be returned. Will eventually HTML5 embed the mp4 of the video alongside the Facebook object.
                'attributes'        => array(           // Additional html attributes to be applied to the element.
                    'title' => '',
                    'alt'   => '',
                ),
            );
            $media_info = wp_parse_args($media_info, $info_defaults);
            
            $formatted_obj = '';
            switch ($media_format) {
                case 'video' :
                    if ( !empty($media_info['youtube_video_id']) ) {
                        $formatted_obj = "<iframe class='youtube-player' type='text/html' class='{$media_info['css']}' width='{$media_info['width']}' height='{$media_info['height']}' src='http://www.youtube.com/embed/{$media_info['youtube_video_id']}' frameborder='0'></iframe>";
                    } elseif ( !empty($media_info['facebook_embed_html']) ) {
                        return $media_info['facebook_embed_html'];
                    }
                    break;
                case 'image' :
                    $formatted_obj = "<img src='{$media_info['source_url']}' class='{$media_info['css']}' ";
                    foreach ($media_info['attributes'] as $attr => $value) {
                        $formatted_obj .= "$attr='$value' ";
                    }
                    
                    if ( !empty($media_info['link']) )
                        $formatted_obj = "<a href='{$media_info['link']}' class='{$media_info['css']}' title='{$media_info['link_text']}' target='{$media_info['link_target']}'>$formatted_obj/></a>";
                    else
                        $formatted_obj .= '/>';
                    break;
                case 'link'  :
                    $formatted_obj = "<p><a href='{$media_info['link']}' class='{$media_info['css']}' target='{$media_info['link_target']}'>{$media_info['link_text']}</a></p>";
                    ;
                    break;
            }
            
            return $formatted_obj;
        }
        
        /**
         * This function is designed to find things like urls, twitter usernames,
         * hashtags etc.. in strings, and represent them as clickable links to end users.
         * 
         * The matched text becomes the link text, and we attempt to create a link 
         * to the original content.
         * 
         * The default behaviour of this function is configured in the GlobalFeed
         * admin under 'Presentation'. There the user can choose which objects should
         * be automatically linked to. It is best to respect these settings.
         * 
         * This function can find and replace:
         *      -URLs (url) ('http://example.com')
         *      -Twitter Users (twitter) (@ example => https://twitter.com/example
         *      -Hashtags (hashtag) (#example => https://twitter.com/#!/search/#example
         * 
         * @param str $text The text to search through
         * @param array $types The types of objects to replace. (url|twitter_user|twitter_hashtag) Defaults to what is set in the GlobalFeed configuration for link types to be automatically detected.
         */
        function detect_content_links( $text, $types = null ) {
            if ($types == null)
                $types = array('url','twitter_user','twitter_hashtag');
            
            if ( in_array('url', $types) ) {
                $pattern = '/[^\'"](ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:\.\?+=&%@!\-\/]))?([^ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒÂ¢Ã¢â€šÂ¬Ã¯Â¿Â½Ãƒâ€šÃ‚Â«Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾])[^\'"]/u';
                $text = implode(' ',preg_replace_callback( $pattern, array( &$this, '_replace_link_callback'), explode(' ', $text)));
            }
            
            if ( in_array('twitter_user', $types) ) {
                $pattern = "/@([A-Za-z0-9_]+(\W)??)/"; //'<a href="http://twitter.com/$0" target="_blank">$0</a>'
                
                $text = implode(' ',preg_replace_callback( $pattern, array( &$this, '_replace_twitter_user_callback'), explode(' ', $text)));
            }
            
            if ( in_array('twitter_hashtag', $types) ) {
                $pattern = "/^(\s)??#([A-Za-z0-9_]{2,})$/"; //'<a href="http://twitter.com/$0" target="_blank">$0</a>'
                $text = implode(' ',preg_replace_callback( $pattern, array( &$this, '_replace_twitter_hashtag_callback'), explode(' ', $text)));
            }
            
            return $text;
        }
        
        /**
         * Embeds custom links given either the offset and length of the text to
         * be linked to or the text to be replaced to create the link.
         * 
         * @param str $content The content to have links added
         * @param array $links An array of links to be added, with the link url as the key
         */
        function embed_links( $content, $links ) {
            $c_offset = 0;
            foreach ($links as $url => $args) {
                $args = (array) $args;
                if ( isset($args['offset']) && isset($args['length']) ) {
                    $this->print_debug_info($args);
                    $args['offset'] = (int) $args['offset']; $args['length'] = (int) $args['length'];
                    $link = "<a href='{$url}'" . ($this->settings['autodetect_link_open_new_window'] ? " target='_blank'" : "") . ">" . substr($content, $c_offset + $args['offset'], $args['length']) . "</a>";
                    
                    $content = substr($content, 0, $c_offset + $args['offset']) . $link . substr($content, $c_offset + $args['offset'] + $args['length']);
                    $c_offset += strlen($link) - $args['length'];
                } else if ( isset($args['name']) ) {
                    
                }
            }
            
            return $content;
        }
        
        /** 
         * Used by detect_content_links as the callback for link creation to avoid
         * anonymous functions for PHP < 5.3
         * 
         * @param type $matches 
         */
        private function _replace_link_callback ( $matches ) {
            return '<a href="' .$matches[0]. '"' . ($this->settings['autodetect_link_open_new_window'] ? ' target="_blank">' : '>') . $matches[0] . '</a>';
        }
        
        private function _replace_twitter_user_callback( $matches ) {
            $ins = substr($matches[0],1);
            return "<a href='http://twitter.com/$ins' class='twitter_user'" . ($this->settings['autodetect_link_open_new_window'] ? " target='_blank'" : "") . ">@<span>$ins</span></a>";
        }
        
        private function _replace_twitter_hashtag_callback( $matches ) {
            return "<a href='http://twitter.com/#!/search/" . urlencode($matches[0]) . "' class='twitter_hashtag'" . ($this->settings['autodetect_link_open_new_window'] ? " target='_blank'" : "") . ">#<span>" . substr($matches[0],1) . "</span></a>";
        }
        
        /**
         * Generates a post excerpt using user settings. Feeds can override the 
         * default settings by passing values through the $args variable.
         * 
         * Will also embed media content if specified in $args and permitted by user settings.
         * 
         * @param string $content The full, unprocessed post content
         * @param array $args Feed-specific arguments for post content, overrides globalfeed defaults
         * 
         * @return The formatted excerpt
         */
        function post_content( $content, $args = array() ) {
            $args = wp_parse_args($args, $this->settings['post_content']);
            
            // First check if there is a set word limit
            if ( isset($args['character_count']) && $args['word_count'] !== false ) {
                $words = explode(' ', $content);
                $content = implode(' ', array_slice($words, 0, $args['word_count']));
            }
            
            // Now check if there is a set character limit
            if ( isset($args['character_count']) && $args['character_count'] !== false && count($content) > $args['character_count'] )
                // Get the position of the last space within the character limit and cut the string
                $content = substr($content, 0, strrpos(substr($content, 0, $args['character_count']), ' '));
            
            // Check if we need to embed custom links
            if ( isset($args['links']) && is_array($args['links']) )
                $content = $this->embed_links($content, $args['links']);
            
            // Check if we have to detect content links
            if ( isset($args['detect_content_links']) && $args['detect_content_links'] ) 
                $content = $this->detect_content_links ($content);
            
            // Now check if we're to embed content
            if ( isset($args['include_media']) && $args['include_media'] && $this->media_display_mode() == 'embed' && isset($args['media_format']) && isset($args['media_info']))
                $content = $this->embed_media_item($content, $args['media_format'], $args['media_info']);
            
            return $content;
        }
        
        /**
         * Generates a post excerpt using user settings. Feeds can override the 
         * default settings by passing values through the $args variable.
         * 
         * Will also embed media content if specified in $args and permitted by user settings.
         * 
         * @param string $content The full, unprocessed post content
         * @param array $args Feed-specific arguments for post content, overrides globalfeed defaults
         * 
         * @return The formatted excerpt
         */
        function post_excerpt ( $content, $args ) {
            $args = wp_parse_args($args, $this->settings['post_excerpt']);
            
            // First check if there is a set word limit
            if ( $args['word_count'] !== false ) {
                $words = explode(' ', $content);
                $content = implode(' ', array_slice($words, 0, $args['word_count']));
            }
            
            // Now check if there is a set character limit
            if ( $args['character_count'] !== false && count($content) > $args['character_count'] )
                // Get the position of the last space within the character limit and cut the string
                $content = substr($content, 0, strrpos(substr($content, 0, $args['character_count']), ' '));
            
            // Check if we have to detect content links
            if ( $args['detect_content_links'] ) 
                $content = $this->detect_content_links ($content);
            
            // Now check if we're to embed content
            if ( $args['include_media'] && $this->media_display_mode() == 'embed' && isset($args['media_format']) && isset($args['media_info']))
                $content = $this->embed_media_item($content, $args['media_format'], $args['media_info']);
            
            return $content;
        }

        /**
         * This function takes a date, and formats it relative to the current date.
         * 
         * @param timestamp $ts the time to format.
         * @return string 
         */
        function formatRelativeDate($ts) {
            if(!ctype_digit($ts))
                $ts = strtotime($ts);

            $diff = time() - $ts;
            if($diff == 0)
                return __('now', 'mb_globalfeed');
            elseif($diff > 0) {
                $day_diff = floor($diff / 86400);
                if($day_diff == 0)
                {
                    if($diff < 60) return __('just now', 'mb_globalfeed');
                    if($diff < 120) return __('1 minute ago', 'mb_globalfeed');
                    if($diff < 3600) return sprintf(__('%d minutes ago', 'mb_globalfeed'), floor($diff / 60));
                    if($diff < 7200) return __('1 hour ago', 'mb_globalfeed');
                    if($diff < 86400) return sprintf(__('%d hours ago', 'mb_globalfeed'), floor($diff / 3600));
                }
                if($day_diff == 1) return __('Yesterday', 'mb_globalfeed');
                if($day_diff < 7) return sprintf(__('%d days ago', 'mb_globalfeed'), $day_diff);
                if($day_diff == 7) return __('last week', 'mb_globalfeed');
                if($day_diff < 31) return sprintf(__('%d weeks ago', 'mb_globalfeed'), ceil($day_diff / 7));
                if($day_diff < 60) return __('last month', 'mb_globalfeed');
                return date('F Y', $ts);
            } else {
                $diff = abs($diff);
                $day_diff = floor($diff / 86400);
                if($day_diff == 0)
                {
                    if($diff < 120) return __('in a minute', 'mb_globalfeed');
                    if($diff < 3600) return sprintf(__('in %d minutes', floor($diff / 60)));
                    if($diff < 7200) return __('in an hour', 'mb_globalfeed');
                    if($diff < 86400) return sprintf(__('in %d hours', floor($diff / 3600)));
                }
                if($day_diff == 1) return __('Tomorrow', 'mb_globalfeed');
                if($day_diff < 4) return date('l', $ts);
                if($day_diff < 7 + (7 - date('w'))) return __('next week', 'mb_globalfeed');
                if(ceil($day_diff / 7) < 4) return sprintf(__('in %d weeks',ceil($day_diff / 7)));
                if(date('n', $ts) == date('n') + 1) return __('next month', 'mb_globalfeed');
                return date('F Y', $ts);
            }
        }
        
        /* UTILITY FUNCTIONS ---------------------------------------------------*/
        /**
         * Removes an item from an array based on its value
         * 
         * @param array $needle The value to find in the array
         * @param string $haystack The array to search for the value
         * @param boolean $preserve_keys Whether array keys should be preserved.
         * @return array
         */
        private function remove_item_by_value($needle, $haystack = '', $preserve_keys = true) {
                if (empty($haystack) || !is_array($haystack)) 
                    return false;
                if (!in_array($needle, $haystack)) 
                        return $haystack;

                foreach($haystack as $key => $value) {
                        if ($value == $val) unset($haystack[$key]);
                }

                return ($preserve_keys === true) ? $haystack : array_values($haystack);
        }
       
    } // end mb_globalfeed class
}

// Make sure the GlobalFeed Base Directory is available to all plugins
define( 'GLOBALFEED_BASE_DIR', dirname(__FILE__) );

// Load expected dependencies
require_once('class-mb_globalfeed_feed.php');

// Instantiates the plugin...
$mb_globalfeed = mb_globalfeed::init();

if ( !function_exists('globalfeed_get_template_part') ) {
    function globalfeed_get_template_part( $slug, $name ) {
        global $post;
        
        $settings = $mb_globalfeed->load_settings();
        
        if ( strstr($post->post_type, $settings['post_type_namespace'] ) )
            get_template_part ($slug, $settings['post_type_namespace'] . $name );
        else
            get_template_part ($slug, $name);
    }
} // -- END globalfeed_get_template_part

if ($mb_globalfeed->first_run) {

}
?>
