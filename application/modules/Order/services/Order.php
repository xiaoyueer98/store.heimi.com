<?php
/**
 * order service file
 *
 * @author  刑天 <wangtongmeng@747.cn>
 * @final 2014-12-08
 */
class OrderService {

	/*
	 * 修改订单
	 * uid		用户的UID
	 * order_sn	应用系统订单号
	 * return int 
	 */
	public function setOrder($order_sn,$uid)
	{
		//只有未付款的订单用户可以修改
		$cCondition = array('status:in' => array('1','4'),'order_sn:eq' =>$order_sn,'uid:eq' => $uid);
		$classOrder = TZ_Loader::model('Orders','Order');
		$checkOrder = $classOrder->select($cCondition,'order_sn','ROW');
		if(is_array($checkOrder) && count($checkOrder)>0)
		{
			$set = array('status'=>5,'updated_at'=>date('Y-m-d H:i:s'));
			$upCondition = array('order_sn:eq' => $order_sn, 'uid:eq' => $uid, 'status:in' => array('1','4'));
			$upOrder = $classOrder->update($set,$upCondition);
			if(!empty($upOrder))
			{
				//修改成功
				return 1;
			}else
			{
				//修改失败
				return  2;
			}
		}else
		{
        	return 2;
        }
	}
	
	/*
	 * 生成订单支付
	 * userInfo	用户信息
	 * arGoods	商品信息
	 * num		商品数量
	 * arAddress收货地址信息
	 */
	public function createOrder($userInfo,$arGoods,$num,$arAddress)
	{
		//下订单
		$order_sn = TZ_Loader::service('IdManager', 'User')->createUID();
		$total_price = bcmul($arGoods['price'], $num,2);
		$real_price = $arGoods['is_promot']>0 ? bcmul($arGoods['promot'],$num,2) : bcmul($arGoods['price'],$num,2);
		$arOrder = array();
		$arOrder['order_sn'] = $order_sn;
		$arOrder['uid']	= $userInfo['uid'];	
		$arOrder['telephone']	= $userInfo['telephone'];	
		$arOrder['goods_id']	= $arGoods['goods_id'];	
		$arOrder['goods_name']	= $arGoods['goods_name'];	
		$arOrder['title']	= $arGoods['title'];	
		$arOrder['desc']	= $arGoods['desc'];	
		$arOrder['picture']	= $arGoods['picture'];	
		$arOrder['price']	= $arGoods['price'];	
		$arOrder['promot']	= $arGoods['promot'];	
		$arOrder['is_promot']	= $arGoods['is_promot'];	
		$arOrder['num']	= $num;	
		$arOrder['total_price']	= $total_price;	
		$arOrder['real_price']	= $real_price;	
		$arOrder['buyer_name']	= $arAddress['name'];	
		$arOrder['buyer_telephone']	= $arAddress['receive_tel'];	
		$arOrder['buyer_address']	= $arAddress['address'];	
		$arOrder['buyer_postcode']	= $arAddress['postcode'];	
		$arOrder['pay_type']	= 1;//1支付宝，2银联	
		$arOrder['status']	= 1;//1 未付款	
		$arOrder['created_at']	= $arOrder['updated_at'] = date('Y-m-d H:i:s');	
		//入库
		$create = TZ_Loader::model('Orders','Order')->insert($arOrder);
		if($create !==false)
		{
			//支付
			$result = TZ_Loader::service('Pay','Pay')->pay($userInfo['uid'],$order_sn,$real_price,$arGoods['goods_name']);
			return $result;
		}else
		{
			throw new Exception('下单失败，请重试');
		}
	}
	
	/*
	 * 订单支付
	 * userInfo	用户信息
	 * arOrder	商品信息
	 */
	public function payOrder($uid,$arOrder)
	{
		$result = TZ_Loader::service('Pay','Pay')->pay($uid,$arOrder['order_sn'],$arOrder['real_price'],$arOrder['goods_name']);
		return $result;
	}
	
}
