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
	
	const LOOP_USLEEP = 100000;
	const CHAR_ESCAPE = "\x1b";
	const CHAR_BACKSPACE = "\x7f";
	const CHAR_EOF = "\x04";
	const RANDOM_MSG_DELAY_MIN = 30;
	const RANDOM_MSG_DELAY_MAX = 300;
	const RANDOM_MSG_CHAR_SET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
	const RANDOM_MSG_CHAR_SET_LEN = 58;
	
	private $log = null;
	private $ipcKernelConnection = null;
	private $ipcKernelShutdown = false;
	private $ps1 = 'phpchat:> ';
	private $tcols = 0;
	private $tlines = 0;
	private $msgStack = array();
	private $buffer = '';
	private $modeChannel = false;
	private $modeChannelClient = null;
	private $userNickname = '';
	private $talkRequestsId = 0;
	private $talkRequests = array();
	private $nextRandomMsg = 0;
	private $sttySettings = '';
	private $history = array();
	
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
	
	public function printPs1($debug = ''){
		$this->log->debug('printPs1');
		
		if($this->getModeChannel()){
			print '"'.$debug.'"" '.$this->settings['phpchat']['user']['nickname'].':> _'.$this->buffer.'_';
			#print $this->userNickname.':> '.$this->buffer;
		}
		else{
			print '"'.$debug.'" '.$this->getPs1().' _'.$this->buffer.'_';
			#print $this->getPs1().$this->buffer;
		}
	}
	
	private function cursorUp(){
		print static::CHAR_ESCAPE.'[1A';
	}
	
	private function cursorJumpToTop(){
		print static::CHAR_ESCAPE.'[1;1f';
	}
	
	private function lineClear(){
		#$this->log->debug('line clear');
		print "\r".static::CHAR_ESCAPE.'[K';
	}
	
	private function scrollUp(){
		#$this->log->debug('scrollUp');
		print static::CHAR_ESCAPE.'[S';
	}
	
	private function scrollDown(){
		#$this->log->debug('scrollDown');
		print static::CHAR_ESCAPE.'[T';
	}
	
	private function printHistory(){
		$this->log->debug('line clear');
		
		#$this->cursorJumpToTop();
		
		$n = 0;
		foreach($this->history as $hline){
			$n++;
			$this->lineClear();
			
			print $n.'  "'.$hline.'"'.PHP_EOL;
			usleep(50000);
			
			if($this->getExit()) break;
		}
	}
	
	private function linePrint($text){
		$this->log->debug('line print "'.$text.'"');
		print $text.PHP_EOL;
		
		/*
		if(count($this->history) >= $this->tlines - 1){
			$this->log->debug('shift history');
			array_shift($this->history);
		}
		$this->history[] = $text;
		*/
	}
	
	private function printMsgStack(){
		#$this->log->debug('printMsgStack');
		
		#$this->lineClear();
		if($this->msgStack){
			$this->log->debug('printMsgStack');
			
			foreach($this->msgStack as $msgId => $msg){
				$this->linePrint($this->getDate().' '.$msg['text']);
			}
			$this->msgStack = array();
			
			$this->printPs1('printMsgStack');
		}
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
		
		$this->sttySetup();
		
		$this->userNickname = $this->getIpcKernelConnection()->execSync('getSettingsUserNickname');
		
		print PHP_EOL."Type '/help' for help.".PHP_EOL;
		
		$this->msgAdd('start');
		#$this->printPs1('init');
		
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
		
		exec('stty -echo -icanon');
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
			
			#print __CLASS__.'->'.__FUNCTION__.': '.$this->getExit()."\n";
			
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
				
				#print "buffer: ".$bufferLen." '".substr($buffer, 0, -1)."'\n";
				
				#$this->log->debug('buffer: '.ord($buffer[0]));
				$bufferHex = ''; for($n = 0; $n < $bufferLen; $n++){ $bufferHex .= sprintf('%02x ', ord($buffer[$n])); }
				$this->log->debug('user input raw: '.$bufferHex.'');
				
				for($bufferIndex = 0; $bufferIndex < $bufferLen && !$this->getExit(); $bufferIndex++){
					$char = $buffer[$bufferIndex];
					
					if($char == PHP_EOL){
						#$this->log->debug('EOL');
						$line = $this->buffer;
						$this->buffer = '';
						$this->handleLine($line);
					}
					elseif($char == static::CHAR_EOF){
						$this->log->debug('break: EOF');
						$this->setExit(1);
						print "\nexit\n";
						break;
					}
					elseif($char == static::CHAR_BACKSPACE){
						$this->log->debug('got backspace');
						
						#print chr(8).chr(8).chr(8).'   '.chr(8).chr(8).chr(8);
						#flush();
						if($this->buffer){
							print chr(8);
							#print chr(static::CHAR_BACKSPACE);
							#print static::CHAR_BACKSPACE;
							$this->buffer = substr($this->buffer, 0, -1);
						}
						$this->log->debug('buffer "'.$this->buffer.'"');
					}
					else{
						print $char;
						$this->buffer .= $char;
						$this->log->debug('buffer "'.$this->buffer.'"');
					}
				}
			}
		}
		#else{ $this->log->debug('stream not changed'); }
	}
	
	private function handleLine($line){
		if($line){
			$this->log->debug('user input line: "'.$line.'"');
			
			if($line[0] == '/'){
				$line = substr($line, 1);
				if($line == 'help'){
					$this->handleCommandHelp();
				}
				elseif(substr($line, 0, 8) == 'connect '){
					$this->handleCommandConnect($line);
				}
				elseif($line == 'ab'){ # TODO
					print ' ID UUID                                  USERNAME'.PHP_EOL;
					foreach($this->getIpcKernelConnection()->execSync('getAddressbook')->getContacts() as $contactId => $contact){
						printf('%3d %36s  %s'.PHP_EOL, $contact->getId(), $contact->getNodeId(), $contact->getUserNickname());
					}
					$this->printPs1('handleLine ab A');
				}
				elseif(substr($line, 0, 3) == 'ab '){
					$this->handleCommandAddressbook($line);
				}
				elseif($line == 'talk' || $line == 'talk '){ # TODO
					print 'Usage: /talk <NICK|UUID>'.PHP_EOL;
					$this->printPs1('handleLine talk A');
				}
				elseif(substr($line, 0, 5) == 'talk '){
					$this->handleCommandTalk($line);
				}
				elseif($line == 'request'){ # TODO
					$format = '%3d %36s  %s %s'.PHP_EOL;
					print ' ID RID                                   IP:PORT               USERNAME'.PHP_EOL;
					foreach($this->talkRequests as $talkRequestId => $request){
						$rid = substr($request->getRid(), 0, 36);
						$ipPortStr = $request->getClient()->getIp().':'.$request->getClient()->getPort();
						$ipPortStrLen = strlen($ipPortStr);
						$ip = $ipPortStr.str_repeat(' ', 21 - $ipPortStrLen);
						
						printf($format, $request->getId(), $rid, $ip, $request->getUserNickname());
					}
					$this->printPs1('handleLine request');
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
					$this->msgAdd('Your nickname: '.$this->userNickname);
				}
				elseif(substr($line, 0, 5) == 'nick '){
					$this->handleCommandNick($line);
				}
				elseif($line == 'save'){
					$this->handleCommandSave();
				}
				elseif($line == 'exit'){
					$this->handleCommandExit();
				}
				elseif($line == 't'){
					$this->handleCommandTest();
				}
				else{
					$this->printPs1('handleLine else');
				}
			}
			else{
				if($this->getModeChannel()){
					$this->lineClear();
					$this->cursorUp();
					$this->talkMsgAdd(0, $this->userNickname, $line);
					$this->talkMsgSend($line);
				}
				else{
					$this->log->debug('do nothing B');
					
					#sleep(1);
					$this->lineClear();
					
					#sleep(1);
					$this->printPs1('handleLine else B');
				}
			}
		}
		else{
			#sleep(1);
			#$this->scrollDown();
			#$this->cursorUp();
			
			#sleep(1);
			#$this->lineClear();
			
			#sleep(1);
			#$this->printPs1('handleLine else A');
			
			$this->log->debug('do nothing A');
		}
	}
	
	private function handleCommandHelp(){
		$help = '';
		$help .= '/connect <IP> <PORT>      - open a connection'.PHP_EOL;
		$help .= '/ab                       - address book: list nicks'.PHP_EOL;
		$help .= '/ab rem <ID>              - address book: remove contact'.PHP_EOL;
		$help .= '/talk <NICK|UUID>         - open a connection to a know nick'.PHP_EOL;
		$help .= '/request                  - list all talk requests'.PHP_EOL;
		$help .= '/request accept <ID>      - accept  a talk request'.PHP_EOL;
		$help .= '/request decline <ID>     - decline a talk request'.PHP_EOL;
		$help .= '/close                    - close talk'.PHP_EOL;
		$help .= '/msg                      - list msgs'.PHP_EOL;
		$help .= '/msg new <UUID>           - send a msg to a uuid'.PHP_EOL;
		$help .= '/msg read <NO|ID>         - read a msg'.PHP_EOL;
		$help .= '/nick                     - print your nickname'.PHP_EOL;
		$help .= '/nick <NICK>              - set a new nickname'.PHP_EOL;
		$help .= '/exit                     - exit this programm'.PHP_EOL;
		
		print $help;
		$this->printPs1('handleCommandHelp');
	}
	
	private function handleCommandConnect($line){
		$data = substr($line, 8);
		$ip = '';
		$port = 0;
		
		$pos = strpos($data, ' ');
		if($pos === false){
			print 'Usage: /connect <IP> <PORT>'.PHP_EOL.'/connect 192.168.241.10 25000'.PHP_EOL;
			$this->printPs1('handleCommandConnect A');
		}
		else{
			$ip = substr($data, 0, $pos);
			$port = (int)substr($data, $pos + 1);
			$portMax = 0xffff;
			
			if($port <= $portMax){
				$this->connect($ip, $port);
			}
			else{
				print 'ERROR: Port can not be bigger than '.$portMax.'.'.PHP_EOL;
				$this->printPs1('handleCommandConnect C');
			}
		}
	}
	
	private function handleCommandAddressbook($line){
		$data = substr($line, 3);
		
		$pos = strpos($data, ' ');
		if($pos === false){
			print 'Usage: /ab rem <ID>'.PHP_EOL;
			$this->printPs1('handleCommandAddressbook A');
		}
		else{
			$action = substr($data, 0, $pos);
			$id = substr($data, $pos + 1);
			
			if($action == 'rem'){
				if($this->getIpcKernelConnection()->execSync('addressbookContactRemove', array($id))){
					$this->msgAdd('Removed '.$id.' from addressbook.');
				}
				else{
					print 'ERROR: Can not remove '.$id.' from addressbook.'.PHP_EOL;
					$this->printPs1('handleCommandAddressbook B');
				}
			}
			else{
				print 'ERROR: Command "'.$action.'" not found.'.PHP_EOL;
				$this->printPs1('handleCommandAddressbook C');
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
				print 'Found several nodes with nickname "'.$data.'". ';
				print 'Delete old nodes or use UUID instead.'.PHP_EOL.PHP_EOL;
				print ' ID UUID                                  USERNAME'.PHP_EOL;
				foreach($contacts as $contactId => $contact){
					printf('%3d %36s  %s'.PHP_EOL, $contact->getId(), $contact->getNodeId(), $contact->getUserNickname());
				}
				$this->printPs1('handleCommandTalk A');
			}
			elseif(count($contacts) == 1){
				$contact = array_shift($contacts);
				$uuid = $contact->getNodeId();
			}
			else{
				print 'ERROR: Nick "'.$data.'" not found.'.PHP_EOL;
				$this->printPs1('handleCommandTalk B');
			}
		}
		
		if($uuid){
			$node = new Node();
			$node->setIdHexStr($uuid);
			
			if($onode = $this->getIpcKernelConnection()->execSync('getTable')->nodeFindInBuckets($node)){
				$this->connect($onode->getIp(), $onode->getPort());
			}
			else{
				print 'ERROR: Node '.$node->getIdHexStr().' not found.'.PHP_EOL;
				$this->printPs1('handleCommandTalk C');
			}
		}
	}
	
	private function handleCommandRequest($line){
		$data = substr($line, 8);
		
		$pos = strpos($data, ' ');
		if($pos === false){
			print 'Usage: /request accept <ID>'.PHP_EOL.'       /request decline <ID>'.PHP_EOL;
		}
		else{
			$action = substr($data, 0, $pos);
			$id = substr($data, $pos + 1);
			
			if(isset($this->talkRequests[$id])){
				$talkRequest = $this->talkRequests[$id];
				
				if($talkRequest->getStatus() == 0){
					if($action == 'accept'){
						$talkRequest->setStatus(1);
						
						$msgText = 'Accepting talk request ID '.$talkRequest->getId().'.'.PHP_EOL;
						$msgText .= 'Now talking to "'.$talkRequest->getUserNickname().'".';
						$this->msgAdd($msgText);
						
						$this->setModeChannel(true);
						$this->setModeChannelClient($talkRequest->getClient());
					}
					else{
						$talkRequest->getStatus(2);
						$this->msgAdd('Declining talk request ID '.$id.'.');
					}
					
					$this->talkResponseSend($talkRequest);
				}
				elseif($talkRequest->getStatus() == 1){
					$this->msgAdd('You already accepted this talk request.');
				}
				elseif($talkRequest->getStatus() == 2){
					$this->msgAdd('You already declined this talk request.');
				}
				elseif($talkRequest->getStatus() == 3){
					$this->msgAdd('Talk request ID '.$id.' timed-out.');
				}
			}
			else{
				#print $this->getDate()..PHP_EOL;
				$this->msgAdd('Talk request ID '.$id.' not found.');
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
			
			#$this->printPs1('handleCommandMsg A');
			
			if(($args[0] == 'new' || $args[0] == 'n')){
				if(strIsUuid($args[1])){
					print 'NOTE: end text with  <RETURN>.<RETURN>'.PHP_EOL;
					
					$text = '';
					#$this->sttyExitIcanonMode();
					#stream_set_blocking(STDIN, 1);
					while(true){
						$line = fgets(STDIN, 1024);
						
						#print "line: '".substr($line, 0, -1)."'\n";
						if(substr($line, 0, -1) == '.') break;
						$text .= $line;
						
						sleep(1);
					}
					print 'Send msg? [Y/n] ';
					
					$text = substr($text, 0, -1);
					
					$answer = strtolower(substr(fgets(STDIN, 100), 0, -1));
					if(!$answer){
						$answer = 'y';
					}
					print "Answer: '".$answer."'\n";
					print "Text: '".$text."'\n";
					
					#stream_set_blocking(STDIN, 0);
					#$this->sttyEnterIcanonMode();
					
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
							print 'found node in table'.PHP_EOL;
							$msg->setDstSslPubKey($oDstNode->getSslKeyPub());
						}
						else{ print 'node not found'.PHP_EOL; }
						
						$msg->setText($text);
						$msg->setSslKeyPrvPath($settings->data['node']['sslKeyPrvPath'], $settings->data['node']['sslKeyPrvPass']);
						$msg->setStatus('O');
						
						$encrypted = false;
						print 'DstSslPubKey: '.strlen($msg->getDstSslPubKey()).PHP_EOL;
						if($msg->getDstSslPubKey()){
							print 'use dst key'.PHP_EOL;
							
							$msg->setEncryptionMode('D');
						}
						else{
							// Encrypt with own public key
							// while destination public key is not available.
							print 'use local key'.PHP_EOL;
							
							$msg->setEncryptionMode('S');
							$msg->setDstSslPubKey($table->getLocalNode()->getSslKeyPub());
						}
						
						try{
							$encrypted = $msg->encrypt();
						}
						catch(Exception $e){
							print 'ERROR: '.$e->getMessage().PHP_EOL;
						}
						
						if($encrypted){
							$this->getIpcKernelConnection()->execAsync('msgDbMsgAdd', array($msg));
						}
						else{
							print 'ERROR: Could not encrypt msg.'.PHP_EOL;
						}
					}
				}
				else{
					print 'ERROR: "'.$args[1].'" is not a UUID.'.PHP_EOL;
				}
				
				$this->printPs1('handleCommandMsg B');
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
						
						if(!$text){
							print 'WARNING: could not decrypt text. Only meta data available.'.PHP_EOL;
						}
						print 'ID: '.$msg->getId().PHP_EOL;
						print 'From: '.( $msg->getSrcUserNickname() ? $msg->getSrcUserNickname().' ' : '').'<'.$msg->getSrcNodeId().'>'.PHP_EOL;
						print 'To: '.($table->getLocalNode()->getIdHexStr() == $msg->getDstNodeId() ? 'Me ' : '').'<'.$msg->getDstNodeId().'>'.PHP_EOL;
						print 'Status: '.$msg->getStatus().PHP_EOL;
						print 'Date: '.$dateCreated->format('Y/m/d H:i:s').PHP_EOL;
						
						if($text){
							print PHP_EOL;
							print $text.PHP_EOL;
							
							$msg->setStatus('R');
							$this->getIpcKernelConnection()->execAsync('msgDbMsgUpdate', array($msg));
						}
						
						$this->printPs1('handleCommandMsg C');
					}
					else{
						print 'ERROR: could not read msg "'.$args[1].'"'.PHP_EOL;
						$this->printPs1('handleCommandMsg D');
					}
				}
				else{
					print 'ERROR: you must specify a msg number or ID'.PHP_EOL;
					$this->printPs1('handleCommandMsg E');
				}
			}
		}
		else{
			$format = '%3d %36s  %s'.PHP_EOL;
			print ' NO ID                                    STATUS'.PHP_EOL;
			
			$no = 0;
			#foreach($msgDb->getMsgs() as $msgId => $msg){
			foreach($msgs as $msgId => $msg){
				$no++;
				printf($format, $no, $msg->getId(), $msg->getStatus());
			}
			
			$this->printPs1('handleCommandMsg F');
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
			
			$this->msgAdd('New nickname: '.$this->userNickname);
			
			if($this->getModeChannel()){
				$this->talkUserNicknameChangeSend($userNicknameOld, $this->userNickname);
			}
		}
		else{
			$this->msgAdd('Your nickname: '.$this->userNickname);
		}
	}
	
	private function handleCommandSave(){
		$this->getIpcKernelConnection()->execAsync('save');
		$this->printPs1('handleCommandSave');
	}
	
	private function handleCommandExit(){
		$this->setExit(1);
	}
	
	private function handleCommandTest(){
		/*$this->linePrint('line A');
		$this->linePrint('line B');
		$this->linePrint('line C');*/
		#$this->printHistory();
		
		$this->msgAdd('line A');
		$this->msgAdd('line B');
		$this->msgAdd('line C');
		
	}
	
	public function shutdown(){
		#print __CLASS__.'->'.__FUNCTION__.': '.(int)$this->ipcKernelShutdown."\n";
		$this->getLog()->info('shutdown');
		
		$this->sttyReset();
		
		#fclose(STDIN);
		
		if(!$this->ipcKernelShutdown){
			#$this->getIpcKernelConnection()->execSync('shutdown'); # TODO
		}
	}
	
	public function ipcKernelShutdown(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log->info('Connection to kernel process closed.');
		$this->setExit(1);
		$this->ipcKernelShutdown = true;
		
		return null;
	}
	
	public function connect($ip, $port){
		$this->msgAdd('Connecting to '.$ip.':'.$port.' ...');
		$connected = $this->getIpcKernelConnection()->execSync('serverConnect', array($ip, $port, true));
		$this->msgAdd('Connection to '.$ip.':'.$port.' '.($connected ? 'established' : 'failed').'.');
		
		$this->printPs1('connect');
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
		
		$msgText = 'User "'.$talkRequest->getUserNickname().'" wants to talk to you. ';
		$msgText .= 'Type "/request accept '.$talkRequest->getId().'" to get in touch.';
		$this->msgAdd($msgText);
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
		
		$this->msgAdd('<'.$userNickname.'> '.$text);
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
	
}
