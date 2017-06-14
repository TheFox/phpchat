<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use TheFox\PhpChat\HttpUri;

class HttpUriTest extends PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $uri1 = new HttpUri('http://192.168.241.24:25000');

        $uri2 = unserialize(serialize($uri1));

        #\Doctrine\Common\Util\Debug::dump($uri2);

        $this->assertEquals('http://192.168.241.24:25000/', (string)$uri2);
    }
}
