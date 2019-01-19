<?php
/**
 * 团长控制器
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-28
 * Time: 15:12
 */

namespace app\wapp\controller;


use app\common\model\SendSms;
use app\common\model\WeiXinPay;
use think\Controller;
use think\Exception;

class Leader extends Controller
{
    private $leader;

    protected function _initialize()
    {
        parent::_initialize();
        $open_id = input("open_id");
        $this->leader = model("User")->where("open_id", $open_id)->find();
        if (!$this->leader) {
            exit_json(-1, '团长不存在');
        }
        if ($this->leader['role_status'] != 2) {
            exit_json(-1, '抱歉，你还不是团长');
        }
    }

    /**
     * 获取产品分类
     */
    public function getCateList()
    {
        $header_id = $this->leader["header_id"];
        $list = model("Cate")->where("header_id", $header_id)->field("id cate_id, cate_name")->select();
        exit_json(1, "请求成功", $list);
    }

    /**
     * 获取指定分类下商品
     */
    public function getProductList()
    {
        $cate_id = input("cate_id");
        $page = input("page");
        $page_num = input("page_num");
        if ($cate_id == "hot") {
            $list = model("Product")->where("is_hot", 1)->where("is_up", 1)->limit($page_num * $page, $page_num)->select();
        } elseif ($cate_id == "sec") {
            $list = model("Product")->where("is_sec", 1)->where("is_up", 1)->limit($page_num * $page, $page_num)->select();
        } else {
            $list = model("Product")->where("cate_id", $cate_id)->where("is_up", 1)->limit($page_num * $page, $page_num)->select();
        }
        $data = [];
        foreach ($list as $item) {
            $temp = [
                "product_id" => $item["id"],
                "product_name" => $item["product_name"],
                "unit" => $item["unit"],
                "attr" => $item["attr"],
                "one_num" => $item["one_num"],
                "total_sell" => $item["total_sell"],
                "market_price" => $item["market_price"],
                "sale_price" => $item["sale_price"],
                "swiper" => model("ProductSwiper")->getSwiper($item["id"])
            ];
            $data[] = $temp;
        }
        exit_json(1, "请求成功", $data);
    }

    /**
     * 获取待配送，待收获，待自提订单商品
     */
    public function getOrderProduct()
    {
        $page = input("page");
        $page_num = input("page_num");
        //商品状态  0 待配送 1 待收货 2 待自提 3 已完成
        $status = input("status");
        $model = model("OrderDet")->alias("a");
        if ($status == 0) {
            $model->where("a.status", 1)->where("a.is_get", 0);
        }
        if ($status == 1) {
            $model->where("a.status", 1)->where("a.is_get", 1);
        }
        if ($status == 2) {
            $model->where("a.status", 2)->where("a.is_get", 2);
        }
        if ($status == 3) {
            $model->where("a.status", 3)->where("a.is_get", 2);
        }
        $list = $model->where("a.leader_id", $this->leader["id"])->join("User b", "a.user_id=b.id")->join("Order c", "c.order_no=a.order_no")->field("a.id order_det_id, b.user_name, a.product_id, a.order_no, a.product_name, a.buy_num, a.market_price, a.sale_price, a.one_num, c.telephone")->order("a.create_time desc")->limit($page * $page_num, $page_num)->select();
        $data = [];
        foreach ($list as $item) {
            $item["swiper"] = model("ProductSwiper")->getSwiper($item['product_id']);
            $item["status"] = $status;
            $data[] = $item;
        }
        exit_json(1, "请求成功", $data);

    }

    /**
     * 确定收获
     */
    public function sureProduct()
    {
        $order_det_id = input("order_det_id");
        $product = model("OrderDet")->where("id", $order_det_id)->where("leader_id", $this->leader['id'])->find();
        if (!$product) {
            exit_json(-1, "订单信息不存在");
        }
        $res = $product->save(["is_get" => 2, "status" => 2]);
        if ($res) {

            //自动短信通知
            $order = model("Order")->where("order_no", $product["order_no"])->find();
            $phone = [
                $order["telephone"]
            ];
            $product_name = $product["product_name"] . " " . $product["attr"] . " " . $product["one_num"] . $product['unit'] . "/份";
            $param = [
                $product_name
            ];

            $sms = new SendSms($phone, "9734123");
            $sms->sendTemplate($param);
            //短信通知结束

            exit_json();
        } else {
            exit_json(-1, "处理失败，请刷新后重试");
        }
    }

