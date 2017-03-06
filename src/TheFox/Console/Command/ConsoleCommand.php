<?php

namespace TheFox\Console\Command;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TheFox\PhpChat\Console;

class ConsoleCommand extends BasicCommand{
	
	private $console;
	
	public function getLogfilePath(){
		return 'log/console.log';
	}
	
	public function getPidfilePath(){
		return 'pid/console.pid';
	}
	
	protected function configure(){
		$this->setName('console');
		$this->setDescription('Run the Console.');
		$this->addOption('shutdown', 's', InputOption::VALUE_NONE, 'Shutdown.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output){
		$this->executePre($input, $output);
		
		try{
			$this->log->info('console start');
			$this->console = new Console();
			$this->console->setLog($this->log);
		}
		catch(Exception $e){
			$this->log->error('console create: '.$e->getMessage());
			exit(1);
		}
		
		try{
			$this->console->init();
		}
		catch(Exception $e){
			$log->error('init: '.$e->getMessage());
			exit(1);
		}

		try{
			$this->console->loop();
		}
		catch(Exception $e){
			$log->error('loop: '.$e->getMessage());
			exit(1);
		}
		
		$this->executePost();
		$this->log->info('exit');
	}
	
	public function signalHandler($signal){
		$this->exit++;
		
		switch($signal){
			case SIGTERM:
				$this->log->notice('signal: SIGTERM');
				break;
			case SIGINT:
				print PHP_EOL;
				$this->log->notice('signal: SIGINT');
				break;
			case SIGHUP:
				$this->log->notice('signal: SIGHUP');
				break;
			case SIGQUIT:
				$this->log->notice('signal: SIGQUIT');
				break;
			case SIGKILL:
				$this->log->notice('signal: SIGKILL');
				break;
			case SIGUSR1:
				$this->log->notice('signal: SIGUSR1');
				break;
			default:
				$this->log->notice('signal: N/A');
		}
		
		$this->log->notice('main abort ['.$this->exit.']');
		
		if($this->console){
			$this->console->setExit($this->exit);
		}
		if($this->exit >= 2){
			exit(1);
		}
	}
	
}
