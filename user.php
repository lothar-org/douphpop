<?php
define('IN_LOTHAR', true);
$sub = 'login|login_post|register|register_post|edit|edit_post|password|password_post|password_reset|password_reset_post|logout|order_list|order|order_cancel';
$subbox = array(
        "module" => 'user',
        "sub" => $sub
);
require (dirname(__FILE__) . '/include/init.php');
// require ROOT_PATH .'public.php';

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

// 引入和实例化订单功能
if (file_exists($order_file = ROOT_PATH . 'include/order.class.php')) {
    include_once ($order_file);
    $dou_order = new Order();
}

$no_login = 'login|login_post|register|register_post|password_reset|password_reset_post';// 设定不需要登录权限的页面
if (!in_array($rec, explode('|', $no_login)) && !is_array($_USER)) // 需要登录且没有登录的情况
    $dou->dou_header($_URL['login']);
if (in_array($rec, explode('|', $no_login)) && is_array($_USER)) // 不需要登录却登录的情况
    $dou->dou_header($_URL['user']);

// 赋值给模板-meta和title信息
$smarty->assign('keywords', $_CFG['site_keywords']);
$smarty->assign('description', $_CFG['site_description']);

// 赋值给模板-导航栏
$smarty->assign('nav_top_list', $dou->get_nav('top'));
$smarty->assign('nav_middle_list', $dou->get_nav('middle', 0, 'user', 0));
$smarty->assign('nav_bottom_list', $dou->get_nav('bottom'));

// 赋值给模板-数据
$smarty->assign('rec', $rec);

/**
 * +----------------------------------------------------------
 * 会员中心
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    // 获取会员信息
    $user_info = $dou_user->get_user_info($_USER['user_id']);

    // 格式化信息
    $user_info['last_login'] = date("Y-m-d H:i:s", $dou->get_first_log($user_info['last_login']));
    $user_info['last_ip'] = $dou->get_first_log($user_info['last_ip']);

    $smarty->assign('page_title', $dou->page_title('user'));
    $smarty->assign('ur_here', $dou->ur_here('user'));
    $smarty->assign('user_info', $user_info);
    $smarty->assign('order_list', $dou_user->get_order_list($_USER['user_id']));
    $smarty->display('user.dwt');
}

/**
 * +----------------------------------------------------------
 * 登录注册页面
 * +----------------------------------------------------------
 */
elseif ($rec == 'register') {
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->set_token('user_register'));
    
    // 赋值给模板
    $smarty->assign('page_title', $dou->page_title('user', 'user_register'));
    $smarty->assign('ur_here', $dou->ur_here('user', 'user_register'));
    
    $smarty->display('user.dwt');
}

/**
 * +----------------------------------------------------------
 * 注册提交
 * +----------------------------------------------------------
 */
