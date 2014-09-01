<?php

use Symfony\Component\Finder\Finder;

use TheFox\PhpChat\Settings;
use TheFox\PhpChat\Console;

class ConsoleTest extends PHPUnit_Framework_TestCase{
	
	public function testBasic(){
		@unlink('tests/history.yml');
		
		$settings = new Settings();
		$settings->data['datadir'] = 'tests';
		$settings->data['user']['nickname'] = 'user1';
		$settings->data['console']['history']['enabled'] = false;
		$settings->data['console']['history']['entriesMax'] = 1000;
		$settings->data['console']['history']['saveToFile'] = false;
		
		$console = new Console();
		$console->setDebug(true);
		$console->setSettings($settings);
		$console->init();
		$console->handleLine('/help');
		$console->handleLine('/nick');
		$console->run();
		$console->shutdown();
		
		$finder = new Finder();
		$files = $finder->in('tests')->depth(0)->name('history.yml');
		$this->assertEquals(0, count($files));
		
		@unlink('tests/history.yml');
	}
	
	public function testHistory1(){
		@unlink('tests/history.yml');
		
		$settings = new Settings();
		$settings->data['datadir'] = 'tests';
		$settings->data['user']['nickname'] = 'user1';
		$settings->data['console']['history']['enabled'] = false;
		$settings->data['console']['history']['entriesMax'] = 1000;
		$settings->data['console']['history']['saveToFile'] = false;
		
		$console = new Console();
		$console->setDebug(true);
		$console->setSettings($settings);
		$console->init();
		$console->handleLine('/help');
		$console->run();
		$console->shutdown();
		
		$finder = new Finder();
		$files = $finder->in('tests')->depth(0)->name('history.yml');
		$this->assertEquals(0, count($files));
		
		@unlink('tests/history.yml');
	}
	
	public function testHistory2(){
		@unlink('tests/history.yml');
		
		$settings = new Settings();
		$settings->data['datadir'] = 'tests';
		$settings->data['console']['history']['enabled'] = true;
		$settings->data['console']['history']['entriesMax'] = 1000;
		$settings->data['console']['history']['saveToFile'] = false;
		
		$console = new Console();
		$console->setDebug(true);
		$console->setSettings($settings);
		$console->handleLine('/help');
		$console->handleLine('/history');
		$console->run();
		$console->shutdown();
		
		$finder = new Finder();
		$files = $finder->in('tests')->depth(0)->name('history.yml');
		$this->assertEquals(0, count($files));
		
		@unlink('tests/history.yml');
	}
	
	public function testHistory3(){
		@unlink('tests/history.yml');
		
		$settings = new Settings();
		$settings->data['datadir'] = 'tests';
		$settings->data['console']['history']['enabled'] = true;
		$settings->data['console']['history']['entriesMax'] = 1000;
		$settings->data['console']['history']['saveToFile'] = true;
		
		$console = new Console();
		$console->setDebug(true);
		$console->setSettings($settings);
		$console->handleLine('/help');
		$console->handleLine('/history');
		$console->run();
		$console->shutdown();
		
		$finder = new Finder();
		$files = $finder->in('tests')->depth(0)->name('history.yml');
		$this->assertEquals(1, count($files));
		
		$expect = '';
		$expect .= '- /history'."\n";
		$expect .= '- /help'."\n";

		$this->assertEquals($expect, file_get_contents('tests/history.yml'));
		
		@unlink('tests/history.yml');
	}
	
}
