<?php
namespace Addons\Captcha;
class CaptchaUtil {
    private $_img='';
    
    public function __construct($namespace=''){
        require_once "lib/securimage.php";
        $this->_img = new \Securimage();
        $this->_img->setNamespace($namespace);
    }
    
    public function show(){
        $this->_img->show();
    }
}

