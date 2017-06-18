<?php

// @todo 1. move functions into services
// @todo 2. remove this file

$filesystem = new Filesystem();


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
