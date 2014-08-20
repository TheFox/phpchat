<?php

namespace TheFox\Console\Command;

use RuntimeException;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Colors\Color;

use TheFox\PhpChat\PhpChat;
use TheFox\PhpChat\Console;
use TheFox\Dht\Kademlia\Node;
use TheFox\Ipc\ConnectionClient;
use TheFox\Ipc\StreamHandler as IpcStreamHandler;
use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;

class InfoCommand extends BasicCommand{
	
	const LOOP_USLEEP = 50000;
	
	private $ipcKernelConnection = null;
	private $ipcKernelShutdown = false;
	
	public function getLogfilePath(){
		return 'log/info.log';
	}
	
	public function getPidfilePath(){
		return 'pid/info.pid';
	}
	
	protected function configure(){
		$this->setName('info');
		$this->setDescription('Show infos about this node.');
		$this->addOption('name', null, InputOption::VALUE_NONE, 'Print the name this application.');
		$this->addOption('name_lc', null, InputOption::VALUE_NONE, 'Print the lower-case name this application.');
		$this->addOption('version_number', null, InputOption::VALUE_NONE, 'Print the version this application.');
		$this->addOption('connections', 'c', InputOption::VALUE_NONE, 'Print connections infos.');
	}
	
	private function initIpcKernelConnection(){
		usleep(100000); // Let the kernel start up.
		
		$this->ipcKernelConnection = new ConnectionClient();
		$this->ipcKernelConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20004));
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
		#$this->executePre($input, $output);
		
		if($input->hasOption('name') && $input->getOption('name')){
			print PhpChat::NAME;
		}
		elseif($input->hasOption('name_lc') && $input->getOption('name_lc')){
			print strtolower(PhpChat::NAME);
		}
		elseif($input->hasOption('version_number') && $input->getOption('version_number')){
			print PhpChat::VERSION;
		}
		elseif($input->hasOption('connections') && $input->getOption('connections')){
			print 'Live Connections'.PHP_EOL.PHP_EOL;
			
			$this->executePre($input, $output);
			
			$this->log = new Logger($this->getName());
			#$this->log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
			$this->log->pushHandler(new StreamHandler($this->getLogfilePath(), Logger::DEBUG));
			
			$this->initIpcKernelConnection();
			
			/*
			print 'get settings'."\n";
			$this->log->info('get settings');
			$settings = $this->ipcKernelConnection->execSync('getSettings');
			print 'get settings: done'."\n";
			$this->log->info('get settings: done');
			*/
			
			$color = new Color();
			
			$startTime = time();
			$time = time();
			$seconds = 0;
			$oldClients = array();
			$tcols = (int)exec('tput cols');
			$tlines = (int)exec('tput lines');
			
			#print 'cols: '.$tcols.PHP_EOL;
			#print 'lines: '.$tlines.PHP_EOL;
			
			#print 'Clients: N/A'.PHP_EOL;
			print ' Clients: '."\r";
			
			while(!$this->getExit()){
				#$this->log->debug('run');
				
				if(!$this->ipcKernelConnection->run()){
					$this->log->info('Connection to kernel process end unexpected.');
					$this->setExit(1);
				}
				
				$update = false;
				if($time != time()){
					$time = time();
					$seconds++;
					
					$tcols = (int)exec('tput cols');
					$tlines = (int)exec('tput lines');
					
					$update = true;
				}
				
				$update = true;
				
				if($update){
					$newClients = $this->ipcKernelConnection->execSync('serverClientsInfo');
					#ve($newClients);
					
					$clientsChanged = 0;
					foreach($newClients as $newClientId => $newClient){
						if(isset($oldClients[$newClientId])){
							$oldClient = $oldClients[$newClientId];
							
							$changed = false;
							if($oldClient['hasId'] != $newClient['hasId']){
								$this->log->debug('update '.$newClientId.': hasId='.(int)$newClient['hasId']);
								$oldClients[$newClientId]['hasId'] = $newClient['hasId'];
								$oldClients[$newClientId]['lastUpdate'] = time();
							}
							if($oldClient['hasTalkRequest'] != $newClient['hasTalkRequest']){
								$this->log->debug('update '.$newClientId.': hasTalkRequest='.(int)$newClient['hasTalkRequest']);
								$oldClients[$newClientId]['hasTalkRequest'] = $newClient['hasTalkRequest'];
								$oldClients[$newClientId]['lastUpdate'] = time();
							}
							if($oldClient['hasTalk'] != $newClient['hasTalk']){
								$this->log->debug('update '.$newClientId.': hasTalk='.(int)$newClient['hasTalk']);
								$oldClients[$newClientId]['hasTalk'] = $newClient['hasTalk'];
								$oldClients[$newClientId]['lastUpdate'] = time();
							}
							if($oldClient['hasTalkClose'] != $newClient['hasTalkClose']){
								$this->log->debug('update '.$newClientId.': hasTalkClose='.(int)$newClient['hasTalkClose']);
								$oldClients[$newClientId]['hasTalkClose'] = $newClient['hasTalkClose'];
								$oldClients[$newClientId]['lastUpdate'] = time();
							}
							if($oldClient['hasShutdown'] != $newClient['hasShutdown']){
								$this->log->debug('update '.$newClientId.': hasShutdown='.(int)$newClient['hasShutdown']);
								$oldClients[$newClientId]['hasShutdown'] = $newClient['hasShutdown'];
								$oldClients[$newClientId]['lastUpdate'] = time();
							}
							if($oldClient['isChannelPeer'] != $newClient['isChannelPeer']){
								$this->log->debug('update '.$newClientId.': isChannelPeer='.(int)$newClient['isChannelPeer']);
								$oldClients[$newClientId]['isChannelPeer'] = $newClient['isChannelPeer'];
								$oldClients[$newClientId]['lastUpdate'] = time();
							}
							if($oldClient['isChannelLocal'] != $newClient['isChannelLocal']){
								$this->log->debug('update '.$newClientId.': isChannelLocal='.(int)$newClient['isChannelLocal']);
								$oldClients[$newClientId]['isChannelLocal'] = $newClient['isChannelLocal'];
								$oldClients[$newClientId]['lastUpdate'] = time();
							}
							
							if($changed){
								$clientsChanged++;
							}
						}
						else{
							$this->log->debug('new client: '.$newClientId);
							$oldClients[$newClientId] = array(
								'lastUpdate' => time(),
								
								'hasId' => $newClient['hasId'],
								'hasTalkRequest' => $newClient['hasTalkRequest'],
								'hasTalk' => $newClient['hasTalk'],
								'hasTalkClose' => $newClient['hasTalkClose'],
								'hasShutdown' => $newClient['hasShutdown'],
								'isChannelPeer' => $newClient['isChannelPeer'],
								'isChannelLocal' => $newClient['isChannelLocal'],
								
								'shutdown' => 0,
								'status' => '.',
							);
						}
					}
					
					foreach($oldClients as $oldClientId => $oldClient){
						if(!isset($newClients[$oldClientId]) || $oldClient['hasShutdown']){
							if(!$oldClients[$oldClientId]['shutdown']){
								$this->log->debug('update '.$oldClientId.': shutdown=1');
								$oldClients[$oldClientId]['shutdown'] = time();
								$oldClients[$oldClientId]['lastUpdate'] = time();
							}
						}
						
						if($oldClient['isChannelPeer'] || $oldClient['isChannelLocal']){
							$oldClients[$oldClientId]['status'] = 'c';
						}
						if($oldClient['hasTalkRequest']){
							$oldClients[$oldClientId]['status'] = 't';
						}
						if($oldClient['hasTalk']){
							$oldClients[$oldClientId]['status'] = 'T';
						}
						if($oldClient['hasTalkClose']){
							$oldClients[$oldClientId]['status'] = 'X';
						}
						if($oldClient['shutdown']){
							#$this->log->debug('client '.$oldClientId.' has shutdown: '.(time() - $oldClient['shutdown']));
							
							$oldClients[$oldClientId]['status'] = 'x';
							if($oldClient['shutdown'] <= time() - 5){
								unset($oldClients[$oldClientId]);
							}
						}
					}
					
					$oldClientsLen = count($oldClients);
					
					Console::cursorJumpToColumn(11);
					print $oldClientsLen;
					Console::lineClearRight();
					print PHP_EOL.' ';
					
					$line = 0;
					$lineClients = 0;
					
					foreach($oldClients as $oldClientId => $oldClient){
						#$this->log->debug('client '.$oldClientId.' print: '.(time() - $oldClient['lastUpdate']));
						
						$output = $oldClient['status'];
						if($oldClient['lastUpdate'] >= time() - 2){
							$output = $color($oldClient['status'])->bg_green;
						}
						elseif($oldClient['lastUpdate'] <= time() - 60){
							#$output = $color($oldClient['status'])->dark;
							$output = $color($oldClient['status'])->bg_blue;
						}
						
						print $output;
						
						$lineClients++;
						if($lineClients >= $tcols - 2){
							$lineClients = 0;
							$line++;
							print PHP_EOL.' ';
						}
					}
					
					Console::screenClearToBottom();
					print PHP_EOL;
					Console::cursorUp($line + 2);
					Console::cursorJumpToColumn(1);
					
					#print 'run /'.$time.'/ /'.$seconds.'/ /'.$clientLines.'/ /'.$tcols.'/ /'.$tlines.'/'."\r";
				}
				
				usleep(static::LOOP_USLEEP);
			}
			
			#print "\n\n\n\n";
			
			Console::screenClearToBottom();
			
			$this->executePost();
			$this->log->info('exit');
		}
		else{
			$settings = $this->getSettings();
			
			$localNode = new Node();
			$localNode->setIdHexStr($settings->data['node']['id']);
			$localNode->setUri('tcp://'.$settings->data['node']['ip'].':'.$settings->data['node']['port']);
			$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));
			
			print '--------'.PHP_EOL;
			print 'Informations about your node:'.PHP_EOL;
			print '   Version: '.PhpChat::NAME.'/'.PhpChat::VERSION.' ('.PhpChat::RELEASE.')'.PHP_EOL;
			print '   ID: '.$localNode->getIdHexStr().PHP_EOL;
			print '   Public key fingerprint: '.$localNode->getSslKeyPubFingerprint().PHP_EOL;
			print '   Last public IP: '.$settings->data['node']['ipPub'].PHP_EOL;
			print '   Listen IP:Port: '.$settings->data['node']['ip'].':'.$settings->data['node']['port'].PHP_EOL;
			print '   Nickname: '.$settings->data['user']['nickname'].PHP_EOL;
			print '   SSL version: '.OPENSSL_VERSION_TEXT.PHP_EOL;
			print '--------'.PHP_EOL;
			print '   Pub Key Base64:'.PHP_EOL.base64_encode($localNode->getSslKeyPub()).PHP_EOL;
			print '--------'.PHP_EOL;
		}
		
		
		
		#$this->executePost();
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
		
		if($this->exit >= 2){
			exit(1);
		}
	}
	
	public function ipcKernelShutdown(){
		$this->log->info('kernel shutdown');
		
		$this->log->info('Connection to kernel process closed.');
		$this->setExit(1);
		$this->ipcKernelShutdown = true;
	}
	
}
