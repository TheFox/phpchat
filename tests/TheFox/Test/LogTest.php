<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Filesystem;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;

class LogTest extends PHPUnit_Framework_TestCase{
	
	public function providerLog(){
		$data = array();
		
		$expected = '';
		
		$expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.EMERGENCY: test8'.PHP_EOL.$expected;
		$data[] = array(Logger::EMERGENCY, $expected);
		
		$expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.ALERT: test7'.PHP_EOL.$expected;
		$data[] = array(Logger::ALERT, $expected);
		
		$expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.CRITICAL: test6'.PHP_EOL.$expected;
		$data[] = array(Logger::CRITICAL, $expected);
		
		$expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.ERROR: test5'.PHP_EOL.$expected;
		$data[] = array(Logger::ERROR, $expected);
		
		$expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.WARNING: test4'.PHP_EOL.$expected;
		$data[] = array(Logger::WARNING, $expected);
		
		$expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.NOTICE: test3'.PHP_EOL.$expected;
		$data[] = array(Logger::NOTICE, $expected);
		
		$expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.INFO: test2'.PHP_EOL.$expected;
		$data[] = array(Logger::INFO, $expected);
		
		$expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.DEBUG: test1'.PHP_EOL.$expected;
		$data[] = array(Logger::DEBUG, $expected);
		
		return $data;
	}
	
	/**
     * @dataProvider providerLog
     */
	public function testLog($level, $expected){
		$runName = uniqid('', true);
		$fileName = 'testfile_log_'.date('Ymd_His').'_'.$runName.'.log';
		
		if(file_exists('test_data/'.$fileName)){
			$filesystem = new Filesystem();
			$filesystem->remove('test_data/'.$fileName);
		}
		
		$log = new Logger('tester');
		$log->pushHandler(new StreamHandler('test_data/'.$fileName, $level));
		
		$log->debug('test1');
		$log->info('test2');
		$log->notice('test3');
		$log->warning('test4');
		$log->error('test5');
		$log->critical('test6');
		$log->alert('test7');
		$log->emergency('test8');
		
		$this->assertRegExp('/^'.$expected.'/s', file_get_contents('test_data/'.$fileName));
	}
	
	public function providerLevel(){
		return array(
			array(Logger::DEBUG, 100, 'DEBUG'),
			array(Logger::INFO, 200, 'INFO'),
			array(Logger::NOTICE, 250, 'NOTICE'),
			array(Logger::WARNING, 300, 'WARNING'),
			array(Logger::ERROR, 400, 'ERROR'),
			array(Logger::CRITICAL, 500, 'CRITICAL'),
			array(Logger::ALERT, 550, 'ALERT'),
			array(Logger::EMERGENCY, 600, 'EMERGENCY'),
		);
	}
	
	/**
     * @dataProvider providerLevel
     */
	public function testLevel($const, $number, $name){
		$this->assertEquals($const, $number);
		$this->assertEquals($name, Logger::getLevelNameByNumber($number));
	}
	
}
