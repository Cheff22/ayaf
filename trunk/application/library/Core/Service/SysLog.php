<?php
namespace Core\Service;
class SysLog extends \Core\BaseModels {
    //忽略的请求
    protected $_nRequestUri=[
        '/favicon.ico',
    ];
    
    //系统错误日志
    public function sysError($data,$type=1){
        if(!DEBUG_SYSERROR_DB){
            return;
        }
        if($type==1){
            $this->sysErrorDb($data);
        }else{
            $this->sysErrorText($data);
        }
    }
    
    //系统访问日志
    public function sysAccess($data,$type=1){
        if(!DEBUG_SYSERROR_DB){
            return;
        }
        if($type==1){
            $this->sysAccessDb($data);
        }else{
            $this->sysAccessText($data);
        }
    }
       
    //系统错误日志文本
    public function sysErrorText($data=[]){
        $word= json_encode($data);
        $fp = fopen("sys_log_error.txt","a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
    }
    
    //系统访问日志文本
    public function sysAccessText($data=[]){
        $word= json_encode($data);
        $fp = fopen("sys_log_access.txt","a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
    }

    //系统错误日志数据库
    public function sysErrorDb($error){
        if(!isset($error['code']) || !isset($error['message']) ||!isset($error['data'])){
            \Core\BaseErrors::ErrorHandler(5998);
        }
        if(isset($_POST)&&!empty($_POST)){
            $postData=json_encode($_POST);
        }else{
            $postData=isset($_GET)?  json_encode($_GET):'';
        }
        $param['uid']=isset($_SESSION[SESSION_LOGGED_USERID])?$_SESSION[SESSION_LOGGED_USERID]:'';
        $param['ip']=$this->getRealIp();
        $param['user_agent']=isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'';
        $param['request_host']=isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');
        $param['request_uri']=isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'';
        $param['sessionid']=session_id();
        $param['code']=isset($error['code'])?$error['code']:'';
        $param['message']=isset($error['message'])? json_encode($error['message']):'';
        $param['data']=isset($error['data'])?json_encode($error['data']):'';
        $param['postdata']=$postData;
        $param['cdate']=date('Y-m-d H:i:s',time());
        $tmpData=$param;
        $options['table']='sys_log_error';
        if(in_array($param['request_uri'],$this->_nRequestUri)){
            return;
        }
        if($error['code']==5999){
            return;
        }
        $this->db->sysadd($tmpData,$options);
    }
    
    //系统访问日志数据库
    public function sysAccessDb($error){
        if(!isset($error['code']) || !isset($error['message']) ||!isset($error['data'])){
            \Core\BaseErrors::ErrorHandler(5998);
        }
        if(isset($_POST)&&!empty($_POST)){
            $postData=json_encode($_POST);
        }else{
            $postData=isset($_GET)?  json_encode($_GET):'';
        }
        $param['uid']=isset($_SESSION[SESSION_LOGGED_USERID])?$_SESSION[SESSION_LOGGED_USERID]:'';
        $param['ip']=$this->getRealIp();
        $param['user_agent']=isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'';
        $param['request_host']=isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');
        $param['request_uri']=isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'';
        $param['sessionid']=session_id();
        $param['code']=isset($error['code'])?$error['code']:'';
        $param['message']=isset($error['message'])? json_encode($error['message']):'';
        $param['data']=isset($error['data'])?json_encode($error['data']):'';
        $param['postdata']=$postData;
        $param['cdate']=date('Y-m-d H:i:s',time());
        $tmpData=$param;
        $options['table']='sys_log_access';
        if(in_array($param['request_uri'],$this->_nRequestUri)){
            return;
        }
        if($error['code']==5999){
            return;
        }
        $this->db->sysadd($tmpData,$options);
    }
    
}