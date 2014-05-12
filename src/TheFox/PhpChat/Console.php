<?php

namespace TheFox\PhpChat;

use Exception;
use RuntimeException;
use DateTime;
use DateTimeZone;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\Ipc\ConnectionClient;
use TheFox\Ipc\StreamHandler as IpcStreamHandler;
use TheFox\Dht\Kademlia\Node;

class Console extends Thread{
	
	const LOOP_USLEEP = 10000;
	const CHAR_ESCAPE = "\x1b";
	const VT100_CHAR_CONTROL_H = "\x08";
	const VT100_CHAR_BACKSPACE = "\x7f";
	const VT100_CHAR_DELETE = "\x5b";
	const CHAR_EOF = "\x04";
	const RANDOM_MSG_DELAY_MIN = 30;
	const RANDOM_MSG_DELAY_MAX = 300;
	const RANDOM_MSG_CHAR_SET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
	const RANDOM_MSG_CHAR_SET_LEN = 58;
	const HISTORY_MAX = 100;
	
	private $log = null;
	private $ipcKernelConnection = null;
	private $ipcKernelShutdown = false;
	private $ps1 = 'phpchat:> ';
	private $tcols = 0;
	private $tlines = 0;
	private $msgStack = array();
	private $msgStackPrintPs1 = true;
	private $buffer = '';
	private $bufferOriginal = '';
	private $bufferCursorPos = 0;
	private $modeChannel = false;
	private $modeChannelClient = null;
	private $userNickname = '';
	private $talkRequestsId = 0;
	private $talkRequests = array();
	private $nextRandomMsg = 0;
	private $sttySettings = '';
	private $history = array();
	private $historyCursorPos = -1;
	private $charControlH;
	private $charBackspace;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(!posix_isatty(STDIN)){
			throw new RuntimeException('STDIN: Invalid TTY.', 1);
		}
		if(!posix_isatty(STDOUT)){
			throw new RuntimeException('STDOUT: Invalid TTY.', 1);
		}
		
