<?php

require_once __DIR__.'/bootstrap.php';

use TheFox\PhpChat\Kernel;


$kernel = new Kernel();
$kernel->setSettings($settings);
$kernel->run();
