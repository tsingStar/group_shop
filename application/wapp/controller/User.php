<?php
/**
 * 普通团员
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-03
 * Time: 15:26
 */

namespace app\wapp\controller;


use app\common\model\ApplyLeaderRecord;
use app\common\model\Cart;
use app\common\model\Order;
use app\common\model\OrderDet;
use app\common\model\Product;
use app\common\model\UserCoupon;
use app\common\model\WeiXinPay;
use think\Cache;
use think\Controller;
use think\Exception;
use think\Log;

class User extends Controller
{
    private $user;

    protected function _initialize()
    {
        parent::_initialize();
        $open_id = input('open_id');
        if (!$open_id) {
            exit_json(-1, '用户不存在，请重新登陆');
        } else {
            $this->user = model("User")->getUserInfo($open_id);
            if (!$this->user) {
                exit_json(-1, '用户不存在，请重新登陆');
            }
        }
    }

    /**
     * 申请成为团长
     */
    public function applyLeader()
    {
        //城主id
        $data = [
            'header_id' => input("header_id"),
            'user_id' => $this->user["id"],
            'name' => input('name'),
            'telephone' => input('telephone'),
            'address' => input('address'),
            'residential' => input('residential'),
            'neighbours' => input('neighbours'),
            'have_group' => input('have_group'),
            'have_sale' => input('have_sale'),
            'work_time' => input('work_time'),
            'other' => input('other'),
            'lat' => input('lat'),
            'lng' => input("lng")
        ];
        $header = model('Header')->where('id', $data['header_id'])->find();
        if (!$header) {
            exit_json(-1, '参数错误');
        }
        if ($this->user['role_status'] == 2) {
            exit_json(-1, '你已经是团长无需重复申请');
        }
        $apply_leader = ApplyLeaderRecord::get(['user_id' => $data['user_id'], 'status' => 0]);
        if ($apply_leader) {
            exit_json(-1, '已提交申请，等待城主审核');
        }
        $res = ApplyLeaderRecord::create($data);
        if ($res) {
            exit_json(1, '申请成功');
        } else {
            exit_json(-1, '申请失败');
        }
    }

    /**
     * 获取当前地址下最近团长信息
     */
    public function getLeader()
    {
        $lat = input("lat");
        $lng = input("lng");
        $list = model("User")->where("role_status", 2)->field("lat, lng, name, address, id, avatar, header_id")->select();
        $temp = GetDistance($lat, $lng, $list[0]['lat'], $list[0]['lng'], 2);
        $res = $list[0];
        foreach ($list as $item) {
            $distance = GetDistance($lat, $lng, $item['lat'], $item['lng'], 2);
            if ($distance < $temp) {
                $temp = $distance;
                $res = $item;
            }
        }
        $res["distance"] = $temp . "KM";
        exit_json(1, '请求成功', $res);

    }

    /**
     * 获取分类列表
     */
    public function getCateList()
    {
        $header_id = input("header_id");
        $list = model("Cate")->where("header_id", $header_id)->field("id, cate_name")->select();
        exit_json(1, "请求成功", $list);
    }

    /**
     * 获取商品列表
     */
    public function getProductList()
    {
        $header_id = input("header_id");
        $leader_id = input("leader_id");
        //分类id  hot sec
        $cate_type = input("cate_type");
        $page = input("page");
        $page_num = input("page_num");
        $redis = new \Redis2();
        if (is_numeric($cate_type)) {
            $list = $redis->zrange($header_id . ":product_cate:" . $cate_type, $page * $page_num, ($page + 1) * $page_num - 1);
        } else {
            $list = $redis->zrange($header_id . ":product_" . $cate_type, $page * $page_num, ($page + 1) * $page_num - 1);
        }
        $product_list = [];
        $cart = new Cart($this->user["open_id"], $leader_id);
        foreach ($list as $val) {
            $temp = model("Product")->getProductInfo($val);
            $temp["cart_num"] = $cart->getCartField($val);
            $temp["current_sell"] = model("Product")->getCurrentSale($val);
            $product_list[] = $temp;
        }
        exit_json(1, '请求成功', $product_list);
    }

