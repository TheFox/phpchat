<?php

// This command cost me a whole day. Use it even in signalHandlerSetup().
declare(ticks = 1);

error_reporting(E_ALL | E_STRICT);

ini_set('display_errors', true);
ini_set('memory_limit', '128M');

if(@date_default_timezone_get() == 'UTC') date_default_timezone_set('UTC');

chdir(__DIR__);

#define('DEBUG', 1, true);
define('PHP_EOL_LEN', strlen(PHP_EOL), true);


if(PHP_SAPI != 'cli'){
	print 'FATAL ERROR: you need to run this in your shell'."\n";
	exit(1);
}

if(version_compare(PHP_VERSION, '5.3.0', '<')){
	print 'FATAL ERROR: you need at least PHP 5.3. Your version: '.PHP_VERSION."\n";
	exit(1);
}

// Check modules installed.
if(!extension_loaded('openssl')){
	print 'FATAL ERROR: you must first install "openssl" extension.'."\n";
	exit(1);
}
if(!extension_loaded('sockets')){
	print 'FATAL ERROR: you must first install "sockets" extension.'."\n";
	exit(1);
}
if(!extension_loaded('curl')){
	print 'FATAL ERROR: you must first install "curl" extension.'."\n";
	exit(1);
}
if(!function_exists('gzcompress')){
	print 'FATAL ERROR: you need the PHP gzip functions.'."\n";
	exit(1);
}
if(!function_exists('mt_rand')){
	print 'FATAL ERROR: you need the PHP mt_rand function.'."\n";
	exit(1);
}

// Check algorythms.
if(!in_array('sha512', hash_algos())){
	print 'FATAL ERROR: sha512 is not available.'."\n";
	exit(1);
}
if(!in_array('ripemd160', hash_algos())){
	print 'FATAL ERROR: ripemd160 is not available.'."\n";
	exit(1);
}

if(!file_exists('vendor')){
	print "FATAL ERROR: you must first run 'composer install'.\nVisit https://getcomposer.org\n";
	exit(1);
}

require_once __DIR__.'/vendor/autoload.php';

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Uri\UriFactory;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;
use TheFox\PhpChat\Settings;
use TheFox\Dht\Kademlia\Node;


$filesystem = new Filesystem();
$filesystem->mkdir('log', 0700);
$filesystem->mkdir('pid', 0700);

$log = new Logger('main');
$log->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
$log->pushHandler(new StreamHandler('log/bootstrap.log', Logger::DEBUG));

UriFactory::registerScheme('tcp', 'TheFox\PhpChat\TcpUri');
UriFactory::registerScheme('http', 'TheFox\PhpChat\HttpUri');

$settings = new Settings(__DIR__.'/settings.yml');

if(isset($settings->data['datadir']) && !file_exists($settings->data['datadir'])){
	$log->info('create datadir: '.$settings->data['datadir']);
	$filesystem->mkdir($settings->data['datadir'], 0700);
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

$keyPub = null;
if(file_exists($settings->data['node']['sslKeyPrvPath'])){
	$sslPubKey = openssl_pkey_get_public(file_get_contents($settings->data['node']['sslKeyPubPath']));
	if($sslPubKey){
		$sslPubKeyDetails = openssl_pkey_get_details($sslPubKey);
		if(isset($sslPubKeyDetails['key'])){
			$keyPub = $sslPubKeyDetails['key'];
		}
	}
}
else{
	$log->info('SSL: key pair generation.  this may take a while...');
	
	$keyPrv = null;
	
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
					$log->info('SSL: test keys ok');
					file_put_contents($settings->data['node']['sslKeyPubPath'], $keyPub);
					$filesystem->chmod($settings->data['node']['sslKeyPubPath'], 0400);
				}
				else{
					$log->critical('SSL: test keys failed');
					exit(1);
				}
			}
			else{
				$log->critical('SSL: public key generation failed: '.openssl_error_string());
				exit(1);
			}
		}
		else{
			$log->critical('SSL: private key generation failed');
			exit(1);
		}
	}
	else{
		$log->critical('SSL: key generation failed: '.openssl_error_string());
		exit(1);
	}
	
	if(file_exists($settings->data['node']['sslKeyPrvPath'])){
		$filesystem->chmod($settings->data['node']['sslKeyPrvPath'], 0400);
	}
	
	openssl_pkey_free($ssl);
	
	$log->info('SSL: key pair generation done');
}

if($keyPub && !$settings->data['node']['id']){
	$nodeId = '';
	$nodeId = Node::genIdHexStr($keyPub);
	if($nodeId){
		$settings->data['node']['id'] = $nodeId;
		$settings->setDataChanged(true);
	}
	else{
		$log->critical('node ID generation failed');
		exit(1);
	}
}

$settings->save();

$filesystem->chmod(__DIR__.'/settings.yml', 0600);
