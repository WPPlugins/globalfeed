<?php

if ( !class_exists( 'mbgfa_theme' ) ) {

    /**
     * This class provides tools for theming feed admin pages.
     * @author Michael Blouin
     * @link www.MichaelBlouin.ca
     */
    class mbgfa_theme {
        
        private function imgdir( $img = ''){
            global $mb_globalfeed;
            return plugins_url( "admin/pages/images/$img", $mb_globalfeed->plugin_location() );
        }
        
        private function img_with_tooltip( $icon, $tooltip, $return = false ){
            if (isset($tooltip))
                $ret = "<img src='{$icon}' alt='$tooltip' class='informative tooltip'/>";
            else
                $ret = "<img src='{$icon}' class='informative'/>";
                
            if ( $return )
                return $ret;
            else 
                echo $ret;
        }
        
        /**
         * Outputs an Informative "i" icon with a javascript ToolTip for providing 
         * more information to the user on a setting when the user hovers over the image. 
         * 
         * @param str $tooltip The tooltip to display on hover
         * @param int $size The size of tooltip to display (16,24)
         * @param bool $return Whether to return or display the element
         */
        public function informative( $tooltip, $size = 24, $return = false ) {
            if (isset($size) && in_array($size, array(16)))
                $ret = $this->img_with_tooltip($this->imgdir( "information-{$size}x{$size}.png" ), $tooltip, $return);
            else
                $ret = $this->img_with_tooltip($this->imgdir( 'information.png' ), $tooltip, $return);
            if ( $return )
                return $ret;
        }
        
         /**
         * Outputs the bug icon with a javascript ToolTip.
         * 
         * @param str $tooltip The tooltip to display on hover
         * @param int $size The size of tooltip to display (16,24)
         * @param bool $return Whether to return or display the element
          */
        public function bug( $tooltip, $size = 24, $return = false ){
            if (isset($size) && in_array($size, array(16,24)))
                $ret = $this->img_with_tooltip($this->imgdir( "bug-{$size}x{$size}.png" ), $tooltip, $return);
            else
                $ret = $this->img_with_tooltip($this->imgdir( 'bug.png' ), $tooltip, $return);
            
            if ( $return )
                return $ret;
        }
        
        /**
         * Outputs a plus icon with a javascript ToolTip.
         * 
         * @param str $tooltip The tooltip to display on hover
         * @param int $size The size of tooltip to display (16,24)
         * @param bool $return Whether to return or display the element
         */
        public function add( $tooltip, $size = 24, $return = false ){
            if (isset($size) && in_array($size, array(16,24,32)))
                $ret = $this->img_with_tooltip($this->imgdir( "add_button-{$size}x{$size}.png" ), $tooltip, $return);
            else
                $ret = $this->img_with_tooltip($this->imgdir( 'add_button.png' ), $tooltip, $return);
            
            if ( $return )
                return $ret;
        }
        
        /**
         * Outputs a minus icon with a javascript ToolTip.
         * 
         * @param str $tooltip The tooltip to display on hover
         * @param int $size The size of tooltip to display (16,24)
         * @param bool $return Whether to return or display the element
         */
        public function minus( $tooltip, $size = 24, $return = false ){
            if (isset($size) && in_array($size, array(16,24,32)))
                $ret = $this->img_with_tooltip($this->imgdir( "minus_button-{$size}x{$size}.png" ), $tooltip, $return);
            else
                $ret =$this->img_with_tooltip($this->imgdir( 'minus_button.png' ), $tooltip, $return);
            
            if ( $return )
                return $ret;
        }
        
        /**
         * Outputs the GlobalFeed icon with a javascript tooltip.
         * 
         * @param str $tooltip The tooltip to display on hover
         * @param int $size The size of tooltip to display (16,24)
         * @param bool $return Whether to return or display the element
         */
        public function globalfeed( $tooltip, $size = 24, $return = false ){
            if (isset($size) && in_array($size, array(16,24,32,48)))
                $ret = $this->img_with_tooltip($this->imgdir( "GlobalFeed-{$size}x{$size}.png" ), $tooltip, $return);
            else
                $ret =$this->img_with_tooltip($this->imgdir( 'GlobalFeed.png' ), $tooltip, $return);
            
            if ( $return )
                return $ret;
        }
        
        /**
         * Load javascript display tools support. Good API for showing messages, alerts
         * and wizards that are consistant with the general look of GlobalFeed.
         * 
         * Automatically ensures that jQuery and Fancybox are loaded.
         * 
         * @global type $mb_globalfeed 
         */
        public function queue_js_client_tools(){
            global $mb_globalfeed;
            wp_enqueue_script('mb_globalfeed_client_tools', plugins_url( 'admin/pages/js/client_tools.js', $mb_globalfeed->plugin_location() ), array('jquery'));
            wp_enqueue_script('fancybox', plugins_url( 'admin/pages/js/jquery.fancybox/jquery.fancybox.js', $mb_globalfeed->plugin_location() ), array('jquery', 'mb_globalfeed_client_tools'));
            
            wp_enqueue_style('fancybox', plugins_url( 'admin/pages/js/jquery.fancybox/jquery.fancybox.css', $mb_globalfeed->plugin_location() ));
        }
        
        /**
         * Outputs the common spinning ajax indicator. Defaults to hidden.
         * 
         * @param bool $hidden Whether or not the icon should be hidden by default.
         * @param str $classes Additional classes to add to the <img> object.
         */
        public function ajaxIndicator( $hidden = true, $classes = '', $return = false ) {
            if ($hidden)
                $ret = "<img alt='loading...' class='ajax-loading-icon $classes' src='" . get_bloginfo('wpurl') . "/wp-admin/images/wpspin_light.gif' />";
            else
                $ret = "<img alt='loading...' class='ajax-loading-icon $classes' style='display:block;' src='" . get_bloginfo('wpurl') . "/wp-admin/images/wpspin_light.gif' />";
            
            if ($return)
                return $ret;
            else
                echo $ret;
        }

        /** 
         * Includes a footer that that runs the standard theming JS 
         */
        public function footer(){
            ?>
<script type="text/javascript">
jQuery(document).ready(function(){
    jQuery('.tooltip').qtip({content:{attr:'alt'}});
});
</script>
            <?php
        }
    }
}