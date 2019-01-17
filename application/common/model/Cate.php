<?php
/**
 * 商品分类表
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/11/22
 * Time: 14:49
 */

namespace app\common\model;


use think\Cache;
use think\Model;

class Cate extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 根据城主id获取当前城主下的分类
     */
    public function getCateList($header_id)
    {
        if(!Cache::has($header_id.":cate")){
            $list = $this->where("header_id", $header_id)->field("id, cate_name")->order("ord")->select();
            Cache::set($header_id.":cate", $list);
        }
        return Cache::get($header_id.":cate");
    }
    

}