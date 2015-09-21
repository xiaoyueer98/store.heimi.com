<?php
/* Goods model
 * @author  nick <zhaozhiwei@747.cn>
 * @created_at  2014-12-5 10:52:18 
 */

class GoodsModel extends TZ_Db_Table {

    //init
    public function __construct() {

        parent::__construct(Yaf_Registry::get('mall_db'), 'mall_db.goods');
    }
	
	/*
	 * 减少商品库存数量
	 * goods_id 商品ID
	 * num      商品数量
	 *
	 * return bool
	 */
	public function updateNum($goods_id,$num)
	{
		$sql = "update mall_db.goods set num=num-".$num.",updated_at='".date('Y-m-d H:i:s')."' where goods_id=".$goods_id." and status=1 and num>=".$num;		
		$res = $this->query($sql);
		return $res;
	}
}