    /**
     * 获取商品详情
     */
    public function getProductDetail()
    {
        $pid = input("pid");
        $leader_id = input("leader_id");
        $product = model("Product")->getProductInfo($pid);
        if (!$product["is_up"]) {
            exit_json(-1, "商品不存在或已下架");
        }
        $cart = new Cart($this->user["open_id"], $leader_id);
        $product['cart_num'] = $cart->getCartField($pid);

        $redis = new \Redis2();
        $user_list = $redis->hgetAll($pid . ":buy_user");
        $user_length = count($user_list);
        $user_data = [];
        $temp = array_slice($user_list, 0, 20, true);
        foreach ($temp as $key => $item) {
            $user = model("User")->getUserInfo($key);
            $user_data[] = ['avatar' => $user["avatar"]];
        }
        $product["user_list"] = [
            "total_num" => $user_length,
            "user_list" => $user_data
        ];
        exit_json(1, "请求成功", $product);
    }

    /**
     * 加入购物车
     */
    public function addCart()
    {
        $pid = input("pid");
        $product = model("Product")->getProductInfo($pid);
//        $num = 1;
        $num = input("num");
        $leader_id = input("leader_id");
        if (!$leader_id) {
            exit_json(-1, "参数错误");
        }
        $cart = new Cart($this->user["open_id"], $leader_id);
        $cart_num = $cart->getCartField($pid);
        if ($cart_num + $num <= 0) {
            $cart->delCart($pid);
            exit_json(1, "商品已删除", ["cart_num" => 0]);
        }
        if ($num != 1 && $num != -1) {
            exit_json(-1, "参数错误", ["cart_num" => $cart_num]);
        }
        if ($product["start_time"] != "") {
            if (strtotime($product["start_time"]) > time()) {
                exit_json(-1, "商品将于" . $product['start_time'] . "开售");
            }
        }
        if (!$product["is_up"]) {
            //判断商品是否在售卖
            $cart->delCart($pid);
            exit_json(-1, "商品已下架");
        } else {
            if ($product["self_limit"] != "") {
                //判断商品是否有个人限购
                $buy_num = model("Product")->getBuyUser($pid, $this->user["open_id"]);
                if ($cart_num + $num + $buy_num > $product["self_limit"]) {
                    exit_json(-1, "商品个人限量购买" . $product["self_limit"] . "件", ["cart_num" => $cart_num]);
                }
            }
        }
        //商品是否限量售卖
        if ($product["limit"] != "") {
            if ($cart_num + $num > model("Product")->getStock($pid)) {
                if (model("Product")->getStock($pid) == 0) {
                    $cart->delCart($pid);
                    $cart_num = 0;
                } else {
                    $cart->addCart($pid, model("Product")->getStock($pid));
                    $cart_num = model("Product")->getStock($pid);
                }
                exit_json(-1, "商品库存不足", ["cart_num" => $cart_num]);
            }
        }
        $cart_num += $num;
        $cart->addCart($pid, $cart_num);
        exit_json(1, "操作成功", ["cart_num" => $cart->getCartField($pid)]);
    }

    /**
     * 获取购物车信息
     */
    public function getCartList()
    {
        $leader_id = input("leader_id");
        $cart = new Cart($this->user["open_id"], $leader_id);
        $list = $cart->getCart();
        $data = [];
        foreach ($list as $key => $item) {
            $temp = model("Product")->getProductInfo($key);
            if (!$temp["is_up"]) {
                $cart->delCart($key);
                continue;
            }
            if ($temp["limit"] != "") {
                if ($item > model("Product")->getStock($key)) {
                    if (model("Product")->getStock($key) == 0) {
                        $cart->delCart($key);
                        continue;
                    } else {
                        $item = model("Product")->getStock($key);
                        $cart->addCart($key, $item);
                    }
                }
            }
            $temp["cart_num"] = $item;
            $data[] = $temp;
        }
        exit_json(1, "请求成功", $data);
    }

    /**
     * 立即购买校验订单
     */
//    public function checkOrderByIm()
//    {
//        $leader_id = input("leader_id");
//        $pid = input("pid");
//        $res = $this->updateCart($leader_id, $pid);
//        if ($res["error"]) {
//            exit_json(-1, $res["msg"]);
//        }
//        $data["product_list"][] = $res['product'];
//        $data["order_money"] = $res["order_money"];
//        $leader = model("User")->getLeaderInfo($leader_id);
//        $data["leader_info"] = ["leader_name" => $leader["name"], "address" => $leader['address']];
//        exit_json(1, "请求成功", $data);
//    }

