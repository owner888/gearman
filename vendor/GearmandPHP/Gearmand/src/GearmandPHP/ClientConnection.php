<?php

namespace GearmandPHP;

use \SplQueue;
use \EventDnsBase;
use \EventBufferEvent;
use \GearmandPHP\EventStoreInterface;
use \GearmandPHP\GearmandPHPEvent;
use \GearmandPHP\ClientRequestHandler;
use \GearmandPHP\ClientResponseHandler;

class ClientConnection
{
	private $bev, $base, $buffer, $id, $fd, $headers;

	const MIN_WATERMARK = 1;

	// Request / Response TYPES
	const SUBMIT_JOB = 7;
	const JOB_CREATED = 8;
	const WORK_STATUS = 12;
	const WORK_COMPLETE = 13;
	const WORK_FAIL = 14;
	const GET_STATUS = 15;
	const ECHO_REQ = 16;
	const ECHO_RES = 17;
	const SUBMIT_JOB_BG = 18;
	const ERROR = 19;
	const STATUS_REQ = 20;
	const SUBMIT_JOB_HIGH = 21;
	const WORK_EXCEPTION = 25;
	const OPTION_REQ = 26;
	const OPTION_RES = 27;
	const WORK_DATA = 28;
	const WORK_WARNING = 29;
	const SUBMIT_JOB_HIGH_BG = 32;
	const SUBMIT_JOB_LOW = 33;
	const SUBMIT_JOB_LOW_BG = 34;
	const SUBMIT_JOB_SCHED = 35;
	const SUBMIT_JOB_EPOCH = 36;

	public function __destruct() {
		$this->bev->free();
	}

	public function __construct($base, $fd, $ident, $schivel){
		$this->buffer = '';
		$this->headers = array();
		$this->base = $base;
		$this->fd = $fd;
		$this->ident = $ident;
		$this->id = 0;
		$this->index = null;
		//$this->eventStore = $eventStore;

		$this->schivel = $schivel;

		$dns_base = new EventDnsBase($this->base, TRUE);

		$this->bev = new EventBufferEvent($this->base, $fd,
			EventBufferEvent::OPT_CLOSE_ON_FREE | EventBufferEvent::OPT_DEFER_CALLBACKS
		);

		$this->bev->setCallbacks(
			array($this,'readCallback'),
			array($this,'writeCallback'),
			array($this,'eventCallback')
		);

		$this->bev->setWatermark(\Event::READ|\Event::WRITE, self::MIN_WATERMARK, 0);

		if(!$this->bev->enable(\Event::READ | \Event::WRITE)){
			echo 'failed to enable'.PHP_EOL;
		}
/*
		// If client hasn't sent headers within 3 sec, kill it
		$e = \Event::timer($base, function() use (&$e, $ident){
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
		if(empty($this->headers)){
			if($input->length >= 12){
				switch(chr(implode('',unpack("c",substr($input->read(4),3))))){
					case 'Q':
						$this->headers['which'] = 'REQ';
						$this->handler = new ClientRequestHandler($this->ident, $bev, $this->schivel);
						break;
					case 'S': // clients send requests, not responses
					default:
						// INVALID REQUEST
						break;
				}
				$this->headers['type'] = implode('',unpack('N',$input->read(4)));
				$this->headers['size'] = implode('',unpack('N',$input->read(4)));
			}
		}
		if(isset($this->headers['size'])){
			if($input->length >= $this->headers['size']){
				$data = $input->length > 0 ? substr($input->read($input->length),0,$this->headers['size']) : '';
				$this->handler->handle($this->headers, $data);
				$this->headers = array();
			}
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

	public function sendResponse($type, $message){
		$response = pack('c4',"\0",ord('R'),ord('E'),ord('S'));
		$response.= pack('N',$type);
		$response.= pack('N',strlen($message));
		$response.= $message;

		$output = $this->bev->output;
		return $output->add($response);
	}

}
