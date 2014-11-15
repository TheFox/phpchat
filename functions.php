<?php

if(!function_exists('strIsIp')){
	function strIsIp($ip){
		if(preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $ip)){
			return true;
		}
		return false;
	}
}

if(!function_exists('sslKeyPubClean')){
	function sslKeyPubClean($key){
		$key = str_replace("\r", '', $key);
		$key = str_replace("\n", '', $key);
		$key = str_replace('-----BEGIN PUBLIC KEY-----', '', $key);
		$key = str_replace('-----END PUBLIC KEY-----', '', $key);
		
		return $key;
	}
}

if(!function_exists('intToBin')){
	function intToBin($i){
		$rv = '';
		$rv .= $i & (1 << 7) ? '1' : '0';
		$rv .= $i & (1 << 6) ? '1' : '0';
		$rv .= $i & (1 << 5) ? '1' : '0';
		$rv .= $i & (1 << 4) ? '1' : '0';
		$rv .= $i & (1 << 3) ? '1' : '0';
		$rv .= $i & (1 << 2) ? '1' : '0';
		$rv .= $i & (1 << 1) ? '1' : '0';
		$rv .= $i & (1 << 0) ? '1' : '0';
		return $rv;
	}
}

if(!function_exists('timeStop')){
	$timeStopStart = 0;
	function timeStop($name = ''){
		global $timeStopStart;
		$time = microtime(true);
		$diff = $time - $timeStopStart;
		$timeStopStart = $time;
		#fwrite(STDOUT, '[time] '.$name.' '.sprintf('%d', ($diff * 1000)).PHP_EOL);
		fwrite(STDOUT, '[time] '.$name.' '.sprintf('%f', $diff).PHP_EOL);
	}
}

if(!function_exists('gzdecode') && function_exists('gzinflate')){
	function gzdecode($data){
		return gzinflate(substr($data, 10, -8));
	}
}
