<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use Phreezer\Phreezer;
use Phreezer\Storage\CouchDB;

#########################################
// LONG CONSTRUCTOR

$lazyProxy = false;
$blacklist = array();
$useAutoload = true;

$freezer = new Phreezer([
	'blacklist' => $blacklist,
	'autoload'  => $useAutoload
]);

$couch = new CouchDB([
	'database'  => 'phreezer_tests',
	'user'      => '{{USERNAME}}',
	'pass'      => '{{PASSWORD}}',
	'host'      => 'localhost',
	'port'      => 5984,
	'lazyproxy' => $lazyProxy,
	'freezer'   => $freezer
]);
var_dump($couch);


#########################################
// SHORTCUT CONSTRUCTOR : only 'database' is required argument

/**
 * NOTE:  username/password are optional.  It depends
 *    on how your database is configured.
 */

$couch = new CouchDB([
	'database'  => 'phreezer_tests'
]);
var_dump($couch);
