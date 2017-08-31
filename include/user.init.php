<?php
if (!defined('IN_LOTHAR'))
    die('Hacking attempt');

require (ROOT_PATH . 'include/user.class.php');

// 初始化
if (empty(session_id())) session_start();
$dou_user = new DouUser();
$smarty->assign('user', $_USER = $dou_user->global_user_info());
?>