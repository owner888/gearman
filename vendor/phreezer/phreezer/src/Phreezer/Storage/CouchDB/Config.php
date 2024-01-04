<?php

namespace Phreezer\Storage\CouchDB;

use \Phreezer\Storage\CouchDB\View;
use \Phreezer\Phreezer;
use \EventBase;
use \EventDnsBase;
use \ReflectionFunction;

trait Config
{
	private $services;
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
		return $this->getOption('port','5984');
	}
	public function setPort($port){
		$this->config['port'] = $port;
	}

	public function getUser(){
		return $this->getOption('user','');
	}
	public function setUser($user){
		$this->config['user'] = $user;
	}

	public function getPassword(){
		return $this->getOption('pass','');
	}
	public function setPassword($password){
		$this->config['pass'] = $password;
	}

	public function &getBase(){
		$value = $this->getOption('base', null);
		return $value;
	}
	public function setBase($base){
		$this->config['base'] = $base;
	}

	public function getDnsBase(){
		return $this->getOption('dns_base',null);
	}
	public function setDnsBase($dnsBase){
		$this->config['dns_base'] = $dnsBase;
	}

	public function getContentType(){
		return $this->getOption('contentType','application/json');
	}
	public function setContentType($contentType){
		$this->config['contentType'] = $contentType;
	}

	public function getOption($key, $default = null){
		switch($key){
			case 'base':
				$default = new EventBase();
				break;
			case 'dns_base':
				$default = new EventDnsBase($this->config['base'], true);
				break;
		}
		if(empty($this->config[$key])){
			$this->config[$key] = $default;
		}
		return $this->config[$key];
	}
	public function setOption($key, $value){
		return $this->config[$key] = $value;
	}
	public function getOptions(){
		return $this->config;
	}
	public function setOptions(array $options){
		foreach($options as $k=>$v){
			if($k === 'services'){
				continue;
			}
			$this->config[$k] = $v;
		}
	}

	public function getLazyProxy(){
		return $this->getOption('lazyProxy',false);
	}
	public function setLazyProxy($lazyProxy){
		$this->config['lazyProxy'] = $lazyProxy;
	}

	public function getDatabase(){
		return $this->getOption('database','');
	}
	public function setDatabase($database){
		$this->config['database'] = $database;
	}

	public function getDebug(){
		return $this->getOption('debug',false);
	}
	public function setDebug($debug){
		$this->config['debug'] = $debug;
	}

	public function getFreezer(){
		return $this->getOption('freezer', new Phreezer([
			'blacklist'=>array(),
			'autoload'=>true
		]));
	}
	public function setFreezer($freezer){
		$this->config['freezer'] = $freezer;
	}



	public function getService($key, $default = null){
		if(empty($this->services[$key])){
			$this->services[$key] = $default;
		}
		return $this->services[$key];
	}
	public function setService($key, $value){
		$this->services[$key] = $value;
	}
	public function getServices(){
		return $this->services;
	}
	public function setServices(array $services){
		$this->services = $services;
	}

	public function getViewService(){
		return $this->getService('_view',new View($this));
	}
	public function setViewService($service){
		$this->setService('_view', $service);
	}

	public function getListService(){
		return $this->getService('_list',null);
	}
	public function setListService($service){
		$this->setService('_list', $service);
	}

	public function getShowService(){
		return $this->getService('_show',null);
	}
	public function setShowService($service){
		$this->setService('_show', $service);
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

		if(empty($this->context->getCallback())){
			throw new \Exception('you must first define a callback with setCallback');
		}

		$reflect = new ReflectionFunction($fn);
		$args = $reflect->getParameters();
		if((count($args) > 3) || !$args[0]->isCallable()){
			throw new \Exception('setProcessor callback prototype must be $fn(callable $func)');
		}

		$this->context->setProcessor($fn);
	}

	public function getCallback(){
		return $this->context->getOption('callback', null);
	}

	public function setCallback(callable $fn){
		$this->context->setCallback($fn);
	}




	public function getFilterService(){
		return $this->getService('_filter',null);
	}
	public function setFilterService($service){
		$this->setService('_filter', $service);
	}

	public function getReplService(){
		return $this->getService('_repl',null);
	}
	public function setReplService($service){
		$this->setService('_repl', $service);
	}
}
