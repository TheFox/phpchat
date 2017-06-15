<?php

namespace TheFox\PhpChat\Entity;

use TheFox\Storage\YamlStorage;

class Settings extends YamlStorage
{
    const USER_NICKNAME_LEN_MAX = 256;

    public function __construct($filePath = null)
    {
        parent::__construct($filePath);

        $this->data['version'] = PhpChat::VERSION;
        $this->data['release'] = PhpChat::RELEASE;
        $this->data['datadir'] = 'data';
        $this->data['firstRun'] = true;
        $this->data['timeCreated'] = time();

        $this->data['node'] = [];
        $this->data['node']['uriLocal'] = 'tcp://0.0.0.0:25000';
        $this->data['node']['uriPub'] = '';
        $this->data['node']['id'] = '';

        $this->data['node']['sslKeyPrvPass'] = '';
        $this->data['node']['sslKeyPrvPath'] = 'id_rsa.prv';
        $this->data['node']['sslKeyPubPath'] = 'id_rsa.pub';

        $this->data['node']['traffic'] = [];
        $this->data['node']['traffic']['in'] = '0';
        $this->data['node']['traffic']['out'] = '0';

        $this->data['node']['bridge'] = [];
        $this->data['node']['bridge']['server'] = [];
        $this->data['node']['bridge']['server']['enabled'] = false;
        $this->data['node']['bridge']['client'] = [];
        $this->data['node']['bridge']['client']['enabled'] = false;

        $this->data['user'] = [];
        $this->data['user']['nickname'] = '';

        $this->data['console'] = [];
        $this->data['console']['history'] = [];
        $this->data['console']['history']['enabled'] = true;
        $this->data['console']['history']['entriesMax'] = 1000;
        $this->data['console']['history']['saveToFile'] = true;

        $this->load();

        if ($this->isLoaded()) {
            $this->data['version'] = PhpChat::VERSION;
            $this->data['release'] = PhpChat::RELEASE;
        } else {
            $this->data['user']['nickname'] = 'user_' . substr(md5(time()), 0, 4);

            $this->setDataChanged(true);
            $this->save();
        }
    }

    public function __sleep()
    {
        return ['data', 'dataChanged'];
    }
}
