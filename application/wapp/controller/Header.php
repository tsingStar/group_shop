<?php
/**
 * 城主管理
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-28
 * Time: 15:11
 */

namespace app\wapp\controller;


use app\common\model\ApplyLeaderRecord;
use app\common\model\WeiXinPay;
use think\Cache;
use think\Controller;
use think\Exception;
use think\Log;

class Header extends Controller
{
    private $header;

    protected function _initialize()
    {
        parent::_initialize();
        $open_id = input("open_id");
        $wx = model("Waccount")->where("open_id", $open_id)->find();
        if (!$wx) {
            exit_json(-1, "您还不是城主");
        }
        $header = model("Header")->where("id", $wx["header_id"])->find();
        if (!$header) {
            exit_json(-1, '城主不存在');
        }
        $this->header = $header;
    }

    /**
     * 获取当前在售商品信息
     */
    public function getCurrentProduct()
    {
        $page = input("page");
        $page_num = input("page_num");
        $list = model("Product")->where("header_id", $this->header["id"])->where("is_up", 1)->limit($page * $page_num, $page_num)->select();
        foreach ($list as $value) {
            $value["current_sell"] = model("Product")->getCurrentSale($value["id"]);
            $value["swiper"] = model("ProductSwiper")->getSwiper($value["id"]);
        }
        exit_json(1, "请求成功", $list);
    }

    /**
     * 商品下架
     */
    public function setUpDown()
    {
        $pid = input("pid");
        $type = input("type");
        $product = model("Product")->where("id", $pid)->find();
        if ($product->save(["is_up" => $type])) {
            model("Product")->setUpDown($type, $product, $this->header["id"]);
            exit_json();
        } else {
            exit_json(-1, "操作失败");
        }
    }

    /**
     * 获取当前待配送商品
     */
    public function getDispatchProduct()
    {
        $page = input("page");
        $page_num = input("page_num");
        $list = model("OrderDet")->alias("a")->join("Product b", "a.product_id=b.id")->where("a.status", 1)->where("a.is_get", 0)->where("a.header_id", $this->header["id"])->where("a.is_match", 1)->field("a.*, b.stock")->group("a.product_id")->limit($page * $page_num, $page_num)->select();
        $data = [];
        foreach ($list as $item) {
            //获取商品销量
            $sell_num = model("OrderDet")->where("status", 1)->where("is_get", 0)->where("is_match", 1)->where("product_id", $item["product_id"])->sum('buy_num-back_num');
            $purchase_num = $item["one_num"] * $sell_num;
            if ($item["stock"] > 0) {
                $purchase_num = $purchase_num - $item['stock'];
            }
            $t = [
                "product_id" => $item["product_id"],
                "product_name" => $item["product_name"],
                "unit" => $item['unit'],
                "attr" => $item["attr"],
                "one_num" => $item["one_num"],
                "swiper" => model("ProductSwiper")->getSwiper($item["product_id"]),
                "stock" => $item["stock"],
                "sell_num" => $sell_num,
                "purchase_num" => $purchase_num . $item['unit']
            ];
            $data[] = $t;
        }
        exit_json(1, "请求成功", $data);
    }

    /**
     * 配送商品，清空当前销售数量
     */
    public function dispatchProduct()
    {
        $pid = input("product_id");
        $dispatch_num = model("OrderDet")->where("product_id", $pid)->where("is_get", 0)->where("is_match", 1)->count();
        if ($dispatch_num == 0) {
            exit_json(-1, "暂无需要配送订单,刷新后重试");
        }
        $res = model("OrderDet")->save(['is_get' => 1], ["is_get" => 0, "product_id" => $pid, "is_match" => 1]);
        if ($res) {
            //当前销量处理   个人限购处理
            model("Product")->clearCurrentSale($pid);
            $redis = new \Redis2();
            $redis->delKey($pid . ":buy_user");
            exit_json(1, "操作成功");
        } else {
            exit_json(-1, "商品配送处理失败");
        }
    }

