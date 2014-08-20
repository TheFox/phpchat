<?php

namespace TheFox\PhpChat;

use TheFox\Storage\YamlStorage;
use TheFox\Dht\Kademlia\Node;

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
			#print __CLASS__.'->'.__FUNCTION__.': '.$msgId."\n";
			
			$this->data['msgs'][$msgId] = array(
				'path' => $msg->getFilePath(),
			);
			$msg->save();
		}
		
		$rv = parent::save();
		unset($this->data['msgs']);
		
		return $rv;
	}
	
	public function load(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(parent::load()){
			
			if(array_key_exists('msgs', $this->data) && $this->data['msgs']){
				foreach($this->data['msgs'] as $msgId => $msgAr){
					if(file_exists($msgAr['path'])){
						$msg = new Msg($msgAr['path']);
						$msg->setDatadirBasePath($this->getDatadirBasePath());
						if($msg->load()){
							#print __CLASS__.'->'.__FUNCTION__.': '.$msg->getId().', '.$msg->getStatus()."\n";
							$this->msgs[$msg->getId()] = $msg;
						}
					}
				}
			}
			unset($this->data['msgs']);
			
			return true;
		}
		
		return false;
	}
	
	public function msgAdd(Msg $msg){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#print __CLASS__.'->'.__FUNCTION__.': '.$msg->getId()."\n";
		
		$filePath = null;
		if($this->getDatadirBasePath()){
			$filePath = $this->getDatadirBasePath().'/msg_'.$msg->getId().'.yml';
		}
		
		$msg->setFilePath($filePath);
		$msg->setDatadirBasePath($this->getDatadirBasePath());
		$msg->setMsgDb($this);
		
		$this->msgs[$msg->getId()] = $msg;
		$this->setDataChanged(true);
	}
	
	public function msgUpdate(Msg $msgNew){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(isset($this->msgs[$msgNew->getId()])){
			$msgOld = $this->msgs[$msgNew->getId()];
			
			#print __CLASS__.'->'.__FUNCTION__.': update'."\n";
			
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
			if($msgOld->getDstSslPubKey() != $msgNew->getDstSslPubKey()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: dstSslPubKey'."\n";
				$msgOld->setDstSslPubKey($msgNew->getDstSslPubKey());
				$this->setDataChanged(true);
			}
			if($msgOld->getSubject() != $msgNew->getSubject()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: subject'."\n";
				$msgOld->setSubject($msgNew->getSubject());
				$this->setDataChanged(true);
			}
			if($msgOld->getBody() != $msgNew->getBody()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: text'."\n";
				$msgOld->setBody($msgNew->getBody());
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
			if(count($msgOld->getSentNodes()) < count($msgNew->getSentNodes())){
				#print __CLASS__.'->'.__FUNCTION__.': new sent nodes'."\n";
				#ve($msgNew->getSentNodes());
				$msgOld->setSentNodes(array_unique(array_merge($msgOld->getSentNodes(), $msgNew->getSentNodes())));
			}
			if($msgOld->getRelayCount() != $msgNew->getRelayCount()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: relayCount'."\n";
				$msgOld->setRelayCount($msgNew->getRelayCount());
				$this->setDataChanged(true);
			}
			if($msgOld->getForwardCycles() != $msgNew->getForwardCycles()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: forwardCycles'."\n";
				$msgOld->setForwardCycles($msgNew->getForwardCycles());
				$this->setDataChanged(true);
			}
			if($msgOld->getEncryptionMode() != $msgNew->getEncryptionMode()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: encryptionMode, '.$msgOld->getEncryptionMode();
				#print ', '.$msgNew->getEncryptionMode()."\n";
				$msgOld->setEncryptionMode($msgNew->getEncryptionMode());
				$this->setDataChanged(true);
			}
			if($msgOld->getStatus() != $msgNew->getStatus()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: status'."\n";
				$msgOld->setStatus($msgNew->getStatus());
				$this->setDataChanged(true);
			}
			if($msgOld->getTimeCreated() != $msgNew->getTimeCreated()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: timeCreated'."\n";
				$msgOld->setTimeCreated($msgNew->getTimeCreated());
				$this->setDataChanged(true);
			}
			
			if($msgOld->getDataChanged() != $msgNew->getDataChanged()){
				#print __CLASS__.'->'.__FUNCTION__.': changed: dataChanged'."\n";
				$msgOld->setDataChanged($msgNew->getDataChanged());
			}
			
		}
	}
	
	public function getMsgs(){
		#print __CLASS__.'->'.__FUNCTION__.': '.count($this->msgs).', "'.$pos.'"'."\n";
		return $this->msgs;
	}
	
	public function getMsgsCount(){
		return count($this->msgs);
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
	
	public function getMsgsForDst(Node $node){
		#print __CLASS__.'->'.__FUNCTION__.': dst '.$node->getIdHexStr()."\n";
		
		$rv = array();
		foreach($this->msgs as $msgId => $msg){
			#print __CLASS__.'->'.__FUNCTION__.': '.$msg->getId().', '.$msg->getDstNodeId()."\n";
			if($msg->getDstNodeId() == $node->getIdHexStr()){
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
