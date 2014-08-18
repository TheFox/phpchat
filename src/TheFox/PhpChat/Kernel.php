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
	private $nodesNewDb;
	private $server;
	private $ipcConsoleConnection = null;
	private $ipcConsoleShutdown = false;
	private $ipcCronjobConnection = null;
	private $ipcImapServerConnection = null;
	private $ipcSmtpServerConnection = null;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
	}
	
	public function setLog($log){
		$this->log = $log;
	}
	
	public function getLog(){
		return $this->log;
	}
	
	public function setSettings(Settings $settings){
		$this->settings = $settings;
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
	
	public function getServer(){
		return $this->server;
	}
	
	public function init(){
		if(!$this->log){
			$this->log = new Logger('kernel');
			$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::ERROR));
			$this->log->pushHandler(new LoggerStreamHandler('log/kernel.log', Logger::DEBUG));
			
			$this->getLog()->info('start');
		}
		if(!$this->settings){
			$this->getLog()->info('setup settings');
			$this->settings = new Settings(getcwd().'/settings.yml');
			$this->getLog()->info('setup settings: done');
		}
		
		$this->getLog()->info('setup local node');
		$this->localNode = new Node();
		$this->localNode->setIdHexStr($this->settings->data['node']['id']);
		$this->localNode->setUri('tcp://'.$this->settings->data['node']['ip'].':'.$this->settings->data['node']['port']);
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
		
		$this->getLog()->info('setup nodesNewDb');
		$this->nodesNewDb = new NodesNewDb($this->settings->data['datadir'].'/nodesnewdb.yml');
		$this->nodesNewDb->setDatadirBasePath($this->settings->data['datadir']);
		$load = $this->nodesNewDb->load();
		$this->getLog()->info('setup nodesNewDb: done ('.(int)$load.')');
		
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
			'setSettingsUserNickname',
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
			'getSettings', 'getLocalNode',
			'getTable', 'tableNodeEnclose',
			'getMsgDb', 'msgDbMsgUpdate', 'msgDbMsgIncForwardCyclesById', 'msgDbMsgSetStatusById',
			'getNodesNewDb', 'nodesNewDbNodeAdd', 'nodesNewDbNodeIncConnectAttempt', 'nodesNewDbNodeRemove',
			'serverConnect', 'save', 
		) as $functionName){
			$this->ipcCronjobConnection->functionAdd($functionName, $this, $functionName);
		}
		$this->ipcCronjobConnection->connect();
		
		// IMAP Server Connection
		$this->getLog()->info('setup IPC IMAP server connection');
		$this->ipcImapServerConnection = new ConnectionServer();
		$this->ipcImapServerConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20002));
		foreach(array(
			'getSettings'
		) as $functionName){
			$this->ipcImapServerConnection->functionAdd($functionName, $this, $functionName);
		}
		$this->ipcImapServerConnection->connect();
		
		// SMTP Server Connection
		$this->getLog()->info('setup IPC SMTP server connection');
		$this->ipcSmtpServerConnection = new ConnectionServer();
		$this->ipcSmtpServerConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20003));
		foreach(array(
			'getSettings', 'getTable', 'msgDbMsgAdd'
		) as $functionName){
			$this->ipcSmtpServerConnection->functionAdd($functionName, $this, $functionName);
		}
		$this->ipcSmtpServerConnection->connect();
	}
	
	public function serverConnect($uri, $isTalkRequest = false, $isPingOnly = false, $msgIds = array()){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#ve($uri);
		
		if($this->getServer()){
			
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
					$this->ipcConsoleMsgSend('Sening talk request to '.$client->getUri().' ...', true, false);
					$client->sendTalkRequest($this->getSettingsUserNickname());
					$this->ipcConsoleMsgSend('Talk request sent to '.$client->getUri().'. Waiting for response ...', true, true);
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
			
			return $this->getServer()->connect($uri, $clientActions);
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
		#ve($node->getIdHexStr());
		return $this->getTable()->nodeEnclose($node);
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
		$this->ipcCronjobConnection->execAsync('msgDbInit');
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
	
	public function getNodesNewDb(){
		return $this->nodesNewDb;
	}
	
	public function nodesNewDbNodeAdd($uri){
		return $this->getNodesNewDb()->nodeAdd($uri);
	}
	
	public function nodesNewDbNodeIncConnectAttempt($id){
		return $this->getNodesNewDb()->nodeIncConnectAttempt($id);
	}
	
	public function nodesNewDbNodeRemove($id){
		return $this->getNodesNewDb()->nodeRemove($id);
	}
	
	public function getIpcConsoleConnection(){
		return $this->ipcConsoleConnection;
	}
	
	public function getIpcImapConnection(){
		return $this->ipcImapServerConnection;
	}
	
	public function getIpcSmtpConnection(){
		return $this->ipcSmtpServerConnection;
	}
	
	public function run(){
		$this->server->run();
		$this->ipcConsoleConnection->run();
		$this->ipcCronjobConnection->run();
		$this->ipcImapServerConnection->run();
		$this->ipcSmtpServerConnection->run();
	}
	
	public function loop(){
		$this->getLog()->info('loop start');
		while(!$this->getExit()){
			$this->run();
			usleep(static::LOOP_USLEEP);
		}
		$this->getLog()->info('loop end');
		$this->shutdown();
	}
	
	public function save(){
		$this->getTable()->save();
		
		$this->getAddressbook()->save();
		
		$this->getMsgDb()->setDataChanged(true);
		$this->getMsgDb()->save();
		
		$this->getHashcashDb()->setDataChanged(true);
		$this->getHashcashDb()->save();
		
		$this->getNodesNewDb()->setDataChanged(true);
		$this->getNodesNewDb()->save();
		
		$this->getSettings()->save();
	}
	
	public function shutdown(){
		$this->getLog()->info('shutdown');
		
		$this->getLog()->info('IPC Console send shutdown');
		if($this->ipcConsoleShutdown){
			$this->ipcConsoleConnection->execAsync('shutdown');
		}
		else{
			$this->ipcConsoleConnection->execSync('shutdown');
		}
		
		$this->getLog()->info('IPC Cronjob send shutdown');
		$this->ipcCronjobConnection->execSync('shutdown');
		
		$this->getLog()->info('IPC IMAP server send shutdown');
		$this->ipcImapServerConnection->execSync('shutdown');
		
		$this->getLog()->info('IPC SMTP server send shutdown');
		$this->ipcSmtpServerConnection->execSync('shutdown');
		
		
		$nodesNum = (int)$this->getTable()->getNodesNum();
		$firstRun = (int)$this->getSettings()->data['firstRun'];
		$this->getLog()->debug('nodes num: '.$nodesNum.', '.$firstRun);
		$this->getSettings()->data['firstRun'] = $this->getTable()->getNodesNum() <= 0;
		$this->getSettings()->setDataChanged(true);
		
		$this->getServer()->shutdown();
		$this->save();
		
		$this->getLog()->info('end');
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
