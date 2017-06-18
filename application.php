#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use TheFox\PhpChat\PhpChat;
use TheFox\Console\Command\KernelCommand;
use TheFox\Console\Command\ConsoleCommand;
use TheFox\Console\Command\CronjobCommand;
use TheFox\Console\Command\InfoCommand;
use TheFox\Console\Command\ImapCommand;
use TheFox\Console\Command\SmtpCommand;

// This command cost me a whole day. Use it even in signalHandlerSetup().
declare(ticks = 1);

if(@date_default_timezone_get() == 'UTC') {date_default_timezone_set('UTC');}

chdir(__DIR__);

// @todo remove PHP_EOL_LEN. really needed?
define('PHP_EOL_LEN', strlen(PHP_EOL), true);

$application = new Application(PhpChat::NAME, PhpChat::VERSION);
foreach(array(
	new KernelCommand(),
	new ConsoleCommand(),
	new CronjobCommand(),
	new InfoCommand(),
	new ImapCommand(),
	new SmtpCommand(),
) as $command){
	$command->setSettings($settings);
	$application->add($command);
}
$application->run();
