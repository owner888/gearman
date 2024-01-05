<?php

namespace SimpleHttpClient;

use \SimpleHttpClient\Context;
use \EventBase;
use \EventDnsBase;

class SimpleHttpClient
{
	use \SimpleHttpClient\Config;

	private $config;

	private $errors = array();

	public function __construct(array $config = []){
		$config['base'] = empty($config['base']) ? new EventBase() : $config['base'];
		$config['dns_base'] = empty($config['dns_base']) ? new EventDnsBase($config['base'],true) : $config['dns_base'];

		$this->config = $config;
	}

	public function getContext(){
		return new Context($this->config);
	}
}
