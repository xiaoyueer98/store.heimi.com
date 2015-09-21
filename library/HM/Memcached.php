<?php
/**
 * @name Memcached.class.php
 * @desc memCache统一操作类
 */

class HM_Memcached
{
    
    private $errorDesc = "";
    /**
	 * @name __construct
	 * @desc 构造函数
	 * @param void
	 * @return object instance of ClubMemCached
	 * @access public
	 *
	 */
    private $host = "";
    private $port = 11211;
    public function __construct($host,$port)
    {
         $this->host = $host;
         $this->port = $port;
         $this->memcached = new Memcached;
         $this->memcached->addServer($host,$port);
    }

    /**
	 * @name add
	 * @desc Add an item to the server
	 * @param string $key
	 * @param mixed $key
	 * @param int $flag default by 0
	 * @param int $expire default by 0
	 * @return boolean
	 * @access public
	 *
	 */
    public function add($key, $val, $flag = 0, $expire = 0)
    {
         if($this->memcached->add($key, $val, $flag, $expire))
         {
              return true;
         }
         return false;
    }
    
    /**
	 * @name set
	 * @desc Store data at the server
	 * @param string $key
	 * @param mixed $key
	 * @param int $flag default by 0
	 * @param int $expire default by 0
	 * @return boolean
	 * @access public
	 *
	 */
    public function set($key, $val, $expire = 0,$flag = 1)
    {
    	if (!is_string($key))
    	{
    		return false;
    	}
        if($this->memcached->set($key, $val,  $expire)){
            return true;
        }
        return false;
    }
    
    /**
	 * @name get
	 * @desc Retrieve item from the server
	 * @param string $key / array $keys
	 * @return Returns the string associated with the key or FALSE on failure or if such key was not found
	 * @access public
	 *
	 */
    public function get($key)
    {
        $result = $this->memcached->get($key);
        return $result;
    }
    
    /**
	 * @name close
	 * @desc Close memcached server connection
	 * @param void
	 * @return boolean
	 * @access public
	 *
	 */
    public function close()
    {
        if($this->memcached->close())
        {
            return true;
        }
        return false;
    }

    /**
	 * @name delete
	 * @desc Delete item from the server
	 * @param string $key
	 * @return public
	 * @access public
	 *
	 */
    public function delete($key, $timeout = 0)
    {
        if($this->memcached->delete($key, $timeout))
        {
             return true;   
        }
        return false;
    }
    /**
     * 
     *  清洗（删除）已经存储的所有的元素
     */
    public function flush()
    {
         if($this->memcached->flush())
         {
              return true;   
         }
         return false;
    }
    /**
	 * @name setError
	 * @desc 设置错误信息
	 * @param string $errorDesc
	 * @return void
	 * @access public
	 *
	 */
    public function setError($errorDesc)
    {
        $this->errorDesc = $errorDesc;
    }
    
    /**
	 * @name getError
	 * @desc 取得错误信息
	 * @param void
	 * @return string $errorDesc
	 * @access public
	 *
	 */
    public function getError()
    {
        return $this->errorDesc;
    }

    public function getAllStats()
    {
    	return $this->memcached->getExtendedStats();
    }
    
    /**
	 * @name __destruct
	 * @desc 析构函数
	 * @param void
	 * @return void
	 * @access public
	 *
	 */
    public function __destruct()
    {
         //  $this->close();
    }
}


