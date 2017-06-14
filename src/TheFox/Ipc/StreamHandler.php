<?php

namespace TheFox\Ipc;

use RuntimeException;
use Closure;

/**
 * @codeCoverageIgnore
 */
class StreamHandler extends AbstractHandler
{
    public function __construct($ip = '', $port = 0)
    {
        if ($ip && $port) {
            $this->setIp($ip);
            $this->setPort($port);
        }
    }

    public function connect()
    {
        $handle = @stream_socket_client('tcp://' . $this->getIp() . ':' . $this->getPort(), $errno, $errstr, 2);
        if ($handle !== false) {
            $this->setHandle($handle);
            $this->isConnected(true);
            return true;
        } else {
            throw new RuntimeException($errstr, $errno);
        }
    }

    public function listen()
    {
        $handle = @stream_socket_server('tcp://' . $this->getIp() . ':' . $this->getPort(), $errno, $errstr);
        if ($handle !== false) {
            $this->setHandle($handle);
            $this->isListening(true);
            return true;
        } else {
            throw new RuntimeException($errstr, $errno);
        }
    }

    public function run()
    {
        $readHandles = [];
        $writeHandles = [];
        $exceptHandles = [];

        if ($this->isListening()) {
            $readHandles[] = $this->getHandle();
            foreach ($this->getClients() as $client) {
                $readHandles[] = $client['handle'];
            }
        } elseif ($this->isConnected()) {
            $readHandles[] = $this->getHandle();
        }

        if (count($readHandles)) {
            $handlesChangedNum = stream_select($readHandles, $writeHandles, $exceptHandles, 0);

            if ($handlesChangedNum) {
                foreach ($readHandles as $readableHandle) {
                    if ($this->isListening() && $readableHandle == $this->getHandle()) {
                        // Server
                        $handle = @stream_socket_accept($this->getHandle(), 2);
                        $client = $this->clientAdd($handle);
                        $this->execOnClientConnectFunction($client);
                    } else {
                        // Client
                        if (feof($readableHandle)) {
                            if ($this->isListening()) {
                                $client = $this->clientGetByHandle($readableHandle);
                                if ($client) {
                                    stream_socket_shutdown($client['handle'], STREAM_SHUT_RDWR);
                                    $this->clientRemove($client);
                                }
                            } else {
                                stream_socket_shutdown($this->getHandle(), STREAM_SHUT_RDWR);
                                $this->isConnected(false);
                            }
                        } else {
                            $this->handleDataRecv($readableHandle);
                        }
                    }
                }
            }
        }
    }

    public function handleDataSend($handle, $data)
    {
        $rv = @stream_socket_sendto($handle, $data);
    }

    public function handleDataRecv($handle)
    {
        $data = stream_socket_recvfrom($handle, 4096);
        $this->recv($handle, $data);
    }
}
