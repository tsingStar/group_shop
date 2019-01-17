<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-10
 * Time: 10:26
 */

namespace app\common\model;


use think\Model;

class OrderDet extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取订单详情
     */
    public function getOrderDetail($order_no)
    {
        $list = $this->where("order_no", $order_no)->field("id order_det_id, header_id, leader_id, user_id, product_id, order_no, product_name, buy_num, market_price, sale_price, unit, attr, one_num, status")->select();
        foreach ($list as $item) {
            $item["swiper"] = \model("ProductSwiper")->getSwiper($item['product_id']);
        }
        return $list;
    }



}