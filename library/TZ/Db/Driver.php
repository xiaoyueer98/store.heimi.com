<?php
/**
 * 数据库驱动接口类文件
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-10-20
 */
interface TZ_Db_Driver
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
	public function connect($host, $port, $username, $password, $default, $charset);

	/**
	 * 关闭数据库连接
	 *
	 * @param	string	$connection
	 * @return	int
	 */
	public function close($connection);

	/**
	 * 执行SQL
	 *
	 * @param	resource	$connection
	 * @return	mixed
	 */
	public function query($sql, $connection);

	/**
	 * 返回所有结果集
	 *
	 * @param	string	$object
	 * @return	mixed	
	 */
	public function fetchAll($object = false);

	/**
	 * 从结果集中获取一行
	 *
	 * @param	int		$column
	 * @param	bool	$object
	 * @return	array
	 */
	public function fetchRow($column = null, $object = false);
	
	/**
	 * 返回上一次INSERT操作插入的id
	 *
	 * @return	int
	 */
	public function insertId();
	
	/**
	 * 返回上一次操作影响记录行数
	 *
	 * @return	int
	 */
	public function affectedRows();
	
	/**
	 * 返回查询结果集行总数，仅对SELECT语句有效
	 *
	 * @return	int
	 */
	public function rowCount();
}
