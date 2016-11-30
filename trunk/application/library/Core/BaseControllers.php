<?php
namespace Core;
class BaseControllers extends \Yaf_Controller_Abstract{
    protected $_apiWhiteModelList=array('Oauth','Tests'); // 白名单模块TODO写入配置文件
    protected $_postData=[];//post数据
    protected $_getData=[];//get数据
    protected $_paramData=[];//路由数据
    protected $_mid=0;//当前登录用户uid
    protected $_aid=0;//当前登录admin用户uid
    protected $_aidLevel=0;//当前登录admin用户权限
    protected $_count=20;//默认个数
    protected $_page=1;//默认页数
    protected $_userAgent='';
    protected $_httpUserAgent='';
    protected $_httpReferer='';
    protected $_remoteIp='';
    protected $_realIp='';
    protected $_channelId='';//渠道
    protected $_requestUri='';
    protected $_module='';
    protected $_controller='';
    protected $_action='';
    protected $_view='';
    protected $_pageType='';
    protected $_returnFormat='json';
    protected $_sysVersion=0;//默认版本
    //protected $_sessionObject=null; //session对象
    protected $_isAjax = FALSE;
     
    protected $_clientId='';
    protected $_clientSecret='';
    protected $_oauthToken='';
    
    protected $_cid=0;//企业用户id
    
    //初始化系统配置
    protected function init() {
        $this->fitlerHTTPValue();
        $this->fitlerUserAgent();

        $this->_httpReferer=isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
        $this->_requestUri=isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'';
        $this->_remoteIp=isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'';
        $this->_realIp=$this->getRealIp();
        $this->_module=strtolower($this->getRequest()->getModuleName());
        $this->_controller=strtolower($this->getRequest()->getControllerName());
        $this->_action=$this->getRequest()->getActionName();
        //TODO跨域请求设置
        $this->_isAjax=$this->getRequest()->isXmlHttpRequest();
        $this->_count=isset($this->_postData['count'])?intval($this->_postData['count']):(isset($this->_getData['count'])?intval($this->_getData['count']):20);
        $this->_page=isset($this->_postData['page'])?intval($this->_postData['page']):(isset($this->_getData['page'])>0?intval($this->_getData['page']):1);
        $this->_count=$this->_count>0?$this->_count:20;
        $this->_page=$this->_page>0?$this->_page:1;
        $this->_cid=isset($_SESSION[SESSION_LOGGED_COMPANYID])?$_SESSION[SESSION_LOGGED_COMPANYID]:'';
        
        $this->fitlerSession();
        $this->accessRule();
        
        $tmp=  explode('/', $this->_requestUri);
        $this->_pageType=isset($tmp[1])?$tmp[1]:'';
        $this->_view=$this->getView();
    }
    
