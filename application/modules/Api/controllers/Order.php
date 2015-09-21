<?php
/**
 * Order controller file
 * 获取订单列表
 * @author 刑天 <wangtongmeng@747.cn>
 * @final 2014-12-5
 */
class OrderController extends Yaf_Controller_Abstract 
{
    
	/**
	 * 获得用户订单信息
	 *
	 * @param  $session_id 用户session
	 *
	 * @Return array 
	 */
	public function indexAction()
	{
                $this -> include_alert();
		$params = TZ_Request::getParams('get');
		$sid = TZ_Request::clean($params['session_id']);
		$uid = TZ_Loader::service('SessionManager','User')->getUid($sid);
		$mallHost = Yaf_Registry::get('config')->heimi->appstore->host;
		if(empty($uid))
		{
                        $url = "hmbox://cn.747.box/login?to=".$mallHost."/api/order/index?session_id={sessionid}";
			echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript'  charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
			die();
		}
		$oContidion = array('uid:eq' => $uid, 'status:neq' => 5,'order' => 'created_at DESC');
		$arOrders = TZ_Loader::model('Orders','Order')->select($oContidion,'*','ALL');
		//处理订单跳转
		if(count($arOrders)>0)
		{
			foreach($arOrders as &$val)
			{
				switch($val['status'])
				{
				case 1:
					$val['goto_name'] = '去支付';
					$val['status_name'] = '未付款';
					break;
				case 2:
					$val['goto_name'] = '再次购买';
					$val['status_name'] = '已付款未发货';
					break;
				case 3:
					$val['goto_name'] = '再次购买';
					$val['status_name'] = '已发货';
					break;
				case 4:
					$val['goto_name'] = '再次购买';
					$val['status_name'] = '已关闭';
					break;
				default:
					$val['goto_name'] = '再次购买';
					$val['status_name'] = '处理中';
				
				}
			}
			$this->_view->assign('sid',$sid);
			$this->_view->assign('arOrders',$arOrders);
			$this->_view->display('order_info_list.html');
		}else
		{
			$this->_view->display('order_info_no.html');
		}
	}
	
	/**
	 * 用户下订单
	 *
	 * @param  $session_id	用户session
	 * @param  $goods_id 	商品ID
	 *
	 * @Return array 
	 */
	public function handleOrderAction()
	{
		$params = TZ_Request::getParams('get');
                $goods_id = TZ_Request::clean($params['goods_id']);
                $address_id = TZ_Request::clean($params['addressId']);
                $sid = TZ_Request::clean($params['session_id']);
		$trad = '0.00';
                $this -> include_alert();
		if(!empty($goods_id) && isset($sid) && !empty($sid))
		{
			$gCondition = array('goods_id:eq' => $goods_id, 'status:eq' => 1, 'num:gt' => 0);
			$arGoods = TZ_Loader::model('Goods','Api')->select($gCondition,'*','ROW');	
			if(empty($arGoods))
			{       $url ="hmbox://cn.747.box/shop";
				echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript'  charset='utf-8'>like_alert_just('商品库存不足或已下架，请选择其它商品','".$url."','商品库存不足');</script>";
				die();
			}
			//加运费后的价格
			$arGoods['true_price'] =  !empty($arGoods['is_promot']) ? $arGoods['promot'] : $arGoods['price'];
			//获取用户默认地址
                        $addressId = intval($address_id)>0 ? intval($address_id) : 0;
			$arAddress = TZ_Loader::service('AddressAbout', 'Api') -> getDefaultAddress($sid,$addressId);
			if($arAddress=='error')
			{
				$mallHost = Yaf_Registry::get('config')->heimi->appstore->host;
                                $url = "hmbox://cn.747.box/login?to=".$mallHost."/api/order/handleOrder?goods_id=".$goods_id."&session_id={sessionid}";
				echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript'  charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
				die();

			}
		}else
		{
			$mallHost = Yaf_Registry::get('config')->heimi->appstore->host;
                        $url = "hmbox://cn.747.box/login?to=".$mallHost."/api/order/handleOrder?goods_id=".$goods_id."&session_id={sessionid}";
                        echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript'  charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
                        die();
		}
		$this->_view->assign('trad',$trad);
		$this->_view->assign('sid',$sid);
		$this->_view->assign('arGoods',$arGoods);
		$this->_view->assign('arAddress',$arAddress);
		$this->_view->display('order_shopping_cart.html');

	}
	
