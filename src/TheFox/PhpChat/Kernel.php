<?php

namespace TheFox\PhpChat;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\Dht\Kademlia\Table;
use TheFox\Dht\Kademlia\Node;
use TheFox\Ipc\ConnectionServer;
use TheFox\Ipc\StreamHandler as IpcStreamHandler;

class Kernel extends Thread{
	
	const LOOP_USLEEP = 100000;
	
	private $log;
	private $settings;
	private $localNode;
	private $server;
	private $table;
	private $ipcConsoleConnection = null;
	private $ipcConsoleShutdown = false;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log = new Logger('kernel');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new LoggerStreamHandler('log/kernel.log', Logger::DEBUG));
		
		$this->getLog()->info('setup settings');
		$this->settings = new Settings(getcwd().'/settings.yml');
		$this->getLog()->info('setup settings: done');
		
		$this->getLog()->info('setup local node');
		$this->localNode = new Node();
		$this->localNode->setIdHexStr($this->settings->data['node']['id']);
		$this->localNode->setPort($this->settings->data['node']['port']);
		$this->localNode->setSslKeyPub(file_get_contents($this->settings->data['node']['sslKeyPubPath']));
		$this->getLog()->info('setup local node: done');
		
		$this->getLog()->info('setup table');
		$this->table = new Table($this->settings->data['datadir'].'/table.yml');
		$this->table->setDatadirBasePath($this->settings->data['datadir']);
		$this->table->setLocalNode($this->getLocalNode());
		$load = $this->table->load();
		$this->getLog()->info('setup table: done ('.(int)$load.')');
		
		$this->getLog()->info('setup server');
		$this->server = new Server();
		$this->server->setKernel($this);
		$this->server->setIp($this->settings->data['node']['ip']);
		$this->server->setPort($this->settings->data['node']['port']);
		$this->server->setSslPrv($this->settings->data['node']['sslKeyPrvPath'], $this->settings->data['node']['sslKeyPrvPass']);
		$init = $this->server->init();
		$this->getLog()->info('setup server: done');
		if(!$init){
			#print __CLASS__.'->'.__FUNCTION__.': failed'."\n";
			$this->setExit(1);
		}
		
		$this->ipcConsoleConnection = new ConnectionServer();
		$this->ipcConsoleConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20000));
		$this->ipcConsoleConnection->functionAdd('shutdown', $this, 'ipcConsoleShutdown');
		$this->ipcConsoleConnection->functionAdd('getSettingsUserNickname', $this, 'getSettingsUserNickname');
		$this->ipcConsoleConnection->functionAdd('setSettingsUserNickname', $this, 'setSettingsUserNickname');
		$this->ipcConsoleConnection->functionAdd('serverConnect', $this, 'serverConnect');
		$this->ipcConsoleConnection->connect();
		
		
		#ve($this->server);
		#$this->shutdown();
		
	}
	
	private function getLog(){
		return $this->log;
	}
	
	public function getSettings(){
		#ve($this->settings);
		return $this->settings;
	}
	
	public function getSettingsUserNickname(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		#ve($this->settings);
		
		return $this->getSettings()->data['user']['nickname'];
	}
	
	public function setSettingsUserNickname($userNickname){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$this->getSettings()->data['user']['nickname'] = $userNickname;
		$this->getSettings()->setDataChanged(true);
	}
	
	public function getLocalNode(){
		return $this->localNode;
	}
	
	private function getServer(){
		return $this->server;
	}
	
	public function serverConnect($ip, $port, $isTalkRequest = false){
		print __CLASS__.'->'.__FUNCTION__.': '.$ip.':'.$port."\n";
		
		if($this->getServer()){
			
			$clientActions = array();
			if($isTalkRequest){
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_HELLO);
				$action->functionSet(function($client){
					$client->setStatus('isChannelLocal', true);
				});
				$clientActions[] = $action;
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_OK);
				$action->functionSet(function($client){
					$client->sendSslInit();
				});
				$clientActions[] = $action;
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_HAS_SSL);
				$action->functionSet(function($client){
					$this->ipcConsoleMsgSend('Sening talk request to '.$client->getIpPort().' ...');
					$client->sendTalkRequest($this->getSettingsUserNickname());
					$this->ipcConsoleMsgSend('Talk request sent to '.$client->getIpPort().'. Waiting for response ...');
				});
				$clientActions[] = $action;
			}
			
			return $this->getServer()->connect($ip, $port, $clientActions);
		}
		
		return false;
	}
	
	public function serverTalkResponseSend(Client $client, $rid, $status, $userNickname = ''){
		print __CLASS__.'->'.__FUNCTION__.': '.$rid.', '.$status.', '.$userNickname."\n";
		
		if($this->getServer()){
			$this->getServer()->clientTalkResponseSend($client, $rid, $status, $userNickname);
		}
	}
	
	public function getTable(){
		return $this->table;
	}
	
	public function getIpcConsoleConnection(){
		return $this->ipcConsoleConnection;
	}
	
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		while(!$this->getExit()){
			#print __CLASS__.'->'.__FUNCTION__.': '.$this->getExit()."\n";
			
			$this->server->run();
			$this->ipcConsoleConnection->run();
			
			usleep(static::LOOP_USLEEP);
		}
		
		$this->shutdown();
	}
	
	public function shutdown(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->getLog()->info('shutdown');
		
		if($this->ipcConsoleShutdown){
			$this->ipcConsoleConnection->execAsync('shutdown');
		}
		else{
			$this->ipcConsoleConnection->execSync('shutdown');
		}
		
		$this->getLog()->debug('getNodesNum: '.(int)$this->getTable()->getNodesNum().', '.(int)$this->getSettings()->data['firstRun']);
		$this->getSettings()->data['firstRun'] = $this->getTable()->getNodesNum() <= 0;
		$this->getSettings()->setDataChanged(true);
		
		$this->getServer()->shutdown();
		$this->getTable()->save();
		$this->getSettings()->save();
	}
	
	public function ipcConsoleShutdown(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		$this->setExit(1);
		$this->ipcConsoleShutdown = true;
	}
	
	public function ipcConsoleMsgSend($msgText){
		if($this->getIpcConsoleConnection()){
			$this->getIpcConsoleConnection()->execAsync('msgAdd', array($msgText));
		}
	}
	
}