elseif ($rec == 'register_post') {
    // 安全处理用户输入信息
    $_POST['email'] = $firewall->dou_foreground($_POST['email']);
    
    // 验证用户名
    if (!$check->is_email($_POST['email'])) {
        $wrong['email'] = $_LANG['user_email_cue'];
    } elseif ($dou->value_exist('user', 'email', $_POST['email'])) {
        $wrong['email'] = $_LANG['user_email_exists'];
    }
    
    // 验证密码
    if (!$check->is_password($_POST['password']))
        $wrong['password'] = $_LANG['user_password_cue'];
    
    // 验证确认密码
    if (!isset($wrong['password']) && ($_POST['password_confirm'] !== $_POST['password']))
        $wrong['password_confirm'] = $_LANG['user_password_confirm_cue'];
    
    // AJAX验证表单
    if ($_REQUEST['do'] == 'callback') {
        if ($wrong) {
            foreach ($_POST as $key => $value) {
                $wrong_json[$key] = $wrong[$key];
            }
            echo json_encode($wrong_json);
        }
        exit;
    }

    if ($wrong) {
        foreach ($wrong as $key => $value) {
            $wrong_format .= $wrong[$key] . '<br>';
        }
        $dou->dou_msg($wrong_format, $_URL['user']);
    }
    
    $password = md5($_POST['password']);
    $add_time = time();
    
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token'], 'user_register');
    
    $sql = "INSERT INTO " . $dou->table('user') . " (user_id, email, password, add_time)" . " VALUES (NULL, '$_POST[email]', '$password', '$add_time')";
    $dou->query($sql);
        
    // 注册成功后直接登录
    $user = $dou->fetch_array($dou->select($dou->table('user'), '*', "email = '$_POST[email]'"));
    
    // 将会员登录信息写入SESSION
    $_SESSION[DOU_ID]['user_id'] = $user['user_id'];
    $_SESSION[DOU_ID]['shell'] = md5($user['email'] . $user['password'] . DOU_SHELL);
    $_SESSION[DOU_ID]['ontime'] = time();
    
    $last_login = time();
    $last_ip = $dou->get_ip();
    $login_count = $user['login_count'] + 1;
    $data = array(
            'login_count'  => $login_count,
            'last_login'  => $last_login,
            'last_ip'  => $last_ip
        );
    $dou->update('user',$data,'user_id='.$user['user_id']);

    $dou->dou_msg($_LANG['user_insert_success'], $_URL['user']);
}

/**
 * +----------------------------------------------------------
 * 登录页
 * +----------------------------------------------------------
 */
elseif ($rec == 'login') {
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->set_token('user_login'));
    
    $return_url = $_REQUEST['return_url'] ? $_REQUEST['return_url'] : urlencode($_SERVER['HTTP_REFERER']);
    
    // 赋值给模板息
    $smarty->assign('page_title', $dou->page_title('user', 'user_login'));
    $smarty->assign('ur_here', $dou->ur_here('user', 'user_login'));
    $smarty->assign('return_url', $return_url);
    
    $smarty->display('user.dwt');
}

/**
 * +----------------------------------------------------------
 * 登录提交
 * +----------------------------------------------------------
 */
elseif ($rec == 'login_post') {
    if (!$_POST['email'])
        $dou->dou_msg($_LANG['user_email_cue'], $_URL['login']);
    
    $_POST['email'] = $check->is_email(trim($_POST['email'])) ? trim($_POST['email']) : '';
    $_POST['password'] = $check->is_password(trim($_POST['password'])) ? trim($_POST['password']) : '';
    
    // 如果用户名存在则获取用户信息
    $user = $dou->fetch_array($dou->select($dou->table('user'), '*', "email = '$_POST[email]'"));
    
    // 验证用户是否存在和密码是否正确
    if (!is_array($user)) {
        $dou->dou_msg($_LANG['user_login_wrong'], $_URL['login']);
    } elseif (md5($_POST['password']) != $user['password']) {
        $dou->dou_msg($_LANG['user_login_wrong'], $_URL['login']);
    }
    
    // 会员登录信息验证成功则写入SESSION
    $_SESSION[DOU_ID]['user_id'] = $user['user_id'];
    $_SESSION[DOU_ID]['shell'] = md5($user['email'] . $user['password'] . DOU_SHELL);
    $_SESSION[DOU_ID]['ontime'] = time();
    
    $last_login = $dou_user->log_update($user['last_login'], time());
    $last_ip = $dou_user->log_update($user['last_ip'], $dou->get_ip());
    $login_count = $user['login_count'] + 1;
    
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token'], 'user_login');
    
    $dou->query("update " . $dou->table('user') . " SET login_count = '$login_count', last_login = '$last_login', last_ip = '$last_ip' WHERE user_id = " . $user['user_id']);
    
    $dou->dou_msg($_LANG['user_login_success'], urldecode($_POST['return_url']));
}

/**
 * +----------------------------------------------------------
 * 重置密码
 * +----------------------------------------------------------
 */
