<?php

namespace TheFox\PhpChat;

use Exception;
use RuntimeException;

class ClientAction{
	
	const CRITERION_NONE = 0;
	const CRITERION_AFTER_CONNECT = 1;
	const CRITERION_AFTER_ID = 2;
	const CRITERION_AFTER_ID_OK = 4;
	
	private $id = 0;
	private $criteria = 0;
	
	
	public function __construct($criteria = 0){
		$this->criteria = $criteria;
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function getCriteria(){
		return $this->criteria;
	}
	
	public function hasCriterion($criterion){
		return $this->getCriteria() & $criterion;
	}
	
}
