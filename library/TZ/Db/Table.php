<?php
/**
 * TZ_Db_Table class file
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-10-20
 * @version 1.0
 */
class TZ_Db_Table
{
	/**
	 * 数据库对象
	 * 
	 * @var object
	 */
	protected $_db;
	
	/**
	 * 数据表名称
	 * 
	 * @var
	 */
	protected $_tableName;
	
	/**
	 * 表主键
	 */
	protected $_primaryKey;
	
	/**
	 * object init
	 * 
	 * @param unknown_type $tableName
	 * @param unknown_type $priKey
	 * @return void
	 */
	public function __construct($db, $tableName, $primaryKey = null)
	{
		$this->_db = $db;
		$this->_tableName = $tableName;
		$this->_primaryKey = (null === $primaryKey) ? 'id' : $primaryKey;
	}
	
	/**
	 * 根据SQL语句查询
	 * 
	 * @param string $sql
	 * @return driver object
	 */
	public function query($sql)
	{
		return $this->_db->query($sql);
	}
	
	/**
	 * 查询数据
	 * 
	 * @param int|string|array	$conditions		查询条件
	 * @param int|string|array	$fields			查询字段
	 * @param string				$fetchMode			获取模式
	 * return array
	 *
	 */
	public function select($conditions, $fields = '*', $fetchMode = 'ALL') 
	{	
		$fieldSql = $this->_getFieldSql($fields);
		$conditionSql = $this->_getCondition($conditions);
		
		$sql = "SELECT {$fieldSql} FROM {$this->_tableName} {$conditionSql}";
                //echo $sql;
		$query = $this->_db->query($sql);
		switch ($fetchMode) {
			case 'ALL':
				$result = $query->fetchAll();
				break;
				
			case 'ROW':
				$result = $query->fetchRow();
				break;

			default:
				throw new Exception('Fetch type undefined.');
		}
		return $result;
	}
	
	/**
	 * 插入数据
	 * 
	 * @param array $rowData		要插入的数据
	 * 	eg:
	 * 	1.一维数组表示插入一条数据。
	 * 		$data = array(
	 * 			'column_1'	=>	'value_1',
	 * 			'column_2'	=>	'value_2',
	 * 			...
	 * 		);
	 * 
	 * 	2.二维数组表示批量插入，需要保证每条数据的字段一致。
	 * 		$data = array(
	 * 			0	=>	array(
	 * 				'column_1'	=>	'value_0_0',
	 * 				'column_2'	=>	'value_0_1',
	 * 				...
	 * 			),
	 * 			1	=>	array(
	 * 				'column_1'	=>	'value_1_0',
	 * 				'column_2'	=>	'value_1_1',
	 * 				...
	 * 			),
	 * 			...
	 * 		);
	 * @return int
	 */
	public function insert($data)
	{
		if (empty($data) || !is_array($data))
			throw new Exception('param error.');
		
		if (isset($data[0])) {
			$columns = join('`,`', array_keys($data[0]));
			$values = '';
			foreach ($data as $rowData) {
				$values .= "('".join("','", array_values($rowData))."'),";
			}
			$values = rtrim($values, ',');
		} else {
			$columns = join('`,`', array_keys($data));
			$values = "('".join("','", array_values($data))."')";
		}
		$sql = "INSERT INTO {$this->_tableName}(`{$columns}`) VALUES{$values}";
        
		$query = $this->_db->query($sql);
		return (false !== $query) ? $query->insertId() : false;
	}
	
	/**
	 * 更新数据
	 * 
	 * @param array $set
	 * @param array $condition
	 * @return int
	 */
	public function update($set, $condition)
	{
		if (empty($set) || !is_array($set)) 
			throw new Exception('param error.');
		$setSql = '';
		foreach ($set as $key => $value) {
			$setSql .= "`{$key}`='{$value}',";
		}
		$setSql = rtrim($setSql, ',');
		$condition = $this->_getCondition($condition);
		
		$sql = "UPDATE {$this->_tableName} SET {$setSql} {$condition}";	

		$query = $this->_db->query($sql);
		return (false !== $query) ? $query->affectedRows() : false;

	}
	
	/**
	 * 删除数据
	 * 去除delete操作 刑天 2014-12-18
	 * @param array $condition
	 * @return int
	 */
	public function delete($condition)
	{
        throw new Exception('Access denied');
		/*
        $condition = $this->_getCondition($condition);
		$sql = "DELETE FROM {$this->_tableName} {$condition}";
		return $this->_db->query($sql)->affectedRows();
        */
	}
	