	/**
	 * 用户购买支付
	 *
	 * @param  $session_id	用户session
	 * @param  $goods_id 	商品ID
	 * @param  $num			商品数量
	 *
	 * @Return array 
	 */
	public function payAction()
	{       
		$params = TZ_Request::getParams('post');
                $sid = TZ_Request::clean($params['session_id']);
                $goods_id = TZ_Request::clean($params['goods_id']);
                $num = TZ_Request::clean($params['num']);
                $address_id = TZ_Request::clean($params['addressId']);
		$mallHost = Yaf_Registry::get('config')->heimi->appstore->host;
		//检测用户是否登录
		$uid = TZ_Loader::service('SessionManager','User')->getUid($sid);
		$userInfo = TZ_Loader::service('User','User')->getInfoByUid($uid);
		if(empty($userInfo) || empty($goods_id) || empty($num))
		{
                        $this -> include_alert();
                        $url = "hmbox://cn.747.box/login?to=".$mallHost."/api/order/pay?goods_id=".$goods_id."&num=".$num."&session_id={sessionid}";
			echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript'  charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
			die();
		}
		//商品是否存在
		$gCondition = array('goods_id:eq' => $goods_id, 'status:eq' => 1, 'num:egt' =>$num, 'max_num:egt' => $num);
		$arGoods = TZ_Loader::model('Goods','Api')->select($gCondition,'*','ROW');
		if(empty($arGoods)||count($arGoods)<1)
		{
                        $this -> include_alert();
                        $url = "hmbox://cn.747.box/shop";
			echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript'  charset='utf-8'>like_alert_just('商品库存不足或已下架，请选择其它商品','".$url."','商品库存不足');</script>";
			die();
		}
		//获取用户默认地址
                $addressId = intval($address_id)>0 ? intval($address_id) : 0;
		$arAddress = TZ_Loader::service('AddressAbout', 'Api') -> getDefaultAddress($sid,$addressId);
		if(empty($arAddress) || count($arAddress)<1)
		{
                        $this -> include_alert();
                        $url = "hmbox://cn.747.box/login?to=".$mallHost."/api/addressinfo/addaddress?goods_id=".$goods_id."default=y&linkfrom=2&goods_id=".$goods_id."&session_id={sessionid}";
			echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript'  charset='utf-8'>like_alert_just('请先填写收货地址','".$url."','请先填写收货地址');</script>";
			die();
		}
		$creatOrder = TZ_Loader::service('Order','Order')->createOrder($userInfo,$arGoods,$num,$arAddress);
		TZ_Request::success($creatOrder);
	}
	
	/**
	 * 用户订单支付
	 *
	 * @param  $session_id	用户session
	 * @param  $osn 		订单ID
	 *
	 * @Return array 
	 */
	public function payOrderAction()
	{
		$params =TZ_Request::getParams('post');
                $sid = TZ_Request::clean($params['session_id']);
                $order_sn = TZ_Request::clean($params['osn']);
		$uid = TZ_Loader::service('SessionManager','User')->getUid($sid);
		if(empty($uid) || empty($order_sn))
		{
			throw new Exception('Parameter error');
		}
		$oContidion = array('uid:eq' => $uid, 'status:eq' =>1,'order_sn:eq' => $order_sn);
		$arOrder = TZ_Loader::model('Orders','Order')->select($oContidion,'*','ROW');;
		if(count($arOrder)>0 && $arOrder['real_price']>0)
		{
			$payOrder = TZ_Loader::service('Order','Order')->payOrder($uid,$arOrder);		
			TZ_Request::success($payOrder);
		}else
		{
			throw new Exception('没有该订单');
		}
	}

