<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2019/1/10
 * Time: 10:52
 */

namespace app\header\controller;


class Coupon extends ShopBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 优惠券列表
     */
    public function index()
    {
        $list = model("Coupon")->where("header_id", HEADER_ID)->paginate(10);
        foreach ($list as $item) {
            $item["spread_num"] = model("UserCoupon")->where("coupon_id", $item['id'])->count();
            $item["use_num"] = model("UserCoupon")->where("coupon_id", $item['id'])->where("status", 1)->count();
        }
        $totalNum = model("Coupon")->where("header_id", HEADER_ID)->count();
        $this->assign("list", $list);
        $this->assign("totalNum", $totalNum);
        return $this->fetch();
    }

    /**
     * 添加购物券
     */
    public function addCoupon()
    {
        if (request()->isAjax()) {
            $data = input("post.");
            $data["header_id"] = HEADER_ID;
            $res = model("Coupon")->save($data);
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, "添加失败");
            }
        }
        return $this->fetch();
    }

    public function getUserList()
    {
        $name = input("name");
        $list = model("User")->whereLike("user_name", "%$name%")->field("id user_id, user_name")->select();
        exit_json(1, "", $list);
    }

    /**
     * 删除优惠券
     */
    public function delCoupon()
    {
        $coupon_id = input("id");
        $coupon = model("Coupon")->where("header_id", HEADER_ID)->where("id", $coupon_id)->find();
        if(!$coupon){
            exit_json(-1, "订单不存在");
        }else{
            $r = model("UserCoupon")->where("coupon_id", $coupon_id)->where("status", 1)->find();
            if($r){
                exit_json(-1, "优惠券已有使用，不可删除");
            }else{
                $res = $coupon->delete();
                if($res){
                    exit_json();
                }else{
                    exit_json(-1, "删除失败");
                }
            }
        }
    }

    /**
     * 发放优惠券
     */
    public function delivery()
    {
        $coupon_id = input("coupon_id");

        if(request()->isAjax()){
            $coupon = model("Coupon")->where("header_id", HEADER_ID)->where("id", $coupon_id)->find();
            if(!$coupon){
                exit_json(-1, "优惠券不存在");
            }
            $type = input("type");
            if($type == 0){
                $user_id_arr = model("User")->column("id user_id");
            }else{
                $user_id_str = input("user_id");
                $user_id_arr = explode(",", $user_id_str);
            }
            $data = [];
            foreach ($user_id_arr as $item){
                $t = [
                    "user_id"=>$item,
                    "header_id"=>HEADER_ID,
                    "coupon_id"=>$coupon_id,
                    "limit_money"=>$coupon["limit_money"],
                    "coupon_money"=>$coupon["coupon_money"],
                    "out_time"=>$coupon["out_time"]
                ];
                $data[] = $t;
            }
            $res = model("UserCoupon")->saveAll($data);
            if($res){
                exit_json();
            }else{
                exit_json(-1, "发放失败");
            }

        }

        return $this->fetch();
    }


}