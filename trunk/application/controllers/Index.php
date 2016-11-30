<?php
class IndexController extends \Core\BaseControllers {
    protected $_pageTypes = [];
    public function init() {
        parent::init();
        $this->_pageTypes = [
            'test'=>'page-test',
            'home'=>'page-home',
        ];
        $this->_view->assign('_mid', $this->_mid);
        $this->_view->assign('title', 'ayaf');
        $this->_view->assign('keywords', 'ayaf');
        $this->_view->assign('description', 'ayaf');
        $this->_view->assign('pageHeader', '');
        $this->_view->assign('pageTypes', $this->_pageTypes);
    }
    
    //导航页
    public function indexAction(){
        $siteUrl=isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'';
        $isWap=$this->isMobile();
        if($isWap){
            $this->forward("Wap","home");
        }else{
            $this->forward("Index","home");
        }
    }
    
    public function homeAction(){       
        $this->_pageType='home';
        $this->displayPage('web-index');
    }
    
    public function testdbAction(){
        //请先配置数据库
        $model=new \Web\IndexModel();
        $data=$model->dbSample();
        print_r($data);
    }
    
    public function testresdisAction(){
        //请先配置redis
        $model=new \Web\IndexModel();
        $data=$model->redisSample();
        print_r($data);
    }
}