<?php

/**
 * Object_Freezer
 *
 * Copyright (c) 2008-2012, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Object_Freezer
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since      File available since Release 1.0.0
 */


namespace Phreezer\Storage;

use \Phreezer\Storage\CouchDB\Context;
use \Phreezer\Storage;
use \SimpleHttpClient\SimpleHttpClient;

class CouchDB
{
	use \Phreezer\Storage\CouchDB\Config;

	public $database;
	/**
	 * @var array
	 */
	protected $revisions = [];

	public $_view;
	public $_list;
	public $_show;
	public $_filter;
	public $_repl;
	public $transport;

	private $config;

	/**
	 * Constructor.
	 *
	 * @param  string            $database      Name of the database to be used
	 * @param  Phreezer          $freezer       Phreezer instance to be used
	 * @param  boolean           $useLazyLoad   Flag that controls whether objects are fetched using lazy load or not
	 * @param  string            $host          Hostname of the CouchDB instance to be used
	 * @param  int               $port          Port of the CouchDB instance to be used
	 * @throws Exception
	 */
	public function __construct(array $config = [])
	{

		$this->setOptions($config);

		$this->transport = new SimpleHttpClient([
			'scheme'      => $this->getScheme(),
			'host'        => $this->getHost(),
			'port'        => $this->getPort(),
			'user'        => $this->getUser(),
			'pass'        => $this->getPassword(),
			'base'        => $this->getBase(),
			'dns_base'    => $this->getDnsBase(),
			'contentType' => $this->getContentType()
		]);

		$this->database = $this->getDatabase();
		$this->_view = $this->getViewService();
		$this->_list = $this->getListService();
		$this->_show = $this->getShowService();
		$this->_filter = $this->getFilterService();
		$this->_repl = $this->getReplService();
		// var_dump($this->getViewService());
	}

	public function getContext(){
		return new Context($this->config, $this->transport->getContext());
	}

	public function getId(){
		return $this->freezer->getId();
	}
}
