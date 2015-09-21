<?php
/**
 * TZ test class file
 * 
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-10-20
 */
/**
 * @var string
 */
defined('APP_PATH') 
	|| define('APP_PATH', dirname(dirname(dirname(__FILE__))));
	
/***
 * yaf config
*/
ini_set('yaf.use_spl_autoload', 1);
	
/**
 * include phpunit libraries
*/
require_once 'PHPUnit/Autoload.php';

class TZ_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var object
	 */
	static private $_app = null;
	
	/**
	 * init
	 * 
	 * @return void
	 */
	public function __construct()
	{
		if (null === self::$_app) {
			$app = new Yaf_Application(APP_PATH.'/config/application.ini');
			self::$_app = $app->bootstrap();
		}
		parent::__construct();
	}
}