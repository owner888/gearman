<?php

/**
 *  Example assumes you will set up your own mock view to query.
 */

require_once(dirname(dirname(__DIR__)).'/vendor/autoload.php');

use Phreezer\Storage\CouchDB;

$lazyProxy = false;
$blacklist = array();
$useAutoload = true;

$start = microtime(true);

$base = new EventBase();
$dns_base = new EventDnsBase($base,true);

$client = new CouchDB([
	'database'  => 'phreezer_tests',
	'host'      => 'datashovel_couchdb',
	'base'      => $base,
	'dns_base'  => $dns_base
//	'user'      => '{{USERNAME}}',
//	'pass'      => '{{PASSWORD}}'
]);

$view = 'testview';

$couch = $client->getContext();


$couch->getViewService()->async($view, array(
	'query'=>array('keys'=>json_encode(array('jane','john')),'include_docs'=>'true'),
	'opts'=>array(
		'format'=>'array',
		'thaw'=>true
	)
));

$couch->getViewService()->async($view, array(
	'query'=>array('key'=>json_encode('jane'),'include_docs'=>'true'),
	'opts'=>array(
		'format'=>'array',
		'thaw'=>true
	)
));


$couch->getViewService()->dispatch(function($buffers) use($base, $start) {
	var_dump($buffers);
	$base->exit();
	echo 'FINISHED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;
});

$base->dispatch();

class ViewTestClass
{
	public $name;
	public $microtime;
}

