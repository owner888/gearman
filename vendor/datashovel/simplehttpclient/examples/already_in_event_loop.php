<?php
	
require_once(dirname(__DIR__).'/vendor/autoload.php');

use SimpleHttpClient\SimpleHttpClient;

try{

	$base = new EventBase();
	$dns_base = new EventDnsBase($base, TRUE);

	$client = new SimpleHttpClient([
		'host'=>'www.datashovel.com',
		'port'=>80,
		'contentType'=>'text/html',
		'base'=>$base,
		'dns_base'=>$dns_base,
	]);

	$http = $client->getContext();

	$http->setCallback(function() use ($http,$base){
		echo PHP_EOL.'RAW RESULTS:'.PHP_EOL;
		echo '====================='.PHP_EOL;
		$buffers = $http->getBuffers(function($val){return $val;});
		var_dump($buffers);

		echo PHP_EOL.'BODY ONLY:'.PHP_EOL;
		echo '====================='.PHP_EOL;
		$buffers = $http->getBuffers('body');
		var_dump($buffers);

		echo PHP_EOL.'RAW HEADERS:'.PHP_EOL;
		echo '====================='.PHP_EOL;
		$buffers = $http->getBuffers('raw_headers');
		var_dump($buffers);

		echo PHP_EOL.'PARSED HEADERS:'.PHP_EOL;
		echo '====================='.PHP_EOL;
		$buffers = $http->getBuffers('parsed_headers');
		var_dump($buffers);

		$base->exit();
	});

	$start = microtime(true);

	$http->get('/');
	//$http->post('/', 'k=v');
	//$http->put('/','key=val');
	//$http->delete('/','key2=val2');
	$http->dispatch();
	$base->dispatch();

	echo 'FINISHED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;

}catch(\Exception $e){
	echo $e->getMessage().PHP_EOL;
}
