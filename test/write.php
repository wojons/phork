<?php

//connect to pipe
$q = msg_get_queue (123666);

while(True) {
    //read from pipe
    sleep(1);
    msg_send ( $q , 1, (string)time());
}
