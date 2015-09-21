<?php
/**
 * UserModel class file
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2013-3-25
 */
class UsersModel extends TZ_Db_Table
{
	/**
	 * cache status
	 *
	 * @var int
	 */
	const USER_INFO_CACHE = 1;

	/**
	 * redis prekey
	 *
	 * @var string
	 */
	private $_infoPreKey = 'user:info:';

	/**
	 * redis prekey
	 *
	 * @var string
	 */
	private $_telephonePreKey = 'user:uid:';

	/**
	 * construct table
	 */
	public function __construct()
	{
		parent::__construct(Yaf_Registry::get('user_db'), 'user_db.users');
	}

	/**
	 * get user info
	 *
	 * @param string $uid
	 * @param string | array $fields
	 * @return array
	 */
	public function getInfoByUid($uid, $fields = array())
	{
		$userInfoCache = $this->_getCacheByUid($uid, $fields);
		if (!empty($userInfoCache) && (self::USER_INFO_CACHE === 1))
		{
			return $userInfoCache;
		}
		$userInfo = $this->_getInfoFromDbByUid($uid);
		if (!empty($userInfo)) {
			$this->_setCache($uid, $userInfo);
			foreach ($userInfo as $field => $value) {
				if (!empty($fields) && !in_array($field, $fields))
				unset($userInfo[$field]);
			}
		}
		return $userInfo;
	}

	/**
	 * get user info
	 *
	 * @param string $telephone
	 * @param string | array $fields
	 * @return array
	 */
	public function getInfoByTelephone($telephone, $fields = array())
	{
		$userInfoCache = $this->_getCacheByTelephone($telephone, $fields);
		if (!empty($userInfoCache) && (self::USER_INFO_CACHE === 1))
		{
			return $userInfoCache;
		}
		$userInfo = $this->_getInfoFromDbByTelephone($telephone);
		if (!empty($userInfo)) {
			$this->_setCache($userInfo['uid'], $userInfo);
			foreach ($userInfo as $field => $value) {
				if (!empty($fields) && !in_array($field, $fields))
				unset($userInfo[$field]);
			}
		}
		return $userInfo;
	}

	/**
	 * update user info
	 *
	 * @param string $uid
	 * @param array $set
	 * @return boolean
	 */
	public function updateInfoByUid($uid, $set)
	{
		$set['updated_at'] = date('Y-m-d H:i:s');
		$updateStatus = $this->_updateInfoFromDbByUid($uid, $set);
		if (false === $updateStatus)
		return false;
		return $this->_resetCache($uid, $set);
	}

	/**
	 * delete user info
	 *
	 * @param string $telephone
	 * @return int
	 */
	public function deleteInfoByTelephone($telephone)
	{
		$this->_cleanCache($telephone);
		$conditions['telephone:eq'] = $telephone;
		return $this->delete($conditions);
	}

	/**
	 * get data from db
	 *
	 * @param string $uid
	 * @return array
	 */
	private function _getInfoFromDbByUid($uid)
	{
		$conditions['id:eq'] = $uid;
		$uniqueInfo = TZ_Loader::model('UniqueUsers', 'User')->select($conditions, 'type', 'ROW');

		if (empty($uniqueInfo))
		return array();

		$infoTable = ($uniqueInfo['type'] == '1') ? '`user_db`.`users`' : '`user_db`.`companies`';
		$sql = "SELECT u.*, uu.id uid, uu.type type ";
		$sql .= "FROM {$infoTable} u,`user_db`.`unique_users` uu";
		$sql .= " WHERE u.`id`=uu.`mapper_id` AND uu.`id`='{$uid}' LIMIT 1";
		return $this->_db->query($sql)->fetchRow();
	}


	/**
	 * get data from db
	 *
	 * @param string $telephone
	 * @return array
	 */
	private function _getInfoFromDbByTelephone($telephone)
	{
		$sql = "SELECT u.*, uu.id uid, uu.type type ";
		$sql .= "FROM `user_db`.`users` u,`user_db`.`unique_users` uu";
		$sql .= " WHERE u.`id`=uu.`mapper_id` AND uu.`type`=1";
		$sql .= " AND u.`telephone`='{$telephone}' LIMIT 1";
		return $this->_db->query($sql)->fetchRow();
	}

