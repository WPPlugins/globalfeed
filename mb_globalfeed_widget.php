<?php

class mb_globalfeed_widget extends WP_Widget {
    /**
     * The GlobalFeed instance this class was created with.
     * @var mb_globalfeed $globalfeed 
     */
    private $globalfeed;
    /**
     * Register the widget with WordPress. 
     * 
     * @param mb_globalfeed $globalfeed The GlobalFeed instance this class was created with.
     */
    public function __construct() {
        global $mb_globalfeed;
        $this->globalfeed = &$mb_globalfeed;
        
        parent::__construct(
            'mb_globalfeed_widget', 
            'GlobalFeed Widget', 
            array(
                'description' => __('GlobalFeed widget for embedding feeds in pages.', __('mb_globalfeed')),
            ),
            array( 'width' => 400, 'height' => 200 )
        );
    }
    
    /**
     * The default widget settings.
     * @var array $defaults
     */
    private $defaults = array(
            'title'                => 'My GlobalFeed',
            'feeds'                => 'all',
            'num_items'            => 10,
            'include_blog_posts'   => false,
            'use_globalfeed_theme' => 'default'
        );
    
    function register(){
        
    }
    
    /**
     * Displays the widget form in WP Admin
     * 
     * @see WP_Widget::form()
     * 
     * @param array $instance Peviously saved settings from the DB.
     */
    public function form( $instance ) {
        extract( wp_parse_args( $instance, $this->defaults ) );
        
        // Get the list of selected feeds
        if ( $feeds != 'all' )
            $feeds_set = explode( ',', $feeds );
        ?>
<p>
<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
<table class="form-table">
    <tr>
        <th><label for="<?php $this->field_id( 'feeds' ); ?>"><?php _e( 'Feeds to show:', 'mb_globalfeed'); ?></label></th>
        <td>
            <?php $i = 0; foreach ($this->globalfeed->get_registered_feeds() as $feed_slug => $feed_name) {
                    $checked = '';
                    if ( $feeds == 'all' || in_array($feed_slug, $feeds_set) )
                        $checked = 'checked="checked"';
                ?>
            <label for="<?php echo $this->get_field_id('feeds') . "-o$i"; ?>" ><input type="checkbox" id="<?php echo $this->get_field_id('feeds') . "-o$i"; ?>" name="<?php $this->field_name('feeds') ?>[]" value="<?php echo $feed_slug; ?>" <?php echo $checked; ?>/> <?php echo $feed_name; ?></label><br />
            <?php $i++;} ?>
        </td>
    </tr>
    <tr>
        <th><label for="<?php $this->field_id( 'num_items' ); ?>"><?php _e( 'Number of feed items to show:', 'mb_globalfeed'); ?></label></th>
        <td><input type="text" id="<?php $this->field_id( 'num_items' ); ?>" name="<?php $this->field_name('num_items') ?>" value="<?php echo $num_items; ?>" /></td>
    </tr>
    <tr>
        <th><label for="<?php $this->field_id( 'include_blog_posts' ); ?>"><?php _e( 'Include blog posts:', 'mb_globalfeed'); ?></label></th>
        <td><input type="checkbox" id="<?php $this->field_id( 'include_blog_posts' ); ?>" <?php echo $include_blog_posts ? 'checked="checked"' : ''; ?> name="<?php $this->field_name('include_blog_posts') ?>" /></td>
    </tr>
    <tr>
        <th><label for="<?php $this->field_id( 'use_globalfeed_theme' ); ?>"><?php _e( 'Theme:', 'mb_globalfeed'); ?></label></th>
        <td>
            <label for="<?php echo $this->get_field_id('use_globalfeed_theme') . "-o0"; ?>" ><input type="radio" id="<?php echo $this->get_field_id('use_globalfeed_theme') . "-o0"; ?>" name="<?php $this->field_name('use_globalfeed_theme') ?>" value="default" <?php echo $use_globalfeed_theme == 'default' ? 'checked="checked"' : ''; ?>/> <?php _e('GlobalFeed Default') ?></label><br />
            <label for="<?php echo $this->get_field_id('use_globalfeed_theme') . "-o1"; ?>" ><input type="radio" id="<?php echo $this->get_field_id('use_globalfeed_theme') . "-o1"; ?>" name="<?php $this->field_name('use_globalfeed_theme') ?>" value="wordpress" <?php echo !$use_globalfeed_theme ? 'checked="checked"' : ''; ?>/> <?php _e('WordPress Theme') ?></label><br />
            <?php $i = 2; foreach ($this->globalfeed->available_themes() as $theme_slug => $theme_name) {
                    $checked = '';
                    if ( $use_globalfeed_theme == $theme_slug )
                        $checked = 'checked="checked"';
                ?>
            <label for="<?php echo $this->get_field_id('use_globalfeed_theme') . "-o$i"; ?>" ><input type="radio" id="<?php echo $this->get_field_id('use_globalfeed_theme') . "-o$i"; ?>" name="<?php $this->field_name('use_globalfeed_theme') ?>" value="<?php echo $theme_slug; ?>" <?php echo $checked; ?>/> <?php echo $theme_name; ?></label><br />
            <?php $i++;} ?>
        </td>
    </tr>
</table>
</p>
        <?php
    }
    
