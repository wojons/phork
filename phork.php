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
}