    /**
     * 商品购买
     */
    public function checkOrder()
    {
        $leader_id = input("leader_id");
        $pid_str = input("pid_str");
        $is_modify = false;
        $order_money = 0;
        $product_list = [];
        foreach (explode(",", $pid_str) as $value) {
            $res = $this->updateCart($leader_id, $value);
            if ($res["error"]) {
                $is_modify = true;
                if ($res["code"] == 2) {
                    $product_list[] = $res['product'];
                    $order_money += $res["order_money"];
                }
            } else {
                $product_list[] = $res['product'];
                $order_money += $res["order_money"];
            }
        }
        if (count($product_list) == 0) {
            exit_json(-1, "抱歉您购买的商品已下架或已被抢光，请重新挑选");
        }
        if ($is_modify) {
            exit_json(2, "商品库存有变动，是否继续购买？");
        }
        $data["product_list"] = $product_list;
        $data["order_money"] = $order_money;
        $leader = model("User")->getLeaderInfo($leader_id);
        $data["leader_info"] = ["leader_name" => $leader["name"], "address" => $leader['address']];
        exit_json(1, "请求成功", $data);
    }

    /**
     * 更新购物车信息
     * @param $leader_id
     * @param $pid
     * @return mixed
     */
    private function updateCart($leader_id, $pid)
    {
        $cart = new Cart($this->user["open_id"], $leader_id);
        $cart_num = $cart->getCartField($pid);
        $product = model("Product")->getProductInfo($pid);
        if ($cart_num == 0) {
            $cart->addCart($pid, 1);
            $cart_num = 1;
        }
        if ($product["limit"] != "") {
            $stock = model("Product")->getStock($pid);
            if ($stock == 0) {
                $cart->delCart($pid);
                return ["error" => true, "code" => -1, "msg" => "你手慢了，商品已被抢光"];
            }
            if ($stock < $cart_num) {
                $cart_num = $stock;
                $cart->addCart($pid, $cart_num);
                $product["cart_num"] = $cart_num;
                $order_money = round($cart_num * $product["sale_price"], 2);
                return ["error" => true, "code" => 2, "msg" => "商品库存不足，系统已为您自动调整数量，是否继续购买？", "product" => $product, "order_money" => $order_money];
            }
        }
        $product["cart_num"] = $cart_num;
        $order_money = round($cart_num * $product["sale_price"], 2);
        return ["error" => false, "code" => 1, "product" => $product, "msg" => "", "order_money" => $order_money];
    }

    /**
     * 获取团长地址
     */
    public function getLeaderAddress()
    {
        $leader_id = input("leader_id");
        $leader = model("User")->getLeaderInfo($leader_id);
        exit_json(1, "请求成功", ["leader_name" => $leader["name"], "address" => $leader['address'], "header_id" => $leader["header_id"], "leader_id" => $leader['id']]);
    }

    /**
     * 获取优惠券列表
     */
    public function getCouponList()
    {
        $leader_id = input("leader_id");
        $leader = model("User")->getLeaderInfo($leader_id);
        $list = model("UserCoupon")->where("header_id", $leader["header_id"])->where("user_id", $this->user["id"])->field("id coupon_id, limit_money, coupon_money, use_time, status, out_time")->select();
        exit_json(1, "请求成功", $list);
    }

