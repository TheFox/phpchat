<?php

error_reporting(E_ALL | E_STRICT);

if(@date_default_timezone_get() == 'UTC') date_default_timezone_set('UTC');

ini_set('display_errors', true);
ini_set('memory_limit', '128M');

chdir(__DIR__);

define('DEBUG', 1, true);
define('PHP_EOL_LEN', strlen(PHP_EOL), true);


if(PHP_SAPI != 'cli'){
	print "FATAL ERROR: you need to run this in your shell\n";
	exit(1);
}
if(version_compare(PHP_VERSION, '5.3.0', '<')){
	print "FATAL ERROR: you need at least PHP 5.3. Your version: ".PHP_VERSION."\n";
	exit(1);
}

// Check modules installed.
if(!extension_loaded('openssl')){
	print "FATAL ERROR: you must first install openssl.\n";
	exit(1);
}
if(!extension_loaded('sockets')){
	print "FATAL ERROR: you must first install sockets.\n";
	exit(1);
}
if(!function_exists('gzcompress')){
	print "FATAL ERROR: you need the PHP gzip functions.\n";
	exit(1);
}
if(!function_exists('mt_rand')){
	print "FATAL ERROR: you need the PHP mt_rand function.\n";
	exit(1);
}

// Check algorythms.
if(!in_array('sha512', hash_algos())){
	print "FATAL ERROR: sha512 is not available.\n";
	exit(1);
}
if(!in_array('ripemd160', hash_algos())){
	print "FATAL ERROR: ripemd160 is not available.\n";
	exit(1);
}

# TODO: use DIRECTORY_SEPARATOR
if(!file_exists(__DIR__.'/vendor')){
	print "FATAL ERROR: you must first run 'composer install'.\nVisit https://getcomposer.org\n";
	exit(1);
}

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/functions.php';

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;
use TheFox\Phpchat\Settings;


if(!file_exists(__DIR__.'/log')){
	mkdir(__DIR__.'/log');
	chmod(__DIR__.'/log', 0700);
}

$log = new Logger('main');
$log->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
$log->pushHandler(new StreamHandler('log/bootstrap.log', Logger::DEBUG));

$settings = new Settings(__DIR__.'/settings.yml');

if( isset($settings->data['datadir']) && !file_exists($settings->data['datadir'])){
	$log->info('create datadir: '.$settings->data['datadir']);
	mkdir($settings->data['datadir']);
	chmod($settings->data['datadir'], 0700);
}

if(!$settings->data['node']['id']){
	$nodeId = '';
	try{
		$nodeId = (string)Uuid::uuid4();
		$log->info('node id: '.$nodeId);
	}
	catch(UnsatisfiedDependencyException $e){
		$log->critical('uuid4: '.$e->getMessage());
		exit(1);
	}
	
	if($nodeId){
		$settings->data['node']['id'] = $nodeId;
		$settings->setDataChanged(true);
	}
}

if(!$settings->data['node']['sslKeyPrvPass']){
	$sslKeyPrvPass = '';
	try{
		$log->info('ssl: generate private key password');
		$sslKeyPrvPass = (string)Uuid::uuid4();
	}
	catch(UnsatisfiedDependencyException $e){
		$log->critical('uuid4: '.$e->getMessage());
		exit(1);
	}
	
	$settings->data['node']['sslKeyPrvPass'] = hash('sha512', mt_rand(0, 999999).'_'.time().'_'.$sslKeyPrvPass);
	$settings->setDataChanged(true);
}

if(!file_exists($settings->data['node']['sslKeyPrvPath'])){
	$log->info('ssl: key pair generation.  this may take a while...');
	
	$keyPrv = null;
	$keyPub = null;
	
	$sslConfig = array(
		'digest_alg' => 'sha512',
		'private_key_bits' => 4096,
		'private_key_type' => OPENSSL_KEYTYPE_RSA,
	);
	
	// Create the private and public key
	$ssl = openssl_pkey_new($sslConfig);
	if($ssl){
		openssl_pkey_export($ssl, $keyPrv);
		
		openssl_pkey_export_to_file($ssl, $settings->data['node']['sslKeyPrvPath'], $settings->data['node']['sslKeyPrvPass']);
		
		if($keyPrv){
			$keyPub = openssl_pkey_get_details($ssl);
			if($keyPub && isset($keyPub['key'])){
				$keyPub = $keyPub['key'];
				
				openssl_public_encrypt('test my keys', $encrypted, $keyPub);
				openssl_private_decrypt($encrypted, $decrypted, $ssl);
				
				if($decrypted == 'test my keys'){
					$log->info('ssl: test keys ok');
					file_put_contents($settings->data['node']['ssl_key_pub_path'], $keyPub);
				}
				else{
					$log->critical('ssl: test keys failed');
					exit(1);
				}
			}
			else{
				$log->critical('ssl: public key generation failed: '.openssl_error_string());
				exit(1);
			}
		}
		else{
			$log->critical('ssl: private key generation failed');
			exit(1);
		}
	}
	else{
		$log->critical('ssl: key generation failed: '.openssl_error_string());
		exit(1);
	}
	
	if(file_exists($settings->data['node']['sslKeyPrvPath'])){
		chmod($settings->data['node']['sslKeyPrvPath'], 0400);
	}
	
	openssl_pkey_free($ssl);
	
	$log->info('ssl: key pair generation done');
}

$settings->save();
