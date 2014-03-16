<?php

namespace TheFox\PhpChat;

use RuntimeException;
use DateTime;
use DateTimeZone;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\Ipc\ConnectionClient;
use TheFox\Ipc\StreamHandler as IpcStreamHandler;

class Console extends Thread{
	
	const LOOP_USLEEP = 100000;
	const CHAR_ESCAPE = "\033";
	const CHAR_BACKSPACE = "\177";
	const CHAR_EOF = "\004";
	
	private $log = null;
	private $ipcKernelConnection = null;
	private $ipcKernelShutdown = false;
	private $ps1 = 'phpchat:> ';
	private $tcols = 0;
	private $tlines = 0;
	private $stdin = null;
	private $msgStack = array();
	private $buffer = '';
	private $modeChannel = false;
	private $modeChannelClient = null;
	private $userNickname = '';
	private $talkRequestsId = 0;
	private $talkRequests = array();
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log = new Logger('console');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::INFO));
		$this->log->pushHandler(new LoggerStreamHandler('log/console.log', Logger::DEBUG));
		
		$this->log->info('start');
		
		$this->log->debug('tput setup');
		$this->tcols = (int)exec('tput cols');
		$this->tlines = (int)exec('tput lines');
		$this->log->debug('cols = '.$this->tcols.', lines = '.$this->tlines);
	}
	
	private function getLog(){
		return $this->log;
	}
	
	private function setIpcKernelConnection($ipcKernelConnection){
		$this->ipcKernelConnection = $ipcKernelConnection;
	}
	
	private function getIpcKernelConnection(){
		return $this->ipcKernelConnection;
	}
	
	private function setPs1($ps1){
		$this->ps1 = $ps1;
	}
	
	private function getPs1(){
		return $this->ps1;
	}
	
	public function setModeChannel($modeChannel){
		$this->modeChannel = $modeChannel;
	}
	
	private function getModeChannel(){
		return $this->modeChannel;
	}
	
	public function setModeChannelClient(Client $modeChannelClient){
		$this->modeChannelClient = $modeChannelClient;
	}
	
	private function getModeChannelClient(){
		return $this->modeChannelClient;
	}
	
	public function printPs1($debug = ''){
		if($this->getModeChannel()){
			#print $debug.$this->settings['phpchat']['user']['nickname'].':> _'.$this->buffer.'_'; # TODO
			print $this->userNickname.':> '.$this->buffer;
		}
		else{
			#print $debug.' '.$this->getPs1().' _'.$this->buffer.'_'; # TODO
			print $this->getPs1().$this->buffer;
		}
	}
	
	private function lineClear(){
		#$this->log->debug('line clear');
		print "\r".Console::CHAR_ESCAPE.'[K';
	}
	
	private function linePrint($text){
		$this->log->debug('line print "'.$text.'"');
		print $text.PHP_EOL;
	}
	
	public function msgAdd($text){
		$this->msgStack[] = array(
			'text' => $text,
		);
	}
	
	private function getDate(){
		$dt = new DateTime('now', new DateTimeZone('UTC'));
		return $dt->format('H:i:s');
	}
	
	public function init(){
		$this->setIpcKernelConnection(new ConnectionClient());
		$this->getIpcKernelConnection()->setHandler(new IpcStreamHandler('127.0.0.1', 20000));
		$this->getIpcKernelConnection()->functionAdd('shutdown', $this, 'ipcKernelShutdown');
		$this->getIpcKernelConnection()->functionAdd('msgAdd', $this, 'msgAdd');
		$this->getIpcKernelConnection()->functionAdd('talkRequestAdd', $this, 'talkRequestAdd');
		$this->getIpcKernelConnection()->functionAdd('talkMsgAdd', $this, 'talkMsgAdd');
		$this->getIpcKernelConnection()->functionAdd('setModeChannel', $this, 'setModeChannel');
		$this->getIpcKernelConnection()->functionAdd('setModeChannelClient', $this, 'setModeChannelClient');
		
		if(!$this->getIpcKernelConnection()->connect()){
			throw new RuntimeException('Could not connect to kernel process.');
		}
		
		$this->userNickname = $this->getIpcKernelConnection()->execSync('getSettingsUserNickname');
		
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log->debug('tty setup');
		#system('stty -icanon && echo icanon ok');
		system('stty -icanon');
		
		$this->stdin = fopen('php://stdin', 'r');
		stream_set_blocking($this->stdin, 0);
		
		print PHP_EOL."Type '/help' for help.".PHP_EOL;
		
		$this->printPs1('init');
		$this->msgAdd('start');
		
		return true;
	}
	
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(!$this->getIpcKernelConnection()){
			throw new RuntimeException('You must first run init().');
		}
		
		while(!$this->getExit()){
			$this->readStdin();
			$this->printMsgStack();
			
			if(!$this->getIpcKernelConnection()->run()){
				$this->log->info('Connection to kernel process end unexpected.');
				$this->setExit(1);
			}
			
			#print __CLASS__.'->'.__FUNCTION__.': '.$this->getExit()."\n";
			
			usleep(static::LOOP_USLEEP);
		}
		
		$this->shutdown();
	}
	
	private function readStdin(){
		$read = array($this->stdin);
		$write = array();
		$except = array();
		$streamsChanged = stream_select($read, $write, $except, 0);
		if($streamsChanged){
			
			#$this->log->debug('fgets');
			$buffer = fgets($this->stdin, 1024);
			if($buffer === false){
				$this->log->error('buffer is false');
			}
			
			if($buffer !== false){
				$bufferLen = strlen($buffer);
				
				#$this->log->debug('buffer: '.ord($buffer[0]));
				#$bufferHex = ''; for($n = 0; $n < $bufferLen; $n++){ $bufferHex .= sprintf('%02x ', ord($buffer[$n])); }
				#$this->log->debug('user input raw: '.$bufferHex.'');
				
				for($bufferIndex = 0; $bufferIndex < $bufferLen && !$this->getExit(); $bufferIndex++){
					$char = $buffer[$bufferIndex];
					
					if($char == PHP_EOL){
						$line = $this->buffer;
						$this->buffer = '';
						$this->handleLine($line);
					}
					elseif($char == Console::CHAR_EOF){
						$this->log->debug('break: EOF');
						print "\nexit\n";
						break;
					}
					elseif($char == Console::CHAR_BACKSPACE){
						$this->log->debug('got backspace');
						print chr(8).chr(8).chr(8).'   '.chr(8).chr(8).chr(8); flush();
						if($this->buffer){
							$this->buffer = substr($this->buffer, 0, -1);
						}
					}
					else{
						$this->buffer .= $char;
						#$this->log->debug('buffer "'.$this->buffer.'"');
					}
				}
			}
		}
	}
	
	private function handleLine($line){
		if($line){
			$this->log->debug('user input line: "'.$line.'"');
			
			if($line[0] == '/'){
				$line = substr($line, 1);
				if($line == 'help'){
					print(
						         "/connect <IP> <PORT>      - open a connection"
						.PHP_EOL."/ab                       - address book: list nicks"
						.PHP_EOL."/ab rem <ID>              - address book: remove contact"
						.PHP_EOL."/talk <NICK|UUID>         - open a connection to a know nick"
						.PHP_EOL."/request                  - list all talk requests"
						.PHP_EOL."/request accept <ID>      - accept  a talk request"
						.PHP_EOL."/request decline <ID>     - decline a talk request"
						.PHP_EOL."/close                    - close talk"
						.PHP_EOL."/nick                     - print your nickname"
						.PHP_EOL."/nick <NICK>              - set a new nickname"
						.PHP_EOL."/exit                     - exit this programm"
						.PHP_EOL.''
					);
					$this->printPs1('printMsgStack help');
				}
				elseif(substr($line, 0, 8) == 'connect '){
					$data = substr($line, 8);
					$ip = '';
					$port = 0;
					
					$pos = strpos($data, ' ');
					if($pos === false){
						print 'Usage: /connect <IP> <PORT>'.PHP_EOL.'/connect 192.168.241.10 25000'.PHP_EOL;
						$this->printPs1('printMsgStack connect A');
					}
					else{
						$ip = substr($data, 0, $pos);
						$port = (int)substr($data, $pos + 1);
						
						if($port <= 0xffff){
							$this->msgAdd('Connecting to '.$ip.':'.$port.' ...');
							$connected = $this->getIpcKernelConnection()->execSync('serverConnect', array($ip, $port, true));
							$this->msgAdd('Connection to '.$ip.':'.$port.' '.($connected ? 'established' : 'failed').'.');
							$this->printPs1('printMsgStack connect B');
						}
						else{
							print 'ERROR: Port can not be bigger than '. 0xffff .'.'.PHP_EOL;
							$this->printPs1('printMsgStack connect C');
						}
					}
				}
				elseif($line == 'request'){
					print ' ID RID                                   IP:PORT               USERNAME'.PHP_EOL;
					foreach($this->talkRequests as $talkRequestId => $request){
						$ipPortStr = $request->getClient()->getIp().':'.$request->getClient()->getPort();
						$ipPortStrLen = strlen($ipPortStr);
						
						printf('%3d %36s  %s %s'.PHP_EOL, $request->getId(), substr($request->getRid(), 0, 36), $ipPortStr.str_repeat(' ', 21 - $ipPortStrLen), $request->getUserNickname());
					}
					$this->printPs1('printMsgStack request');
				}
				elseif(substr($line, 0, 8) == 'request '){
					$data = substr($line, 8);
					
					$pos = strpos($data, ' ');
					if($pos === false){
						print 'Usage: /request accept <ID>'.PHP_EOL.'       /request decl <ID>'.PHP_EOL;
					}
					else{
						$action = substr($data, 0, $pos);
						$id = substr($data, $pos + 1);
						
						if(isset($this->talkRequests[$id])){
							$talkRequest = $this->talkRequests[$id];
							
							if($talkRequest->getStatus() == 0){
								if($action == 'accept'){
									$talkRequest->setStatus(1);
									
									$this->msgAdd('Accepting talk request ID '.$talkRequest->getId().'.'.PHP_EOL.'Now talking to "'.$talkRequest->getUserNickname().'".');
									
									$this->setModeChannel(true);
									$this->setModeChannelClient($talkRequest->getClient());
								}
								else{
									$talkRequest->getStatus(2);
									$this->msgAdd('Declining talk request ID '.$id.'.');
								}
								
								$this->talkResponseSend($talkRequest);
							}
							elseif($request->getStatus() == 1){
								$this->msgAdd('You already accepted this talk request.');
							}
							elseif($request->getStatus() == 2){
								$this->msgAdd('You already declined this talk request.');
							}
							elseif($request->getStatus() == 3){
								$this->msgAdd('Talk request ID '.$id.' timed-out.');
							}
						}
						else{
							#print $this->getDate()..PHP_EOL;
							$this->msgAdd('Talk request ID '.$id.' not found.');
						}
					}
				}
				elseif($line == 'close'){
					$this->talkCloseSend();
					
					$this->setModeChannel(false);
					$this->setModeChannelClient(null);
				}
				elseif($line == 'nick'){
					$this->msgAdd('Your nickname: '.$this->userNickname);
				}
				elseif(substr($line, 0, 5) == 'nick '){
					$tmp = substr($line, 5);
					$tmp = preg_replace('/[^a-zA-Z0-9-_.]/', '', $tmp);
					$tmp = substr($tmp, 0, Settings::USER_NICKNAME_LEN_MAX);
					
					if($tmp){
						$this->userNickname = $tmp;
						
						$this->getIpcKernelConnection()->execAsync('setSettingsUserNickname', array($this->userNickname));
						
						$this->msgAdd('New nickname: '.$this->userNickname);
					}
					else{
						$this->msgAdd('Your nickname: '.$this->userNickname);
					}
				}
				elseif($line == 'exit'){
					#print "\nexit\n";
					#$this->msgAdd('exit');
					$this->setExit(1);
				}
				else{
					$this->printPs1('handleLine else');
				}
			}
			else{
				if($this->getModeChannel()){
					$this->talkMsgAdd(0, $this->userNickname, $line);
					$this->talkMsgSend($line);
				}
				else{
					$this->printPs1('handleLine');
				}
			}
		}
		else{
			$this->printPs1('handleLine');
		}
	}
	
	private function printMsgStack(){
		if($this->msgStack){
			$this->lineClear();
			foreach($this->msgStack as $msgId => $msg){
				$this->linePrint($this->getDate().' '.$msg['text']);
			}
			$this->msgStack = array();
			$this->printPs1('printMsgStack');
		}
	}
	
	public function shutdown(){
		#print __CLASS__.'->'.__FUNCTION__.': '.(int)$this->ipcKernelShutdown."\n";
		$this->getLog()->info('shutdown');
		
		fclose($this->stdin);
		
		$this->log->debug('tty restore');
		system('stty sane');
		
		if(!$this->ipcKernelShutdown){
			$this->getIpcKernelConnection()->execSync('shutdown');
		}
	}
	
	public function ipcKernelShutdown(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log->info('Connection to kernel process closed.');
		$this->setExit(1);
		$this->ipcKernelShutdown = true;
		
		return null;
	}
	
	public function talkRequestAdd(Client $client, $rid, $userNickname){
		$this->talkRequestsId++;
		
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#ve($client);
		
		$talkRequest = new TalkRequest();
		$talkRequest->setId($this->talkRequestsId);
		$talkRequest->setRid($rid);
		$talkRequest->setClient($client);
		$talkRequest->setUserNickname($userNickname);
		
		$this->talkRequests[$this->talkRequestsId] = $talkRequest;
		
		$this->msgAdd('User "'.$talkRequest->getUserNickname().'" wants to talk to you. Type "/request accept '.$talkRequest->getId().'" to get in touch.');
	}
	
	private function talkResponseSend(TalkRequest $talkRequest){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$userNickname = '';
		if($talkRequest->getStatus() == 1){
			$userNickname = $this->userNickname;
		}
		
		$this->getIpcKernelConnection()->execAsync('serverTalkResponseSend',
			array($talkRequest->getClient(), $talkRequest->getRid(), $talkRequest->getStatus(), $userNickname));
	}
	
	private function talkMsgSend($text){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$rid = (string)Uuid::uuid4();
		
		$args = array($this->getModeChannelClient(), $rid, $this->userNickname, $text);
		$this->getIpcKernelConnection()->execAsync('serverTalkMsgSend', $args);
	}
	
	public function talkMsgAdd($rid = '', $userNickname, $text){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->msgAdd('<'.$userNickname.'> '.$text);
	}
	
	private function talkCloseSend(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$rid = (string)Uuid::uuid4();
		
		$args = array( $this->getModeChannelClient(), $rid, $this->userNickname);
		$this->getIpcKernelConnection()->execAsync('serverTalkCloseSend', $args);
	}
	
}
