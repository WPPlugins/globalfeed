<?php
/*
  Feed Name: Facebook Connect
  Feed Slug: facebook_connect
  Feed URI: http://MichaelBlouin.ca
  Version: v0.10
  Author: Michael Blouin
  Author URI: http://www.michaelblouin.ca/
  GlobalFeed Version: 0.1
  WordPress Version: 3.4.1
  Description: This feed configures Facebook to automatically inform your site of any relevant updates.

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

/**
 * The container class for GlobalFeed Facebook Connect.
 * 
 * @package MB Facebook Connect 
 */
class mb_globalfeed_facebook_connect extends mb_globalfeed_feed {
    /**
     * The name of the current feed.
     * 
     * @var str
     */
    private $feed_name = 'Facebook Connect';
    
    /**
     * A url friendly feed slug. (Lacking spaces and special characters).
     * 
     * @var str
     */
    private $feed_slug = 'facebook_connect';
    
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
    
    private $admin_pages = array();


    /**
     * The instance of Global Feed this plugin was created using.
     * @var mb_globalfeed
     */
    private $globalfeed;
    
    
    private $oathurl = 'https://www.facebook.com/dialog/oauth';
    private $graphurl = 'https://graph.facebook.com/';
    
    public function graphurl() {
        return $this->graphurl;
    }
    
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
        'user_info' => null,
        'app_id' => '',
        'app_secret' => '',
        'feed_queryvar' => 'mbgf_facebook_connect',
        'fb_subscription_status' => 'not_initiated',                            // can be not_initiated, waiting_for_callback, setup
        'fb_auth_status' => 'not_initiated',                                    // can be 'not_initiated', 'authorizing', 'authorized'. 
        'object_to_subscribe' => '',
        'subscribe_object_type' => '',
        'subscribe_items' => 'feed',
        'last_feed_update' => 0,
        'max_feed_items' => 0,
        'initial_setup_done' => false,
        'show_welcome_message' => true,
    );
    
    /** 
     * This array contains any settings that should be saved for this feed.
     * 
     * Settings in this array are automatically saved when you use register_feed()
     * 
     * @var array $feed_options
     */
    private $feed_options = null;
    
    public function get_queryvar() {
        return $this->feed_options['feed_queryvar'];
    }
    /**
     * This function requires a reference to MB_GlobalFeed in order to start. This is because the feed is loaded before
     * $this->globalfeed is available in the global scope.
     * 
     * @todo Remove the setup_fb var. Setup should be called from admin.
     * 
     * @param mb_globalfeed $globalfeed 
     */
    function __construct( $globalfeed ){
        $this->globalfeed = $globalfeed;
        
        $globalfeed->print_debug_info('MB Facebook Plugin Loaded.', 'mb_facebook');
        
        // Load the saved settings from Global Feed. (First detecting if the settings have already been saved.
        $feed_options = $globalfeed->get_feed_options( $this->feed_slug );
        $this->feed_slug = !empty($feed_options['feed_slug']) ? $feed_options['feed_slug'] : $this->feed_slug;
        $feed_options = $this->feed_options = $feed_options ? $feed_options['feed_options'] : $this->feed_defaults;
        
        // @todo implement the following pages:
        $this->admin_pages = array(
            //'settings-main' => __('Main', $this->get_slug()),
            //'settings-presentation' => __('Presentation', $this->get_slug()),
            //'settings-update' => __('Updates', $this->get_slug()),
        );
        
        // Setup feed filters
        $this->setup_filters();

        // Check for our query vars that tell the feed to take control. (IE communications received from Facebook.)
//        if ( isset( $_GET['reset_since'] ) )
//            $this->feed_options['last_feed_update'] = 0;
        
//        if ( isset( $_GET[ 'setup_fb' ] ) && $feed_options != false)
//                $this->get_authorization();
        
        // If were in the admin, we need to be using sessions
        if ( is_admin() ) {
            if (!isset($_SESSION['state']))
                $_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection for admin.
        }
        
        if ( isset( $_GET['force_update'] )  && $feed_options['fb_auth_status'] == 'authorized')
            $this->update_feed (array());

        if ( isset( $_GET[ 'code' ] ) && isset( $_GET[ 'state' ] ) && $feed_options != false)
            $this->handle_feed_call();
        
        if ( isset( $_GET[ 'hub_verify_token' ] ) && (string) $_GET["hub_verify_token"] == (string) $feed_options['fb_verify_token'] && $feed_options['fb_subscription_status'] == 'authorizing' )
            $this->setup_fb_subscription();
        
        if ( isset( $_POST[ $feed_options['feed_queryvar']]) && isset($_POST['process_updates']))
            $this->process_updates();
        
        if ( isset( $_GET[ $feed_options['feed_queryvar'] ] ) || isset( $_POST[ $feed_options['feed_queryvar'] ] ) )
            $this->handle_subscription_call();
        
        // If we're set to automatically interupt application flow, then exit so WordPress doesn't do its thing.
//        if ( (isset( $_GET['feed_queryvar'])  || isset( $_POST['feed_queryvar'])) && $this->auto_interupt_flow && false)
//            exit;
    }
    
    /**
     * This function is called when WordPress receives a request to the callback url. 
     * 
     * This is usually because data is being transmitted from Facebook.
     */
    private function handle_feed_call() {
        $this->globalfeed->print_debug_info('MB_Facebook received a request at the callback URL and interrupted application flow. '.get_bloginfo( 'wpurl' ).'?'.$this->feed_options['feed_queryvar'], 'mb_facebook');
        if ( $this->feed_options['fb_auth_status'] != 'authorized')
            $this->get_authorization();
    }
    
    function setup_filters() {
        add_filter( 'query_vars', array( &$this, 'add_listener_vars' ));
        add_filter( 'get_avatar', array( &$this, 'get_avatar'));
        add_filter( 'author_link', array( &$this, 'author_link' ));
        add_filter( 'the_permalink', array( &$this, 'the_permalink' ),10);
        add_filter( 'post_type_link', array( &$this, 'the_permalink' ),10);
        add_filter( 'post_link', array( &$this, 'the_permalink' ),1,10);
        add_filter( 'the_author', array( &$this, 'the_author' ));
        add_action( 'mbgf-fb-theme_show_user_actions', array( &$this, 'show_user_actions' ) );
        
        // These action are related to the setup while were in admin...
        if (is_admin()) {
            add_action( 'wp_ajax_mbgf_facebook_connect_set_app_info', array( &$this, 'set_app_info' ));
            add_action( 'wp_ajax_mbgf_facebook_connect_is_authed', array( &$this, 'ajax_app_is_authed' ));
            add_action( 'wp_ajax_mbgf_facebook_connect_set_fb_obj_id', array( &$this, 'ajax_set_fb_obj_id' ));
            add_action( 'wp_ajax_mbgf_facebook_connect_renew_access_codes', array( &$this, 'renew_access_codes' ));
            add_action( 'wp_ajax_mbgf_facebook_connect_redo_initial_setup', array( &$this, 'redo_initial_setup' ));
            add_action( 'wp_ajax_mbgf_facebook_connect_reset_feed_defaults', array( &$this, 'reset_feed_defaults' ));
            add_action( 'wp_ajax_mbgf_facebook_connect_manual_feed_update', array( &$this, 'ajax_do_update' ));
            
            
            add_action( 'mbgf_feed_menu-' . $this->get_slug(), array(&$this, 'register_admin_menus'));
            add_action( 'mbgf_unregister_feed-' . $this->get_slug(), array( &$this, 'unregister_feed') );
        }
    }
    
    function show_user_actions() {
        if (get_post_type() != ($this->globalfeed->post_type_namespace() . $this->feed_slug)) 
            return;
        
        $actions = get_post_meta(get_the_ID(), 'actions', true);
        if ( empty($actions) )
            return;
        
        $echo = array();
        foreach ($actions as $action) {
            switch ( $action->name ) {
                case 'Comment' :
                    $echo[] = "<a href='{$action->link}' class='comment link' target='_blank'>" . __('Comment', $this->get_slug()) . "</a>";
                    break;
                case 'Like' :
                    array_unshift($echo,  "<a href='{$action->link}' class='like link' target='_blank'>" . __('Like', $this->get_slug()) . "</a>");
                    break;
                case 'View' :
                    array_unshift($echo, "<a href='https://www.facebook.com{$action->link}' class='view link' target='_blank'>" . __('View', $this->get_slug()) . "</a>");
                    break;
                default:
                    $echo[] = "<a href='{$action->link}' class='action link' target='_blank'>" . __($action->name, $this->get_slug()) . "</a>";
        }   }
        
        if ( count($echo) )
            echo implode('<span class="sep"></span>', $echo) . '<span class="sep"></span>';
    }
    
    /**
     * Registers the page tabs at the top of the GlobalFeed page.
     * 
     * @param array $pages The currently registered pages.
     * @return array
     */
    function register_admin_menus( $pages ) {
        $pages = $this->admin_pages;
        return $pages;
    }

    /**
     * Renews the feeds access codes with Facebook. 
     */
    public function renew_access_codes(){
        check_admin_referer( 'facebook-connect-settings_main' );
        wp_verify_nonce( 'facebook-connect-settings_main' );
        
        $this->feed_options['fb_auth_status'] = 'not_initiated';
        
        $this->feed_options['oauth_user_token'] = '';
        $this->feed_options['ouath_user_expires'] = '';
        $this->feed_options['oauth_app_token'] = '';
        $this->feed_options['ouath_app_expires'] = '';
        
        $this->register_feed(true);
        
        die(json_encode(array(
            'redirect_url' => get_bloginfo('wpurl').'/',
            'activation_url' => $this->get_client_auth_redirect_url() . "&client_id={$this->feed_options['app_id']}",
        )));
    }
    
    /**
     * Removes all saved feed items, then marks the initial setup to be done.
     * 
     * (Called via ajax) 
     */
    public function redo_initial_setup(){
        check_admin_referer( 'facebook-connect-settings_main' );
        wp_verify_nonce( 'facebook-connect-settings_main' );
        
        $this->feed_options['initial_setup_done'] = false;
        $this->remove_feed_items();
        $this->register_feed(true);
        
        die(json_encode(true));
    }
    
    /** 
     * This function is called VIA Ajax to reset the feed to its defaults. 
     */
    public function reset_feed_defaults() {
        check_admin_referer( 'facebook-connect-settings_main' );
        wp_verify_nonce( 'facebook-connect-settings_main' );
        
        $this->feed_options = $this->feed_defaults;
        $this->pages_to_show = array('all');
        $this->pages_not_to_show = array();
        $this->remove_feed_items();
        
        $this->register_feed(true);
        
        die(json_encode(true));
    }

    /**
     * When the user attempts to authorize the facebook app, this function is called to
     * set an app_id and app_secret.
     * 
     */
    public function set_app_info() {
        check_admin_referer( 'facebook-connect-settings_main' );
        wp_verify_nonce( 'facebook-connect-settings_main' );
        
        $app_id = $_POST['app_id'];
        $app_secret = strtolower( $_POST['app_secret'] );
        
        // Validate the values we've received
        if (preg_match('/^[a-z0-9]+$/', $app_secret) == 0 || preg_match('/^[0-9]+$/', $app_id) == 0 )
            die('Invalid');
        
        $this->feed_options['fb_auth_status'] = 'not_initiated';
        $this->feed_options['app_id'] = $app_id;
        $this->feed_options['app_secret'] = $app_secret;
        
        $this->feed_options['oauth_user_token'] = '';
        $this->feed_options['ouath_user_expires'] = '';
        $this->feed_options['oauth_app_token'] = '';
        $this->feed_options['ouath_app_expires'] = '';
        
        $this->register_feed(true);
        
        echo json_encode(array('app_id'=>$this->feed_options['app_id'], 'app_secret' => $this->feed_options['app_secret']));
        die();
    }
    
    /**
     * When the user attempts to authorize the facebook app, this function is called to
     * see if the feed now has an app_id and app_secret
     * 
     */
    public function ajax_app_is_authed() {
        check_admin_referer( 'facebook-connect-settings_main' );
        wp_verify_nonce( 'facebook-connect-settings_main' );
        
        if (!empty($this->feed_options['oauth_user_token']) || (isset($_SERVER['mbgf_app_is_authed']) && $_SERVER['mbgf_app_is_authed'])  || get_transient('mbgf_app_is_authed')) {
            unset( $_SERVER['mbgf_app_is_authed'] );
            die( json_encode(true) );
        } else {
            $c = 1;
            while ( $c <= 2 ) {
                sleep(2);
                if ( (isset($_SERVER['mbgf_app_is_authed']) && $_SERVER['mbgf_app_is_authed']) || get_transient('mbgf_app_is_authed') ) {
                    unset( $_SERVER['mbgf_app_is_authed'] );
                    die(json_encode(true));
                }
                    
                $c++;
            }
            
            die( json_encode(false) );
        }
    }

    /**
     * When the user attempts to authorize the facebook app, this function is called to
     * see if the feed now has an app_id and app_secret
     * 
     */
    public function ajax_set_fb_obj_id() {
        check_admin_referer( 'facebook-connect-settings_main' );
        wp_verify_nonce( 'facebook-connect-settings_main' );
        
        $obj_id = $_POST['fb_object_id'] ;
        $obj_type = $_POST['fb_object_type'] ;
        
        // Validate the values we've received
        if (preg_match('/^[0-9]+$/', $obj_id) == 0 || preg_match('/^[a-zA-Z]+$/', $obj_type) == 0){
            die(json_encode('Input arguments are not of the right format'));
        }
        
        // If we're receveing a new ID, kill the last update time.
        $this->feed_options['last_feed_update'] = 0;
        
        // The object ID appears valid. Save.
        $this->feed_options['object_to_subscribe'] =  $obj_id;
        $this->feed_options['subscribe_object_type'] = $obj_type;
        
        // If this var is set, then mark the initial setup complete.
        if (isset($_POST['mb_facebook_finish_setup']) && ((bool) $_POST['mb_facebook_finish_setup']) == true ) {
            $this->feed_options['initial_setup_done'] = true;
            $this->feed_options['show_welcome_message'] = true;
        }
        
        // Save changes
        $this->register_feed(true);
        
        $result = $this->update_feed(null);
        
        if ( $result === false ) {
            $this->feed_options['object_to_subscribe'] = $this->feed_defaults['object_to_subscribe'];
            $this->feed_options['subscribe_object_type'] = $this->feed_defaults['subscribe_object_type'];
            $this->feed_options['initial_setup_done'] = false;
            $this->feed_options['show_welcome_message'] = false;
            
            die(json_encode('Error: A network error occured while requesting data from facebook.'));
        }
        
        // Update and return the feed items retrieved.
        die(json_encode(
                array( 'num_feed_items_retrieved' => $result )
        ));
    }
    
    /**
     * Add our query var to the list of query vars
     */
    public function add_listener_vars($public_query_vars) {
        //$public_query_vars[] = $this->facebook_secret;
        return $public_query_vars;
    }
    
    /**
     * This function will switch the avatar displayed by posts to the user facebook profile picture.
     */
    public function get_avatar($avatar) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->feed_slug)) {
            $author = get_post_meta(get_the_ID(), 'author', true);
            $avatar = "<img alt='$author->name' src='https://graph.facebook.com/{$author->id}/picture' class='avatar photo avatar-default'/>";
        }

        return $avatar;
    }
    
    /**
     * This function will switch the avatar displayed by posts to the user facebook profile picture.
     */
    public function the_permalink($permalink) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->get_slug())) {
            $actions = get_post_meta(get_the_ID(), 'actions', true);
            // This is a regular item
            if (is_array($actions) && count($actions) > 1){
                foreach ($actions as $value) {
                    if ((string) $value->name == 'Comment') {
                        $permalink = (string) $value->link;
                        break;
                    }
                }
            } else { //This is a story
                $author = get_post_meta(get_the_ID(), 'author', true);
                $post_id = get_post_meta(get_the_ID(), 'facebook_id', true);
                $permalink = "https://www.facebook.com/{$author->id}/posts/{$post_id}";
            }
        }
        return $permalink;
    }
    
    /**
     * This function will switch the avatar displayed by posts to the user facebook profile picture.
     */
    public function author_link($link) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->get_slug())) {
            $author = get_post_meta(get_the_ID(), 'author', true);
            $link = "https://www.facebook.com/{$author->id}";
        }

        return $link;
    }
    
     /**
     * This function will switch the avatar displayed by posts to the user facebook profile picture.
     */
    public function the_author($author_info) {
        if (get_post_type() == ($this->globalfeed->post_type_namespace() . $this->get_slug())) {
            $author = get_post_meta(get_the_ID(), 'author_name', true);
            $author_info = $author . ($this->globalfeed->show_byline() ? __(' via Facebook', 'mb_facebook_connect') : '');
        }

        return $author_info;
    }
    
    
    
    function activate_feed(){
        // @TODO:  Make feed check compatibility with the current WordPress environment and other requirements.
        
        // The activation state is the state used to secure facebook authentication
        
        // Register this feed with Global Feed.
        if ( !$this->register_feed() )
            return new WP_Error ('MB_Facebook_Feed_Activation_Failed', 'Feed registration failed for an unknown reason.');
        
        // Get Facebook Authorization.
        //if ( !$this->get_authorization())
        //    return new WP_Error ('MB_Facebook_Feed_Authorization_Failed', 'Feed authentication with Facebook failed.');
            
    }
    
    /**
     * This function registers the feed options and information with Global Feed.
     * 
     * If called with the update option it can also be used to save feed settings.
     * 
     * @todo Allow users to customize the request frequency away from the mbgf default
     * @param bool $update If this feed is already registered, should the values here overwrite the existing ones?
     */
    function register_feed( $update = false ) {
        $this->globalfeed->print_debug_info('MB_Facebook Register Feed Started.');
        
        $info = array(
            'feed_name'  => $this->feed_name,
            'feed_slug'  => $this->feed_slug,
            'feed_type'  => $this->feed_type,
            'file_path'  => __FILE__, 
            'class_name' => 'mb_globalfeed_facebook_connect',
            'update'     => $update,
            'request_frequency' => 'mbgf_default',
        );
        
        if ( !isset($this->feed_options['activate_state'] ))
            $this->feed_options['activate_state'] = md5(uniqid(rand(), TRUE));
        
        $feed_options = $this->feed_options;
        $feed_options[ 'pages_to_show' ] = $this->pages_to_show;
        $feed_options[ 'pages_not_to_show' ] = $this->pages_not_to_show;
        
        return $this->globalfeed->register_feed($info, $feed_options);
    }
    
    /**
     * This function is called while the subscription is being setup.
     * 
     * @todo Handle errors with subscription a bit better.
     * @todo Check with facebook to make sure the subscription is properly setup
     */
    function setup_fb_subscription(){
        $feed_options = &$this->feed_options;
        $globalfeed = &$this->globalfeed;
        $method = $_SERVER['REQUEST_METHOD'];
        $my_url = 'http://mbgfdev.michaelblouin.ca/?'.$feed_options['feed_queryvar'];
        
        $globalfeed->print_debug_info("Setup FB subscription called. Method: $method");
        
        if ( $method == 'GET' && isset($_GET["hub_verify_token"]) && $_GET["hub_verify_token"] == $feed_options['fb_verify_token'] && $_GET['hub_mode'] == 'subscribe' && $feed_options['fb_subscription_status'] = 'waiting_for_callback' ) {
            $globalfeed->print_debug_info("Facebook subscription verify request received. Hub verify token: {$_GET['hub_verify_token']}. Echoing challenge: {$feed_options['hub_challenge']}");
            
            die($_GET['hub_challenge']);
            exit;
            
        } else {
            // Authorization hasn't already started... So start it...
            $globalfeed->print_debug_info('Starting fb subscription setup.');
            
            //$graph_url = "https://graph.facebook.com/{$feed_options['app_id']}/subscriptions?auth_token=" . $feed_options['oauth_token']
                //. '&verify_token=' . $verify_token . '&callback_url=' . urlencode($my_url) . '&object=user&fields=' . 'feed,picture,email';
            $feed_options['subscribe_object_type'] = 'user';
            $feed_options['subscribe_items'] = 'feed';
            $fb_params = array(
                    'access_token' => $feed_options['oauth_app_token'], // App token required for setting up subscriptions
                    'verify_token' => $feed_options['fb_verify_token'], 
                    'callback_url' => $my_url,
                    'object'       => $feed_options['subscribe_object_type'],
                    'fields'       => $feed_options['subscribe_items'],
                );
            $post_params = array( 
                'method' => 'POST',
                'body'   => $fb_params
            );
            
            $graph_url = "https://graph.facebook.com/{$feed_options['app_id']}/subscriptions/?access_token={$this->feed_options['oauth_app_token']}";
            
            $globalfeed->print_debug_info("Sending a request for the subscription setup: $graph_url", 'mb_facebook');
            
            // Send the result:
            $fb_result = $this->_oauthRequest($graph_url, $fb_params);
            $globalfeed->print_debug_info('oAuth result: '.var_export($fb_result, true), 'mb_facebook');
            
            // Save the subscription setup status. We want $fb_result to be null. If it is, things went well. If not, an error happened.
            $feed_options['fb_subscription_status'] = 'setup';
            
            $this->feed_options = $feed_options;
            
            $this->register_feed(true);
        }
    }
    
    /**
     * This function is called when we've received a call at the subscription URL
     * and handles updating our database of objects during a notification.
     * 
     * @todo Make sure this only allows posts from the subscribed user.
     */
    function handle_subscription_call() {
        $globalfeed = $this->globalfeed;
        $feed_options = $this->feed_options;
        
        // Get the updates (Which were given to us via post)
        $updates = json_decode(file_get_contents("php://input"), true); 
        
        $globalfeed->print_debug_info($updates, 'mb_facebook');
        
        //We're dealing with an update to a user object.
        if ( $updates['object'] == 'user') {
            
            foreach ($updates['entry'] as $entry) {
                // If the uid does not match what we want here, disregard it.
                //if ( $entry['id'] != $feed_options['user_info']['id'] )
                //    continue;
                
                // Loop through all the changes because Facebook groups changes by user object.
                foreach ($entry['changed_fields'] as $change) {
                    $url = get_bloginfo('wpurl')."?{$feed_options['feed_queryvar']}&process.updates";
                    $globalfeed->print_debug_info($url);
                    $globalfeed->print_debug_info($this->curl_request_async($url, array()));
                    $globalfeed->print_debug_info($entry['uid'].' changed their: '.$change.' at: '.$entry['time'], 'mb_facebook');
                }
            }
            
        }
        exit; 
    }
    
    /**
     * This calls the update_feed function and is accessible from the WP Admin page
     * 
     */
    public function ajax_do_update() {
        check_admin_referer( 'facebook-connect-settings_main' );
        wp_verify_nonce( 'facebook-connect-settings_main' );
        
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
     * @todo Add more post type support for tracking post type information
     * @todo Add the ability to map facebook types to WP types in UI.
     * 
     * @param type $args 
     */
    function update_feed( $args ) {
        $globalfeed = &$this->globalfeed;
        $feed_options = &$this->feed_options;
        $globalfeed->print_debug_info('Update Feed Called.',$this->feed_slug);
        
        // Different facebook objects require that we query different things
        $query_object = 'feed';
        $access_token = $feed_options['oauth_user_token'];
        switch ( $feed_options['subscribe_object_type'] ) {
            case 'user':
                $query_object = 'statuses';
            case 'page':
                $query_object = 'feed';
        }
        
        $graph_url = "https://graph.facebook.com/{$feed_options['object_to_subscribe']}/{$query_object}?access_token={$access_token}&since={$feed_options['last_feed_update']}&limit={$feed_options['max_feed_items']}";
        $updates = wp_remote_get($graph_url);
        
        if (is_wp_error($updates) || $updates['response']['code'] != '200')
            return false;
        
        // Use the regex to replace numerical values to strings for older versions of php
        $updates = json_decode( preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $updates['body']) );
        
        $globalfeed->print_debug_info($updates, 'mb_facebook');
        
        $feed_items = array();
        foreach ($updates->data as $update) {
            
            $update->id = explode('_', $update->id); 
            $page_id = $update->id[0];
            $update->id = $update->id[1];
            
            // Check that the update is from the object we're subscribing to
            if ( ((int) $update->from->id) != ((int) $page_id) )
                continue;
            
            // Check that the content is a type we can handle.
            if ( !in_array($update->type, array('status','video','link','photo')) )
                continue;
            
            $post_args = array(
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_author' => $update->from->id,
                'post_title' => $update->from->name,
                'post_date' => date('Y-m-d H:i:s', strtotime( $update->created_time ) ),
                'post_modified' => date('Y-m-d H:i:s', strtotime( $update->updated_time ) ),
                'meta' => array(
                    'author_name' => $update->from->name,
                    'facebook_id' => $update->id,
                ),
            );
            
            // Check if this item is already stored in the DB, and update if so...
            $posts_query = array(
                'post_type' => $globalfeed->post_type_namespace() . $this->get_slug(),
                'post_date' => $post_args['post_date'],
                'meta_key'  => 'facebook_id',
                'meta_value'=> $post_args['meta']['facebook_id'],
            );
            $posts = query_posts($posts_query);
            if ( count($posts) >= 1 )
                $post_args['ID'] = $posts[0]->ID;

            $content_args = array();
            switch ($update->type) {
                case 'video' :
                    $post_args['post_title'] = $update->name;
                    $post_args['post_excerpt'] = $update->description;
                    $post_args['post_format'] = 'video';
                    $post_args['meta']['source'] = $update->source;
                    $post_args['meta']['picture'] = $update->picture;
                    $post_args['meta']['link'] = $update->link;
                    $post_args['meta']['caption'] = $update->caption;
                    
                    // figure out where this video resides to display the correct content.
                    if ( !is_null($update->format) ) {
                        $post_args['meta']['video_source'] = 'facebook';
                        if ($globalfeed->media_display_mode() == 'embed') {
                            $content_args['media_format'] = 'video';
                            $content_args['media_info'] = array('facebook_embed_html' => array_pop(array_values($update->format)), 'width'=>'100%');
                        } else {
                            $post_args['meta']['embed_html'] = array_pop(array_values($update->format));
                        }
                    } else {
                        // Check for a youtube video
                        if ( strpos( $post_args['meta']['link'], 'youtube.com/watch?') ) {
                            // The link is to a youtube video. Extract the id, then call the embedder.
                            $post_args['meta']['video_source'] = 'youtube';
                            $post_args['meta']['youtube_id'] = substr( $post_args['meta']['link'], strripos( $post_args['meta']['link'], '/' ) + 9 );
                            
                            $content_args['media_format'] = 'video';
                            $content_args['media_info'] = array('youtube_video_id' => $post_args['meta']['youtube_id'], 'width' => '100%', 'height' => 390);
                        }
                    }
                    // Let GlobalFeed embed the content of required.
                    
                    break;
                case 'link' :
                    $post_args['post_format'] = 'link';
                    $post_args['meta']['link'] = $update->link;
                    $post_args['meta']['link_title'] = $update->name;
                    $post_args['meta']['description'] = $update->description;
                    $post_args['meta']['picture'] = $update->picture;
                    
                    $content_args['media_format'] = 'link';
                    $content_args['media_info'] = array(
                            'link' => $post_args['meta']['link'],
                            'link_text' => $post_args['meta']['link_title'],
                        );
                    break;
                case 'photo' :
                    $post_args['post_format'] = 'image';
                    // Get the full sized image link
                    $img = wp_remote_get("https://graph.facebook.com/{$update->object_id}/picture?type=normal&access_token=$access_token",array('method' => 'HEAD', 'redirection'=>0));
                    if ( !is_wp_error($img) )
                        $post_args['meta']['picture'] = $img['headers']['location'];
                    else
                        $globalfeed->print_debug_info ($img);
                    $globalfeed->print_debug_info($update);
                    $post_args['meta']['link'] = $update->link;
                    $content_args['media_format'] = 'image';
                    $content_args['media_info'] = array('source_url' => $post_args['meta']['picture']);
                    break;
                case 'status' :
                    $post_args['post_format'] = 'status';
                    break;
                default :
                    $post_args['post_format'] = 'status';
                    break;
            }
            
            // If this object has story attributes, parse them
            if (isset($update->story) && isset($update->story_tags) ) {
                if ( !isset($update->actions) )
                    $update->actions = array();
                
                // This is a story... Perform a bit of introspection to figure out how to link to it.
                foreach ($update->story_tags as $key => $value) {
                    $value = $value[0];
                    // Detect the object type
                    $type = '';
                    if ( isset($value->type) ) {
                        $type = $value->type;
                    } else {
                        $graph_url = "https://graph.facebook.com/{$value[0]->id}?access_token={$access_token}&metadata=1";
                        $introspect = wp_remote_get($graph_url);

                        if (is_wp_error($introspect))
                            return false;
                        $introspect = json_decode($introspect['body']);
                        $type = $introspect->type;
                        //$globalfeed->print_debug_info($introspect, 'mb_facebook');
                    }

                    if ( !empty($type) ) {
                        $action_name = 'View ' . ucwords($type);

                        $obj = new stdClass();
                        switch ($type) {
                            case 'user':
                                $obj->name = $action_name;
                                $obj->link = "https://facebook.com/{$value->id}";
                                break;
                            default:
                                $obj->name = $action_name;
                                $obj->link = "https://facebook.com/{$type}/{$value->id}";
                                break;
                        }

                        // Now that we know the type, check and see if we should add a link to this object in the content
                        if ( !isset($content_args['links']) )
                            $content_args['links'] = array();

                        $content_args['links'][$obj->link] = $value;

                        $update->actions[] = $obj;
                    }
                }
            }
            
            // The $content_args array will contain information about any media to be embedded in the content
            $post_args['post_content'] = $globalfeed->post_content(isset($update->message) ? $update->message : $update->story, $content_args);
            
            if ( isset($update->from) )
                $post_args['meta']['author'] = array( 'value' => $update->from, 'unique' => false );
            if ( isset($update->privacy) )
                $post_args['meta']['privacy'] = array( 'value' => $update->privacy, 'unique' => false );
            if ( isset($update->actions) )
                $post_args['meta']['actions'] = array( 'value' => $update->actions, 'unique' => false );
            
            $feed_items[] = $post_args;
        }
        
        // Set this as the last time the feed was updated
        if (count($feed_items)) {
            $feed_options['last_feed_update'] = time();
            
            // Save all of the feed items.
            $globalfeed->save_feed_items( $this->feed_slug, $feed_items );
            $this->register_feed(true);
            return count( $feed_items );
        } else {
            return 0;
        }
    }
    
    /**
     * Applies regular expressions to content and attempts to locate links and place them within anchors.
     * @param str $content The content to search
     */
    private function detect_content_links( $content ) {
        $pattern = "/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/";
        return preg_replace( $pattern, '<a href="$0" target="_blank">$0</a>', $content);
    }

    function process_updates( $initial = false ){
        $globalfeed = &$this->globalfeed;
        $feed_options = &$this->feed_options;
        
        $globalfeed->print_debug_info("async request called. Waiting 10 seconds.");
        sleep(10);
        $globalfeed->print_debug_info('ssync_finished');
        exit;
    }


    protected function _oauthRequest($url, $params) {
        // json_encode all params values that are not strings
        foreach ($params as $key => $value) {
          if (!is_string($value)) {
            $params[$key] = json_encode($value);
          }
        }

        return $this->makeRequest($url, $params);
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
        $globalfeed->print_debug_info($params, 'mb_facebook');
        $opts = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'facebook-php-3.0',
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
                      dirname(__FILE__) . '/fb_ca_chain_bundle.crt');
          $result = curl_exec($ch);
        }
        $globalfeed->print_debug_info(curl_errno($ch));
        curl_close($ch);
        return $result;
    }
    
    /**
     * Returns the URL the client should be redirected to in order to authorize facebook.
     * 
     * @return str The url to redirect to. 
     */
    public function get_client_auth_redirect_url() {
        $scope = 'read_stream,user_status,user_videos,user_photos,offline_access';
        $app_id = $this->feed_options[ 'app_id' ];
        $my_url = get_bloginfo('wpurl').'/';
        
        $dialog = "https://www.facebook.com/dialog/oauth?state=" . $this->feed_options['activate_state'] . "&redirect_uri=" . urlencode($my_url) . "&scope=" . $scope ;
            
        if (isset($app_id))
            $dialog .= "&client_id={$app_id}";
            
        return $dialog;
    }
    
    /** 
     * Handles the initial registration and o-auth authentication with Facebook.
     * 
     * return bool|WP_Error True if success, WP_Error if not.
     */
    function get_authorization() {
        $globalfeed = $this->globalfeed;
        /** @todo O-Auth Authentication */
        $feed_options = &$this->feed_options;

        $app_id = $feed_options[ 'app_id' ];
        $app_secret = $feed_options[ 'app_secret' ];
        $my_url = get_bloginfo('wpurl').'/';
        
        $code = $_REQUEST["code"];
        $globalfeed->print_debug_info('Auth Started.', 'mb_facebook');
        var_dump( $_REQUEST['state']);
        
        var_dump( $feed_options['activate_state']);
        if(trim((string) $_REQUEST['state']) == trim($feed_options['activate_state']) && !empty($_REQUEST['code'])) {var_dump('running');
            $token_url = "https://graph.facebook.com/oauth/access_token?"
            . "client_id=" . $app_id . "&redirect_uri=" . urlencode($my_url)
            . "&client_secret=" . $app_secret . "&code=" . $code;
            
            // Get user access credentials
            $response = wp_remote_get($token_url);
                
            if (is_wp_error( $response ))
                return false;
            
            $globalfeed->print_debug_info($response);
            $params = null;
            parse_str($response['body'], $params);
            
            $feed_options['oauth_user_token'] = $params['access_token'];
            
            if ( isset($params['expires']) )
                $feed_options['ouath_user_expires'] = $params['expires'];
            
            // Get app access credentials
            $response = wp_remote_get($token_url . '&grant_type=client_credentials');
            
            if (is_wp_error( $response ))
                return false;
            
            $params = null;
            parse_str($response['body'], $params);
            
            $feed_options['oauth_app_token'] = $params['access_token'];
            
            if ( isset($params['expires']) )
                $feed_options['ouath_app_expires'] = $params['expires'];
            
//            $globalfeed->print_debug_info($feed_options['oauth_user_token'] . $feed_options['oauth_app_token'], 'mb_facebook');
//            $graph_url = "https://graph.facebook.com/me?access_token=" 
//            . $feed_options['oauth_user_token']; // The user access token is used with the "me" context in order to get user info without knowing a user ID beforehand.
//
//            $feed_options['user_info'] = json_decode(file_get_contents($graph_url));
//            $globalfeed->print_debug_info( "Hello User: " .$feed_options['user_info']->name, 'mb_facebook' );
            
            $feed_options['fb_auth_status'] = 'authorized';
            
            // Generate a randomized verify token in order to setup the subscription server (must be done now instead of during setup).
            $feed_options['fb_verify_token'] = (string) mt_rand(100000000, 1000000000);
            
            $this->feed_options = $feed_options;
            
            //Feed info has been updated -- save changes.
            $this->register_feed( true );
            
            $_SERVER['mbgf_app_is_authed'] = true;
            set_transient('mbgf_app_is_authed', true, 3600);
            
            if ($this->feed_type == 'callback')
                $this->setup_fb_subscription();//Awesome! We're authenticated, now lets setup that subscription.
            // Request mode does not require any additional setup after registering the feed.
            
        }
        else {
            $globalfeed->print_debug_info("The state does not match. You may be a victim of CSRF. Received-Expected State: \n{$_REQUEST['state']}\n{$_SESSION['state']}", 'mb_facebook');
        }
        
        return false;
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
     * This function take the rawdata returned by the feed source (ie Titter, Facebook etc...) and converts it into
     * the array format expected by $this->globalfeed->save_feed_items(), then it saves it using this function.
     * 
     * @uses $this->globalfeed->save_feed_items()
     * @param array $feed_items An array containing the raw data from the feed source that should be parsed and saved.
     */
    function store_feed_items( $feed_items ){
        $this->globalfeed->print_debug_info( "Store Facebook Feed Items: \n" . var_export($feed_items, true) );
    }
    
    function print_admin_page(){
        $mb_globalfeed = $this->globalfeed;
        $mb_globalfeed->include_admin_theming();
        /* @var $mbgfa_theme mbgfa_theme */
        global $mbgfa_theme;
        
        // Include the feed styles
        wp_enqueue_style('mb_facebook', plugins_url( 'feeds/mb_facebook/pages/style.css', $mb_globalfeed->plugin_location() ));
        wp_enqueue_script('jquery_cookie', plugins_url( 'feeds/mb_facebook/pages/js/jquery.cookie.js', $mb_globalfeed->plugin_location() ), array('jquery'));
        
        // Include javascript client tools etc..
        $mbgfa_theme->queue_js_client_tools();
        wp_enqueue_script('mb_facebook', plugins_url( 'feeds/mb_facebook/pages/js/mb_facebook.js', $mb_globalfeed->plugin_location() ), array('jquery','mb_globalfeed_client_tools'));
        
        // Detect whether the plugin has been setup and configured yet. If not, run the user through the setup.
        // If yes, serve up plugin options.
        if ($this->feed_options['initial_setup_done'] == false) {
            require_once('pages/setup.php');
            //$this->feed_options['initial_setup_done'] == true;
        } else {
            // The specified page must be in our array of pages -- this stops people from accessing other files on the FS
            if ( isset($_REQUEST['mbgf_admin_page']) && array_key_exists($_REQUEST['mbgf_admin_page'], $this->admin_pages))
                require_once( "pages/{$_REQUEST['mbgf_admin_page']}.php" );
            else
                require_once( 'pages/settings-main.php' );
        }
        
        // This function is created when the page is loaded. It is passed the feed settings. It returns true if changes were made to the settings.
        $changed = show_mb_facebook_page( $this->feed_options );
        
        if ( $changed == true )
            $this->register_feed( true );
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
    function mb_globalfeed_facebook_connect_activate_initial () {
        global $mb_globalfeed;
        $mb_globalfeed->print_debug_info('Activate initial');
        $activation_instance = new mb_globalfeed_facebook_connect($mb_globalfeed);
        $activation_instance->activate_feed();
    }

    add_action('mbgf_activate_feed-' . $mb_globalfeed->get_shortened_feed_slug('facebook_connect'), 'mb_globalfeed_facebook_connect_activate_initial');
    $mb_globalfeed->print_debug_info('mbgf_activate_feed-' . $mb_globalfeed->get_shortened_feed_slug('facebook_connect'));
}
?>
