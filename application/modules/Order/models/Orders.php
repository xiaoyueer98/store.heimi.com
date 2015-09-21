<?php
/**
 * OrdersModel file
 * @author 刑天 <wangtongmeng@747.cn>
 * @final 2014-12-5
 */
class OrdersModel extends TZ_Db_Table {

    //init
	public function __construct() 
	{
        parent::__construct(Yaf_Registry::get('mall_db'), 'mall_db.orders');
    }
}