	private function _delInfoFromDbByTelephone($telephone)
	{
		$sql = "DELETE u.*, uu.*";
		$sql .= "FROM `user_db`.`users` u,`user_db`.`unique_users` uu";
		$sql .= " WHERE u.`id`=uu.`mapper_id`";
		$sql .= " AND u.`telephone`='{$telephone}'";
		return $this->_db->query($sql)->affectedRows();
	}

	public function delWInfoFromDbByTelephone($telephone)
	{
		return $this->_delInfoFromDbByTelephone($telephone);
	}

	/**
	 * change password from db
	 *
	 * @param string $uid
	 * @param string $set
	 * @return boolean
	 */
	private function _updateInfoFromDbByUid($uid, $set)
	{
		$conditions['id:eq'] = $uid;
		$uniqueModel = TZ_Loader::model('UniqueUsers', 'User');
		$uniqueInfo = $uniqueModel->select($conditions, array('mapper_id', 'type'), 'ROW');
		if (empty($uniqueInfo))
		return false;

		$model = ($uniqueInfo['type'] == '1') ? $this : TZ_Loader::model('Companies');
		$upConditions['id:eq'] = $uniqueInfo['mapper_id'];
		return $model->update($set, $upConditions);
	}


	/**
	 * set to redis
	 *
	 * @param string $uid	unique_id
	 * @param array $userInfo
	 * @return boolean
	 */
	private function _setCache($uid, $userInfo)
	{

		$memcacheConfig = TZ_Loader::config('memcache');
		$host = $memcacheConfig['user']['host'];
		$port = $memcacheConfig['user']['port'];
		$cache = new HM_Memcached($host,$port);
		 
		$json = json_encode($userInfo);
		$cache->set($this->_telephonePreKey.$userInfo['telephone'], $uid);
		$cache->set($this->_infoPreKey.$uid, $json);

	}

	/**
	 * reset to redis
	 *
	 * @param string $uid
	 * @param array $set
	 * @return boolean
	 */
	private function _resetCache($uid, $set)
	{
		$infoKey = $this->_infoPreKey.$uid;
		$memcacheConfig = TZ_Loader::config('memcache');
		$host = $memcacheConfig['user']['host'];
		$port = $memcacheConfig['user']['port'];
		$cache = new HM_Memcached($host,$port);
		$old = $this->_getCacheByUid($uid);
		foreach ($set as $key => $value) {
			$old[$key] = $value;
		}
		$json = json_encode($old);
		return $cache->set($this->_infoPreKey.$uid, $json);
	}

	/**
	 * clean cache
	 *
	 * @param string $telephone
	 * @return boolean
	 */
	private function _cleanCache($telephone)
	{
		/*		$redis = TZ_Redis::connect('user');
		 $telephoneKey = $this->_telephonePreKey.$telephone;
		 $uid = $redis->get($telephoneKey);
		 if ($uid) {
			$infoKey = $this->_infoPreKey.$uid;
			$keys = $redis->hKeys($infoKey);
			foreach ($keys as $key) {
			$redis->hDel($this->_infoPreKey.$uid, $key);
			}
			}
			return $redis->delete($telephoneKey);*/
		$memcacheConfig = TZ_Loader::config('memcache');
		$host = $memcacheConfig['user']['host'];
		$port = $memcacheConfig['user']['port'];
		$cache = new HM_Memcached($host,$port);
		$uid =  $cache->get($this->_telephonePreKey.$telephone);
		if($uid)
		{
			$infoKey = $this->_infoPreKey.$uid;
			$cache->delete($infoKey);
		}

		return $cache->delete($this->_telephonePreKey.$telephone);
	}

	/**
	 * get info cache
	 *
	 * @param string $uid
	 * @param array $fields
	 * @return array
	 */
	private function _getCacheByUid($uid, $fields = array())
	{
		/*
		 $redis = TZ_Redis::connect('user');
		 $infoKey = $this->_infoPreKey.$uid;
		 if (empty($fields)) {
			return $redis->hGetAll($infoKey);
			}
			$userInfo = $redis->hmGet($infoKey, $fields);
			$key = array_shift($fields);

			return (false === $userInfo[$key]) ? false : $userInfo;
			*/
		$memcacheConfig = TZ_Loader::config('memcache');
		$host = $memcacheConfig['user']['host'];
		$port = $memcacheConfig['user']['port'];
		$cache = new HM_Memcached($host,$port);
		$infoKey = $this->_infoPreKey.$uid;
		$json = $cache->get($infoKey);
		$userInfo = (array)json_decode($json);
		if (empty($fields)) {
			return $userInfo;
		}
		$key = array_shift($fields);
		return (!isset($userInfo[$key])) ? false : $userInfo;

	}

