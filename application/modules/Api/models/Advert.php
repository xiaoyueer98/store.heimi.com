<?php
/* Goods model
 * @author  nick <zhaozhiwei@747.cn>
 * @created_at  2014-12-5 10:52:18 
 */

class AdvertModel extends TZ_Db_Table {

    //init
    public function __construct() {

        parent::__construct(Yaf_Registry::get('mall_db'), 'mall_db.advert');
    }
    

}