    /**
     * 获取分类信息
     */
    public function getCateList()
    {
        $list = model("Cate")->where("header_id", $this->header["id"])->field("id, cate_name")->select();
        $list[] = [
            "id" => 0,
            "cate_name" => "未分类"
        ];
        exit_json(1, "请求成功", $list);
    }

    /**
     * 获取产品库列表
     */
    public function getProductList()
    {
        $page = input("page");
        $page_num = input("page_num");
        $cate_id = input("cate_id");
        $list = model("Product")->where("header_id", $this->header["id"])->where("cate_id", $cate_id)->limit($page * $page_num, $page_num)->select();
        foreach ($list as $value) {
            $value["current_sell"] = model("Product")->getCurrentSale($value["id"]);
            $value["swiper"] = model("ProductSwiper")->getSwiper($value["id"]);
        }
        exit_json(1, "请求成功", $list);
    }

    /**
     * 获取商品详情
     */
    public function getProductDetail()
    {
        $pid = input("pid");
        $product = model("Product")->where("id", $pid)->where("header_id", $this->header["id"])->find();
        if (!$product) {
            exit_json(-1, "商品信息错误");
        }
        $product["current_sell"] = model("Product")->getCurrentSale($pid);
        $product["swiper"] = model("ProductSwiper")->getSwiper($pid);
        exit_json(1, "请求成功", $product);
    }

    /**
     * 获取七牛云上传凭证
     */
    public function getUpToken()
    {
        $token = \Qiniu::getUpToken();
        exit_json(1, "请求成功", ["upToken" => $token]);
    }

    /**
     * 保存商品信息
     */
    public function saveProduct()
    {
        $pid = input("pid");
        $product_name = input("product_name");
        $tag_name = input("tag_name");
        $stock = input("stock");
        $purchase_price = input("purchase_price");
        $sale_price = input("sale_price");
        $market_price = input("market_price");
        $commission = input("commission");
        $unit = input("unit");
        $attr = input("attr");
        $one_num = input("one_num");
        $current_limit = input("limit");
        $self_limit = input("self_limit");
        $is_up = input("is_up");
        $is_hot = input("is_hot");
        $is_sec = input("is_sec");
        $start_time = input("start_time");
        $down_time = input("down_time");
        $desc = input("desc");
        $swiper = input("swiper");
        $product = model("Product")->where("id", $pid)->find();
        $org_limit = $product["limit"];
        //商品基本属性修改
        $product_attr = [
            "product_name" => $product_name,
            "stock" => $stock,
            "unit" => $unit,
            "attr" => $attr,
            "is_up" => $is_up,
            "is_hot" => $is_hot,
            "is_sec" => $is_sec,
            "desc" => $desc
        ];
        //商品销售属性
        $sale_data = [
            "tag_name" => $tag_name,
            "self_limit" => $self_limit,
            "one_num" => $one_num,
            "purchase_price" => $purchase_price,
            "sale_price" => $sale_price,
            "market_price" => $market_price,
            "commission" => $commission,
            "ratioOfMargin" => (round(1 - $purchase_price / $market_price, 2)) * 100,
            "limit" => $current_limit,
            "start_time" => $start_time,
            "down_time" => $down_time
        ];
        if ($current_limit != "" && $current_limit < model("Product")->getCurrentSale($pid)) {
            exit_json(-1, "商品限量不能低于已售出数量");
        }
        $product_data = array_merge($product_attr, $sale_data, ["update_time" => time()]);
        //更新商品信息
        $res = $product->save($product_data);
        if ($res) {
            //数据更新成功，更新缓存内容
            Cache::rm($pid . ":product");
            //处理商品销售属性
            model("Product")->modifyLimitStock($pid, $org_limit, $current_limit, $sale_data);

            //商品上下架处理
            model("Product")->setUpDown($is_up, $product, $product["header_id"]);
            //商品轮播图
            $swiper_list = explode(",", $swiper);
            $swiper_data = [];
            foreach ($swiper_list as $item) {
                $ext = pathinfo($item, PATHINFO_EXTENSION);
                $type = in_array($ext, ["jpg", "gif", "jpeg", "png"]) ? 1 : 2;
                $swiper_data[] = [
                    "product_id" => $pid,
                    "type" => $type,
                    "url" => $item
                ];
            }
            //轮播图处理
            model("ProductSwiper")->where("product_id", $pid)->delete();
            model("ProductSwiper")->saveAll($swiper_data);
            Cache::rm($pid . ":swiper");
            exit_json();

        } else {
            exit_json(-1, "保存失败");
        }
    }