elseif ($rec == 'password_reset') {
    // uid和code 是区分是否是用户通过 邮箱打开 传过来的验证数据
    $user_id = $check->is_number($_REQUEST['uid']) ? $_REQUEST['uid'] : '';
    $code = preg_match("/^[a-zA-Z0-9]+$/", $_REQUEST['code']) ? $_REQUEST['code'] : '';

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->set_token('user_password_reset'));
    
    if ($user_id && $code) {
        if (!$dou_user->check_password_reset($user_id, $code)) {
            $dou->dou_msg($_LANG['user_password_reset_fail'], ROOT_URL, 30);
        }
        $smarty->assign('user_id', $user_id);
        $smarty->assign('code', $code);
        $smarty->assign('action', 'reset');
    } else {
        $smarty->assign('action', 'default');
    }
    
    // 赋值给模板
    $smarty->assign('page_title', $dou->page_title('user', 'user_password_reset'));
    $smarty->assign('ur_here', $dou->ur_here('user', 'user_password_reset'));

    $smarty->display('user.dwt');
}

/**
 * +----------------------------------------------------------
 * 重置密码提交
 * +----------------------------------------------------------
 */
elseif ($rec == 'password_reset_post') {
    // action 为reset时，说明用户已通过邮箱打开了验证
    $action = $_POST['action'] == 'reset' ? 'reset' : 'default';
    
    if ($action == 'default') {
        // 验证用户名
        if (!$dou->value_exist('user', 'email', $_POST['email']))
            $dou->dou_msg($_LANG['user_email_no_exist'], $_URL['password_reset']);
    
        // 判断验证码
        $captcha = $check->is_captcha($_POST['captcha']) ? strtoupper($_POST['captcha']) : '';
        if ($_CFG['captcha'] && md5($captcha . DOU_SHELL) != $_SESSION['captcha'])
            $dou->dou_msg($_LANG['captcha_wrong'], $_URL['password_reset']);
        
        // CSRF防御令牌验证
        $firewall->check_token($_POST['token'], 'user_password_reset');
        
        // 生成密码找回码
        $user = $dou->fetch_array($dou->select($dou->table('user'), '*', "email = '$_POST[email]'"));
        $time = time();
        $code = substr(md5($user['email'] . $user['password'] . $time . $user['last_login'] . DOU_SHELL) , 0 , 16) . $time;
        $site_url = rtrim(ROOT_URL, '/');
        $mark = strpos($_URL['password_reset'], '?') !== false ? '&' : '?';
        
        $body = $user['email'] . $_LANG['user_password_reset_body_0'] . $_URL['password_reset'] . $mark . 'uid=' . $user['user_id'] . '&code=' . $code . $_LANG['user_password_reset_body_1'] . $_CFG['site_name'] . '. ' . $site_url;
        /*d4dsr@qq.com您好！
        您正在进行密码找回操作,请点击下面的链接重置您的密码(如无法打开，可以直接复制地址到您的浏览器)：
        http://tx.ext2/user.php?rec=password_reset&uid=2&code=c6f581dcb9a1080c1502356633
        (此链接有效期为24小时，如果您没有提交密码重置的请求，请忽略这封邮件)
        成运网站. http://tx.ext2*/
        
        // 发送密码重置邮件
        if ($dou->send_mail($_POST['email'], $_LANG['user_password_reset_title'], $body)) {
            $dou->dou_msg($_LANG['user_password_mail_success'] . $user['email'], ROOT_URL, 30);
        } else {
            $dou->dou_msg($_LANG['mail_send_fail'], $_URL['password_reset'], 30);
        }
    } elseif ($action == 'reset') {
        // 验证密码
        if (!$check->is_password($_POST['password'])) {
            $dou->dou_msg($_LANG['user_password_cue']);
        } elseif (($_POST['password_confirm'] !== $_POST['password'])) {
            $dou->dou_msg($_LANG['user_password_confirm_cue']);
        }

        $user_id = $check->is_number($_POST['user_id']) ? $_POST['user_id'] : '';
        $code = preg_match("/^[a-zA-Z0-9]+$/", $_POST['code']) ? $_POST['code'] : '';
        
        if ($dou_user->check_password_reset($user_id, $code)) {
            // 重置密码
            $sql = "UPDATE " . $dou->table('user') . " SET password = '" . md5($_POST['password']) . "' WHERE user_id = '$user_id'";
            $dou->query($sql);
            $dou->dou_msg($_LANG['user_password_reset_success'], $_URL['login'], 15);
        } else {
            $dou->dou_msg($_LANG['user_password_reset_fail'], ROOT_URL, 30);
        }
    }
}

