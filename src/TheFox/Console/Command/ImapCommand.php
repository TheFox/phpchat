<?php

namespace TheFox\Console\Command;

use RuntimeException;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Mail\Message;
use Zend\Mail\Headers;

use TheFox\Imap\Server;
use TheFox\Imap\Event;
use TheFox\Ipc\ConnectionClient;
use TheFox\Ipc\StreamHandler as IpcStreamHandler;

class ImapCommand extends BasicCommand{
	
	const LOOP_USLEEP = 10000;
	
	private $server;
	private $settings;
	private $ipcKernelConnection = null;
	private $ipcKernelShutdown = false;
	
	public function getLogfilePath(){
		return 'log/imap.log';
	}
	
	public function getPidfilePath(){
		return 'pid/imap.pid';
	}
	
	protected function configure(){
		$this->setName('imap');
		$this->setDescription('Run the IMAP server.');
		$this->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Run in daemon mode.');
		$this->addOption('address', 'a', InputOption::VALUE_REQUIRED,
			'The address of the network interface. Default = 127.0.0.1');
		$this->addOption('port', 'p', InputOption::VALUE_REQUIRED,
			'The port of the network interface. Default = 21143');
		$this->addOption('shutdown', 's', InputOption::VALUE_NONE, 'Shutdown.');
	}
	
	private function initIpcKernelConnection(){
		usleep(100000); // Let the kernel start up.
		
		$this->ipcKernelConnection = new ConnectionClient();
		$this->ipcKernelConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20002));
		$this->ipcKernelConnection->functionAdd('shutdown', $this, 'ipcKernelShutdown');
		foreach(array(
			'mailAdd'
		) as $functionName){
			$this->ipcKernelConnection->functionAdd($functionName, $this, $functionName);
		}
		
		if(!$this->ipcKernelConnection->connect()){
			throw new RuntimeException('Could not connect to kernel process.');
		}
	}
	
	protected function execute(InputInterface $input, OutputInterface $output){
		$this->executePre($input, $output);
		$this->initIpcKernelConnection();
		
		$this->settings = $this->ipcKernelConnection->execSync('getSettings');
		$this->log->debug('settings: '.(is_object($this->settings) ? 'OK' : 'failed'));
		
		$address = '127.0.0.1';
		if($input->getOption('address')){
			$address = $input->getOption('address');
		}
		
		$port = 21143;
		if($input->getOption('port')){
			$port = (int)$input->getOption('port');
		}
		
		$maildirPath = $this->settings->data['datadir'].'/mailbox';
		$this->log->debug('maildir: '.$maildirPath);
		
		$this->log->info('server start');
		$this->server = new Server($address, $port);
		$this->server->setLog($this->log);
		
		#$eventMailAdd = new Event(Event::TRIGGER_MAIL_ADD, $this, 'imapMailAdd');
		#$this->server->eventAdd($eventMailAdd);
		
		try{
			$this->server->init();
		}
		catch(Exception $e){
			$this->log->error('init: '.$e->getMessage());
			exit(1);
		}
		
		try{
			$this->server->storageAddMaildir($maildirPath);
		}
		catch(Exception $e){
			$this->log->error('storage: '.$e->getMessage());
			exit(1);
		}
		
		try{
			$this->server->listen();
		}
		catch(Exception $e){
			$this->log->error('listen: '.$e->getMessage());
			exit(1);
		}
		
		while(!$this->getExit()){
			#$this->log->debug('run');
			
			if(!$this->ipcKernelConnection->run()){
				$this->log->info('Connection to kernel process end unexpected.');
				$this->setExit(1);
			}
			
			try{
				$this->server->run();
			}
			catch(Exception $e){
				$this->log->error('run: '.$e->getMessage());
				exit(1);
			}
			
			usleep(static::LOOP_USLEEP);
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
		
		if($this->server){
			$this->server->setExit($this->exit);
			$this->server->shutdown();
		}
		if($this->exit >= 2){
			exit(1);
		}
	}
	
	public function ipcKernelShutdown(){
		$this->log->info('kernel shutdown');
		
		$this->log->info('Connection to kernel process closed.');
		$this->setExit(1);
		$this->ipcKernelShutdown = true;
		
		if($this->server){
			$this->server->setExit($this->exit);
			$this->server->shutdown();
		}
		
		return null;
	}
	
	/*public function imapMailAdd($event, $mail){
		$this->log->info('mail add');
	}*/
	
	public function mailAdd($version, $id, $srcNodeId, $srcUserNickname, $dstNodeId, $subject, $text,
		$checksum, $relayCount, $encryptionMode, $status, $timeCreated, $timeReceived){
		$this->log->info('mail add: '.$id);
		$this->log->info('subject: '.$subject);
		$this->log->info('from: '.$srcNodeId);
		$this->log->info('nick: '.$srcUserNickname);
		
		$headers = new Headers();
		$headers->addHeaderLine('Date', date('r', $timeReceived));
		$headers->addHeaderLine('X-Version', $version);
		$headers->addHeaderLine('X-Id', $id);
		$headers->addHeaderLine('X-Checksum', $checksum);
		$headers->addHeaderLine('X-RelayCount', $relayCount);
		$headers->addHeaderLine('X-EncrptionMode', $encryptionMode);
		$headers->addHeaderLine('X-Status', $status);
		$headers->addHeaderLine('X-TimeCreated', $timeCreated);
		$headers->addHeaderLine('X-TimeReceived', $timeReceived);
		
		$message = new Message();
		$message->setHeaders($headers);
		$message->addFrom($srcNodeId.'@phpchat.fox21.at', $srcUserNickname);
		$message->addTo($dstNodeId.'@phpchat.fox21.at');
		$message->setSubject($subject);
		$message->setBody($text);
		
		$this->server->mailAdd($message);
	}
	
}
