<?php

namespace TheFox\PhpChat;

class Contact
{
    private $id = 0;

    private $nodeId = '';

    private $userNickname = '';

    private $timeCreated = 0;

    public function __construct()
    {
        $this->timeCreated = time();
    }

    public function __sleep()
    {
        return ['id', 'nodeId', 'userNickname', 'timeCreated'];
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setNodeId($nodeId)
    {
        $this->nodeId = $nodeId;
    }

    public function getNodeId()
    {
        return $this->nodeId;
    }

    public function setUserNickname($userNickname)
    {
        $this->userNickname = $userNickname;
    }

    public function getUserNickname()
    {
        return $this->userNickname;
    }

    public function setTimeCreated($timeCreated)
    {
        $this->timeCreated = $timeCreated;
    }

    public function getTimeCreated()
    {
        return $this->timeCreated;
    }
}
