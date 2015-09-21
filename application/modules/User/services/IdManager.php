<?php
/**
 * ID manager
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2013-3-25
 */
class IdManagerService
{
	/**
     * 获取UID
     * 
     * @return string
     */
	public function createUid()
	{
//		$uid = TZ_Generator_Manager::create();
//		return !$uid ? $this->_getUid() : $uid;
	return $this->_getUid();
	}

	/**
     * 创建SESSION ID
     *
     * @param string $userId
     * @return string
     */ 
	public function createSessionId($uid)
	{
		return md5($uid.time()); 		
	}
        
        //local
	private function _getUid()
	{
		//2013-01-01 00:00:00 (timestamp-microtime)
		$startTime= 1356969600000;
		$preBit = '0'.decbin(intval(microtime(1) * 1000) - $startTime);	
		$partitionNumber = '0000000000';
		$counter = decbin(rand(0, 4096));
		return bindec($preBit.$partitionNumber.$counter);
	}
        
        
        /**
         * 
         * @return type
         */
        public function getWUid()
        {
            return $this->_getUid();
        }
	
}