	/**
	 * 逻辑删除用户订单
	 *
	 * @param  $session_id	用户session
	 * @param  $osn 		用户订单ID
	 *
	 * @Return array 
	 */
	public function delAction()
	{
		$params = TZ_Request::getParams('post');
                $sid = TZ_Request::clean($params['sid']);
                $order_sn = TZ_Request::clean($params['osn']);
		if(!empty($order_sn) && !empty($sid))
		{
			$uid = TZ_Loader::service('SessionManager','User')->getUid($sid);
			if(empty($uid))
			{
				//登录过期
				echo 3;
                                die();
			}
			$upOrder = TZ_Loader::service('Order','Order')->setOrder($order_sn,$uid);	
			if(!empty($upOrder))
			{
				echo $upOrder;
                                die();
			}else
			{
				echo 3;
                                die();
			}
		}else
		{
			//非法修改，让其重新登录
			echo 3;
                        die();
		}
	}

	/**
	 * 查看用户订单详情
	 *
	 * @param  $session_id	用户session
	 * @param  $osn 		用户订单ID
	 *
	 * @Return array 
	 */
	public function detailAction()
	{       
                $this -> include_alert();
		$params = TZ_Request::getParams('get');
                $sid = TZ_Request::clean($params['session_id']);
                $order_sn = TZ_Request::clean($params['osn']);
                $num = TZ_Request::clean($params['num']);
		if(!empty($order_sn) && !empty($sid))
		{
			$uid = TZ_Loader::service('SessionManager','User')->getUid($sid);
			$mallHost = Yaf_Registry::get('config')->heimi->appstore->host;
			if(empty($uid))
			{
				//登录过期
                                $url = "hmbox://cn.747.box/login?to=".$mallHost."/api/order/detail?osn=".$order_sn."&num=".$num."&session_id={sessionid}";
				echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript'  charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
				die();
			}
			//获取订单详细信息
			$iCondition = array('uid:eq' => $uid, 'order_sn:eq' => $order_sn, 'status:neq' => 5);
			$arOrderInfo = TZ_Loader::model('Orders','Order')->select($iCondition,'*','ROW');
			if(empty($arOrderInfo) || !is_array($arOrderInfo) || count($arOrderInfo)< 1)	
			{
				throw New Exception('没有该订单');
			}
			$arOrderInfo['saving_price'] = $arOrderInfo['total_price']-$arOrderInfo['real_price'] > 0 ? $arOrderInfo['total_price']-$arOrderInfo['real_price'] : '0.00';
			switch($arOrderInfo['status'])
			{
				case 1:
					$arOrderInfo['goto_name'] = '去支付';
					$arOrderInfo['status_name'] = '未付款';
					break;
				case 2:
					$arOrderInfo['goto_name'] = '再次购买';
					$arOrderInfo['status_name'] = '已付款未发货';
					break;
				case 3:
					$arOrderInfo['goto_name'] = '再次购买';
					$arOrderInfo['status_name'] = '已发货';
					break;
				case 4:
					$arOrderInfo['goto_name'] = '再次购买';
					$arOrderInfo['status_name'] = '已关闭';
					break;
				default:
					$arOrderInfo['goto_name'] = '再次购买';
					$arOrderInfo['status_name'] = '处理中';
				
			}
		}else
		{
			throw new Exception('Parameter error');
		}
		$this->_view->assign('sid',$sid);
		$this->_view->assign('arOrderInfo',$arOrderInfo);
		$this->_view->display('order_info_detail.html');

	}

        /*
           引入类似alert效果html
         *
         */
        public function include_alert() {
            include_once(APP_PATH . "/application/modules/Api/views/like_alert_php.html");
        }
}
