<?php
namespace Core;
class BaseErrors {  
    //错误码
    protected static $_errorCode=[
        5000=>'Return Value Error',
        5001=>'Fatal Error',
        5002=>'Exception',
        5003=>'Notice',        
        5005=>'UserAgent Error',
        5006=>'Db PDO Error',
        5007=>'Sign Error',
        5008=>'Sign Timestamp Invalid',
        5009=>'Sign Secret Invalid',
        5010=>'Oauth Error',
        5998=>'Sys Return Error',
        5999=>'Sys PDO Error',
        ];

    //框架错误处理
    public static function ErrorHandler($code,$result='',$message=''){
        $msg=isset(self::$_errorCode[$code])?self::$_errorCode[$code]:$message;
        $data=['code'=>$code,'message'=>$msg,'data'=>!empty($result)?$result:new \stdClass()];   
        return self::EchoError($data);
    }
    
    //输出错误
    protected static function EchoError($data){
        $log= new \Core\Service\SysLog();
        $log->sysError($data,DEBUG_SYSERROR_TYPE);
        if(DEBUG_SYSERROR){
            header("Content-Type: application/json;charset=utf-8");
            echo json_encode($data);
            exit(0);           
        }
    }
    
    //致命错误
    public static function fatalError() {
        if ($e = error_get_last()) {
            $errorStr = "[".$e['type']."] ". $e['message'] . " in " . $e['file']." on line ".$e['line'] .".";
            self::ErrorHandler(5002, $errorStr, 'Fatal Error');
        }
        exit(0);
    }
    
    //异常处理
    public static function appException($e) {
        self::ErrorHandler(5003, $e->__toString(), 'Exception');
        exit(0);
    }
    
    //错误处理
    public static function appError($errno, $errstr, $errfile, $errline) {
        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:            
            case E_STRICT:
            case E_USER_WARNING:
            case E_USER_NOTICE:
            default:
                $errorStr = "[$errno] $errstr "." in ".$errfile." on line $errline .";
                break;
        }
        self::ErrorHandler(5003, $errorStr);
        exit(0);
    }
    
}
