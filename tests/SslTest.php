<?php

class SslTest extends PHPUnit_Framework_TestCase{
	
	public function testSsl(){
		$this->assertTrue(extension_loaded('openssl'));
	}
	
	public function providerFunctions(){
		$rv = array();
		
		$rv[] = array('openssl_pkey_new');
		$rv[] = array('openssl_pkey_export');
		$rv[] = array('openssl_pkey_export_to_file');
		$rv[] = array('openssl_pkey_get_details');
		$rv[] = array('openssl_public_encrypt');
		$rv[] = array('openssl_private_decrypt');
		$rv[] = array('openssl_error_string');
		$rv[] = array('openssl_pkey_get_public');
		$rv[] = array('openssl_pkey_get_private');
		$rv[] = array('openssl_sign');
		$rv[] = array('openssl_verify');
		$rv[] = array('openssl_encrypt');
		$rv[] = array('openssl_decrypt');
		$rv[] = array('openssl_free_key');
		
		return $rv;
	}
	
	/**
     * @dataProvider providerFunctions
     * @group medium
     */
	public function testFunctions($name){
		$this->assertTrue(function_exists($name), $name.' function not found.');
	}
	
	public function testKeyGen(){
		$sslConfig = array(
			'digest_alg' => 'sha512',
			'private_key_bits' => 4096,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		);
		
		$ssl = openssl_pkey_new($sslConfig);
		$this->assertTrue($ssl ? true : false);
		
		openssl_pkey_export_to_file($ssl, 'tests/id_rsa.prv');
		#fwrite(STDOUT, 'SSL ERROR: '.openssl_error_string()."\n");
		openssl_pkey_export_to_file($ssl, 'tests/id_rsa_pass.prv', 'my_password');
		#fwrite(STDOUT, 'SSL ERROR: '.openssl_error_string()."\n");
		
		$keyPub = openssl_pkey_get_details($ssl);
		$keyPub = $keyPub['key'];
		file_put_contents('tests/id_rsa.pub', $keyPub);
		
		
		openssl_public_encrypt('test my keys', $encrypted, $keyPub);
		#fwrite(STDOUT, 'SSL ERROR: '.openssl_error_string()."\n");
		openssl_private_decrypt($encrypted, $decrypted, $ssl);
		#fwrite(STDOUT, 'SSL ERROR: '.openssl_error_string()."\n");
		$this->assertEquals('test my keys', $decrypted);
		
		
		openssl_pkey_free($ssl);
		
	}
	
}
