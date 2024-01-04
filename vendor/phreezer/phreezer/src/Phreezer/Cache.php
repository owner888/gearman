<?php

namespace Phreezer;

use Phreezer\Cache;

class Cache
{
	/**
	 * @var array
	 */
	protected static $objects = array(
		'frozen'=>array(),
		'thawed'=>array()
	);

	public static function get($id)
	{
		if (isset(self::$objects['frozen'][$id])) {
			return self::getFrozen($id);
		}
		elseif (isset(self::$objects['thawed'][$id])) {
			return self::getThawed($id);
		}
		else {
			return FALSE;
		}
	}

	public static function put($id, $object)
	{
		if(is_array($object)){
			self::putFrozen($id, $object);
		}
		else{
			self::putThawed($id, $object);
		}
	}

	public static function delete($id){
		self::deleteFrozen($id);
		self::deleteThawed($id);
	}

	protected static function getFrozen($id){
		return @self::$objects['frozen'][$id] ?: false;
	}

	protected static function getThawed($id){
		return @self::$objects['thawed'][$id] ?: false;
	}

	protected static function putFrozen($id, $object){
		self::$objects['frozen'][$id] = $object;
	}

	protected static function putThawed($id, $object){
		self::$objects['thawed'][$id] = $object;
	}

	protected static function deleteFrozen($id){
		if(isset(self::$objects['frozen'][$id])){
			unset(self::$objects['frozen'][$id]);
		}
	}

	protected static function deleteThawed($id){
		if(isset(self::$objects['thawed'][$id])){
			unset(self::$objects['thawed'][$id]);
		}
	}
}
