<?php

/*
 * This template is responsible for displaying the administration page for the
 * GlobalFeed plugin. This page is called from ../mb_globalfeed.php
 * 
 * This page uses the WordPress get_file_data() function to list the feeds that
 * are available to GlobalFeed.
 */

class mb_globalfeed_admin {
    private $current_page = false;
    private $_current_page_type = 'default';
    private $_current_feed_file = '';
    private $_mbgf_admin_pages = array(
        'admin_presentation', 
        'admin_settings',
        'main'
    );
    private $_globalfeed_settings = array();
    private $_current_feed_slug = '';
    
    /**
     * Returns the current admin page that is being displayed.
     * 
     * @return string|array Returns a string if standard admin page, or an array if a feeds admin page is being shown.
     */
    public function current_admin_page(){
        return $this->current_page;
    }
    
    /**
     * Returns the current page type being processed.
     * 
     * @return string The type of the current admin page. 
     */
    public function current_page_type() {
        return $this->_current_page_type;
    }
    
    /**
     * Returns the feed slug if on a feed admin page, or false if not.
     * 
     * @return mixed
     */
    public function current_feed_slug() {
        return !empty($this->_current_feed_slug) ? $this->_current_feed_slug : false;
    }
    
    public function mbgf_admin_pages() {
        return $this->_mbgf_admin_pages;
    }
    
    /**
     * Become self aware... 
     * 
     * @todo Do something to ensure WordPress is loaded properly.
     */
    function __construct( &$globalfeed_settings ) {
        global $mb_globalfeed;
        // Okay, the page has been loaded. Check that WordPress has been loaded
        // @todo Do something here to check that Wordpress is loaded.
        
        $this->_globalfeed_settings = &$globalfeed_settings;
        
        // Check that globalfeed is loaded.
        if ( !isset( $mb_globalfeed ) )
            return false;

        // Many features require that session be instated
        if ( !headers_sent() )
        session_start();
        
        // Check if we have a feed that needs deactivating, and deactivate it.
        if ( isset( $_POST['deactivate_feed']) ) {
            if ( !isset( $_POST['feed_slug'] ) )
                break;
            
            // Check that intention
            if (!check_admin_referer( 'globalfeed_admin' ) || !wp_verify_nonce( $_POST['_wpnonce'], 'globalfeed_admin' ))
                die('Security Check Failed.');
            
            // Deactivate the feed
            $mb_globalfeed->unregister_feed( (string) $_POST['feed_slug'] );
        }
        
        // Register feeds
        $this->register_actions_filters();
    }
    
    /** 
     * Registers the actions and filters required by the mbgf admin section
     */
    private function register_actions_filters() {
        add_filter( "mbgf_default_menu", array( &$this, 'add_admin_pages' ) );
    }
    
    /**
     * Adds the admin tabs to our pages. Called by WordPress filter.
     * 
     * @param array $pages The pages for the current page.
     */
    function add_admin_pages( $pages ) {
        $pages['main'] = __('Main', 'mb_globalfeed');
        $pages['admin_settings'] = __('Settings', 'mb_globalfeed');
        $pages['admin_presentation'] = __('Presentation', 'mb_globalfeed');
        
        return $pages;
    }
    
