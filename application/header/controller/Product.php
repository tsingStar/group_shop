<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-18
 * Time: 10:08
 */

namespace app\header\controller;


use app\common\model\ProductSwiper;
use think\Cache;
use think\Exception;
use think\Log;

class Product extends ShopBase
{

    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 商品列表
     */
    public function index()
    {
        $param = input("get.");
        $model = model('Product')->where('header_id', session(config('headerKey')));
        if (isset($param["product_name"])) {
            $model->whereLike("product_name", "%" . $param["product_name"] . "%");
        }
        if (isset($param["cate_id"]) && $param["cate_id"] != "") {
            $model->where("cate_id", $param["cate_id"]);
        }
        $model->order("stock desc, id desc");
        $list = $model->paginate(10, false, ["query" => $param]);
        $this->assign('list', $list);
        $this->assign("param", $param);
        $cate_list = model("Cate")->where("header_id", HEADER_ID)->column("cate_name", "id");
        $this->assign("cate_list", $cate_list);
        $this->assign("totalNum", model("Product")->where("header_id", HEADER_ID)->count());
        return $this->fetch();
    }

    /**
     * 待配送商品
     */
    public function shippingProduct()
    {
        $param = input("get.");
        $model = model("OrderDet")->alias("a")->join("Product b", "a.product_id=b.id");
        if (isset($param["product_name"]) && trim($param["product_name"]) != "") {
            $model->whereLike("b.product_name", "%" . $param["product_name"] . "%");
        }
        $list = $model->where("a.status", 1)->where("a.is_match", 1)->where("a.is_get", 0)->where("a.header_id", HEADER_ID)->field("sum(a.buy_num-a.back_num) buy_num, b.*")->group("a.product_id")->paginate(15, false, ["query" => $param]);
        $this->assign("list", $list);
        $this->assign("param", $param);
        return $this->fetch();

    }

    /**
     * 待配货商品
     */
    public function readyMatchProduct()
    {
        $param = input("get.");
        $model = model("OrderDet")->alias("a")->join("Product b", "a.product_id=b.id");
        if (isset($param["product_name"]) && trim($param["product_name"]) != "") {
            $model->whereLike("b.product_name", "%" . $param["product_name"] . "%");
        }
        $list = $model->where("a.header_id", HEADER_ID)->where("a.status", 1)->where("a.is_match", 0)->field("sum(a.buy_num-a.back_num) buy_num, b.*")->group("a.product_id")->paginate(15, false, ["query" => $param]);
        $this->assign("list", $list);
        $this->assign("param", $param);
        return $this->fetch();
    }

    /**
     * 下载配送列表
     */
    public function downloadDispatch()
    {
        $list = model("OrderDet")->alias("a")->join("User b", "a.leader_id=b.id")->where("a.header_id", HEADER_ID)->where("a.status", 1)->where("a.is_match", 1)->where("a.is_get", 0)->field(" b.name leader_name, b.address, a.product_name, sum(a.buy_num-a.back_num) buy_num, a.unit, a.attr, a.one_num")->group("a.leader_id, a.product_id")->select();
        $data = [];
        foreach ($list as $item){
            $data[] = array_values($item->getData());
        }
        $header = [
            "团长名称",
            "团长地址",
            "商品名称",
            "待配送数量",
            "单位",
            "规格",
            "每份数量"
        ];
        excel($header, $data, "待配送商品列表".date("Y-m-d"));
        exit;
    }

