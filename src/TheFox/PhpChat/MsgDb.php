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
			$msgAr['id'] = $msg->getId();
			$msgAr['srcNodeId'] = $msg->getSrcNodeId();
			$msgAr['srcSslKeyPub'] = base64_encode($msg->getSrcSslKeyPub());
			$msgAr['srcUserNickname'] = $msg->getSrcUserNickname();
			$msgAr['dstNodeId'] = $msg->getDstNodeId();
			$msgAr['text'] = $msg->getText();
			$msgAr['sentNodes'] = $msg->getSentNodes();
			$msgAr['relayCount'] = $msg->getRelayCount();
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
					$msg->setId($msgAr['id']);
					$msg->setSrcNodeId($msgAr['srcNodeId']);
					$msg->setSrcSslKeyPub(base64_decode($msgAr['srcSslKeyPub']));
					$msg->setSrcUserNickname($msgAr['srcUserNickname']);
					$msg->setDstNodeId($msgAr['dstNodeId']);
					$msg->setText($msgAr['text']);
					$msg->setSentNodes($msgAr['sentNodes']);
					$msg->setRelayCount($msgAr['relayCount']);
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
	
}
