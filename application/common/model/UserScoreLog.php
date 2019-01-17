<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2019/1/8
 * Time: 9:48
 */

namespace app\common\model;


use think\Model;

class UserScoreLog extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }
    protected $pk = "score_id";



}