    /**
     * 发送短信通知
     * 暂时废弃
     */
    public function sendSms()
    {
        $order_det_id = input("order_det_id");
        $product = model("OrderDet")->where("leader_id", $this->leader['id'])->where("id", $order_det_id)->find();
        if (!$product) {
            exit_json(-1, "订单不存在");
        }
        $order = model("Order")->where("order_no", $product["order_no"])->find();
        $phone = [
            $order["telephone"]
        ];
        $product_name = $product["product_name"] . " " . $product["attr"] . " " . $product["one_num"] . $product['unit'] . "/份";
        $param = [
            $product_name
        ];

        $sms = new SendSms($phone, "9734123");
        $res = $sms->sendTemplate($param);
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, $sms->getError());
        }
    }


    /**
     * 商品申请退款
     */
    public function applyRefund()
    {
        $order_no = input('order_no');
        $order_det_id = input('order_det_id');
        $num = input('num');
        $reason = input('reason');
        $order_det = model("OrderDet")->where('order_no', $order_no)->where('id', $order_det_id)->find();
        $order = model('Order')->where('order_no', $order_no)->find();
        if ($order_det) {
            $refund_num = model('OrderRefund')->where("order_no", $order_no)->where("order_det_id", $order_det_id)->sum("num");
            if ($num > $order_det['buy_num'] - $refund_num) {
                exit_json(-1, "退货申请数量不应大于订单商品总数量");
            }

            $refund_money = round($order_det["sale_price"] * $num * $order["pay_money"] / $order["order_money"], 2);
            $res = model('OrderRefund')->save([
                "order_det_id" => $order_det_id,
                "leader_id" => $order_det['leader_id'],
                "header_id" => $order_det['header_id'],
                "user_id" => $order_det["user_id"],
                "product_id" => $order_det['product_id'],
                "order_no" => $order_no,
                "num" => $num,
                "product_name" => $order_det['product_name'],
                "market_price" => $order_det['market_price'],
                "sale_price" => $order_det['sale_price'],
                "refund_money" => $refund_money,
                "reason" => $reason,
                "refund_no" => getOrderNo()
            ]);
            if ($res) {
                exit_json(1, "申请成功");
            } else {
                exit_json(-1, '申请失败，刷新后重试');
            }
        } else {
            exit_json(-1, "商品不存在");
        }
    }

    /**
     * 获取订单详情商品信息
     */
    public function getOrderDetProduct()
    {
        $order_det_id = input("order_det_id");
        $item = model("OrderDet")->alias("a")->join("User b", "a.user_id=b.id")->where("a.id", $order_det_id)->field("a.order_no, a.id order_det_id, a.product_name, a.product_id, a.unit, a.attr, a.one_num, a.market_price, a.sale_price, (a.buy_num-a.back_num) num, b.user_name")->find();
        $order = model("Order")->where("order_no", $item["order_no"])->find();
        $refund_money = round($item["sale_price"] * $item["num"] * $order["pay_money"] / $order["order_money"], 2);
        $item["refund_money"] = $refund_money;
        $item["swiper"] = model("ProductSwiper")->getSwiper($item["product_id"]);
        exit_json(1, "请求成功", $item);

    }

    /**
     * 提现
     */
    public function withDraw()
    {
        $f = fopen(__PUBLIC__ . "/withdraw.lock", "w");
        if (flock($f, LOCK_EX | LOCK_NB)) {
            $leader = model("User")->where("id", $this->leader["id"])->find();
            if (!$leader) {
                exit_json(-1, '团长信息错误');
            }
            $amount = input("money");
            if (!is_numeric($amount) || !$amount || $amount < 10) {
                exit_json(-1, "提现金额不合法");
            }
            if ($amount > $leader["amount_able"]) {
                exit_json(-1, "可提现余额不足");
            }
            $fee = 0;
            $money = $amount - $fee;
            $order_no = getOrderNo();
            model("WithdrawLog")->save([
                "role" => 2,
                "user_id" => $this->leader['id'],
                "amount" => $amount,
                "fee" => $fee,
                "money" => $money,
                "status" => 0,
                "order_no" => $order_no
            ]);
            $withdraw_id = model("WithdrawLog")->getLastInsID();
            $log_model = model("WithdrawLog")->where("id", $withdraw_id)->find();
            $weixin = new WeiXinPay();
            $res = $weixin->withdraw([
                "open_id" => $leader["open_id"],
                "amount" => $money,
                "check_name" => "NO_CHECK",
                "desc" => "佣金提现",
                "order_no" => $order_no
            ]);
//            $res = [
//                "code" => 1,
//                "result" => [
//                    "payment_time" => "2019-01-15 12:25:89"
//                ]
//            ];
            if ($res["code"] == 1) {
                $result = $res["result"];
                //更改申请状态
                $log_model->save(["withdraw_time" => $result["payment_time"], "status" => 1]);
                //减少可提现金额
                $leader->setDec("amount_able", $amount);
                //增加提现总金额
                $leader->setInc("withdraw", $amount);
                addCommissionLog($this->leader["id"], 2, $this->leader["amount_able"], $amount, $order_no);
                $code = 1;
                $msg = "提现成功";
            } else {
                $log_model->save(["status" => 2, "reason" => $res['reason']]);
                $code = -1;
                $msg = "提现操作失败";
            }
            flock($f, LOCK_UN);
            fclose($f);
            exit_json($code, $msg);
        } else {
            exit_json(-1, "系统繁忙");
        }
    }

    /**
     * 获取团长基本信息
     */
    public function getLeaderInfo()
    {
        $leader_money = model("LeaderMoneyLog")->where("leader_id", $this->leader["id"])->where("type", 1)->where("create_time", "gt", strtotime(date("Y-m-d")))->find();
        $today_money = model("OrderDet")->where("leader_id", $this->leader['id'])->where("status", 1)->where("create_time", "gt", strtotime(date("Y-m-d")))->sum("(buy_num-back_num)*commission*sale_price/100");
        $month_money = model("OrderDet")->where("leader_id", $this->leader['id'])->where("status", 1)->where("create_time", "gt", strtotime(date("Y-m")))->sum("(buy_num-back_num)*commission*sale_price/100");
        $order_num = model("Order")->where("create_time", "gt", strtotime(date("Y-m-d")))->where("leader_id", $this->leader['id'])->where('order_status', 1)->count();
        $today_money = is_null($today_money)?0:$today_money;
        $month_money = is_null($month_money)?0:$month_money;
        $data = [
            "amount" => $this->leader["amount"],
            "amount_able" => $this->leader["amount_able"],
            "yesterday_money" => $leader_money["money"]?:0,
            "today_money" => $today_money * (1 - 0.006),
            "month_money" => $month_money * (1 - 0.006),
            "order_num" => $order_num
        ];
        exit_json(1, "请求成功", $data);
    }

    /**
     * 获取佣金明细
     */
    public function getCommissionList()
    {
        $page = input("page");
        $page_num = input("page_num");
        $list = model("LeaderMoneyLog")->where("leader_id", $this->leader['id'])->where("type", 1)->limit($page * $page_num, $page_num)->order("create_time desc")->select();
        exit_json(1, "请求成功", $list);
    }

    /**
     * 获取余额明细
     */
    public function getAmountList()
    {
        $page = input("page");
        $page_num = input("page_num");
        $list = model("LeaderMoneyLog")->where("leader_id", $this->leader["id"])->limit($page_num * $page, $page_num)->order("create_time desc")->select();
        exit_json(1, "请求成功", $list);
    }

    /**
     * 获取商品详情
     */
    public function getProductInfo()
    {
        $product_id = input("product_id");
        $product = model("Product")->where("id", $product_id)->field("product_name, id product_id, market_price, sale_price, header_id")->find();
        $product["swiper"] = model("ProductSwiper")->getSwiper($product_id);
        $product["leader_id"] = $this->leader["id"];
        exit_json(1, "请求成功", $product);
    }

    /**
     * 获取退款记录
     */
    public function getRefundList()
    {
        $page = input("page");
        $page_num = input("page_num");
        $list = model("OrderRefund")->alias("a")->join("User b", "a.user_id=b.id", "left")->where("a.leader_id", $this->leader["id"])->order("a.create_time desc")->field("b.user_name, a.id refund_id, a.product_id, a.product_name, a.order_no, a.order_det_id, a.num, a.market_price,a.sale_price,a.refund_money,a.reason, a.status, a.refuse_reason, a.create_time")->limit($page_num * $page, $page_num)->select();
        foreach ($list as $item) {
            $item["swiper"] = model("ProductSwiper")->getSwiper($item["product_id"]);
        }
        exit_json(1, "请求成功", $list);
    }

    /**
     * 获取用户待提产品
     */
    public function getUserProduct()
    {
        $uuid = input("uuid");
        $user = model("User")->where("open_id", $uuid)->find();
        $list = model("OrderDet")->alias("a")->join("User b", "a.user_id=b.id")->where("a.leader_id", $this->leader["id"])->where("a.user_id", $user["id"])->where("a.is_get", 2)->where("a.is_match", 1)->where("a.status", 2)->field("b.user_name, a.id order_det_id, a.product_name, a.unit, a.attr, a.one_num, (a.buy_num-a.back_num) buy_num, a.market_price, a.sale_price, a.product_id")->select();
        foreach ($list as $item){
            $item["swiper"] = model("ProductSwiper")->getSwiper($item["product_id"]);
        }
        exit_json(1, "请求成功", $list);
    }

    /**
     * 确认提货
     */
    public function getProduct()
    {
        $order_det_id = input("order_det_id");
        $order_det = model("OrderDet")->where("leader_id", $this->leader["id"])->where("id", $order_det_id)->where("status", 2)->find();
        if (!$order_det) {
            exit_json(-1, "待确认提货商品不存在");
        }
        $res = $order_det->save(["status" => 3]);
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, "操作失败");
        }
        
    }





