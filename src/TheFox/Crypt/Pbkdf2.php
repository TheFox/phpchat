<?php

namespace TheFox\Crypt;

use RuntimeException;

class Pbkdf2{
	
	const PBKDF2_HASH_ALGORITHM = 'sha512';
	const PBKDF2_ITERATIONS = 102400;
	const PBKDF2_HASH_BYTE_SIZE = 32;
	
	private $algo = '';
	private $iter = 0;
	private $keyLength = 0;
	private $salt = '';
	
	public function __construct($algo = static::PBKDF2_HASH_ALGORITHM,
		$iter = static::PBKDF2_ITERATIONS, $keyLength = static::PBKDF2_HASH_BYTE_SIZE){
		$this->algo = $algo;
		$this->iter = $iter;
		$this->keyLength = $keyLength;
	}
	
	public function getAlgo(){
		return $this->algo;
	}
	
	public function getIter(){
		return $this->iter;
	}
	
	public function getKeyLength(){
		return $this->keyLength;
	}
	
	public function setSalt($salt){
		$this->salt = $salt;
	}
	
	public function getSalt(){
		return $this->salt;
	}
	
	public function create($password){
		$this->salt = '';
		if(function_exists('mcrypt_create_iv')){
			$this->salt = base64_encode(mcrypt_create_iv(24, MCRYPT_DEV_URANDOM));
		}
		elseif(class_exists('\\TheFox\\Utilities\\Rand')){
			$this->salt = base64_encode(\TheFox\Utilities\Rand::data(24));
		}
		else{
			throw new RuntimeException('Function "mcrypt_create_iv" or "TheFox\\Utilities\\Rand::data()" not found.', 1);
		}
		
		$hash = base64_encode($this->pbkdf2($this->algo, $password, $this->salt, $this->iter, $this->keyLength, true));
		
		return $hash;
	}
	
	public function pbkdf2($algorithm, $password, $salt, $count, $keyLength, $raw_output = false){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$algorithm = strtolower($algorithm);
		
		if(!in_array($algorithm, hash_algos(), true)){
			throw new RuntimeException('PBKDF2 ERROR: Invalid hash algorithm.', 1);
		}
		if($count <= 0 || $keyLength <= 0){
			throw new RuntimeException('PBKDF2 ERROR: Invalid parameters.', 2);
		}
		
		if(function_exists('hash_pbkdf2')){
			if(!$raw_output){
				$keyLength = $keyLength * 2;
			}
			return hash_pbkdf2($algorithm, $password, $salt, $count, $keyLength, $raw_output);
		}
		
		$hash_length = strlen(hash($algorithm, '', true));
		$block_count = ceil($keyLength / $hash_length);
		
		#print __CLASS__.'->'.__FUNCTION__.': keyLength: '.$keyLength."\n";
		#print __CLASS__.'->'.__FUNCTION__.': hash_length: '.$hash_length."\n";
		#print __CLASS__.'->'.__FUNCTION__.': block_count: '.$block_count."\n";
		
		$output = '';
		for($i = 1; $i <= $block_count; $i++){
			#print __CLASS__.'->'.__FUNCTION__.': block: '.$i."\n";
			
			$last = $salt.pack('N', $i);
			$last = $xorsum = hash_hmac($algorithm, $last, $password, true);
			
			for($j = 1; $j < $count; $j++){
				#print __CLASS__.'->'.__FUNCTION__.': count: '.$j."\n";
				$xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
			}
			$output .= $xorsum;
		}
		
		if($raw_output){
			#print __CLASS__.'->'.__FUNCTION__.': raw'."\n";
			return substr($output, 0, $keyLength);
		}
		
		return bin2hex(substr($output, 0, $keyLength));
	}
	
	private function equals($a, $b){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$aLen = strlen($a);
		$bLen = strlen($b);
		$diff = $aLen ^ $bLen;
		#print __CLASS__.'->'.__FUNCTION__.': diff = '.$diff.''."\n";
		for($i = 0; $i < $aLen && $i < $bLen; $i++){
			#print __CLASS__.'->'.__FUNCTION__.': '.$i.' = '.$diff.''."\n";
			$diff |= ord($a[$i]) ^ ord($b[$i]);
		}
		
		return $diff === 0;
	}
	
	public function validate($hash, $password){
		$hash = base64_decode($hash);
		
		return $this->equals($hash, $this->pbkdf2($this->algo, $password, $this->salt, $this->iter, strlen($hash), true));
	}
	
}
