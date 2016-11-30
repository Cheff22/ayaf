<?php
class IndexController extends \Core\BaseControllers {
    protected $_pageTypes = [];
    public function init() {
        parent::init();
        $this->_pageTypes = [
            'home'=>'page-home',
        ];
        $this->_view->assign('_mid', $this->_mid);
        $this->_view->assign('title', 'ayaf');
        $this->_view->assign('keywords', 'ayaf');
        $this->_view->assign('description', 'ayaf');
        $this->_view->assign('pageHeader', '');
        $this->_view->assign('pageTypes', $this->_pageTypes);
    }
   
    public function homeAction(){
        $this->_pageType='home';
        $this->displayPage('web-index');
    }
    
}