<?php

/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @link       http://fuelphp.com
 */

class Input extends \Fuel\Core\Input{

	/**
	 * @var  $mpk  parsed request body as mpk
	 */
	protected static $mpk = null;

	/**
	 * Get the request body interpreted as MessagePack.
	 *
	 * @param   mixed  $index
	 * @param   mixed  $default
	 * @return  array  parsed request body content.
	 */
	public static function mpk($index = null, $default = null)
	{
		static::$mpk === null and static::hydrate_raw_input('mpk');
		return (func_num_args() === 0) ? static::$mpk : \Arr::get(static::$mpk, $index, $default);
	}

	
}
