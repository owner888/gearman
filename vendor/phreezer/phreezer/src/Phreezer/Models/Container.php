<?php

namespace Phreezer\Models;

class Container
{
	public $objects;
	public $_delete;

	public function __construct(){
		$this->objects = array();
		$this->_delete = true;
	}
}