    /**
     * 商品编辑
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $product_id = input('product_id');
        $product = model('Product')->where('id', $product_id)->find();
        if (request()->isAjax()) {
            $data = input('post.');
            $swiper = input("swiper/a");
            if (count($swiper) > 0) {
                foreach ($swiper as $item) {
                    $arr = pathinfo($item);
                    $ext = in_array($arr['extension'], ["jpg", "jpeg", "png", "bmp"]) ? 1 : 2;
                    $swiper_list[] = [
                        'type' => $ext,
                        'url' => $item,
                        'product_id' => $product_id
                    ];
                }
                model('ProductSwiper')->where('product_id', $product_id)->delete();
                model('ProductSwiper')->saveAll($swiper_list);
                Cache::rm($product_id . ":swiper");
            }
            $ori_cate = $product["cate_id"];
            $product->allowField(['product_name', 'cate_id', 'attr', 'unit', 'desc', 'product_detail'])->save($data);
            if ($product["is_up"]) {
                $redis = new \Redis2();
                $redis->zrem(HEADER_ID . ":product_cate:" . $ori_cate, $product_id);
                $redis->zadd(HEADER_ID . ":product_cate:" . $data["cate_id"], $product['ord'], $product_id);
            }
            Cache::rm($product_id . ":product");
            exit_json();
        }
        $swiper_list = model('ProductSwiper')->where('product_id', $product_id)->select();
        $this->assign('item', $product);
        $this->assign('swiper_list', $swiper_list);
        $cate_list = model("Cate")->where("header_id", HEADER_ID)->column("cate_name", "id");
        $this->assign("cate_list", $cate_list);
        $unit_list = model("Unit")->where("header_id", HEADER_ID)->column("unit_name");
        $this->assign("unit_list", $unit_list);
        return $this->fetch();
    }

    /**
     * 上下架
     */
    public function upstair()
    {
        $pid = input("id");
        $type = input("type");
        $product = model("Product")->where("id", $pid)->find();
        if ($type == 1) {
            $pattr = model("ProductSaleAttrLog")->where("product_id", $pid)->find();
            if (!$pattr) {
                exit_json(-1, "请设置完销售属性后上架商品");
            }
        }
        $res = $product->save(['is_up' => $type]);
        if ($res) {
            model("Product")->setUpDown($type, $product, HEADER_ID);
            Cache::rm($pid . ":product");
            exit_json();
        } else {
            exit_json(-1, "操作失败");
        }
    }

