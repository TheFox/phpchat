<?php

namespace TheFox\PhpChat;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

class Msg{
	
	private $id = '';
	private $srcNodeId = '';
	private $srcSslKeyPub = '';
	private $srcUserNickname = '';
	private $dstNodeId = '';
	private $text = '';
	private $timeCreated = 0;
	
	public function __construct($text = ''){
		try{
			$this->setId((string)Uuid::uuid4());
		}
		catch(UnsatisfiedDependencyException $e){
			# TODO
		}
		
		$this->setText($text);
		$this->setTimeCreated(time());
	}
	
	public function __sleep(){
		return array('id', 'srcNodeId', 'srcSslKeyPub', 'srcUserNickname', 'dstNodeId', 'text', 'timeCreated');
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function setSrcNodeId($srcNodeId){
		$this->srcNodeId = $srcNodeId;
	}
	
	public function getSrcNodeId(){
		return $this->srcNodeId;
	}
	
	public function setSrcSslKeyPub($srcSslKeyPub){
		$this->srcSslKeyPub = $srcSslKeyPub;
	}
	
	public function getSrcSslKeyPub(){
		return $this->srcSslKeyPub;
	}
	
	public function setSrcUserNickname($srcUserNickname){
		$this->srcUserNickname = $srcUserNickname;
	}
	
	public function getSrcUserNickname(){
		return $this->srcUserNickname;
	}
	
	public function setDstNodeId($dstNodeId){
		$this->dstNodeId = $dstNodeId;
	}
	
	public function getDstNodeId(){
		return $this->dstNodeId;
	}
	
	public function setText($text){
		$this->text = $text;
	}
	
	public function getText(){
		return $this->text;
	}
	
	public function setTimeCreated($timeCreated){
		$this->timeCreated = $timeCreated;
	}
	
	public function getTimeCreated(){
		return $this->timeCreated;
	}
	
}
