<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-29
 * Time: 17:28
 */
class Redis2
{
    private $redis;

    public function __construct($host = '127.0.0.1', $port = 6379)
    {
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
//        $this->redis->auth("ybt666666");
    }

    /**
     * 字符串设置
     * @param $key
     * @param $val
     * @return bool
     */
    public function set($key, $val)
    {
        return $this->redis->set($key, $val);
    }

    /**
     * 获取字符串key对应的值
     * @param $key
     * @return bool|string
     */
    public function get($key)
    {
        return $this->redis->get($key);
    }

    /**
     * 指定键值增加rank数量
     * @param $key
     * @param int $rank
     * @return bool|float|int
     */
    public function incr($key, $rank = 1)
    {
        if ($rank === 1) {
            return $this->redis->incr($key);
        } elseif (is_int($rank) && $rank > 1) {
            return $this->redis->incrBy($key, $rank);
        } elseif (is_float($rank)) {
            return $this->redis->incrByFloat($key, $rank);
        } else {
            return false;
        }
    }

    /**
     * 批量设置key-value字符串
     * @param $arr
     * @return bool
     */
    public function mset($arr)
    {
        return $this->redis->mset($arr);
    }

    /**
     * 返回多个指定key对应的value
     * @param $arr
     * @return array
     */
    public function mget($arr)
    {
        return $this->redis->mget($arr);
    }

    /**
     * 返回指定key对应的字符串的长度
     * @param $key
     * @return int
     */
    public function strlen($key)
    {
        return $this->redis->strlen($key);
    }

    /**
     * 将value 插入到列表 key 的表头
     * @param $key
     * @param $value1
     * @return bool|int
     */
    public function lpush($key, $value1)
    {
        return $this->redis->lPush($key, $value1);
    }

    /**
     * 移除并返回列表 key 的头元素。
     * @param $key
     * @return string
     */
    public function lpop($key)
    {
        return $this->redis->lPop($key);
    }

    /**
     * 返回列表长度
     * @param $key
     * @return int
     */
    public function llen($key)
    {
        return $this->redis->lLen($key);
    }

    /**
     * 获取列表指定区间内容
     */
    public function lrange($key, $start, $stop)
    {
        return $this->redis->lRange($key, $start, $stop);
    }

    /**
     * 移除列表中所有元素
     */
    public function lremAll($key, $val=1)
    {
        return $this->redis->lRem($key, $val, 0);
    }

    /**
     * 将哈希表 key 中的域 field 的值设为 value 。
     * @param $key
     * @param $field
     * @param $value
     * @return bool|int
     */
    public function hset($key, $field, $value)
    {
        return $this->redis->hset($key, $field, $value);
    }

    /**
     * 批量设置哈希表key-value
     * @param $key
     * @param $array
     * @return bool
     */
    public function hmset($key, $array)
    {
        return $this->redis->hMSet($key, $array);
    }

    /**
     * 获取所有字段及值
     * @param $key
     * @return array
     */
    public function hgetAll($key)
    {
        return $this->redis->hGetAll($key);
    }

    /**
     * 获取哈希表中指定key下所有field值
     */
    public function hgetKeys($key)
    {
        return $this->redis->hKeys($key);
    }

    /**
     * 获取哈希表中指定key下所有field对应的域值
     */
    public function hgetVal($key)
    {
        return $this->redis->hVals($key);
    }

    /**
     * 返回哈希表 key 中给定域 field 的值
     * @param $key
     * @param $field
     * @return string
     */
    public function hget($key, $field)
    {
        return $this->redis->hGet($key, $field) !== false ? $this->redis->hGet($key, $field) : 0;
    }

    /**
     * 删除哈希表中指定key中给定域field
     */
    public function hdel($key, $field)
    {
        return $this->redis->hDel($key, $field);
    }

    /**
     * 向集合中添加value
     * @param $key
     * @param $value
     * @return int
     */
    public function sadd($key, $value)
    {
        return $this->redis->sAdd($key, $value);
    }

    /**
     * 移除集合 key 中的一个或多个 member 元素，不存在的 member 元素会被忽略。
     * @param $key
     * @param $value
     * @return int
     */
    public function srem($key, $value)
    {
        return $this->redis->sRem($key, $value);
    }

    /**
     * 有序集合添加
     * @param $key
     * @param $score
     * @param $val
     * @return int
     */
    public function zadd($key, $score, $val)
    {
        return $this->redis->zAdd($key, $score, $val);
    }

    /**
     * 顺序分页获取商品列表
     * @param $key
     * @param $start
     * @param $stop
     * @return array
     */
    public function zrange($key, $start, $stop)
    {
        return $this->redis->zRange($key, $start, $stop);
    }

    /**
     * 逆序分页获取商品列表
     */
    public function zrevrange($key, $start, $stop)
    {
        return $this->redis->zRevRange($key, $start, $stop);
    }

    /**
     * 有序集合中移除指定元素
     */
    public function zrem($key, $val)
    {
        return $this->redis->zRem($key, $val);
    }

    /**
     * 删除指定key
     * @param $key
     * @return int
     */
    public function delKey($key)
    {
        return $this->redis->del($key);
    }

    /**
     * 开启事务代码块
     * @return Redis
     */
    public function muti()
    {
        return $this->redis->multi();
    }
}
