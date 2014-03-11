<?php

namespace TheFox\PhpChat;

use RuntimeException;
use DateTime;
use DateTimeZone;

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
	private $ps1 = 'phpchat:> ';
	private $tcols = 0;
	private $tlines = 0;
	private $stdin = null;
	private $msgStack = array();
	private $buffer = '';
	
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
	
	public function init(){
		$this->setIpcKernelConnection(new ConnectionClient());
		$this->getIpcKernelConnection()->setHandler(new IpcStreamHandler('127.0.0.1', 20000));
		
		if(!$this->getIpcKernelConnection()->connect()){
			throw new RuntimeException('Could not connect to kernel process.');
		}
		
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log->debug('tty setup');
		#system('stty -icanon && echo icanon ok');
		system('stty -icanon');
		
		$this->stdin = fopen('php://stdin', 'r');
		stream_set_blocking($this->stdin, 0);
		
		print PHP_EOL."Type '/help' for help.".PHP_EOL;
		
		return true;
	}
	
	public function run(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(!$this->getIpcKernelConnection()){
			throw new RuntimeException('You must first run init().');
		}
		
		while(!$this->getExit()){
			#print __CLASS__.'->'.__FUNCTION__.': '.$this->getExit()."\n";
			$this->readStdin();
			$this->printMsgStack();
			
			$this->getIpcKernelConnection()->run();
			
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
						$this->log->debug('buffer "'.$this->buffer.'"');
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
						.PHP_EOL."/request decl <ID>        - decline a talk request"
						.PHP_EOL."/close                    - close talk"
						.PHP_EOL."/nick                     - print your nickname"
						.PHP_EOL."/nick <NICK>              - set a new nickname"
						.PHP_EOL."/exit                     - exit this programm"
						.PHP_EOL.''
					);
				}
				elseif($line == 'exit'){
					print "\nexit\n";
					$this->setExit(1);
				}
				
			}
		}
	}
	
	private function printMsgStack(){
		if($this->msgStack){
			$this->lineClear();
			foreach($this->msgStack as $msgId => $msg){
				$this->linePrint($msg['text']);
			}
		}
	}
	
	public function shutdown(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->getLog()->info('shutdown');
		
		fclose($this->stdin);
		
		$this->log->debug('tty restore');
		system('stty sane');
		
	}
	
}
