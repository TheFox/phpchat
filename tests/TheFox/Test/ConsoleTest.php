<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Finder\Finder;

use TheFox\PhpChat\Settings;
use TheFox\PhpChat\Console;
use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;

class ConsoleTest extends PHPUnit_Framework_TestCase{
	
	public function testBasic(){
		@unlink('test_data/history.yml');
		
		$settings = new Settings();
		$settings->data['datadir'] = 'test_data';
		$settings->data['user']['nickname'] = 'user1';
		$settings->data['console']['history']['enabled'] = false;
		$settings->data['console']['history']['entriesMax'] = 1000;
		$settings->data['console']['history']['saveToFile'] = false;
		
		$consoleLog = new Logger('console');
		#$consoleLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$console = new Console();
		$console->setLog($consoleLog);
		$console->setSettings($settings);
		$console->init();
		$console->handleLine('/help');
		$console->handleLine('/nick');
		$console->run();
		$console->shutdown();
		
		$finder = new Finder();
		$files = $finder->in('test_data')->depth(0)->name('history.yml');
		$this->assertEquals(0, count($files));
		
		@unlink('test_data/history.yml');
	}
	
	public function testHistory1(){
		@unlink('test_data/history.yml');
		
		$settings = new Settings();
		$settings->data['datadir'] = 'test_data';
		$settings->data['user']['nickname'] = 'user1';
		$settings->data['console']['history']['enabled'] = false;
		$settings->data['console']['history']['entriesMax'] = 1000;
		$settings->data['console']['history']['saveToFile'] = false;
		
		$consoleLog = new Logger('console');
		#$consoleLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$console = new Console();
		$console->setLog($consoleLog);
		$console->setSettings($settings);
		$console->init();
		$console->handleLine('/help');
		$console->run();
		$console->shutdown();
		
		$finder = new Finder();
		$files = $finder->in('test_data')->depth(0)->name('history.yml');
		$this->assertEquals(0, count($files));
		
		@unlink('test_data/history.yml');
	}
	
	public function testHistory2(){
		@unlink('test_data/history.yml');
		
		$settings = new Settings();
		$settings->data['datadir'] = 'test_data';
		$settings->data['console']['history']['enabled'] = true;
		$settings->data['console']['history']['entriesMax'] = 1000;
		$settings->data['console']['history']['saveToFile'] = false;
		
		$consoleLog = new Logger('console');
		#$consoleLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$console = new Console();
		$console->setLog($consoleLog);
		$console->setSettings($settings);
		$console->handleLine('/help');
		$console->handleLine('/history');
		$console->run();
		$console->shutdown();
		
		$finder = new Finder();
		$files = $finder->in('test_data')->depth(0)->name('history.yml');
		$this->assertEquals(0, count($files));
		
		@unlink('test_data/history.yml');
	}
	
	public function testHistory3(){
		@unlink('test_data/history.yml');
		
		$settings = new Settings();
		$settings->data['datadir'] = 'test_data';
		$settings->data['console']['history']['enabled'] = true;
		$settings->data['console']['history']['entriesMax'] = 1000;
		$settings->data['console']['history']['saveToFile'] = true;
		
		$consoleLog = new Logger('console');
		#$consoleLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$console = new Console();
		$console->setLog($consoleLog);
		$console->setSettings($settings);
		$console->handleLine('/help');
		$console->handleLine('/history');
		$console->run();
		$console->shutdown();
		
		$finder = new Finder();
		$files = $finder->in('test_data')->depth(0)->name('history.yml');
		$this->assertEquals(1, count($files));
		
		$expect = '';
		$expect .= '- /history'."\n";
		$expect .= '- /help'."\n";

		$this->assertEquals($expect, file_get_contents('test_data/history.yml'));
		
		@unlink('test_data/history.yml');
	}
	
}
