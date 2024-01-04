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


namespace Phreezer;

use Phreezer\Util;
use Phreezer\Cache;

class Phreezer
{
	/**
	 * @var boolean
	 */
	protected $autoload = TRUE;

	/**
	 * @var array
	 */
	protected $blacklist = [];

	/**
	 * Constructor.
	 *
	 * @param  array                  $blacklist
	 * @param  boolean                $useAutoload
	 * @throws InvalidArgumentException
	 */
	public function __construct(array $options = [])
	{
		$options['blacklist'] = @is_array($options['blacklist']) ? $options['blacklist'] : [];
		$options['autoload'] = @is_bool($options['autoload']) ? $options['autoload'] : TRUE;

		$this->setBlacklist($options['blacklist']);
		$this->setUseAutoload($options['autoload']);
	}

	public function freeze($object, $checkHash = true)
	{
		// Bail out if a non-object was passed.
		if (!is_object($object)) {
			throw Util::getInvalidArgumentException(1, 'object');
		}

		$objects = [];
		$uuid = $this->freezeObject($object, $objects);

		foreach($objects as $uuid=>&$object){
			Cache::delete($uuid);
			$hash = $this->getHash($object);
			if (empty($object['state']['__phreezer_hash']) 
				  || ($object['state']['__phreezer_hash'] !== $hash)
				  || empty($checkHash)) {
				$object['state']['__phreezer_hash'] = $hash;
			}
			else{
				unset($objects[$uuid]);
			}
		}
		return ['root' => $uuid, 'objects' => $objects];
	}

	public function freezeObject(&$object, &$objects){

		// The object has not been frozen before, generate a new UUID and
		// store it in the "special" __phreezer_uuid attribute.
		if (!isset($object->__phreezer_uuid)) {
			$object->__phreezer_uuid = $this->getId();
		}

		$uuid = $object->__phreezer_uuid;

		if(isset($objects[$uuid])){
			return $uuid;
		}

		if (!isset($objects[$uuid])) {
			$objects[$uuid] = [
				'class' => get_class($object),
				'state'     => []
			];

			if(!empty($object->__phreezer_rev)){
				$objects[$uuid]['_rev'] = $object->__phreezer_rev;
			}

			if(in_array('JsonSerializable', class_implements($object))){
				$objects[$uuid]['state'] = json_decode(json_encode($object),true);
			}
			else {
				// Iterate over the attributes of the object.
				foreach (Util::readAttributes($object) as $k => $v) {
					if (!in_array($k,array('__phreezer_uuid','__phreezer_rev'))){

						if (is_array($v)) {
							$this->freezeArray($v, $objects);
						}
						else if (is_object($v) && !in_array(get_class($v), $this->blacklist)) {
							// Freeze the aggregated object.
							$childuuid = $this->freezeObject($v, $objects);

							// Replace $v with the aggregated object's UUID.
							$v = '__phreezer_' . $childuuid;
						}
						else if (is_resource($v)) {
							$v = NULL;
						}

						// Store the attribute in the object's state array.
						$objects[$uuid]['state'][$k] = $v;
					}
				}
			}
		}
		return $uuid;
	}

	/**
	 * Freezes an array.
	 *
	 * @param array $array   The array that is to be frozen.
	 * @param array $objects Only used internally.
	 */
	protected function freezeArray(array &$array, array &$objects)
	{
		foreach ($array as &$value) {
			if (is_array($value)) {
				$this->freezeArray($value, $objects);
			}

			else if (is_object($value)) {
				$childuuid   = $this->freezeObject($value, $objects);
				$value = '__phreezer_' . $childuuid;
			}
		}
	}

	public function thaw(array $frozenObject, $root = NULL, array &$objects = [])
	{
		// Bail out if one of the required classes cannot be found.
		foreach ($frozenObject['objects'] as $object) {
			if (!class_exists($object['class'], $this->useAutoload)) {
				throw new \Exception(
					sprintf(
						'Class "%s" could not be found.', $object['class']
					)
				);
			}
		}

		// By default, we thaw the root object and (recursively)
		// its aggregated objects.
		if ($root === NULL) {
			$root = $frozenObject['root'];
		}

		// Thaw object (if it has not been thawed before).
		if (!isset($objects[$root])) {
			if(!empty($frozenObject['objects'][$root]['_rev'])){
				$rev = $frozenObject['objects'][$root]['_rev'];
			}
			$class = $frozenObject['objects'][$root]['class'];
			$state = $frozenObject['objects'][$root]['state'];
			$reflector = new \ReflectionClass($class);
			$objects[$root] = $reflector->newInstanceWithoutConstructor();

			// Handle aggregated objects.
			$this->thawArray($state, $frozenObject, $objects);

			$reflector = new \ReflectionObject($objects[$root]);

			foreach ($state as $name => $value) {
				if (strpos($name, '__phreezer') !== 0) {
					if ($reflector->hasProperty($name)) {
						$attribute = $reflector->getProperty($name);
						$attribute->setAccessible(TRUE);
						$attribute->setValue($objects[$root], $value);
					} else {
						$objects[$root]->$name = $value;
					}
				}
			}

			// Store UUID.
			$objects[$root]->__phreezer_uuid = $root;

			if(!empty($rev)){
				$objects[$root]->__phreezer_rev = $rev;
			}

			// Store hash.
			if (isset($state['__phreezer_hash'])) {
				$objects[$root]->__phreezer_hash =
				$state['__phreezer_hash'];
			}
		}

		return $objects[$root];
	}

	/**
	 * Thaws an array.
	 *
	 * @param  array   $array        The array that is to be thawed.
	 * @param  array   $frozenObject The frozen object structure from which to thaw.
	 * @param  array   $objects      Only used internally.
	 */
	protected function thawArray(array &$array, array $frozenObject, array &$objects)
	{
		foreach ($array as &$value) {
			if (is_array($value)) {
				$this->thawArray($value, $frozenObject, $objects);
			}

			else if (is_string($value) && (strpos($value, '__phreezer') === 0)) {
				$aggregatedObjectId = str_replace(
					'__phreezer_', '', $value
				);

				if (isset($frozenObject['objects'][$aggregatedObjectId])) {
					$value = $this->thaw(
						$frozenObject, $aggregatedObjectId, $objects
					);
				}
			}
		}
	}

	/**
	 * Returns the blacklist of class names for which aggregates objects are
	 * not frozen.
	 *
	 * @return array
	 */
	public function getBlacklist()
	{
		return $this->blacklist;
	}

	/**
	 * Sets the blacklist of class names for which aggregates objects are
	 * not frozen.
	 *
	 * @param  array $blacklist
	 * @throws InvalidArgumentException
	 */
	public function setBlacklist(array $blacklist)
	{
		$this->blacklist = $blacklist;
	}

	/**
	 * Returns the flag that controls whether or not __autoload()
	 * should be invoked.
	 *
	 * @return boolean
	 */
	public function getUseAutoload()
	{
		return $this->useAutoload;
	}

	/**
	 * Sets the flag that controls whether or not __autoload()
	 * should be invoked.
	 *
	 * @param  boolean $flag
	 * @throws InvalidArgumentException
	 */
	public function setUseAutoload($flag)
	{
		// Bail out if a non-boolean was passed.
		if (!is_bool($flag)) {
			throw Util::getInvalidArgumentException(1, 'boolean');
		}

		$this->useAutoload = $flag;
	}

	protected function getHash(array $object)
	{
		if (isset($object['state']['__phreezer_hash'])) {
			unset($object['state']['__phreezer_hash']);
		}
		return sha1(serialize($object));
	}

	public function getId() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

}
