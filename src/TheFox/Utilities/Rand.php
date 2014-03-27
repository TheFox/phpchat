<?php

namespace TheFox\Utilities;

class Rand{
	
	public static function data($len = 16){
		$rv = '';
		for($n = 0; $n < $len; $n++){
			$rv .= chr(mt_rand(0, 255));
		}
		return $rv;
	}
	
}
