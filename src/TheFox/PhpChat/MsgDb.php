<?php

namespace TheFox\PhpChat;

use TheFox\Storage\YamlStorage;
use TheFox\Dht\Kademlia\Node;

class MsgDb extends YamlStorage
{
    private $msgs = [];

    public function __construct($filePath = null)
    {
        parent::__construct($filePath);

        $this->data['timeCreated'] = time();
    }

    public function __sleep()
    {
        return ['msgs'];
    }

    public function save()
    {
        $this->data['msgs'] = [];
        foreach ($this->msgs as $msgId => $msg) {

            $this->data['msgs'][$msgId] = [
                'path' => $msg->getFilePath(),
            ];
            $msg->save();
        }

        $rv = parent::save();
        unset($this->data['msgs']);

        return $rv;
    }

    public function load()
    {
        if (parent::load()) {
            if (array_key_exists('msgs', $this->data) && $this->data['msgs']) {
                foreach ($this->data['msgs'] as $msgId => $msgAr) {
                    if (file_exists($msgAr['path'])) {
                        $msg = new Msg($msgAr['path']);
                        $msg->setDatadirBasePath($this->getDatadirBasePath());
                        if ($msg->load()) {
                            $this->msgs[$msg->getId()] = $msg;
                        }
                    }
                }
            }
            unset($this->data['msgs']);

            return true;
        }

        return false;
    }

    public function msgAdd(Msg $msg)
    {
        $filePath = $msg->getFilePath();
        if ($this->getDatadirBasePath() && !$filePath) {
            $filePath = $this->getDatadirBasePath() . '/msg_' . $msg->getId() . '.yml';
        }

        $msg->setFilePath($filePath);
        $msg->setDatadirBasePath($this->getDatadirBasePath());
        $msg->setMsgDb($this);

        $this->msgs[$msg->getId()] = $msg;
        $this->setDataChanged(true);
    }

    public function msgUpdate(Msg $msgNew)
    {
        $rv = false;

        if (isset($this->msgs[$msgNew->getId()])) {
            $msgOld = $this->msgs[$msgNew->getId()];

            if ($msgOld->getVersion() != $msgNew->getVersion()) {
                $msgOld->setVersion($msgNew->getVersion());
                $this->setDataChanged(true);
                $rv = true;
            }
            /*if($msgOld->getId() != $msgNew->getId()){
                $msgOld->setId($msgNew->getId());
                $this->setDataChanged(true);
                $rv = true;
            }*/
            if ($msgOld->getSrcNodeId() != $msgNew->getSrcNodeId()) {
                $msgOld->setSrcNodeId($msgNew->getSrcNodeId());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getSrcSslKeyPub() != $msgNew->getSrcSslKeyPub()) {
                $msgOld->setSrcSslKeyPub($msgNew->getSrcSslKeyPub());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getDstNodeId() != $msgNew->getDstNodeId()) {
                $msgOld->setDstNodeId($msgNew->getDstNodeId());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getDstSslPubKey() != $msgNew->getDstSslPubKey()) {
                $msgOld->setDstSslPubKey($msgNew->getDstSslPubKey());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getSubject() != $msgNew->getSubject()) {
                $msgOld->setSubject($msgNew->getSubject());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getBody() != $msgNew->getBody()) {
                $msgOld->setBody($msgNew->getBody());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getText() != $msgNew->getText()) {
                $msgOld->setText($msgNew->getText());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getPassword() != $msgNew->getPassword()) {
                $msgOld->setPassword($msgNew->getPassword());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getChecksum() != $msgNew->getChecksum()) {
                $msgOld->setChecksum($msgNew->getChecksum());
                $this->setDataChanged(true);
                $rv = true;
            }
            $msgOldSentNodes = $msgOld->getSentNodes();
            $msgNewSentNodes = $msgNew->getSentNodes();
            #if(count($msgOldSentNodes) < count($msgNewSentNodes)){
            if ($msgOldSentNodes != $msgNewSentNodes) {
                $msgOld->setSentNodes(array_unique(array_merge($msgOldSentNodes, $msgNewSentNodes)));
            }
            if ($msgOld->getRelayCount() != $msgNew->getRelayCount()) {
                $msgOld->setRelayCount($msgNew->getRelayCount());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getForwardCycles() != $msgNew->getForwardCycles()) {
                $msgOld->setForwardCycles($msgNew->getForwardCycles());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getEncryptionMode() != $msgNew->getEncryptionMode()) {
                $msgOld->setEncryptionMode($msgNew->getEncryptionMode());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getStatus() != $msgNew->getStatus()) {
                $msgOld->setStatus($msgNew->getStatus());
                $this->setDataChanged(true);
                $rv = true;
            }
            if ($msgOld->getTimeCreated() != $msgNew->getTimeCreated()) {
                $msgOld->setTimeCreated($msgNew->getTimeCreated());
                $this->setDataChanged(true);
                $rv = true;
            }
            /*if($msgOld->getDataChanged() != $msgNew->getDataChanged()){
                $msgOld->setDataChanged($msgNew->getDataChanged());
            }*/
        }

        return $rv;
    }

    public function getMsgs()
    {
        return $this->msgs;
    }

    public function getMsgsCount()
    {
        return count($this->msgs);
    }

    public function getMsgWithNoDstNodeId()
    {
        $rv = [];
        foreach ($this->msgs as $msgId => $msg) {
            if (!$msg->getDstNodeId()) {
                $rv[$msgId] = $msg;
            }
        }
        return $rv;
    }

    public function getUnsentMsgs()
    {
        $rv = [];
        foreach ($this->msgs as $msgId => $msg) {
            if (!$msg->getSentNodes()) {
                $rv[$msgId] = $msg;
            }
        }
        return $rv;
    }

    public function getMsgsForDst(Node $node)
    {
        $rv = [];
        foreach ($this->msgs as $msgId => $msg) {
            if ($msg->getDstNodeId() == $node->getIdHexStr()) {
                $rv[$msgId] = $msg;
            }
        }
        return $rv;
    }

    public function getMsgById($id)
    {
        foreach ($this->msgs as $msgId => $msg) {
            if ($msg->getId() == $id) {
                return $msg;
            }
        }

        return null;
    }
}
