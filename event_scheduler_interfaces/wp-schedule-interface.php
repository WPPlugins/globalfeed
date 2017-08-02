<?php

/*
 * Schedules events using the builtin WordPress scheduler.
 * 
 * @todo Have this check the things that wp_schedule_event() does so that it can generate more informative errors.
 */
require_once 'mb_globalfeed_event_scheduler_interface.php';
class mb_globalfeed_wp_scheduler extends mb_globalfeed_event_scheduler_interface{
    private $hook_name = 'mb_globalfeed_wp_scheduler_do';
    private $globalfeed;
    
    function __construct( $globalfeed ) {
        $this->globalfeed = &$globalfeed;
        
        if ($this->globalfeed)
            $globalfeed->print_debug_info('Scheduler Loaded', 'wp-schedule-interface');
        
        add_filter($this->hook_name, array( &$this, 'do_scheduled_action'), 1, 3);
    }
    
    function schedule_event( $args ) {
        $this->globalfeed->print_debug_info("Scheduler scheduling", 'wp-schedule-interface');
        $this->globalfeed->print_debug_info($args);
        $caller = $args[0];
        $action = $args[1];
        $period = $args[2];
        $first  = (int) $args[3];
        $update = $args[4];
        
        //Set the timestamp to occur immediately if it has not been set yet.
        if ( $first == 0 ) $first = time();
        
        //Return false if this doesn't work out, or true otherwise.
        return wp_schedule_event( $first, $period, $this->hook_name, 
                array( 
                    'caller' => $caller, 
                    'action' => $action,
                    'args' => $args
                )
        ) !== false;
    }
    
    function unschedule_event( $args ) {
        if (is_array($args)) {
            $caller = $args[0];
            $action = $args[1];
        } else if (is_string($args)) {
            $caller = $args;
            $action = '';
        } else {
            return false;
        }
        
        $schedule = _get_cron_array();
        $new_schedule = array();
        
        foreach ($schedule as $timestamp => $event) {
            if ( !isset($event[$this->hook_name]) ) {
                $new_schedule[$timestamp] = $event;
            } elseif ( $action == '' ) {
                $vals = array_values($event[$this->hook_name]);
                if ( count($vals) === 1 ) {
                    $inner = array_shift($vals);
                    $this->globalfeed->print_debug_info($inner);
                    $this->globalfeed->print_debug_info( $inner['args']['caller'] != $caller );
                    if ( $inner['args']['caller'] != $caller )
                        $new_schedule[$timestamp] = $event;
                } else {
                    $nevent = array();
                    $this->globalfeed->print_debug_info($event[$this->hook_name]);
                    foreach ($event[$this->hook_name] as $key => $value) {
                        $this->globalfeed->print_debug_info($value);
                        if ( $value['args']['caller'] != $caller )
                            $nevent[$key] = $value;
                    }
                    
                    if ( count($nevent) )
                        $new_schedule[$timestamp] = $nevent;
                }
            } else {
                $inner = array_shift(array_values($event[$this->hook_name]));
                if ( $event[$this->hook_name]['args']['caller'] != $caller &&
                        $event[$this->hook_name]['args']['action'] != $action )
                    $new_schedule[$timestamp] = $event;
            }
        }
        
        // Sve the cron array
        if ( !empty($new_schedule) )
            _set_cron_array($new_schedule);
    }
    
    function get_scheduled_events() {
        $return_array = array();
        $schedule = _get_cron_array();
        foreach ($schedule as $key => $value) {
            if (isset($value[$this->hook_name]))
                $return_array[$key] = $value;
        }
        return $return_array;
    }
    
    function do_scheduled_action( $caller, $action, $args){
        global $mb_globalfeed, $$caller;
        $mb_globalfeed->print_debug_info('do_scheduled_action called.', 'wp-schedule-interface');
        $mb_globalfeed->print_debug_info($args, 'wp-schedule-interface');
        
        if ( !class_exists($caller) || !method_exists($caller, $action)) {
            $mb_globalfeed->print_debug_info('Scheduled action method or class did not exist at runtime', 'wp-schedule-interface');
            return;
        }
        
        $mb_globalfeed->print_debug_info( call_user_func( array( &$$caller, $action ), $args ) );
    }
    
}
?>
