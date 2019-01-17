<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-17
 * Time: 10:14
 */

namespace app\common\model;


use think\Cache;
use think\Exception;
use think\Model;

class User extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取用户基本信息
     */
    public function getUserInfo($open_id)
    {
        if(!$open_id){
            $this->error = "参数错误";
            return false;
        }
        if(!Cache::has($open_id.":user")){
            $user = $this->where("open_id", $open_id)->find();
            if(!$user){
                $this->error = "用户不存在";
                return false;
            }
            Cache::set($open_id.":user", $user->getData());
        }
        return Cache::get($open_id.":user");
    }

    /**
     * 获取团长基本信息
     */
    public function getLeaderInfo($leader_id)
    {
        if(!$leader_id){
            $this->error = "参数错误";
            return false;
        }
        if(!Cache::has($leader_id.":leader")){
            $user = $this->where("id", $leader_id)->find();
            if(!$user){
                $this->error = "用户不存在";
                return false;
            }
            Cache::set($leader_id.":leader", $user->getData());
        }
        return Cache::get($leader_id.":leader");
    }

    /**
     * 校验积分
     */
    public function checkScore($open_id, $score)
    {
        $u = $this->getUserInfo($open_id);
        if($u["score"]<$score){
            throw new Exception("积分不足");
        }
        return true;
    }

}