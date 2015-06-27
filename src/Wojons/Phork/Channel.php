<?php
namespace Wojons\Phork;

class Channel {

    private $queue = null;
    public $message = null;
    private $msgtype = null;

    public function __construct($channel) {
        $this->queue = msg_get_queue($channel);
    }
    
    public function send($message) {
        $result = msg_send ($this->queue, 1, $message);
        if ($result == False) {
            print "end error".PHP_EOL;
        }
        return $result;
    }
    
    public function destroy() {
        return msg_remove_queue($this->queue);
    }
    
    public function stats() {
        return msg_stat_queue ($this->queue);
    }
    
    public function try_promise() {
        $message = null;
        $msgtype = null;
        return msg_receive ( $this->queue , 0 , $this->msgtype , 1024 , $this->message, true, MSG_IPC_NOWAIT);
    }

}
