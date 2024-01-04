<?php

namespace WindowSeat;

interface EventInterface
{
	public function __construct($event = null);

	public function getEvent();

	public function setEvent($event);
}
