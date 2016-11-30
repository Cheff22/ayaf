<?php
class TestController extends \Core\BaseControllers {
    
    public function init() {
        parent::init();   
    }    
    
    public function testAction(){
        $data=["code"=>200,"message"=>"Test","data"=>""];
        $this->returnValue($data); 
    }
    
}

