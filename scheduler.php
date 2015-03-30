<?php

class scheduler {
    
    private $co_stack = array();
    private $co_pointer = null;
    private $promise = array();
    
    function new_co($func) {
        $this->co_stack[] = $func;
        $this->co_pointer = count($this->co_stack)-1;
        //print "running";
        $t = array();
        $this->co_stack[$this->co_pointer] = $this->co_stack[$this->co_pointer]($this);
        $this->co_stack[$this->co_pointer]->current(); //get to first yield
        $this->co_stack[$this->co_pointer]->next(); //run to next yield to get a promise
        //print "meh";
    }
    
    function promise($promise, $pointer=null) {
        if ($pointer == null) { $pointer = $this->co_pointer; }
        $this->promise[] = array('co_pointer' => $pointer, 'promise' => $promise);
    }
    
    function loop() {
        
        //var_dump($this->promise);
        while (!empty($this->promise)) {
            $loop_time = microtime(true);
            //var_dump($this->promise);
            foreach($this->promise as $dex=>$dat) {
                if ($dat['promise']()== true) {
                    $this->co_pointer = $dat['co_pointer'];
                    unset($this->promise[$dex]);
                    $this->co_stack[$this->co_pointer]->next();
                }
            }
            
            //print "DEX: ".$dex.PHP_EOL;
            if (max(array_keys($this->promise)) > $dex) {
                continue;
            }
            
            $loop_end_time = microtime(true);
            if($loop_time-$loop_end_time < 1) {
                usleep((($loop_time+1)-$loop_end_time)*1000000);
                print "PAUSE".PHP_EOL;
            }
        }
    }
    
    function try_promise() {
        
    }
}

class promise {
    
    
}
