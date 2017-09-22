<?php
/*
* 回调页面
* old/msg.php
*/
define('IN_LOTHAR', true);
require ('../../init.php');

// 引入和实例化订单功能
require_once (ROOT_PATH . 'include/order.class.php');
$dou_order = new Order();

// 实例化插件
require_once("work.plugin.php");
$plugin = new Plugin();

require_once("lib/alipay_notify.class.php");
?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
//计算得出通知验证结果
$alipayNotify = new AlipayNotify($plugin->p_config());
$verify_result = $alipayNotify->verifyReturn();
if ($verify_result) {//验证成功
    //请在这里加上商户的业务逻辑程序代码
    
    //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
    
    //商户订单号
    $out_trade_no = $_GET['out_trade_no'];
    
    //支付宝交易号
    $trade_no = $_GET['trade_no'];
    
    //交易状态
    $trade_status = $_GET['trade_status'];


    if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
        $dou_order->change_status($out_trade_no, 1);
    } else {
        echo "trade_status=".$_GET['trade_status'];
    }
  
    $dou->dou_header($_URL['order_list']);

    //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
}
else {
    //验证失败
    //如要调试，请看alipay_notify.php页面的verifyReturn函数
    $dou->dou_header(ROOT_URL);
}
?>
<title>支付宝即时到账交易接口</title>
</head>
<body>
</body>
</html>