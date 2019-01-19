<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-27
 * Time: 08:22
 */

namespace app\wapp\controller;


use app\common\model\Group;
use think\Cache;
use think\Controller;
use think\Exception;
use think\Log;

class Interval extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 定时下架商品
     */
    public function downProduct()
    {
        set_time_limit(0);
        $f = fopen(__PUBLIC__ . "/downProduct.lock", "w");
        if (flock($f, LOCK_EX | LOCK_NB)) {
            $list = model("Product")->where("down_time", "neq", "")->where("down_time", "lt", date("Y-m-d H:i:s"))->select();
            try {
                foreach ($list as $item) {
                    $item->save(["is_up" => 0]);
                    model("Product")->setUpDown(0, $item, $item["header_id"]);
                    Cache::rm($item["id"] . ":product");
                }
            } catch (Exception $e) {
                Log::error("商品下架异常：" . $e->getMessage());
            }
            flock($f, LOCK_UN);
            fclose($f);
        }
        exit;
    }

    /**
     * 定时关闭过期订单
     */
    public function closeOrder()
    {
        set_time_limit(0);
        $order_list = model("Order")->where("order_status", 0)->where("create_time", "lt", time() - 15 * 60)->select();
        foreach ($order_list as $order) {
            //处理商品库存及销量信息
            $order_det = model("OrderDet")->where("order_no", $order["order_no"])->select();
            $order->save(["order_status", 2]);
            $user = model("User")->where("id", $order["user_id"])->find();
            foreach ($order_det as $value) {
                //增加商品库存
                model("Product")->addStock($value["product_id"], $value["buy_num"]);
                //减少用户购买量
                model("Product")->addBuyUser($user["open_id"], $value["product_id"], -$p["buy_num"]);
                //减少当期商品销量
                model("Product")->addCurrentSale($value["product_id"], -$value["buy_num"]);
            }
            //返还积分
            if ($order["score"] > 0) {
                model("User")->where("id", $user["id"])->setInc("score", $order["score"]);
                Cache::rm($user["open_id"] . ":userInfo");
            }
            //返还优惠券
            if ($order["coupon_id"] > 0) {
                $coupon = model("UserCoupon")->where("id", $order["coupon_id"])->find();
                if ($coupon["out_time"] != "" && strtotime($coupon["out_time"]) > time()) {
                    $coupon->save(["status" => 0]);
                } else {
                    $coupon->save(["status" => 2]);
                }
            }
        }
    }

    /**
     * 计算团长佣金及城主收入
     */
    public function compCommissionAndSalary()
    {
        set_time_limit(0);
        $f = fopen(__PUBLIC__ . "/comp.lock", "w");
        if (flock($f, LOCK_EX | LOCK_NB)) {
            $list = model("OrderDet")->where("is_comp", 0)->where("status", 3)->where("is_get", 2)->select();
            try {
                $data = [];
                foreach ($list as $item) {
                    //计算佣金状态 及写入佣金日志
                    $total_money = ($item["buy_num"] - $item["back_num"]) * $item["sale_price"];
                    //当前商品佣金
                    $commission = round($total_money * $item['commission'], 2) / 100;
                    $header_money = ($total_money - $commission);
                    $data[] = [
                        "leader_id" => $item["leader_id"],
                        "header_id" => $item["header_id"],
                        "order_det_id" => $item["id"],
                        "commission" => $commission,
                        "header_money" => $header_money,
                        "day" => date("Y-m-d"),
                        "status" => 0
                    ];
                }
                //添加佣金计算记录
                model("CommissionLog")->saveAll($data);

                //计算团长佣金
                $leader_commission = model("CommissionLog")->alias("a")->join("User b", "a.leader_id=b.id")->where("a.day", date("Y-m-d"))->where("a.status", 0)->group("a.leader_id")->field("sum(a.commission) commission_money, a.leader_id, b.amount_able")->select();
                foreach ($leader_commission as $l) {
                    if ($l["commission_money"] > 0) {
                        $commission_money = $l["commission_money"] * (1 - 0.006);
                        //增加佣金记录
                        addCommissionLog($l['leader_id'], 1, $l["amount_able"], $l["commission_money"]);
                        //增加微信手续费
                        addCommissionLog($l['leader_id'], 4, $l["amount_able"] + $l["commission_money"], $l["commission_money"] * 0.006);
                        //增加团长可提现金额
                        db()->execute("update ts_user set amount_able=amount_able+" . $commission_money . ", amount=amount+" . $commission_money . " where id=" . $l["leader_id"]);
                    }
                }
                //计算城主昨日金额
                $header_data = model("CommissionLog")->alias("a")->join("Header b", "a.header_id=b.id")->where("a.day", date("Y-m-d"))->where("a.status", 0)->group("a.header_id")->field("sum(a.header_money) header_money, a.header_id, b.amount_able")->select();
                foreach ($header_data as $h) {
                    if ($h["header_money"] > 0) {
                        $temp = $h["header_money"];
                        //增加城主金额记录
                        addHeaderMoneyLog($h["header_id"], 1, $h['amount_able'], $temp);
                        $temp += $h["amount_able"];
                        //减少积分和优惠券昨日消耗
                        $coupon_fee = model("Order")->where("create_time", "gt", strtotime(date("Y-m-d")) - 86400)->where("create_time", "lt", strtotime(date("Y-m-d")))->sum("coupon_money+score_money");
                        //积分优惠券抵扣
                        addHeaderMoneyLog($h["header_id"], 6, $temp, $coupon_fee);
                        $temp -= $coupon_fee;
                        //增加微信手续费
                        addHeaderMoneyLog($h["header_id"], 4, $temp, ($h['header_money'] - $coupon_fee) * 0.006);
                        $temp -= ($h['header_money'] - $coupon_fee) * 0.006;
                        //红包消耗
                        $red_pack = model("UserRedPack")->where("header_id", $h['header_id'])->where("status", 1)->sum("red_pack");
                        addHeaderMoneyLog($h["header_id"], 7, $temp, $red_pack);
//                        $temp -= $red_pack;
                        //增加城主金额
                        $h_money = $h["header_money"] - $coupon_fee - ($h['header_money'] - $coupon_fee) * 0.006 - $red_pack;
                        db()->execute("update ts_header set amount=amount+" . $h_money . ", amount_able=amount_able+" . $h_money . " where id=" . $h["header_id"]);
                    }
                }
                //处理商品佣金日志状态
                model("CommissionLog")->save(["status" => 1], ["day" => date("Y-m-d")]);
                //处理商品佣金状态
                model("OrderDet")->save(["is_comp" => 1], ["is_comp" => 0, "status" => 3, "is_get" => 2]);
            } catch (Exception $e) {
                Log::error("结算佣金异常：" . $e->getMessage());
            }
            flock($f, LOCK_UN);
            fclose($f);
        }
        exit;
    }


}

























































