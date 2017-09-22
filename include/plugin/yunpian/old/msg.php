<?php
header('Content-Type:text/html;charset=UTF-8');
session_start();
// require_once '../../source/init_base.php';
// require_once ROOT_PATH . SOUR_COM .'lib\class\MsgClass.php';
/*
 * 短信发送器
*/

$config_arr['msg_apikey'] = '8d5234d8f9302e69eb75c844fd40871f';
$config_arr['msg_sign'] = '【青影玺淼】';
$config_arr['msg_retry_times'] = 3;


$apikey = $config_arr['msg_apikey']; //修改为您的apikey(https://www.yunpian.com)登录官网后获取
$retry_times = $config_arr['msg_retry_times'];// 重发间隔
$code = rand(1000,9999);
$_SESSION['msg_code'] = $code;
require_once 'YunpianAutoload.php';


// 发送单条短信
$smsOperator = new SmsOperator();
$data['mobile'] = trim($_POST['phone']); //请用自己的手机号代替 
// $text = $config_arr['msg_sign']."您的验证码是". $code ."。如非本人操作，请忽略本短信";
$data['text'] = $config_arr['msg_sign'].'您的验证码是'.$code;
$result = $smsOperator->single_send($data);

/*$result = (array)$result;
if (is_array($result)) {
    echo $result['success'];exit;
    // echo $result['statusCode'];die;
    // echo $result['error'];die;
    // echo $result['responseData']['code'];die;
    // echo $result['responseData']['msg'];die;
} else {
    $result->success;exit();
}*/

print_r($result);exit();
/*$result['responseData'] = array(
        [code] => 0
        [msg] => 发送成功
        [count] => 1
        [fee] => 0.05
        [unit] => RMB
        [mobile] => 15855156840
        [sid] => 14805430070
    );*/



/*
//发送批量短信
$data['mobile'] = '13100000000,13100000001,2,13100000003';
$result = $smsOperator->batch_send($data);
print_r($result);

//发送个性化短信
$data['mobile'] = '13000000000,13000000001,1,13000000003';
$data['text'] = '【云片网】您的验证码是1234,【云片网】您的验证码是6414,【云片网】您的验证码是0099,【云片网】您的验证码是3451';
$result = $smsOperator->multi_send($data);
print_r($result);
*/

/*
// 获取用户信息
$userOperator = new UserOperator();
$result = $userOperator->get();
print_r($result);

// 模板
$tplOperator = new TplOperator();
$result = $tplOperator->get_default(array("tpl_id"=>'2'));
print_r($result);
$result = $tplOperator->get();
print_r($result);
$result = $tplOperator->add(array("tpl_content"=>"【bb】大倪#asdf#"));
print_r($result);

*/


//GBK编码时需要转换
function convertUTF8($str){
    if(empty($str)) return '';
    // return  iconv('GBK', 'UTF-8', $str);//转成UTF-8
    return $str;
}
exit;
?>