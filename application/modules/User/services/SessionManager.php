<?php
/**
 * session manager service
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2013-3-25
 */
class SessionManagerService
{
	/**
     * redis prekey
     *
     * @var string
     */
	static private $_sessionPreKey = 'session:';		

	/**
     * create session info
     *
     * @param string $uid
     * @return string
     */
	public function create($uid) 
	{
		$sessionId = TZ_Loader::service('IdManager')->createSessionId($uid);
		$redis = TZ_Redis::connect('session');	
		//---------------octopus update 2014-01-03---------------------------------
		//设置一下时间戳,传入两个传
		$setStatus = $redis->hmset(self::$_sessionPreKey.$sessionId,array($uid,time()));
		if (!$setStatus)
			throw new Exception('创建用户SESSION失败。');
		//-------------------------------end---------------------------------
		return $sessionId;
	}

	/**
     * get unique id
     *
     * @param string $sessionId
     * @return string 
     */
	public function getUid($sessionId)
	{
		//---------------octopus update 2014-01-03---------------------------------
		//得到用户id后，查看是否超时
		$redis = TZ_Redis::connect('session');	
		$data=$redis->hgetall(self::$_sessionPreKey.$sessionId);
		if(!empty($data)){
			if((time()-$data[1])<36000){
				return $data[0];
			}
		}
		//如果超时，删除当条记录
		$data=$redis->del(self::$_sessionPreKey.$sessionId);
		return false;
		//-------------------------------end---------------------------------
	}

	/**
     * unset session info
     *
     * @param string $sessionId
     * @return void
     */
	public function discard($sessionId)
	{
		$redis = TZ_Redis::connect('session');	
		return $redis->del(self::$_sessionPreKey.$sessionId);
	}
}
