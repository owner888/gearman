<?php

class blah implements JsonSerializable
{
	public $a;
	public $b;
	public $c;
	public function blah(){
		return 'blahblah';
	}

	public function JsonSerialize(){
		return [
			'a'=>$this->a,
			'b'=>$this->b,
			'c'=>$this->c,
			'_delete'=>empty($this->_delete) ? null : $this->_delete,
			'__phreezer_hash'=>empty($this->__phreezer_hash) ? null : $this->__phreezer_hash
		];
	}
}

// Demonstrates objects which implement JsonSerializable

require_once(dirname(__DIR__).'/vendor/autoload.php');

use Phreezer\Storage\CouchDB;

$lazyProxy = false;
$blacklist = array();
$useAutoload = true;

$start = microtime(true);

$client = new CouchDB([
	'database'  => 'phreezer_tests',
	'host'      => 'datashovel_couchdb',
//	'user'      => '{{USERNAME}}',
//	'pass'      => '{{PASSWORD}}'
]);

$ids = [];
for($x=0; $x<2; $x++){
	$obj = new blah();
	$obj->a = 1+$x;
	$obj->b = 2+$x;
	$obj->c = 3+$x;
	echo 'STORING RECORD: ';
	$couch = $client->getContext();
	$ids[] = $id = $couch->store($obj);
	echo $id.PHP_EOL;
}
echo PHP_EOL;

foreach($ids as $id){
	echo 'FETCHING: '.$id.PHP_EOL;
	$couch = $client->getContext();
	$obj = $couch->fetch($id);

	echo 'UPDATING: '.$obj->a.' TO "'.$obj->blah().'"'.PHP_EOL;
	$obj->a = $obj->blah();

	echo 'STORING UPDATED VERSION OF: '.$id.PHP_EOL;
	$couch = $client->getContext();
	$couch->store($obj);

	echo PHP_EOL;
}
echo PHP_EOL;

// verify hashing function prevents resubmission of duplicate object
foreach($ids as $id){
	echo 'FETCHING: '.$id.PHP_EOL;
	$couch = $client->getContext();
	$obj = $couch->fetch($id);

	echo 'STORING SAME VERSION OF: '.$id.PHP_EOL;
	$couch = $client->getContext();
	$couch->store($obj);

	echo PHP_EOL;
}
echo PHP_EOL;

foreach($ids as $id){
	echo 'FETCHING: '.$id.PHP_EOL;
	$couch = $client->getContext();
	$obj = $couch->fetch($id);

	echo 'DELETING: '.$id.PHP_EOL;
	$obj->_delete = true;
	$couch = $client->getContext();
	$couch->store($obj);

	echo PHP_EOL;
}
echo PHP_EOL;

echo 'COMPLETED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;

