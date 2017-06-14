<?php

namespace TheFox\PhpChat;

use TheFox\Storage\YamlStorage;
use TheFox\Dht\Kademlia\Node;

class NodesNewDb extends YamlStorage
{
    public function __construct($filePath = null)
    {
        parent::__construct($filePath);

        $this->data['timeCreated'] = time();
        $this->data['nodesId'] = 0;
        $this->data['nodes'] = [];
    }

    public function __sleep()
    {
        return ['data', 'dataChanged'];
    }

    public function nodeAddConnect($uri, $bridgeServer = false)
    {
        if ((string)$uri) {
            $oldId = 0;
            foreach ($this->data['nodes'] as $nodeId => $node) {
                if ($node['uri'] == $uri) {
                    $oldId = $nodeId;
                    break;
                }
            }
            if ($oldId) {
                $this->data['nodes'][$oldId]['insertAttempts']++;
            } else {
                $this->data['nodesId']++;
                $this->data['nodes'][$this->data['nodesId']] = [
                    'type' => 'connect',
                    'id' => null,
                    'uri' => $uri,
                    'bridgeServer' => $bridgeServer,
                    'connectAttempts' => 0,
                    'findAttempts' => 0,
                    'insertAttempts' => 0,
                ];
            }
            $this->setDataChanged(true);
        }
    }

    public function nodeAddFind($id, $bridgeServer = false)
    {
        if ($id != '00000000-0000-4000-8000-000000000000') {
            $oldId = false;
            foreach ($this->data['nodes'] as $nodeId => $node) {
                if ($node['id'] == $id) {
                    $oldId = $nodeId;
                    break;
                }
            }
            if ($oldId) {
                $this->data['nodes'][$oldId]['insertAttempts']++;
            } else {
                $this->data['nodesId']++;
                $this->data['nodes'][$this->data['nodesId']] = [
                    'type' => 'find',
                    'id' => $id,
                    'uri' => null,
                    'bridgeServer' => $bridgeServer,
                    'connectAttempts' => 0,
                    'findAttempts' => 0,
                    'insertAttempts' => 0,
                ];
            }
            $this->setDataChanged(true);
        }
    }

    public function nodeIncConnectAttempt($id)
    {
        if (isset($this->data['nodes'][$id])) {
            $this->data['nodes'][$id]['connectAttempts']++;
            $this->setDataChanged(true);
        }
    }

    public function nodeIncFindAttempt($id)
    {
        if (isset($this->data['nodes'][$id])) {
            $this->data['nodes'][$id]['findAttempts']++;
            $this->setDataChanged(true);
        }
    }

    public function nodeRemove($id)
    {
        if (isset($this->data['nodes'][$id])) {
            unset($this->data['nodes'][$id]);
        }
        $this->setDataChanged(true);
    }

    public function getNodes()
    {
        return $this->data['nodes'];
    }
}
