<?php
namespace Core\Dao\Nosql;
class RedisInit {
    protected $database = null;
     
    public function __construct($config) {
        $this->database=RedisDriver::getInstance();
        $this->database->factory($config);
    }
    
    public function get($key){
        return $this->database->get($key);
    }
    
    public function set($key, $value, $expire=0){
        return $this->database->set($key, $value, $expire);
    }
    
    public function delete($keys){
        return $this->database->delete($keys);
    }
    
    public function expire($key,$expire=0){
        return $this->database->expire($key,$expire);
    }
    
    //有序集合添加一个或多个成员，或者更新已存在成员的分数
    public function zAdd($key,$score,$value,$expire=0){
        return $this->database->zAdd($key,$score,$value,$expire);
    }
    
    //通过索引区间返回有序集合成指定区间内的成员
    public function zRange($key,$start,$stop,$withscrore=false){
        return $this->database->zRange($key,$start,$stop,$withscrore);
    }
    
    //返回有序集中指定分数区间内的成员，分数从高到低排序
    public function zRevRange($key,$start,$stop,$withscrore=false){
        return $this->database->zRevRange($key,$start,$stop,$withscrore);
    }
    
    //移除有序集合中的一个或多个成员
    public function zRem($key,$value){
        return $this->database->zRem($key,$value);
    }
}
