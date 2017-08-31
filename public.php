<?php
// if (!defined('IN_LOTHAR')) die('Hacking attempt');

// if (empty($_SESSION[DOU_ID]['user_id'])) {
//     unset($_SESSION['captcha']);
//     $dou->dou_msg('请登录！','user.php?rec=login');
// }

// 用户信息 $_USER 只有 user_id 和 user_name 信息
if ($_SESSION[DOU_ID]) {
    $gUid = $_SESSION[DOU_ID]['user_id'];
    if ($gUid) {
        $gUinfos = $dou->fetchRow(sprintf('SELECT * from %s where user_id=%d',$dou->table('user'),$gUid));
    }
}

// 地区 district




// $dou->debug($nav_product);
// $dou->debug($countrys);

// $dou->debug(ROOT_PATH,1);
// $dou->debug($_CFG);
// $dou->debug($GLOBALS['_CFG']);
// $dou->debug($_LANG);
// $dou->debug($_URL);
// $dou->debug($_OPEN);
// $dou->debug($_DISPLAY);
// $dou->debug($_DEFINED);
// $dou->debug($_SESSION[DOU_ID]['user_id']);
// $dou->debug($_SESSION[DOU_ID]['shell'],1);
// $dou->debug($_USER);

// unset($_SESSION);
// session_unset();
// session_destroy();
// $dou->debug($_SESSION,1);
// $dou->debug($GLOBALS,1);
// $dou->debug($_SERVER,1);








// $phpself = $_SERVER['PHP_SELF'];
// $forbid_url = array(
//         '/pay.php',
//         '/include/weixin/example/native.php',
//         '/include/weixin/return.php',
//         '/include/pay/alipayto.php',
//         '/include/pay/return_url.php'
//     );
// if (in_array($phpself,$forbid_url)) {
//     $dou->dou_msg('非法进入！');
// }

// $dou->debug(ROOT_PATH);
// $dou->debug($gUinfos,1);
?>