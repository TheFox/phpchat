<?php

namespace TheFox\PhpChat;

use TheFox\Yaml\YamlStorage;

class MsgDb extends YamlStorage{
	
	private $msgs = array();
	
	public function __construct($filePath = null){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		parent::__construct($filePath);
		
		$this->data['timeCreated'] = time();
	}
	
	public function __sleep(){
		return array('msgs');
	}
	
	public function save(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->data['msgs'] = array();
		foreach($this->msgs as $msgId => $msg){
			
			$msgAr = array();
			$msgAr['version'] = $msg->getVersion();
			$msgAr['id'] = $msg->getId();
			$msgAr['srcNodeId'] = $msg->getSrcNodeId();
			$msgAr['srcSslKeyPub'] = base64_encode($msg->getSrcSslKeyPub());
			$msgAr['dstNodeId'] = $msg->getDstNodeId();
			$msgAr['text'] = $msg->getText();
			$msgAr['password'] = $msg->getPassword();
			$msgAr['checksum'] = $msg->getChecksum();
			$msgAr['sentNodes'] = $msg->getSentNodes();
			$msgAr['relayCount'] = $msg->getRelayCount();
			$msgAr['encryptionMode'] = $msg->getEncryptionMode();
			$msgAr['timeCreated'] = $msg->getTimeCreated();
			
			$this->data['msgs'][$msgAr['id']] = $msgAr;
		}
		
		$rv = parent::save();
		unset($this->data['msgs']);
		
		return $rv;
	}
	
	public function load(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(parent::load()){
			
			if(isset($this->data['msgs']) && $this->data['msgs']){
				foreach($this->data['msgs'] as $msgId => $msgAr){
					
					$msg = new Msg();
					$msg->setVersion($msgAr['version']);
					$msg->setId($msgAr['id']);
					$msg->setSrcNodeId($msgAr['srcNodeId']);
					$msg->setSrcSslKeyPub(base64_decode($msgAr['srcSslKeyPub']));
					$msg->setDstNodeId($msgAr['dstNodeId']);
					$msg->setText($msgAr['text']);
					$msg->setPassword($msgAr['password']);
					$msg->setChecksum($msgAr['checksum']);
					$msg->setSentNodes($msgAr['sentNodes']);
					$msg->setRelayCount($msgAr['relayCount']);
					$msg->setEncryptionMode($msgAr['encryptionMode']);
					$msg->setTimeCreated($msgAr['timeCreated']);
					
					$this->msgs[$msg->getId()] = $msg;
				}
			}
			unset($this->data['msgs']);
			
			return true;
		}
		
		return false;
	}
	
	public function msgAdd(Msg $msg){
		$this->msgs[$msg->getId()] = $msg;
		$this->setDataChanged(true);
	}
	
	public function msgUpdate(Msg $msgNew){
		if(isset($this->msgs[$msg->getId()])){
			$msgOld = $this->msgs[$msgNew->getId()];
			
			#ve($msgOld);
			
			if($msgOld->getVersion() != $msgNew->getVersion()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: version'."\n";
				$msgOld->setVersion($msgNew->getVersion());
				$this->setDataChanged(true);
			}
			if($msgOld->getId() != $msgNew->getId()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: id'."\n";
				$msgOld->setId($msgNew->getId());
				$this->setDataChanged(true);
			}
			if($msgOld->getSrcNodeId() != $msgNew->getSrcNodeId()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: srcNodeId'."\n";
				$msgOld->setSrcNodeId($msgNew->getSrcNodeId());
				$this->setDataChanged(true);
			}
			if($msgOld->getSrcSslKeyPub() != $msgNew->getSrcSslKeyPub()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: srcSslKeyPub'."\n";
				$msgOld->setSrcSslKeyPub($msgNew->getSrcSslKeyPub());
				$this->setDataChanged(true);
			}
			if($msgOld->getDstNodeId() != $msgNew->getDstNodeId()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: dstNodeId'."\n";
				$msgOld->setDstNodeId($msgNew->getDstNodeId());
				$this->setDataChanged(true);
			}
			if($msgOld->getText() != $msgNew->getText()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: text'."\n";
				$msgOld->setText($msgNew->getText());
				$this->setDataChanged(true);
			}
			if($msgOld->getPassword() != $msgNew->getPassword()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: password'."\n";
				$msgOld->setPassword($msgNew->getPassword());
				$this->setDataChanged(true);
			}
			if($msgOld->getChecksum() != $msgNew->getChecksum()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: checksum'."\n";
				$msgOld->setChecksum($msgNew->getChecksum());
				$this->setDataChanged(true);
			}
			if($msgNew->getSentNodes()){
				#ve($msgNew->getSentNodes()); # TODO
				$msgOld->setSentNodes(array_unique(array_merge($msgOld->getSentNodes(), $msgNew->getSentNodes())));
			}
			if($msgOld->getRelayCount() != $msgNew->getRelayCount()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: relayCount'."\n";
				$msgOld->setRelayCount($msgNew->getRelayCount());
				$this->setDataChanged(true);
			}
			if($msgOld->getEncryptionMode() != $msgNew->getEncryptionMode()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: encryptionMode'."\n";
				$msgOld->setEncryptionMode($msgNew->getEncryptionMode());
				$this->setDataChanged(true);
			}
			if($msgOld->getTimeCreated() != $msgNew->getTimeCreated()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: timeCreated'."\n";
				$msgOld->setTimeCreated($msgNew->getTimeCreated());
				$this->setDataChanged(true);
			}
			
		}
	}
	
	public function getMsgs(){
		return $this->msgs;
	}
	
	public function getMsgWithNoDstNodeId(){
		$rv = array();
		foreach($this->msgs as $msgId => $msg){
			if(!$msg->getDstNodeId()){
				$rv[$msgId] = $msg;
			}
		}
		return $rv;
	}
	
	public function getUnsentMsgs(){
		$rv = array();
		foreach($this->msgs as $msgId => $msg){
			if(!$msg->getSentNodes()){
				$rv[$msgId] = $msg;
			}
		}
		return $rv;
	}
	
	public function getMsgById($id){
		foreach($this->msgs as $msgId => $msg){
			if($msg->getId() == $id){
				return $msg;
			}
		}
		
		return null;
	}
	
}