/**
 * +----------------------------------------------------------
 * 会员信息编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit') {
    $user_info = $dou_user->get_user_info($_USER['user_id']);

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->set_token('user_edit'));
    
    // 格式化自定义参数
    if ($_DEFINED['user'] || $user_info['defined']) {
        $defined = explode(',', $_DEFINED['user']);
        foreach ($defined as $row) {
            $defined_user .= $row . "：\n";
        }
        // 如果商品中已经写入自定义参数则调用已有的
        $user_info['defined'] = $user_info['defined'] ? str_replace(",", "\n", $user_info['defined']) : trim($defined_user);
        $user_info['defined_count'] = count(explode("\n", $user_info['defined'])) * 2;
    }

    // 赋值给模板
    $smarty->assign('page_title', $dou->page_title('user', 'user_edit'));
    $smarty->assign('ur_here', $dou->ur_here('user', 'user_edit'));
    $smarty->assign('user_info', $user_info);

    $smarty->display('user.dwt');
}

/**
 * +----------------------------------------------------------
 * 会员信息编辑提交
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit_post') {
    // 验证昵称
    if (isset($_POST['nickname']) && $check->is_illegal_char($_POST['nickname']))
        $wrong['nickname'] = $_LANG['user_nickname'] . $_LANG['illegal_char'];

    // 验证手机
    if (!$check->is_telephone($_POST['telephone']))
        $wrong['telephone'] = $_LANG['user_telephone_cue'];

    // 验证联系人
    if (!$_POST['contact']) {
        $wrong['contact'] = $_LANG['user_contact_empty'];
    } elseif ($check->is_illegal_char($_POST['contact'])) {
        $wrong['contact'] = $_LANG['user_contact'] . $_LANG['illegal_char'];
    }

    // 验证地址
    if (!$_POST['address']) {
        $wrong['address'] = $_LANG['user_address_empty'];
    } elseif ($check->is_illegal_char($_POST['address'])) {
        $wrong['address'] = $_LANG['user_address'] . $_LANG['illegal_char'];
    }

    // 验证邮政编码
    if (isset($_POST['postcode']) && !$check->is_postcode($_POST['postcode']))
        $wrong['postcode'] = $_LANG['user_postcode_cue'];

    // AJAX验证表单
    if ($_REQUEST['do'] == 'callback') {
        if ($wrong) {
            foreach ($_POST as $key => $value) {
                $wrong_json[$key] = $wrong[$key];
            }
            echo json_encode($wrong_json);
        }
        exit;
    }
    
    if ($wrong) {
        foreach ($wrong as $key => $value) {
            $wrong_format .= $wrong[$key] . '<br>';
        }
        $dou->dou_msg($wrong_format, $_URL['edit']);
    }
    
    // 格式化自定义参数
    $_POST['defined'] = str_replace("\r\n", ',', $_POST['defined']);

    // CSRF防御令牌验证
    $firewall->check_token($_POST['token'], 'user_edit');
    
    // 安全处理用户输入信息
    $_POST = $firewall->dou_foreground($_POST);
    $data = array(
            'nickname'  => $_POST['nickname'],
            'telephone'  => $_POST['telephone'],
            'contact'  => $_POST['contact'],
            'address'  => $_POST['address'],
            'postcode'  => $_POST['postcode'],
            'sex'  => $_POST['sex'],
            'defined'  => $_POST['defined'],
        );
    $dou->update('user',$data,'user_id='.$_USER['user_id']);
    
    $dou->dou_msg($_LANG['user_edit_success'], $_URL['edit']);
}

/**
 * +----------------------------------------------------------
 * 密码修改
 * +----------------------------------------------------------
 */