	/**
	 * 生成字段查询SQL
	 * 
	 * @param	string|array	$fields
	 * @return string	
	 */
	private function _getFieldSql($fields)
	{
		if (empty($fields))
			return '*';
		
		$fieldsSql = '';
		if (is_string($fields)) {
			return $fields;
		} else if (is_array($fields)) {
			$fieldsSql = '`'.join('`,`', $fields).'`';
		} else {
			$fieldsSql = '*';
		}
		return $fieldsSql;
	}
	
	/**
	 * get sql condition
	 * 
	 * @param int|string|array $condition
	 * @return string
	 */
	private function _getCondition($condition)
	{
		$conditionStr = "WHERE 1 = 1";
		if (is_string($condition) || is_numeric($condition)) {
			$conditionStr .= " AND `{$this->_primaryKey}` = '{$condition}'";
		} else if (is_array($condition)) {
			$groupStr = '';
			$orderStr = '';
			$limitStr = '';
			foreach ($condition as $key => $value) {
				switch ($key) {
					case 'group':
						$groupStr = $this->_getGroupBy($value);
						break;
					case 'order':
						$orderStr = $this->_getOrderBy($value);
 						break;
					case 'limit':
						$limitStr = $this->_getLimit($value);
						break;
				}

				$key = explode(':', $key);
				if (count($key) != 2) {
					continue;
				}
				$field = $key[0];
				$compare = strtolower($key[1]);
				$op = self::$_op;
				if (isset($op[$compare])) {
					$conditionStr .= " AND `{$field}` {$op[$compare]} '{$value}'";
				} else {
					switch ($compare) {
						case 'like':
							$conditionStr .= " AND `{$field}` LIKE '%{$value}%'";
							break;	
						case 'in':
							$conditionStr .= " AND `{$field}` IN ('".join("','", $value)."')";
							break;							
						case 'notin':
							$conditionStr .= " AND `{$field}` NOT IN ('".join("','", $value)."')";
							break;			
						case 'between':
							$conditionStr .= " AND `{$field}` BETWEEN '$value[0]' AND '{$value[1]}'";
							break;
						case 'llike':
							$conditionStr .= " AND `{$field}` LIKE '{$value}%'";
							break;
						case 'rlike':
							$conditionStr .= " AND `{$field}` LIKE '%{$value}'";
							break;
						default:
							throw new Exception('op type undefined.');
					}
				}
			}
		}
		return $conditionStr.$groupStr.$orderStr.$limitStr;
	}
	
	/**
	 * group by
	 * 
	 * @param string|array $groupBy
	 * @return string
	 */
	private function _getGroupBy($groupBy)
	{
		if (empty($groupBy))
			return '';
		
		if (is_string($groupBy)) {
			return " GROUP BY {$groupBy}";
		} else if (is_array($groupBy)) {
			return ' GROUP BY `'.join('`,`', $groupBy).'`';
		} else {
			return '';
		}
	}
	
	/**
	 * order by
	 * 
	 * @param string|array $orderBy
	 * @return string
	 */
	private function _getOrderBy($orderBy)
	{
		if (empty($orderBy))
			return '';
		
		if (is_string($orderBy)) {
			return " ORDER BY {$orderBy}";
		} else if (is_array($orderBy)) {
			return ' ORDER BY `'.join('`,`', $orderBy).'`';
		} else {
			return '';
		}
	}
	
	/**
	 * limit
	 * 
	 * @param int|string|array limit条件
	 * eg:
	 * $limit = 1 同  LIMI 1
	 * $limit = array(1, 2) 同 LIMIT 1,2
	 * @return string
	 */
	private function _getLimit($limit)
	{
		if (empty($limit))
			return '';
		
		if (is_array($limit) && (count($limit) == 2)) {
			return " LIMIT {$limit[0]}, {$limit[1]}";
		} else {
			return " LIMIT {$limit}";
		}
	}
	
	/**
	 * sql where 条件
	 *
	 * @var array
	 */
	static private $_op = array(
			'eq'	=>	'=',
			'neq'	=>	'!=',
			'gt'	=>	'>',
			'egt'	=>	'>=',
			'lt'	=>	'<',
			'elt'	=>	'<='
	);
}	
