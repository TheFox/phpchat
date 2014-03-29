<?php

namespace TheFox\PhpChat;

use TheFox\Yaml\YamlStorage;

class MsgSendQueue extends YamlStorage{
	
	private $msgsId = 0;
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
			$msgAr['srcUserNickname'] = $msg->getSrcUserNickname();
			$msgAr['dstNodeId'] = $msg->getDstNodeId();
			$msgAr['text'] = $msg->getText();
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
					$msg->setSrcUserNickname($msgAr['srcUserNickname']);
					$msg->setDstNodeId($msgAr['dstNodeId']);
					$msg->setText($msgAr['text']);
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
	
}
