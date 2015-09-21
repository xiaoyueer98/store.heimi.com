<?php
/**
 * goodsinfo controller class
 * @author  nick <zhaozhiwei@747.cn>
 * @final 2014-12-05
 */
class AdvertService
{
    private $redis = '';
    private $redis_advert = 'advert';
    
    public function __construct() {
        $this->redis = TZ_Redis::connect('mall');
    }  
    //获取广告图
    public function getAdvert()
    {
        $is = $this->redis->hExists($this->redis_advert,'banner');
        if($is)
        {
            $banner_info = $this->redis->hGet($this->redis_advert,'banner');
            $banner_info = unserialize($banner_info);
            return $banner_info;
        }
        else
        {
            $conditions = array('status:eq' => 1,'order' => ' `id` DESC');
            $banner_info = TZ_Loader::model('Advert', 'Api')->select($conditions,array('img','src'),'ALL');
            if($banner_info)
            {
                $this->redis->hSet($this->redis_advert,'banner',  serialize($banner_info));
            }
            return $banner_info;
        }
    }
}
