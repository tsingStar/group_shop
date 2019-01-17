<?php
/**
 * 商品模型
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-25
 * Time: 13:55
 */

namespace app\common\model;


use think\Cache;
use think\Log;
use think\Model;

class Product extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * @param int $pid 商品id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getProductInfo($pid)
    {
        if (!Cache::has($pid . ":product")) {
            $data = $this->alias("a")->join("Cate b", "a.cate_id=b.id")->where("a.id", $pid)->field("a.*, b.cate_name")->find()->getData();
            $data['swiper'] = \model("ProductSwiper")->getSwiper($pid);
            $data["current_sell"] = $this->getCurrentSale($pid);
            Cache::set($pid . ":product", serialize($data));
        }
        return unserialize(Cache::get($pid . ":product"));
    }

    /**
     * 更改商品限量库存及保存商品销售属性设置日志
     * @param int $id 商品id
     * @param float $org_limit 原始限量
     * @param float $current_limit 修改后限量
     * @param [] $data 销售属性
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function modifyLimitStock($id, $org_limit, $current_limit, $data)
    {
        $redis = new \Redis2();
        if ($org_limit != $current_limit) {
            if ($org_limit == '') {
                //初始为不限量设置
                for ($i = 0; $i < $current_limit - model("Product")->getCurrentSale($id); $i++) {
                    $redis->lpush($id . ":stock", 1);
                }
            } else {
                //初始限量
                if ($current_limit == "") {
                    //改为不限量
                    $redis->lremAll($id . ":stock");
                } else {
                    //初始小于调整
                    if ($org_limit > $current_limit) {
                        $limit = $org_limit - $current_limit;
                        for ($i = 0; $i < $limit; $i++) {
                            $redis->lpop($id . ":stock");
                        }
                    }
                    //初始大于调整
                    if ($org_limit < $current_limit) {
                        $limit = $current_limit - $org_limit;
                        for ($i = 0; $i < $limit; $i++) {
                            $redis->lpush($id . ":stock", 1);
                        }
                    }
                }
            }
        }
        $data['product_id'] = $id;
        $sa = model("ProductSaleAttrLog")->where($data)->find();
        if (!$sa) {
            model("ProductSaleAttrLog")->save($data);
        }
        return true;
    }

    /**
     * 获取商品限量剩余
     */
    public function getStock($pid)
    {
        $redis = new \Redis2();
        return $redis->llen($pid . ":stock");
    }

    /**
     * 删除商品库存
     */
    public function delStock($pid, $num)
    {
        $redis = new \Redis2();
        $t = 0;
        for ($i = 0; $i < $num; $i++) {
            $r = $redis->lpop($pid . ":stock");
            if ($r === false) {
                break;
            }
            $t = $i + 1;
        }
        return $t;
    }

    /**
     * 增加商品库存限量
     */
    public function addStock($pid, $num)
    {
        $redis = new \Redis2();
        $product = $this->getProductInfo($pid);
        if ($product["limit"] != "") {
            for ($i = 0; $i < $num; $i++) {
                $redis->lpush($pid.":stock", 1);
            }
        }
        return true;
    }

    /**
     * 商品销量变动
     */
    public function addCurrentSale($pid, $num)
    {
        $redis = new \Redis2();
        return $redis->incr($pid . ":product_current_sale", $num);
    }

    /**
     * 获取当前商品销量
     */
    public function getCurrentSale($pid)
    {
        $redis = new \Redis2();
        return intval($redis->get($pid . ":product_current_sale"));
    }

    /**
     * 清空当前商品销量
     */
    public function clearCurrentSale($pid)
    {
        $redis = new \Redis2();
        return $redis->set($pid . ":product_current_sale", 0);
    }

    /**
     * 商品上下架
     */
    public function setUpDown($type, $product, $header_id)
    {
        $redis = new \Redis2();
        if ($type == 0) {
            $redis->zrem($header_id . ":product_cate:" . $product["cate_id"], $product["id"]);
            if ($product["is_hot"]) {
                $redis->zrem($header_id . ":product_hot", $product["id"]);
            }
            if ($product["is_sec"]) {
                $redis->zrem($header_id . ":product_sec", $product["id"]);
            }
        } else {
            $redis->zadd($header_id . ":product_cate:" . $product["cate_id"], $product["ord"], $product["id"]);
            if ($product["is_hot"]) {
                $redis->zadd($header_id . ":product_hot", $product["ord"], $product["id"]);
            } else {
                $redis->zrem($header_id . ":product_hot", $product["id"]);
            }
            if ($product["is_sec"]) {
                $redis->zadd($header_id . ":product_sec", $product["ord"], $product["id"]);
            } else {
                $redis->zrem($header_id . ":product_sec", $product["id"]);
            }
            if ($product["limit"] != "") {
                $remain = $product["limit"] - $this->getStock($product["id"]) - $this->getCurrentSale($product["id"]);
                if ($remain > 0) {
                    for ($i = 0; $i < $remain; $i++) {
                        $redis->lpush($product["id"] . ":stock", 1);
                    }
                } else {
                    for ($i = 0; $i < -$remain; $i++) {
                        $redis->lpop($product["id"] . ":stock");
                    }
                }
            }
        }
        return true;
    }

    /**
     * 用户购买数量处理
     * 取消订单时 buy_num为负值
     */
    public function addBuyUser($open_id, $product_id, $buy_num)
    {
        $redis = new \Redis2();
        $num = $this->getBuyUser($open_id, $product_id) + $buy_num;
        $redis->hset($product_id . ":buy_user", $open_id, $num);
        return;
    }

    public function getBuyUser($open_id, $product_id)
    {
        $redis = new \Redis2();
        $num = $redis->hget($product_id . ":buy_user", $open_id);
        return $num;

    }


}