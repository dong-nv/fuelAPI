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


class Model_Crud extends \Fuel\Core\Model_Crud
{

	protected static $_sharding_num		= 2;	// 分割数
	public static $sharding_mid	= 0;	// 分割する際の基準値( $_sharding_value % $_sharding_num = num)

	public static function setSharding($sharding_mid = 0){
             static::$sharding_mid = $sharding_mid;
	}

	// 分割ID計算
	public static function getShardingId($sharding_mid = 0){
		$sharding_value = substr($sharding_mid, -1, 1); //
        return ($sharding_value % static::$_sharding_num) + 1; //下一桁で分割するIDを計算する。
	}

	/**
	 * Sets up the object.
	 *
	 * @param   array  $data  The data array
	 */
	public function __construct(array $data = array())
	{
                
		$this->set($data);

		if (isset($this->_data[static::primary_key()]))
		{
			$this->is_new(false);
		}
	}

	/**
	 * Get the connection to use for reading or writing
	 *
	 * @param  boolean  $writable Get a writable connection
	 * @return Database_Connection
	 */
	protected static function get_connection($writable = false)
	{
               
		// sharding
		if(isset(static::$_sharding) && static::$_sharding === true){
			// sharding
			$shardingID = self::getShardingId(static::$sharding_mid);//テストするため、固定値設定
                        
			if ($writable and isset(static::$_write_connection))
			{
				return static::$_write_connection.$shardingID;
			}
                        
			return isset(static::$_connection) ? static::$_connection.$shardingID : null;
			
			
		}else{

			if ($writable and isset(static::$_write_connection))
			{
				return static::$_write_connection;
			}

			return isset(static::$_connection) ? static::$_connection : null;
		}
	}


}
