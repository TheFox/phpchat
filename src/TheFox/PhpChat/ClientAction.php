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
	private $objc = null;
	private $func = null;
	
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
	
	public function functionSet($objc, $func = null){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		#ve($objc);
		#ve($func);
		
		if($func === null){
			#print __CLASS__.'->'.__FUNCTION__.': func is null'."\n";
			$this->func = $objc;
		}
		else{
			$this->objc = $objc;
			$this->func = $func;
		}
	}
	
}
