<?php

class SslTest extends PHPUnit_Framework_TestCase{
	
	const SSL_KEY_PUB1 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA2+wZQQSxQXaxUmL/bg7O
gA7fOuw4Kk6/UtEntvM4O1Ll75l0ptgalwkO8DFhwRmWxDd0BYd/RxsbWrii3/1R
6+HSQdjyeeY3gQFdL7r65RRvXkYTtNSsFDeqcVQC+c6lFqRozQDNnAtxmy1Fhc0z
IUeC0iWNXIJciDYLTJV6VB0WNNl+5mCV2KaH2H3opw2A0c/+FTPWbvgf28WAd4FQ
koWiNjnDEDl5Ti39HeJN7q9LjpiafRTSrwE/kNcFNEtcdcxArxITuR92H+VjgXqs
dre0pqN7q1cJCZ/XP8Z0ZWA8rpLym+3S+FJaTJXhHBAv05hOu2zfzKUqaxmatAWz
NgxY7wvarGol/kqBYqyfVO/c1AOdr2Uw9rO0vJ9nPADih+OMYltaX521i6gvngdc
P7JJIZyNcZgN1l6HbO0KxugD2nJfkgGmU/ihIEpHjmrMXYMSzJy1KVOmLFpd8tiu
WXQCmarTOlzkcH7jmVqDRAjMUvDoAve4LYl0jua1W2wtCm1DisgIK6MCt38W8Zn3
o1pxgj1LiQmhAx4D9nL4MH14Zi++mK0iu8tJeXJdcql1l+bOJfkRjkNh3QjmLX3b
zoDXmjCC/vFQgspeMCSnIeml5Ymlk1tgxgiRNAPRpttbyr0jzlnUGEYZ/fGzNsY7
O5mYMzSLyuOXR5xhBhG7fjsCAwEAAQ==
-----END PUBLIC KEY-----
';
	
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
		$fileName = 'testfile_ssl_id_rsa_'.date('Ymd_His').'_'.uniqid('', true);
		
		$sslConfig = array(
			'digest_alg' => 'sha512',
			'private_key_bits' => 4096,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		);
		
		$ssl = openssl_pkey_new($sslConfig);
		$this->assertTrue($ssl ? true : false);
		
		openssl_pkey_export_to_file($ssl, 'test_data/'.$fileName.'.prv');
		#fwrite(STDOUT, 'SSL ERROR: '.openssl_error_string()."\n");
		openssl_pkey_export_to_file($ssl, 'test_data/'.$fileName.'_pass.prv', 'my_password');
		#fwrite(STDOUT, 'SSL ERROR: '.openssl_error_string()."\n");
		
		$keyPub = openssl_pkey_get_details($ssl);
		#ve($keyPub);
		$keyPub = $keyPub['key'];
		file_put_contents('test_data/'.$fileName.'.pub', $keyPub);
		
		openssl_public_encrypt('test my keys', $encrypted, $keyPub);
		#fwrite(STDOUT, 'SSL ERROR: '.openssl_error_string()."\n");
		openssl_private_decrypt($encrypted, $decrypted, $ssl);
		#fwrite(STDOUT, 'SSL ERROR: '.openssl_error_string()."\n");
		$this->assertEquals('test my keys', $decrypted);
		
		openssl_pkey_free($ssl);
	}
	
	public function testSslKeyPubClean(){
		$expect = '';
		$expect .= 'MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA2+wZQQSxQXaxUmL/bg7O';
		$expect .= 'gA7fOuw4Kk6/UtEntvM4O1Ll75l0ptgalwkO8DFhwRmWxDd0BYd/RxsbWrii3/1R';
		$expect .= '6+HSQdjyeeY3gQFdL7r65RRvXkYTtNSsFDeqcVQC+c6lFqRozQDNnAtxmy1Fhc0z';
		$expect .= 'IUeC0iWNXIJciDYLTJV6VB0WNNl+5mCV2KaH2H3opw2A0c/+FTPWbvgf28WAd4FQ';
		$expect .= 'koWiNjnDEDl5Ti39HeJN7q9LjpiafRTSrwE/kNcFNEtcdcxArxITuR92H+VjgXqs';
		$expect .= 'dre0pqN7q1cJCZ/XP8Z0ZWA8rpLym+3S+FJaTJXhHBAv05hOu2zfzKUqaxmatAWz';
		$expect .= 'NgxY7wvarGol/kqBYqyfVO/c1AOdr2Uw9rO0vJ9nPADih+OMYltaX521i6gvngdc';
		$expect .= 'P7JJIZyNcZgN1l6HbO0KxugD2nJfkgGmU/ihIEpHjmrMXYMSzJy1KVOmLFpd8tiu';
		$expect .= 'WXQCmarTOlzkcH7jmVqDRAjMUvDoAve4LYl0jua1W2wtCm1DisgIK6MCt38W8Zn3';
		$expect .= 'o1pxgj1LiQmhAx4D9nL4MH14Zi++mK0iu8tJeXJdcql1l+bOJfkRjkNh3QjmLX3b';
		$expect .= 'zoDXmjCC/vFQgspeMCSnIeml5Ymlk1tgxgiRNAPRpttbyr0jzlnUGEYZ/fGzNsY7';
		$expect .= 'O5mYMzSLyuOXR5xhBhG7fjsCAwEAAQ==';
		
		$this->assertEquals($expect, sslKeyPubClean(static::SSL_KEY_PUB1));
	}
	
}