    /**
     * Checks which admin page should be shown, and loads the appropriate one from 
     * the pages/ dir.
     * 
     * Additionally will activate a feed if it is marked to be activated.
     * 
     */
    function show_current_admin_page() {
        global $mb_globalfeed;
        $current_page = 'main';
        if ( isset( $_GET['mbgf_admin_page'] ) )
            $current_page = $_GET['mbgf_admin_page'];
        elseif ( isset( $_POST['mbgf_admin_page'] ) )
            $current_page = $_POST['mbgf_admin_page'];
        
        if ( !isset($_REQUEST['mbgf_page_type']) )
            $_REQUEST['mbgf_page_type'] = '';
        
        switch ( $_REQUEST['mbgf_page_type'] ){
            case 'feed':
                $this->_current_page_type = 'feed';
                $this->_current_feed_slug = (string) $_REQUEST['mbgf_feed_slug'];
                $current_page = '';
                break;
            default:
                $this->_current_page_type = 'default';
                $current_page = isset($_REQUEST['mbgf_admin_page']) ? $_REQUEST['mbgf_admin_page'] : 'main' ; 
                break;
        }
        
               
        $this->current_page = $current_page;
        
        // Include standard JS and CSS
        $this->include_client_reqs();
        
        // If were displaying check if its been activated...
        if ( $this->_current_page_type == 'feed' && !$mb_globalfeed->feed_activated($this->_current_feed_slug) && isset($_POST['activate_feed-' . $this->_current_feed_slug])) {
            // A feed may have just activated. Check nonce 
            // and if it validates call the action that activates the feed
            if (check_admin_referer( 'globalfeed_admin' ) && wp_verify_nonce( $_POST['_wpnonce'], 'globalfeed_admin' )){
                //The feed was not already registered, so manually include the file
                $feeds = $this->get_feed_list();

                foreach ($feeds as $feed_file => $feed) {
                    if ($feed['Slug'] != $this->_current_feed_slug)
                        continue;

                    require_once dirname(__FILE__).'/../feeds/'.$feed_file;
                    break;
                }
                
                do_action('mbgf_activate_feed-' . $mb_globalfeed->get_shortened_feed_slug($this->_current_feed_slug));
            }
        }
        
        // Call the wrapper page that outputs the GlobalFeed container
        require_once dirname(__FILE__) . '/pages/wrapper.php';
    }
    
    /**
     * Includes the standard JS and CSS libraries utilized by GlobalFeed and feeds
     * for client-side presentation.
     *  
     */
    function include_client_reqs() {
        global $mb_globalfeed;
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'qtip', plugins_url( 'admin/pages/js/jquery.qtip.min.js', $mb_globalfeed->plugin_location() ), array('jquery'));
        wp_enqueue_script( 'jquery_simplemodal', plugins_url( 'admin/pages/js/jquery.simplemodal.js', $mb_globalfeed->plugin_location() ), array('jquery'));
        wp_enqueue_script( 'bug_reports', plugins_url( 'admin/pages/js/jquery.simplemodal.js', $mb_globalfeed->plugin_location() ), array('jquery','jquery_simplemodal'));
        
