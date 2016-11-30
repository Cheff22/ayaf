<?php
namespace Core\Dao\Db;
class DbSqlBuild {
    private static $_instance = NULL;
    private $_params=[];
    private $_dbDriver='PDO';
    
    private function __construct(){}
    public static function getInstance() {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self ();
        }
        return self::$_instance;
    }
    
    protected function parseKey(&$key) {
        $key = trim($key);
        if (!preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        return $key;
    }

    protected function parseValue($value) {
        if (is_string($value)) {
            $value = '\'' . addslashes($value) . '\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
            $value = addslashes($value[1]);
        } elseif (is_array($value)) {
            $value = array_map(array($this, 'parseValue'), $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'null';
        }
        return $value;
    }

    //identify_field标识字段，string
    //modify_field修改字段，array
    //data数据字段，array('track_name'=>array('12345678'=>'test','12345685'=>'test1'))
    protected function parseSet($data) {
        if(isset($data['_way'])&& $data['_way']=='CASE' && isset($data['identify_field'])&&isset($data['modify_field'])){
            $sql='SET ';
            $field=$data['identify_field'];
            foreach ($data['modify_field'] as $v){
                $sql.="$v = CASE $field ";
                foreach ($data['data'][$v] as $kt=>$vt){
                    if(is_int($kt)){
                        $sql .= sprintf("WHEN %d THEN '%s' ", $kt, addslashes($vt));
                    }elseif(is_string($kt)){
                        $sql .= sprintf("WHEN '%s' THEN '%s' ", addslashes($kt), addslashes($vt));
                    }
                }
                $sql.=' END,';
            }
            return rtrim($sql,',');
        }else{
            foreach ($data as $key => $val) {
                //$set[]= $this->parseKey($key) . '=' . $val;
                $set[]= $this->parseKey($key) . '=' . $this->parseParams($val);
            }
            return ' SET ' . implode(',', $set);
        }
    }
    
    protected function parseTable($tables) {
        $tables = explode(' ', $tables);
        $tables[0]=$this->parseKey($tables[0]);
        return implode(' ', $tables);
    }

    protected function parseField($fields) {
        if (is_string($fields) && strpos($fields, ',')) {
            $fields = explode(',', $fields);
        }
        if (is_array($fields)) {
            $array = [];
            foreach ($fields as $key => $field) {
                if (!is_numeric($key))
                    $array[] = $this->parseKey($key) . ' AS ' . $this->parseKey($field);
                else
                    $array[] = $this->parseKey($field);
            }
            $fieldsStr = implode(',', $array);
        }elseif (is_string($fields) && !empty($fields)) {
            $fieldsStr = $this->parseKey($fields);
        } else {
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }
    
    protected function parseWhere($where) {
        $whereStr = '';
        if (is_string($where)) {//1,整个where是字符串
            $whereStr = $where;
        } else {                //2,整个where是数组
            $operate = isset($where['_logic']) ? strtoupper($where['_logic']) : '';
            if (in_array($operate, ['AND', 'OR', 'XOR'])) {
                $operate = ' ' . $operate . ' ';
                unset($where['_logic']);
            } else {
                $operate = ' AND ';
            }
            foreach ($where as $key => $val) {
                $whereStr .='( '. $this->parseWhereItem($this->parseKey($key), $val) .' )' . $operate;
            }            
            $whereStr=rtrim($whereStr,$operate);
        }
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }
    
    protected function parseWhereItem($key, $val) {
        $comparison = ['EQ'=>'=','NEQ'=>'<>','GT' =>'>','EGT'=>'>=','LT'=>'<','ELT'=>'<=','NOTLIKE'=>'NOT LIKE','LIKE'=>'LIKE','IN'=>'IN','NOTIN'=>'NOT IN'];
        $whereStr = '';
        if (is_array($val)) {
            //运算特殊处理
            if (is_string($val[0])){
                if (preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT)$/i', $val[0])) { // 比较运算
                    //$whereStr .= $key . ' ' . $comparison[strtoupper($val[0])] . ' ' . $val[1];
                    $whereStr .= $key . ' ' . $comparison[strtoupper($val[0])] . $this->parseParams($val[1]);
                }elseif (preg_match('/^(NOTLIKE|LIKE)$/i', $val[0])) {// 模糊查找
                    if (is_array($val[1])) {
                        $likeLogic = isset($val[2]) ? strtoupper($val[2]) : 'OR';
                        if (in_array($likeLogic, ['AND', 'OR', 'XOR'])) {
                            $like = [];
                            foreach ($val[1] as $kitem=>$item) {
                                //$like[] = $kitem . ' '  .'LIKE'. ' ' . "'$item'"  ;
                                $like[] = $kitem . ' LIKE ' . "'".$this->parseParams($item)."'" ;
                            }
                            $whereStr .= implode(' ' . $likeLogic . ' ', $like);
                        }
                    }else {
                        //$whereStr.=$key.' '.$comparison[strtoupper($val[0])].' ' .$val[1];
                        $whereStr .= $key . ' ' . $comparison[strtoupper($val[0])] . $this->parseParams($val[1]);
                    }
                } elseif (preg_match('/IN|NOTIN/i', $val[0])) { // IN 运算 (NOT IN)
                    if(is_array($val[1])){
                        $zone=rtrim(str_repeat('?, ',count($val[1])),', ');
                        $whereStr .= $key . ' ' . strtoupper($val[0]) . ' (' . $zone . ')';
                        $this->parseParams($val[1]);
                    }
                }elseif(preg_match('/BETWEEN/i',$val[0])){ // BETWEEN运算
                    $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                    //$whereStr .=  ' ('.$key.' '.strtoupper($val[0]).' '.$data[0].' AND '.$data[1].' )';
                    $whereStr .=  ' ('.$key.' '.strtoupper($val[0]).' '.$this->parseParams($data[0]).' AND '.$this->parseParams($data[1]).' )';
                }
            }else{//仍是数组（）
                $count = count($val);
                $rule = isset($val[$count - 1]) ? strtoupper($val[$count - 1]) : '';
                if (in_array($rule, ['AND', 'OR', 'XOR'])) {
                    $count = $count - 1;
                } else {
                    $rule = 'AND';
                }
                for ($i = 0; $i < $count; $i++) {
                    $data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
                    if ('exp' == strtolower($val[$i][0])) {
                        //$whereStr .= '(' . $key . ' ' . $data . ') ' . $rule . ' ';
                        $whereStr .= '(' . $key . ' ' . $this->parseParams($data) . ') ' . $rule . ' ';
                    } else {
                        $op = is_array($val[$i]) ? $comparison[strtoupper($val[$i][0])] : '=';
                        //$whereStr .= '(' . $key . ' ' . $op . ' ' . $data . ') ' . $rule . ' ';
                        $whereStr .= '(' . $key . ' ' . $op . ' ' . $this->parseParams($data) . ') ' . $rule . ' ';
                        
                    }
                }
                $whereStr = substr($whereStr, 0, -4);
            }
        }else {
            //处理普通where语句
            //$whereStr .= $key . ' = ' . $val;
            $whereStr .= $key . ' = ' . $this->parseParams($val);
        }
        return $whereStr;
    }
    
    protected function parseParams($param){
        if(is_array($param)){
            $this->_params=  array_merge($this->_params,$param);
        }else{
            $this->_params=  array_merge($this->_params,[$param]);
        }
        if($this->_dbDriver=='PDO'){
            return '?';
        }
    }

    protected function parseLimit($limit) {
        return !empty($limit) ? ' LIMIT ' . $limit . ' ' : '';
    }

    protected function parseJoin($join) {
        $joinStr = '';
        if(empty($join)){
            return $joinStr;
        }
        if (is_array($join)) {
            foreach ($join as $_join) {
                $joinStr.=(false !== stripos($_join, 'JOIN'))?' ' . $_join:' LEFT JOIN ' . $_join;
            }
        }else {
            $joinStr .= ' LEFT JOIN ' . $join;
        }
            
        return $joinStr;
    }

    protected function parseOrder($order) {
        if (is_array($order)) {
            $array = [];
            foreach ($order as $key => $val) {
                if (is_numeric($key)) {
                    $array[] = $val;
                } else {
                    $array[] = $key . ' ' . $val;
                }
            }
            $order = implode(',', $array);
        }
        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    protected function parseGroup($group) {
        return !empty($group) ? ' GROUP BY ' . $group : '';
    }

    protected function parseHaving($having) {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    protected function parseComment($comment) {
        return !empty($comment) ? ' /* ' . $comment . ' */' : '';
    }

    protected function parseDistinct($distinct) {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    protected function parseUnion($union) {
        if (empty($union)){ return ''; }
        if (isset($union['_all'])) {
            $str = 'UNION ALL ';
            unset($union['_all']);
        } else {
            $str = 'UNION ';
        }
        foreach ($union as $u) {
            $sql[] = $str . (is_array($u) ? $this->buildSelectSql($u) : $u);
        }
        return implode(' ', $sql);
    }
    
    protected function parseLock($lock = false) {
        if (!$lock){ return ''; }
        return ' FOR UPDATE ';
    }
    
    public function getParams(){
        return $this->_params;
    }
    
    public function buildSelectSql($options = []) {
        $this->_params=[];
        $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING% %UNION%%ORDER%%LIMIT% %COMMENT%';
        $sql = str_replace(['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%','%UNION%', '%ORDER%', '%LIMIT%', '%COMMENT%'], 
                [
                    $this->parseTable($options['table']),
                    $this->parseDistinct(isset($options['distinct']) ? $options['distinct'] : false),
                    $this->parseField(!empty($options['field']) ? $options['field'] : '*'),
                    $this->parseJoin(!empty($options['join']) ? $options['join'] : ''),
                    $this->parseWhere(!empty($options['where']) ? $options['where'] : ''),
                    $this->parseGroup(!empty($options['group']) ? $options['group'] : ''),
                    $this->parseHaving(!empty($options['having']) ? $options['having'] : ''),
                    $this->parseUnion(!empty($options['union']) ? $options['union'] : ''),
                    $this->parseOrder(!empty($options['order']) ? $options['order'] : ''),
                    $this->parseLimit(!empty($options['limit']) ? $options['limit'] : ''),
                    $this->parseComment(!empty($options['comment']) ? $options['comment'] : '')
                ], $selectSql);
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $sql;
    }
    
    public function buildInsertSql($data, $options = [], $replace = false ){
        $this->_params=[];
        $values = $fields = [];
        foreach ($data as $key => $val) {
            //$values[] = $val;
            $values[] = $this->parseParams($val);
            $fields[] = $this->parseKey($key);
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        //$sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $sql;
    }
    
    public function buildInsertAllSql($datas, $options = [], $replace = false){
        $this->_params=[];
        if (!is_array($datas[0])) {return false;}
        $fields = array_keys($datas[0]);
        array_walk($fields, [$this, 'parseKey']);
        $values = [];
        foreach ($datas as $data) {
            $value = [];
            foreach ($data as $val) {
                // 过滤非标量数据
                //$value[] = $val; 
                $value[] = $this->parseParams($val); 
            }
            $values[] = '(' . implode(',', $value) . ')';
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $values);
        return $sql;
    }
    
    public function buildUpdateSql($data, $options){
        $this->_params=[];
        $sql = 'UPDATE '
            . $this->parseTable($options['table'])
            . $this->parseSet($data)
            . $this->parseWhere(!empty($options['where']) ? $options['where'] : '')
            . $this->parseOrder(!empty($options['order']) ? $options['order'] : '')
            . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '')
            . $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
            //. $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $sql;
    }
    
    public function buildDeleteSql($options){
        $this->_params=[];
        $sql = 'DELETE FROM '
            . $this->parseTable($options['table'])
            . $this->parseWhere(!empty($options['where']) ? $options['where'] : '')
            . $this->parseOrder(!empty($options['order']) ? $options['order'] : '')
            . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '')
            . $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
            //. $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $sql;
    }
    
}
