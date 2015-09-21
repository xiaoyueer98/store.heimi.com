<?php
/**
 * goodsinfo controller class
 * @author  nick <zhaozhiwei@747.cn>
 * @final 2014-12-05
 */
class GoodsService
{
    private $redis = '';
    private $redis_cname = '';
    private $redis_goods = 'goods_info';
    
    public function __construct() {
        $this->redis = TZ_Redis::connect('mall');
    }
    /* 
     * 获取商品信息
     * 1.分页分类获取（分类ID=0所有分类）
     * 2.根据商品ID获取
     * 分类商品分页获取 价格默认为-1
     */
    public function getGoods($info)
    {
         /* 指定商品ID获取 */
        if($info['goods_id'])
        {
            $goods_ids = array($info['goods_id']);
            //根据商品ID获取商品信息
            $goods_list = $this->getGoodsInfo($goods_ids);
            return array('data'=>$goods_list,'pages'=>1);
        }
         /* 分类模式获取 */
        else
        {
            //分类下商品ID列表
            $category = $this->getCategory($info);
            //计算分页
            $count = $this->redis->hGet($this->redis_cname,'count'); //总条数
            $pages = ceil($count/$info['size']);
            if($pages < $info['page'])
            {
                $info['page'] = 1;
            }
            $start = ($info['page']-1)*$info['size']+1;
            //获取本页商品ID
            $goods_ids = array_slice($category,$start,$info['size']);
            //根据商品ID获取商品信息
            $goods_list = $this->getGoodsInfo($goods_ids);
            return array('data'=>$goods_list,'pages'=>$pages);
        }
    }
    //获取某分类下的商品ID列表
    public function getCategory($info)
    {
        $this->redis_cname = 'category_'.$info['category_id'];
        $category_count = $this->redis->hExists($this->redis_cname,'count');
        if($category_count == false)
        {
            $conditions = array('status:eq' => 1,'order' => ' `goods_id` DESC');
            if($info['category_id'] > 0)
            {
                $conditions['category_id:eq'] = $info['category_id'];
            }
            $list = TZ_Loader::model('Goods', 'Api')->select($conditions,array('goods_id','category_id'),'ALL');
            if($list)
            {
                $count = count($list); //总数
                $this->redis->hSet($this->redis_cname, 'count', $count);
                foreach($list AS $val)
                {
                    $this->redis->hSet($this->redis_cname, $val['goods_id'], $val['category_id']);
                }
            }
        }
        return $this->redis->hKeys($this->redis_cname);
    }
    //根据商品ID获取商品信息
    public function getGoodsInfo($goods_ids)
    {
        $goods_info = array();
        if(is_array($goods_ids))
        {
            foreach($goods_ids AS $val)
            {
                $is = $this->redis->hExists($this->redis_goods,$val);
                if($is)
                {
                    $g_info = $this->redis->hGet($this->redis_goods,$val);
                    $g_info = unserialize($g_info);
                    $goods_info[] = $g_info;
                }
                else
                {
                    $conditions = array('status:eq' => 1,'goods_id:eq' => $val);
                    $g_info = TZ_Loader::model('Goods', 'Api')->select($conditions,'*','ROW');
                    $goods_info[] = $g_info;
                    $g_info = serialize($g_info);
                    $this->redis->hSet($this->redis_goods,$val,$g_info);
                }
            }
        }
        return $goods_info;
    }
}