		$this->log = new Logger('console');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::INFO));
		$this->log->pushHandler(new LoggerStreamHandler('log/console.log', Logger::DEBUG));
		
		$this->log->info('start');
		
		$this->nextRandomMsg = time() + static::RANDOM_MSG_DELAY_MIN;
		$this->randomMsgDebug();
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
		if($this->getModeChannel()){
			return $this->userNickname.':> ';
		}
		return $this->ps1;
	}
	
	public function setModeChannel($modeChannel){
		$this->modeChannel = $modeChannel;
	}
	
	private function getModeChannel(){
		return $this->modeChannel;
	}
	
	public function setModeChannelClient($modeChannelClient){
		$this->modeChannelClient = $modeChannelClient;
	}
	
	private function getModeChannelClient(){
		return $this->modeChannelClient;
	}
	
	public function printPs1($printBuffer = true, $debug = ''){
		#$this->log->debug('printPs1');
		
		$output = $this->getPs1();
		if($printBuffer){
			$output .= $this->buffer;
		}
		print $output;
	}
	
	private function cursorUp(){
		print static::CHAR_ESCAPE.'[1A';
	}
	
	private function cursorJumpToTop(){
		print static::CHAR_ESCAPE.'[1;1f';
	}
	
	private function cursorJumpToColumn($column = 1){
		print static::CHAR_ESCAPE.'['.$column.'G';
	}
	
	private function cursorRight($offset = 1){
		print static::CHAR_ESCAPE.'['.$offset.'C';
	}
	
	private function cursorLeft($offset = 1){
		print static::CHAR_ESCAPE.'['.$offset.'D';
	}
	
	private function lineClear(){
		#$this->log->debug('line clear');
		print "\r".static::CHAR_ESCAPE.'[K';
	}
	
	private function lineClearRight(){
		#$this->log->debug('line clear');
		print static::CHAR_ESCAPE.'[J';
	}
	
	private function scrollUp(){
		#$this->log->debug('scrollUp');
		print static::CHAR_ESCAPE.'[S';
	}
	
	private function scrollDown(){
		#$this->log->debug('scrollDown');
		print static::CHAR_ESCAPE.'[T';
	}
	
	private function linePrint($text){
		#$this->log->debug('line print "'.$text.'"');
		print $text.PHP_EOL;
	}
	
	private function printMsgStack(){
		if($this->msgStack){
			$this->log->debug('printMsgStack begin '.(int)$this->msgStackPrintPs1);
			
			$this->msgStackPrintPs1 = true;
			foreach($this->msgStack as $msgId => $msg){
				$this->log->debug('msg '.(int)$msg['printPs1'].' '.(int)$msg['clearLine'].' "'.$msg['text'].'"');
				if($msg['clearLine']){
					$this->lineClear();
				}
				$this->linePrint( ($msg['showDate'] ? $this->getDate().' ' : '').$msg['text'] );
				$this->msgStackPrintPs1 = $msg['printPs1'];
			}
			$this->msgStack = array();
			
			if($this->msgStackPrintPs1){
				$this->printPs1(true, 'printMsgStack');
			}
			
			$this->log->debug('printMsgStack end '.(int)$this->msgStackPrintPs1);
		}
	}
	
	public function msgAdd($text = '', $showDate = false, $printPs1 = false, $clearLine = false){
		$this->msgStack[] = array(
			'text' => $text,
			'showDate' => $showDate,
			'printPs1' => $printPs1,
			'clearLine' => $clearLine,
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
		
		$this->sttySetup();
		$this->keybindingsSetup();
		
		$this->userNickname = $this->getIpcKernelConnection()->execSync('getSettingsUserNickname');
		
		print PHP_EOL."Type '/help' for help.".PHP_EOL;
		
		$this->msgAdd('start', true, true);
		
		return true;
	}
	
	private function sttySetup(){
		$this->log->debug('stty setup');
		
		$this->sttySettings = exec('stty -g');
		#print "settings: '".$this->sttySettings."'\n";
		
		$this->log->debug('tput setup');
		$this->tcols = (int)exec('tput cols');
		$this->tlines = (int)exec('tput lines');
		$this->log->debug('cols = '.$this->tcols.', lines = '.$this->tlines);
		
		stream_set_blocking(STDIN, 0);
		
		$this->sttyEnterIcanonMode();
		$this->sttyEchoOff();
	}
	
	private function keybindingsSetup(){
		// Default
		$this->charControlH = static::VT100_CHAR_CONTROL_H;
		$this->backspace = static::VT100_CHAR_BACKSPACE;
		
		
		$keys = array();
		exec('infocmp', $lines);
		foreach($lines as $line){
			
			if($line[0] == "\t"){
				#print "line '".substr($line, 1)."'\n";
				#ve(\TheFox\Utilities\Hex::dataEncode($line));
				$items = preg_split('/, ?/', substr($line, 1));
				foreach($items as $item){
					#print "item '$item'\n";
					$pos = strpos($item, '=');
					if($pos !== false){
						$keys[substr($item, 0, $pos)] = substr($item, $pos + 1);
					}
				}
			}
			
			#break;
		}
		
	}
	
	private function sttyReset(){
		$this->log->debug('tty restore');
		
		#$this->sttyExitIcanonMode();
		
		#system('stty sane');
		exec('stty '.$this->sttySettings);
	}
	
	private function sttyEnterIcanonMode(){
		system('stty -icanon');
	}
	
	private function sttyExitIcanonMode(){
		system('stty icanon');
	}
	
	private function sttyEchoOn(){
		exec('stty echo');
	}
	
	private function sttyEchoOff(){
		exec('stty -echo');
	}
	
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(!$this->getIpcKernelConnection()){
			throw new RuntimeException('You must first run init().');
		}
		
		while(!$this->getExit()){
			#$this->log->debug('run');
			
			$this->readStdin();
			$this->printMsgStack();
			$this->sendRandomMsg();
			
			if(!$this->getIpcKernelConnection()->run()){
				$this->log->info('Connection to kernel process end unexpected.');
				$this->setExit(1);
			}
			
			usleep(static::LOOP_USLEEP);
		}
		
		$this->shutdown();
	}
	
	private function readStdin(){
		$read = array(STDIN);
		$write = array();
		$except = array();
		$streamsChanged = stream_select($read, $write, $except, 0);
		if($streamsChanged){
			
			#print "fgets\n";
			#$this->log->debug('fgets');
			$buffer = fgets(STDIN, 1024);
			if($buffer === false){
				$this->log->error('buffer is false');
			}
			
			if($buffer !== false){
				$bufferLen = strlen($buffer);
				
				#$this->log->debug('user input: '.$bufferLen);
				
				#$this->log->debug('buffer: '.ord($buffer[0]));
				#$bufferHex = ''; for($n = 0; $n < $bufferLen; $n++){ $bufferHex .= sprintf('%02x ', ord($buffer[$n])); }
				#$this->log->debug('user input raw: '.$bufferHex.'');
				
				for($bufferIndex = 0; $bufferIndex < $bufferLen && !$this->getExit(); $bufferIndex++){
					$char = $buffer[$bufferIndex];
					
					if($char == PHP_EOL){
						#$this->log->debug('EOL');
						$line = $this->buffer;
						$this->buffer = '';
						$this->bufferOriginal = '';
						$this->bufferCursorPos = 0;
						$this->historyCursorPos = -1;
						$this->handleLine($line);
					}
					elseif($char == static::CHAR_EOF){
						$this->log->debug('break: EOF');
						$this->setExit(1);
						print "\nexit\n";
						break;
					}
					elseif($char == static::VT100_CHAR_BACKSPACE || $char == static::VT100_CHAR_CONTROL_H){
						#$this->log->debug('got backspace');
						
						if($this->historyCursorPos == count($this->history)){
							$this->buffer = substr($this->buffer, 0, -1);
							
							sleep(1);
							$this->cursorLeft();
							
							sleep(1);
							$this->lineClearRight();
							
						}
						elseif($this->bufferCursorPos){
							$this->buffer = substr($this->buffer, 0, $this->bufferCursorPos - 1).substr($this->buffer, $this->bufferCursorPos);
							
							sleep(1);
							$this->cursorLeft();
							
							sleep(1);
							$this->lineClearRight();
							
							sleep(1);
							print substr($this->buffer, $this->bufferCursorPos - 1);
							
							sleep(1);
							$this->cursorJumpToColumn(strlen($this->getPs1()) + $this->bufferCursorPos);
							
							$this->bufferCursorPos--;
						}
						
						$this->log->debug('buffer '.$this->bufferCursorPos.', '.strlen($this->buffer).' "'.$this->buffer.'"');
					}
					elseif($char == "\x1b" && $buffer[$bufferIndex + 1] == "\x5b"
						&& $buffer[$bufferIndex + 2] == "\x41"){
						$bufferIndex += 2;
						
						if($this->historyCursorPos == -1){
							$this->bufferOriginal = $this->buffer;
						}
						
						if($this->historyCursorPos < count($this->history) - 1){
							$this->historyCursorPos++;
							
							#sleep(1);
							$this->cursorJumpToColumn(strlen($this->getPs1()) + 1);
							
							#sleep(1);
							$this->buffer = $this->history[$this->historyCursorPos];
							$this->bufferCursorPos = strlen($this->buffer);
							print $this->buffer;
							
							#sleep(1);
							$this->lineClearRight();
						}
						
						$this->log->debug('got arrow u: '.$this->historyCursorPos);
					}
					elseif($char == "\x1b" && $buffer[$bufferIndex + 1] == "\x5b"
						&& $buffer[$bufferIndex + 2] == "\x42"){
						$bufferIndex += 2;
						
						if($this->historyCursorPos > -1){
							$this->historyCursorPos--;
							
							#sleep(1);
							$this->cursorJumpToColumn(strlen($this->getPs1()) + 1);
							
							#sleep(1);
							if($this->historyCursorPos == -1){
								$this->buffer = $this->bufferOriginal;
							}
							else{
								$this->buffer = $this->history[$this->historyCursorPos];
							}
							$this->bufferCursorPos = strlen($this->buffer);
							print $this->buffer;
							
							#sleep(1);
							$this->lineClearRight();
						}
						
						$this->log->debug('got arrow d: '.$this->historyCursorPos);
					}
					elseif($char == "\x1b" && $buffer[$bufferIndex + 1] == "\x5b"
						&& $buffer[$bufferIndex + 2] == "\x43"){
						$bufferIndex += 2;
						
						if($this->bufferCursorPos < strlen($this->buffer)){
							$this->cursorRight();
							$this->bufferCursorPos++;
						}
						
						$this->log->debug('got arrow right: '.$this->bufferCursorPos);
					}
					elseif($char == "\x1b" && $buffer[$bufferIndex + 1] == "\x5b"
						&& $buffer[$bufferIndex + 2] == "\x44"){
						$bufferIndex += 2;
						
						if($this->bufferCursorPos > 0){
							$this->cursorLeft();
							$this->bufferCursorPos--;
						}
						
						$this->log->debug('got arrow left:  '.$this->bufferCursorPos);
					}
					
					elseif($char == "\x1b" && $buffer[$bufferIndex + 1] == "\x5b"
							&& $buffer[$bufferIndex + 2] == "\x33"
							&& $buffer[$bufferIndex + 3] == "\x7e"
						){
						$bufferIndex += 3;
						$this->log->debug('got delete');
						
						if($this->bufferCursorPos < strlen($this->buffer)){
							$this->buffer = substr($this->buffer, 0, $this->bufferCursorPos).substr($this->buffer, $this->bufferCursorPos + 1);
							
							#sleep(1);
							$this->lineClearRight();
							
							#sleep(1);
							print substr($this->buffer, $this->bufferCursorPos);
							
							#sleep(1);
							$this->cursorJumpToColumn(strlen($this->getPs1()) + $this->bufferCursorPos + 1);
						}
						
						$this->log->debug('buffer '.$this->bufferCursorPos.', '.strlen($this->buffer).' "'.$this->buffer.'"');
					}
					
					else{
						#$this->log->debug('user input raw: '.sprintf('%02x ', ord($char)).'');
						
						
						$end = '';
						if($this->bufferCursorPos == strlen($this->buffer)){
							$end = 'end';
							
							print $char;
							$this->buffer .= $char;
						}
						else{
							$end = 'NOT end';
							
							$this->buffer = substr($this->buffer, 0, $this->bufferCursorPos).$char.substr($this->buffer, $this->bufferCursorPos);
							
							#sleep(1);
							$this->lineClearRight();
							
							#sleep(1);
							print substr($this->buffer, $this->bufferCursorPos);
							
							#sleep(1);
							$this->cursorJumpToColumn(strlen($this->getPs1()) + $this->bufferCursorPos + 2);
							
						}
						
						$this->bufferCursorPos++;
						$this->log->debug('buffer '.$this->bufferCursorPos.', '.$end.', '.strlen($this->buffer).' "'.$this->buffer.'"');
						
					}
				}
			}
		}
		#else{ $this->log->debug('stream not changed'); }
	}
	
	private function handleLine($line){
		if($line){
			$this->log->debug('user input line: "'.$line.'"');
			
			$this->historyAdd($line);
			
			if($line[0] == '/'){
				$line = substr($line, 1);
				
				$commandFound = true;
				
				if($line == 'help'){
					#print PHP_EOL;
					$this->handleCommandHelp();
				}
				elseif(substr($line, 0, 8) == 'connect '){
					$this->handleCommandConnect($line);
				}
				elseif($line == 'ab'){ # TODO
					$format = '%3d %36s  %s';
					
					$this->msgAdd(' ID UUID                                  USERNAME', false, false);
					foreach($this->getIpcKernelConnection()->execSync('getAddressbook')->getContacts() as $contactId => $contact){
						$this->msgAdd(sprintf($format, $contact->getId(), $contact->getNodeId(), $contact->getUserNickname()), false, false);
					}
					$this->msgAdd('END OF LIST', false, true);
				}
				elseif(substr($line, 0, 3) == 'ab '){
					$this->handleCommandAddressbook($line);
				}
				elseif($line == 'talk' || $line == 'talk '){ # TODO
					$this->msgAdd('Usage: /talk <NICK|UUID>', false, true);
				}
				elseif(substr($line, 0, 5) == 'talk '){
					$this->handleCommandTalk($line);
				}
				elseif($line == 'request'){ # TODO
					$format = '%3d %36s  %s %s';
					
					$this->msgAdd(' ID RID                                   IP:PORT               USERNAME', false, false);
					foreach($this->talkRequests as $talkRequestId => $request){
						$rid = substr($request->getRid(), 0, 36);
						$ipPortStr = $request->getClient()->getIp().':'.$request->getClient()->getPort();
						$ipPortStrLen = strlen($ipPortStr);
						$ip = $ipPortStr.str_repeat(' ', 21 - $ipPortStrLen);
						
						$this->msgAdd(sprintf($format, $request->getId(), $rid, $ip, $request->getUserNickname()), false, false);
					}
					$this->msgAdd('END OF LIST', false, true);
				}
				elseif(substr($line, 0, 8) == 'request '){
					$this->handleCommandRequest($line);
				}
				elseif($line == 'close'){
					$this->handleCommandClose();
				}
				elseif(substr($line, 0, 3) == 'msg'){
					$this->handleCommandMsg($line);
				}
				elseif($line == 'nick'){
					$this->msgAdd('Your nickname: '.$this->userNickname, true, true);
				}
				elseif(substr($line, 0, 5) == 'nick '){
					$this->handleCommandNick($line);
				}
				elseif($line == 'save'){
					$this->handleCommandSave();
				}
				elseif($line == 'history'){
					$this->handleCommandHistory();
				}
				elseif($line == 'exit'){
					$this->msgAdd();
					$this->setExit(1);
				}
				elseif($line == 't'){
					$this->handleCommandTest();
				}
				else{
					$this->log->debug('do nothing C');
					
					#$this->lineClear();
					#$this->printPs1(true, 'handleLine else C');
					
					$this->msgAdd();
					$this->msgAdd('ERROR: Command "'.$line.'" not found.', false, true);
				}
			}
			else{
				if($this->getModeChannel()){
					#$this->lineClear();
					#$this->cursorUp();
					$this->talkMsgAdd(0, $this->userNickname, $line);
					$this->talkMsgSend($line);
				}
				else{
					$this->log->debug('do nothing B');
					
					#$this->lineClear();
					#$this->printPs1(true, 'handleLine else B');
					
					$this->msgAdd();
					$this->msgAdd('ERROR: Command "'.$line.'" not found.', false, true);
				}
			}
		}
		else{
			#$this->log->debug('do nothing A');
			
			print PHP_EOL;
			$this->printPs1(true, 'handleLine else A');
		}
	}
	
	private function handleCommandHelp(){
		$this->msgAdd();
		$this->msgAdd('/connect <IP> <PORT>      - open a connection', false, false);
		$this->msgAdd('/ab                       - address book: list nicks', false, false);
		$this->msgAdd('/ab rem <ID>              - address book: remove contact', false, false);
		$this->msgAdd('/talk <NICK|UUID>         - open a connection to a know nick', false, false);
		$this->msgAdd('/request                  - list all talk requests', false, false);
		$this->msgAdd('/request accept <ID>      - accept  a talk request', false, false);
		$this->msgAdd('/request decline <ID>     - decline a talk request', false, false);
		$this->msgAdd('/close                    - close talk', false, false);
		$this->msgAdd('/msg                      - list msgs', false, false);
		$this->msgAdd('/msg new <UUID>           - send a msg to a uuid', false, false);
		$this->msgAdd('/msg read <NO|ID>         - read a msg', false, false);
		$this->msgAdd('/nick                     - print your nickname', false, false);
		$this->msgAdd('/nick <NICK>              - set a new nickname', false, false);
		$this->msgAdd('/history                  - print history', false, true);
		$this->msgAdd('/exit                     - exit this programm', false, true);
	}
	
	private function handleCommandConnect($line){
		$data = substr($line, 8);
		$ip = '';
		$port = 0;
		
		$pos = strpos($data, ' ');
		if($pos === false){
			$this->msgAdd();
			$this->msgAdd('Usage: /connect <IP> <PORT>', false, false);
			$this->msgAdd('       /connect 192.168.241.10 25000', false, true);
		}
		else{
			$ip = substr($data, 0, $pos);
			$port = (int)substr($data, $pos + 1);
			$portMax = 0xffff;
			
			if($port <= $portMax){
				$this->connect($ip, $port);
			}
			else{
				$this->msgAdd();
				$this->msgAdd('ERROR: Port can not be bigger than '.$portMax.'.', false, true);
			}
		}
	}
	
	private function handleCommandAddressbook($line){
		$data = substr($line, 3);
		
		$this->msgAdd();
		
		$pos = strpos($data, ' ');
		if($pos === false){
			$this->msgAdd('Usage: /ab rem <ID>', false, true);
		}
		else{
			$action = substr($data, 0, $pos);
			$id = substr($data, $pos + 1);
			
			if($action == 'rem'){
				if($this->getIpcKernelConnection()->execSync('addressbookContactRemove', array($id))){
					$this->msgAdd('Removed '.$id.' from addressbook.', true, true);
				}
				else{
					$this->msgAdd('ERROR: Can not remove '.$id.' from addressbook.', false, true);
				}
			}
			else{
				$this->msgAdd('ERROR: Command "'.$action.'" not found.', false, true);
			}
		}
	}
	
	private function handleCommandTalk($line){
		$data = substr($line, 5);
		
		$uuid = '';
		if(strIsUuid($data)){
			$uuid = $data;
		}
		else{
			$contacts = $this->getIpcKernelConnection()->execSync('getAddressbook')->contactsGetByNick($data);
			if(count($contacts) > 1){
				$format = '%3d %36s  %s';
				
				$this->msgAdd();
				$this->msgAdd('Found several nodes with nickname "'.$data.'". ', false, false);
				$this->msgAdd('Delete old nodes or use UUID instead.', false, false);
				$this->msgAdd(' ID UUID                                  USERNAME', false, false);
				foreach($contacts as $contactId => $contact){
					$this->msgAdd(sprintf($format, $contact->getId(), $contact->getNodeId(), $contact->getUserNickname()), false, false);
				}
				$this->msgAdd('END OF LIST', false, true);
			}
			elseif(count($contacts) == 1){
				$contact = array_shift($contacts);
				$uuid = $contact->getNodeId();
			}
			else{
				$this->msgAdd();
				$this->msgAdd('ERROR: Nick "'.$data.'" not found.', false, true);
			}
		}
		
		if($uuid){
			$node = new Node();
			$node->setIdHexStr($uuid);
			
			if($onode = $this->getIpcKernelConnection()->execSync('getTable')->nodeFindInBuckets($node)){
				$this->connect($onode->getIp(), $onode->getPort());
			}
			else{
				$this->msgAdd();
				$this->msgAdd('ERROR: Node '.$node->getIdHexStr().' not found.', false, true);
			}
		}
	}
	
	private function handleCommandRequest($line){
		$data = substr($line, 8);
		
		$pos = strpos($data, ' ');
		if($pos === false){
			$this->msgAdd();
			$this->msgAdd('Usage: /request accept <ID>', false, false);
			$this->msgAdd('       /request decline <ID>', false, true);
		}
		else{
			$action = substr($data, 0, $pos);
			$id = substr($data, $pos + 1);
			
			if(isset($this->talkRequests[$id])){
				$talkRequest = $this->talkRequests[$id];
				
				if($talkRequest->getStatus() == 0){
					if($action == 'accept'){
						$talkRequest->setStatus(1);
						
						$this->msgAdd();
						$this->msgAdd('Accepting talk request ID '.$talkRequest->getId().'.', true, false);
						$this->msgAdd('Now talking to "'.$talkRequest->getUserNickname().'".', true, true);
						
						$this->setModeChannel(true);
						$this->setModeChannelClient($talkRequest->getClient());
					}
					else{
						$talkRequest->getStatus(2);
						$this->msgAdd();
						$this->msgAdd('Declining talk request ID '.$id.'.', true, true);
					}
					
					$this->talkResponseSend($talkRequest);
				}
				elseif($talkRequest->getStatus() == 1){
					$this->msgAdd();
					$this->msgAdd('You already accepted this talk request.', true, true);
				}
				elseif($talkRequest->getStatus() == 2){
					$this->msgAdd();
					$this->msgAdd('You already declined this talk request.', true, true);
				}
				elseif($talkRequest->getStatus() == 3){
					$this->msgAdd();
					$this->msgAdd('Talk request ID '.$id.' timed-out.', true, true);
				}
			}
			else{
				$this->msgAdd();
				$this->msgAdd('Talk request ID '.$id.' not found.', true, true);
			}
		}
	}
	
	private function handleCommandClose(){
		$this->talkCloseSend();
		
		$this->setModeChannel(false);
		$this->setModeChannelClient(null);
	}
	
	private function handleCommandMsg($line){
		#print __CLASS__.'->'.__FUNCTION__.': "'.$line.'"'."\n";
		$line = substr($line, 3);
		
		#print __CLASS__.'->'.__FUNCTION__.': get settings'."\n";
		$settings = $this->getIpcKernelConnection()->execSync('getSettings');
		
		#print __CLASS__.'->'.__FUNCTION__.': get table'."\n";
		$table = $this->getIpcKernelConnection()->execSync('getTable');
		
		#print __CLASS__.'->'.__FUNCTION__.': get msgDb'."\n";
		#$msgDb = $this->getIpcKernelConnection()->execSync('getMsgDb');
		$msgs = $this->getIpcKernelConnection()->execSync('msgDbMsgGetMsgsForDst');
		$msgsByIndex = array_keys($msgs);
		#ve($msgDb);
		#ve($msgs);
		#ve($msgsByIndex);
		
		/*
		if(!$msgDb){
			print __CLASS__.'->'.__FUNCTION__.': get msgDb failed'."\n";
			return;
		}*/
		
		if($line){
			$line = substr($line, 1);
			$args = preg_split('/ /', $line);
			#ve($args);
			
			#$this->printPs1(true, 'handleCommandMsg A');
			
			if(($args[0] == 'new' || $args[0] == 'n')){
				if(strIsUuid($args[1])){
					if($args[1] != $table->getLocalNode()->getIdHexStr()){
						print PHP_EOL.'Enter the text to send to '.$args[1].'.'.PHP_EOL;
						print 'NOTE: end text with  <RETURN>.<RETURN>'.PHP_EOL;
						
						$this->sttyExitIcanonMode();
						$this->sttyEchoOn();
						stream_set_blocking(STDIN, 1);
						
						$text = '';
						while(!$this->getExit()){
							$line = fgets(STDIN, 1024);
							
							#print "line: '".substr($line, 0, -1)."'\n";
							if(substr($line, 0, -1) == '.') break;
							$text .= $line;
							
							sleep(1);
						}
						
						if(!$this->getExit()){
							print 'Send msg? [Y/n] ';
							
							$text = substr($text, 0, -1);
							
							$answer = strtolower(substr(fgets(STDIN, 100), 0, -1));
							if(!$answer){
								$answer = 'y';
							}
							print "Answer: '".$answer."'\n";
							#print "Text: '".$text."'\n";
							
							stream_set_blocking(STDIN, 0);
							$this->sttyEnterIcanonMode();
							$this->sttyEchoOff();
							
							if($answer == 'y'){
								$dstNodeId = $args[1];
								#$dstNodeId = '42785b21-011b-4093-b61d-000000000001';
								#$text = 'this is  a test. '.date('Y/m/d H:i:s');
								
								
								$settings = $this->getIpcKernelConnection()->execSync('getSettings');
								$table = $this->getIpcKernelConnection()->execSync('getTable');
								
								
								$msg = new Msg();
								$msg->setSrcNodeId($settings->data['node']['id']);
								$msg->setSrcSslKeyPub($table->getLocalNode()->getSslKeyPub());
								$msg->setSrcUserNickname($this->userNickname);
								
								$dstNode = new Node();
								$dstNode->setIdHexStr($dstNodeId);
								
								$msg->setDstNodeId($dstNode->getIdHexStr());
								if($oDstNode = $table->nodeFindInBuckets($dstNode)){
									#print 'found node in table'.PHP_EOL;
									$msg->setDstSslPubKey($oDstNode->getSslKeyPub());
								}
								#else{ print 'node not found'.PHP_EOL; }
								
								$msg->setText($text);
								$msg->setSslKeyPrvPath($settings->data['node']['sslKeyPrvPath'], $settings->data['node']['sslKeyPrvPass']);
								$msg->setStatus('O');
								
								$encrypted = false;
								#print 'DstSslPubKey: '.strlen($msg->getDstSslPubKey()).PHP_EOL;
								if($msg->getDstSslPubKey()){
									#print 'use dst key'.PHP_EOL;
									
									$msg->setEncryptionMode('D');
								}
								else{
									// Encrypt with own public key
									// while destination public key is not available.
									#print 'use local key'.PHP_EOL;
									
									$msg->setEncryptionMode('S');
									$msg->setDstSslPubKey($table->getLocalNode()->getSslKeyPub());
								}
								
								try{
									$encrypted = $msg->encrypt();
									
									if($encrypted){
										$this->getIpcKernelConnection()->execAsync('msgDbMsgAdd', array($msg));
										
										$this->msgAdd('OK: created msg '.$msg->getId(), true, true);
									}
									else{
										$this->msgAdd('ERROR: could not encrypt message.', true, true);
									}
								}
								catch(Exception $e){
									$this->msgAdd('ERROR: '.$e->getMessage(), true, true);
								}
								
								#$this->printPs1(true, 'handleCommandMsg B');
							}
							else{
								print 'Nothing created, nothing sent.'.PHP_EOL;
								$this->printPs1(true, 'handleCommandMsg C');
							}
						}
					}
					else{
						$this->msgAdd();
						$this->msgAdd('Send a message to yourself?', false, true);
					}
				}
				else{
					$this->msgAdd();
					$this->msgAdd('ERROR: "'.$args[1].'" is not a UUID.', false, true);
				}
			}
			elseif($args[0] == 'read' || $args[0] == 'r'){
				if(isset($args[1])){
					$msg = null;
					if(strIsUuid($args[1])){
						if(isset($msgs[$args[1]])){
							$msg = $msgs[$args[1]];
						}
					}
					else{
						$no = (int)$args[1] - 1;
						if(isset($msgsByIndex[$no])){
							$msg = $msgs[$msgsByIndex[$no]];
						}
					}
					if($msg){
						
						$msg->setDstSslPubKey($table->getLocalNode()->getSslKeyPub());
						
						$sslKeyPrvPath = $settings->data['node']['sslKeyPrvPath'];
						$sslKeyPrvPass = $settings->data['node']['sslKeyPrvPass'];
						$msg->setSslKeyPrvPath($sslKeyPrvPath, $sslKeyPrvPass);
						
						#ve($msg);
						$text = null;
						try{
							$text = $msg->decrypt();
						}
						catch(Exception $e){
							$text = null;
							#print 'ERROR: decrypt: '.$e->getMessage().PHP_EOL;
						}
						
						$dateCreated = new DateTime();
						$dateCreated->setTimestamp($msg->getTimeCreated());
						
						$dateReceived = new DateTime();
						$dateReceived->setTimestamp($msg->getTimeReceived());
						
						$fromLine = '';
						if($msg->getSrcUserNickname()){
							$fromLine .= $msg->getSrcUserNickname().' ';
						}
						$fromLine .= '<'.$msg->getSrcNodeId().'>';
						
						$toLine = '';
						if($table->getLocalNode()->getIdHexStr() == $msg->getDstNodeId()){
							$toLine .= 'Me ';
						}
						$toLine .= '<'.$msg->getDstNodeId().'>';
						
						$this->msgAdd();
						if(!$text){
							$this->msgAdd('WARNING: could not decrypt text. Only meta data available.', false, false);
						}
						$this->msgAdd('----- MESSAGE BEGIN -----');
						$this->msgAdd('Msg ID: '.$msg->getId(), false, false);
						$this->msgAdd('From: '.$fromLine, false, false);
						$this->msgAdd('To: '.$toLine, false, false);
						$this->msgAdd('Status: '.$msg->getStatusText(), false, false);
						$this->msgAdd('Date: '.$dateCreated->format('Y/m/d H:i:s'), false, false);
						
						if($text){
							$this->msgAdd();
							$this->msgAdd($text, false, false);
							
							$msg->setStatus('R');
							$this->getIpcKernelConnection()->execAsync('msgDbMsgUpdate', array($msg));
						}
						$this->msgAdd('----- MESSAGE END -----', false, true);
					}
					else{
						$this->msgAdd();
						$this->msgAdd('ERROR: could not read msg "'.$args[1].'".', false, true);
					}
				}
				else{
					$this->msgAdd();
					$this->msgAdd('ERROR: you must specify a msg number or ID.', false, true);
				}
			}
		}
		else{
			$format = '%3d %36s  %s';
			
			$this->msgAdd();
			$this->msgAdd(' NO ID                                    STATUS', false, true);
			
			$no = 0;
			foreach($msgs as $msgId => $msg){
				$no++;
				$this->msgAdd(sprintf($format, $no, $msg->getId(), $msg->getStatusText()), false, false);
			}
			$this->msgAdd('END OF LIST', false, true);
		}
	}
	
	private function handleCommandNick($line){
		$tmp = substr($line, 5);
		$tmp = preg_replace('/[^a-zA-Z0-9-_.]/', '', $tmp);
		$tmp = substr($tmp, 0, Settings::USER_NICKNAME_LEN_MAX);
		
		if($tmp){
			$userNicknameOld = $this->userNickname;
			$this->userNickname = $tmp;
			
			$this->getIpcKernelConnection()->execAsync('setSettingsUserNickname', array($this->userNickname));
			
			$this->msgAdd();
			$this->msgAdd('New nickname: '.$this->userNickname, true, true);
			
			if($this->getModeChannel()){
				$this->talkUserNicknameChangeSend($userNicknameOld, $this->userNickname);
			}
		}
		else{
			$this->msgAdd();
			$this->msgAdd('Your nickname: '.$this->userNickname, true, true);
		}
	}
	
	private function handleCommandSave(){
		$this->getIpcKernelConnection()->execAsync('save');
		$this->msgAdd();
		$this->msgAdd('Saved.', true, true);
	}
	
	private function handleCommandHistory(){
		$this->msgAdd();
		foreach(array_reverse($this->history) as $line){
			$this->msgAdd($line, false, false);
		}
		$this->msgAdd('END OF LIST', false, true);
	}
	
	private function handleCommandTest(){
		/*$this->linePrint('line A');
		$this->linePrint('line B');
		$this->linePrint('line C');*/
		
		#$this->msgAdd();
		#$this->msgAdd('line A', false, false, true);
		$this->msgAdd('line B');
		$this->msgAdd('line C', false, true);
		
	}
	
	public function shutdown(){
		#print __CLASS__.'->'.__FUNCTION__.': '.(int)$this->ipcKernelShutdown."\n";
		$this->getLog()->info('shutdown');
		
		if($this->getModeChannel()){
			$this->talkCloseSend();
		}
		
		if(!$this->ipcKernelShutdown){
			#$this->getIpcKernelConnection()->execSync('shutdown'); # TODO
		}
		
		$this->sttyReset();
	}
	
	public function ipcKernelShutdown(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log->info('Connection to kernel process closed.');
		$this->setExit(1);
		$this->ipcKernelShutdown = true;
		
		return null;
	}
	
	public function connect($ip, $port){
		$ipPort = $ip.':'.$port;
		$this->msgAdd();
		$this->msgAdd('Connecting to '.$ipPort.' ...', true, false);
		$connected = $this->getIpcKernelConnection()->execSync('serverConnect', array($ip, $port, true));
		$this->msgAdd('Connection to '.$ipPort.' '.($connected ? 'established' : 'failed').'.', true, false);
	}
	
	public function talkRequestAdd(Client $client, $rid, $userNickname){
		$this->talkRequestsId++;
		
		$talkRequest = new TalkRequest();
		$talkRequest->setId($this->talkRequestsId);
		$talkRequest->setRid($rid);
		$talkRequest->setClient($client);
		$talkRequest->setUserNickname($userNickname);
		
		$this->talkRequests[$this->talkRequestsId] = $talkRequest;
		
		$this->msgAdd('User "'.$talkRequest->getUserNickname().'" wants to talk to you.', true, false, true);
		$this->msgAdd('Type "/request accept '.$talkRequest->getId().'" to get in touch.', true, true);
	}
	
	private function talkResponseSend(TalkRequest $talkRequest){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$userNickname = '';
		if($talkRequest->getStatus() == 1){
			$userNickname = $this->userNickname;
			
			// Add to addressbook.
			$contact = new Contact();
			$contact->setNodeId($talkRequest->getClient()->getNode()->getIdHexStr());
			$contact->setUserNickname($talkRequest->getUserNickname());
			$this->getIpcKernelConnection()->execAsync('addressbookContactAdd', array($contact));
		}
		
		$this->getIpcKernelConnection()->execAsync('serverTalkResponseSend',
			array($talkRequest->getClient(), $talkRequest->getRid(), $talkRequest->getStatus(), $userNickname));
	}
	
	private function talkMsgSend($text, $ignore = false){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$rid = (string)Uuid::uuid4();
		
		$args = array($this->getModeChannelClient(), $rid, $this->userNickname, $text, $ignore);
		$this->getIpcKernelConnection()->execAsync('serverTalkMsgSend', $args);
	}
	
	public function talkMsgAdd($rid = '', $userNickname, $text){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->msgAdd('<'.$userNickname.'> '.$text, true, true, true);
	}
	
	private function talkUserNicknameChangeSend($userNicknameOld, $userNicknameNew){
		$this->getIpcKernelConnection()->execAsync('serverTalkUserNicknameChangeSend',
			array($this->getModeChannelClient(), $userNicknameOld, $userNicknameNew));
	}
	
	private function talkCloseSend(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$rid = (string)Uuid::uuid4();
		
		$args = array( $this->getModeChannelClient(), $rid, $this->userNickname);
		$this->getIpcKernelConnection()->execAsync('serverTalkCloseSend', $args);
	}
	
	private function sendRandomMsg(){
		if($this->getModeChannel() && $this->nextRandomMsg <= time()){
			#print __CLASS__.'->'.__FUNCTION__.''."\n";
			
			$this->randomMsgSetNextTime();
			
			$charset = static::RANDOM_MSG_CHAR_SET;
			
			$text = '';
			
			for($n = mt_rand(mt_rand(0, 100), mt_rand(1024, 2048)); $n > 0; $n--){
				$text .= $charset[mt_rand(0, static::RANDOM_MSG_CHAR_SET_LEN - 1)];
			}
			
			$this->talkMsgSend($text, true);
			
			$this->randomMsgDebug();
		}
	}
	
	private function randomMsgSetNextTime(){
		$this->nextRandomMsg = time() + mt_rand(static::RANDOM_MSG_DELAY_MIN, static::RANDOM_MSG_DELAY_MAX);
	}
	
	private function randomMsgDebug(){
		$dt = new DateTime();
		$dt->setTimestamp($this->nextRandomMsg);
		
		$this->log->debug('next random msg: '.$dt->format('Y/m/d H:i:s'));
	}
	
	private function historyAdd($line){
		if(count($this->history) >= static::HISTORY_MAX){
			array_pop($this->history);
		}
		
		array_unshift($this->history, $line);
	}
	
}
