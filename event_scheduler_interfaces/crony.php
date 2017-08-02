<?php

/*
 * The event scheduler to interact with the Crony CronJob Manager.
 */

class crony_event_scheduler extends mb_globalfeed_event_scheduler_interface{
    /**
     * Schedules the given event.
     * 
     * @param string $caller The slug for the item that wants the action. (Usually the feed slug).
     * @param string $action The action to be performed.
     * @param int $period How often the action should be performed. (In seconds)
     * @param timestamp $first (optional) When the first execution should be. (Not always supported)
     */
    function schedule_event( $caller, $action, $period, $first = 0 ){
        
    }
    
    /**
     * Removes the given event. Can accept either the event id, or the caller and action.
     * 
     * @param int $id (optional) The ID of the event to remove.
     * @param str $caller (optional) The event caller slug. Must be accompanied by $action.
     * @param str $action (optional) The action slug.
     */
    function unschedule_event( $id = 0, $caller = '', $action = '' ){
        
    }
}
?>
