<?php
/**
 * redis存储
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-10-20
 */
class TZ_Redis
{
	/**
     * redis object
     *
     * @var object
     */
	static private $_redis = null;
	
	/**
	 * current db index
	 * 
	 * @var int
	 */
	static private $_dbIndex = array(
		'user_db'    => 0,
		'wifi_db'    => 1,
		'white_list' => 2
	);

	/**
     * connect to redis server 
     *
     * @param string $host
     * @param int $port
     * @return object
     */
	static public function connect($index = 'user', $host = null, $port = null)
	{

		//----------------------------octopus update 2014-01-03----------------------------------
		//根据业务，把redis拆分成两部分，user 和 card
	//		if (!isset(self::$_dbIndex[$index])) {
	//			throw new Exception('DB index not found.');
	//		}
		//$db = self::$_dbIndex[$index];
		if (!extension_loaded('redis'))
			throw new Exception('Redis extension not found.');
		$redisConfig = TZ_Loader::config('redis');
		if (empty($redisConfig))	
			throw new Exception('Reids Configuration error.');
		$redis = new Redis();
		$host = (null === $host) ? $redisConfig[$index]['host'] : $host;
		$port = (null === $port) ? $redisConfig[$index]['port'] : $port;
		$connection = $redis->connect($host, $port);
		if (!$connection)
                {
			throw new Exception('Can\'t connect to Redis server.');
                }
		//$redis->select($db);
		//-----------------------------------end ---------------------------------------------------	
		return $redis;
	}			

	/**
     * close connection
     *
     * @return void
     */
	static public function close()
	{
		self::$_redis->close();
		self::$_redis = null;
	}
		
}