    //参数过滤
    private function fitlerHTTPValue(){
        $postData=$this->getRequest()->getPost();
        if(!empty($postData)){
            foreach ($postData as $k=>$v){
                if(!is_array($postData[$k])){
                    $this->_postData[$k]= filter_input(INPUT_POST,$k);                    
                }else{
                    $this->_postData[$k]=filter_input(INPUT_POST,$k,FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
                }
            }
        }
        $getData=$this->getRequest()->getQuery();
        if(!empty($getData)){
            foreach ($getData as $k=>$v){
                if(!is_array($getData[$k])){
                    $this->_getData[$k]=filter_input(INPUT_GET,$k);
                }else{
                    $this->_getData[$k]=filter_input(INPUT_GET,$k,FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
                }
            }
        }
        $this->_paramData=$this->getRequest()->getParams();
    }
    
    //规范用户代理
    private function fitlerUserAgent(){
        //规范用户代理模式
        if(isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT'])){
            $httpUserAgent=$_SERVER['HTTP_USER_AGENT'];   
            if(strpos($httpUserAgent,'iPhone OS')!==false){
                $userAgent='ios';
            }elseif(strpos($httpUserAgent,'Android')!==false){
                $userAgent='android';
            }else{
                //排除抓取可能性
                $userAgent='pc';//strpos($httpUserAgent,'Mozilla')
            }
        }else{
            if($this->_module=='admin'){
                $_SERVER['HTTP_USER_AGENT']='admin';
                $userAgent='pc';
            }else{
                //BaseErrors::ErrorHandler(5005);
            }
        }
        $this->_userAgent= $userAgent;
        $this->_httpUserAgent= isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'';
    }
    
    //过滤会话//设置mid,aid
    private function fitlerSession(){
        if(isset($_SESSION[SESSION_LOGGED_USERID]) && $_SESSION[SESSION_LOGGED_USERID]>0){
            if(isset($_COOKIE[COOKIE_LOGGED_USER])){
                //$tmpValue=explode('#',filter_input(INPUT_COOKIE, 'LOGGED_USER'));
                $tmpValue=explode('#',$_COOKIE[COOKIE_LOGGED_USER]);
                $loggedUser=base64_decode($tmpValue[1]);
                if($loggedUser==$_SESSION[SESSION_LOGGED_USERID]){
                    $this->_mid=$_SESSION[SESSION_LOGGED_USERID];
                }elseif($loggedUser>0){
                    $_SESSION[SESSION_LOGGED_USERID]=$loggedUser;
                    $this->_mid=$_SESSION[SESSION_LOGGED_USERID];
                }else{
                    BaseErrors::ErrorHandler(5010);
                }
            }else{
                $this->_mid=$_SESSION[SESSION_LOGGED_USERID];
            }
            return;
        }else{
            if(isset($_COOKIE[COOKIE_LOGGED_USER])){
                $tmpValue=explode('#',$_COOKIE[COOKIE_LOGGED_USER]);
                $loggedUser=base64_decode($tmpValue[1]);
                if($loggedUser>0){
                    $_SESSION[SESSION_LOGGED_USERID]=$loggedUser;
                    $this->_mid=$_SESSION[SESSION_LOGGED_USERID];
                    return;
                }else{
                    BaseErrors::ErrorHandler(5010);
                }
            }
        }
        
        if(isset($_SESSION[SESSION_LOGGED_ADMIN_USERID]) && $_SESSION[SESSION_LOGGED_ADMIN_USERID]>0 &&isset($_SESSION[SESSION_LOGGED_ADMIN_PRIVILEGE])){
            $this->_aid=$_SESSION[SESSION_LOGGED_ADMIN_USERID];
            $this->_aidLevel=$_SESSION[SESSION_LOGGED_ADMIN_PRIVILEGE];
        }
    }
    
    //设置授权会话
    protected function setOauthSession($data){
        if($data['code']==200 && isset($data['data']['uid']) && $data['data']['uid']>0){
            session_regenerate_id();
            $_SESSION=[];
            $_SESSION[SESSION_LOGGED_USERID]=$data['data']['uid'];
            $_SESSION[SESSION_LOGGED_EMAIL]=isset($data['data']['email'])?$data['data']['email']:'';
            $_SESSION[SESSION_LOGGED_CELLPHONE]=isset($data['data']['cellphone'])?$data['data']['cellphone']:'';
            $_SESSION[SESSION_LOGGED_COMPANYID]=isset($data['data']['company_id'])?$data['data']['company_id']:'';
            //setcookie(COOKIE_LOGGED_USER, base64_encode(session_id())."#".base64_encode($data['data']['uid']),time()+3600*24*365,'/');
        }
        //$this->_sessionObject->__set('uid',$data['data']['uid']);
    }
    
    //取消授权会话
    protected function unsetOauthSession(){
        //setcookie(COOKIE_LOGGED_USER,'',time() - 3600,'/');
        if(filter_has_var(INPUT_COOKIE, session_name())){
            setcookie(session_name(),'',time() - 3600,'/');
        }
        $_SESSION=[];
        session_destroy();
    }
    
    //设置授权管理员会话
    protected function setOauthAdminSession($data){
        if($data['code']==200 && isset($data['data']['uid']) && $data['data']['uid']>0){
            session_regenerate_id();
            $_SESSION=[];
            $_SESSION[SESSION_LOGGED_ADMIN_USERID]=$data['data']['uid'];
            $_SESSION[SESSION_LOGGED_ADMIN_PRIVILEGE]=$data['data']['privilege'];
        }
    }
    
    //取消授权管理员会话
    protected function unsetOauthAdminSession(){
        $_SESSION=array();
        session_destroy();
    }

    //过滤规则
    protected function accessRule(){      
        switch ($this->_module){
            case 'api':
            case 'web':
                if($this->_mid>0){
                    return;
                }
                break;
            case 'admin':
                if($this->_aid>0){
                    return;
                }
                if($this->_controller=='Adminindex'){
                    $this->redirect("/admin/adminoauth/login");
                }
                break;
            default :
                break;
        }       
    }
    
    //web页面返回
    public function displayPage($pageview){
        $this->_view->assign('pageType', $this->_pageType);
        $this->display($pageview);
    }

    //api返回数据
    protected function returnValue($data){
        if(!isset($data['code']) || !isset($data['message']) ||!isset($data['data'])){
            BaseErrors::ErrorHandler(5000);
        }
        if($this->_returnFormat==='json'){
            header("Content-Type: application/json;charset=utf-8");
            echo json_encode($data);
        }elseif($this->_returnFormat==='test'){
            print_r($data);
        }else{
            BaseErrors::ErrorHandler(5000);
        }
        exit;
    }

    //生成签名
    protected function generateSign($params) {
        $secretKey=isset($params['client_secret'])?$params['client_secret']:'';
        if(empty($secretKey)){
            BaseErrors::ErrorHandler(5009);
        }
        unset($params['client_secret']);
        ksort($params);
        $stringToBeSigned = $secretKey;
        foreach ($params as $k => $v) {
            if ("@" != substr($v, 0, 1)) {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $secretKey;
        return strtoupper(md5($stringToBeSigned));
    }
    
    //验证签名
    protected function verifySign($clientSecret=''){
        if(ALLOW_SIGN){
            $sysSign=isset($this->_postData['sys_sign']) ? $this->_postData['sys_sign']: '';
            $params=$this->_postData;
            $params['client_secret']= $clientSecret;
            unset($params['sys_sign']);
            if(isset($this->_postData['timestamp'])&&($this->_postData['timestamp']<time()-300)){
                BaseErrors::ErrorHandler(5008);
            }
            $sysSign1=$this->generateSign($params);
            if($sysSign!==$sysSign1){
                BaseErrors::ErrorHandler(5007);
            }
            unset($this->_postData['timestamp']);
        }
    }
    
    //真实iP
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
    
    //获得浏览器(no used)
    public function getBrowser(){
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Maxthon')) {
            $browser = 'Maxthon';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 12.0')) {
            $browser = 'IE12.0';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 11.0')) {
            $browser = 'IE11.0';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 10.0')) {
            $browser = 'IE10.0';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9.0')) {
            $browser = 'IE9.0';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0')) {
            $browser = 'IE8.0';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')) {
            $browser = 'IE7.0';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')) {
            $browser = 'IE6.0';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'NetCaptor')) {
            $browser = 'NetCaptor';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Netscape')) {
            $browser = 'Netscape';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Lynx')) {
            $browser = 'Lynx';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera')) {
            $browser = 'Opera';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
            $browser = 'Google';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox')) {
            $browser = 'Firefox';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari')) {
            $browser = 'Safari';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'iphone') || strpos($_SERVER['HTTP_USER_AGENT'], 'ipod')) {
            $browser = 'iphone';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'ipad')) {
            $browser = 'iphone';
        } elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'android')) {
            $browser = 'android';
        } else {
            $browser = 'other';
        }
        return $browser;
    }
    
    protected function isMobile(){
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

