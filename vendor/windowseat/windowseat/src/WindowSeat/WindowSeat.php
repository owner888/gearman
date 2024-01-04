<?php

namespace WindowSeat;

use \WindowSeat\EventHandlerInterface;
use \WindowSeat\EventInterface;
use \EventBuffer;
use \EventBufferEvent;
use \EventDnsBase;
use \Event;
use \EventUtil;
use \Phreezer\Storage\CouchDB;

class WindowSeat
{
	private $eventHandler;
	private $uri;
	private $config;
	private $couchdb;
	private $bev;
	private $last_seq;
	private $connection;
	private $request;
	private $instructions;
	public $dnsbase;

	public function __construct(CouchConfig $config){
		$this->config = $config;
		$this->instructions = $config->getInstructions();
		if(!empty($this->instructions['thaw'])){
			$this->couchdb = new CouchDB([
				'host'=>$this->config->getHost(),
				'database'=>$this->config->getDbName(),
				'port'=>$this->config->getPort(),
				'base'=>$this->config->getBase()
			]);
		}
	}
	public function getConfig(){
		return $this->config;
	}
	public function getInstructions(){
		return $this->instructions;
	}
	public function getEventHandler(){
		return $this->eventHandler;
	}

	public function setEventHandler(EventHandlerInterface $eh){
		$this->eventHandler = $eh;
	}

	public function initialize(){
		$this->dnsbase = new EventDnsBase($this->config->getBase(),true);
		$this->connect();
	}

	private function connect(){
		$this->bev = new EventBufferEvent(
			$this->config->getBase(),
			null,
			EventBufferEvent::OPT_CLOSE_ON_FREE | EventBufferEvent::OPT_DEFER_CALLBACKS,
			array($this,'readcb'),
			array($this,'writecb'),
			array($this,'eventcb'),
			$this->config->getBase()
		);
		$this->bev->enable(Event::READ|Event::WRITE);
		$output = $this->bev->getOutput();
		$path = $this->config->getPath();
		if(!empty($this->last_seq)){
			$path .= '&since='.$this->last_seq;
		}
		$output->add(implode("\r\n",array(
			'GET '.$path.' HTTP/1.1',
			'Host: '.$this->config->getHost(),
			'Content-Length: 0',
			'Connection: Keep-Alive'
		))."\r\n\r\n");
		$this->bev->connectHost($this->dnsbase,$this->config->getHost(),$this->config->getPort(),EventUtil::AF_UNSPEC);
	}

	public function writecb($bev, $base){
	}

	public function eventcb($bev, $events, $base){
		if(EventBufferEvent::READING & $events){
		}
		if(EventBufferEvent::WRITING & $events){
		}
		if(EventBufferEvent::EOF & $events){
		}
		if(EventBufferEvent::ERROR & $events){
			error_log('ERROR: WindowSeat\CouchWorker '.EventUtil::getLastSocketError(),' '.EventUtil::getLastSocketErrno());
		}
		if(EventBufferEvent::TIMEOUT & $events){
		}
		if(EventBufferEvent::CONNECTED & $events){
		}
	}

	public function readcb($bev,$base){
		$buf = $bev->getInput();
		while($data = trim($buf->readLine(EventBuffer::EOL_ANY))){
			if($parsed = json_decode($data,true)){
				if(!empty($parsed['_deleted'])){
					continue;
				}
				if(isset($parsed['last_seq'])){
					$this->last_seq = $parsed['last_seq'];
					$this->bev->free();
					$this->connect();
				}
				else if(isset($parsed['seq'])){
					if($this->instructions['retrieve_docs']){
						$worker = new CouchWorker($parsed,$this);
						$worker->retrieveDoc();
					}
					else if($this->instructions['thaw']){
						$worker = new CouchWorker($parsed,$this);
						$worker->dispatchThaw($parsed);
					}
					else{
						$ev = $this->eventHandler->createEvent(
							$parsed['id'],
							$this->instructions['parse_json'] ? $parsed : $data
						);
						$this->dispatchEvent($ev);
					}
				}
				else{
					// debugging only.
				}
			}
			else{
				// debugging only.
			}
		}
	}

	public function dispatchEvent(EventInterface $event){
		$this->eventHandler->handle($event);
	}

}
