<?php

namespace TheFox\PhpChat;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\Dht\Kademlia\Table;
use TheFox\Dht\Kademlia\Node;
use TheFox\Ipc\ConnectionServer;
use TheFox\Ipc\StreamHandler as IpcStreamHandler;
use TheFox\Pow\HashcashDb;

class Kernel extends Thread{
	
	const LOOP_USLEEP = 10000;
	
	private $log;
	private $settings;
	private $localNode;
	private $table;
	private $addressbook;
	private $msgDb;
	private $hashcashDb;
	private $server;
	private $ipcConsoleConnection = null;
	private $ipcConsoleShutdown = false;
	private $ipcCronjobConnection = null;
	private $ipcImapServerConnection = null;
	
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
		
		$this->getLog()->info('setup hashcashDb');
		$this->hashcashDb = new HashcashDb($this->settings->data['datadir'].'/hashcashdb.yml');
		$this->hashcashDb->setDatadirBasePath($this->settings->data['datadir']);
		$load = $this->hashcashDb->load();
		$this->getLog()->info('setup hashcashDb: done ('.(int)$load.')');
		
		$this->getLog()->info('setup server');
		$this->server = new Server();
		$this->server->setKernel($this);
		$this->server->setIp($this->settings->data['node']['ip']);
		$this->server->setPort($this->settings->data['node']['port']);
		$this->server->setSslPrv($this->settings->data['node']['sslKeyPrvPath'],
			$this->settings->data['node']['sslKeyPrvPass']);
		$init = $this->server->init();
		$this->getLog()->info('setup server: done');
		if(!$init){
			#print __CLASS__.'->'.__FUNCTION__.': failed'."\n";
			$this->setExit(1);
		}
		
