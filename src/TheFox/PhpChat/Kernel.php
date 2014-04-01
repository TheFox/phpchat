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
	private $table;
	private $addressbook;
	private $msgDb;
	private $server;
	private $ipcConsoleConnection = null;
	private $ipcConsoleShutdown = false;
	private $ipcCronjobConnection = null;
	
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
		
		$this->getLog()->info('setup addressbook');
		$this->addressbook = new Addressbook($this->settings->data['datadir'].'/addressbook.yml');
		$this->addressbook->setDatadirBasePath($this->settings->data['datadir']);
		$load = $this->addressbook->load();
		$this->getLog()->info('setup addressbook: done ('.(int)$load.')');
		
		$this->getLog()->info('setup msgDb');
		$this->msgDb = new MsgDb($this->settings->data['datadir'].'/msgdb.yml');
		$this->msgDb->setDatadirBasePath($this->settings->data['datadir']);
		$load = $this->msgDb->load();
		$this->getLog()->info('setup msgDb: done ('.(int)$load.')');
		
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
		$this->ipcConsoleConnection->functionAdd('serverTalkResponseSend', $this, 'serverTalkResponseSend');
		$this->ipcConsoleConnection->functionAdd('serverTalkMsgSend', $this, 'serverTalkMsgSend');
		$this->ipcConsoleConnection->functionAdd('serverTalkUserNicknameChangeSend', $this, 'serverTalkUserNicknameChangeSend');
		$this->ipcConsoleConnection->functionAdd('serverTalkCloseSend', $this, 'serverTalkCloseSend');
		$this->ipcConsoleConnection->functionAdd('getAddressbook', $this, 'getAddressbook');
		$this->ipcConsoleConnection->functionAdd('addressbookContactAdd', $this, 'addressbookContactAdd');
		$this->ipcConsoleConnection->functionAdd('addressbookContactRemove', $this, 'addressbookContactRemove');
		$this->ipcConsoleConnection->functionAdd('msgDbMsgAdd', $this, 'msgDbMsgAdd');
		$this->ipcConsoleConnection->functionAdd('getSettings', $this, 'getSettings');
		$this->ipcConsoleConnection->functionAdd('getLocalNode', $this, 'getLocalNode');
		$this->ipcConsoleConnection->functionAdd('getTable', $this, 'getTable');
		$this->ipcConsoleConnection->functionAdd('save', $this, 'save');
		$this->ipcConsoleConnection->connect();
		
		$this->ipcCronjobConnection = new ConnectionServer();
		$this->ipcCronjobConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20001));
		$this->ipcCronjobConnection->functionAdd('getSettings', $this, 'getSettings');
		$this->ipcCronjobConnection->functionAdd('getLocalNode', $this, 'getLocalNode');
		$this->ipcCronjobConnection->functionAdd('getTable', $this, 'getTable');
		$this->ipcCronjobConnection->functionAdd('tableNodeEnclose', $this, 'tableNodeEnclose');
		$this->ipcCronjobConnection->functionAdd('getMsgDb', $this, 'getMsgDb');
		$this->ipcCronjobConnection->functionAdd('msgDbMsgUpdate', $this, 'msgDbMsgUpdate');
		$this->ipcCronjobConnection->functionAdd('serverConnect', $this, 'serverConnect');
		$this->ipcCronjobConnection->functionAdd('save', $this, 'save');
		$this->ipcCronjobConnection->connect();
		
		
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
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
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
	
	public function serverConnect($ip, $port, $isTalkRequest = false, $isPingOnly = false, $msgId = null){
		if($this->getServer() && $ip && $port){
			print __CLASS__.'->'.__FUNCTION__.': '.$ip.':'.$port."\n";
			
			$clientActions = array();
			if($isTalkRequest){
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_HELLO);
				$action->functionSet(function($action, $client){
					$client->setStatus('isChannelLocal', true);
				});
				$clientActions[] = $action;
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_OK);
				$action->functionSet(function($action, $client){
					$client->sendSslInit();
				});
				$clientActions[] = $action;
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_HAS_SSL);
				$action->functionSet(function($action, $client){
					$this->ipcConsoleMsgSend('Sening talk request to '.$client->getIpPort().' ...');
					$client->sendTalkRequest($this->getSettingsUserNickname());
					$this->ipcConsoleMsgSend('Talk request sent to '.$client->getIpPort().'. Waiting for response ...');
				});
				$clientActions[] = $action;
			}
			
			if($isPingOnly){
				$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_OK);
				$action->functionSet(function($action, $client){
					#print __CLASS__.'->'.__FUNCTION__.': shutdown'."\n";
					$client->sendQuit();
					$client->shutdown();
				});
				$clientActions[] = $action;
			}
			
			if($msgId !== null){
				
				$msg = $this->getMsgDb()->getMsgById($msgId);
				if($msg){
					$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_OK);
					$action->functionSet(function($action, $client){
						$client->sendMsg($action->getVar('msg'));
					}, array('msg' => $msg));
					$clientActions[] = $action;
					
					$action = new ClientAction(ClientAction::CRITERION_AFTER_MSG_RESPONSE);
					$action->functionSet(function($action, $client){
						#print __CLASS__.'->'.__FUNCTION__.': shutdown'."\n";
						$msg = $action->getVar('msg');
						$client->sendQuit();
						$client->shutdown();
					}, array('msg' => $msg));
					$clientActions[] = $action;
				}
			}
			
			return $this->getServer()->connect($ip, $port, $clientActions);
		}
		
		return false;
	}
	
	public function serverTalkResponseSend(Client $client, $rid, $status, $userNickname = ''){
		#print __CLASS__.'->'.__FUNCTION__.': '.$rid.', '.$status.', '.$userNickname.''."\n";
		
		if($this->getServer()){
			$this->getServer()->clientTalkResponseSend($client, $rid, $status, $userNickname);
		}
	}
	
	public function serverTalkMsgSend(Client $client, $rid, $userNickname, $text, $ignore = false){
		#print __CLASS__.'->'.__FUNCTION__.': '.$rid.', '.$userNickname.', '.$text.', '.(int)$ignore."\n";
		
		if($this->getServer()){
			$this->getServer()->clientTalkMsgSend($client, $rid, $userNickname, $text, $ignore);
		}
	}
	
	public function serverTalkUserNicknameChangeSend(Client $client, $userNicknameOld, $userNicknameNew){
		if($this->getServer()){
			$this->getServer()->clientTalkUserNicknameChangeSend($client, $userNicknameOld, $userNicknameNew);
		}
	}
	
	public function serverTalkCloseSend(Client $client, $rid, $userNickname){
		#print __CLASS__.'->'.__FUNCTION__.': '.$rid.', '.$userNickname."\n";
		
		if($this->getServer()){
			$this->getServer()->clientTalkCloseSend($client, $rid, $userNickname);
		}
	}
	
	public function getTable(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		return $this->table;
	}
	
	public function tableNodeEnclose(Node $node){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#ve($node);
		
		$enode = $this->getTable()->nodeEnclose($node);
		
		#ve($enode);
	}
	
	public function getAddressbook(){
		return $this->addressbook;
	}
	
	public function addressbookContactAdd(Contact $contact){
		$this->addressbook->contactAdd($contact);
	}
	
	public function addressbookContactRemove($id){
		return $this->addressbook->contactRemove($id);
	}
	
	public function getMsgDb(){
		return $this->msgDb;
	}
	
	public function msgDbMsgAdd(Msg $msg){
		$this->getMsgDb()->msgAdd($msg);
	}
	
	public function msgDbMsgUpdate(Msg $msg){
		$this->getMsgDb()->msgUpdate($msg);
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
			$this->ipcCronjobConnection->run();
			
			usleep(static::LOOP_USLEEP);
		}
		
		$this->shutdown();
	}
	
	public function save(){
		$this->getTable()->save();
		$this->getAddressbook()->save();
		$this->getMsgDb()->setDataChanged(true);
		$this->getMsgDb()->save();
		$this->getSettings()->save();
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
		
		#print __CLASS__.'->'.__FUNCTION__.': ipcCronjobConnection send shutdown'."\n";
		$this->ipcCronjobConnection->execSync('shutdown');
		#print __CLASS__.'->'.__FUNCTION__.': ipcCronjobConnection send done'."\n";
		
		$this->getLog()->debug('getNodesNum: '.(int)$this->getTable()->getNodesNum().', '.(int)$this->getSettings()->data['firstRun']);
		$this->getSettings()->data['firstRun'] = $this->getTable()->getNodesNum() <= 0;
		$this->getSettings()->setDataChanged(true);
		
		$this->getServer()->shutdown();
		$this->save();
	}
	
	public function ipcConsoleShutdown(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$this->setExit(1);
		$this->ipcConsoleShutdown = true;
	}
	
	public function ipcConsoleMsgSend($msgText){
		if($this->getIpcConsoleConnection()){
			$this->getIpcConsoleConnection()->execAsync('msgAdd', array($msgText));
		}
	}
	
}
