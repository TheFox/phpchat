<?php

namespace TheFox\PhpChat;

use Exception;
use RuntimeException;
use Closure;

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
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
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
	
	public function functionExec(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#print __CLASS__.'->'.__FUNCTION__.': inst '.(int)($this->func instanceof Closure)."\n";
		
		$args = func_get_args();
		
		$objc = $this->objc;
		$func = $this->func;
		
		#print __CLASS__.'->'.__FUNCTION__.': objc '.gettype($objc).', '.get_class($objc)."\n";
		#print __CLASS__.'->'.__FUNCTION__.': func '.gettype($func).', '.get_class($func)."\n";
		
		#print_r($objc);
		#print_r($func);
		
		if($objc === null && $func === null){
			#print __CLASS__.'->'.__FUNCTION__.': null'."\n";
			return null;
		}
		elseif($objc === null && $func instanceof Closure){
			#print __CLASS__.'->'.__FUNCTION__.': exec anon'."\n";
			return call_user_func_array($func, $args);
		}
		elseif($objc === null && is_string($func)){
			#print __CLASS__.'->'.__FUNCTION__.': exec string "'.$func.'"'."\n";
			return call_user_func_array($func, $args);
		}
		elseif(is_object($objc) && is_string($func)){
			#print __CLASS__.'->'.__FUNCTION__.': exec objc'."\n";
			return call_user_func_array(array($objc, $func), $args);
		}
		else{
			#print __CLASS__.'->'.__FUNCTION__.': else'."\n";
		}
	}
	
}
