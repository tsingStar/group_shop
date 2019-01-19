<?php
/**
 * 支付结果通知
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018.6.4
 * Time: 09:31
 */

namespace app\payresult\controller;


use app\common\model\WeiXinPay;
use think\Controller;
use think\Exception;
use think\Log;

class PayResult extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 支付通知入口
     */
    public function index()
    {
        set_time_limit(0);
        //微信支付来源
        $xml = file_get_contents('php://input');
        $_POST = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $log_path = LOG_PATH . 'weixin';
        is_dir($log_path) or mkdir($log_path, "0777");
        file_put_contents($log_path . DS . date("Y-m-d") . ".txt", date("H:m:s") . json_encode($_POST) . "\r\n", FILE_APPEND);
        if ($_POST['result_code'] == 'SUCCESS') {
            $weixin = new WeiXinPay();
            $validRes = $weixin->chargeNotify();
            if ($validRes === false) {
                Log::error('签名错误');
            }
            $orderInfo = $this->formatRes($validRes);
            //测试
//            $no = input("order_no");
//            $t = model("Payment")->where("order_no", $no)->order("id desc")->find();
//            $orderInfo=[
//                "out_trade_no"=>$t["pay_no"],
//                "total_money"=>input("money"),
//                "trade_no"=>getOrderNo()
//            ];
            //测试
            try {
//                $f = fopen(__PUBLIC__."/pay.txt", "w");
//                if(flock($f, LOCK_EX)){
                model('Payment')->startTrans();
                model('Order')->startTrans();
                model('OrderDet')->startTrans();
                $payment = model('Payment')->where('pay_no', $orderInfo['out_trade_no'])->find();
                $order_no = $payment["order_no"];
                $order = model("Order")->where("order_no", $order_no)->find();
                if ($payment['status'] === 0 && $order["order_status"] == 0) {
                    if ($orderInfo['total_money'] == $payment['pay_money'] && $orderInfo['total_money'] == $order['pay_money']) {
                        //订单支付成功
                        $res1 = $payment->save(['status' => 1]);
                        if(!$res1){
                            throw new Exception("订单处理失败1". $order_no);
                        }
                        $transaction_id = $orderInfo['trade_no'];
                        //订单处理
                        $res2 = $order->allowField(true)->save([
                            "transaction_id" => $transaction_id,
                            "order_status" => 1,
                            "pay_time" => date("Y-m-d H:i:s"),
                            "pay_no" => $orderInfo["out_trade_no"]
                        ]);
                        if (!$res2) {
                            throw new Exception("订单处理失败" . $order_no);
                        }
                        //订单详情处理
                        model("OrderDet")->save(["status" => 1], ["order_no" => $order_no]);
                        $order_detail = model("OrderDet")->where("order_no", $order_no)->select();
                        foreach ($order_detail as $value) {
                            //商品库存处理
                            model("Product")->where("id", $value["product_id"])->setDec("stock", $value["one_num"]*$value["buy_num"]);
                            //商品总销量处理
                            model("Product")->where("id", $value["product_id"])->setInc("total_sell", $value["buy_num"]);
                        }

                        //订单支付后处理商品库存及销量信息
                        model('Payment')->commit();
                        model('Order')->commit();
                        model('OrderDet')->commit();
                    } else {
                        Log::error("订单支付金额错误" . $orderInfo['out_trade_no']);
                    }
                } else {
                    Log::error('订单已处理' . $orderInfo['out_trade_no']);
                }
//                    flock($f, LOCK_UN);
//                    fclose($f);
//                }
            } catch (Exception $e) {
                model('Payment')->rollback();
                model('Order')->rollback();
                model('OrderDet')->rollback();
                Log::error("订单处理异常1" . $orderInfo['out_trade_no'] . ":" . $e->getMessage());
            }
        } else {
            Log::error('订单支付失败' . json_encode($_POST));
        }
        header("Content-Type:application/xml");
        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    /**
     * 格式化支付返回信息
     * @param $orderInfo
     * @param $pay_type
     * @return array
     */
    private function formatRes($orderInfo)
    {
        $payInfo = [];
        $payInfo['out_trade_no'] = $orderInfo['out_trade_no'];
        $payInfo['trade_no'] = $orderInfo['transaction_id'];
        $payInfo['total_money'] = $orderInfo['total_fee'] / 100;
        return $payInfo;
    }


}