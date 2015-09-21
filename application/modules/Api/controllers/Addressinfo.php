<?php

/**
 * addressinfo controller class
 *
 * @author  莫愁 <sunyue@747.cn>
 * @final 2014-12-05
 */
class AddressinfoController extends Yaf_Controller_Abstract {
    
    /*
       引入类似alert效果html
     *
     */
    public function include_alert() {
        include_once(APP_PATH . "/application/modules/Api/views/like_alert_php.html");
    }

    /**
     * 
     * 地址管理页面
     * 
     * 需要传值
     * session_id  
     * 
     */
    public function indexAction() {
        
       
        
        $params = TZ_Request::getParams('get');
        
        //过滤
        foreach($params as $pk =>$pv){
            $params[$pk] = TZ_Request::clean($pv);
        }
        
        $host = Yaf_Application::app()->getConfig()->heimi->appstore->host;
        $sid = !empty($params['session_id']) ? $params['session_id'] : "";
        $uid = TZ_Loader::service('SessionManager', 'User')->getUid($sid);
        //$uid = 11;
        if (empty($uid)) {
            $this -> include_alert();
            //throw new Exception("登录过期，请重新登录");
            $url = "hmbox://cn.747.box/login?to=" . $host . "/api/addressinfo/index?session_id={sessionid}";
            echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript' charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
            die;
        }
        $this->_view->assign("session_id", $sid);
        //load service
        $oAddressAboutService = TZ_Loader::service('AddressAbout', 'Api');
        //get data
        $condition = array();
        $condition['uid:eq'] = $uid;
        $condition['status:eq'] = 1;
        //查找该用户使用状态的地址信息列表
        $arAddressList = $oAddressAboutService->getAddressList($condition);
        //查找该用户使用状态的地址信息总数
        $arAddressTotal = $oAddressAboutService->getAddressTotal($condition);

        $arDefault = array();   //默认地址一维数组
        //如果没有地址信息   跳转到没有地址页面
        if (empty($arAddressList)) {
            $this->_view->display("mg_noaddress.html");
        } else {
            foreach ($arAddressList as $k => $v) {

                if (trim($v['area']) == '0') {
                    $arAddressList[$k]['area'] = "";
                }
                $proLength = mb_strlen($v['province'], 'utf8');
                $cityLength = mb_strlen($v['city'], 'utf8');
                $areaLength = mb_strlen(trim($v['area']), 'utf8');
                $lastLength = 40 - intval($proLength) - intval($cityLength) - intval($areaLength);
                if (mb_strlen(trim($v['detail']), 'utf8') > $lastLength) {
                    $arAddressList[$k]['detail'] = mb_substr(trim($arAddressList['detail']), 0, $lastLength, 'utf-8') . '...';
                }
                if ($v['is_default'] == "1") {
                    $arDefault = $arAddressList[$k];
                    unset($arAddressList[$k]);
                }
            }
            if (!empty($arDefault)) {
                array_unshift($arAddressList, $arDefault);
            }
        }
        //var_dump($arAddressList);

        $this->_view->assign("arAddressList", $arAddressList);
        $this->_view->assign("arAddressTotal", $arAddressTotal);
        $this->_view->display("mg_mgaddress.html");
    }