    /**
     * Sanitize the saved values
     * 
     * @param array $new_instance The new instance of values to be saved.
     * @param array $old_instance The old instance of values
     * @return array The sanitized values
     */
    public function update( $new_instance, $old_instance ) {$this->globalfeed->print_debug_info($new_instance);
        $instance = wp_parse_args( $old_instance, $this->defaults );
        
        // Sanitize the title
        $instance['title'] = strip_tags( $new_instance['title'] );
        
        // Check that num_items is an integer
        $instance['num_items'] = (int) $new_instance['num_items'];
        if ( $instance['num_items'] == 0 )
            $instance['num_items'] = $this->defaults['num_items'];
        
        // Check that the selected GlobalFeed theme is still available.
        if ( $new_instance['use_globalfeed_theme'] == 'wordpress' )
            $instance['use_globalfeed_theme'] = false;
        elseif ( array_key_exists($new_instance['use_globalfeed_theme'], $this->globalfeed->available_themes()) )
            $instance['use_globalfeed_theme'] = $new_instance['use_globalfeed_theme'];
        else
            $instance['use_globalfeed_theme'] = $this->defaults['use_globalfeed_theme'];
        
        // Convert the value from include_blog_posts to native bool
        $instance['include_blog_posts'] = $new_instance['include_blog_posts'] == 'on';
        
        // Check that the selected feeds are still all available
        if ( $new_instance['feeds'] != 'all' ) {
            $set_feeds = array();
            
            foreach ($new_instance['feeds'] as $feed) {
                if ( $this->globalfeed->feed_activated( strip_tags($feed) ) )
                    $set_feeds[] = strip_tags($feed);
            }
            
            $instance['feeds'] = implode( ',', $set_feeds );
        } else 
            $instance['feeds'] = 'all';
        
        return $instance;
    }
    
    /**
     * Displays the widget
     * 
     * @uses $mb_globalfeed->do_shortcode()
     * @param array $args
     * @param array $instance 
     */
    public function widget( $args, $instance ) {
        if ( $instance['feeds'] == '' )
            return;
        
        extract( $args );
        
        echo $before_widget;
        if ( $instance['title'] ) 
            echo $before_title . $instance['title'] . $after_title;
        
        // do_shortcode will handle printing out of the GlobalFeed
        echo $this->globalfeed->do_shortcode( $instance );
        
        echo $after_widget;
    }
    
    function field_name( $field_name ){
        echo $this->get_field_name($field_name);
    }
    
    function field_id( $field_id ){
        echo $this->get_field_id($field_id);
    }
}