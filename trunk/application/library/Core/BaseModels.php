<?php
namespace Core;
class BaseModels{
    private $_dbConfig=[];
    private $_dbName='';
    private $_dbConnection='';
    private $_nosqlConfig=[];
    protected $db=null;
    protected $cache=null;
    protected $nosql=null;
    
    protected $_returnCode=[
        200=>'Success',
        201=>'Not Have Any Data',
        202=>'Already Done',
        400=>'Failed',
    ];
       
    /*public function __call($name, $arguments) {
        $this->find($options);$this->db->find($options);
    }*/
    
    public function __construct($config=array(),$name = '', $connection = '') {
        $errorCode=\Yaf_Registry::get("returncode")->returncode->toArray();
        foreach ($errorCode as $k=>$v){
            $this->_returnCode[$k]=$v;
        }
        $this->_dbConfig = !empty($config) ? $config : \Yaf_Registry::get("dbconfig")->db->toArray();
        $this->_dbName = !empty($name) ? $name : '';
        $this->_dbConnection = !empty($connection) ? $connection : '';
        $this->initDb($this->_dbConfig,$this->_dbName,$this->_dbConnection);
        $this->_nosqlConfig =\Yaf_Registry::get("redisconfig")->nosql->toArray();
        $this->initNosql($this->_nosqlConfig);
    }
    
    public function initDb($config,$name = '', $connection = ''){
        if($this->db == NULL){
            $this->db= new \Core\Dao\Db\DbInit($config,$name,$connection);
        }
        return $this->db;
    }
    
    public function initCache(){
        
    }
    
    public function initNosql($nosqlConfig){
        if($this->nosql == NULL){
            $this->nosql= new \Core\Dao\Nosql\RedisInit($nosqlConfig);
        }
        return $this->nosql;
    }
    
    //返回结果
    public function returnResult($code,$data=[]){
        $result=['code'=>$code,'message'=>$this->_returnCode[$code],'data'=>$data];
        $log= new \Core\Service\SysLog();
        $log->sysAccess($result,DEBUG_SYSERROR_TYPE);      
        return $result;    
    }        
    
    //验证是否是手机号码
    public function isCellphone($cellphone){
        //134—139、150—152、158、159、182,130—132、155、156,147、157、188,186,133、153,189、180、181,178，177，176
        $status=preg_match('/^1[34578]\d{9}$/', $cellphone);
        return $status ? TRUE:FALSE;
    }
    
    //验证是否是规则用户名
    public function isRegUname($uname){
        $status=preg_match('/^[A-Za-z0-9]{3,20}$/', $uname);
        return $status ? TRUE:FALSE;
    }
    
    //验证邮箱是否合法
    public function isEmail($email){
        $status = preg_match('/^\w+[(\w\.?)|(\.?\w)|(\.?\w\.?)|(\w\-?)]{0,28}\w+@\w+(\-|\w)*\.\w+([.]\w+)*$/', $email);
        return $status ? TRUE:FALSE;
    }
    
    //生成字母和数字随机数
    public function random_str($length){
        //生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
        $arr = array_merge(range(0, 9), range('a', 'z'));
        $str = '';
        $arr_len = count($arr);
        for ($i = 0; $i < $length; $i++){
            $rand = mt_rand(0, $arr_len-1);
            $str.=$arr[$rand];
        }
        return $str;
    }
      
    //base62编码
    public function base62($i){
        if($i<0) return '';
        $ch = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $a='';
        do{
            $a=$ch[bcmod($i,62)].$a;
            $i=bcdiv($i,62,0);      
        }while($i>0);
        return $a;
    }
    
    //毫秒数
    public function getMicrotime(){
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec)*10000;
    }
    
    //获取ip
    protected function getRealIp(){
        $realip='';
        if(isset($_SERVER)){
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
                $realip=$_SERVER['HTTP_X_FORWARDED_FOR'];
            }else if(isset($_SERVER['HTTP_CLIENT_IP'])){
                $realip=$_SERVER['HTTP_CLIENT_IP'];
            }else{
                $realip=isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'127.0.0.1';
            }
        }else{
            if(getenv('HTTP_X_FORWARDED_FOR')){
                $realip=getenv('HTTP_X_FORWARDED_FOR');
            }else if(getenv('HTTP_CLIENT_IP')){
                $realip=getenv('HTTP_CLIENT_IP');
            }else{
                $realip=getenv('REMOTE_ADDR');
            }
        }
        return $realip;
    }
    
    /*    
    //计算字符串长度
    public function countWords($var){
        $count=0;
        if(mb_detect_encoding($var)=='ASCII'){
            $var=urldecode($var);
        }
        $strLen = mb_strlen($var,'UTF-8');
        for($i=0; $i<$strLen; $i++) {
            $oneChar = mb_substr($var, $i, 1,'UTF-8');
            $count+=preg_match("/^[\x7f-\xff]+$/", $oneChar)?1:0.5;	
        }
        return $count;
    }
    
    //限制字数
    public function limitWordsCount($var,$num){
        $str='';
        $count=0;
        $strLen = mb_strlen($var); 
        for($i=0; $i<$strLen; $i++) {
            if($count>=$num){break;}
            $oneChar = mb_substr($var, $i, 1);
            $count+=preg_match("/^[\x7f-\xff]+$/", $oneChar)?1:0.5;	
            $str.=$oneChar;
        }
        return $str;
    }
    */
}

