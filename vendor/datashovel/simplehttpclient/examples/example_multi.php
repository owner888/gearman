<?php

/**
 *  Shows how to send multiple requests simultaneously
 */
    
require_once(dirname(__DIR__).'/vendor/autoload.php');

use SimpleHttpClient\SimpleHttpClient;

try{

	$client = new SimpleHttpClient([
		'host'=>'download.finance.yahoo.com',
		'port'=>80,
		'contentType'=>'text/csv'
	]);
	$start = microtime(true);

	$http = $client->getContext();

	$http->get('/d/quotes.csv?s=GOOG&f=csv');
	$http->get('/d/quotes.csv?s=YHOO&f=csv');
	//$http->post('/', 'k=v');
	//$http->put('/','key=val');
	//$http->delete('/','key2=val2');

	$http->fetch();

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

	echo 'FINISHED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;

}catch(\Exception $e){
	echo $e->getMessage().PHP_EOL;
}
