<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\cache\driver;

use think\cache\Driver;

/**
 * Redis缓存驱动，适合单机部署、有前端代理实现高可用的场景，性能最好
 * 有需要在业务层实现读写分离、或者使用RedisCluster的需求，请使用Redisd驱动
 *
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 * @author    尘缘 <130775@qq.com>
 */
class Redis extends Driver
{
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => 'dxss_',
    ];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $this->handler = new \Redis;
        if ($this->options['persistent']) {
            $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
        } else {
            $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
        }

        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
        }
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name)
    {
        return $this->handler->get($this->getCacheKey($name)) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $value = $this->handler->get($this->getCacheKey($name));
        if (is_null($value) || false === $value) {
            return $default;
        }

        try {
            $result = 0 === strpos($value, 'think_serialize:') ? unserialize(substr($value, 16)) : $value;
        } catch (\Exception $e) {
            $result = $default;
        }

        return $result;
    }

    /**
     * 写入缓存
     * @access public
     * @param string            $name 缓存变量名
     * @param mixed             $value  存储数据
     * @param integer|\DateTime $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp() - time();
        }
        if ($this->tag && !$this->has($name)) {
            $first = true;
        }
        $key   = $this->getCacheKey($name);
        $value = is_scalar($value) ? $value : 'think_serialize:' . serialize($value);
        if ($expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        isset($first) && $this->setTagItem($key);
        return $result;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param  string    $name 缓存变量名
     * @param  int       $step 步长
     * @return false|int
     */
    public function inc($name, $step = 1)
    {
        $key = $this->getCacheKey($name);

        return $this->handler->incrby($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param  string    $name 缓存变量名
     * @param  int       $step 步长
     * @return false|int
     */
    public function dec($name, $step = 1)
    {
        $key = $this->getCacheKey($name);

        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name)
    {
        return $this->handler->delete($this->getCacheKey($name));
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null)
    {
        if ($tag) {
            // 指定标签清除
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                $this->handler->delete($key);
            }
            $this->rm('tag_' . md5($tag));
            return true;
        }
        return $this->handler->flushDB();
    }

    ###########################以下为扩展 by wh ##########################################3

    /**
     * Returns the size of a list identified by Key. If the list didn't exist or is empty,
     * the command returns 0. If the data type identified by Key is not a list, the command return FALSE.
     *
     * @param   string  $key
     * @return  int     The size of the list identified by Key exists.
     * bool FALSE if the data type identified by Key is not list
     * @link    http://redis.io/commands/llen
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->lLen('key1');       // 3
     * $redis->rPop('key1');
     * $redis->lLen('key1');       // 2
     * </pre>
     */
    public function lLen($name){
        $key = $this->getCacheKey($name);
        return $this->handler->llen($key);
    }

    /**
     * Return the specified element of the list stored at the specified key.
     * 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     * Return FALSE in case of a bad index or a key that doesn't point to a list.
     * @param string    $key
     * @param int       $index
     * @return String the element at this index
     * Bool FALSE if the key identifies a non-string data type, or no value corresponds to this index in the list Key.
     * @link    http://redis.io/commands/lindex
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->lGet('key1', 0);     // 'A'
     * $redis->lGet('key1', -1);    // 'C'
     * $redis->lGet('key1', 10);    // `FALSE`
     * </pre>
     */
    public function lIndex($key, $index){
        $name = $this->getCacheKey($key);
        return $this->handler->lIndex($name, $index);
    }

    /**
     * Adds the string values to the tail (right) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
     *
     * @param   string  $key
     * @param   string  $value1 String, value to push in key
     * @param   string  $value2 Optional
     * @param   string  $valueN Optional
     * @return  int     The new length of the list in case of success, FALSE in case of Failure.
     * @link    http://redis.io/commands/rpush
     * @example
     * <pre>
     * $redis->rPush('l', 'v1', 'v2', 'v3', 'v4');    // int(4)
     * var_dump( $redis->lRange('l', 0, -1) );
     * //// Output:
     * // array(4) {
     * //   [0]=> string(2) "v1"
     * //   [1]=> string(2) "v2"
     * //   [2]=> string(2) "v3"
     * //   [3]=> string(2) "v4"
     * // }
     * </pre>
     */
    public function rPush( $key, $value1, $value2 = null, $valueN = null ) {
        $name = $this->getCacheKey($key);
        if ($value2 !== null){
            if($valueN !== null){
                return $this->handler->rpush($name, $value1, $value2, $valueN);
            }else{
                return $this->handler->rpush($name, $value1, $value2);
            }
        }else{
            return $this->handler->rpush($name, $value1);
        }
    }

    /**
     * Returns the keys that match a certain pattern.
     *
     * @param   string  $pattern pattern, using '*' as a wildcard.
     * @return  array   of STRING: The keys that match a certain pattern.
     * @link    http://redis.io/commands/keys
     * @example
     * <pre>
     * $allKeys = $redis->keys('*');   // all keys will match this.
     * $keyWithUserPrefix = $redis->keys('user*');
     * </pre>
     */
    public function keys( $pattern ) {
        $name = $this->getCacheKey($pattern);
        $oriKeys = $this->handler->keys($name);
        $key = [];
        foreach($oriKeys as $key){
            $key = str_replace($this->options['prefix'], "", $key);
            $keys[] = $key;
        }
        return $key;
    }

    /**
     * Returns the length of a hash, in number of items
     *
     * @param   string  $key
     * @return  int     the number of items in a hash, FALSE if the key doesn't exist or isn't a hash.
     * @link    http://redis.io/commands/hlen
     * @example
     * <pre>
     * $redis->delete('h')
     * $redis->hSet('h', 'key1', 'hello');
     * $redis->hSet('h', 'key2', 'plop');
     * $redis->hLen('h'); // returns 2
     * </pre>
     */
    public function hLen( $key ) {
        $name = $this->getCacheKey($key);
        return $this->handler->hLen($name);
    }

    /**
     * Watches a key for modifications by another client. If the key is modified between WATCH and EXEC,
     * the MULTI/EXEC transaction will fail (return FALSE). unwatch cancels all the watching of all keys by this client.
     * @param string | array $key: a list of keys
     * @return void
     * @link    http://redis.io/commands/watch
     * @example
     * <pre>
     * $redis->watch('x');
     * // long code here during the execution of which other clients could well modify `x`
     * $ret = $redis->multi()
     *          ->incr('x')
     *          ->exec();
     * // $ret = FALSE if x has been modified between the call to WATCH and the call to EXEC.
     * </pre>
     */
    public function watch( $key ) {
        $name = $this->getCacheKey($key);
        $this->handler->watch($name);
    }

    /**
     * Verify if the specified member exists in a key.
     *
     * @param   string  $key
     * @param   string  $hashKey
     * @return  bool    If the member exists in the hash table, return TRUE, otherwise return FALSE.
     * @link    http://redis.io/commands/hexists
     * @example
     * <pre>
     * $redis->hSet('h', 'a', 'x');
     * $redis->hExists('h', 'a');               //  TRUE
     * $redis->hExists('h', 'NonExistingKey');  // FALSE
     * </pre>
     */
    public function hExists( $key, $hashKey ) {
        $name = $this->getCacheKey($key);
        return $this->handler->hExists($name, $hashKey);
    }

    /**
     * Enter and exit transactional mode.
     *
     * @param int Redis::MULTI|Redis::PIPELINE
     * Defaults to Redis::MULTI.
     * A Redis::MULTI block of commands runs as a single transaction;
     * a Redis::PIPELINE block is simply transmitted faster to the server, but without any guarantee of atomicity.
     * discard cancels a transaction.
     * @return Redis returns the Redis instance and enters multi-mode.
     * Once in multi-mode, all subsequent method calls return the same object until exec() is called.
     * @link    http://redis.io/commands/multi
     * @example
     * <pre>
     * $ret = $redis->multi()
     *      ->set('key1', 'val1')
     *      ->get('key1')
     *      ->set('key2', 'val2')
     *      ->get('key2')
     *      ->exec();
     *
     * //$ret == array (
     * //    0 => TRUE,
     * //    1 => 'val1',
     * //    2 => TRUE,
     * //    3 => 'val2');
     * </pre>
     */
    public function multi() {
        $this->handler->multi();
    }

    /**
     * Adds a value to the hash stored at key. If this value is already in the hash, FALSE is returned.
     *
     * @param string $key
     * @param string $hashKey
     * @param string $value
     * @return int
     * 1 if value didn't exist and was added successfully,
     * 0 if the value was already present and was replaced, FALSE if there was an error.
     * @link    http://redis.io/commands/hset
     * @example
     * <pre>
     * $redis->delete('h')
     * $redis->hSet('h', 'key1', 'hello');  // 1, 'key1' => 'hello' in the hash at "h"
     * $redis->hGet('h', 'key1');           // returns "hello"
     *
     * $redis->hSet('h', 'key1', 'plop');   // 0, value was replaced.
     * $redis->hGet('h', 'key1');           // returns "plop"
     * </pre>
     */
    public function hSet( $key, $hashKey, $value ) {
        $name = $this->getCacheKey($key);
        return $this->handler->hSet($name, $hashKey, $value);
    }

    /**
     * Sets an expiration date (a timeout) on an item.
     *
     * @param   string  $key    The key that will disappear.
     * @param   int     $ttl    The key's remaining Time To Live, in seconds.
     * @return  bool    TRUE in case of success, FALSE in case of failure.
     * @link    http://redis.io/commands/expire
     * @example
     * <pre>
     * $redis->set('x', '42');
     * $redis->setTimeout('x', 3);  // x will disappear in 3 seconds.
     * sleep(5);                    // wait 5 seconds
     * $redis->get('x');            // will return `FALSE`, as 'x' has expired.
     * </pre>
     */
    public function expire( $key, $ttl ) {
        $name = $this->getCacheKey($key);
        return $this->handler->expire($name, $ttl);
    }

    /**
     * @see multi()
     * @link    http://redis.io/commands/exec
     * @return  array
     */
    public function exec( ) {
        return $this->handler->exec();
    }

    /**
     * Verify if the specified key exists.
     *
     * @param   string $key
     * @return  bool  If the key exists, return TRUE, otherwise return FALSE.
     * @link    http://redis.io/commands/exists
     * @example
     * <pre>
     * $redis->set('key', 'value');
     * $redis->exists('key');               //  TRUE
     * $redis->exists('NonExistingKey');    // FALSE
     * </pre>
     */
    public function exists( $key ) {
        $name = $this->getCacheKey($key);
        return $this->handler->exists($name);
    }

    /**
     * Removes a values from the hash stored at key.
     * If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     *
     * @param   string  $key
     * @param   string  $hashKey1
     * @param   string  $hashKey2
     * @param   string  $hashKeyN
     * @return  int     Number of deleted fields
     * @link    http://redis.io/commands/hdel
     * @example
     * <pre>
     * $redis->hMSet('h',
     *               array(
     *                    'f1' => 'v1',
     *                    'f2' => 'v2',
     *                    'f3' => 'v3',
     *                    'f4' => 'v4',
     *               ));
     *
     * var_dump( $redis->hDel('h', 'f1') );        // int(1)
     * var_dump( $redis->hDel('h', 'f2', 'f3') );  // int(2)
     * s
     * var_dump( $redis->hGetAll('h') );
     * //// Output:
     * //  array(1) {
     * //    ["f4"]=> string(2) "v4"
     * //  }
     * </pre>
     */
    public function hDel( $key, $hashKey1, $hashKey2 = null, $hashKeyN = null ) {
        $name = $this->getCacheKey($key);
        if ($hashKey2 !== null){
            if($hashKeyN !== null){
                return $this->handler->rpush($name, $hashKey1, $hashKey2, $hashKeyN);
            }else{
                return $this->handler->rpush($name, $hashKey1, $hashKey2);
            }
        }else{
            return $this->handler->rpush($name, $hashKey1);
        }
    }

    /**
     * Returns the whole hash, as an array of strings indexed by strings.
     *
     * @param   string  $key
     * @return  array   An array of elements, the contents of the hash.
     * @link    http://redis.io/commands/hgetall
     * @example
     * <pre>
     * $redis->delete('h');
     * $redis->hSet('h', 'a', 'x');
     * $redis->hSet('h', 'b', 'y');
     * $redis->hSet('h', 'c', 'z');
     * $redis->hSet('h', 'd', 't');
     * var_dump($redis->hGetAll('h'));
     *
     * // Output:
     * // array(4) {
     * //   ["a"]=>
     * //   string(1) "x"
     * //   ["b"]=>
     * //   string(1) "y"
     * //   ["c"]=>
     * //   string(1) "z"
     * //   ["d"]=>
     * //   string(1) "t"
     * // }
     * // The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     */
    public function hGetAll( $key ) {
        $name = $this->getCacheKey($key);
        return $this->handler->hGetAll($name);
    }

    /**
     * Increments the value of a member from a hash by a given amount.
     *
     * @param   string  $key
     * @param   string  $hashKey
     * @param   int     $value (integer) value that will be added to the member's value
     * @return  int     the new value
     * @link    http://redis.io/commands/hincrby
     * @example
     * <pre>
     * $redis->delete('h');
     * $redis->hIncrBy('h', 'x', 2); // returns 2: h[x] = 2 now.
     * $redis->hIncrBy('h', 'x', 1); // h[x] ← 2 + 1. Returns 3
     * </pre>
     */
    public function hIncrBy( $key, $hashKey, $value ) {
        $name = $this->getCacheKey($key);
        return $this->handler->hIncrBy($name, $hashKey, $value);
    }

    /**
     * Set the string value in argument as value of the key, with a time to live.
     *
     * @param   string  $key
     * @param   int     $ttl
     * @param   string  $value
     * @return  bool    TRUE if the command is successful.
     * @link    http://redis.io/commands/setex
     * @example $redis->setex('key', 3600, 'value'); // sets key → value, with 1h TTL.
     */
    public function setex( $key, $ttl, $value ) {
        $name = $this->getCacheKey($key);
        return $this->handler->setex($name, $ttl, $value);
    }

    /**
     * Gets a value from the hash stored at key.
     * If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     *
     * @param   string  $key
     * @param   string  $hashKey
     * @return  string  The value, if the command executed successfully BOOL FALSE in case of failure
     * @link    http://redis.io/commands/hget
     */
    public function hGet($key, $hashKey) {
        $name = $this->getCacheKey($key);
        return $this->handler->hGet($name, $hashKey);
    }

    
}
