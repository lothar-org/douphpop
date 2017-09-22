<?php
/*通知页面*/
define('IN_LOTHAR', true);
require ('../../init.php');

// 引入和实例化订单功能
include_once (ROOT_PATH . 'include/order.class.php');
$dou_order = new Order();

// 实例化插件
require_once("work.plugin.php");
$plugin = new Plugin();

require_once("lib/alipay_notify.class.php");

//计算得出通知验证结果
$alipayNotify = new AlipayNotify($plugin->p_config());
$verify_result = $alipayNotify->verifyNotify();

if($verify_result) {//验证成功
    //请在这里加上商户的业务逻辑程序代
    //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
    
    //商户订单号
    $out_trade_no = $_POST['out_trade_no'];
   
    //支付宝交易号
    $trade_no = $_POST['trade_no'];
   
    //交易状态
    $trade_status = $_POST['trade_status'];


    if($_POST['trade_status'] == 'TRADE_FINISHED') {
        $dou_order->change_status($out_trade_no, 1);
    } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
        $dou_order->change_status($out_trade_no, 1);
    }

    //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
        
    echo "success";  //请不要修改或删除
 
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
    //验证失败
    echo "fail";

    //调试用，写文本函数记录程序运行情况是否正常
    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
}
?>