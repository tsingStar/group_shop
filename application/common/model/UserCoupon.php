<?php
/**
 * 用户优惠券
 * Created by PhpStorm.
 * User: tsing
 * Date: 2019/1/3
 * Time: 9:38
 */

namespace app\common\model;


use think\Exception;
use think\Model;

class UserCoupon extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 校验优惠券合法性
     */
    public function checkCoupon($user_id, $coupon_id, $header_id)
    {
        $c = $this->where("user_id", $user_id)->where("id", $coupon_id)->where("header_id", $header_id)->find();
        if(!$c){
            throw new Exception("优惠券不存在");
        }
        if($c["out_time"] && strtotime($c["out_time"])<time()){
            throw new Exception("优惠券已过期");
        }
        if($c["status"] != 0){
            throw new Exception("优惠券已使用");
        }
        return $c;
    }



}