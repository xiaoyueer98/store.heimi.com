<?php
/**
 * view object
 * 
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-04-09
 */
class TZ_View implements Yaf_View_Interface
{
	/**
	 * 是否开启调试模式
	 * 
	 * @var boolean
	 */
	private $_debug = false;
	
	/**
	 * 是否开启gzip压缩
	 * 
	 * @var boolean
	 */
	private $_gzip = true;
	
	/**
	 * 缓存开启状态
	 * 
	 * @var boolean
	 */
	private $_cacheEnable = false;
	
	/**
	 * 模板所在目录
	 * 
	 * @var string
	 */
	private $_templateDir;
	
	/**
	 * 模板常量
	 * 
	 * @var array
	 */
	static private $_staticData = array();
	
	/**
	 * 模板变量
	 * 
	 * @var array
	 */
	private $_data = array();
	
	/**
	 * 模板文件
	 * 
	 * @var array
	 */
	private $_files = array();
	
	/**
	 * 引入的外部模板
	 * 
	 * @var array
	 */
	private $_includeFiles = array();
	
	/**
	 * 语法替换正则
	 * 
	 * @var string
	 */
	private $_varRegexp = "\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*";
	private $_vtagRegexp = "\<\?=(\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)\?\>";
	private $_constRegexp = "\{([\w]+)\}";
	
	/**
	 * 重写
	 * 
	 * @var array
	 */
	private $_rewriteEnable = false;
	private $_pregSearchs = array();
	private $_pregReplaces = array();
	
	/**
	 * 缓存系统对象
	 * 
	 * @var object
	 */
	static private $_cacheDriver = null;
	
	
	/**
	 * init
	 * 
	 * @return void
	 */
	public function __construct()
	{
		ob_start();
	}
	
	/**
	 * set && get methods
	 */
	public function setDebug($status)
	{
		$this->_debug = $status;	
	}
	public function setGzip($status)
	{
		$this->_gzip = $status;
	}
	public function setScriptPath($templateDir)
	{
		$this->_templateDir = $templateDir;
	}
	public function setCacheEnable($status)
	{
		$this->_cacheEnable = $status;
	}
	public function setRewriteEnable($status)
	{
		$this->_rewriteEnable = $status;
	}
	public function setRewriteRules($searchs, $replaces) {
		$this->_pregSearchs = $searchs;
		$this->_pregReplaces = $replaces;
	}
	public function getScriptPath()
	{
		return $this->_templateDir;
	}
	
	/**
	 * 注册某个变量
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function assign($name, $value = null)
	{
		$this->_data[$name] = $value;
	}
	
	/**
	 * 注册某个静态变量
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function assignStatic($name, $value)
	{
		self::$_staticData[$name] = $value;
	}
	
	/**
	 * 渲染并输出页面内容
	 * 
	 * @param string $template
	 * @param array $pageData
	 * @return void
	 */
	public function display($template, $pageData = null)
	{
		
		$pageContent = $this->render($template, $pageData);

		if ($this->_rewriteEnable)
			$pageContent = self::_rewrite($pageContent);
		
		if ($this->_gzip)
			$pageContent = self::_gzip($pageContent);
		
		TZ_Request::_xhprofDisable();
		echo $pageContent;
	}
	
	/**
	 * 渲染页面
	 * 
	 * @param string $template
	 * @param array $pageData
	 * @return string
	 */
	public function render($template, $pageData = null)
	{
		$pageData = (null === $pageData) ? array_merge($this->_data, self::$_staticData) : $pageData; 
		extract($pageData, EXTR_SKIP);
		
		//预处理
		$templateFile = $this->_templateDir.'/'.$template;
		
		//print_r($templateFile);die;
		$fileContent = $this->_process($templateFile);
		
		//开启缓存模式
		if ($this->_cacheEnable) {
			
			$cacheDriver = $this->_getCacheDriver();
			$fileHash = md5($templateFile);
			
			//当文件被更新时重新编译模板并生成缓存，否则直接加载缓存
			if ($this->_fileUpdateStatus($fileHash)) {
				$pageContent = $this->compile($fileContent);
				$status = $cacheDriver->set($fileHash, $pageContent);
				
			} else {
				$pageContent = $cacheDriver->get($fileHash);
			}
			
		} else {
			//直接编译模板
			$pageContent = $this->compile($fileContent);
		}
		
		//解析文件内容
		eval('?>'.$pageContent);
		
		$pageContent = ob_get_contents();
		ob_end_clean();
		return $pageContent;
	}
	
	/**
	 * 返回编译后的文件内容
	 * 
	 * @param string $fileContent
	 * @return string
	 */
	public function compile($fileContent)
	{
		$content = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $fileContent);
		
		foreach ($this->_includeFiles as $includeFile) {
			$fileContent = file_get_contents($this->_templateDir.'/'.$includeFile);
			$content = str_replace('{include '.$includeFile.'}', $fileContent, $content);
		}
		
		$content = preg_replace("/\{($this->_varRegexp)\}/", "<?=\\1?>", $content);
		$content = preg_replace("/\{($this->_constRegexp)\}/", "<?=\\1?>", $content);
		$content = preg_replace("/(?<!\<\?\=|\\\\)$this->_varRegexp/", "<?=\\0?>", $content);
		
		
		$content = preg_replace("/\<\?=(\@?\\\$[a-zA-Z_]\w*)((\[[\\$\[\]\w]+\])+)\?\>/ies", 
			"self::_arrayIndex('\\1', '\\2')", $content);
		
