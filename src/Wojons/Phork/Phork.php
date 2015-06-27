<?php
namespace Wojons\Phork;

use Wojons\Phork\Scheduler;
use Wojons\Phork\Channel;

class Phork {
    
    public $scheduler = null;
    
    public function __construct () {
        $this->scheduler = new Scheduler;
    }
    
    public function exec($func) {
        $this->scheduler->new_co($func);
    }
    
    public function branch($func) {
        $pid = pcntl_fork();
        if ($pid) {
            return True;
        } else {
            //clear old schedules
            $this->scheduler = new Scheduler;
            $this->exec($func);
            $this->scheduler->loop();
            exit(); // we are done
        }
    }
}
