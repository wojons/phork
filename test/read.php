<?php

//connect to pipe
$q = msg_get_queue (123666);

while(True) {
    //read from pipe
    if (msg_receive ( $q , 0 , $msgtype , 1024, $message, true)) {
        echo $message.PHP_EOL;
    } else { sleep(1); }
}
