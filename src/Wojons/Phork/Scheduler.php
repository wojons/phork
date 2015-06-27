<?php
namespace Wojons\Phork;

use Wojons\Phork\Promise;

class Scheduler {
    
    private $co_stack = array();
    private $co_pointer = null;
    private $promise = array();
    private $yields = array();
    
    private $delay = 0.1;
    private $master_key = null; //this is the key that is ussed when building channels
    
    public function __construct($delay) {
        $pid   = getmypid();
        $time  = time();
        $this->master_key = (int)"$pid.$time";
    }
    
    public function new_co($func) {
        $this->co_stack[] = $func;
        $this->co_pointer = count($this->co_stack)-1;
        //print "running";
        $t = array();
        $this->co_stack[$this->co_pointer] = $this->co_stack[$this->co_pointer]($this);
        $this->co_stack[$this->co_pointer]->current(); //get to first yield
        $this->co_stack[$this->co_pointer]->next(); //run to next yield to get a promise
        //print "meh";
    }
    
    public function promise_set($promise, $sub=null, $pointer=null) {
        if ($pointer == null) { $pointer = $this->co_pointer; }
        $this->promise[$pointer][] = array('promise' => $promise);
        
        if (isset($this->yields[$pointer])) { //make sure it does not run with the yiels group
            unset($this->yields[$pointer]);
        }
        
        return max(array_keys($this->promise[$pointer]));
    }
    
    public function promise_del($promise_num, $pointer=null) {
        if ($pointer == null) { $pointer = $this->get_current_co_pointer(); }
        unset($this->promise[$pointer][$pointer_num]);
        if (empty($this->promise[$pointer])) {
            $this->yields[$pointer] = True;
        }
    }
    
    public function loop() {
        
        //var_dump($this->promise);
        while (!empty($this->promise)) {
            $loop_time = microtime(true);
            //var_dump($this->promise);
            foreach($this->promise as $dex=>$dat) {
                foreach($dat as $prom_dex => $prom_dat) {
                    if ($prom_dat['promise']()== true) {
                        do {
                            $this->co_pointer = $dex;
                            $this->co_stack[$this->co_pointer]->send($prom_dex);
                        } while ($prom_dat['promise']() == True);
                        break; //we have meet one of the promises no need to stick around
                    }
                }
            }
            
            //run all the things that simply yieled
            foreach($this->yields as $dex=>$dat) {
                $this->co_stack[$this->co_pointer]->next();
            }
            
            //print "DEX: ".$dex.PHP_EOL;
            if (max(array_keys($this->promise)) > $dex) {
                continue;
            }
            
            $loop_end_time = microtime(true);
            if($loop_time-$loop_end_time < $this->delay) {
                print "PAUSE".PHP_EOL;
                usleep((($loop_time+$this->delay)-$loop_end_time)*1000000);
            }
        }
    }
    
    public function try_promise() {
        
    }
}