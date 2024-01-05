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
		$buffers = $http->getBuffers('parsed_headers');
		echo PHP_EOL.'PARSED HEADERS:'.PHP_EOL;
		echo '====================='.PHP_EOL;
		foreach($buffers as $buffer){
			var_dump($buffer);
		}

		$base->exit();
	});

	$http->setProcessor(function(callable $cb) use ($http,$base){
		if($http->getCount() < 2){
			$http->setHost('www.google.com');
			$http->get('/');
			$http->dispatch();
		}
		if($http->isDone()){
			$cb();
		}
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
