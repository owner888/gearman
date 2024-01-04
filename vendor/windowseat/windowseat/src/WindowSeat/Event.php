<?php

namespace WindowSeat;

use \WindowSeat\EventInterface;

class Event implements EventInterface
{
	private $content;
	public function __construct($content = null){
		$this->content = $content;
	}

	public function getEvent(){
		return $this->content;
	}

	public function setEvent($event){
		$this->content = $event;
	}
}
