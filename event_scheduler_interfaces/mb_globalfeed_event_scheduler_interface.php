<?php

/*
 * The base class for event scheduler interface to be used to MB_GlobalFeed.
 */


class mb_globalfeed_event_scheduler_interface{
    
    /**
     * Schedules the given event.
     * 
     * @param string $caller The slug for the item that wants the action. (Usually the feed slug).
     * @param string $action The action to be performed.
     * @param int $period How often the action should be performed. (In seconds)
     * @param timestamp $first (optional) When the first execution should be. (Not always supported)
     * @param bool $update (optional) Whether the action should be updated if it already exists.
     */
    function schedule_event( $caller, $action, $period, $first = 0, $update = false ){
        
    }
    
    /**
     * Removes the given event. If just the caller is specific, this removes all actions for that caller.
     * 
     * @param str $caller (optional) The event caller slug. Must be accompanied by $action.
     * @param str $action (optional) The action slug.
     */
    function unschedule_event( $caller = '', $action = ''){
        
    }
    
    /**
     * Reports all current scheduled events.
     */
    function get_scheduled_events(){
        
    }
    
    /**
     * Called by the scheduling subsystem which then calls the registered caller and action.
     * (Therefore acting as an in-between between the scheduling subsys and the feed.)
     * 
     * @param array $args The arguments to be passed to the function.
     */
    function do_scheduled_action( $args ){
        global $mb_globalfeed, $$args[0];
        $mb_globalfeed->print_debug_info('do_scheduled_action called.');
        $mb_globalfeed->print_debug_info($args);
        $mb_globalfeed->print_debug_info($args[0]);
        $mb_globalfeed->print_debug_info($args[1]);
        call_user_func_array( array( &$$args[0], $args[1] ), $args[2] );
    }
    
    /**
     * Unschedules all events scheduled with this interface and sends them all
     * to the new preferred scheduler interface.
     */
    function change_scheduler(){
        
    }
    
    
}

?>
