<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/12/19
 * Time: 9:12
 */

namespace app\header\model;


class RBAC
{
    private $accessNode;
    private $menuNode;
    private $menuUrl;
    private $error;
    public function __construct($user_id)
    {
        require_once "../leftmenu.php";
        $this->menuNode = $leftmenu;
        $role_id = db("HeaderRoleUser")->where("user_id", $user_id)->value("role_id");
        $node_url = db("HeaderAccess")->whereIn("role_id", $role_id)->value("node_url");
        $node_url = array_unique($node_url);
        foreach ($this->menuNode as $item){
            $access = $item['navChild'];
            foreach ($item['navChild'] as $key=>$value) {
                $this->menuUrl[] = $value['url'];
                if (!in_array($value['url'], $node_url)) unset($access[$key]);
            }
            if(count($access)>0){
                $this->accessNode[] = [
                    'navName'=>$item["navName"],
                    'navChild'=>$access
                ];
            }
        }
    }

    /**
     * 获取可访问目录
     */
    public function getAccessNode()
    {
        return $this->accessNode;
    }

    /**
     * 判断目录请求是否合法
     * @param string $url 节点信息 index/index
     * @return bool
     */
    public function checkAccess($url)
    {
        if(in_array($this->menuUrl, $url)){
            if(in_array($this->accessNode, $url)){
                return true;
            }else{
                $this->error = "请求不存在";
                return false;
            }
        }else{
            return true;
        }
    }
    




}