#!/usr/bin/env php
<?php

require_once __DIR__.'/bootstrap.php';

use Symfony\Component\Console\Application;

use TheFox\Console\Command\KernelCommand;
use TheFox\Console\Command\ConsoleCommand;
use TheFox\Console\Command\CronjobCommand;


$application = new Application('PHPChat', '0.3.x-dev');
$application->add(new KernelCommand());
$application->add(new ConsoleCommand());
$application->add(new CronjobCommand());
$application->run();
