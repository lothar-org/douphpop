<?php
// header('Content-Type:text/html;charset=GBK');//设置文本类型和编码
ini_set('date.timezone','Asia/Shanghai');
//error_reporting(E_ERROR);

require_once "../lib/WxPay.Api.php";
require_once "WxPay.NativePay.php";
require_once 'log.php';

//模式一
/**
 * 流程：
 * 1、组装包含支付信息的url，生成二维码
 * 2、用户扫描二维码，进行支付
 * 3、确定支付之后，微信服务器会回调预先配置的回调地址，在【微信开放平台-微信支付-支付配置】中进行配置
 * 4、在接到回调通知之后，用户进行统一下单支付，并返回支付信息以完成支付（见：native_notify.php）
 * 5、支付完成之后，微信服务器会通知支付成功
 * 6、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
 */
$notify = new NativePay();
// $url1 = $notify->GetPrePayUrl("123456789");

//模式二
/**
 * 流程：
 * 1、调用统一下单，取得code_url，生成二维码
 * 2、用户扫描二维码，进行支付
 * 3、支付完成之后，微信服务器会通知支付成功
 * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
 */



// echo $title = trim($_POST['title']);
$title = trim(" Product Details ");
// echo $title = iconv('utf-8', 'gb2312', $title);
// echo $title = urlencode($title);
$orders_id = trim($_POST['orders_id']);
if (intval($_POST['price_count'])) {
    $price_count = intval(ceil($_POST['price_count']*100));
} else {
    $price_count = 1;
}


$input = new WxPayUnifiedOrder();
$input->SetBody("$title");
$input->SetAttach("add");
$input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
$input->SetTotal_fee("$price_count");
$input->SetTime_start(date("YmdHis"));
$input->SetTime_expire(date("YmdHis", time() + 600));
$input->SetGoods_tag("mark");
$input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
$input->SetTrade_type("NATIVE");
$input->SetProduct_id("$orders_id");
// var_dump($input);die();
$result = $notify->GetPayUrl($input);
$url2 = $result["code_url"];




/*
$input = new WxPayUnifiedOrder();
$input->SetBody("details");//商品描述    编码问题,不能为中文
$input->SetAttach("add");// 附加数据 编码问题,不能为中文  非必填
$input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));//商户订单号   编码问题,只能是字母、数字
$input->SetTotal_fee("2");//总金额 单位：分。不能为小数
$input->SetTime_start(date("YmdHis"));//交易起始时间   非必填
$input->SetTime_expire(date("YmdHis", time() + 600));//交易结束时间    非必填
$input->SetGoods_tag("mark");//商品标记  编码问题,不能为中文
$input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");//通知地址 
    SetNotify_url 的地址需要是公网可以访问的到的地址，且必须是完整的地址，包括协议
    填127.0.0.1是不行的，在回掉页面用
    $data = file_get_contents("php://input"); 
    var_dump($data);
    var_dump($_REQUEST);exit();
    来输出获取的数据
$input->SetTrade_type("NATIVE");//交易类型   原生NATIVE
$input->SetProduct_id("123456789");//商品ID    编码问题,不能为中文
$result = $notify->GetPayUrl($input);//转二维码
$url2 = $result["code_url"];//二维码
*/
?>

<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=GBK"/>
    <meta name="viewport" content="width=device-width, initial-scale=1" /> 
    <title>微信支付</title>
</head>
<body>
    <!-- <div style="margin-left: 10px;color:#556B2F;font-size:30px;font-weight: bolder;">扫描支付模式一</div><br/>
    <img alt="模式一扫码支付" src="http://paysdk.weixin.qq.com/example/qrcode.php?data=<?php echo urlencode($url1);?>" style="width:150px;height:150px;"/>
    <br/><br/><br/> -->
    <!-- <div style="margin-left: 10px;color:#556B2F;font-size:30px;font-weight: bolder;">扫描支付模式二</div><br/> -->
    <img alt="模式二扫码支付" src="http://paysdk.weixin.qq.com/example/qrcode.php?data=<?php echo urlencode($url2);?>" style="width:150px;height:150px;"/>
    
</body>
</html>