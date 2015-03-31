<?php

require_once('../phork.php');

$phork = new phork();
$channel = new channel(123777);
$channel->destroy();


print "MAIN THREAD START".PHP_EOL;

$phork->exec(function(&$s) { 
    yield;
    
    $channel = new channel(123777);
    $s->promise_set(function() use ($channel) {
            if($channel->stats()['msg_qnum'] < 100) {
                return True;
            }
            //print "fail".PHP_EOL;
            return False;
        });
    while (true) {
        for ($x=0;$x<mt_rand(100, 10000);$x++) {
            $msg = "Hello ".md5(microtime());
            $channel->send($msg);
            usleep(500);
            print getmypid().": ".$msg.PHP_EOL;
        }
        $c_time = time();
        //var_dump($channel->stats());
        
        yield;
    }
    
});

$name = "bob1";
print "FORKING".PHP_EOL;
$phork->branch(function(&$s) use ($name) {
    
    yield;
    $channel = new channel(123777);
    $s->promise_set(function() use ($channel) {
            return $channel->try_promise();
    });
    while (true) {
        yield;
    
        $msg = $channel->message;
        print $name."-".getmypid().": ".$msg.PHP_EOL;
    }
});


$phork->scheduler->loop();




print "MAIN THREAD END".PHP_EOL;
