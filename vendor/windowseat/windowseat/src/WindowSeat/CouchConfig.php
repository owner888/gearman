<?php

namespace WindowSeat;

use \EventBase;

class CouchConfig
{
	private $base;
	private $config;

	public function __construct(EventBase $base, array $config){
		$this->base = $base;
		$this->config = $config;
	}

	public function getHost(){
		return $this->config['host'];
	}

	public function getPath(){
		return '/'.$this->getDbName().'/'.$this->config['path'];
	}

	public function getPort(){
		return $this->config['port'];
	}

	public function getInstructions(){
		return array(
			'parse_json'=> $this->config['parse_json'] ?: false,
			'retrieve_docs'=> $this->config['retrieve_docs'] ?: false,
			'thaw'=> $this->config['thaw'] ?: false,
		);
	}

	public function getDbName(){
		return $this->config['database'];
	}

	public function &getBase(){
		return $this->base;
	}
}
