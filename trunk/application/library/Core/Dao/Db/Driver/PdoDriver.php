<?php

namespace Dao\Db\Driver;

class PdoDriver {

    protected $_pdoStatement = '';

    public function __construct() {
    }

    public function connect($config = '', $linkNum = 0) {
        if (!isset($this->_linkIDArray[$linkNum])) {
            if (empty($config)){
                $config = $this->_dbConfig;
            }
            
            $dsn = $config ['TYPE'] . ':host=' . $config ['HOST'] . ($config ['PORT'] ? ';port=' . $config['PORT'] : '') . ';dbname=' . $config ['NAME'];
            try {
                $this->linkID[$linkNum] = new PDO($dsn, $config ['USER'], $config ['PWD']);
            } catch (PDOException $e) {
                throw new Exception($e->getMessage());
            }
            $this->_dbType = strtoupper($config['TYPE']);
            $this->linkID[$linkNum]->exec('SET NAMES ' . $config ['CHARSET']);

            $this->connected = true;
            unset($this->config);
        }
        return $this->_linkIDArray[$linkNum];
    }

    public function free() {
        $this->_pdoStatement = null;
    }

    public function close() {
        $this->_linkID = null;
    }

    public function error() {
        if ($this->_pdoStatement) {
            $error = $this->_pdoStatement->errorInfo();
            $this->_error = $error[2];
        } else {
            $this->_error = '';
        }
        if ('' != $this->_queryStr) {
            $this->_error .= "\n [ SQL语句 ] : " . $this->_queryStr;
        }
        return $this->_error;
    }

    public function getFields($tableName) {
        $this->initConnect(true);
        switch ($this->_dbType) {
            case 'MYSQL':
            default:
                $sql = 'DESCRIBE ' . $tableName;
        }
        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);
                $val['name'] = isset($val['name']) ? $val['name'] : "";
                $val['type'] = isset($val['type']) ? $val['type'] : "";
                $name = isset($val['field']) ? $val['field'] : $val['name'];
                $info[$name] = array(
                    'name' => $name,
                    'type' => $val['type'],
                    'notnull' => (bool) (((isset($val['null'])) && ($val['null'] === '')) || ((isset($val['notnull'])) && ($val['notnull'] === ''))), // not null is empty, null is yes
                    'default' => isset($val['default']) ? $val['default'] : (isset($val['dflt_value']) ? $val['dflt_value'] : ""),
                    'primary' => isset($val['dey']) ? strtolower($val['dey']) == 'pri' : (isset($val['pk']) ? $val['pk'] : false),
                    'autoinc' => isset($val['extra']) ? strtolower($val['extra']) == 'auto_increment' : (isset($val['key']) ? $val['key'] : false),
                );
            }
        }
        return $info;
    }

    public function query($str) {
        $this->initConnect(false);
        if (!$this->_linkID)
            return false;
        if (!empty($this->_pdoStatement))
            $this->free();
        $this->_pdoStatement = $this->_linkID->prepare($str);
        if (false === $this->_pdoStatement)
            throw new Exception($this->error());
        $result = $this->_pdoStatement->execute();
        if (false === $result) {
            echo $this->error();
            return false;
        } else {
            $result = $this->_pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    public function execute($str) {
        $this->initConnect(true);
        if (!$this->_linkID)
            return false;
        if (!empty($this->_pdoStatement))
            $this->free();
        $this->_pdoStatement = $this->_linkID->prepare($str);
        if (false === $this->_pdoStatement)
            throw new Exception($this->error());
        $result = $this->_pdoStatement->execute();
        if (false === $result) {
            echo $this->error();
            return false;
        } else {
            $this->_queryNumRows = $this->_pdoStatement->rowCount();
            if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->_lastInsID = $this->getLastInsertId();
            }
            return $this->_queryNumRows;
        }
    }

    public function startTrans() {
        $this->initConnect(true);
        if (!$this->_linkID)
            return false;
        if ($this->transTimes == 0) {
            $this->_linkID->beginTransaction();
        }
        $this->transTimes++;
        return;
    }

    public function commit() {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->commit();
            $this->transTimes = 0;
            if (!$result) {
                $this->error();
                return false;
            }
        }
        return true;
    }

    public function rollback() {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->rollback();
            $this->transTimes = 0;
            if (!$result) {
                $this->error();
                return false;
            }
        }
        return true;
    }

    public function getLastInsertId() {
        switch ($this->_dbType) {
            /* case 'PGSQL':
              case 'SQLITE':
              case 'MSSQL':
              case 'SQLSRV':
              case 'IBASE': */
            case 'MYSQL':
                return $this->_linkID->lastInsertId();
            case 'ORACLE':
            case 'OCI':
            /* $sequenceName = $this->table;
              $vo = $this->query("SELECT {$sequenceName}.currval currval FROM dual");
              return $vo?$vo[0]["currval"]:0; */
        }
    }

}
