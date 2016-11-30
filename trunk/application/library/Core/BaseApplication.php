<?php
include_once 'BaseErrors.php';
class BaseApplication {
    private $__application='';
    private $__dispatcher='';

    private function _initConfig(){
        error_reporting(0);
        register_shutdown_function(['Core\BaseErrors','fatalError']);
        set_error_handler(['Core\BaseErrors','appError']);
        set_exception_handler(['Core\BaseErrors','appException']);
       
        $this->__application = new \Yaf_Application(SITE_PATH . "/conf/application.ini");
        $dispatcher= $this->__application->getDispatcher();
        \Yaf_Registry::set("sysconfig",\Yaf_Application::app()->getConfig());
        define('SESSION_REDIS',\Yaf_Registry::get("sysconfig")->sys->session->redis);
        define('SESSION_TIME',\Yaf_Registry::get("sysconfig")->sys->session->time);
        define('ENVIRONMENT',\Yaf_Registry::get("sysconfig")->sys->environment);
        \Yaf_Registry::set("returncode",new \Yaf_Config_Ini('conf/errorcode.ini', 'returncode'));
        \Yaf_Registry::set("routes",new \Yaf_Config_Ini('conf/routes.ini', 'routes'));
        \Yaf_Registry::set("webroutes",new \Yaf_Config_Ini('conf/routes.ini', 'webroutes'));
        \Yaf_Registry::set("waproutes",new \Yaf_Config_Ini('conf/routes.ini', 'waproutes'));
        if(ENVIRONMENT==1){
            \Yaf_Registry::set("dbconfig",new \Yaf_Config_Ini('conf/db.ini', 'production'));
            \Yaf_Registry::set("redisconfig",new \Yaf_Config_Ini('conf/redis.ini', 'production'));
            \Yaf_Registry::set("payconfig",new \Yaf_Config_Ini('conf/pay.ini', 'production'));
            define("SITE_URL", \Yaf_Registry::get("sysconfig")->sys->url->production);
        }else{
            \Yaf_Registry::set("dbconfig",new \Yaf_Config_Ini('conf/db.ini', 'development'));
            \Yaf_Registry::set("redisconfig",new \Yaf_Config_Ini('conf/redis.ini', 'development'));
            \Yaf_Registry::set("payconfig",new \Yaf_Config_Ini('conf/pay.ini', 'development'));
            define("SITE_URL", \Yaf_Registry::get("sysconfig")->sys->url->development);
        }
        preg_match('/[^.]+\.[^.]+$/',SITE_URL, $matches);
        define('DOMAIN',$matches[0]);
        if(SESSION_REDIS){
            $host=\Yaf_Registry::get("redisconfig")->nosql->HOST;
            $auth=\Yaf_Registry::get("redisconfig")->nosql->AUTH;
            if(!empty($auth)){
                $link="tcp://$host:6379?auth=$auth";
            }else{
                $link="tcp://$host:6379";
            }
            ini_set('session.save_handler','redis');
            ini_set('session.save_path',$link);
            ini_set('session.cookie_domain', '.'.DOMAIN);
            ini_set('session.gc_maxlifetime',SESSION_TIME);
            ini_set('session.cookie_lifetime',SESSION_TIME);
        }else{
            ini_set('session.gc_maxlifetime', SESSION_TIME);
            ini_set('session.cookie_lifetime',SESSION_TIME);
        }
        session_start(); 
        
        define('RESOURCE_PATH',SITE_URL.\Yaf_Registry::get("sysconfig")->sys->resource);
        define('VIEW_PATH',SITE_PATH.'/public/default/views/ayaf');
        define('REAL_RESOURCE_URL',\Yaf_Registry::get("sysconfig")->sys->url->resource);
        define('HANDLE_RESOURCE_URL',\Yaf_Registry::get("sysconfig")->sys->url->handle);
        define('CDN_URL',\Yaf_Registry::get("sysconfig")->sys->url->cdn);
        define('ALLOW_SIGN',\Yaf_Registry::get("sysconfig")->sys->allow->sign);
        define('ALLOW_OAUTH2',\Yaf_Registry::get("sysconfig")->sys->allow->oauth2);
        define('DEBUG_SYSERROR',\Yaf_Registry::get("sysconfig")->debug->syserror->exp);
        define('DEBUG_SYSERROR_DB',\Yaf_Registry::get("sysconfig")->debug->syserror->db);
        define('DEBUG_SYSERROR_TYPE',\Yaf_Registry::get("sysconfig")->debug->syserror->type);
        define('COOKIE_LOGGED_USER','BASE_LOGGED_USER');
        define('SESSION_LOGGED_USERID','uid');
        define('SESSION_LOGGED_EMAIL','email');
        define('SESSION_LOGGED_CELLPHONE','cellphone');
        define('SESSION_LOGGED_COMPANYID','cid');
        define('SESSION_LOGGED_ADMIN_USERID','admin_uid');
        define('SESSION_LOGGED_ADMIN_PRIVILEGE','admin_level');
 
        \Core\BaseRoute::init($dispatcher);
        
        $dispatcher->autoRender(FALSE);//关闭自动渲染
        //初始化视图路径
        $dispatcher->initView(SITE_PATH . "/public/default/views");
    }
      
    //基本请求
    public function start(){
        $this->_initConfig();
        $this->__application->run();
    }
    
    //命令行请求
    public function startc(){
        $this->_initConfig();
        $this->__application->getDispatcher()->dispatch(new \Yaf_Request_Simple());
    }   
}
