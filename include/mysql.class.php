<?php
if (!defined('IN_LOTHAR')) die('Hacking attempt');

class DbMysql {
    private $dbhost; // 数据库主机
    private $dbuser; // 数据库用户名
    private $dbpass; // 数据库用户名密码
    private $dbname; // 数据库名
    private $dou_link; // 数据库连接标识
    private $prefix; // 数据库前缀
    private $charset; // 数据库编码，GBK,UTF8,gb2312
    private $pconnect; // 持久链接,1为开启,0为关闭
    private $sql; // sql执行语句
    private $result; // 执行query命令的结果资源标识
    private $error_msg; // 数据库错误提示

    // 构造函数
    // function DbMysql($dbhost, $dbuser, $dbpass, $dbname = '', $prefix, $charset='utf8', $pconnect=0) {
    //     $this->dbhost = $dbhost;
    //     $this->dbuser = $dbuser;
    //     $this->dbpass = $dbpass;
    //     $this->dbname = $dbname;
    //     $this->prefix = $prefix;
    //     $this->charset = strtolower(str_replace('-', '', $charset));
    //     $this->pconnect = $pconnect;
    //     $this->connect();
    // }
    function __construct($dbhost, $dbuser, $dbpass, $dbname = '', $prefix, $charset='utf8', $pconnect=0) {
        $this->dbhost = $dbhost;
        $this->dbuser = $dbuser;
        $this->dbpass = $dbpass;
        $this->dbname = $dbname;
        $this->prefix = $prefix;
        $this->charset = strtolower(str_replace('-', '', $charset));
        $this->pconnect = $pconnect;
        $this->connect();
    }

    // 数据库连接
    function connect() {
        if ($this->pconnect) {
            if (!$this->dou_link = @mysql_pconnect($this->dbhost, $this->dbuser, $this->dbpass)) {
                $this->error('Can not pconnect to mysql server');
                return false;
            }
        } else {
            if (!$this->dou_link = @mysql_connect($this->dbhost, $this->dbuser, $this->dbpass, true)) {
                $this->error('Can not connect to mysql server');
                return false;
            }
        }

        if ($this->version() > '4.1') {
            if ($this->charset) {
                $this->query("SET character_set_connection=" . $this->charset . ", character_set_results=" . $this->charset .
                         ", character_set_client=binary");
            }

            if ($this->version() > '5.0.1') {
                $this->query("SET sql_mode=''");
            }
        }

        if (mysql_select_db($this->dbname, $this->dou_link) === false) {
            $this->error("NO THIS DBNAME:" . $this->dbname);
            return false;
        }
    }

    // 数据库执行语句，可执行查询添加修改删除等任何sql语句
    function query($sql) {
        $this->sql = $sql;
        $query = mysql_query($this->sql, $this->dou_link);
        if (empty($query)) return;
        return $query;
    }

    // 取得前一次 MySQL 操作所影响的记录行数
    function affected_rows() {
        return mysql_affected_rows();
    }

    // 返回结果集中行的数目
    function num_rows($query) {
        return @ mysql_num_rows($query);
    }

    // 返回结果集中一个字段的值
    function result($row = 0) {
        return @ mysql_result($this->result, $row);
    }

    // 返回结果集中字段的数
    function num_fields($query) {
        return mysql_num_fields($query);
    }

    // 释放结果内存
    function free_result() {
        return mysql_free_result($this->result);
    }

    // 返回上一步 INSERT 操作产生的 ID
    function insert_id() {
        return mysql_insert_id();
    }

    // 获取下一个自增(id)值
    function auto_id($table) {
        return $this->get_one("SELECT auto_increment FROM information_schema.`TABLES` WHERE  TABLE_SCHEMA='" . $this->dbname . "' AND TABLE_NAME = '" . trim($this->table($table), '`') . '\'');
    }

    public function dbinfos($table='', $result_type=MYSQL_ASSOC)
    {
        $result = $this->query("SELECT * FROM information_schema.SCHEMATA where SCHEMA_NAME='{$table}';");
        $row = $this->fetch_array($result,$result_type);
        return $row;
    }

    // 从结果集中取得一行作为数字数组
    function fetch_row($query) {
        return mysql_fetch_row($query);
    }

    // 从结果集中取得一行作为关联数组
    function fetch_assoc($query) {
        if (empty($query)) return;
        return mysql_fetch_assoc($query);
    }

    // 从结果集取得的行生成的数组
    function fetch_array($query,$result_type=MYSQL_BOTH) {
        return mysql_fetch_array($query,$result_type);
    }

    // 返回 MySQL 服务器的信息
    function version() {
        if (empty($this->version)) {
            $this->version = mysql_get_server_info($this->dou_link);
        }
        return $this->version;
    }

    // 关闭 MySQL 连接
    function close() {
        return mysql_close($this->dou_link);
    }

    // 将指定的表名加上前缀后返回
    function table($str) {
        return '`' . $this->prefix . $str . '`';
    }

    // 查询全部
    function select_all($table) {
        return $this->query("SELECT * FROM " . $table);
    }

    // 判断表是否存在
    function table_exist($table) {
        if($this->num_rows($this->query("SHOW TABLES LIKE '" . trim($this->table($table), '`') .'\'')) == 1)
            return true;
    }

    // 判断字段是否存在
    function field_exist($table, $field) {
        $sql = "SHOW COLUMNS FROM " . $this->table($table);
        $query = $this->query($sql);
        while($row = $this->fetch_array($query, MYSQL_ASSOC))   {
            $array[] = $row['Field'];
        }
        if (in_array($field, $array))
            return true;
    }

