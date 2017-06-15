<?php

namespace TheFox\Service;

class BinaryService
{
    /**
     * @todo remove
     * @deprecated Use thefox/utilities Bin::intToBinaryString instead
     * @param $i
     * @return string
     */
    public function intToBinaryString($i)
    {
        $rv = '';
        $rv .= $i & (1 << 7) ? '1' : '0';
        $rv .= $i & (1 << 6) ? '1' : '0';
        $rv .= $i & (1 << 5) ? '1' : '0';
        $rv .= $i & (1 << 4) ? '1' : '0';
        $rv .= $i & (1 << 3) ? '1' : '0';
        $rv .= $i & (1 << 2) ? '1' : '0';
        $rv .= $i & (1 << 1) ? '1' : '0';
        $rv .= $i & (1 << 0) ? '1' : '0';
        return $rv;
    }
}