		$content = preg_replace("/\{\{eval (.*?)\}\}/ies", "self::_stripvtag('<? \\1?>')", $content);
		$content = preg_replace("/\{eval (.*?)\}/ies", "self::_stripvtag('<? \\1?>')", $content);
		
		$content = preg_replace("/\{elseif\s+(.+?)\}/ies", "self::_stripvtag('<? } elseif(\\1) { ?>')", $content);
		
		for($i=0; $i<2; $i++) {
			$content = preg_replace("/\{loop\s+$this->_vtagRegexp\s+$this->_vtagRegexp\s+$this->_vtagRegexp\}(.+?)\{\/loop\}/ies", 
				"self::_loopsection('\\1', '\\2', '\\3', '\\4')", $content);
			$content = preg_replace("/\{loop\s+$this->_vtagRegexp\s+$this->_vtagRegexp\}(.+?)\{\/loop\}/ies", 
				"self::_loopsection('\\1', '', '\\2', '\\3')", $content);
		}
		
		$content = preg_replace("/\{if\s+(.+?)\}/ies", "self::_stripvtag('<? if(\\1) { ?>')", $content);
		
		
		$content = preg_replace("/\{else\}/is", "<? } else { ?>", $content);
		$content = preg_replace("/\{\/if\}/is", "<? } ?>", $content);
		
		$content = preg_replace("/$this->_constRegexp/", "<?=\\1?>", $content);
		
		$content = preg_replace("/(\\\$[a-zA-Z_]\w+\[)([a-zA-Z_]\w+)\]/i", "\\1'\\2']", $content);
		
		return $content;
	}
	
	/**
	 * 分析文件，返回预处理后的文件内容
	 * 
	 * @param string $file
	 * @return string
	 */
	private function _process($templateFile)
	{
		$fileContent = file_get_contents($templateFile);
		$this->_files[] = $templateFile;
		
		$res = preg_match_all("#\{include\s+(.+?)\}#ise", $fileContent, $matches);
		if ($res) {
			foreach ($matches[1] as $includeFile) {
				$this->_files[] = $this->_templateDir.'/'.$includeFile;
				$this->_includeFiles[] = $includeFile;
			}
		}
		return $fileContent;
	}
	
	/**
	 * gzip压缩
	 * 
	 * @param string $pageContent
	 * @return void
	 */
	private function _gzip($pageContent)
	{
            /*
		if (!headers_sent() && extension_loaded('zlib') 
			&& strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
			$pageContent = gzencode($pageContent, 9);
			header("Content-Encoding: gzip");
			header("Vary: Accept-Encoding");
			header("Content-Length: " . strlen($pageContent));
		}
             * 
             */
		return $pageContent;
	}
	
	/**
	 * 编译语法
	 */
	private static function _arrayIndex($name, $items)
	{
		$items = preg_replace("/\[([a-zA-Z_]\w*)\]/is", "['\\1']", $items);
		return "<?={$name}{$items}?>";
	}
	private function _stripvtag($s)
	{
		return preg_replace("/$this->_vtagRegexp/is", "\\1", str_replace("\\\"", '"', $s));
	}
	private function _loopsection($arr, $k, $v, $statement)
	{
		$arr = self::_stripvtag($arr);
		$k = self::_stripvtag($k);
		$v = self::_stripvtag($v);
		$statement = str_replace("\\\"", '"', $statement);
		return $k ? "<? foreach((array)$arr as $k => $v){?>$statement<?}?>" 
			: "<? foreach((array)$arr as $v) {?>$statement<? } ?>";
	}
	private function _rewrite($content)
	{
		if ($this->_rewriteEnable)
			$content = preg_replace($this->_pregSearchs, $this->_pregReplaces, $content);
		return $content;
	}
	
	/**
	 * 获取缓存系统的驱动对象
	 * 
	 * @return object
	 */
	private function _getCacheDriver()
	{
		if (null !== self::$_cacheDriver)
			return self::$_cacheDriver;
		return self::$_cacheDriver = TZ_Redis::connect('user');
	}
	
	/**
	 * 模板文件的更新状态
	 * 
	 * @param string $fileHash
	 * @return boolean
	 */
	private function _fileUpdateStatus($fileHash)
	{
		$updateStatus = false;									//文件更新状态
		$cacheDriver = $this->_getCacheDriver();				//缓存驱动
		$fileUpdateTimeKey = $fileHash.'_time';					//文件哈希
		$lastModifyTime = array();								//最后更新时间
		
		foreach ($this->_files as $file)
			$lastModifyTime[md5($file)] = filemtime($file);		//子文件hash
		$cacheTime = $cacheDriver->get($fileUpdateTimeKey);
		
		//缓存时间存在
		if ($cacheTime) {
			$cacheTime = json_decode($cacheTime);
			foreach ($cacheTime as $itemFile => $itemCacheTime) {
				if ($lastModifyTime[$itemFile] > $itemCacheTime) {
					$updateStatus = true;
					break;
				}
			}
			if ($updateStatus)
				$cacheDriver->set($fileUpdateTimeKey, json_encode($lastModifyTime));
		} else {
			$cacheDriver->set($fileUpdateTimeKey, json_encode($lastModifyTime));
			$updateStatus = true;
		}
		
		return $updateStatus;
	}
	
}