<?php

/**
 * bootstrap file
 * 
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-10-20
 */
class Bootstrap extends Yaf_Bootstrap_Abstract {

    /**
     * data
     */
    private $_config = null;

    /**
     * config init
     */
    public function _initConfig() {
        $this->_config = Yaf_Application::app()->getConfig();
        Yaf_Registry::set('config', $this->_config);
    }

    /**
     * loader config
     */
    public function _initLoader() {
        $loader = new TZ_Loader;
        Yaf_Registry::set('loader', $loader);
    }

    /**
     * plug config
     */
    public function _initPlugin(Yaf_Dispatcher $dispatcher) {
        $routerPlugin = new RouterPlugin();
        $dispatcher->registerPlugin($routerPlugin);
    }

    /**
     * view config 
     */
    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        defined('STATIC_SERVER') or define('STATIC_SERVER', $this->_config->static->server);
        defined('STATIC_VERSION') or define('STATIC_VERSION', md5(date('Ymd')));
        $dispatcher->disableView();
    }
    
    /**
     * db config
     */
    public function _initDb() {
        //user_db
        $userDb = $this->_config->database->user;
        $userMaster = $userDb->master->toArray();
        $userSlave = !empty($userDb->slave) ? $userDb->slave->toArray() : null;
        $userDb = new TZ_Db($userMaster, $userSlave, $userDb->driver);
        Yaf_Registry::set('user_db', $userDb);

       
        
        //mall_db
        $mallDb = $this->_config->database->mall;
        $mallMaster = $mallDb->master->toArray();
        $mallSlave = !empty($mallDb->slave) ? $mallDb->slave->toArray() : null;
        $mallDb = new TZ_Db($mallMaster, $mallSlave, $mallDb->driver);
        Yaf_Registry::set('mall_db', $mallDb);
    }

}

/**
 * RouterPlugin.php
 */
class RouterPlugin extends Yaf_Plugin_Abstract {

    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        
    }

    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        
    }

    
    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        $view = new TZ_View();
        $view->setCacheEnable(true);
        $view->setScriptPath(APP_PATH . '/application/modules/' . $request->getModuleName() . '/views');
        Yaf_Dispatcher::getInstance()->setView($view);
    }

    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
		//将下划线请求的控制器名称重写为大小写
		$controller = $request->getControllerName();
		if (false !== ($pos = strpos($controller, '_')))
			$request->setControllerName(substr($controller, 0, $pos).substr($controller, ($pos + 1)));

		//在模块名称合法的情况下，对方法名称做处理
		$moduleName = $request->getModuleName();
		if (in_array($moduleName, Yaf_Application::app()->getModules())) {

			//记录将访问的接口名称
			Yaf_Registry::set('REQUEST_API_NAME', strtolower($moduleName.'/'.$controller));

			//action中存在".",不存在则默认返回Json数据并且指向index动作
			$action = $request->getActionName();
			if (false !== strpos($action, '.')) {

				//source && format
				$param = explode('.', $action); 
				if ((count($param) < 2) || empty($param[1]))
					die('request error.');

				//记录需要格式化的类型
				Yaf_Registry::set('REQUEST_FORMAT_TYPE', $param[1]);

				switch ($param[1]) {

				case 'json':
					header("Content-type:application/json;charset=utf-8");
					$request->setActionName('index');
					break;

				case 'html':
					header("Content-type:text/html;charset=utf-8");
					$request->setActionName($param[0]);
					break;

				case 'zip':
					header('Content-Type:application/zip;charset=utf-8');
					$request->setActionName('index');
					break;

				}   

				$source = $param[0];
			} else {
				//header("Content-type:application/json;charset=utf-8");          //默认返回json数据
				//$request->setActionName($action);                               //默认全部指向index方法
				//Yaf_Registry::set('REQUEST_FORMAT_TYPE', $param[1]);
				$source = $action;
			}

			self::_analySource($source);
		}
    }
    
    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        
    }

    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        
    }
    /**
     * 定义os,app_name,app_version
     * 
     * @param string $source
     * @return void
     */
    static private function _analySource($source)
    {
        $osName     = 'unknow';
        $appName    = $source;
        $appVersion = 'unknow';
        if (preg_match_all('#^([ai])([\w]+)([\d]{6})$#', $source, $matches))
        {
            $osName     = self::$_os[$matches[1][0]];
            $appName    = $matches[2][0];
            $appVersion = $matches[3][0];
        }
        else
        {
            if (isset($_SERVER['HTTP_USER_AGENT']))
            {

                $sUserAgent = $_SERVER['HTTP_USER_AGENT'];
                preg_match('#android|ios|ubuntu|windows#i', $sUserAgent, $matches);
                //               $osName     = strtolower($matches[0]);
                if (isset($matches[0]) && is_array($matches[0]))
                {
                    $osName = strtolower($matches[0]);
                }
            }
        }
        Yaf_Registry::set('REQUEST_API_VERSION', '3');
        Yaf_Registry::set('REQUEST_OS_NAME', $osName);
        Yaf_Registry::set('REQUEST_APP_NAME', $appName);
        Yaf_Registry::set('REQUEST_APP_VERSION', $appVersion);
    }

    /**
     * @var array
     */
    static private $_os = array(
        'a' => 'android',
        'i' => 'ios'
    );

}




/**
 * browser debug
 * 
 * @param mixed $params
 * @return void
 */
function d($params) {
    echo '<pre>';
    var_dump($params);
    echo '</pre>';
}
