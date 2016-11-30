<?php
namespace Web;
class IndexModel extends \Core\BaseModels {
    
    public function dbSample($page=1,$count=20){
        //多个查询
        $options['table'] = 'test';
        $options['where'] = ['status'=>1];
        $options['limit'] = ($page-1)*$count.','.$count;
        $data = $this->db->select($options);
        
        //单个查询
        $options1['table'] = 'test';
        $options1['where'] = ['status'=>1];
        $data1 = $this->db->find($options1);
        
        //添加
        $tmpData2=['status'=>1,'content'=>'test'];
        $options2['table'] = 'test';
        $data2 = $this->db->add($tmpData2,$options2);
        
        //保存
        $tmpData3=['status'=>3,'content'=>'test3'];
        $options3['table'] = 'test';
        $options3['where'] = ['status'=>1];
        $data3 = $this->db->save($tmpData3,$options3);
        
        //删除
        $options4['table'] = 'test';
        $options4['where'] = ['status'=>1];
        $data4 = $this->db->delete($options4);
                
        //返回标准
        if(!empty($data)){           
            return $this->returnResult(200,$data);
        }else {
            return $this->returnResult(201);
        }
        
    }
    
    //resdis操作
    public function redisSample(){
        //获取
        $test=$this->nosql->get('test');
        //设置
        $this->nosql->set('test','123',60);
        //删除
        $this->nosql->delete('test');
    }
    
}



