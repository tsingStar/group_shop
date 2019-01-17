<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/12/28
 * Time: 16:37
 */

namespace app\common\model;


class Cart
{
    private $key;
    private $redis;
    public function __construct($open_id, $leader_id)
    {
        $this->redis = new \Redis2();
        $this->key = $open_id.":".$leader_id.":cart";
    }

    /**
     * @return array
     */
    public function getCart()
    {
        return $this->redis->hgetAll($this->key);
    }

    /**
     * 获取指定域下的值
     */
    public function getCartField($field)
    {
        return intval($this->redis->hget($this->key, $field));

    }

    /**
     * 加入购物车
     */
    public function addCart($product_id, $num)
    {
        return $this->redis->hset($this->key, $product_id, $num);
    }

    /**
     * 删除购物车商品
     */
    public function delCart($product_id)
    {
        return $this->redis->hdel($this->key, $product_id);
    }




}