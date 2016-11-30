<?php
namespace Core\Dao\Nosql;
class RedisDriver {
    private static $_instance = NULL;
    protected $_connected = false;      // 是否已经连接数据库
    protected $_dbDrive='';
    protected $_dbConfig=array();
    protected $_linkID = null;          //数据库当前连接ID
    protected $_linkIDArray = array();  //数据库当前连接ID数组
    
    private function __construct() {}
    public function __destruct() {
        $this->close();
    }
    public static function getInstance() {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self ();
        }
        return self::$_instance;
    }
    
    public function close() {
        if($this->_linkID){
            $this->_linkID ->close();
        }
    }
    
    public function factory($config) {
        if(empty($config)){
            if(!isset($config['DRIVER'])||!isset($config['ARCHITECTURE'])||!isset($config['HOST'])||!isset($config['AUTH'])||!isset($config['PORT'])){
                \Core\BaseErrors::SysErrorDbConfigError();
            }
        }
        $this->_dbDriver=$config['DRIVER'];
        $this->_dbConfig=$config;
        $this->_dbArchitecture=$config['ARCHITECTURE'];
    }
    
    protected function initConnect($master = true) {
        if($this->_dbArchitecture==1){
            $this->_linkID = $this->multiConnect($master);
        }elseif(empty($this->_linkID)){
            $this->_linkID = $this->connect();
        }
    }
    
    protected function multiConnect($master=false) {
        static $config=array();
        if(empty($config)){
            $config=$this->_dbConfig;
        }
        if($master){//写操作
            $config['HOST']=$config['MASETER_HOST'];
            $num=10;
        }else{//读操作
            $config['HOST']=$config['SLAVE_HOST'];
            $num=rand(11,30);
        }
        return $this->connect($config,$num);
    }
    
    protected function connect($config = '', $linkNum = 0) {
        if (!isset($this->_linkIDArray[$linkNum])) {
            if (empty($config)){
                $config = $this->_dbConfig;
            }
            $this->_linkIDArray[$linkNum] = new \Redis();
            $this->_linkIDArray[$linkNum]->pconnect($config['HOST'],$config['PORT']);
            if(!empty($config['AUTH'])){
                $this->_linkIDArray[$linkNum]->auth($config['AUTH']);
            }
            //TODOtrycatch
            unset($this->_dbConfig);
        }
        return $this->_linkIDArray[$linkNum];
    }
    
    public function get($key){
        $this->initConnect(false);
        if (!$this->_linkID){ return false;}
        $result=$this->_linkID->get($key);
        return $result;
    }
    
    public function set($key, $value, $expire=0){
        $this->initConnect(false);
        if (!$this->_linkID){ return false;}
        $result=$this->_linkID->set($key,$value,$expire);
        return $result;
    }
    
    public function delete($keys){
        $this->initConnect(false);
        if (!$this->_linkID){ return false;}
        $result=$this->_linkID->delete($keys);
        return $result;
    }
    
    public function expire($key,$expire=0){
        $this->initConnect(false);
        if (!$this->_linkID){ return false;}
        $result=$this->_linkID->expire($key,$expire);
        return $result;
    }
    
    public function zAdd($key,$score,$value,$expire=0){
        $this->initConnect(false);
        if (!$this->_linkID){ return false;}
        $result=$this->_linkID->zAdd($key,$score,$value);
        return $result;
    }
    
    public function zRange($key,$start,$stop,$withscrore=false){
        $this->initConnect(false);
        if (!$this->_linkID){ return false;}
        $result=$this->_linkID->zRange($key,$start,$stop,$withscrore);
        return $result;
    }
    
    public function zRevRange($key,$start,$stop,$withscrore=false){
        $this->initConnect(false);
        if (!$this->_linkID){ return false;}
        $result=$this->_linkID->zRevRange($key,$start,$stop,$withscrore);
        return $result;
    }
    
    public function zRem($key,$value){
        $this->initConnect(false);
        if (!$this->_linkID){ return false;}
        $result=$this->_linkID->zRem($key,$value);
        return $result;
    }
    
}
