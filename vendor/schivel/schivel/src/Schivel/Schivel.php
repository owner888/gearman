<?php

namespace Schivel;

use \Phreezer\Storage\CouchDB;

class Schivel
{
	use Traits\SimpleQueries;

	private $db;
	private $couch;
	private $database;
	private $scheme;
	private $host;
	private $port;
	private $user;
	private $pass;

	public function __construct(CouchDB $db){
		$this->couch = $db;
		$this->db = $db->getContext();
	}

	public function setDebug($debug){
		return $this->db->setDebug($debug);
	}

	public function setDatabase($database){
		$this->db->database = $database;
	}

	public function setScheme($scheme){
		$this->db->scheme = $scheme;
	}

	public function setHost($host){
		$this->db->host = $host;
	}

	public function setPort($port){
		$this->db->port = $port;
	}

	public function setUsername($user){
		$this->db->user = $user;
	}

	public function setPassword($pass){
		$this->db->pass = $pass;
	}

	public function store($object, callable $fn = null){
		return $this->db->store($object, $fn);
	}

	public function getId(){
		return $this->db->getId();		
	}

	public function fetch($id, callable $fn = null){
		return $this->db->fetch($id, $fn);
	}

	public function getContext(){
		$this->db = $this->couch->getContext();
	}

	public function delete(&$obj, callable $fn = null){
		$obj->_delete = true;
		$this->db->store($obj, $fn);
		$obj = null;
	}
}
