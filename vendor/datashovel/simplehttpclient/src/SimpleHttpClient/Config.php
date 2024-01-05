<?php

namespace SimpleHttpClient;

use \ReflectionFunction;

trait Config
{

	public function getScheme(){
		return $this->getOption('scheme','http');
	}

	public function setScheme($scheme){
		$this->config['scheme'] = $scheme;
	}

	public function getHost(){
		return $this->getOption('host','localhost');
	}

	public function setHost($host){
		$this->config['host'] = $host;
	}

	public function getPort(){
		return $this->getOption('port',80);
	}

	public function setPort($port){
		$this->config['port'] = $port;
	}

	public function getOption($key, $default = null){
		return empty($this->config[$key]) ? $default : $this->config[$key];
	}

	public function getOptions(){
		return $this->config;
	}

	public function setOption($key, $value){
		$this->config[$key] = $value;
	}

	public function setOptions(Array $config){
		foreach($config as $k => $v){
			$this->config[$k] = $v;
		}
	}

	public function getDebug(){
		return $this->getOption('scheme',false);
	}

	public function setDebug($debug){
		$this->config['debug'] = $debug;
	}

	public function getContentType(){
		return $this->getOption('contentType','application/json');
	}

	public function setContentType($contentType){
		$this->config['contentType'] = $contentType;
	}

	public function getUser(){
		return $this->getOption('user', '');
	}

	public function setUser($user){
		$this->config['user'] = $user;
	}

	public function getPassword(){
		return $this->getOption('pass', '');
	}

	public function setPassword($pass){
		$this->config['pass'] = $pass;
	}

	public function getProcessor(){
		return $this->getOption('processor', null);
	}

	/**
	 * @param $fn - prototype of $fn must be $fn(callable $func)
	 *              callable $func will be the $fn defined in 'setCallback'
	 * @return void
	 *
	 * NOTE:  This will essentially make it possible to "chain" requests together.
	 *        Useful for cases where subsequent requests may be dependent on the
	 *        first request, but you don't want to have to worry about the implementation
	 *        details to make this happen.  You just want the end result when everything is done.
	 */
	public function setProcessor(callable $fn){

		if(empty($this->config['callback'])){
			throw new \Exception('you must first define a callback with setCallback');
		}

		$reflect = new ReflectionFunction($fn);
		$args = $reflect->getParameters();
		if((count($args) > 3) || !$args[0]->isCallable()){
			throw new \Exception('setProcessor callback prototype must be $fn(callable $func)');
		}

		$this->config['processor'] = $fn;
	}

	public function getCallback(){
		return $this->getOption('callback', function(){});
	}

	public function setCallback(callable $fn){
		$this->config['callback'] = $fn;
	}

	public function &getBase(){
		return $this->config['base'];
	}

	public function setBase($base){
		$this->config['base'] = $base;
	}

	public function getDnsBase(){
		return $this->config['dns_base'];
	}

	public function setDnsBase($dns_base){
		$this->config['dns_base'] = $dns_base;
	}
}