    /**
     * 商品配货
     */
    public function matchProduct()
    {
        $product_id = input("product_id");
        $match_num = model("OrderDet")->where("product_id", $product_id)->where("is_match", 0)->where("status", 1)->count();
        if ($match_num == 0) {
            exit_json(-1, "暂无待配货订单");
        }
        $res = model("OrderDet")->save(['is_match' => 1], ['product_id' => $product_id, 'is_match' => 0, 'status' => 1]);
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, "操作失败");
        }
    }

    /**
     * 配送商品，清空当前销售数量
     */
    public function dispatchProduct()
    {
        $pid = input("id");
        $dispatch_num = model("OrderDet")->where("product_id", $pid)->where("is_get", 0)->where("is_match", 1)->count();
        if ($dispatch_num == 0) {
            exit_json(-1, "暂无需要配送订单");
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
     * 添加商品销售属性
     */
    public function saleAttr()
    {
        $id = input("product_id");
        $item = model("Product")->where("id", $id)->find();
        if (request()->isAjax()) {
            //获取原始限量数量
            $data = input("post.");
            $org_limit = $item["limit"];
            $current_limit = $data["limit"];
            if ($current_limit != "" && $current_limit < model('Product')->getCurrentSale($id)) {
                exit_json(-1, "限量不能低于商品已售出数量");
            }
            $res = $item->save($data);
            Cache::rm($id . ":product");
            if ($res) {
                //更改商品限量库存及商品销售属性
                model("Product")->modifyLimitStock($id, $org_limit, $current_limit, $data);
                exit_json();
            } else {
                exit_json(-1, "设置失败");
            }
        }
        $cate_name = model("ProductTag")->where("header_id", session("header_id"))->column("tag_name");
        $this->assign("tag_name", $cate_name);
        $this->assign("item", $item);
        return $this->fetch();
    }

    /**
     * 分类更改
     */
    public function changeCate()
    {
        $id = input("id");
        $cate_id = input("cate_id");
        $product = model("Product")->where("id", $id)->find();
        if ($product) {
            $ori_cate = $product["cate_id"];
            $res = $product->save(["cate_id" => $cate_id]);
            if ($res) {
                if ($product["is_up"]) {
                    $redis = new \Redis2();
                    $redis->zrem(HEADER_ID . ":product_cate:" . $ori_cate, $id);
                    $redis->zadd(HEADER_ID . ":product_cate:" . $cate_id, $product["ord"], $id);
                }
                Cache::rm($id . ":product");
                exit_json();
            } else {
                exit_json(-1, "分类变更失败");
            }
        } else {
            exit_json(-1, "商品不存在");
        }
    }

    /**
     * 更改热销/抢购
     */
    public function modifyC()
    {
        $pid = input("pid");
        $checked = input("checked");
        //0 热销 1 抢购
        $type = input("type");
        $product = model("Product")->where("id", $pid)->find();
        if (!$product) {
            exit_json(-1, "商品不存在");
        }
        if ($type == 0) {
            $res = $product->save(["is_hot" => $checked]);
        } else if ($type == 1) {
            $res = $product->save(["is_sec" => $checked]);
        } else {
            exit_json(-1, "参数错误");
        }
        if ($res) {
            if ($product["is_up"]) {
                $redis = new \Redis2();
                if ($checked == 0) {
                    //取消 redis中 删除
                    if ($type == 0) {
                        $redis->zrem(HEADER_ID . ":product_hot", $pid);
                    } else {
                        $redis->zrem(HEADER_ID . ":product_sec", $pid);
                    }
                } else {
                    //取消 redis中 删除
                    if ($type == 0) {
                        $redis->zadd(HEADER_ID . ":product_hot", $product["ord"], $pid);
                    } else {
                        $redis->zadd(HEADER_ID . ":product_sec", $product["ord"], $pid);
                    }
                }
            }
            //移除商品，已方便前端更新
            Cache::rm($pid . ":product");
            exit_json();

        } else {
            exit_json(-1, "更改失败");
        }
    }

    /**
     * 更改排序
     */
    public function changeOrd()
    {
        $pid = input("pid");
        $ord = input("ord");
        $product = model("Product")->where("id", $pid)->find();
        if ($product) {
            $res = $product->save(["ord" => $ord]);
            if ($res) {
                if ($product["is_up"]) {
                    $redis = new \Redis2();
                    if ($product["is_hot"] == 1) {
                        $redis->zadd(HEADER_ID . ":product_hot", $ord, $pid);
                    }
                    if ($product["is_sec"] == 1) {
                        $redis->zadd(HEADER_ID . ":product_sec", $ord, $pid);
                    }
                    $redis->zadd(HEADER_ID . ":product_cate:" . $product["cate_id"], $ord, $pid);
                }
                Cache::rm($pid . ":product");
                exit_json();
            } else {
                exit_json(-1, "更改失败");
            }
        } else {
            exit_json(-1, "商品不存在");
        }
    }

    /**
     * 添加商品
     */
    public function add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data["product_name"] = '【' . $data["product_name"] . '】';
            $swiper = input("swiper/a");
            $data['header_id'] = session(config('headerKey'));
            $res = model('Product')->allowField(['product_name', 'desc', 'header_id', 'unit', 'is_bulk', 'attr', 'cate_id', "product_detail"])->save($data);
            if (!$res) {
                exit_json(-1, "添加失败，刷新后重试");
            }
            $product_id = model('Product')->getLastInsID();
            if (count($swiper) > 0) {
                foreach ($swiper as $item) {
                    $arr = pathinfo($item);
                    $ext = in_array($arr['extension'], ["jpg", "jpeg", "png", "bmp"]) ? 1 : 2;
                    $swiper_list[] = [
                        'type' => $ext,
                        'url' => $item,
                        'product_id' => $product_id
                    ];
                }
                model('ProductSwiper')->saveAll($swiper_list);
            }
//            $redis = new \Redis2();
//            $redis->zadd(HEADER_ID.":product_cate:".$data["cate_id"], 10, $product_id);
            exit_json();
        }
        $cate_list = model("Cate")->where("header_id", HEADER_ID)->column("cate_name", "id");
        $this->assign("cate_list", $cate_list);
        $unit_list = model("Unit")->where("header_id", HEADER_ID)->column("unit_name");
        $this->assign("unit_list", $unit_list);
        return $this->fetch();
    }

    /**
     * 商品入库
     */
    public function stockAdd()
    {
        $product_id = input("product_id");
        $product = model("Product")->where("id", $product_id)->find();
        $this->assign("item", $product);
        return $this->fetch();
    }

    /**
     * 添加入库记录
     */
    public function stockRecord()
    {
        $product_id = input("product_id");
        $product = model("Product")->where("id", $product_id)->find();
        if (!$product) {
            exit_json(-1, "商品不存在，请先添加商品在进行入库操作");
        }
        $product->startTrans();
        model("ProductStockRecord")->startTrans();
        try {
            $data = [
                "product_id" => $product_id,
                "purchase_price" => input("purchase_price"),
                "market_price" => input("market_price"),
                "num" => input("num"),
                "type" => input("type"),
                "stock_before" => input("stock"),
                "stock_after" => input("stock") + input("num")
            ];
            $res1 = model("ProductStockRecord")->save($data);
            if (!$res1) {
                throw new Exception("添加入库记录失败");
            }

            $res2 = $product->setInc("stock", $data["num"]);
            if (!$res2) {
                throw new Exception("增加商品库存失败");
            }
            $product->commit();
            model("ProductStockRecord")->commit();
            exit_json();
        } catch (\Exception $e) {
            $product->rollback();
            model("ProductStockRecord")->rollback();
            exit_json(-1, $e->getMessage());
        }
    }

    /**
     * 商品出入库记录
     */
    public function stockList()
    {
        $product = model("Product")->where("id", input("product_id"))->find();
        $this->assign("product", $product);
        $list = model("ProductStockRecord")->where("product_id", input("product_id"))->order("create_time desc")->paginate(10);
        $this->assign("list", $list);
        return $this->fetch();
    }

    /**
     * 删除商品
     */
    public function delData()
    {
        $product_id = input("idstr");
        $res = \app\common\model\Product::destroy(["id" => ["in", $product_id]]);
        if ($res) {
            ProductSwiper::destroy(["product_id" => ["in", $product_id]]);
            exit_json();
        } else {
            exit_json(-1, "删除失败");
        }
    }

    /**
     * 获取历史商品
     */
    public function showHistory()
    {
        $pid = input("id");
        $record_list = model("HeaderGroupProduct")->where("base_id", $pid)->select();
        exit_json(1, "请求成功", $record_list);
    }

    /**
     * 商品标签列表
     */
    public function tagList()
    {
        $list = model("ProductTag")->where("header_id", session(config("headerKey")))->paginate(10);
        $this->assign("list", $list);
        $this->assign("totalNum", model("ProductTag")->where("header_id", session(config("headerKey")))->count());
        return $this->fetch();
    }

    /**
     * 添加编辑标签
     */
    public function addTag()
    {
        $data = input("post.");
        $id = input("id");
        $item = model("ProductTag")->where("id", $id)->find();
        if (request()->isAjax()) {
            if ($item) {
                $res = $item->save($data);
            } else {
                $data["header_id"] = HEADER_ID;
                $res = model("ProductTag")->save($data);
            }
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, '保存失败');
            }
        }
        $this->assign("item", $item);
        return $this->fetch();

    }

    /**
     * 删除标签
     */
    public function delTag()
    {
        $id = input("idstr");
        $res = model("ProductTag")->where("id", $id)->delete();
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, '删除失败，刷新后重试');
        }

    }

    /**
     * 商品预热图
     */
    public function readyImg()
    {

        $list = model("PreImage")->where("header_id", HEADER_ID)->select();
        $this->assign("list", $list);
        return $this->fetch();

    }

    /**
     * 保存预热图
     */
    public function saveReadyImg()
    {
        $img_str = input("img_str");
        if ($img_str == "") {
            exit_json(-1, "未选择图片");
        } else {
            $img_arr = explode(",", $img_str);
            $data = [];
            foreach ($img_arr as $item) {
                $temp = [
                    "header_id" => HEADER_ID,
                    "image_url" => $item
                ];
                $r = model("PreImage")->where($temp)->find();
                if (!$r) {
                    $data[] = $temp;
                }
            }
            if (count($data) > 0) {
                $res = model("PreImage")->saveAll($data);
            } else {
                $res = true;
            }
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, "保存失败");
            }
        }
    }

    /**
     * 删除预热图
     */
    public function delReadyImg()
    {
        $ids = input("idstr");
        $res = model("PreImage")->whereIn("id", $ids)->delete();
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, "操作失败");
        }

    }

    /**
     * 分类列表
     */
    public function cateIndex()
    {
        $list = model("Cate")->where("header_id", HEADER_ID)->order("create_time desc")->paginate(10);
        $this->assign("list", $list);
        $this->assign("totalNum", model("Cate")->where("header_id", HEADER_ID)->count());
        return $this->fetch();
    }

    /**
     * 编辑商品分类
     */
    public function editCate()
    {
        $cate_id = input("cate_id");
        $data = input("post.");
        $cate = model("Cate")->where("id", $cate_id)->find();
        if (request()->isAjax()) {
            if ($cate) {
                $res = $cate->save($data);
            } else {
                $data["header_id"] = HEADER_ID;
                $res = model("Cate")->save($data);
            }
            if ($res) {
                Cache::rm(HEADER_ID . ":cate");
                exit_json();
            } else {
                exit_json(-1, '保存失败');
            }
        }
        $this->assign("item", $cate);
        return $this->fetch();
    }

    /**
     * 删除分类
     */
    public function delCate()
    {
        $item = model("Cate")->where("id", input("id"))->find();
        if ($item) {
            $p = model("Product")->where("cate_id", input('id'))->find();
            if ($p) {
                exit_json(-1, "当前分类下存在商品，不可删除分类");
            }
            if ($item->delete()) {
                Cache::rm(HEADER_ID . ":cate");
                exit_json();
            } else {
                exit_json(-1, "删除失败");
            }
        } else {
            exit_json(-1, "记录不存在");
        }

    }

    /**
     * 单位列表
     */
    public function unitIndex()
    {
        $list = model("Unit")->where("header_id", HEADER_ID)->order("create_time desc")->paginate(10);
        $this->assign("list", $list);
        $this->assign("totalNum", model("Unit")->where("header_id", HEADER_ID)->count());
        return $this->fetch();
    }

    /**
     * 编辑单位
     */
    public function editUnit()
    {
        $cate_id = input("id");
        $data = input("post.");
        $cate = model("Unit")->where("id", $cate_id)->find();
        if (request()->isAjax()) {
            if ($cate) {
                $res = $cate->save($data);
            } else {
                $data["header_id"] = HEADER_ID;
                $res = model("Unit")->save($data);
            }
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, '保存失败');
            }
        }
        $this->assign("item", $cate);
        return $this->fetch();
    }

    /**
     * 删除单位
     */
    public function delUnit()
    {
        $item = model("Unit")->where("id", input("id"))->find();
        if ($item) {
            if ($item->delete()) {
                exit_json();
            } else {
                exit_json(-1, "删除失败");
            }
        } else {
            exit_json(-1, "记录不存在");
        }

    }

}