<?php

function ve($v = null){
	try{
		$rv = var_export($v, true);
		#print "\n";
		fwrite(STDOUT, $rv."\n");
	}
	catch(Exception $e){
		print 'ERROR: '.$e->getMessage()."\n";
	}
}

function vej($v = null){
	try{
		ve(json_encode($v));
	}
	catch(Exception $e){
		print 'ERROR: '.$e->getMessage()."\n";
	}
}

function vew($v = null){
	try{
		print '<pre>';
		var_export($v);
		print '</pre>';
	}
	catch(Exception $e){
		print 'ERROR: '.$e->getMessage()."\n";
	}
}

function strIsIp($ip){
	if(preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $ip)){
		return true;
	}
	return false;
}

function sslKeyPubClean($key){
	$key = str_replace("\r", '', $key);
	$key = str_replace("\n", '', $key);
	$key = str_replace('-----BEGIN PUBLIC KEY-----', '', $key);
	$key = str_replace('-----END PUBLIC KEY-----', '', $key);
	
	return $key;
}

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
