<?php
/**
 * 数据库访问抽象层文件
 * 
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-10-20
 */
class TZ_Db
{
	/**
	 * 驱动对象
	 *
	 * @var object
	 */
	private $_driver;
	
	/**
	 * 写配置
	 *
	 * @var array
	 */
	private $_masterConfig;
	
	/**
	 * 读配置
	 *
	 * @var array
	 */
	private $_slaveConfig;
	
	/**
	 * 写连接
	 *
	 * @var resource
	 */
	private $_writeConnection;
	
	/**
	 * 读连接
	 *
	 * @var resource
	 */
	private $_readConnection;
	
	/**
	 * 单库模式
	 *
	 * @var boolean
	 */
	private $_singleMode = false;
	
	/**
	 * 事物模式 
	 * 
	 * @var boolean
	 */
	private $_transMode = false;
	
	/**
	 * 事物状态
	 */
	private $_transStatus = true;
	
	/**
	 * 初始化确定主从连接配置
	 *
	 * @param array $masterConfig	//获取Master主机的配置信息
	 * @param int $driver			//获取连接的驱动程序
	 * @param array $slaveConfig	//获取Slave主机的配置列表
	 * @return void
	 *
	 * eg:
	 * $masterConfig = array(
	 *     'host'		=>	'localhost',		//Master主机的ip地址
	 *     'port'		=>	'3306',				//监听端口
	 *     'username'	=>	'root',				//数据库用户名
	 *     'password'	=>	'123456',			//数据库密码
	 *     'default'	=>	'route_db',			//默认连接的数据库
	 *     'charset'	=>	'UTF-8'				//连接使用的字符集
	 * );
	 *
	 * $slaveConfig = array(					//Slave配置与Master相同，节点名称自定义
	 *     'node_1'	=>	array(
	 *         'host'		=>	'slave_1',
	 *         'port'		=>	'3306',
	 *         'username'	=>	'root',
	 *         'password'	=>	'123456',
	 *         'default'	=>	'database_1',
	 *         'charset'	=>	'UTF-8'
	 *     ),
	 *     'node_2'	=>	array(
	 *         'host'		=>	'slave_2',
	 *         'port'		=>	'3306',
	 *         'username'	=>	'root',
	 *         'password'	=>	'123456',
	 *         'default'	=>	'database_2',
	 *         'charset'	=>	'UTF-8'
	 *     )
	 * );
	 */
	public function __construct($masterConfig, $slaveConfig = null, $driver = 'MYSQL')
	{
		if (empty($masterConfig) || !is_array($masterConfig)) {
			throw new Exception('数据库主库配置异常');
		}
		$this->_masterConfig = $masterConfig;
	
		if (!empty($slaveConfig) && is_array($slaveConfig)) {
			$this->_slaveConfig = $slaveConfig;
		} else {
			$this->_singleMode = true;
		}
	
		switch ($driver) {
	
			case 'MYSQL':
				$this->_driver = new TZ_Db_Driver_Mysql;
				break;
	
			default:
				throw new Exception('数据库驱动程序不存在');
		}
	
	}
	
	/**
	 * 执行SQL
	 *
	 * @param	string	$sql
	 * @return	object
	 */
	public function query($sql)
	{
		if (empty($sql) || !is_string($sql)) {
			throw new Exception('SQL语句不合法');
		}

		if ($this->_singleMode || $this->_transMode ) {
			$connection = $this->_getWriteConnection();
		} else {
			$action = strtoupper(substr(ltrim($sql), 0, 6));
			if ($action == 'SELECT') {
				$connection = $this->_getReadConnection($this->_slaveConfig);
			} else {
				$connection = $this->_getWriteConnection();
			}
		}
	
		$driver = clone $this->_driver;
		$query = $driver->query($sql, $connection);
		if (false === $query) {
			if ($this->_transMode) {
				$this->_transStatus = false;
			} else {
				throw new Exception("SQL执行失败:{$sql}");
			}
		}
		return $driver;
	}
	
	/**
	 * 关闭数据库连接
	 *
	 * @return void
	 */
	public function close()
	{
		$this->_driver->close($this->_writeConnection);
		$this->_writeConnection = null;
		if (!empty($this->_readConnection)) {
			$this->_driver->close($this->_readConnection);
			$this->_readConnection = null;
		}
	}
	
	/**
	 * 开始事物
	 * 
	 * @return void
	 */
	public function transBegin()
	{
		$this->query('SET AUTOCOMMIT=0');
		$this->query('START TRANSACTION');
		$this->_transMode = true;
	}
	
	/**
	 * 事物提交
	 * 
	 * @return void
	 */
	public function commit()
	{
		$this->query('COMMIT');
		$this->query('SET AUTOCOMMIT=1');
		$this->_transMode = false;
	}
	
	/**
	 * 事物回滚
	 * 
	 * @return void
	 */
	public function rollback()
	{
		$this->query('ROLLBACK');
		$this->query('SET AUTOCOMMIT=1');
		$this->_transMode = false;
	}
	
	/**
	 * 返回事物状态
	 * 
	 * @return boolean
	 */
	public function transStatus()
	{
		return $this->_transMode ? $this->_transStatus : true;
	}
	
	/**
	 * 获取读连接
	 *
	 * @param	array	$slaveConfig
	 * @return  resource
	 */
	private function _getReadConnection($slaveConfig)
	{
		//Slave连接已存在
		if (null !== $this->_readConnection) {
			return $this->_readConnection;
		}
	
		//如果所有slave失效，则使用Master
		if (empty($slaveConfig) || !is_array($slaveConfig)) {
			return $this->_readConnection = $this->_getWriteConnection();
		}
	
		//从Slave集群随机取一个主机进行连接
		$key = array_rand($slaveConfig);
		$connection = $this->_connect($this->_slaveConfig[$key]);
		if (!$connection) {
			unset($slaveConfig[$key]);
			return $this->_readConnection = $this->_getReadConnection($slaveConfig);
		} else {
			return $this->_readConnection = $connection;
		}
	}
	
	/**
	 * 获取写连接
	 *
	 * @return resource
	 */
	private function _getWriteConnection()
	{
		if (null !== $this->_writeConnection) {
			return $this->_writeConnection;
		}
		$connection = $this->_connect($this->_masterConfig);
		if (!$connection) {
			throw new Exception('亲，我鸭梨山大，请稍后再试');
		}
		return $this->_writeConnection = $connection;
	}
	
	/**
	 * 连接数据库
	 *
	 * @param	array	$config
	 * @return	mixed
	 */
	private function _connect($db)
	{	

		try {
			$connection = $this->_driver->connect($db['host'], $db['port'],
					$db['username'], $db['password'], $db['default'], $db['charset']);
		} catch(Exception $e) {
			throw new Exception('亲，我鸭梨山大，请稍后再试');
		}
	
		return $connection;
	}
}
