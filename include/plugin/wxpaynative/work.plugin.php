<?php
/*// 用于测试
define('IN_LOTHAR', true);
include_once dirname(dirname(dirname(__FILE__))) .'/init.php';*/

if (!defined('IN_LOTHAR')) die('Hacking attempt');// 测试时请注释

class Plugin {
    var $plugin_id = 'wxpaynative'; // 插件唯一ID

    /**
     * +----------------------------------------------------------
     * 构造函数
     * $order_sn 订单编号
     * $order_amount 订单金额
     * +----------------------------------------------------------
     */
    function __construct($order_sn = '', $order_amount = '') {
        $this->order_sn = $order_sn;
        $this->order_amount = intval(ceil($order_amount*100));
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
        require_once(ROOT_PATH . 'include/plugin/' . $this->plugin_id . '/lib/WxPay.Api.php');
        require_once(ROOT_PATH . 'include/plugin/' . $this->plugin_id . '/lib/WxPay.NativePay.php');
        // require_once ROOT_PATH .'include/plugin/' . $this->plugin_id . '/example/log.php';

        $notify = new NativePay();

        $input = new WxPayUnifiedOrder();
        $input->SetBody("Product Details");
        $input->SetAttach("add");
        $input->SetOut_trade_no($this->order_sn);// WxPayConfig::MCHID.date("YmdHis")
        $input->SetTotal_fee($this->order_amount);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("mark");
        $input->SetNotify_url("http://marketing.wincomtech.cn/notify_url.php");
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($this->order_sn);
        // var_dump($input);die();
        $result = $notify->GetPayUrl($input);
        $url2 = $result["code_url"];

        return 'http://paysdk.weixin.qq.com/example/qrcode.php?data='.urlencode($url2);//二维码图片链接 183x227
    }

    /**
     * +----------------------------------------------------------
     * 支付URL
     * +----------------------------------------------------------
     * 
     * +----------------------------------------------------------
     */
    function workurl() {
        $sResult = $this->work();

        // URL跳转
        // $sResult = str_replace('&amp','&',$sResult);// 替换实体字符
        $sResult = '<img src="'.$sResult.'">';
        echo $sResult;die;
        echo '<script src="'.THEME_S.'js/jquery-1.12.1.min.js"></script><script type="text/javascript">window.location.href="'.$sResult.'"</script>';exit;
        return $sResult;
    }

    /**
     * +----------------------------------------------------------
     * 直达支付页面
     * +----------------------------------------------------------
     * $session_cart session储存的商品信息
     * +----------------------------------------------------------
     */
    function workcurl() {

    }

    /**
     * +----------------------------------------------------------
     * 查询订单
     * +----------------------------------------------------------
    */
    function OrderStatus() {
        // require_once(ROOT_PATH . 'include/plugin/' . $this->plugin_id . '/notify_url.php');

        require_once(ROOT_PATH . 'include/plugin/' . $this->plugin_id . '/lib/WxPay.Api.php');
        // require_once(ROOT_PATH . 'include/plugin/' . $this->plugin_id . '/lib/WxPay.Notify.php');

        //查询订单
        $inputObj = new WxPayOrderQuery();
        $inputObj->SetOut_trade_no($this->order_sn);
        $res = WxPayApi::orderQuery($inputObj);
        // Log::DEBUG("query:" . json_encode($res));
        if ( array_key_exists('return_code',$res) && 
            $res['return_code']=='SUCCESS' && 
            array_key_exists('result_code',$res) && 
            $res['result_code']=='SUCCESS' && 
            $res['trade_state']=='SUCCESS' )
        {
            return true;
            // $res['wxpay_status'] = true;
        } else {
            return false;
            // $res['wxpay_status'] = false;
        }
        return $res;
    }

    /**
     * +----------------------------------------------------------
     * 配置信息
     * +----------------------------------------------------------
     */
    function p_config() {
        // 获取插件配置信息
        $plugin = $GLOBALS['dou']->get_plugin($this->plugin_id);
        
        // 微信账户
        $p_config['account'] = $plugin['config']['account'];
        
        // 绑定支付的APPID
        $p_config['appid'] = $plugin['config']['appid'];
        // 公众帐号secert
        $p_config['appsecret'] = $plugin['config']['appsecret'];
        
        // 商户号
        $p_config['mchid'] = $plugin['config']['mchid'];
        // 商户支付密钥
        $p_config['key'] = $plugin['config']['key'];
        
        // 字符编码格式 目前支持 gbk 或 utf-8
        // $p_config['input_charset']= strtolower('utf-8');
        // $p_config['input_charset']= strtolower('gbk');
        
        // ca证书路径地址，用于curl中ssl校验
        // 请保证cacert.pem文件在当前文件夹目录中
        // $p_config['cacert']    = ROOT_PATH . 'include/plugin/' . $this->plugin_id . '/cacert.pem';
        
        // 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        // $p_config['transport']    = 'http';
        
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

        $parameter['service'] = "create_direct_pay_by_user";
        
        // 合作身份者id，以2088开头的16位纯数字
        $parameter['partner'] = trim($plugin['config']['partner']);
        
        // 收款支付宝账号
        $parameter['seller_email'] = trim($plugin['config']['seller_email']);
        
        //支付类型，必填，不能修改
        $parameter['payment_type'] = "1";
        
        //服务器异步通知页面路径，需http://格式的完整路径，不能加?id=123这类自定义参数
        $parameter['notify_url'] = ROOT_URL . 'include/plugin/' . $this->plugin_id . '/notify_url.php';
        
        //页面跳转同步通知页面路径，需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
        $parameter['return_url'] = ROOT_URL . 'include/plugin/' . $this->plugin_id . '/return_url.php';
        
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $parameter['out_trade_no'] = $this->order_sn;
        
        //订单名称，必填
        $parameter['subject'] = 'Order Sn : ' . $this->order_sn . ' (' . $GLOBALS['_CFG']['site_name'] . ')';
        
        //付款金额，必填
        $parameter['total_fee'] = $this->order_amount;
        
        //订单描述
        $parameter['body'] = "";
        
        //商品展示地址，需以http://开头的完整路径，例如：http://www.商户网址.com/myorder.html
        $parameter['show_url'] = "";
        
        //防钓鱼时间戳，若要使用请调用类文件submit中的query_timestamp函数
        $parameter['anti_phishing_key'] = "";
        
        //客户端的IP地址，非局域网的外网IP地址，如：221.0.0.1
        $parameter['exter_invoke_ip'] = "";
        
        // 字符编码格式 目前支持 gbk 或 utf-8
        $parameter['_input_charset'] = trim(strtolower(strtolower('utf-8')));

        return $parameter;
    }
}
/*// 用于测试
$aa = new Plugin('wqwq','2');
echo $bb = $aa->work();
echo '<br><img src="'.$bb.'" alt="">';*/
?>