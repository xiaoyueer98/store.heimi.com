<?php
/**
 * callUser model file
 * 商品获取操作
 * @author 子龙 <songyang@747.cn>
 * @final 2014-10-18
 */
class CallUserModel extends TZ_Db_Table {
	/**
	 * construct table
	 */
	public function __construct()
	{
		parent::__construct(Yaf_Registry::get('user_db'), 'user_db.call_user');
	}
}
