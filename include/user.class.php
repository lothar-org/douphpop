<?php
if (!defined('IN_LOTHAR')) die('Hacking attempt');

class DouUser {
    /**
     * +----------------------------------------------------------
     * 用户权限判断
     * +----------------------------------------------------------
     * $user_id 会员ID
     * $shell 会员信息验证字符串
     * +----------------------------------------------------------
     */
    function user_check($user_id, $shell) {
        if ($row = $this->user_state($user_id, $shell)) {
            $this->user_ontime(10800);
            return $row;
        } else {
            return false;
        }
    }
    
    /**
     * +----------------------------------------------------------
     * 用户状态
     * +----------------------------------------------------------
     * $user_id 会员ID
     * $shell 会员信息验证字符串
     * +----------------------------------------------------------
     */
    function user_state($user_id, $shell) {
        $query = $GLOBALS['dou']->select($GLOBALS['dou']->table('user'), '*', '`user_id` = \'' . $user_id . '\'');
        $user = $GLOBALS['dou']->fetch_assoc($query);
        // 如果$user则开始比对$shell值
        $check_shell = is_array($user) ? $shell == md5($user['email'] . $user['password'] . DOU_SHELL) : FALSE;
        // 如果比对$shell吻合，则返回会员信息，否则返回空
        return $check_shell ? $user : false;
    }
    
    /**
     * +----------------------------------------------------------
     * 登录超时默认为3小时(10800秒)
     * +----------------------------------------------------------
     * $timeout 超时时间
     * +----------------------------------------------------------
     */
    function user_ontime($timeout = 10800) {
        $ontime = $_SESSION[DOU_ID]['ontime'];
        $cur_time = time();
        if ($cur_time - $ontime > $timeout) {
            unset($_SESSION[DOU_ID]);
        } else {
            $_SESSION[DOU_ID]['ontime'] = time();
        }
    }
    
    /**
     * +----------------------------------------------------------
     * 获取会员信息
     * +----------------------------------------------------------
     * $user_id 会员ID
     * +----------------------------------------------------------
     */
    function get_user_info($user_id) {
        $query = $GLOBALS['dou']->select($GLOBALS['dou']->table('user'), '*', '`user_id` = \'' . $user_id . '\'');
        $uinfo = $GLOBALS['dou']->fetch_array($query,MYSQL_ASSOC);
        $uinfo['username'] = $uinfo['nickname']?$uinfo['nickname'] : ($uinfo['email']?$uinfo['email']:($uinfo['telephone']?$uinfo['telephone']:''));
        $uinfo['avatar'] = $uinfo['avatar'] ? ROOT_URL . $uinfo['avatar'] : '';
        $uinfo['sex'] = $uinfo['sex'] ? $GLOBALS['_LANG']['user_man'] : $GLOBALS['_LANG']['user_woman'];
        $uinfo['add_time'] = date("Y-m-d", $uinfo['add_time']);
        $uinfo['last_login'] = date("Y-m-d", $uinfo['last_login']);
        // $uinfo['last_login'] = date("Y-m-d H:i:s", $GLOBALS['dou']->get_first_log($uinfo['last_login']));
        // $uinfo['last_ip'] = $GLOBALS['dou']->get_first_log($uinfo['last_ip']);
        return $uinfo;
    }
    
    /**
     * +----------------------------------------------------------
     * 初始化会员功能全局变量
     * +----------------------------------------------------------
    */
    function global_user_info() {
        // 如果验证码会员已经登录则读取会员信息
        $user_c = $this->user_check($_SESSION[DOU_ID]['user_id'], $_SESSION[DOU_ID]['shell']);
        if (is_array($user_c)) {
            $user_name = $user_c['nickname'] ? $user_c['nickname'] : ($user_c['email']?$user_c['email']:$user_c['telephone']);
            $user = array(
                'user_id' => $user_c['user_id'],
                'user_name' => $user_name
            );
        }
        $GLOBALS['smarty']->assign('if_user', true);
        return $user;
    }

    /**
     * +----------------------------------------------------------
     * 日志更新（以逗号分隔的两条记录）
     * +----------------------------------------------------------
     * $log_old 旧的日志内容
     * $log_new 要插入的日志内容
     * +----------------------------------------------------------
     */
    function log_update($log_old, $log_new) {
        $log_array = explode(',', $log_old);
        $log_old = $log_array[1] ? $log_array[1] : $log_array[0];
        return $log_old . ',' . $log_new;
    }

    /**
     * +----------------------------------------------------------
     * 调用订单列表
     * +----------------------------------------------------------
     * $num 数量
     * +----------------------------------------------------------
     */
    function get_order_list($user_id, $num = 10) {
        $query = $GLOBALS['dou']->query("SELECT order_id,order_sn,telephone,contact,order_amount,status,add_time FROM " . $GLOBALS['dou']->table('order') . "WHERE user_id = '$user_id' ORDER BY order_id DESC LIMIT " . $num);
        while ($row = $GLOBALS['dou']->fetch_array($query)) {
            $row['add_time'] = date("Y-m-d h:i:s", $row['add_time']);
            $row['status_format'] = $GLOBALS['_LANG']['order_status_' . $row['status']];
            $row['order_amount_format'] = $GLOBALS['dou']->price_format($row['order_amount']);
            $row['product_list'] = $GLOBALS['dou_order']->get_order_product($row['order_id']);
            
            $order_list[] = $row;
        }
        return $order_list;
    }
    
    /**
     * +----------------------------------------------------------
     * 找回密码验证
     * +----------------------------------------------------------
     * $user_id 会员ID
     * $code 密码找回码
     * $timeout 默认为24小时(86400秒)
     * +----------------------------------------------------------
     */
    function check_password_reset($user_id, $code, $timeout = 86400) {
        if ($GLOBALS['dou']->value_exist('user', 'user_id', $user_id)) {
            $user = $GLOBALS['dou']->fetch_array($GLOBALS['dou']->select($GLOBALS['dou']->table('user'), '*', "user_id = '$user_id'"));
            
            // 初始化
            $get_code = substr($code , 0 , 16);
            $get_time = substr($code , 16 , 26);
            $code = substr(md5($user['email'] . $user['password'] . $get_time . $user['last_login'] . DOU_SHELL) , 0 , 16);
            
            // 验证链接有效性
            if (time() - $get_time < $timeout && $code == $get_code) return true;
        }
    }
}
?>