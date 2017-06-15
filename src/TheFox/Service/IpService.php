<?php

namespace TheFox\Service;

class IpService
{
    /**
     * @param string $data
     * @return int
     */
    public function isIp($data)
    {
        return preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $data) == 1;
    }
}
