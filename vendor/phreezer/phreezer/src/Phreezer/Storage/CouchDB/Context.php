<?php

namespace Phreezer\Storage\CouchDB;

use \Phixd\Phixd;
use \Phreezer\Cache;

class Context
{
	use \Phreezer\Storage\CouchDB\Config;

	private $config;
	public $context;
	private $lastResults;
	private static $revisions;

	public function __construct(array $config, $context = null){
		$this->context = empty($context) ? new \SimpleHttpClient\Context($config) : $context;
		$this->config = $config;
	}

	/**
	 * Freezes an object and stores it in the object storage.
	 *
	 * @param array $frozenObject
	 */
	protected function doStoreWithCallback(array $frozenObject)
	{
		$payload = ['docs' => []];

		foreach ($frozenObject['objects'] as $id => $object) {
			$revision = NULL;

			if(isset(self::$revisions[$id])){
				$revision = self::$revisions[$id];
			}
			else if(!empty($object['_rev'])){
				$revision = $object['_rev'];
			}

			$data = [
				'_id'	=> $id,
				'_rev'	=> $revision,
				'class' => $object['class'],
				'state' => $object['state']
			];

			if(isset($data['state']['_delete'])){
				$data['_deleted'] = true;
				unset($object['state']['_delete']);
			}

			if (!$data['_rev']) {
				unset($data['_rev']);
			}

			$payload['docs'][] = $data;
		}

		if (!empty($payload['docs'])) {
			$this->context->post('/' . $this->getDatabase() . '/_bulk_docs', json_encode($payload));
			$this->context->dispatch();
		}
	}

	/**
	 * Freezes an object and stores it in the object storage.
	 *
	 * @param array $frozenObject
	 */
	protected function doStore(array $frozenObject)
	{
		$payload = ['docs' => []];

		foreach ($frozenObject['objects'] as $id => $object) {
			$revision = NULL;

			if(isset(self::$revisions[$id])){
				$revision = self::$revisions[$id];
			}
			else if(!empty($object['_rev'])){
				$revision = $object['_rev'];
			}

			$data = [
				'_id'	=> $id,
				'_rev'	=> $revision,
				'class' => $object['class'],
				'state' => $object['state']
			];

			if(isset($data['state']['_delete'])){
				$data['_deleted'] = true;
				unset($object['state']['_delete']);
			}

			if (!$data['_rev']) {
				unset($data['_rev']);
			}

			$payload['docs'][] = $data;
		}

		if (!empty($payload['docs'])) {
			$response = $this->send(
				'POST',
				'/' . $this->getDatabase() . '/_bulk_docs',
				json_encode($payload)
			);

			$this->setLastResults($response);

			if($this->getDebug()){
				$this->E($response);
			}

			if ((strpos($response['headers'], 'HTTP/1.0 201 Created') !== 0)
				&& (strpos($response['headers'], 'HTTP/1.0 200 OK') !== 0)) {
				// @codeCoverageIgnoreStart
				throw new \Exception('Could not save objects.');
				// @codeCoverageIgnoreEnd
			}

			$data = json_decode($response['body'], TRUE);

			foreach($data as $v){
				if(isset($v['ok']) && isset($v['id']) && isset($v['rev'])){
					self::$revisions[$v['id']] = $v['rev'];
				}
			}

		}
	}

