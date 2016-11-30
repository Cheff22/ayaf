<?php
namespace Core;
class BaseCache {
    const RES_SUCCESS = 0;
    const GenerateData = 1;
    const NotFound = 2;

    public function __construct($memcached) {
        $this->mc = $memcached;
    }

    public function get($key) {

        $data = $this->mc->get($key);
        // check if cache exists
        if ($this->mc->getResultCode() === Memcached::RES_SUCCESS) {
            $this->_setResultCode(Cache::RES_SUCCESS);
            return $data;
        }

        // add locking
        $this->mc->add('lock:' . $key, 'locked', 20);
        if ($this->mc->getResultCode() === Memcached::RES_SUCCESS) {
            $this->_setResultCode(Cache::GenerateData);
            return false;
        }
        $this->_setResultCode(Cache::NotFound);
        return false;
    }

    private function _setResultCode($code){
        $this->code = $code;
    }

    public function getResultCode(){
        return $this->code;
    }

    public function set($key, $data, $expiry){
        $this->mc->set($key, $data, $expiry);
    }
}

$cache = new Cache($mc);
$data = $cache->get('cached_key');

switch($cache->getResultCode()){
    case Cache::RES_SUCCESS:
        // ...
    break;
    case Cache::GenerateData:
        // generate data ...
        $cache->set('cached_key', generateData(), 30);
    break;
    case Cache::NotFound:
       // not found ...
    break;
}


