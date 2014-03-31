<?php

namespace TheFox\PhpChat;

use Exception;
use RuntimeException;
use Closure;

class ClientAction{
	
	const CRITERION_NONE = 0;
	#const CRITERION_AFTER_CONNECT = 100;
	const CRITERION_AFTER_HELLO = 200;
	const CRITERION_AFTER_ID = 300;
	const CRITERION_AFTER_ID_OK = 310;
	const CRITERION_AFTER_MSG_RESPONSE = 320;
	const CRITERION_AFTER_HAS_SSL = 400;
	
	private $id = 0;
	private $criteria = array();
	private $objc = null;
	private $func = null;
	private $vars = array();
	
	public function __construct($criterion){
		$this->criteria = array($criterion);
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function getVar($name = null){
		if($name === null){
			#print __CLASS__.'->'.__FUNCTION__.': name is null'."\n";
			return $this->vars;
		}
		if(isset($this->vars[$name])){
			#print __CLASS__.'->'.__FUNCTION__.': is set'."\n";
			return $this->vars[$name];
		}
		
		#print __CLASS__.'->'.__FUNCTION__.': null'."\n";
		return null;
	}
	
	public function hasCriterion($criterion){
		return in_array($criterion, $this->criteria);
	}
	
	public function functionSet($objc, $func = null, $vars = null){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		#ve($objc);
		#ve($func);
		
		if($func === null && $vars === null){
			#print __CLASS__.'->'.__FUNCTION__.': func is null'."\n";
			$this->objc = null;
			$this->func = $objc;
			$this->vars = array();
		}
		elseif($func !== null && $vars === null){
			$this->objc = null;
			$this->func = $objc;
			$this->vars = $func;
		}
		else{
			$this->objc = $objc;
			$this->func = $func;
			$this->vars = $vars;
		}
	}
	
	public function functionExec(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#print __CLASS__.'->'.__FUNCTION__.': inst '.(int)($this->func instanceof Closure)."\n";
		
		$args = func_get_args();
		array_unshift($args, $this);
		
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
