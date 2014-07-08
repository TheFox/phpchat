#!/usr/bin/env php
<?php

require_once __DIR__.'/bootstrap.php';

use Symfony\Component\Console\Application;

use TheFox\Console\Command\KernelCommand;
use TheFox\Console\Command\ConsoleCommand;
use TheFox\Console\Command\CronjobCommand;
use TheFox\Console\Command\InfoCommand;


$application = new Application('PHPChat', '0.3.x-dev');
foreach(array(
	new KernelCommand(),
	new ConsoleCommand(),
	new CronjobCommand(),
	new InfoCommand(),
) as $obj){
	$obj->setSettings($settings);
	$application->add($obj);
}
$application->run();
