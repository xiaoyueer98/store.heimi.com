<?php
/**
 * goodsinfo controller class
 * @author  nick <zhaozhiwei@747.cn>
 * @final 2014-12-05
 */
class GoodsinfoController extends Yaf_Controller_Abstract
{
    //商品信息接口
    public function indexAction()
    {
        $param          = TZ_Request::getParams('get');
        $goods_id       = isset($param['gid'])  ? $param['gid'] : 0;
        $category_id    = isset($param['cid'])  ? $param['cid'] : 0;
        $page           = isset($param['page']) ? $param['page'] : 1;
        $size           = isset($param['size']) ? $param['size'] : 6;
        $info = array(
            'goods_id'      => $goods_id,
            'category_id'   => $category_id,
            'page'          => $page,
            'size'          => $size
        );
        //获取商品信息
        $goods_info = TZ_Loader::service('Goods','Api')->getGoods($info);
        if(count($goods_info['data']) > 0)
        {
            TZ_Request::success($goods_info['data'],array('pages'=>$goods_info['pages']));
        }
        else
        {
            TZ_Request::error($goods_info);
        }
    }
    //Banana图接口
    public function advertAction()
    {
        //获取广告图
        $ads_info = TZ_Loader::service('Advert','Api')->getAdvert();
        if(count($ads_info) > 0)
        {
            TZ_Request::success($ads_info);
        }
        else
        {
            TZ_Request::error($ads_info);
        }
    }
}
