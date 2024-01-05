<?php

namespace GearmandPHP;

use \SplQueue;
use \Event;
use \EventDnsBase;
use \EventBufferEvent;
use \EventBuffer;
use \GearmandPHP\EventStoreInterface;
use \GearmandPHP\GearmandPHPEvent;
use \GearmandPHP\ClientRequestHandler;
use \GearmandPHP\ClientResponseHandler;

class AdminConnection
{
	private $bev, $base, $buffer, $id, $fd, $headers;

	const MIN_WATERMARK = 1;

	public function __destruct() {
		$this->bev->free();
	}

	public function __construct($base, $fd, $ident, $couchdb){
		$this->buffer = '';
		$this->headers = array();
		$this->base = $base;
		$this->fd = $fd;
		$this->ident = $ident;
		$this->id = 0;
		$this->index = null;
		//$this->eventStore = $eventStore;

		$dns_base = new EventDnsBase($this->base, TRUE);

		$this->bev = new EventBufferEvent($this->base, $fd,
			EventBufferEvent::OPT_CLOSE_ON_FREE | EventBufferEvent::OPT_DEFER_CALLBACKS
		);

		$this->bev->setCallbacks(
			array($this,'readCallback'),
			array($this,'writeCallback'),
			array($this,'eventCallback')
		);

		$this->bev->setWatermark(Event::READ|Event::WRITE, self::MIN_WATERMARK, 0);

		if(!$this->bev->enable(Event::READ | Event::WRITE)){
			echo 'failed to enable'.PHP_EOL;
		}
/*
		// If client hasn't sent headers within 3 sec, kill it
		$e = Event::timer($base, function() use (&$e, $ident){
			if(empty($this->headers)){
				Server::disconnect('client',null,$ident,null);
			}
			$e->delTimer();
		});
		$e->addTimer(3);
*/
	}

	public function readCallback($bev/*, $arg*/) {
		$input = $bev->getInput();
		if($line = $input->readLine(EventBuffer::EOL_ANY)){
			$this->handler = new AdminRequestHandler($bev);
			$this->handler->handle($line);
		}
	}

	public function writeCallback($bev/*, $arg*/) {
	}

	public function eventCallback($bev, $events/*, $arg*/) {
		if ($events & EventBufferEvent::TIMEOUT) {
		}
		if ($events & EventBufferEvent::EOF) {
			//Server::disconnect('client',$this->uuid,$this->ident,$this->index);
		}
		if ($events & EventBufferEvent::ERROR) {
		}
	}

	public function sendResponse($type, $message, $id = null){

		$response = pack('c4',"\0",ord('R'),ord('E'),ord('S'));
		$response.= pack('N',$type);
		$response.= pack('N',strlen($message));
		$response.= $message;

		$output = $this->bev->output;
		return $output->add($response);
	}

}
