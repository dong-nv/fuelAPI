<?php
/**
 * Profiler Ex
 * @package     Fuel
 * @subpackage  App
 * @category    App
 * The MIT License (MIT)
 * Copyright (c) 2013 Qript.inc
 */
class Profiler extends \Fuel\Core\Profiler
{

	public static $db_logs	= array();
	public static $dbname	= array();

	public static function db_log($log = true){
		if($log){
			Log::debug(print_r(self::$dbname, true));
			Log::debug(print_r(self::$db_logs, true));
		}else{
			return print_r(self::$db_logs, true);
		}
	}

	public static function start($dbname, $sql, $stacktrace = array()){
		self::$dbname[]		= $dbname;
		self::$db_logs[]	= $sql;

		if (static::$profiler){
			static::$query = array(
				'sql' => \Security::htmlentities($sql),
				'time' => static::$profiler->getMicroTime(),
				'stacktrace' => $stacktrace,
				'dbname' => $dbname,
			);
			return true;
		}
	}

}
?>
