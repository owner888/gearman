<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use Phixd\Phixd;

Phixd::on('blah',function(){
	echo 'blah'.PHP_EOL;
});

Phixd::emit('blah');
$blah = new blah();

class blah
{
	public function __construct(){
		Phixd::emit('blah');
	}
}