	/**
	 * Fetches a frozen object from the object storage and thaws it.
	 *
	 * @param  string $id The ID of the object that is to be fetched.
	 * @param  array  $objects Only used internally.
	 * @return object
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	protected function doFetchWithCallback($id, array &$objects = [])
	{
		$isRoot = empty($objects);

		if (!isset($objects[$id])) {
			$this->context->get('/' . $this->getDatabase() . '/' . urlencode($id));
			$this->context->dispatch();
		}
	}


	/**
	 * Fetches a frozen object from the object storage and thaws it.
	 *
	 * @param  string $id The ID of the object that is to be fetched.
	 * @param  array  $objects Only used internally.
	 * @return object
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	protected function doFetch($id, array &$objects = [])
	{
		$isRoot = empty($objects);

		if (!isset($objects[$id])) {

			$response = $this->send(
				'GET', '/' . $this->getDatabase() . '/' . urlencode($id)
			);

			if($this->getDebug()){
				$this->E($response);
			}

			if (strpos($response['headers'], 'HTTP/1.0 200 OK') !== 0) {
				throw new \Exception(
					sprintf('Object with id "%s" could not be fetched.', $id)
				);
			}

			$object = json_decode($response['body'], TRUE);

			$objects[$id] = [
				'_rev' => $object['_rev'],
				'class' => $object['class'],
				'state' => $object['state']
			];

			if(!empty($object['_rev'])){
				$objects[$id]['state']['__phreezer_rev'] = $object['_rev'];
			}

			if (!$this->getLazyProxy()) {
				$this->fetchArray($object['state'], $objects);
			}
		}

		if ($isRoot) {
			return ['root' => $id, 'objects' => $objects];
		}
	}

	/**
	 * Sends an HTTP request to the CouchDB server.
	 *
	 * @param  string $method
	 * @param  string $url
	 * @param  string $payload
	 * @return array
	 * @throws Exception
	 */
	public function send($method, $url, $payload = NULL)
	{
		switch(strtolower($method)){
			case 'get':
				if($this->getDebug()){
					$this->E($url);
				}
				$this->context->get($url);
				break;
			case 'post':
				if($this->getDebug()){
					$this->E($url);
					$this->E($payload);
				}
				$this->context->post($url, $payload);
				break;
		}
		$this->context->fetch();
		$buffers = $this->context->getBuffers(function($doc){
			return explode("\r\n\r\n", $doc, 2);
		});

		return ['headers' => $buffers[1][0], 'body' => $buffers[1][1]];
	}


	/**
	 * Fetches a frozen array from the object storage and thaws it.
	 *
	 * @param array $array
	 * @param array $objects
	 */
	protected function fetchArrayWithCallback(array &$array, array &$objects = array())
	{
		foreach ($array as &$value) {
			if (is_array($value)) {
				$this->fetchArrayWithCallback($value, $objects);
			}

			else if (is_string($value) &&
					 strpos($value, '__phreezer_') === 0) {
				$uuid = str_replace('__phreezer_', '', $value);

				if (!$this->getLazyProxy()) {
					$this->doFetchWithCallback($uuid, $objects);
				} else {
					$value = new LazyProxy($this, $uuid);
				}
			}
		}
	}

	/**
	 * Fetches a frozen array from the object storage and thaws it.
	 *
	 * @param array $array
	 * @param array $objects
	 */
	protected function fetchArray(array &$array, array &$objects = array())
	{
		foreach ($array as &$value) {
			if (is_array($value)) {
				$this->fetchArray($value, $objects);
			}

			else if (is_string($value) &&
					 strpos($value, '__phreezer_') === 0) {
				$uuid = str_replace('__phreezer_', '', $value);

				if (!$this->getLazyProxy()) {
					$this->doFetch($uuid, $objects);
				} else {
					$value = new LazyProxy($this, $uuid);
				}
			}
		}
	}

	public function fetch($rootid, callable $cb = null)
	{
		// Bail out if a non-string was passed.
		if (!is_string($rootid)) {
			throw Util::getInvalidArgumentException(1, 'string');
		}

		if(!empty($cb)){
			$this->fetchWithCallback($rootid, $cb);
			return;
		}

		if($this->getBase()->gotStop()){
			$this->getBase()->reInit();
			$this->context = new \SimpleHttpClient\Context($this->config);
		}
		// Try to retrieve object from the object cache.
		$object = Cache::get($rootid);

		if (!$object) {
			// Retrieve object from the object storage.
			$frozenObject = $this->doFetch($rootid);
			$this->fetchArray($frozenObject['objects'][$rootid]['state']);
			$object = $this->getFreezer()->thaw($frozenObject);

			// Put object into the object cache.
			Cache::put($rootid, $object);
		}
		Phixd::emit('phreezer.fetch.after', [$object]);
		return $object;
	}

