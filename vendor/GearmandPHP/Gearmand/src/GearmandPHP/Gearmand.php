<?php

namespace GearmandPHP;

use \EventBase;
use \EventUtil;
use \EventListener;
use \Event;
use \WindowSeat\WindowSeat;
use \WindowSeat\CouchConfig;
use \GearmandPHP\EventHandler;
use \GearmandPHP\Config;
use \Schivel\Schivel;
use \Phreezer\Storage\CouchDB;
use \GearmandPHP\JobQueue;
use \GearmandPHP\Job;

class Gearmand
{
	private $config;
	private $listener;
	private $base;
	private $couchdb;
	public static $state;
	public static $priority_queue;

	public function __construct(Config $config){

		$this->config = $config;
		$this->dns_base = $config->dns_base;

		// TODO: Consider defining an interface
		//  instead of requiring use of Schivel
		$this->config->base = &$config->base;
		$this->config->dns_base = $this->dns_base;

		$this->config->config['couchdb']['base'] = $this->config->base;
		$this->config->config['couchdb']['dns_base'] = $this->config->dns_base;
		$this->couchdb = new Schivel(new CouchDB(
			$this->config->config['couchdb']
		));

		$this->couchdb->setDebug(true);
		$this->windowseat = new WindowSeat(new CouchConfig(
			$this->config->base,
			$this->config->config['windowseat']
		));
		$this->windowseat->setEventHandler(new EventHandler());
		$this->windowseat->initialize();

		// TODO:
		// Are we recovering from crash?
		// 1) Look at persistent_store and see if there is anything in changes feed
		// 2) Once updated, re-send unfinished jobs
		// 3) Proceed with normal setup

		self::$state = array(
			'worker'=>array(
			),
			'client'=>array(
			),
			'admin'=>array(),
			'jobs'=>array()
		);

		self::$priority_queue = new JobQueue();

		//$this->persistent_store = $config->store;
		$this->client_listener = new EventListener($this->config->base,
			array($this, 'clientConnect'), $this->config->base,
			EventListener::OPT_CLOSE_ON_FREE | EventListener::OPT_REUSEABLE, -1,
			$config->server['ip'].':'.$config->server['client_port']
		);

		$this->worker_listener = new EventListener($this->config->base,
			array($this, 'workerConnect'), $this->config->base,
			EventListener::OPT_CLOSE_ON_FREE | EventListener::OPT_REUSEABLE, -1,
			$config->server['ip'].':'.$config->server['worker_port']
		);

		$this->admin_listener = new EventListener($this->config->base,
			array($this, 'adminConnect'), $this->config->base,
			EventListener::OPT_CLOSE_ON_FREE | EventListener::OPT_REUSEABLE, -1,
			$config->server['ip'].':'.$config->server['admin_port']
		);

		$this->client_listener->setErrorCallback(array($this, "accept_error_cb"));
		$this->worker_listener->setErrorCallback(array($this, "accept_error_cb"));
		$this->admin_listener->setErrorCallback(array($this, "accept_error_cb"));

	}

	public function run(){
		$this->config->base->loop();
	}

	public function __destruct() {
		foreach (self::$state as &$c) $c = NULL;
	}

	public function dispatch() {
		$this->config->base->dispatch();
	}

	public function clientConnect($listener, $fd, $address, $ctx) {
		$base = $this->config->base;
		$ident = $this->getUUID('client');
		self::$state['client'][$ident] = array(
			'connection'=>new ClientConnection($base, $fd, $ident, $this->couchdb)
		);
	}

	public function workerConnect($listener, $fd, $address, $ctx) {
		$base = $this->config->base;
		$ident = $this->getUUID('worker');
		self::$state['worker'][$ident] = array(
			'connection'=>new WorkerConnection($base, $fd, $ident, $this->couchdb)
		);
	}

	public function adminConnect($listener, $fd, $address, $ctx) {
		$base = $this->config->base;
		$ident = $this->getUUID('admin');
		self::$state['admin'][$ident] = array(
			'connection'=>new AdminConnection($base, $fd, $ident, $this->couchdb)
		);
	}



	public static function setJobState($ident, $key, $value){
		self::$state['job'][$ident][$key] = $value;
	}

	public static function setAdminState($ident, $key, $value){
		if(!in_array($key,array('connection'))){
			self::$state['admin'][$ident][$key] = $value;
		}
	}

	public static function setClientState($ident, $key, $value){
		if(!in_array($key,array('connection'))){
			self::$state['client'][$ident][$key] = $value;
		}
	}

	public static function clientAddOption($ident, $option_name){
		self::$state['client'][$ident]['options'][$option_name] = true;
	}

	public static function clientGetOptions($ident){
		return array_keys(self::$state['client'][$ident]['options']);
	}

	public static function setWorkerState($ident, $key, $value){
		if(!in_array($key,array('connection'))){
			self::$state['worker'][$ident][$key] = $value;
		}
	}

	public static function workerAddOption($ident, $option_name){
		self::$state['worker'][$ident]['options'][$option_name] = true;
	}

	public static function workerGetOptions($ident){
		return array_keys(self::$state['worker'][$ident]['options']);
	}

	public static function workerAddFunction($ident, $function_name, $timeout = 0){
		self::$state['worker'][$ident]['functions'][$function_name] = $timeout;
	}

	public static function workerRemoveFunction($ident, $function_name){
		if(isset(self::$state['worker'][$ident]['functions'][$function_name])){
			unset(self::$state['worker'][$ident]['functions'][$function_name]);
		}
	}



	public static function getJobState($ident, $key){
		return self::getState('job',$ident,$key);
	}

	public static function getAdminState($ident, $key){
		return self::getState('admin',$ident,$key);
	}

	public static function getClientState($ident, $key){
		return self::getState('client',$ident,$key);
	}

	public static function getWorkerState($ident, $key){
		return self::getState('worker',$ident,$key);
	}

	private static function getState($type,$ident,$key){
		if(!isset(self::$state[$type][$ident][$key])){
			return null;
		}
		return self::$state[$type][$ident][$key];
	}

	public static function registerJob($ident, Job $job){
		self::$state['jobs'][$ident] = $job;
	}

	public static function unregisterJob($job_uuid){
		unset(self::$state['jobs'][$job_uuid]);
	}



	public function accept_error_cb($listener, $ctx) {
		$base = $this->config->base;

		fprintf(STDERR, "Got an error %d (%s) on the listener. "
			."Shutting down.\n",
			EventUtil::getLastSocketErrno(),
			EventUtil::getLastSocketError());

		$base->exit(NULL);
	}

	private function getUUID($type){
		$uuid = sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
		if(empty(self::$state[$type][$uuid])){
			return $uuid;
		}
		else{
			return $this->getUUID($type);
		}
	}

	private function E($val){
		error_log(var_export($val,true));
	}
}