///TODO 待完善

    /**
     * 获取退款详情
     */
    public function getRefundDetail()
    {
        $refund_id = input('id');
        $refund = model("OrderRefund")->where("id", $refund_id)->find();
        if (!$refund) {
            exit_json(-1, '退款记录不存在');
        } else {
            $data = [];
            //订单详情
            $order = model("Order")->where("order_no", $refund["order_no"])->find();
            $order_detail = [
                "order_no" => $order["order_no"],
                "user_name" => $order["user_name"],
                "user_telephone" => $order["user_telephone"],
                "create_time" => $order["create_time"]
            ];
            $data["order_detail"] = $order_detail;
            //团购详情
            $group = model("Group")->alias("a")->join("User b", "a.leader_id=b.id")->where("a.id", $refund["group_id"])->field("a.*, b.user_name, b.telephone")->find();
            $group_detail = [
                "title" => $group["title"],
                "user_name" => $group["user_name"],
                "telephone" => $group["telephone"],
                "remarks" => $refund["reason"],
                "refuse_reason" => $refund["refuse_reason"]
            ];
            $data["group_detail"] = $group_detail;
            $product = model("GroupProduct")->where("id", $refund["product_id"])->find();
            $data["sale_amount"] = $refund["num"];
            $data["group_price"] = $refund["group_price"];
            $data["commission_rate"] = $product["commission"];
            $data["product_name"] = $product["product_name"];
            $data["status"] = $refund["status"];
            exit_json(1, '请求成功', $data);
        }
    }

    /**
     * 获取海报
     */
    public function drawImage()
    {
        //663 664 665 666 45
        $group_id = input("group_id");
        $product_id = input("product_id");
        $leader_id = input("leader_id");
        if (file_exists(__UPLOAD__ . "/erweima/" . $group_id . "-" . $product_id . ".png")) {
            exit_json(1, "请求成功", ["imgUrl" => "https://" . __URI__ . "/upload" . "/erweima/" . $group_id . "-" . $product_id . ".png"]);
        }
        $group = model("Group")->where("id", $group_id)->find();
        $product = model("HeaderGroupProduct")->where("id", $product_id)->find();
        $product["image"] = model("ProductSwiper")->where("product_id", $product["base_id"])->order("type")->value("url");
        $leader = model("User")->where("id", $leader_id)->field("user_name name, avatar header_image")->find();
        try {
            if ($product["attr"] != "") {
                $product["product_name"] .= $product["attr"] . "*";
            }
            $product["product_name"] .= $product["num"] . $product["unit"] . "/份";
            $image_url = \ImageDraw::drawImage($product, $leader, $group);
            exit_json(1, "请求成功", ["imgUrl" => $image_url]);
        } catch (\Exception $e) {
            exit_json(-1, "海报生成失败");
        }
    }

    /**
     * 获取预热图
     */
    public function getReadyImage()
    {
        $image_list = model("PreImage")->where("header_id", $this->leader["header_id"])->column("image_url");
        exit_json(1, "请求成功", ["image_list" => $image_list]);
    }


}