<?php
namespace Wojons\Phork;

class Socket {
    protected $socket;
    protected $bind;
    protected $host;
    protected $port;
    protected $listen;
    protected $clients;
    protected $client_list;

    public function __construct($host = '127.0.0.1', $port = 443) 
    {
        $this->host = $host;
        $this->port = $port;
        $this->clients = [];
        $this->client_list = [];
    }

    public function execute()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        if (!is_resource($this->socket))
        {
            throw new Exception('Could not create Socket: ' . socket_strerror(socket_last_error()));

            return false;
        }

        $this->bind = socket_bind($this->socket, $this->host, $this->port);

        if (!$this->bind)
        {
            throw new Exception('Could not bind Socket: ' . socket_strerror(socket_last_error()));

            return false;
        }

        $this->listen = socket_listen($this->socket, 20);

        if (!$this->listen)
        {
            throw new Exception('Could not listen: ' . socket_strerror(socket_last_error()));

            return false;
        }

        print 'Socket Started' . PHP_EOL;

        $master = $this->socket;
        $this->clients[] = $this->socket;
        $client = false;

        while (true) 
        {
            $current = $this->clients;

            foreach ($current as $socket)
            {
                if ($socket == $master)
                {
                    $except = null;
                    socket_select($current, $write, $except, null);
                    print 'Socket Change' . PHP_EOL;

                    $client = socket_accept($this->socket);
                    if ($client < 0)
                    {
                        print 'Client Failed to connect' . PHP_EOL;
                        continue;
                    }
                    else
                    {
                        print 'Connecting Client' . PHP_EOL;
                        $this->connectClient($socket, $client);
                        $master = null;
                    }
                }
                else
                {
                    if ($client)
                    {
                        if (isset($this->clients[(int) $socket]))
                        {
                            if (!$this->clients[(int) $socket]['handshake'])
                            {
                                $bytes = @socket_recv($client, $data, 2048, MSG_DONTWAIT);

                                if ((int) $bytes == 0)
                                {
                                    continue;
                                }

                                print 'Conducting Handshake: ' . $data . PHP_EOL;

                                if ($this->handshake($client, $data, $socket))
                                {
                                    $this->clients[(int) $socket]['handshake'] = true;
                                }

                            }
                            else if ($this->clients[(int) $socket]['handshake'])
                            {
                                $bytes = @socket_recv($client, $data, 2048, MSG_DONTWAIT);

                                if ($data != '')
                                {
                                    $decoded_data = $this->unmask($data);
                                    socket_write($client, $this->encode('Message: ' . $decoded_data));
                                    print 'Data recieved: ' . $decoded_data . PHP_EOL;
                                    socket_close($socket);
                                }
                            }
                        }
                    }
                }
            }
            sleep(5);
        }

        socket_close($this->socket);
    }

    protected function unmask($payload)
    {
        $length = ord($payload[1]) & 127;

        if ($length == 126) 
        {
            $masks = substr($payload, 4, 4);
            $data = substr($payload, 8);
        }
        else if ($length == 127) 
        {
            $masks = substr($payload, 10, 4);
            $data = substr($payload, 14);
        }
        else 
        {
            $masks = substr($payload, 2, 4);
            $data = substr($payload, 6);
        }

        $text = '';

        for ($i = 0; $i < strlen($data); ++$i) 
        {
            $text .= $data[$i] ^ $masks[$i%4];
        }

        return $text;
    }

    protected function encode($text)
    {
        // 0x1 text frame (FIN + opcode)
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if ($length <= 125)     
        {
            $header = pack('CC', $b1, $length);     
        } elseif ($length > 125 && $length < 65536)    
        {    
            $header = pack('CCS', $b1, 126, $length);   
        } 
        elseif ($length >= 65536)
        {
            $header = pack('CCN', $b1, 127, $length);
        }

        return $header.$text;
    }

    protected function handshake($client, $headers, $socket)
    {
        if (preg_match("/Sec-WebSocket-Version: (.*)\r\n/", $headers, $match))
        {
            $version = $match[1];
        }
        else 
        {
            print 'Invalid Header from Client' . PHP_EOL;
            return false;
        }

        if ($version == 13) 
        {
            // Extract header variables
            if (preg_match("/GET (.*) HTTP/", $headers, $match))
                $root = $match[1];
            if (preg_match("/Host: (.*)\r\n/", $headers, $match))
                $host = $match[1];
            if (preg_match("/Origin: (.*)\r\n/", $headers, $match))
                $origin = $match[1];
            if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $match))
                $key = $match[1];

            $acceptKey = $key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
            $acceptKey = base64_encode(sha1($acceptKey, true));

            $upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
                       "Upgrade: websocket\r\n".
                       "Connection: Upgrade\r\n".
                       "Sec-WebSocket-Accept: $acceptKey".
                       "\r\n\r\n";

            socket_write($client, $upgrade);
            return true;
        }
        else 
        {
            print 'WebSocket version 13 required (the client supports version {$version})' . PHP_EOL;
            return false;
        }
    }

    protected function connectClient($socket, $client)
    {
        $this->clients[(int) $socket]['id'] = uniqid();
        $this->clients[(int) $socket]['socket'] = $socket;
        $this->clients[(int) $socket]['handshake'] = false;

        print 'Client Accepted' . PHP_EOL;

        $this->client_list[(int) $socket] = $client;
    }

    //although these values could be made public we may want to do something to them before sending them
    public function __get($parameter)
    {
        switch ($parameter)
        {
            case 'host':
                return str_replace('ws://', '', $this->host);
                break;
            case 'port':
                return $this->port;
                break;
            default:
                return false;
                break;
        }
    }

    //these variables may need some post processing
    public function __set($parameter, $value)
    {
        switch ($parameter)
        {
            case 'host':
                $this->host = 'ws://' . $value;
                break;
            case 'port':
                $this->port = $value;
                break;
            default:
                return false;
                break;
        }

        return true;
    }
}