        wp_enqueue_style( 'mb_globalfeed', plugins_url( 'admin/pages/style.css', $mb_globalfeed->plugin_location() ));
        wp_enqueue_style( 'qtip', plugins_url( 'admin/pages/js/jquery.qtip.css', $mb_globalfeed->plugin_location() ));
        

    }
    
    /**
     * Displays the content for the content pane. Called by the GlobalFeed Admin wrapper.
     * 
     * Should not be called directly. Call show_current_admin_page if the mb_globalfeed_admin
     * class was not initialized with the 'display' argument set.
     * 
     * @global mb_globalfeed $mb_globalfeed 
     */
    function show_admin_page_content() {
        global $mb_globalfeed;
        $current_page_type = &$this->_current_page_type;
        $mb_globalfeed->print_debug_info($current_page_type);
        //The main administrative page in case no or invalid values are passed
        if ( empty( $current_page_type ) ) 
            $current_page_type = 'default';
        switch ( $current_page_type ) {
            case 'feed':
                
                $feed_slug = &$this->_current_feed_slug;
                    // Tell GlobalFeed to load a certain feeds admin page, if it fails, show the feed activation page
                    if ( $mb_globalfeed->show_feed_admin( $feed_slug ) === false ) {
                        if (isset($_POST['activate_feed-' . $feed_slug])){
                            // The feed should now be activated, try loading it again.
                            if ( $mb_globalfeed->show_feed_admin( $feed_slug ) === false )
                                return $mb_globalfeed->print_debug_info( new WP_Error ('Error loading feed', 'An unknown error was encountered while loading the feed: ' . $feed_slug) );
                        } else 
                            require_once dirname(__FILE__) . '/pages/activate_feed.php';
                    }
                break;
            default:
                // Looks like an mbgf admin page, but WHICH ONE?!?
                    if (in_array($this->current_page, $this->_mbgf_admin_pages))
                        require_once dirname(__FILE__) . "/pages/{$this->current_page}.php";
                    else
                        require_once dirname(__FILE__) . '/pages/main.php';
                    show_mbgf_admin_page( $this->_globalfeed_settings );
                    $mb_globalfeed->save_settings();
                break;
        }
    }
    
    /**
     * Scans the feed directory for feeds, and retrieves their information.
     * 
     * @todo The list of feeds should be cached. (See wp-admin/includes/plugin.php)
     */
    function get_feed_list() {
        global $mb_globalfeed;
        // Files in feeds directory. Next 25 lines taken from wp-admin/includes/plugin.php of WordPress core.
        $feed_root = GLOBALFEED_BASE_DIR . '/feeds/';
	$feeds_dir = @ opendir( $feed_root );
	$feed_files = array();
	if ( $feeds_dir ) {
		while (($file = readdir( $feeds_dir ) ) !== false ) {
			if ( substr($file, 0, 1) == '.' )
				continue;
			if ( is_dir( $feed_root.'/'.$file ) ) {
				$feeds_subdir = @ opendir( $feed_root.'/'.$file );
				if ( $feeds_subdir ) {
					while (($subfile = readdir( $feeds_subdir ) ) !== false ) {
						if ( substr($subfile, 0, 1) == '.' )
							continue;
						if ( substr($subfile, -4) == '.php' )
							$feed_files[] = "$file/$subfile";
					}
					closedir( $feeds_subdir );
				}
			} else {
				if ( substr($file, -4) == '.php' )
					$feed_files[] = $file;
			}
		}
		closedir( $feeds_dir );
	}
        
        // Lines 53-71 adapted from wp-admin/includes/plugin.php of WordPress core.
        $feeds = array();
        if ( empty($feed_files) )
		return $feeds;

	foreach ( $feed_files as $feed_file ) {
		if ( !is_readable( "$feed_root/$feed_file" ) )
			continue;

		$feed_data = $this->get_feed_data( "$feed_root/$feed_file" );

                $feed_data['Slug'] = $mb_globalfeed->get_shortened_feed_slug( $feed_data['Slug'] );

                if ( empty ( $feed_data['Name'] ) )
			continue;

		$feeds[plugin_basename( $feed_file )] = $feed_data;
	}

	uasort( $feeds, '_sort_uname_callback' );
        
	return $feeds;
    }
    
    /**
     * Retrieves the headers from the given feed file. Looks for the following headers:
     * 
     * Required:
     *      Name: The name of the feed
     *      Slug: The feeds slug
     *      Version: The version number for the feed
     *      Author: The name of the feed's author
     *      Description: The feed description
     * 
     * Optional:
     *      Feed URI: The URI to the feed webpage
     *      Author URI: The URI of the feed authors webpage
     *      GlobalFeed Version: The minimum GlobalFeed version that this feed works with
     *      WordPress Version: The minimum WordPress version that this feed works with
     * 
     * Note about required versions: The version number given is taken to be a minimum,
     *      Therefore it is assumed that the feed supports every version greater than
     *      the given version number. There is no way to select specific WordPress versions.
     * 
     *      In addition, versions are checked when the GlobalFeed admin is opened.
     *      if for some reason the versions are no longer compatible, the feed will 
     *      be automatically disabled.
     * 
     *
     * @param string $feed_file The feed file to retrieve headers for
     */
    function get_feed_data( $feed_file ){
        
        // The feed headers to look for in the files we scan
        $default_headers = array(
            'Name' => 'Feed Name',
            'Slug' => 'Feed Slug',
            'FeedURI' => 'Feed URI',
            'Version' => 'Version',
            'Author' => 'Author',
            'AuthorURI' => 'Author URI',
            'Description' => 'Description',
            'GlobalFeedVersion' => 'GlobalFeed Version',
            'WordPressVersion' => 'WordPress Version'
        );
        
        $feed_data = get_file_data( $feed_file, $default_headers, 'globalfeed_feed' );
        
        //$feed_data = _get_plugin_data_markup_translate( $feed_file, $feed_data, true, true );
        
        return $feed_data;
    }
    
    /**
     * This function enques the styles for the admin section 
     */
    static function admin_styles() {
        // First register the style
        wp_register_style( 'mb_globalfeed_admin', plugins_url('pages/style.css', __FILE__) );
        // Now load it
        wp_enqueue_style('mb_globalfeed_admin');
    }
}
?>