    /**
     * 增加地址信息
     * 
     * 需要传值
     * session_id  
     * linkfrom   上一个页面        
     * default    是否设为默认地址，首次添加需要传值"y"
     * 
     */
    public function addAddressAction() {
        
        $host = Yaf_Application::app()->getConfig()->heimi->appstore->host;
        $params = TZ_Request::getParams('get');
        
        //过滤
        foreach($params as $pk =>$pv){
            $params[$pk] = TZ_Request::clean($pv);
        }
        
        $default = !empty($params['default']) ? $params['default'] : "";
        //如果是订单方传过来的，跳回去时需要加上goods_id这个参数
        $goods_id = !empty($params['goods_id']) ? $params['goods_id'] : "";
        $linkfrom = !empty($params['linkfrom']) ? $params['linkfrom'] : "";
        if (empty($linkfrom)) {
            throw new Exception("跳转异常");
        }
        if ($linkfrom == "1") {
            $link = $host . "/api/addressinfo/index?session_id={sessionid}";
        } else {
            $link = $host . "/api/addressinfo/orderaddressmanage?session_id={sessionid}&goods_id={$goods_id}";
        }

        $sid = !empty($params['session_id']) ? $params['session_id'] : "";
        $uid = TZ_Loader::service('SessionManager', 'User')->getUid($sid);
        //$uid = 11;   
        if (empty($uid)) {
            $this -> include_alert();
            //throw new Exception("登录过期，请重新登录");
            $url ="hmbox://cn.747.box/login?to=" . $link ;
            echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript' charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
            die;
        }

        //如果该用户的地址记录已经>=10条,则挑战到列表页面
        $condition['status:eq'] = 1;
        $condition['uid:eq'] = $uid;
        $total = TZ_Loader::service('AddressAbout', 'Api')->getAddressTotal($condition);

        if ($total >= 10) {
            //设置跳转页面
            $arUrl = array("1" => "/api/addressinfo/index?session_id={$sid}", "2" => "/api/addressinfo/orderaddressmanage?session_id={$sid}&goods_id={$goods_id}");
            header("location:" . $arUrl[$linkfrom]);
        }
        
        $this->_view->assign("default", $default);
        $this->_view->assign("session_id", $sid);
        $this->_view->assign("goods_id", $goods_id);
        $this->_view->assign("linkfrom", $linkfrom);
        $this->_view->display("add_address.html");
    }

