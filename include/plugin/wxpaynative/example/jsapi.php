<?php 
ini_set('date.timezone','Asia/Shanghai');
//error_reporting(E_ERROR);
require_once "../lib/WxPay.Api.php";
require_once "WxPay.JsApiPay.php";
require_once 'log.php';

//初始化日志
$logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

//打印输出数组信息
function printf_info($data)
{
    foreach($data as $key=>$value){
        echo "<font color='#00ff55;'>$key</font> : $value <br/>";
    }
}

//①、获取用户openid
$tools = new JsApiPay();//sdk中有这个类
$openId = $tools->GetOpenid();// 多次CURL后只能用cookie获取其它参数
// _GET _POST _REQUEST SESSION 均不可用
// session_start();
// echo $_SESSION['title'].'<br>';

// var_dump($_COOKIE);
// echo $title = trim(" Product Details ");
// echo "<br>";
$title = trim($_COOKIE['dttitle']);
$title = iconv('GBK', 'UTF-8', $title);
// // echo $title = urlencode($title);
// echo "<br>";
// echo $count = intval(trim($_COOKIE['dtcount']));
// echo "<br>";
$oid = trim($_COOKIE['dtOrdersId']);
// echo "<br>";
if($_COOKIE['dtprice']){
	$price = intval($_COOKIE['dtprice']*100);
} else {
	$price = 0;
}

// $nottfy_url = trim($_COOKIE['dtNotify_url']);

//②、调用统一下单接口，调用成功后会返回一个微信预支付订单ID（prepayid），调用JSAPI必须的参数
$input = new WxPayUnifiedOrder();
$input->SetBody("$title");
$input->SetAttach("add");// 如果 把值改为 $input->SetAttach("test this is attach");就会存在bug 后面再说，其实这个参数不是必须的干脆可以去掉。
$input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));//自定义订单号，不同与transaction_id（调用成功后的微信支付订单ID）,这两个订单号其中        一个实现退款流程，个人觉得用$Out_trade_no简单
$input->SetTotal_fee("$price");//总金额,单位：分
$input->SetTime_start(date("YmdHis"));
$input->SetTime_expire(date("YmdHis", time() + 600));
$input->SetGoods_tag("mark");
$input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");// 通知地址，是设置接收支付结果通知的Url 这里是默认的demo 链接我们可以设置成我们的：
// $input->SetNotify_url("$nottfy_url");
$input->SetTrade_type("JSAPI");
$input->SetOpenid($openId);
$order = WxPayApi::unifiedOrder($input);
// echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
// printf_info($order);
$jsApiParameters = $tools->GetJsApiParameters($order);//微信支付参数，传到模板中

//获取共享收货地址js函数参数
// $editAddress = $tools->GetEditAddressParameters();

//③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
/**
 * 注意：
 * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
 * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
 * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
 */
?>

<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/> 
    <title>微信支付</title>
    <script type="text/javascript">
	//调用微信JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',
			<?php echo $jsApiParameters; ?>,
			function(res){
				WeixinJSBridge.log(res.err_msg);
				// alert(res.err_code+res.err_desc+res.err_msg);
				// alert(res.err_code);
				// alert(res.err_desc);
				// alert(res.err_msg);
				if (res.err_msg=='get_brand_wcpay_request:ok') {
					alert("支付成功！页面跳转中...");
					setTimeout(function(){ window.location.href="/plus/shops_go.php?oid=<?php echo $oid; ?>"; },1000);
				}else if(res.err_msg=='get_brand_wcpay_request:cancel'){
				    // var oid="<?php echo $oid; ?>";
					alert("已取消微信支付！");
				} else{
					// res.err_msg=='get_brand_wcpay_request:false'
					alert("微信支付失败！");
				};
			}
		);
	}

	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
		    if( document.addEventListener ){
		        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
		    }else if (document.attachEvent){
		        document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
		        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
		    }
		}else{
		    jsApiCall();
		}
	}
	</script>
	<script type="text/javascript">
	// 获取共享地址
	// function editAddress()
	// {
	// 	WeixinJSBridge.invoke(
	// 		'editAddress',
	// 		<?php echo $editAddress; ?>,
	// 		function(res){
	// 			var value1 = res.proviceFirstStageName;
	// 			var value2 = res.addressCitySecondStageName;
	// 			var value3 = res.addressCountiesThirdStageName;
	// 			var value4 = res.addressDetailInfo;
	// 			var tel = res.telNumber;
				
	// 			alert(value1 + value2 + value3 + value4 + ":" + tel);
	// 		}
	// 	);
	// }
	
	// window.onload = function(){
	// 	if (typeof WeixinJSBridge == "undefined"){
	// 	    if( document.addEventListener ){
	// 	        document.addEventListener('WeixinJSBridgeReady', editAddress, false);
	// 	    }else if (document.attachEvent){
	// 	        document.attachEvent('WeixinJSBridgeReady', editAddress); 
	// 	        document.attachEvent('onWeixinJSBridgeReady', editAddress);
	// 	    }
	// 	}else{
	// 		editAddress();
	// 	}
	// };
	
	</script>
</head>
<body>
    <br/>
    <div align="center">
    	<font color="#9ACD32"><b>该笔订单支付金额为：<br/><span style="color:#f00;font-size:50px"><?php echo $price/100; ?>元</span></b></font>
    </div>
    <br/><br/>
	<div align="center">
		<button style="width:210px; height:50px; border-radius: 15px;background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onclick="callpay()" >立即支付</button>
	</div>
</body>
</html>