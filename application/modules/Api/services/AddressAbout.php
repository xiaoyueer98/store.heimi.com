<?php

/* addressabout service
 * 
 * @author  莫愁 <sunyue@747.cn>
 * @created_at  2014-12-5 10:54:32 

 */

class AddressAboutService {

    //得到地址总数
    public function getAddressTotal($condition) {
        $fields = 'COUNT(id) total';
        $countInfo = TZ_Loader::model('Address', 'Api')->select($condition, $fields, 'ROW');
        return intval($countInfo['total']);
    }

    //地址信息data
    public function getAddressList($condition) {

        $condition['order'] = 'created_at DESC';
        return TZ_Loader::model('Address', 'Api')->select($condition, '*', 'ALL');
    }

    //地址信息data一条
    public function getAddressInfo($condition) {
        return TZ_Loader::model('Address', 'Api')->select($condition, '*', 'ROW');
    }

    //获取默认地址
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
    public function getDefaultAddress($session_id, $addressId) {



        $sid = !empty($session_id) ? $session_id : "";
        $uid = TZ_Loader::service('SessionManager', 'User')->getUid($sid);
        if (empty($uid)) {
            return "error";
        }
        //$uid = 11;
        //get data
        $arAddress = array("id" => "", "name" => "", "receive_tel" => "", "postcode" => "", "address" => "");
        $condition = array();
        $condition['uid:eq'] = $uid;
        $condition['status:eq'] = 1;
        if (!empty($addressId)) {
            $condition['id:eq'] = $addressId;
            $arAddressInfo_def = $this->getAddressInfo($condition);
        } else {

            //查找该用户使用状态的地址信息总数
            $arAddressTotal = $this->getAddressTotal($condition);
            //如果没有地址信息返回空数组
            if ($arAddressTotal == 0) {
                //var_dump($arAddress);
                return $arAddress;
            }
            $condition['is_default:eq'] = 1;
            //查找该用户的默认地址地址信息
            $arAddressInfo_def = $this->getAddressInfo($condition);
            if (empty($arAddressInfo_def)) {

                //如果没有默认地址,就将最新一条数组查出来
                $condition1 = array();
                $condition1['uid:eq'] = $uid;
                $condition1['status:eq'] = 1;
                $condition1['limit'] = "0,1";
                $condition1['order'] = 'id DESC';

                $arAddressInfo_def = $this->getAddressInfo($condition1);
            }
        }
        if (!empty($arAddressInfo_def)) {
            $arAddress['id'] = $arAddressInfo_def['id'];
            $arAddress['name'] = $arAddressInfo_def['name'];
            $arAddress['receive_tel'] = $arAddressInfo_def['receive_tel'];
            $arAddress['postcode'] = $arAddressInfo_def['postcode'];
            if ($arAddressInfo_def['area'] == 0) {
                $area = "";
            }
            $arAddress['address'] = $arAddressInfo_def['province'] . $arAddressInfo_def['city'] . $area . $arAddressInfo_def['detail'];
        }
        //var_dump($arAddress);
        return $arAddress;
    }

}
