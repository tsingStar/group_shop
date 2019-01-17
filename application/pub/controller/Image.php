<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/12/17
 * Time: 11:03
 */

namespace app\pub\controller;


use think\Controller;

class Image extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * @return mixed
     */
    public function image()
    {
        $func = input("callback");
        $this->assign("callback", $func);
        return $this->fetch();
    }


    /**
     * 获取图片库图片
     */
    public function getImageList()
    {
        $page = input("page");
        $list = model("Image")->where("header_id", session("header_id"))->limit($page*20, 20)->select();
        exit_json(1, "请求成功", $list);
    }


    /**
     * 上传文件
     */
    public function uploadFile()
    {
        $file = request()->file("file");
        if ($file) {
            if (is_array($file)) {
                $file_url = [];
                foreach ($file as $item) {
                    $hash = $item->hash();
                    $r = model("Image")->where("header_id", session("header_id"))->where("md5", $hash)->find();
                    if (!$r) {
                        $info = $item->move(__UPLOAD__);
                        $saveName = $info->getSaveName();
                        $path = __URL__ . "/upload/" . $saveName;
                        $file_url[] = [
                            "image_url" => $path,
                            "header_id" => session("header_id"),
                            "md5" => $hash
                        ];
                    }
                }
                if (count($file_url) > 0) {
                    $res = model("Image")->saveAll($file_url);
                } else {
                    $res = true;
                }
            } else {
                $hash = $file->hash();
                $r = model("Image")->where("header_id", session("header_id"))->where("md5", $hash)->find();
                if (!$r) {
                    $info = $file->move(__UPLOAD__);
                    $saveName = $info->getSaveName();
                    $path = __URL__ . "/upload/" . $saveName;
                    $result_url = [
                        "image_url" => $path,
                        "header_id" => session("header_id"),
                        "md5" => $hash
                    ];
                    $res = model("Image")->save($result_url);
                } else {
                    $res = true;
                }
            }
        } else {
            $res = false;
        }
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, "图片上传失败");
        }
    }

    /**
     * 删除图片
     */
    public function delImage()
    {
        $list = model("Image")->whereIn("image_url", input("idstr"))->select();
        foreach ($list as $value) {
            $path = str_replace(__URL__."/upload", __UPLOAD__, $value["image_url"]);
            if (file_exists($path)) {
                @unlink($path);
            }
            $value->delete();
        }
        exit_json();
    }
}