    /**
     * 我的团长列表
     */
    public function getMyLeader()
    {
        $page = input('page');
        $page_num = input('page_num');
        $list = model("User")->where("header_id", $this->header["id"])->where("role_status", 2)->limit($page * $page_num, $page_num)->field("id leader_id, name, residential, is_able")->select();
        exit_json(1, '请求成功', $list);
    }

    /**
     * 打开/取消团长资格
     */
    public function cancelLeader()
    {
        $leader_id = input("leader_id");
        $is_able = input("is_able");
        if ($is_able == 1 || $is_able == 0) {
            $leader = model("User")->where("id", $leader_id)->find();
            if (!$leader) {
                exit_json(-1, "团长不存在");
            } else {
                $res = $leader->save(["is_able" => $is_able]);
                if ($res) {
                    exit_json();
                } else {
                    exit_json(-1, "操作失败");
                }
            }
        } else {
            exit_json(-1, "参数错误");
        }

    }

    /**
     * 获取待申请团长列表
     */
    public function getReadyLeader()
    {
        $page = input("page");
        $page_num = input("page_num");
        $list = model("ApplyLeaderRecord")->where("header_id", $this->header["id"])->where("status", 0)->field("id apply_id, name, residential, neighbours")->limit($page * $page_num, $page_num)->select();
        exit_json(1, "请求成功", $list);
    }

    /**
     * 团长申请详情
     */
    public function getMyLeaderDet()
    {
        $apply_id = input('apply_id');
        $data = ApplyLeaderRecord::get($apply_id);

        exit_json(1, '请求成功', $data);
    }

    /**
     * 同意团长申请
     */
    public function agreeLeader()
    {
        $apply_id = input('apply_id');
        $data = ApplyLeaderRecord::get($apply_id);
        if ($data && $data['status'] == 0) {
            if ($data['header_id'] != $this->header["id"]) {
                exit_json(-1, '权限错误');
            }
            $data->save(['status' => 1]);
            $user = model('User')->where('id', $data['user_id'])->find();
            $user->save(['role_status' => 2, "header_id" => $this->header["id"], "telephone" => $data['telephone'], "address" => $data["address"], "residential" => $data["residential"], "neighbours" => $data["neighbours"], "have_group" => $data["have_group"], "have_sale" => $data["have_sale"], "work_time" => $data["work_time"], "name" => $data["name"], "lat" => $data["lat"], "lng" => $data["lng"]]);
            Cache::rm($user["open_id"] . ":user");
            exit_json();
        } else {
            exit_json(-1, '申请记录不存在或已处理');
        }
    }

    /**
     * 团长申请拒绝
     */
    public function refuseLeader()
    {
        $apply_id = input('apply_id');
        $data = ApplyLeaderRecord::get($apply_id);
        $reason = input('reason');
        if ($data && $data['status'] == 0) {
            if ($data['header_id'] != $this->header["id"]) {
                exit_json(-1, '权限错误');
            }
            $data->save(['status' => 2, 'remarks' => $reason]);
            exit_json();
        } else {
            exit_json(-1, '申请记录不存在或已处理');
        }
    }

