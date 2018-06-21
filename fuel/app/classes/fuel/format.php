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

class Format extends \Fuel\Core\Format{
	
	/**
	 * To MessagePack conversion
	 *
	 * @param   mixed  $data
	 * @return  string
	 */
	public function to_mpk($data = null){
		if ($data === null)
		{
			$data = $this->_data;
		}

		$data = (is_array($data) or is_object($data)) ? $this->to_array($data) : $data;
		return msgpack_pack($data);
	}

	/**
	 * Import MessagePack data
	 *
	 * @param   string  $string
	 * @return  mixed
	 */
	public function _from_mpk($string){

		// 空チェック
		if(strlen($string) == 0 || $string == ""){
			return null;
		}
		return msgpack_unpack($string);

	}

}
