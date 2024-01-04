<?php

/**
 *  To be compliant with the LICENSE from igorw/evenement
 *  as long as the code closely resembles that project
 *
  Copyright (c) 2011 Igor Wiedler

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is furnished
  to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in all
  copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  THE SOFTWARE. 
 */
namespace Phixd;

class Phixd
{
	protected static $listeners = [];

	public static function on($event, callable $listener)
	{
		if (!isset(self::$listeners[$event])) {
			self::$listeners[$event] = [];
		}

		self::$listeners[$event][] = $listener;
	}

	public static function once($event, callable $listener)
	{
		$onceListener = function () use (&$onceListener, $event, $listener) {
			self::removeListener($event, $onceListener);

			call_user_func_array($listener, func_get_args());
		};

		self::on($event, $onceListener);
	}

	public static function removeListener($event, callable $listener)
	{
		if (isset(self::$listeners[$event])) {
			if (false !== $index = array_search($listener, self::$listeners[$event], true)) {
				unset(self::$listeners[$event][$index]);
			}
		}
	}

	public static function removeAllListeners($event = null)
	{
		if ($event !== null) {
			unset(self::$listeners[$event]);
		} else {
			self::$listeners = [];
		}
	}

	public static function listeners($event)
	{
		return isset(self::$listeners[$event]) ? self::$listeners[$event] : [];
	}

	public static function getListeners(){
		return isset(self::$listeners) ? self::$listeners : [];
	}

	public static function emit($event, array $arguments = [])
	{
		foreach (self::listeners($event) as $listener) {
			call_user_func_array($listener, $arguments);
		}
	}
}
