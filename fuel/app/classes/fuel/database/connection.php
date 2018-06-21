<?php

/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       http://fuelphp.com
 */

abstract class Database_Connection extends \Fuel\Core\Database_Connection {

	/**
	 * トランザクションスタートされたインスタンス配列
	 * @var array[name] = Database_Connection
	 */
	public static $started_instances = array();

	/**
	 * コミットされたインスタンス配列
	 * @var array[name] = Database_Connection
	 */
	public static $committed_instances = array();

	/**
	 *
	 * @param string $name
	 * @param array $config
	 * @param string $writable
	 * @return Database_Connection
	 */
	public static function instance($name = null, array $config = null, $writable = true){

		$config = \Config::get('db.'.$name);
		
		$instance = parent::instance($name, $config, $writable);

		if(preg_match('/master/', $name) == 1){
			if(isset(self::$started_instances[$name]) == FALSE){
				$instance->start_transaction();
				self::$started_instances[$name] = $instance;
			}
		}

		return $instance;
	}

	/**
	 * 全コミット
	 */
	public static function allCommit(){

		foreach(self::$started_instances as $name => $instance){
			$instance->commit_transaction();
			self::$committed_instances[$name] = $instance;
		}
	}

	/**
	 * 全ロールバック
	 */
	public static function allRollback(){

		foreach(self::$started_instances as $instance){
			$instance->rollback_transaction();
		}
	}
}
