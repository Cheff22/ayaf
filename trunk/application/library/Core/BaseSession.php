<?php
namespace Core;
class BaseSession {
    private static $handler=null;
    private static $ip=null;
    private static $lifetime=null;
    private static $time=null;

    private static function init($handler){
        self::$handler=$handler;
        self::$ip=!empty($_SERVER["REMOTE_ADDR"])?$_SERVER["REMOTE_ADDR"]:'UNKNOW';
        self::$lifetime=ini_get('session.gc_maxlifetime');
        self::$time=time();
    }
    
    private function start(PDO $pdo){
        self::init($pdo);
        session_set_save_handler(
            array(__CLASS__,"open"),
            array(__CLASS__,"close"),
            array(__CLASS__,"read"),
            array(__CLASS__,"write"),
            array(__CLASS__,"destroy"),
            array(__CLASS__,"gc")
        );
        session_start();
    }
    
    public function open($path,$name){
        return true;
    }
    
    public function close(){
        return true;
    }
    
    public function read($PHPSESSID){
        $sql="select * from ts_sys_session where PHPSESSID=?";
        $stmt=self::$handler->prepare($sql);
        $stmt->execute(array($PHPSESSID));
        if(!$result=$stmt->fetch(\PDO::FETCH_ASSOC)){
            return '';
        }
        if(self::$ip != $result['client_ip']){
            self::destroy($PHPSESSID);
            return '';
        }
        
        if(($result["update_time"] + self::$lifetime) < self::$time ){
            self::destroy($PHPSESSID);
            return '';
        }

        return $result['data'];
    }
    
    public function write($PHPSESSID,$data){
        $sql="select * from ts_sys_session where PHPSESSID=?";
        $stmt=self::$handler->prepare($sql);
        $stmt->execute(array($PHPSESSID));
        if($result=$stmt->fetch(\PDO::FETCH_ASSOC)){
            if($result['data'] != $data || self::$time > ($result['update_time']+30)){
                $sql="update session set update_time = ?, data =? where PHPSESSID = ?";
                $stm=self::$handler->prepare($sql);
                $stm->execute(array(self::$time, $data, $PHPSESSID));
            }
        }else{
            if(!empty($data)){
                $sql="insert into session(PHPSESSID, update_time, client_ip, data) values(?,?,?,?)";
                $sth=self::$handler->prepare($sql);
                $sth->execute(array($PHPSESSID, self::$time, self::$ip, $data));
            }
        }
    }
    
    public function destroy($PHPSESSID) {
        $sql="delete from session where PHPSESSID = ?";
        $stmt=self::$handler->prepare($sql);
        $stmt->execute(array($PHPSESSID));
        return true;
    }
    
    public function gc($lifetime){
        
    }
    
}
try{
        $pdo=new PDO("mysql:host=localhost;dbname=xsphpdb", "root", "123456");
}catch(PDOException $e){
        echo $e->getMessage();
}

Session::start($pdo);