		// Console Connection
		$this->getLog()->info('setup console connection');
		$this->ipcConsoleConnection = new ConnectionServer();
		$this->ipcConsoleConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20000));
		$this->ipcConsoleConnection->functionAdd('shutdown', $this, 'ipcConsoleShutdown');
		foreach(array(
			'getSettingsUserNickname', 'setSettingsUserNickname',
			'serverConnect', 'serverTalkResponseSend', 'serverTalkMsgSend', 'serverTalkUserNicknameChangeSend',
				'serverTalkCloseSend',
			'getAddressbook', 'addressbookContactAdd', 'addressbookContactRemove',
			'getMsgDb', 'msgDbMsgAdd', 'msgDbMsgUpdate', 'msgDbMsgGetMsgsForDst', 'getSettings', 'getLocalNode', 'getTable', 'save', 
		) as $functionName){
			$this->ipcConsoleConnection->functionAdd($functionName, $this, $functionName);
		}
		$this->ipcConsoleConnection->connect();
		
		// Cronjob Connection
		$this->getLog()->info('setup cronjob connection');
		$this->ipcCronjobConnection = new ConnectionServer();
		$this->ipcCronjobConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20001));
		foreach(array(
			'getSettings', 'getLocalNode', 'getTable',
			'getMsgDb', 'msgDbMsgUpdate', 'msgDbMsgIncForwardCyclesById', 'msgDbMsgSetStatusById',
			'serverConnect', 'save', 
		) as $functionName){
			$this->ipcCronjobConnection->functionAdd($functionName, $this, $functionName);
		}
		$this->ipcCronjobConnection->connect();
		
		// IMAP Server Connection
		$this->getLog()->info('setup mailserver connection');
		$this->ipcImapServerConnection = new ConnectionServer();
		$this->ipcImapServerConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20002));
		foreach(array(
			'getSettings'
		) as $functionName){
			$this->ipcImapServerConnection->functionAdd($functionName, $this, $functionName);
		}
		$this->ipcImapServerConnection->connect();
		
		
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
	
	public function serverConnect($ip, $port, $isTalkRequest = false, $isPingOnly = false, $msgIds = array()){
		if($this->getServer() && $ip && $port){
			print __CLASS__.'->'.__FUNCTION__.': '.$ip.':'.$port."\n";
			
			$clientActions = array();
			if($isTalkRequest){
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_HELLO);
				$action->functionSet(function($action, $client){
					$client->setStatus('isChannelLocal', true);
				});
				$clientActions[] = $action;
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_SUCCESSFULL);
				$action->functionSet(function($action, $client){
					$client->sendSslInit();
				});
				$clientActions[] = $action;
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_HAS_SSL);
				$action->functionSet(function($action, $client){
					$this->ipcConsoleMsgSend('Sening talk request to '.$client->getIpPort().' ...', true, false);
					$client->sendTalkRequest($this->getSettingsUserNickname());
					$this->ipcConsoleMsgSend('Talk request sent to '.$client->getIpPort().'. Waiting for response ...', true, true);
				});
				$clientActions[] = $action;
			}
			
			if($isPingOnly){
				$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_SUCCESSFULL);
				$action->functionSet(function($action, $client){
					$client->sendQuit();
				});
				$clientActions[] = $action;
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_PREVIOUS_ACTIONS);
				$action->functionSet(function($action, $client){
					$client->shutdown();
				});
				$clientActions[] = $action;
			}
			
			if($msgIds){
				
				#print __CLASS__.'->'.__FUNCTION__.''."\n"; ve($msgIds);
				
				$msgs = array();
				foreach($msgIds as $msgId){
					$msg = $this->getMsgDb()->getMsgById($msgId);
					if($msg){
						$msgs[] = $msg;
					}
				}
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_SUCCESSFULL);
				$action->functionSet(function($action, $client){
					#print __CLASS__.'->'.__FUNCTION__.': send msgs'."\n";
					
					$msgs = $action->getVar('msgs');
					foreach($msgs as $msgId => $msg){
						#print __CLASS__.'->'.__FUNCTION__.': send msg '.$msg->getId()."\n";
						$client->sendMsg($msg);
					}
				}, array('msgs' => $msgs));
				$clientActions[] = $action;
				
				// Wait to get response. Don't disconnect instantly after sending.
				foreach($msgs as $msgId => $msg){
					$action = new ClientAction(ClientAction::CRITERION_AFTER_MSG_RESPONSE);
					$action->functionSet(function($action, $client){
						#print __CLASS__.'->'.__FUNCTION__.': CRITERION_AFTER_MSG_RESPONSE'."\n";
					});
					$clientActions[] = $action;
				}
				
				$action = new ClientAction(ClientAction::CRITERION_AFTER_PREVIOUS_ACTIONS);
				$action->functionSet(function($action, $client){
					#print __CLASS__.'->'.__FUNCTION__.': shutdown'."\n";
					
					$client->sendQuit();
					$client->shutdown();
				});
				$clientActions[] = $action;
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
	
	public function msgDbMsgGetMsgsForDst($node = null){
		if($node === null){
			return $this->getMsgDb()->getMsgsForDst($this->getLocalNode());
		}
		return $this->getMsgDb()->getMsgsForDst($node);
	}
	
	public function msgDbMsgIncForwardCyclesById($msgId){
		$msgs = $this->getMsgDb()->getMsgs();
		if(isset($msgs[$msgId])){
			$msg = $msgs[$msgId];
			$msg->incForwardCycles();
		}
	}
	
	public function msgDbMsgSetStatusById($msgId, $status){
		$msgs = $this->getMsgDb()->getMsgs();
		if(isset($msgs[$msgId])){
			$msg = $msgs[$msgId];
			$msg->setStatus($status);
		}
	}
	
	public function getHashcashDb(){
		return $this->hashcashDb;
	}
	
	public function getIpcConsoleConnection(){
		return $this->ipcConsoleConnection;
	}
	
	public function getIpcImapConnection(){
		return $this->ipcImapServerConnection;
	}
	
	public function run(){
		$this->server->run();
		$this->ipcConsoleConnection->run();
		$this->ipcCronjobConnection->run();
		$this->ipcImapServerConnection->run();
	}
	
	public function loop(){
		while(!$this->getExit()){
			$this->run();
			usleep(static::LOOP_USLEEP);
		}
		$this->shutdown();
	}
	
	public function save(){
		$this->getTable()->save();
		$this->getAddressbook()->save();
		$this->getMsgDb()->setDataChanged(true);
		$this->getMsgDb()->save();
		$this->getHashcashDb()->setDataChanged(true);
		$this->getHashcashDb()->save();
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
		
		$this->getLog()->info('IPC IMAP send shutdown');
		$this->ipcImapServerConnection->execSync('shutdown');
		#print __CLASS__.'->'.__FUNCTION__.': ipcCronjobConnection send done'."\n";
		
		$nodesNum = (int)$this->getTable()->getNodesNum();
		$firstRun = (int)$this->getSettings()->data['firstRun'];
		$this->getLog()->debug('getNodesNum: '.$nodesNum.', '.$firstRun);
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
	
	public function ipcConsoleMsgSend($msgText = '', $showDate = false, $printPs1 = false, $clearLine = false){
		if($this->getIpcConsoleConnection()){
			$this->getIpcConsoleConnection()->execAsync('msgAdd', array($msgText, $showDate, $printPs1, $clearLine));
		}
	}
	
}
