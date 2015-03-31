<?php

require_once('scheduler.php');
require_once('channel.php');

class phork {
    
    public $scheduler = null;
    
    function __construct () {
        $this->scheduler = new scheduler();
    }
    
    function exec($func) {
        $this->scheduler->new_co($func);
    }
    
    function branch($func) {
        $pid = pcntl_fork();
        if ($pid) {
            return True;
        } else {
            //clear old schedules
            $this->scheduler = new scheduler();
            $this->exec($func);
            $this->scheduler->loop();
            exit(); // we are done
        }
    }
}
