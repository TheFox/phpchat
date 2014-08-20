<?php

namespace TheFox\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use TheFox\PhpChat\Cronjob;

class CronjobCommand extends BasicCommand{
	
	private $cronjob;
	
	public function getPidfilePath(){
		return 'pid/cronjob.pid';
	}
	
	protected function configure(){
		$this->setName('cronjob');
		$this->setDescription('Run the Cronjob.');
		$this->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Run in daemon mode.');
		$this->addOption('cycle', 'c', InputOption::VALUE_NONE, 'Run only one cycle.');
		$this->addOption('msg', 'm', InputOption::VALUE_NONE, 'Run only one cycle with Msgs.');
		$this->addOption('nodes', null, InputOption::VALUE_NONE, 'Run only one cycle with Nodes New DB.');
		$this->addOption('shutdown', 's', InputOption::VALUE_NONE, 'Shutdown.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output){
		$this->executePre($input, $output);
		
		$this->log->info('cronjob start');
		$this->cronjob = new Cronjob();

		try{
			$this->cronjob->init();
		}
		catch(Exception $e){
			$log->error('init: '.$e->getMessage());
			exit(1);
		}
		
		if($input->hasOption('cycle') && $input->getOption('cycle')){
			$this->cronjob->cycle();
		}
		elseif($input->hasOption('msg') && $input->getOption('msg')){
			$this->cronjob->cycleMsg();
		}
		elseif($input->hasOption('nodes') && $input->getOption('nodes')){
			$this->cronjob->cycleNodesNew();
		}
		else{
			try{
				$this->cronjob->loop();
			}
			catch(Exception $e){
				$log->error('loop: '.$e->getMessage());
				exit(1);
			}
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
				print "\n";
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
		
		if($this->cronjob){
			$this->cronjob->setExit($this->exit);
		}
		if($this->exit >= 2){
			exit(1);
		}
	}
	
}
