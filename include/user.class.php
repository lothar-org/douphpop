<?php
/**
 * WincomtechPHP
 * --------------------------------------------------------------------------------------------------
 * 版权所有 2013-2035 XXX网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.wowlothar.cn
 * --------------------------------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在遵守授权协议前提下对程序代码进行修改和使用；不允许对程序代码以任何形式任何目的的再发布。
 * 授权协议：http://www.wowlothar.cn/license.html
 * --------------------------------------------------------------------------------------------------
 * Author: Lothar
 * Release Date: 2015-10-16
 */
if (!defined('IN_LOTHAR')) {
    die('Hacking attempt');
}

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
        $user = $GLOBALS['dou']->fetch_array($query);
        
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
        $data = $GLOBALS['dou']->fetch_array($query);
        
        $sex = $data['sex'] ? $GLOBALS['_LANG']['user_man'] : $GLOBALS['_LANG']['user_woman'];
        $add_time = date("Y-m-d", $data['add_time']);
        $last_login = date("Y-m-d", $data['last_login']);
        
        $user_info = array(
                "email" => $data['email'],
                "nickname" => $data['nickname'],
                "telephone" => $data['telephone'],
                "contact" => $data['contact'],
                "address" => $data['address'],
                "postcode" => $data['postcode'],
                "sex" => $sex,
                "truename" => $data['truename'],
                "defined" => $data['defined'],
                "add_time" => $add_time,
                "login_count" => $data['login_count'],
                "last_login" => $data['last_login'],
                "last_ip" => $data['last_ip']
        );
        
        return $user_info;
    }
    
    /**
     * +----------------------------------------------------------
     * 初始化会员功能全局变量
     * +----------------------------------------------------------
     */
    function global_user_info() {
        // 如果验证码会员已经登录则读取会员信息
        $user_row = $this->user_check($_SESSION[DOU_ID]['user_id'], $_SESSION[DOU_ID]['shell']);
        if (is_array($user_row)) {
            $user_name = $user_row['nickname'] ? $user_row['nickname'] : $user_row['email'];
            $user = array(
                    'user_id' => $user_row['user_id'],
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
        $query = $GLOBALS['dou']->query("SELECT * FROM " . $GLOBALS['dou']->table('order') . "WHERE user_id = '$user_id' ORDER BY order_id DESC LIMIT " . $num);
        while ($row = $GLOBALS['dou']->fetch_array($query)) {
            $add_time = date("Y-m-d h:i:s", $row['add_time']);
            $status_format = $GLOBALS['_LANG']['order_status_' . $row['status']];
            $order_amount_format = $GLOBALS['dou']->price_format($row['order_amount']);
            $product_list = $GLOBALS['dou_order']->get_order_product($row['order_id']);
            
            $order_list[] = array(
                    "order_id" => $row['order_id'],
                    "order_sn" => $row['order_sn'],
                    "telephone" => $row['telephone'],
                    "contact" => $row['contact'],
                    "order_amount" => $row['order_amount'],
                    "order_amount_format" => $order_amount_format,
                    "status" => $row['status'],
                    "status_format" => $status_format,
                    "add_time" => $add_time,
                    "product_list" => $product_list
            );
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