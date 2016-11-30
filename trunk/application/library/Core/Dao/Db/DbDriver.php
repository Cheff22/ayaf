<?php
namespace Core\Dao\Db;
class DbDriver{
    private static $_instance = NULL;
    protected $_connected = false;      // 是否已经连接数据库
    protected $_dbArchitecture=0;        //数据库架构类型
    protected $_dbDriver = '';            //数据库驱动类型
    protected $_dbConfig = '';            //数据库当前配置
    protected $_linkID = null;          //数据库当前连接ID
    protected $_linkIDArray = [];  //数据库当前连接ID数组
    protected $_pdoStatement = '';      //PDOStatement类
    protected $_queryStr = '';          //SQL语句
    protected $_queryNumRows = 0;       //影响行数
    protected $_lastInsID = null;       //最后插入ID

    private function __construct() {}
    public function __destruct() {
        if ($this->_pdoStatement) {
            $this->free();
        }
        $this->close();
    }
    public static function getInstance() {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self ();
        }
        return self::$_instance;
    }
    
    public function factory($config,$name='',$connection='') {
        if(empty($config)){
            if(!isset($config['DRIVER'])||!isset($config['ARCHITECTURE'])||!isset($config['HOST'])||!isset($config['NAME'])||!isset($config['USER'])||!isset($config['PWD'])||!isset($config['PORT'])){//!isset($config['PREFIX'])||!isset($config['CHARSET'])
                \Core\BaseErrors::ErrorHandler(5006);
            }
        }
        $this->_dbDriver=$config['DRIVER'];
        $this->_dbConfig=$config;
        $this->_dbArchitecture=$config['ARCHITECTURE'];
    }

    public function getLastInsID() {
        return $this->_lastInsID;
    }

    protected function initConnect($master = true) {
        if($this->_dbArchitecture==1){
            $this->_linkID = $this->multiConnect($master);
        }elseif(empty($this->_linkID)){
            $this->_linkID = $this->connect();
        }
    }
    
    protected function multiConnect($master=false) {
        static $config=[];
        if(empty($config)){
            $config=$this->_dbConfig;
        }
        if($master){//写操作
            $config['HOST']=$config['MASETER_HOST'];
            $num=10;
        }else{//读操作
            //$config['HOST']=$config['SLAVE_HOST'];
            $slaves=explode(',', $config['SLAVE_HOST']);
            $cnt=count($slaves);
            $slavesId=$cnt>1?rand(0,$cnt-1):0;
            $config['HOST']=$slaves[$slavesId];
            $num=rand(11,30);
        }
        return $this->connect($config,$num);
    }
    
    protected function connect($config = '', $linkNum = 0) {
        if (!isset($this->_linkIDArray[$linkNum])) {
            if (empty($config)){
                $config = $this->_dbConfig;
            }
            $dsn = $this->_dbDriver . ':host=' . $config ['HOST'] . ($config ['PORT'] ? ';port=' . $config['PORT'] : '') . ';dbname=' . $config ['NAME'];
            $this->_linkIDArray[$linkNum] = new \PDO($dsn, $config ['USER'], $config ['PWD']);            
            //$this->_dbType = strtoupper($this->_dbDriver);
            //$this->_linkIDArray[$linkNum]->exec('SET NAMES ' . $config ['CHARSET']);
            unset($this->_dbConfig);
        }
        return $this->_linkIDArray[$linkNum];
    }

    public function free() {
        $this->_pdoStatement = null;
    }

    public function close() {
        $this->_linkID = null;
    }

    public function error() {
        if ($this->_pdoStatement) {
            $error = $this->_pdoStatement->errorInfo();
            //print_r($error);
            //echo "<br/>==SQL===:".$this->_queryStr;
            $error['error_sql']=$this->_queryStr;
            \Core\BaseErrors::ErrorHandler(5006,$error);
        }
    }
    
    public function getQueryStr($str,$param){
        $tmp=[];
        for($i=0;$i<count($param);$i++){
            $tmp[]='/\?/';
        }
        $this->_queryStr=preg_replace($tmp,$param,$str,1);
        return $this->_queryStr;
    }

    public function query($str,$param=[],$fetchType='FETCH_ASSOC'){
        $this->initConnect(false);
        if (!$this->_linkID){ return false;}
        if (!empty($this->_pdoStatement)) {$this->free();}
        $this->_pdoStatement = $this->_linkID->prepare($str);
        $result = $this->_pdoStatement->execute($param);
        if (false !== $result) {
            switch ($fetchType){
                case 'FETCH_ASSOC':
                    $fetchType=\PDO::FETCH_ASSOC;
                    break;
                case 'FETCH_NUM':
                    $fetchType=\PDO::FETCH_NUM;
                    break;
                case 'FETCH_OBJ':
                    $fetchType=\PDO::FETCH_OBJ;
                    break;
                case 'FETCH_BOTH':
                    $fetchType=\PDO::FETCH_BOTH;
                    break;
                default :
                    $fetchType=\PDO::FETCH_ASSOC;
                    break;
            }
            $result = $this->_pdoStatement->fetchAll($fetchType);
        }elseif(false === $result){
            $this->getQueryStr($str, $param);
            $this->error();
        }
        return $result;
    }
    
    public function execute($str,$param=[]) {
        $this->initConnect(true);
        if (!$this->_linkID) {return false;}
        if (!empty($this->_pdoStatement)) {$this->free();}
        $this->_pdoStatement = $this->_linkID->prepare($str);
        $result = $this->_pdoStatement->execute($param);
        if( false !== $result){
            $this->_queryNumRows = $this->_pdoStatement->rowCount();
            if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->_lastInsID = $this->_linkID->lastInsertId();
            }
            return $this->_queryNumRows;
        }elseif(false === $result){
            $this->getQueryStr($str, $param);
            $this->error();
            return false;
        }
    }
    
    public function sysexecute($str,$param=[]) {
        $this->initConnect(true);
        if (!$this->_linkID) {return false;}
        if (!empty($this->_pdoStatement)) {$this->free();}
        $this->_pdoStatement = $this->_linkID->prepare($str);
        $result = $this->_pdoStatement->execute($param);
        if( false !== $result){
            $this->_queryNumRows = $this->_pdoStatement->rowCount();
            if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->_lastInsID = $this->_linkID->lastInsertId();
            }
            return $this->_queryNumRows;
        }elseif(false === $result){
            $this->getQueryStr($str, $param);
            $error = $this->_pdoStatement->errorInfo();
            $error['error_sql']=$this->_queryStr;
            \Core\BaseErrors::ErrorHandler(5999,$error);
            return false;
        }
    }
    
    public function startTrans() {
        $this->initConnect(true);
        if (!$this->_linkID){
            return false;
        }
        if($this->_linkID->inTransaction()){
            $this->_linkID->commit();
        }
        $this->_linkID->beginTransaction();
        return;
    }

    public function commit() {
        $result = $this->_linkID->commit();
        if (!$result) {
            $this->error();
            return false;
        }
        return true;
    }

    public function rollback() {
        $result = $this->_linkID->rollback();
        if (!$result) {
            $this->error();
            return false;
        }
        return true;
    }

}