	/**
	 * Freezes an object and stores it in the object storage.
	 *
	 * @param  object $object The object that is to be stored.
	 * @return string
	 */
	public function store($object, callable $cb = null)
	{
		// Bail out if a non-object was passed.
		if (!is_object($object)) {
			throw Util::getInvalidArgumentException(1, 'object');
		}

		Phixd::emit('phreezer.store.before', [$object]);

		if(!empty($cb)){
			$this->storeWithCallback($object, $cb);
			return;
		}
		else{
			if($this->getBase()->gotStop()){
				$this->getBase()->reInit();
				$this->context = new \SimpleHttpClient\Context($this->config);
			}
			$this->doStore($this->getFreezer()->freeze($object));
			return $object->__phreezer_uuid;
		}
	}



	private function fetchWithCallback($rootid, callable $cb){
		$objects = array();
		$this->setCallback($cb);
		$cl = function(callable $cb, $count, $buffer) use ($rootid, &$objects) {

			if($this->getDebug()){
				$this->E($buffer);
			}

			list($headers, $body) = explode("\r\n\r\n", $buffer, 2);

			$object = json_decode($body, TRUE);

			$uuid = $object['_id'];
			if (strpos($headers, 'HTTP/1.0 200 OK') !== 0) {
				$cb(null);
				return;
			}

			if(empty($object['class']) || empty($object['state'])){
				$cb(null);
				return;
			}

			$objects[$uuid] = [
				'_rev' => $object['_rev'],
				'class' => $object['class'],
				'state' => $object['state']
			];

			if($uuid === $rootid){
				$this->fetchArrayWithCallback($object['state'], $objects);
			}
			else if(!$this->getLazyProxy()){
				$this->fetchArrayWithCallback($object['state'], $objects);
			}

			if($this->context->isDone()){
				$frozenObject = ['root'=>$rootid, 'objects'=>$objects];
				$object = $this->getFreezer()->thaw($frozenObject);
				$cb($object);
			}
		};
		$this->setProcessor($cl->bindTo($this));

		$this->doFetchWithCallback($rootid);
	}


	private function storeWithCallback($object, callable $cb){
		$this->setCallback($cb);

		$this->setProcessor(function(callable $cb,$key,$buffer) use ($object) {

			if($this->getDebug()){
				$this->E($buffer);
			}

			$this->setLastResults($buffer);

			list($headers,$body) = explode("\r\n\r\n",$buffer,2);

			if ((strpos($headers, 'HTTP/1.0 201 Created') !== 0)
				&& (strpos($headers, 'HTTP/1.0 200 OK') !== 0)) {
				// @codeCoverageIgnoreStart
				$cb(null);
				return;
				// @codeCoverageIgnoreEnd
			}

			$data = json_decode($body, TRUE);

			foreach($data as $v){
				if(isset($v['ok']) && isset($v['id']) && isset($v['rev'])){
					self::$revisions[$v['id']] = $v['rev'];
				}
			}

			if($this->context->isDone()){
				$cb($object->__phreezer_uuid);
			}
		});

		$this->doStoreWithCallback($this->getFreezer()->freeze($object));
	}

	public function getLastResults(){
		if(!empty($this->lastResults['headers'])){
			return array(
				'headers'=>$this->context->parseHeaders($this->lastResults['headers']),
				'body' => json_decode($this->lastResults['body'],true)
			);
		}
		return $this->lastResults;
	}

	public function setLastResults($results){
		$this->lastResults = $results;
	}


	private function E($value){
		error_log(var_export($value,true));
	}
}
