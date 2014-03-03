<?php

namespace TheFox\Utilities;

class Base58{
	
	const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
	
	public static function encode($num){
		$chars = Base58::ALPHABET;
		$rv = '';
		
		while(bccomp($num, 0) == 1){
			$dv = (string)bcdiv($num, '58', 0);
			$rem = (integer)bcmod($num, '58');
			$num = $dv;
			$rv .= $chars[$rem];
		}
		
		return strrev($rv);
	}
	
	public static function decode($base58){
		$chars = Base58::ALPHABET;
		$rv = '0';
		
		$base58Len = strlen($base58);
		for($i = 0; $i < $base58Len; $i++){
			$current = (string)strpos($chars, $base58[$i]);
			$rv = (string)bcmul($rv, '58', 0);
			$rv = (string)bcadd($rv, $current, 0);
		}
		
		return $rv;
	}
	
}