    //增加地址保存方法
    public function addAddressSaveAction() {
        
        $host = Yaf_Application::app()->getConfig()->heimi->appstore->host;
        $params = TZ_Request::getParams('post');
        
        //过滤
        foreach($params as $pk =>$pv){
            $params[$pk] = TZ_Request::clean($pv);
        }
        
        //如果是订单方传过来的，跳回去时需要加上goods_id这个参数
        $goods_id = !empty($params['goods_id']) ? $params['goods_id'] : "";
        //var_dump($params);die;
        $linkfrom = !empty($params['linkfrom']) ? $params['linkfrom'] : "";
        if (empty($linkfrom)) {
            throw new Exception("跳转异常");
        }
        if ($linkfrom == "1") {
            $link = $host . "/api/addressinfo/index?session_id={sessionid}";
        } else {
            $link = $host . "/api/addressinfo/orderaddressmanage?session_id={sessionid}&goods_id={$goods_id}";
        }
        $sid = $params['session_id'];
        $uid = TZ_Loader::service('SessionManager', 'User')->getUid($sid);

        //$uid = 11;
        if (empty($uid)) {
            $this -> include_alert();
            //throw New Exception('登录过期，请重新登录');
            $url = "hmbox://cn.747.box/login?to=" . $link ;
            echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript' charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
            die;
        }
        $userInfo = TZ_Loader::service("User", "User")->getInfoByUid($uid);
        //$userInfo['telephone'] = "121";
        if (empty($userInfo)) {
            $this -> include_alert();
            //throw New Exception('登录过期，请重新登录!');
            $url = "hmbox://cn.747.box/login?to=" . $link;
            echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript' charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
            die;
        }
        //如果设置该条地址为默认地址，则将该用户其他的可用地址设为非默认
        if ($params['default'] == "1") {
            $set['is_default'] = 0;
            $condition['uid:eq'] = $uid;
            $condition['status:eq'] = 1;
            $result = TZ_Loader::model('Address', 'Api')->update($set, $condition);
        }
        $city = trim(str_replace($params['prov'], "", $params['city']));
        if (empty($params['distinct'])) {
            $distinct = "0";
        } else {
            $distinct = trim(str_replace($params['city'], "", $params['distinct']));
        }
        $addArr = array(
            'uid' => $uid,
            'telephone' => $userInfo['telephone'],
            'name' => $params['username'],
            'receive_tel' => $params['tel'],
            'province' => $params['prov'],
            'city' => $city,
            'area' => $distinct,
            'detail' => $params['detail'],
            'postcode' => $params['postcode'],
            'status' => 1,
            'is_default' => $params['default'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $result = TZ_Loader::model('Address', 'Api')->insert($addArr);
        //var_dump($result);die;
       
       
        if ($result) {
           
            $arUrl = array("1" => "/api/addressinfo/index?session_id={$sid}", "2" => "/api/order/handleorder?session_id={$sid}&goods_id={$goods_id}&addressId={$result}");
            header("location:" . $arUrl[$linkfrom]);
        }
    }

    /**
     * 删除地址操作
     * 
     * 需要传值
     * session_id  
     * linkfrom   上一个页面
     * addrId     地址信息id
     * 
     */
    public function deleteAddressAction() {
        $host = Yaf_Application::app()->getConfig()->heimi->appstore->host;
        $params = TZ_Request::getParams('post');
        
        //过滤
        foreach($params as $pk =>$pv){
            $params[$pk] = TZ_Request::clean($pv);
        }
        
        $sid = !empty($params['session_id']) ? $params['session_id'] : "";
        $uid = TZ_Loader::service('SessionManager', 'User')->getUid($sid);
        //$uid = 11;
        if (empty($uid)) {
            //throw new Exception("登录过期，请重新登录");
            echo "3";
            die;
        }

        $addressId = !empty($params['addressId']) ? $params['addressId'] : "";
        $set['status'] = 0;
        $condition['id:eq'] = $addressId;
        $result = TZ_Loader::model('Address', 'Api')->update($set, $condition);

        if ($result) {
            echo "1";
            die;
        } else {
            echo "2";
            die;
        }
    }

    /**
     * 修改地址页面
     * 
     * 需要传值
     * session_id  
     * linkfrom   上一个页面
     * addrId     地址信息id
     * 
     */
    public function updateAddressAction() {
        
        $host = Yaf_Application::app()->getConfig()->heimi->appstore->host;
        $params = TZ_Request::getParams('get');
        
        //过滤
        foreach($params as $pk =>$pv){
            $params[$pk] = TZ_Request::clean($pv);
        }
        
        //如果是订单方传过来的，跳回去时需要加上goods_id这个参数
        $goods_id = !empty($params['goods_id']) ? $params['goods_id'] : "";
        $linkfrom = !empty($params['linkfrom']) ? $params['linkfrom'] : "";
        if (empty($linkfrom)) {
            throw new Exception("跳转异常");
        }
        if ($linkfrom == "1") {
            $link = $host . "/api/addressinfo/index?session_id={sessionid}";
        } else {
            $link = $host . "/api/addressinfo/orderaddressmanage?session_id={sessionid}&goods_id={$goods_id}";
        }
        $sid = !empty($params['session_id']) ? $params['session_id'] : "";
        $uid = TZ_Loader::service('SessionManager', 'User')->getUid($sid);
        //$uid = 22;
        if (empty($uid)) {
            $this -> include_alert();
            //throw new Exception("登录过期，请重新登录");
            $url = "hmbox://cn.747.box/login?to=" .$link;
            echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript' charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
            die;
        }

        $addressId = !empty($params['addressId']) ? $params['addressId'] : "";
        if (empty($linkfrom)) {
            throw new Exception("跳转异常");
        }
        //load service
        $oAddressAboutService = TZ_Loader::service('AddressAbout', 'Api');
        $condition['id:eq'] = $addressId;
        //查找指定id的地址信息
        $arAddressInfo = $oAddressAboutService->getAddressInfo($condition);
        if(empty($params['addressId_use'])){
            $addressId_use  = 0;
        }else{
            $addressId_use = $params['addressId_use'];
        }
        $this->_view->assign("addressId_use", $addressId_use);
        $this->_view->assign("goods_id", $goods_id);
        $this->_view->assign("arAddressInfo", $arAddressInfo);
        $this->_view->assign("linkfrom", $linkfrom);
        $this->_view->assign("session_id", $sid);
        $this->_view->display("update_address.html");
    }

    //修改地址保存方法
    public function updateAddressSaveAction() {
        
        $host = Yaf_Application::app()->getConfig()->heimi->appstore->host;
        $params = TZ_Request::getParams('post');
        
        //过滤
        foreach($params as $pk =>$pv){
            $params[$pk] = TZ_Request::clean($pv);
        }
        
        //var_dump($params);die;
        $sid = $params['session_id'];
        $uid = TZ_Loader::service('SessionManager', 'User')->getUid($sid);
        //$uid = 11;
        if (empty($uid)) {
            //throw New Exception('登录过期，请重新登录');
            echo "3";
            die;
        }

        //如果设置该条地址为默认地址，则将该用户其他的可用地址设为非默认
        if ($params['default'] == "1") {
            $set['is_default'] = 0;
            $condition['uid:eq'] = $uid;
            $condition['status:eq'] = 1;
            $result = TZ_Loader::model('Address', 'Api')->update($set, $condition);
        }
        $city = trim(str_replace($params['prov'], "", $params['city']));
        if (empty($params['distinct'])) {
            $distinct = "0";
        } else {
            $distinct = trim(str_replace($params['city'], "", $params['distinct']));
        }
        $addArr = array(
            'name' => $params['username'],
            'receive_tel' => $params['tel'],
            'province' => $params['prov'],
            'city' => $city,
            'area' => $distinct,
            'detail' => $params['detail'],
            'postcode' => $params['postcode'],
            'is_default' => $params['default'],
            'updated_at' => date('Y-m-d H:i:s')
        );
        //var_dump($addArr);
        $condition1['id:eq'] = $params['addressId'];
        $result = TZ_Loader::model('Address', 'Api')->update($addArr, $condition1);
        //var_dump($result);die;

        if ($result) {
            echo "1";
            die;  //修改成功
        } else {
            echo "2";
            die;  //修改失败
        }
    }

    /**
     * 
     * 从确定订单跳转到的地址管理页面
     * 
     * 需要传值
     * session_id  
     * 
     */
    public function orderaddressmanageAction() {
        
        $host = Yaf_Application::app()->getConfig()->heimi->appstore->host;
        $params = TZ_Request::getParams('get');
        
        //过滤
        foreach($params as $pk =>$pv){
            $params[$pk] = TZ_Request::clean($pv);
        }
        
        $goods_id = !empty($params['goods_id']) ? $params['goods_id'] : "";
        $sid = !empty($params['session_id']) ? $params['session_id'] : "";
        $uid = TZ_Loader::service('SessionManager', 'User')->getUid($sid);
        //$uid = 11;
        if (empty($uid)) {
            $this -> include_alert();
            //throw new Exception("登录过期，请重新登录");
            $url = "hmbox://cn.747.box/login?to=" . $host . "/api/addressinfo/orderaddressmanage?session_id={sessionid}&goods_id=" . $goods_id;
            echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/><script type='text/javascript' charset='utf-8'>like_alert_just('登录过期，请重新登录','".$url."');</script>";
            die;
        }
        $this->_view->assign("session_id", $sid);
        //load service
        $oAddressAboutService = TZ_Loader::service('AddressAbout', 'Api');
        //get data
        $condition = array();
        $condition['uid:eq'] = $uid;
        $condition['status:eq'] = 1;
        //查找该用户使用状态的地址信息列表
        $arAddressList = $oAddressAboutService->getAddressList($condition);
        //查找该用户使用状态的地址信息总数
        $arAddressTotal = $oAddressAboutService->getAddressTotal($condition);


        //如果没有地址信息   跳转到没有地址页面
        if (empty($arAddressList)) {
            $this->_view->display("mg_noaddress.html");
        } else {
            foreach ($arAddressList as $k => $v) {

                if (trim($v['area']) == '0') {
                    $arAddressList[$k]['area'] = "";
                }
                $proLength = mb_strlen($v['province'], 'utf8');
                $cityLength = mb_strlen($v['city'], 'utf8');
                $areaLength = mb_strlen(trim($v['area']), 'utf8');
                $lastLength = 40 - intval($proLength) - intval($cityLength) - intval($areaLength);
                if (mb_strlen(trim($v['detail']), 'utf8') > $lastLength) {
                    $arAddressList[$k]['detail'] = mb_substr(trim($arAddressList['detail']), 0, $lastLength, 'utf-8') . '...';
                }

                if ($v['is_default'] == "1") {
                    $arDefault = $arAddressList[$k];
                    unset($arAddressList[$k]);
                }
            }
            if (!empty($arDefault)) {
                array_unshift($arAddressList, $arDefault);
            }
        }
        //var_dump($arAddressList);
        //得到当前使用的那一条地址
        if (!empty($params['addressId'])) {
            $addressId = $params['addressId'];
        } else {
            $addressId = 0;
        }
        $this->_view->assign("addressId", $addressId);
        $this->_view->assign("goods_id", $goods_id);
        $this->_view->assign("arAddressList", $arAddressList);
        $this->_view->assign("arAddressTotal", $arAddressTotal);
        $this->_view->display("order_mgaddress.html");
    }

    /**
     * 
     * 获取用户的默认信息接口
     * 
     * 需要传值
     * session_id   
     *
     * 返回一个数组，数组下标如下
     * name    收货人姓名 
     * receive_tel   收货人电话
     * address    收货人地址 
     */
    public function getDefaultAddressAction() {

        $result = TZ_Loader::service('AddressAbout', 'Api')->getDefaultAddress("11");
    }

}