    /**
     *  生成订单  商品库存不足情况下直接截断提示商品库存不足
     */
    public function makeOrder()
    {
        //商品id串
        $f = fopen(__PUBLIC__."/order.lock", "w");
        if (flock($f, LOCK_EX)) {
            $pid_str = input("pid_str");
            $pid_arr = explode(",", $pid_str);
            if (empty($pid_arr)) {
                exit_json(-1, "商品列表为空");
            }
            $leader_id = input("leader_id");
            $leader = model("User")->getLeaderInfo($leader_id);
            if ($leader === false) {
                exit_json(-1, "团长信息不存在");
            }
            $telephone = input("telephone");
            $address = $leader["address"];
            if ($telephone == "") {
                exit_json(-1, "联系电话为空");
            }
            $cart = new Cart($this->user["open_id"], $leader_id);
            $product_list = [];
            $order_det = [];
            $order_money = 0;
            //积分数量
            $score = input("score");
            //优惠券id
            $coupon_id = input("coupon_id");
            $order = new Order();
            $order_detail = new OrderDet();
            $order->startTrans();
            $order_detail->startTrans();
            try {
                $order_no = getOrderNo();
                foreach ($pid_arr as $value) {
                    //循环查询商品购买数量及现有库存数量是否正常
                    $product = model("Product")->getProductInfo($value);
                    $cart_num = $cart->getCartField($value);
                    if ($cart_num <= 0) {
                        throw new Exception($product["product_name"] . "商品不存在");
                    }
                    if ($product["limit"] != "") {
                        //有库存限制
                        $stock = model("Product")->getStock($value);
                        if ($stock < $cart_num) {
                            //商品库存不足购物车数量 抛出异常
                            throw new Exception($product["product_name"] . "库存不足");
                        } else {
                            //库存充足
                            $del_num = model("Product")->delStock($value, $cart_num);
                            $product_list[] = ['pid' => $value, "num" => $del_num];
                            if ($del_num < $cart_num) {
                                //库存不足异常
                                throw new Exception($product["product_name"] . "库存不足");
                            }
                        }
                    }
                    $p = [];
                    $p["buy_num"] = $cart_num;
                    $p["header_id"] = $leader["header_id"];
                    $p["leader_id"] = $leader_id;
                    $p["user_id"] = $this->user['id'];
                    $p["product_id"] = $product["id"];
                    $p["order_no"] = $order_no;
                    $p["product_name"] = $product["product_name"];
                    $p["purchase_price"] = $product["purchase_price"];
                    $p["sale_price"] = $product["sale_price"];
                    $p["market_price"] = $product["market_price"];
                    $p["commission"] = $product["commission"];
                    $p["unit"] = $product["unit"];
                    $p["attr"] = $product["attr"];
                    $p["one_num"] = $product["one_num"];
                    $order_det[] = $p;
                    $order_money += $product["sale_price"] * $cart_num;
                }
                if ($coupon_id != 0) {
                    $coupon = new UserCoupon();
                    //校验优惠券合法性
                    $c = $coupon->checkCoupon($this->user["id"], $coupon_id, $leader["header_id"]);
                    if ($c["limit_money"] > $order_money) {
                        throw new Exception("订单满" . $c["limit_money"] . "元才可使用");
                    }
                    $coupon_money = $c["coupon_money"];
                } else {
                    $coupon_money = 0;
                }
                //校验积分
                model("User")->checkScore($this->user["open_id"], $score);
                $score_money = $score / 100;
                //支付总金额
                $pay_money = round($order_money - $coupon_money - $score_money, 2);
                if($pay_money<=0){
                    throw new Exception("支付金额不能小于0.01");
                }
                $order_data = [
                    "order_no" => $order_no,
                    "header_id" => $leader["header_id"],
                    "leader_id" => $leader_id,
                    "user_id" => $this->user["id"],
                    "pick_address" => $address,
                    "telephone" => $telephone,
                    "order_money" => $order_money,
                    "coupon_id" => $coupon_id,
                    "coupon_money" => $coupon_money,
                    "score" => $score,
                    "score_money" => $score_money,
                    "pay_money" => $pay_money
                ];
                $r1 = $order->save($order_data);
                $r2 = $order_detail->saveAll($order_det);
                if ($r1 && $r2) {
                    //订单正常生成
                    $r3 = true;
                    if ($coupon_id != 0) {
                        //优惠券使用
                        $r3 = model("UserCoupon")->save(["status" => 1, "use_time" => date("Y-m-d H:i:s")], ["id" => $coupon_id]);
                    }
                    //用户积分处理
                    $r4 = true;
                    if ($score > 0) {
                        addScoreLog($this->user["id"], 2, $score, "用户消费抵扣积分", $this->user["score"]);
                        $r4 = model("User")->where("id", $this->user["id"])->setDec("score", $score);
                    }
                    if ($r3 && $r4) {
                        Cache::rm($this->user["open_id"] . ":user");
                    } else {
                        throw new Exception("优惠券或积分异常");
                    }
                    //清理购物车  添加用户购买数量
                    foreach ($order_det as $val) {
                        //增加用户购买数量
                        model("Product")->addBuyUser($this->user["open_id"], $val["product_id"], $val["buy_num"]);
                        //增加当前销售数量
                        model("Product")->addCurrentSale($val["product_id"], $val["buy_num"]);
                        $cart->delCart($val["product_id"]);
                    }
                    $order->commit();
                    $order_detail->commit();
                    //去支付，生成支付订单
                    $pay_no = getOrderNo();
                    $r5 = model("Payment")->save([
                        "order_no" => $order_no,
                        "pay_no" => $pay_no,
                        "pay_money" => $pay_money
                    ]);
                    if ($r5) {
                        $order_info = [
                            "subject" => "易贝通团购-订单支付",
                            "body" => "订单支付",
                            "out_trade_no" => $pay_no,
                            "total_amount" => $pay_money,
                            "trade_type" => "JSAPI",
                            "open_id" => $this->user['open_id'],
                        ];
                        $notify_url = config("notify_url");
                        $weixin = new WeiXinPay();
                        $pay_arr = $weixin->createPrePayOrder($order_info, $notify_url);
//                        $pay_arr = false;
                        flock($f, LOCK_UN);
                        fclose($f);
                        if ($pay_arr !== false) {
                            exit_json(1, "操作成功", $pay_arr);
                        } else {
                            exit_json(2, "订单生成成功，支付申请失败");
                        }
                    } else {
                        flock($f, LOCK_UN);
                        fclose($f);
                        exit_json(2, "订单生成成功，支付申请失败");
                    }
                } else {
                    throw new Exception("订单生成失败");
                }

            } catch (Exception $e) {
                Log::error("订单生成失败" . $e->getMessage());
                if (count($product_list) > 0) {
                    $redis = new \Redis2();
                    foreach ($product_list as $item) {
                        for ($j = 0; $j < $item["num"]; $j++) {
                            $redis->lpush($item["pid"] . ":stock", 1);
                        }
                    }
                }
                $order->rollback();
                $order_detail->rollback();
                exit_json(-1, $e->getMessage());
            } finally {
                flock($f, LOCK_UN);
                fclose($f);
            }
        }
    }