elseif ($rec == 'password') {
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->set_token('user_password'));

    // 赋值给模板
    $smarty->assign('page_title', $dou->page_title('user', 'user_password_edit'));
    $smarty->assign('ur_here', $dou->ur_here('user', 'user_password_edit'));

    $smarty->display('user.dwt');
}

/**
 * +----------------------------------------------------------
 * 密码修改提交
 * +----------------------------------------------------------
 */
elseif ($rec == 'password_post') {
    // 获取旧密码
    $old_password = $dou->get_one("SELECT password FROM " . $dou->table('user') . " WHERE user_id = '$_USER[user_id]'");

    // 验证原始密码密码
    if (md5($_POST['old_password']) != $old_password)
        $dou->dou_msg($_LANG['user_old_password_cue'], $_URL['password']);

    // 验证密码
    if (!$check->is_password($_POST['password']))
        $dou->dou_msg($_LANG['user_password_cue'], $_URL['password']);
    
    // 验证确认密码
    if (!isset($wrong['password']) && ($_POST['password_confirm'] !== $_POST['password']))
        $dou->dou_msg($_LANG['user_password_confirm_cue'], $_URL['password']);
    
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token'], 'user_password');
    
    $sql = "UPDATE " . $dou->table('user') . " SET password = '" . md5($_POST['password']) . "' WHERE user_id = '$_USER[user_id]'";
    $dou->query($sql);
    
    $dou->dou_msg($_LANG['user_password_success'], $_URL['edit']);
}

/**
 * +----------------------------------------------------------
 * 退出登录
 * +----------------------------------------------------------
 */
elseif ($rec == 'logout') {
    unset($_SESSION[DOU_ID]);
    $dou->dou_header(ROOT_URL);
}

/**
 * +----------------------------------------------------------
 * 我的订单
 * +----------------------------------------------------------
 */