	/**
	 * get relation cache
	 *
	 * @param string $telephone
	 * @param array $fields
	 * @return string
	 */
	private function _getCacheByTelephone($telephone, $fields = array())
	{
		//$redis = TZ_Redis::connect('user');
		//$uid = $redis->get($this->_telephonePreKey.$telephone);
		$memcacheConfig = TZ_Loader::config('memcache');
		$host = $memcacheConfig['user']['host'];
		$port = $memcacheConfig['user']['port'];
		$cache = new HM_Memcached($host,$port);
		$uid =  $cache->get($this->_telephonePreKey.$telephone);
		return $uid ? $this->_getCacheByUid($uid, $fields) : false;
	}

	/**
	 * 查询推荐人的积分
	 *
	 * @param string $uid
	 * @return array
	 */
	public function getReferScore($telephone)
	{
		$sql = "SELECT uu.id,if(isnull(a.score),0,a.score) as score,if(isnull(a.status),2,a.status) as status from user_db.users u  LEFT JOIN  user_db.unique_users  uu on  uu.mapper_id=u.id LEFT JOIN  user_db.account a on  uu.id=a.unique_user_id  where u.telephone= $telephone and u.status=1 ;";
		return $this->_db->query($sql)->fetchRow();
	}
	/**
	 * delete user info
	 *
	 * @param string $telephone
	 * @return int
	 */
	public function delCache($telephone)
	{
		return $this->_cleanCache($telephone);
	}

	/**
	 * delete base_user info
	 *
	 * @param string $telephone
	 * @return int
	 */
	public function deleteBaseUser($telephone)
	{
		$sql = "delete from user_db.base_users where telephone=$telephone";
		return $this->_db->query($sql)->affectedRows();
	}

	/**
	 * delete user_card info
	 *
	 * @param string $telephone
	 * @return int
	 */
	public function deleteUserCard($telephone)
	{
		$sql = "delete from user_db.user_card where telephone=$telephone";
		return $this->_db->query($sql)->affectedRows();
	}
	/**
	 * delete user_card info
	 *
	 * @param string $telephone
	 * @return int
	 */
	public function deleteAccount($id)
	{
		$sql = "delete from user_db.account where unique_user_id=$id";
		return $this->_db->query($sql)->affectedRows();
	}
	/**
	 * delete user_card info
	 *
	 * @param string $telephone
	 * @return int
	 */
	public function deleteAccountBalance($id)
	{
		$sql = "delete from user_db.account_balance where id=$id";
		return $this->_db->query($sql)->affectedRows();
	}
	public function addLottery($you,$her,$pwd)
	{
		$sql = "insert into user_db.matches values(null,'{$her}','{$you}','{$pwd}') ;";
		return $this->_db->query($sql)->affectedRows();
	}


	/**
	 *
	 * @param type $uid
	 * @return type
	 */
	public function getWInfoFromDbByUid($uid)
	{
		return $this->_getInfoFromDbByUid($uid);
	}

	/**
	 *
	 * @param type $telephone
	 * @return type
	 */
	public function getWInfoFromDbByTelephone($telephone)
	{
		return $this->_getInfoFromDbByTelephone($telephone);
	}

	/**
	 *
	 * @param type $uid
	 * @param type $set
	 * @return type
	 */
	public function updateWInfoFromDbByUid($uid,$set)
	{

		$set['updated_at'] = date('Y-m-d H:i:s');
		$updateStatus = $this->_updateWInfoFromDbByUid($uid, $set);
		if (false === $updateStatus)
		return false;
		return $this->_resetCache($uid, $set);
		 
	}

	private function _updateWInfoFromDbByUid($uid, $set)
	{
		$conditions['id:eq'] = $uid;
		$uniqueModel = TZ_Loader::model('UniqueUsers', 'User');
		$uniqueInfo = $uniqueModel->select($conditions, array('mapper_id', 'type'), 'ROW');
		if (empty($uniqueInfo))
		return false;
		$model = ($uniqueInfo['type'] == '1') ? $this : TZ_Loader::model('Companies');
		$upConditions['id:eq'] = $uniqueInfo['mapper_id'];
		return $model->update($set, $upConditions);
        }
}