    /**
     * 待支付订单列表
     */
    public function payOrderList()
    {
        $page = input("page");
        $page_num = input("page_num");
        $list = model("Order")->where("user_id", $this->user["id"])->where("order_status", 0)->limit($page * $page_num, $page_num)->order("create_time desc")->select();
        $data = [];
        foreach ($list as $item) {
            $temp = [];
            $leader = model("User")->getLeaderInfo($item["leader_id"]);
            $temp["order_id"] = $item["id"];
            $temp["order_no"] = $item["order_no"];
            $temp["order_money"] = $item["order_money"];
            $temp["coupon_money"] = $item["coupon_money"];
            $temp["score_money"] = $item["score_money"];
            $temp["pay_money"] = $item["pay_money"];
            $temp["leader_name"] = $leader["name"];
            $temp["residential"] = $leader["residential"];
            $temp["product_list"] = model("OrderDet")->getOrderDetail($item["order_no"]);
            $data[] = $temp;
        }
        exit_json(1, "请求成功", $data);
    }

    /**
     * 订单支付
     */
    public function payOrder()
    {
        $order_id = input("order_id");
        $order = model("Order")->where("id", $order_id)->where("order_status", 0)->find();
        if (!$order) {
            exit_json(-1, "订单不存在");
        }
        $pay_no = getOrderNo();
        $order_info = [
            "subject" => "易贝通团购-订单支付",
            "body" => "订单支付",
            "out_trade_no" => $pay_no,
            "total_amount" => $order["pay_money"],
            "trade_type" => "JSAPI",
            "open_id" => $this->user['open_id'],
        ];
        $notify_url = config("notify_url");
        $weixin = new WeiXinPay();
        $pay_arr = $weixin->createPrePayOrder($order_info, $notify_url);
//        $pay_arr = false;
        if ($pay_arr !== false) {
            exit_json(1, "操作成功", $pay_arr);
        } else {
            exit_json(2, "申请支付失败");
        }
    }

