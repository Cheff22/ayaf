<?php
namespace Core\Dao\Db;
class DbInit {
    protected $database = null;
    protected $sqlBulid= null;
    protected $modelName = '';
    protected $tableName = '';

    //模型层可以传入配置，
    public function __construct($config,$name = '', $connection = '') {
        $this->modelName = !empty($name) ? $name : substr(get_class($this), 0, -5);        
        $this->database=DbDriver::getInstance();
        $this->database->factory($config,$name,$connection);
        $this->sqlBulid=DbSqlBuild::getInstance();
    }
    
    private function __clone(){}
    
    public function __destruct(){}
    
    //选项过滤
    protected function _parseOptions($options = []) {
        if (!isset($options['table'])) {
            $options['table'] = !empty($this->tableName) ? 'ayaf_' . strtolower($this->tableName) : 'ayaf_' . strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $this->modelName), "_"));
        }
        $options['param']=isset($options['param'])?$options['param']:[];
        return $options;
    }
    
    public function startTrans() {
        $this->database->startTrans();
        return;
    }
    public function commit() {
        return $this->database->commit();
    }
    public function rollback() {
        return $this->database->rollback();
    }
    
    public function count($options = []) {
        $options['field'] = 'COUNT(*) AS tp_count';
        $options['limit'] = 1;
        $options = $this->_parseOptions($options); 
        $sql = $this->sqlBulid->buildSelectSql($options);
        $param=$this->sqlBulid->getParams();
        $result = $this->database->query($sql,$param);
        if (!empty($result)) {
            return reset($result[0]);
        } else {
            return 0;
        }
    }
    
    public function find($options = []) {
        $options['limit'] = 1;
        $options = $this->_parseOptions($options);
        $sql = $this->sqlBulid->buildSelectSql($options);
        $param=$this->sqlBulid->getParams();
        $resultSet = $this->database->query($sql,$param);
        if (false === $resultSet){return false;}
        if (empty($resultSet)){return null;}
        return $resultSet[0];
    }
    
    public function select($options = []) {
        $options = $this->_parseOptions($options);
        $sql = $this->sqlBulid->buildSelectSql($options);
        $param=$this->sqlBulid->getParams();
        $resultSet = $this->database->query($sql,$param);
        if (false === $resultSet){return false;}
        if (empty($resultSet)){return null;}
        return $resultSet;
    }
    
    public function add($data = '', $options = [],$replace = false){
        if (empty($data)){return false;}
        $options = $this->_parseOptions($options);
        $sql = $this->sqlBulid->buildInsertSql($data, $options,$replace);
        $param=$this->sqlBulid->getParams();
        $result = $this->database->execute($sql,$param);
        if (false !== $result) {
            $insertId = $this->database->getLastInsID();
            if ($insertId) {
                return $insertId;
            }
        }
        return $result;
    }
    
    public function sysadd($data = '', $options = [],$replace = false){
        if (empty($data)){return false;}
        $options = $this->_parseOptions($options);
        $sql = $this->sqlBulid->buildInsertSql($data, $options,$replace);
        $param=$this->sqlBulid->getParams();
        $result = $this->database->sysexecute($sql,$param);        
        if (false !== $result) {
            $insertId = $this->database->getLastInsID();
            if ($insertId) {
                return $insertId;
            }
        }
        return $result;
    }
    
    public function addAll($datas=[], $options = [],$replace = false) {
        if (empty($datas)){return false;}
        $options = $this->_parseOptions($options);
        $sql = $this->sqlBulid->buildInsertAllSql($datas, $options,$replace);
        $param=$this->sqlBulid->getParams();
        $result = $this->database->execute($sql,$param);
        return $result;
    }

    public function save($data = '', $options = []) {
        if (empty($data)) {return false;}
        $options = $this->_parseOptions($options);
        if (!isset($options['where'])) {return false;}
        $sql = $this->sqlBulid->buildUpdateSql($data, $options);
        $param=$this->sqlBulid->getParams();
        $result = $this->database->execute($sql,$param);
        return $result;
    }
    
    public function setInc($field, $step = 1,$options=[]) {
        $data[$field] = $field . '+' . $step;
        return $this->save($data,$options);
    }
    
    public function setDec($field, $step = 1,$options=[]) {
        $data[$field] = $field . '-' . $step;
        return $this->save($data,$options);
    }

    public function delete($options =[]) {
        $options = $this->_parseOptions($options);
        if (!isset($options['where'])) {return false;}
        $sql = $this->sqlBulid->buildDeleteSql($options);
        $param=$this->sqlBulid->getParams();
        $result = $this->database->execute($sql,$param);
        return $result;
    }
    
    public function create($sql,$options = []){
        if (empty($sql)) {return false;}
        $result = $this->database->execute($sql);
        return $result;
    }
}
