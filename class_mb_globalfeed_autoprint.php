<?php

/*
 * This class will eventually handle the autoprinting of feed items.
 */


class mb_globalfeed_autoprint {
            
        /**
         * Called for each post in the loop -- automatically prints out the post if it is a feed item.
         * 
         * If the plugin is configured to move the loop ahead to the next post after automatically printing the item,
         * this function will do that.
         * 
         * @param array $post the current post in the loop
         */
        function post_auto_print( $post ) {
            global $post;
            
            //Check that the post type belongs to Global Feed...
            if ( strpos( $post->post_type, $settings[ 'post_type_namespace' ] ) === false ) 
                return;
            
            //Okay were good, now determine the feed that this belongs to...
            $feed_slug = substr( $post->$post_type, strlen( $settings[ 'post_type_namespace' ] ) );
            
            extract( $this->registered_feeds[ $feed_slug ] );
            
            $current_page = $this->current_page;
            
//            //Decide whether to show the feed
//            $show_feed = false;
//            //Is this using the custom_feed_show feature?
//            if ( $feed_options[ 'custom_feed_show' ] ) {
//                
//                //Check if custom_feed_show_cache is enabled, and whether this feeds show has already been cached.
//                if ( $this->settings[ 'cache_custom_feed_shows' ] == true ) {
//                    if ( isset( $this->settings[ 'pages_and_feeds' ][ 'custom_feed_show_cache' ][ $feed_slug ] ) )
//                        $show_feed = $this->settings[ 'pages_and_feeds' ][ 'custom_feed_show_cache' ][ $feed_slug ];
//                    else
//                        $show_feed = $this->settings[ 'pages_and_feeds' ][ 'custom_feed_show_cache' ][ $feed_slug ] 
//                                    = apply_filters( 'mb_globalfeed_show_feed-' . $feed_slug, false, $current_page );
//                } else {
//                    //Okay, call an action to decide whether the feed should be shown
//                    $show_feed = apply_filters( 'mb_globalfeed_show_feed-' . $feed_slug, false, $current_page );
//                }
//                
//            } else {
//                
//            }
//            
//            if ( !$show_feed )
//                return;
            
            //Tell the feed to show itself
            if ( $feed_options[ 'feed_show_action' ] )
                do_action( 'mb_globalfeed_print_feed_item-' . $feed_slug, $current_page );
            else
                call_user_func( array( $feed_class_name, 'print_feed_item' ) );
            
            //Should the loop be made to jump to the next item?
            if ( $settings[ 'auto_skip_feed_items_in_loop' ] )
                the_post();
        }
        
}

?>