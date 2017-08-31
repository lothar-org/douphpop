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
define('IN_LOTHAR', true);
define('EXIT_INIT', true);

require (dirname(__FILE__) . '/include/init.php');
require (ROOT_PATH . 'include/captcha.class.php');

// 开启SESSION
if (empty(session_id())) session_start();

// 实例化验证码
$captcha = new Captcha(70, 25);

// 清除之前出现的多余输入  $_SESSION['captcha'] = md5($word.DOU_SHELL);
@ob_end_clean();

$captcha->create_captcha();

?>