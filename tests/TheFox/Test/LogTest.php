<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Filesystem;
use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;

class LogTest extends PHPUnit_Framework_TestCase
{
    public function providerLog()
    {
        $data = [];

        $expected = '';

        $expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.EMERGENCY: test8' . PHP_EOL . $expected;
        $data[] = [Logger::EMERGENCY, $expected];

        $expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.ALERT: test7' . PHP_EOL . $expected;
        $data[] = [Logger::ALERT, $expected];

        $expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.CRITICAL: test6' . PHP_EOL . $expected;
        $data[] = [Logger::CRITICAL, $expected];

        $expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.ERROR: test5' . PHP_EOL . $expected;
        $data[] = [Logger::ERROR, $expected];

        $expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.WARNING: test4' . PHP_EOL . $expected;
        $data[] = [Logger::WARNING, $expected];

        $expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.NOTICE: test3' . PHP_EOL . $expected;
        $data[] = [Logger::NOTICE, $expected];

        $expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.INFO: test2' . PHP_EOL . $expected;
        $data[] = [Logger::INFO, $expected];

        $expected = '.\d{4}.\d{2}.\d{2} \d{2}:\d{2}:\d{2}.\d{4}. tester.DEBUG: test1' . PHP_EOL . $expected;
        $data[] = [Logger::DEBUG, $expected];

        return $data;
    }

    /**
     * @dataProvider providerLog
     */
    public function testLog($level, $expected)
    {
        $runName = uniqid('', true);
        $fileName = 'testfile_log_' . date('Ymd_His') . '_' . $runName . '.log';

        if (file_exists('test_data/' . $fileName)) {
            $filesystem = new Filesystem();
            $filesystem->remove('test_data/' . $fileName);
        }

        $log = new Logger('tester');
        $log->pushHandler(new StreamHandler('test_data/' . $fileName, $level));

        $log->debug('test1');
        $log->info('test2');
        $log->notice('test3');
        $log->warning('test4');
        $log->error('test5');
        $log->critical('test6');
        $log->alert('test7');
        $log->emergency('test8');

        $this->assertRegExp('/^' . $expected . '/s', file_get_contents('test_data/' . $fileName));
    }

    public function providerLevel()
    {
        return [
            [Logger::DEBUG, 100, 'DEBUG'],
            [Logger::INFO, 200, 'INFO'],
            [Logger::NOTICE, 250, 'NOTICE'],
            [Logger::WARNING, 300, 'WARNING'],
            [Logger::ERROR, 400, 'ERROR'],
            [Logger::CRITICAL, 500, 'CRITICAL'],
            [Logger::ALERT, 550, 'ALERT'],
            [Logger::EMERGENCY, 600, 'EMERGENCY'],
        ];
    }

    /**
     * @dataProvider providerLevel
     */
    public function testLevel($const, $number, $name)
    {
        $this->assertEquals($const, $number);
        $this->assertEquals($name, Logger::getLevelNameByNumber($number));
    }
}