elseif ($rec == 'order_list') {
    // 公用SQL查询条件，分页中也使用
    $where = " WHERE user_id = '$_USER[user_id]'";

    // 验证并获取合法的分页ID
    $page = $check->is_number($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    $limit = $dou->pager('order', 15, $page, $_URL['order_list'], $where);
    
    $query = $dou->query("SELECT * FROM " . $dou->table('order') . $where . " ORDER BY order_id DESC" . $limit);
    while ($row = $dou->fetch_array($query)) {
        $email = $dou->get_one("SELECT email FROM " . $dou->table('user') . " WHERE user_id = '$row[user_id]'");
        $add_time = date("Y-m-d h:i:s", $row['add_time']);
        $status_format = $_LANG['order_status_' . $row['status']];
        $order_amount_format = $dou->price_format($row['order_amount']);
        $product_list = $dou_order->get_order_product($row['order_id']);

        // 是否显示支付按钮
        if ($dou->get_plugin($row['pay_id']))
            $if_payment = true;
        
        $order_list[] = array(
                "order_id" => $row['order_id'],
                "order_sn" => $row['order_sn'],
                "email" => $email,
                "telephone" => $row['telephone'],
                "contact" => $row['contact'],
                "order_amount" => $row['order_amount'],
                "order_amount_format" => $order_amount_format,
                "status" => $row['status'],
                "status_format" => $status_format,
                "if_payment" => $if_payment,
                "add_time" => $add_time,
                "product_list" => $product_list
        );
    }

    // 赋值给模板
    $smarty->assign('page_title', $dou->page_title('user', 'order_my'));
    $smarty->assign('ur_here', $dou->ur_here('user', 'order_my'));
    $smarty->assign('order_list', $order_list);

    $smarty->display('user.dwt');
}

/**
 * +----------------------------------------------------------
 * 订单详情
 * +----------------------------------------------------------
 */
elseif ($rec == 'order') {
    // 验证并获取合法的ID
    $order_sn = $check->is_number($_REQUEST['order_sn']) ? $_REQUEST['order_sn'] : '';
    
    $query = $dou->select($dou->table('order'), '*', "order_sn = '$order_sn' AND user_id = '$_USER[user_id]'");
    $order = $dou->fetch_array($query);
    
    // 判断订单是否存在
    if (!$order) $dou->dou_header($_URL['order_list']);
    
    // 格式化订单信息
    $order['pay_name'] = $dou->get_one("SELECT name FROM " . $dou->table('plugin') . " WHERE unique_id = '$order[pay_id]'");
    $order['shipping_name'] = $dou->get_one("SELECT name FROM " . $dou->table('plugin') . " WHERE unique_id = '$order[shipping_id]'");
    $order['product_amount_format'] = $dou->price_format($order['product_amount']);
    $order['shipping_fee_format'] = $dou->price_format($order['shipping_fee']);
    $order['order_amount_format'] = $dou->price_format($order['order_amount']);
    $order['email'] = $dou->get_one("SELECT email FROM " . $dou->table('user') . " WHERE user_id = '$order[user_id]'");
    $order['add_time'] = date("Y-m-d h:i:s", $order['add_time']);
    $order['status_format'] = $_LANG['order_status_' . $order['status']];
    $order['product_list'] = $dou_order->get_order_product($order['order_id']);

    // 是否显示支付按钮
    if ($dou->get_plugin($order['pay_id'])) {
        $order['if_payment'] = true;

        // 生成付款按钮
        include_once (ROOT_PATH . 'include/plugin/' . $order['pay_id'] . '/work.plugin.php');
        $plugin = new Plugin($order_sn, $order['order_amount']);
            
        // 生成支付按钮
        $order['payment'] = $plugin->work();
    }

    // 赋值给模板
    $smarty->assign('page_title', $dou->page_title('user', 'order_view'));
    $smarty->assign('ur_here', $dou->ur_here('user', 'order_view'));
    $smarty->assign('order', $order);

    $smarty->display('user.dwt');
}

/**
 * +----------------------------------------------------------
 * 订单删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'order_cancel') {
    // 验证并获取合法的ID
    $order_sn = $check->is_number($_REQUEST['order_sn']) ? $_REQUEST['order_sn'] : '';

    // 获取订单信息
    $order = $dou->fetch_array($dou->select($dou->table('order'), 'order_sn, status', "order_sn = '$order_sn' AND user_id = '$_USER[user_id]'"));

    if (!$order || $order['status'] != 0)
        exit;
    
    if ($_REQUEST['if_check']) {
        $doubox .= '<div id="douBox"><div class="boxBg"></div><div class="boxFrame">';
        $doubox .= '<h2><a href="javascript:void(0)" class="close" onclick="douRemove('."'douBox'".')">X</a>提示</h2>';
        $doubox .= '<div class="boxCon"><dt>您确定要删除该订单吗？</dt><dd>删除后，您可以在订单回收站还原该订单，也可以做永久删除。</dd><dd><a href="' . $_URL['order_cancel'] . '&order_sn=' . $order_sn . '">确定</a><a href="javascript:void(0)" onclick="douRemove('."'douBox'".')">取消</a></dd></div>';
        $doubox .= '</div></div>';
        echo $doubox;
    } else {
        // 取消订单
        $dou->query("UPDATE " . $dou->table('order') . " SET status = '-1' WHERE order_sn = '$order_sn' AND user_id = '$_USER[user_id]'");
        $dou->dou_header($_URL['order_list']);
    }
}
?>