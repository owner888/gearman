<?php

namespace WindowSeat;

use \WindowSeat\EventHandlerInterface;
use \WindowSeat\EventInterface;
use \WindowSeat\Event as WindowSeatEvent;

class EventHandler implements EventHandlerInterface
{
	public function __construct(){}

	public function handle(EventInterface $event){
		$this->sendToErrorLog($event->getEvent());
	}
	public function createEvent($id, $data = null){
		return new WindowSeatEvent($data);
	}
	private function sendToErrorLog($data){
		error_log(var_export($data,true));
	}
}
