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

class Cache_Storage_Memcached extends \Fuel\Core\Cache_Storage_Memcached {

	
	/**
	 * dump all memcached data.
	 */
	public function get_all(){
		$indexs = $this->_get_index();
		$cache_index_list = $indexs[2];
		$cache_list = array();
		if ($cache_index_list){
			foreach ($cache_index_list as $cache_key => $index_values){
				$instance = new self($cache_key, $this->config);
				try{
					$cache_data = $instance->get();
				} catch (\Exception $e) {
					$cache_data = '';
				}
				$cache_list[$cache_key] = array('data' => $cache_data, 'hash_key' => $index_values[0]);
			}
		}
		return $cache_list;
    }
}
