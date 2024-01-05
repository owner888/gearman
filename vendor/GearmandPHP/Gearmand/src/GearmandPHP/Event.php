<?php

namespace GearmandPHP;

use \WindowSeat\EventInterface;

class Event implements EventInterface
{
	private $data;

	public function __construct($event = null){
		$this->data = $event;
	}

	public function getEvent(){
		return $this->data;
	}

	public function setEvent($event){
		$this->data = $event;
	}

}