    // 验证信息是否已经存在
    function value_exist($table, $field, $value, $where = '') {
        $sql = "SELECT $field FROM " . $this->table($table) . " WHERE $field = '$value'" . $where;
        $number = $this->num_rows($this->query($sql));
        if ($number > 0)
            return true;
    }

    /*
    * 插入记录
    * @param string
    * @param array
    * @return number
    */
    public function insert($table='',$data)
    {
        if (is_array($data)) {
            $keys = '`'. join('`,`',array_keys($data)) .'`';
            $vals = '\''. join("','",array_values($data)).'\'';
            $sql="INSERT INTO ".$this->table($table)." ({$keys}) VALUES ({$vals})";
        } else {
            $sql = $data;
        }
        $this->query($sql);
        return $this->insert_id();
    }

    /*
    * 更新数据
    * @param string
    * @param array
    * @return number
    */
    public function update($table='',$data,$where=null)
    {
        foreach ($data as $key => $val) {
            $str .= '`'. $key .'`=\''. $val .'\',';
        }
        $str = substr($str,0,-1);
        $sql="update ".$this->table($table)." set {$str}". ($where==null?'':" where ".$where);
        $result = $this->query($sql);
        $affected_rows = $this->affected_rows();
        $affected_rows = ($affected_rows==-1)?false:true;// 修改内容 无变化0 , 错误-1
        // $this->close();
        return $affected_rows;
    }

    // 删除数据
    function delete($table, $condition, $url = '') {
        if ($this->query("DELETE FROM $table WHERE $condition")) {
            if (!empty($url)) {
                $GLOBALS['dou']->dou_msg($GLOBALS['_LANG']['del_succes'], $url);
            }
        }
    }

    // 条件查询 mysql_query()
    function select($table, $column = "*", $condition = '', $debug = '') {
        $condition = $condition ? ' Where ' . $condition : NULL;
        if ($debug) {
            echo "SELECT $column FROM $table $condition";
        } else {
            $query = $this->query("SELECT $column FROM $table $condition");
            return $query;
        }
    }

    // 仿真 Adodb 函数。获取一个字段值
    function get_one($sql, $limited = false) {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }
        $res = $this->query($sql);
        if ($res !== false) {
            $row = mysql_fetch_row($res);
            return ($row!==false)?$row[0]:'';
        } else {
            return false;
        }
    }

    /*
    * 获取一条数据
    * @param string
    * @param string
    * @return array
    */
    public function fetchRow($sql='',$result_type=MYSQL_ASSOC)
    {
        $result = $this->query($sql);
        $row = $this->fetch_array($result,$result_type);
        return $row;
    }

    /*
    * 取得所有结果集
    * @param string
    * @param string
    * @return array
    */
    public function fetchAll($sql='',$result_type=MYSQL_ASSOC)
    {
        $result = $this->query($sql);
        while (@$row = $this->fetch_array($result,$result_type)) {
            if (isset($row['addtime'])) {
                $row['addtime'] = date("Y-m-d H:i",$row['addtime']);
            }
            $rows[] = $row;
        }
        return $rows;
    }

    // 循环读取结果集并储存至数组
    function fetch_array_all($table, $order='', $where='', $fields='*') {
        $where = $where ? " WHERE ".$where : '';
        $order = $order ? " ORDER BY " . $order : '';
        $fields = is_array($fields) ? implode(',',$fields) : $fields;
        $query = $this->query("SELECT ". $fields ." FROM " . $table . $where . $order);
        while ($row = $this->fetch_assoc($query)) {
            $data[] = $row;
        }
        return $data;
    }

    /*
    * 取得数据数量
    * @param string
    * @return string
    */
    public function getResNum($sql='')
    {
        $result = $this->query($sql);
        return $this->num_rows($result);
    }

    // 转义特殊字符
    function escape_string($string) {
        if (PHP_VERSION >= '4.3') {
            return mysql_real_escape_string($string);
        } else {
            return mysql_escape_string($string);
        }
    }

    // 返回错误信息
    function error($msg = '') {
        $msg = $msg ? "This Error: $msg" : '<b>MySQL server error report</b><br>' . $this->error_msg;
        exit($msg);
    }

    // 数据库导入
    function fn_execute($sql) {
        $sqls = $this->fn_split($sql);
        if (is_array($sqls)) {
            foreach ($sqls as $sql) {
                if (trim($sql) != '')
                    $this->query($sql);
            }
        } else {
            $this->query($sqls);
        }
        return true;
    }

    // 数据分离
    function fn_split($sql) {
        if ($this->version() > '4.1' && $this->sqlcharset)
            $sql = preg_replace("/TYPE=(InnoDB|MyISAM)( DEFAULT CHARSET=[^; ]+)?/", "TYPE=\\1 DEFAULT CHARSET=" . $this->sqlcharset, $sql);

        $sql = str_replace("\r", "\n", $sql);
        $ret = array();
        $num = 0;
        $queriesarray = explode(";\n", trim($sql));
        unset($sql);
        foreach ($queriesarray as $query) {
            $ret[$num] = '';
            $queries = explode("\n", trim($query));
            $queries = array_filter($queries);
            foreach ($queries as $query) {
                $str1 = substr($query, 0, 1);
                if ($str1 != '#' && $str1 != '-')
                    $ret[$num] .= $query;
            }
            $num++;
        }
        return ($ret);
    }
}
?>