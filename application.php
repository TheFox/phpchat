#!/usr/bin/env php
<?php

require_once __DIR__.'/bootstrap.php';

use Symfony\Component\Console\Application;

use TheFox\PhpChat\Settings;
use TheFox\Console\Command\KernelCommand;
use TheFox\Console\Command\ConsoleCommand;
use TheFox\Console\Command\CronjobCommand;
use TheFox\Console\Command\InfoCommand;
use TheFox\Console\Command\ImapCommand;
use TheFox\Console\Command\SmtpCommand;


$application = new Application('PHPChat', Settings::VERSION);
foreach(array(
	new KernelCommand(),
	new ConsoleCommand(),
	new CronjobCommand(),
	new InfoCommand(),
	new ImapCommand(),
	new SmtpCommand(),
) as $obj){
	$obj->setSettings($settings);
	$application->add($obj);
}
$application->run();
