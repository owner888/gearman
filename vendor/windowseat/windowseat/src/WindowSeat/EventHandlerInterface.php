<?php

namespace WindowSeat;

use \WindowSeat\EventInterface;

interface EventHandlerInterface
{
	public function handle(EventInterface $event);

	public function createEvent($id, $data = null);
}
