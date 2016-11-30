<?php
namespace Core;
class BaseRoute{
    //默认初始简单路由
    private static $_routeConfig=["simple" => ["type"  => "simple","module"=>'m',"controller" => "c","action"=>"a"]];
    
    //初始化
    public static function init($dispatcher) {
        //$dispatcher->setDefaultModule("Index")->setDefaultController("Index")->setDefaultAction("index");
        $request=$dispatcher->getRequest();
        $requestUri=$request->getRequestUri();
        //添加配置文件中的路由
        $router = $dispatcher->getRouter();
        if(self::isMobile()){
            $routeConfig=self::rewriteRoute(\Yaf_Registry::get("waproutes")->rewrite->toArray());
        }else{
            $routeConfig=self::rewriteRoute(\Yaf_Registry::get("webroutes")->rewrite->toArray());
        }
        $routes=self::rewriteRoute(\Yaf_Registry::get("routes")->rewrite->toArray());
        
        $routeConfig= array_merge($routeConfig,$routes);
        self::$_routeConfig=array_merge(self::$_routeConfig,$routeConfig);
        $router->addConfig(new \Yaf_Config_Simple(self::$_routeConfig));
    }
    
    public static function rewriteRoute($route){
        if(!isset($route['match'])||!isset($route['route']) || count($route['match'])!==count($route['route'])){
            return;
        }
        $routes=[];
        $match=$route['match'];
        $rewrite=$route['route'];
        foreach ($match as $k=>$v){
            if(!isset($rewrite[$k])){
                echo $rewrite[$k];
                continue;
            }
            $routes[$k]['type']='rewrite';
            $routes[$k]['match']=$v;
            $route=  explode('/', $rewrite[$k]);
            $routes[$k]['route']['module']=isset($route[1])?$route[1]:'';
            $routes[$k]['route']['controller']=isset($route[2])?$route[2]:'';
            $routes[$k]['route']['action']=isset($route[3])?$route[3]:'';
        }
        return $routes;
    }

    //手机端
    public static function isMobile(){
        $useragent = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT']: '';
        $ua = strtolower($useragent);
        $uachar = "/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i";
        if($ua == '' || preg_match($uachar, $ua)){  
            return TRUE;
        }else{
            return FALSE;
        }
    }
}

