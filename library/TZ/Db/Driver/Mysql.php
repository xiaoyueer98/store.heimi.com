<?php
/**
 * mysql驱动文件
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-10-20
 */
class TZ_Db_Driver_Mysql implements TZ_Db_Driver
{
	/**
	 * 连接数据库
	 *
	 * @param	string	$host
	 * @param	string	$port
	 * @param	string	$username
	 * @param	string	$password
	 * @param	string	$default
	 * @param	string 	$charset
	 * @return	resource
	 */
	public function connect($host, $port, $username, $password, $default, $charset)
	{
		$connection = mysql_connect("{$host}:{$port}", $username, $password);	
		if (!$connection || !is_resource($connection)) {
			return false;
		}

		if (!empty($default)) {
			mysql_select_db($default, $connection);	
		}

		if (!empty($charset)) {
			$sql = "SET NAMES {$charset}";
			mysql_query($sql, $connection);	
		}

		return $connection;
	}

	/**
	 * 关闭数据库连接
	 *
	 * @param	string	$connection
	 * @return	int
	 */
	public function close($connection)
	{
		return @mysql_close($connection);	
	}

	/**
	 * 执行SQL
	 *
	 * @param	string		$sql
	 * @param	resource	$connection
	 * @return	mixed
	 */
	public function query($sql, $connection) 
	{
		$this->_connection = $connection;
		return $this->_result = mysql_query($sql, $connection);			
	}

	/**
	 * 获取所有结果集数据
	 *
	 * @param	string	$object
	 * @return	mixed	
	 */
	public function fetchAll($object = false)
	{
		if (!is_resource($this->_result)) {
			return false;	
		}	

		$data = array();
		while (1) {

			$row = $this->_fetch($object);
			if (!$row) {
				break;
			}
			$data[] = $row;
		}

		return $data;
	}

	/**
	 * 从结果集中获取一行数据
	 *
	 * @param	int		$index
	 * @param	bool	$object
	 * @return	array
	 */
	public function fetchRow($column = null, $object = false)
	{
		if (!is_resource($this->_result)) {
			return false;	
		}	

		$row = $this->_fetch($object);
		if (empty($row))
			return array();

		if (null !== $column) {
			return isset($row[$column]) ? $row[$column] : null;
		} else {
			return $row;
		}
	}

	/**
	 * 返回上一次INSERT操作插入的id
	 *
	 * @return	int
	 */
	public function insertId()
	{
		return mysql_insert_id($this->_connection);	
	}

	/**
	 * 返回上一次操作影响记录行数
	 *
	 * @return	int
	 */
	public function affectedRows()
	{
		return mysql_affected_rows($this->_connection);	
	}

	/**
	 * 返回查询结果集行总数，仅对SELECT语句有效
	 *
	 * @return	int
	 */
	public function rowCount()
	{
		return mysql_num_rows($this->_result);	
	}

	/**
	 * 本次query的连接实例
	 *
	 * @var	resource
	 */
	private $_connection;
	/**
	 * 本次查询结果集
	 *
	 * @var	resource
	 */
	private $_result;

	/**
	 * 根据类型从结果集中取出一行
	 *
	 * @param	bool	$object
	 * @return	mixed
	 */
	private function _fetch($object = false)
	{
		if ($object) {
			return mysql_fetch_object($this->_result);	
		} else {
			return mysql_fetch_assoc($this->_result);	
		}
	}
}
