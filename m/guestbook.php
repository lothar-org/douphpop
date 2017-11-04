<?php

define('IN_LOTHAR', true);

$sub = 'insert|del';
$subbox = array(
        "module" => 'guestbook',
        "sub" => $sub
);

require (dirname(__FILE__) . '/include/init.php');

// 开启SESSION
if (empty(session_id())) session_start();

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

/**
 * +----------------------------------------------------------
 * 留言板
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    // SQL查询条件
    $where = " WHERE if_show = '1'";

    // 获取分页信息
    $page = $check->is_number($_REQUEST['page']) ? trim($_REQUEST['page']) : 1;
    $limit = $dou->pager('guestbook', 10, $page, $dou->rewrite_url('guestbook'), $where);
    
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->set_token('mobile_guestbook'));
    
    $sql = "SELECT * FROM " . $GLOBALS['dou']->table('guestbook') . $where . " ORDER BY id DESC" . $limit;
    while ($row = $GLOBALS['dou']->fetch_array($query)) {
        $add_time = date("Y-m-d", $row['add_time']);
        
        // 获取管理员回复
        $reply = "SELECT content, add_time FROM " . $dou->table('guestbook') . " WHERE reply_id = '$row[id]'";
        $reply = $dou->fetch_array($dou->query($reply));
        $reply_time = date("Y-m-d", $reply['add_time']);
        
        $guestbook[] = array(
                "id" => $row['id'],
                "title" => $row['title'],
                "name" => $row['name'],
                "content" => $row['content'],
                "add_time" => $add_time,
                "reply" => $reply['content'],
                "reply_time" => $reply_time 
        );
    }
    
    // 初始化回复方式
    $contact_type = array('email', 'tel', 'qq');
    foreach ($contact_type as $value) {
        $selected = ($value == $post['contact_type']) ? ' selected="selected"' : '';
        $option .= "<option value=" . $value . $selected . ">" . $_LANG['guestbook_' . $value] . "</option>";
    }
    
    // 赋值给模板-meta和title信息
    $smarty->assign('page_title', $dou->page_title('guestbook'));
    $smarty->assign('keywords', $_CFG['mobile_keywords']);
    $smarty->assign('description', $_CFG['mobile_description']);
    
    // 赋值给模板-导航栏
    $smarty->assign('nav_list', $dou->get_nav_mobile('mobile', 0, 'guestbook', 0));
    
    // 赋值给模板-数据
    $smarty->assign('rec', $rec);
    $smarty->assign('insert_url', $_URL['insert']);
    $smarty->assign('option', $option);
    $smarty->assign('guestbook', $guestbook);
    $smarty->assign('head', $dou->head($_LANG['guestbook']));
    
    $smarty->display('guestbook.dwt');
}

/**
 * +----------------------------------------------------------
 * 留言添加
 * +----------------------------------------------------------
 */
if ($rec == 'insert') {
    $ip = $dou->get_ip();
    $add_time = time();
    $captcha = $check->is_captcha($_POST['captcha']) ? strtoupper($_POST['captcha']) : '';
        
    // 如果限制必须输入中文则修改错误提示
    $include_chinese = $_CFG['guestbook_check_chinese'] ? $_LANG['guestbook_include_chinese'] : '';
    
    // 验证主题
    if ($check->is_illegal_char($_POST['title'])) {
        $wrong['title'] = $_LANG['guestbook_title'] . $_LANG['illegal_char'];
    } elseif (!check_guestbook($_POST['title'], 200)) {
        $wrong['title'] = preg_replace('/d%/Ums', $include_chinese, $_LANG['guestbook_title_wrong']);
    }
    
    // 验证联系人
    if ($check->is_illegal_char($_POST['name'])) {
        $wrong['name'] = $_LANG['guestbook_name'] . $_LANG['illegal_char'];
    } elseif (!check_guestbook($_POST['name'], 200)) {
        $wrong['name'] = preg_replace('/d%/Ums', $include_chinese, $_LANG['guestbook_name_wrong']);
    }
    
    // 验证回复方式
    if (empty($_POST['contact_type'])) {
        $wrong['contact'] = $_LANG['guestbook_contact_type_empty'];
    } elseif ($_POST['contact_type'] == 'email') {
        if (!$check->is_email($_POST['contact']))
            $wrong['contact'] = $_LANG['guestbook_email_wrong'];
    } elseif ($_POST['contact_type'] == 'tel') {
        if (!$check->is_telephone($_POST['contact']))
            $wrong['contact'] = $_LANG['guestbook_tel_wrong'];
    } elseif ($_POST['contact_type'] == 'qq') {
        if (!$check->is_qq($_POST['contact']))
            $wrong['contact'] = $_LANG['guestbook_qq_wrong'];
    }
    
    // 验证留言内容
    if ($check->is_illegal_char($_POST['content'])) {
        $wrong['content'] = $_LANG['guestbook_content'] . $_LANG['illegal_char'];
    } elseif (!check_guestbook($_POST['content'], 300)) {
        $wrong['content'] = preg_replace('/d%/Ums', $include_chinese, $_LANG['guestbook_content_wrong']);
    }
    
    // 判断验证码
    if ($_CFG['captcha'] && md5($captcha . DOU_SHELL) != $_SESSION['captcha'])
        $wrong['captcha'] = $_LANG['captcha_wrong'];
    
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
    
    // 检查IP是否频繁留言
    if (is_water($ip))
        $dou->dou_msg($_LANG['guestbook_is_water'], $_URL['guestbook']);
    
    if ($wrong) {
        foreach ($wrong as $key => $value) {
            $wrong_format .= $wrong[$key] . '<br>';
        }
        $dou->dou_msg($wrong_format, $_URL['guestbook']);
    }
    
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token'], 'mobile_guestbook');

    // 安全处理用户输入信息
    $_POST = $firewall->dou_foreground($_POST);
    
    $sql = "INSERT INTO " . $dou->table('guestbook') . " (id, title, name, contact_type, contact, content, ip, add_time)" . " VALUES (NULL, '$_POST[title]', '$_POST[name]', '$_POST[contact_type]', '$_POST[contact]', '$_POST[content]', '$ip', '$add_time')";
    $dou->query($sql);
    
    $dou->dou_msg($_LANG['guestbook_insert_success'], $_URL['guestbook']);
}

/**
 * +----------------------------------------------------------
 * 防灌水
 * +----------------------------------------------------------
 */
function is_water($ip) {
    $unread_messages = $GLOBALS['dou']->get_one("SELECT COUNT(*) FROM " . $GLOBALS['dou']->table('guestbook') .
             " WHERE ip = '$ip' AND if_read = 0 AND reply_id = 0");
    
    // 如果管理员未回复的留言数量大于3
    if ($unread_messages >= '3')
        return true;
}

/**
 * +----------------------------------------------------------
 * 检查是否包含中文字符且长度符合要求
 * +----------------------------------------------------------
 */
function check_guestbook($value, $length) {
    $check_chinese = $GLOBALS['_CFG']['guestbook_check_chinese'] ? $GLOBALS['check']->if_include_chinese($value) : true;
    
    if ($check_chinese && $GLOBALS['check']->ch_length($value, $length)) {
        return true;
    }
}
?>