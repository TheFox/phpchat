<?php

namespace TheFox\Utilities;

use InvalidArgumentException;

class Hex{
	
	const ALPHABET = '0123456789abcdef';
	
	public static function encode($dec){
		$chars = Hex::ALPHABET;
		$rv = '';
		
		while(bccomp($dec, 0) == 1){
			$dv = (string)bcdiv($dec, '16', 0);
			$rem = (integer)bcmod($dec, '16');
			$dec = $dv;
			$rv .= $chars[$rem];
		}
		
		return strrev($rv);
	}
	
	public static function decode($hex){
		$chars = Hex::ALPHABET;
		$rv = '';
		
		$hex = strtolower($hex);
		$hexLen = strlen($hex);
		for($i = 0; $i < $hexLen; $i++){
			$current = (string)strpos($chars, $hex[$i]);
			$rv = (string)bcmul($rv, '16', 0);
			$rv = (string)bcadd($rv, $current, 0);
		}
		return $rv;
	}
	
	public static function dataEncode($data, $separator = ''){
		$rv = array();
		
		$format = '%02x';
		$dataLen = strlen($data);
		for($n = 0; $n < $dataLen; $n++){
			$rv[] = sprintf($format, ord($data[$n]));
		}
		
		return join($separator, $rv);
	}
	
	public static function dataDecode($hex){
		$hexLen = strlen($hex);
		if($hexLen % 2 != 0){
			throw new InvalidArgumentException('Uneven number of hex string: '.$hexLen);
		}
		
		$rv = '';
		$hexLen = strlen($hex);
		for($n = 0; $n < $hexLen; $n += 2){
			#print "n = $n\n";
			$rv .= chr(hexdec($hex[$n].$hex[$n + 1]));
		}
		
		return $rv;
	}
	
}
