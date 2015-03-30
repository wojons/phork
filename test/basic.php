<?php

require_once('../phork.php');

$phork = new phork();
$channel = new channel(123777);
$channel->destroy();


print "MAIN THREAD START".PHP_EOL;

$phork->exec(function(&$s) { 
    yield;
    
    $channel = new channel(123777);
        
    while (true) {
        for ($x=0;$x<5;$x++) {
            $msg = "Hello ".md5(microtime());
            $channel->send($msg);
            print $msg.PHP_EOL;
        }
        $c_time = time();
        $s->promise(function() use ($c_time) {
            if($c_time+1 < time()) {
                return True;
            }
            //print "fail".PHP_EOL;
            return False;
        });
        yield;
    }
    
});

$name = "bob1";
print "FORKING".PHP_EOL;
$phork->exec(function(&$s) use ($name) {
    
    yield;
    $channel = new channel(123777);
    while (true) {
        $s->promise(function() use ($channel) {
            return $channel->try_promise();
        });
        yield;
    
        $msg = $channel->message;
        print $name.": ".$msg.PHP_EOL;
    }
});

$phork->scheduler->loop();




print "MAIN THREAD END".PHP_EOL;
