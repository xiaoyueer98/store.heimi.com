<?php
/**
 * goodsinfo controller class
 * @author  nick <zhaozhiwei@747.cn>
 * @final 2014-12-05
 */
class PayService
{
    public function updateOrder($orderId,$status)
    {
        $ret = true;
        //1未付款，2未发货，3已发货，4已关闭，5已删除
        $condition = array('order_sn:eq'=>$orderId);
        $order = TZ_Loader::model('Orders', 'Order')->select($condition,array('goods_id','status','num'),'ROW');
        if($order['status'] == '1')
        {
            $cols = array('status' => $status);
            $ret = TZ_Loader::model('Orders', 'Order')->update($cols,$condition);
            TZ_Loader::model('Goods', 'Api')->updateNum($order['goods_id'],$order['num']);
            //刷新商品信息
            $redis = TZ_Redis::connect('mall');
            $redis->hDel('goods_info',$order['goods_id']);
        }
        return $ret;
    }
}