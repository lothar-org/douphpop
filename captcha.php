<?php
define('IN_LOTHAR', true);
define('EXIT_INIT', true);

require (dirname(__FILE__) . '/include/init.php');

/*发送短信验证码*/
if (isset($_POST['mobile'])) {
    extract($_POST);
    // 判断验证码
    // $word = strtoupper($GLOBALS['dou']->create_rand_string('nl', 4));
    // $_SESSION['captcha'] = md5($word . DOU_SHELL);
    if ($_POST['imgCode']) {
        $captcha = $check->is_captcha($imgCode) ? strtoupper($imgCode) : '';
        if ($_CFG['captcha'] && md5($captcha . DOU_SHELL) != $_SESSION['captcha'])
            $dou->djson(0,$_LANG['captcha_wrong']);
    }

    // 验证码正确后，检测数据库是否有该手机号
    if ($status==3) {
        if (!$dou->value_exist('user','telephone',$mobile)) {
            $dou->djson(0,'系统未检测到该手机号，该用户可能被管理员删除');
        }
    } elseif ($status==2) {
        if ($dou->value_exist('user','telephone',$mobile)) {
            $dou->djson(0,'系统检测到已有此手机号，请登录或找回密码');
        }
    }

    // 发送短信验证码
    $result = $dou->send_msg($mobile);
    if ($result) {
        $dou->djson(1,'正在向该手机号发送验证码，请耐心等待');
    } else {
        $dou->djson(0,'发送失败');
    }
}

require (ROOT_PATH . 'include/captcha.class.php');

// 开启SESSION
if (empty(session_id())) session_start();

// 实例化验证码
$captcha = new Captcha(70, 25);

// 清除之前出现的多余输入  $_SESSION['captcha'] = md5($word.DOU_SHELL);
@ob_end_clean();

$captcha->create_captcha();
?>