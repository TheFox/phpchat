<?php

namespace TheFox\Service;

class CompressionService
{
    /**
     * @param string $data
     * @return string
     */
    public function gzdecode($data)
    {
        if (!function_exists('gzinflate')) {
            throw new \RuntimeException('No gzinflate function found.');
        }
        
        return gzinflate(substr($data, 10, -8));
    }
}