    /**
     * 绑定银行卡信息
     */
    public function lockBank()
    {
        $data = [
            "bank_code" => input("bank_code"),
            "true_name" => input("true_name"),
            "bank_name" => input("bank_name"),
            "bank_no" => input("bank_no"),
            "header_id" => $this->header['id']
        ];
        if (!\BankCheck::check_bankCard($data["bank_no"])) {
            exit_json(-1, '银行卡格式错误');
        }
        $bank = model("BankInfo")->where("bank_no", $data["bank_no"])->where("header_id", $this->header["id"])->find();
        if ($bank) {
            exit_json(-1, "银行卡已绑定过");
        } else {
            $res = model("BankInfo")->save($data);
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, "添加失败");
            }
        }
    }

    /**
     * 删除已绑定银行卡
     */
    public function delBank()
    {
        $bank_id = input("bank_id");
        $bank = model("BankInfo")->where("id", $bank_id)->find();
        if ($bank) {
            $res = $bank->delete();
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, "删除失败");
            }
        } else {
            exit_json(-1, "银行卡不存在");
        }
    }

    /**
     * 获取绑定银行卡列表
     */
    public function getLockBank()
    {
        $list = model("BankInfo")->where("header_id", $this->header["id"])->field("id bank_id, bank_code, bank_name, bank_no, true_name")->select();
        foreach ($list as $item) {
            $item["bank_no"] = $this->pwdBankNo($item["bank_no"]);
        }
        exit_json(1, "请求成功", $list);
    }

    function pwdBankNo($bank_no)
    {
        return str_ireplace(substr($bank_no, 6, 8), "********", $bank_no);
    }

    /**
     * 获取城主账户信息
     */
    public function getHeaderAccountInfo()
    {
        //30天营业额
        $last_day = date("Y-m-d", strtotime("- 30 day"));
        $month_sale = model("Order")->where("create_time", "gt", $last_day)->where("header_id", $this->header["id"])->where("order_status", 1)->sum("pay_money-refund_money");
        //当天营业额
        $day_sale = model("Order")->where("header_id", $this->header["id"])->where("order_status", 1)->where("create_time", "gt", strtotime(date("Y-m-d")))->sum("pay_money-refund_money");
        //账户余额
        $money = $this->header["amount_able"];
        exit_json(1, "请求成功", [
            "month_sale" => is_null($month_sale)?0:$month_sale,
            "day_sale" => is_null($day_sale)?0:$day_sale,
            "money" => $money
        ]);
    }

    /**
     * 账单明细
     */
    public function getMoneyLog()
    {
        $page = input("page");
        $page_num = input("page_num");
        $list = model("HeaderMoneyLog")->where("header_id", $this->header["id"])->limit($page * $page_num, $page_num)->order("create_time desc")->select();
        exit_json(1, "请求成功", $list);
    }

    /**
     * 获取退货商品列表
     */
    public function getRefundList()
    {
        $status = input("status");
        if (!in_array($status, [0, 1])) {
            exit_json(-1, "状态错误");
        }
        $page = input("page");
        $page_num = input("page_num");
        $model = model("OrderRefund")->alias("a");
        if ($status == 0) {
            $model->where("a.status", 0);
        }
        if ($status == 1) {
            $model->where("a.status", "neq", 0);
        }
        $list = $model->join("User user", "a.user_id=user.id", "left")->join("User leader", "a.leader_id=leader.id", "left")->join("OrderDet det", "a.order_det_id=det.id")->field("leader.name leader_name, user.user_name user_name, a.id refund_id, a.product_name, det.unit, det.attr, det.one_num, a.market_price, a.sale_price, a.num refund_num, a.refund_money, a.reason, a.refuse_reason, det.product_id, a.create_time")->order("a.create_time desc")->limit($page * $page_num, $page_num)->select();
        foreach ($list as $item) {
            $item['swiper'] = model("ProductSwiper")->getSwiper($item["product_id"]);
        }
        exit_json(1, "请求成功", $list);
    }

    /**
     * 同意退款
     */
    public function agreeRefund()
    {
        $f = fopen(__PUBLIC__ . "lock.lock", "w");
        if (flock($f, LOCK_EX | LOCK_NB)) {
            $refund_id = input("refund_id");
            $status = input("status");
            $refund = model("OrderRefund")->where("header_id", $this->header["id"])->where("id", $refund_id)->find();
            if (!$refund && $refund["status"] != 0) {
                exit_json(-1, "退款申请已处理或不存在");
            } else {
                if ($status == 1) {
                    //同意申请退款
                    $weixin = new WeiXinPay();
                    $order = model("Order")->where("order_no", $refund["order_no"])->find();
                    $refund_money = $refund["refund_money"];
                    $refund_order = [
                        "order_no" => $refund["order_no"],
                        "trade_no" => $order["transaction_id"],
                        "refund_id" => $refund["refund_no"],
                        "total_money" => $order["pay_money"],
                        "refund_money" => $refund["refund_money"],
                        "shop_id" => $refund["leader_id"],
                        "refund_desc" => "团购退款" . "," . $refund['product_name'] . "-" . $refund["reason"],
                    ];
                    Log::error($refund_order);
//                $res = $weixin->refund($refund_order);
                    $res = true;
                    if ($res) {
                        model("RefundLog")->save($refund_order);
                        $refund->save(["status" => 1]);
                        //订单商品详情
                        $product = model("OrderDet")->where("product_id", $refund["product_id"])->where("order_no", $refund["order_no"])->find();
                        //增加退货数量
                        $product->setInc("back_num", $refund['num']);
                        //增加退款金额
                        $order->setInc("refund_money", $refund_money);
                        //商品库存处理
                        db()->execute("update ts_product set stock=stock+" . $refund["num"] * $product["one_num"] . ", total_sell=total_sell-" . $refund["num"] . " where id=" . $refund["product_id"]);

                        //佣金计算
                        if ($product["is_comp"] == 1) {
                            //团长佣金
                            $leader_commission = round($product["sale_price"] * $product["commission"] * $refund["num"] / 100, 2);
                            $leader = model("User")->where("id", $refund["leader_id"])->find();
                            addCommissionLog($refund["leader_id"], 3, $leader["amount_able"], round($leader_commission * (1 - 0.006), 2));
                            db()->execute("update ts_user set amount=amount-" . round($leader_commission * (1 - 0.006), 2) . ", amount_able=amount_able-" . round($leader_commission * (1 - 0.006), 2) . " where id=" . $leader["id"]);
                            //城主剩余金额
                            $header_money = $refund_money - $leader_commission;
                            addHeaderMoneyLog($refund["header_id"], 3, $this->header["amount_able"], round($header_money * (1 - 0.006), 2));
                            db()->execute("update ts_header set amount=amount-" . round($header_money * (1 - 0.006), 2) . ", amount_able=amount_able-" . round($header_money * (1 - 0.006), 2) . " where id=" . $this->header["id"]);
                        }
                        exit_json();
                    } else {
                        exit_json(-1, "退款处理失败");
                    }
                }
                if ($status == 2) {
                    //拒绝申请退款
                    //拒绝原因
                    $refuse_reason = input("refuse_reason");
                    $res = $refund->save(["status" => 2, "refuse_reason" => $refuse_reason]);
                    if ($res) {
                        exit_json(1, "处理成功");
                    } else {
                        exit_json(-1, "处理失败");
                    }
                }
            }
        } else {
            exit_json(-1, "系统繁忙，稍后重试");
        }
    }

    /**
     * 提现
     */
    public function withDraw()
    {
        $f = fopen(__PUBLIC__ . "/withdraw.lock", "w");
        if (flock($f, LOCK_EX | LOCK_NB)) {
            //获取绑定微信open_id
            $header_admin = model("Waccount")->where("header_id", $this->header["id"])->where("is_admin", 1)->find();
            if (!$header_admin) {
                exit_json(-1, "城主未绑定提现微信号，请绑定后操作");
            }
            $h = model("Header")->where("id", $this->header['id'])->find();
            $amount = input("money");
            if (!is_numeric($amount) || $amount <= 0) {
                exit_json(-1, "金额非法");
            }
            if ($amount > $h["amount_able"]) {
                exit_json(-1, "可提现金额不足");
            }
            if (!$amount || $amount < 10) {
                exit_json(-1, "提现金额不能低于10元");
            }
            //计算提现手续费及实际到账金额
            $fee = 0;
            $money = $amount - $fee;
            $order_no = getOrderNo();
            model("WithdrawLog")->save([
                "role" => 1,
                "user_id" => $this->header["id"],
                "amount" => $amount,
                "fee" => $fee,
                "money" => $money,
                "status" => 0,
                "order_no" => $order_no
            ]);
            $withdraw_id = model("WithdrawLog")->getLastInsID();
            $log_model = model("WithdrawLog")->where("id", $withdraw_id)->find();
            $weixin = new WeiXinPay();

//        $res = $weixin->withdraw([
//            "open_id" => $h["open_id"],
//            "amount" => $money,
//            "check_name" => "NO_CHECK",
//            "desc" => "军团提现到账",
//            "order_no" => $order_no
//        ]);
            $res = [
                "code" => 1,
                "result" => [
                    "payment_time" => "2019-01-15 12:25:89"
                ]
            ];
            if ($res['code'] == 1) {
                $result = $res["result"];
                $log_model->save(["withdraw_time" => $result["payment_time"], "status" => 1]);
                addHeaderMoneyLog($this->header["id"], 2, $h['amount_able'], $amount, $order_no);
                $h->setDec("amount_able", $amount);
                $h->setInc("withdraw", $amount);
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
     * 提现到银行卡
     */
    public function withDrawBank()
    {
        $f = fopen(__PUBLIC__ . "/withdraw.lock", "w");
        if (flock($f, LOCK_EX | LOCK_NB)) {
            $bank_id = input("bank_id");
            $header = model("Header")->where("id", $this->header['id'])->find();
            $bank = model("BankInfo")->where("id", $bank_id)->find();
            if (!$bank) {
                exit_json(-1, "账户尚未绑定银行卡");
            }
            $amount = input("money");
            if ($amount > $header["amount_able"]) {
                exit_json(-1, "可提现金额不足");
            }
            if (!$amount || $amount < 1000) {
                exit_json(-1, "提现金额不能低于1000元");
            }
            //计算提现手续费及实际到账金额
            $rate = 0.001;
            $fee = round($amount * $rate, 2);
            $money = $amount - $fee;
            $order_no = getOrderNo();
            model("WithdrawLog")->save([
                "role" => 1,
                "user_id" => $this->header["id"],
                "amount" => $amount,
                "fee" => $fee,
                "money" => $money,
                "status" => 0,
                "order_no" => $order_no

            ]);
            $withdraw_id = model("WithdrawLog")->getLastInsID();
            $log_model = model("WithdrawLog")->where("id", $withdraw_id)->find();
            $weixin = new WeiXinPay();
//            $res = $weixin->withdrawBank([
//                "amount" => $money,
//                "bank_no" => $bank["bank_no"],
//                "bank_code" => $bank["bank_code"],
//                "true_name" => $bank["true_name"],
//                "desc" => "军团提现到银行卡到账",
//                "order_no" => $order_no
//            ]);
            $res = [
                "code" => 1,
                "result" => [
                    "payment_time" => "2019-01-15 12:25:89"
                ]
            ];
            if ($res['code'] == 1) {
                $result = $res["result"];
                $log_model->save(["withdraw_time" => $result["payment_time"], "status" => 1]);
                //增加提现记录
                addHeaderMoneyLog($this->header["id"], 2, $header['amount_able'], $money, $order_no);
                //增加银行卡提现手续费
                addHeaderMoneyLog($this->header["id"], 4, $header['amount_able'] - $money, $fee, $order_no);
                $header->setDec("amount_able", $amount);
                $header->setInc("withdraw", $amount);
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


//TODO 待完善


    /**
     * 获取标签数据
     */
    public function getTagName()
    {
        $tag_arr = model("ProductTag")->where("header_id", $this->header_id)->column("tag_name");
        exit_json(1, "获取成功", $tag_arr);
    }


}