    /**
     * 取消订单
     */
    public function cancelOrder()
    {
        $order_id = input("order_id");
        $order = model("Order")->where("id", $order_id)->where("user_id", $this->user["id"])->find();
        if ($order["order_status"] != 0) {
            exit_json(-1, "订单状态不支持取消");
        }
        $order = new Order();
        $order_det = new OrderDet();
        $product = new Product();
        $order->startTrans();
        $order_det->startTrans();
        try {
            //取消订单
            $order = $order->where("id", $order_id)->find();
            $res = $order->save(["order_status" => 2]);
            if ($res) {
                //释放商品库存
                $r2 = $order_det->save(["status" => 5], ["order_no" => $order["order_no"]]);
                if (!$r2) {
                    throw new Exception("商品处理失败");
                }
                $product_list = model("OrderDet")->where("order_no", $order["order_no"])->field("product_id, buy_num")->select();
                foreach ($product_list as $p) {
                    //增加商品库存
                    $product->addStock($p["product_id"], $p["buy_num"]);
                    //减少用户购买量
                    $product->addBuyUser($this->user["open_id"], $p["product_id"], -$p["buy_num"]);
                    //减少当期商品销量
                    $product->addCurrentSale($p["product_id"], -$p["buy_num"]);
                }
                //返还积分
                if ($order["score"] > 0) {
                    model("User")->where("id", $this->user["id"])->setInc("score", $order["score"]);
                    Cache::rm($this->user["open_id"] . ":userInfo");
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
                $order->commit();
                $order_det->commit();
                exit_json(1, "取消成功");
            } else {
                throw new Exception("订单取消失败");
            }
        } catch (Exception $e) {
            $order->rollback();
            $order_det->rollback();
            Log::error("取消订单异常" . $order_id);
            exit_json(-1, $e->getMessage());
        }
    }

    /**
     * 根据状态获取订单商品
     */
    public function orderProduct()
    {
        $page = input("page");
        $page_num = input("page_num");
        // 1 待配送 2 待自提商品 3 已完成商品
        $status = input("status");
        $list = model("OrderDet")->where("user_id", $this->user["id"])->where("status", $status)->limit($page * $page_num, $page_num)->order("create_time desc")->select();
        $data = [];
        foreach ($list as $item) {
            $leader = model("User")->getLeaderInfo($item["leader_id"]);
            $temp["order_det_id"] = $item["id"];
            $temp["leader_name"] = $leader["name"];
            $temp["residential"] = $leader["residential"];
            $temp["product_name"] = $item["product_name"];
            $temp["market_price"] = $item["market_price"];
            $temp["sale_price"] = $item["sale_price"];
            $temp["one_num"] = $item["one_num"];
            $temp["buy_num"] = $item["buy_num"];
            $temp["swiper"] = model("ProductSwiper")->getSwiper($item["product_id"]);
            $temp["status"] = $item["status"];
            $data[] = $temp;
        }
        exit_json(1, "请求成功", $data);
    }

    /**
     * 确认收货
     */
    public function getProduct()
    {
        $order_det_id = input("order_det_id");
        $order_det = model("OrderDet")->where("user_id", $this->user["id"])->where("id", $order_det_id)->where("status", 2)->find();
        if (!$order_det) {
            exit_json(-1, "待确认收货商品不存在");
        }
        $res = $order_det->save(["status" => 3]);
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, "操作失败");
        }
    }

    /**
     * 获取已取消订单
     */
    public function getCancelOrder()
    {
        $page = input("page");
        $page_num = input("page_num");
        $list = model('Order')->where("user_id", $this->user["id"])->where("order_status", 2)->field("id order_id, order_no, leader_id, order_status, order_money, pay_money")->limit($page*$page_num, $page_num)->select();
        foreach ($list as $item){
            $leader = model('User')->getLeaderInfo($item["leader_id"]);
            $item["leader_name"] = $leader["name"];
            $item["residential"] = $leader["residential"];
            $item["product_list"] = model("OrderDet")->getOrderDetail($item["order_no"]);
        }
        exit_json(1, "请求成功", $list);
    }

    /**
     * 签到
     */
    public function signDay()
    {
        $today = date("Y-m-d");
        $sign = model("UserSignLog")->where("today", $today)->where("user_id", $this->user["id"])->find();
        if($sign){
            exit_json(2, "重复签到");
        }else{
            $res = model("UserSignLog")->save([
                "user_id"=>$this->user["id"],
                "today"=>$today
            ]);
            if($res){
                $score = config("signScore");
                model("User")->where("id", $this->user["id"])->setInc("score", $score);
                addScoreLog($this->user["id"], 1, $score, "签到积分", $this->user["score"]);
                Cache::rm($this->user["open_id"].":user");
                exit_json(1, "签到成功");
            }else{
                exit_json(-1, "签到异常");
            }
        }
    }

    /**
     * 获取提货码
     */
    public function getPickCode()
    {
        $open_id = $this->user["open_id"];
        $url = qrcode($open_id);
        exit_json(1, "请求成功", ["qrcode_url"=>$url]);
    }

    /**
     * 获取用户基本信息
     */
    public function getUserInfo()
    {
        exit_json(1, "请求成功", $this->user);
    }

    /**
     * 获取退款记录
     */
    public function getRefundList()
    {
        $page = input("page");
        $page_num = input("page_num");
        $list = model("OrderRefund")->where("user_id", $this->user["id"])->order("create_time desc")->field("id refund_id, product_id, product_name, order_no, order_det_id, num, market_price,sale_price,refund_money,reason, status, refuse_reason, create_time")->limit($page_num*$page, $page_num)->select();
        foreach ($list as $item){
            $item["swiper"] = model("ProductSwiper")->getSwiper($item["product_id"]);
        }
        exit_json(1, "请求成功", $list);
    }






    //TODO 待完善

    /**
     * 异步保存form_id
     */
    public function setFormId()
    {
        $form_id = input("form_id");
        $res = db("form_id")->insert([
            "user_id" => $this->user['id'],
            "form_id" => $form_id
        ]);
        if ($res) {
            exit_json();
        } else {
            exit_json(-1);
        }
    }

    /**
     * 分享得红包
     */
    public function getCoupon()
    {
        $open_id = $this->user["open_id"];
        $order_no = input("order_no");
        $order = model("Order")->where("order_no", $order_no)->where("user_id", $this->user["id"])->find();
        if (!$order) {
            exit_json(-1, "分享订单异常");
        }
        $group = model("Group")->where("id", $order["group_id"])->find();
        if ($group["status"] != 1) {
            exit_json(-1, "您与红包擦肩而过，下次努力");
        }
        if ($order["order_money"] - $order["refund_money"] < 9) {
            exit_json(-1, "您与红包擦肩而过，下次努力");
        } else {
            $cr = db("CouponRecord")->where("order_no", $order_no)->find();
            if ($cr) {
                exit_json(-1, "抱歉，每个订单只有一次拆红包机会");
            }
//
//            //计算红包金额
            $amount = 0.3;
            $coupon_no = getOrderNo();
            $id = db("CouponRecord")->insertGetId([
                "coupon_no" => $coupon_no,
                "order_no" => $order_no,
                "header_group_id" => $order["header_group_id"],
                "create_time" => date("Y-m-d H:i:s"),
                "coupon" => $amount
            ]);
            $rand = rand(0, 300);
            if ($rand < 100) {
                if ($id > 0) {
                    $weixin = new WeiXinPay();
                    $order_info = [
                        "open_id" => $open_id,
                        "amount" => $amount,
                        "check_name" => "NO_CHECK",
                        "desc" => "分享得红包",
                        "order_no" => $coupon_no
                    ];
                    $res = $weixin->withdraw($order_info);
//                $res = true;
                    if ($res) {
                        db("CouponRecord")->where("id", $id)->update(["status" => 1]);
                    }
                    exit_json(1, "恭喜您获得" . $amount . "元红包，红包会以微信零钱方式发到您手中");
                } else {
                    exit_json(-1, "您与红包擦肩而过，下次努力");
                }
            } else {
                exit_json(-1, "您与红包擦肩而过，下次努力");
            }

        }
    }

}