<?php 
// JSAPI支付
define('IN_DOUCO', true);
require_once ('../init.php');
require_once ROOT_PATH.'public.php';
if ($gUinfos['is_finish']) {
    $dou->dou_msg('您已支付，无需再支付',ROOT_HOST.'/order.php');
}
require_once '../check_order.php';

// 验单
$out_trade_no = $gUinfos['orderid']?$gUinfos['orderid']:($_GET['out_trade_no']?$_GET['out_trade_no']:$_SESSION['wx_out_trade_no']);// 订单号
$res = QueryorderByLothar($out_trade_no);
$total_fee = isset($res['cash_fee'])?($res['cash_fee']/100):($gUinfos['money']?$gUinfos['money']:$_GET['total_fee']);

// 支付操作
if ($get_brand_wcpay_request=='ok' || $res['is_wxpay']) {
    $uid = $gUid ? $gUid : $_SESSION[DOU_ID]['user_id'];
    if (!$uid) {
        $dou->dou_msg('用户信息丢失！',ROOT_HOST.'/pay.php');
    }
    // 交易号 transaction_id ?
    $payment = 'wx_'.strtolower($res['trade_type']);
    $payinfo = serialize($res);
    $paydata = array(
            'is_finish'  => 1,
            'payment'  => $payment,
            'payinfo'  => $payinfo
        );
    $paywh = 'user_id='.$uid;
    $affected_rows = $dou->update('user',$paydata,$paywh);
    if ($affected_rows) {
        $dou->dou_msg('支付成功',ROOT_HOST.'/order.php');
    } else {
        $dou->dou_msg('支付失败，请不扫码点跳转试试',ROOT_URL.'example/native.php?money='.$total_fee);
    }
} elseif ($get_brand_wcpay_request=='bad' || !$res['is_wxpay']) {
    $dou->dou_msg('系统检测出您未支付，如有问题请联系管理员',ROOT_URL.'example/native.php?money='.$total_fee);
} else {
    $dou->dou_msg('支付异常',ROOT_HOST.'/pay.php');
}
?>