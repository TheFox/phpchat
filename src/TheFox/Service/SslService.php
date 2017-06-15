<?php

namespace TheFox\PhpChat\Service;

class SslService
{
    /**
     * @param string $key
     * @return string
     */
    public function getKeyBody($key){
        $key = str_replace("\r", '', $key);
        $key = str_replace("\n", '', $key);
        $key = str_replace('-----BEGIN PUBLIC KEY-----', '', $key);
        $key = str_replace('-----END PUBLIC KEY-----', '', $key);

        return $key;
    }
}
