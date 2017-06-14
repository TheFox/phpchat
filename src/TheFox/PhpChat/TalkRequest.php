<?php

namespace TheFox\PhpChat;

class TalkRequest
{
    private $id = 0;

    private $rid = 0;

    private $client = null;

    private $userNickname = '';

    private $status = 0;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setRid($rid)
    {
        $this->rid = $rid;
    }

    public function getRid()
    {
        return $this->rid;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setUserNickname($userNickname)
    {
        $this->userNickname = $userNickname;
    }

    public function getUserNickname()
    {
        return $this->userNickname;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }
}
