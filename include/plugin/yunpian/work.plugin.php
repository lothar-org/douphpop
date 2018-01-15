<?php
if (!defined('IN_LOTHAR')) die('Hacking attempt');

class Plugin {
    var $plugin_id = 'yunpian'; // 插件唯一ID

    /**
     * +----------------------------------------------------------
     * 构造函数
     * $mobile 对象手机号
     * +----------------------------------------------------------
     */
    function __construct($mobile='') {
        $this->mobile = trim($mobile);
        $this->config = $this->p_config();
    }

    /**
     * +----------------------------------------------------------
     * 建立请求
     * +----------------------------------------------------------
     * $session_cart session储存的商品信息
     * +----------------------------------------------------------
     */
    function work() {
        // 建立请求
        $code = rand(1000,9999);
        $_SESSION['msg_code'] = $code;

        // require ROOT_PATH .'include/plugin/yunpian/YunpianAutoload.php';
        // require_once dirname(__FILE__) .'YunpianAutoload.php';
        require_once 'YunpianAutoload.php';

        // 发送单条短信
        $smsOperator = new SmsOperator();
        $data['mobile'] = $this->mobile; //发送对象手机号 
        // $text = $this->config['sign']."您的验证码是". $code ."。如非本人操作，请忽略本短信";
        $data['text'] = $this->config['sign'].'您的验证码是'.$code;
        $result = $smsOperator->single_send($data);

        $result = (array)$result;
        if ($result['success']) {
            return true;
        } else {
            return false;
        }

        // var_dump($result);die;
        /*// 原始数据
        // 成功
        object(Result)#26 (5) {
          ["success"]=>
          bool(true)
          ["statusCode"]=>
          int(200)
          ["requestData"]=>
          array(3) {
            ["mobile"]=>
            string(11) "18715511536"
            ["text"]=>
            string(49) "【微步大数据营销】您的验证码是3436"
            ["apikey"]=>
            string(32) "96984feab7ee7412c616fbe854245dbd"
          }
          ["responseData"]=>
          array(7) {
            ["code"]=>
            int(0)
            ["msg"]=>
            string(12) "发送成功"
            ["count"]=>
            int(1)
            ["fee"]=>
            float(0.05)
            ["unit"]=>
            string(3) "RMB"
            ["mobile"]=>
            string(11) "18715511536"
            ["sid"]=>
            float(17550673356)
          }
          ["error"]=>
          NULL
        }

        // 失败
        object(Result)#26 (5) {
          ["success"]=>
          bool(false)
          ["statusCode"]=>
          int(400)
          ["requestData"]=>
          array(3) {
            ["mobile"]=>
            string(11) "18715511536"
            ["text"]=>
            string(49) "【微步大数据营销】您的验证码是5450"
            ["apikey"]=>
            string(32) "96984feab7ee7412c616fbe854245dbd"
          }
          ["responseData"]=>
          array(4) {
            ["http_status_code"]=>
            int(400)
            ["code"]=>
            int(22)
            ["msg"]=>
            string(71) "验证码类短信1小时内同一手机号发送次数不能超过3次"
            ["detail"]=>
            string(71) "验证码类短信1小时内同一手机号发送次数不能超过3次"
          }
          ["error"]=>
          NULL
        }
        */
    }

    /**
     * +----------------------------------------------------------
     * 配置信息
     * +----------------------------------------------------------
     */
    function p_config() {
        // 获取插件配置信息
        $plugin = $GLOBALS['dou']->get_plugin($this->plugin_id);
        
        // 短信账号
        $p_config['account']  = $plugin['config']['account'];
        
        // 签名
        $p_config['sign'] = $plugin['config']['sign'];
        
        // 安全检验码，以数字和字母组成的32位字符
        $p_config['apikey']   = $plugin['config']['apikey'];
        
        // 重发间隔，纯数字
        $p_config['retry_times']    = $plugin['config']['retry_times'];
        
        // 字符编码
        $p_config['charset']    = $plugin['config']['charset'];
        
        return $p_config;
    }

    /**
     * +----------------------------------------------------------
     * 请求参数
     * +----------------------------------------------------------
     */
    function parameter() {
        // 获取插件配置信息
        $plugin = $GLOBALS['dou']->get_plugin($this->plugin_id);

        // 字符编码格式 目前支持 gbk 或 utf-8
        $parameter['_input_charset'] = trim(strtolower(strtolower('utf-8')));

        return $parameter;
    }
}
?>