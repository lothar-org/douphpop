<?php
define('IN_LOTHAR', true);
define('CMOD', 'link');
require (dirname(__FILE__) . '/include/init.php');

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

// 赋值给模板
$smarty->assign('rec', $rec);
$smarty->assign('cur', CMOD);
$smarty->assign('ur_here', $_LANG['link']);
$smarty->assign('link_list', get_link_list());

/**
 * +----------------------------------------------------------
 * 友情链接列表
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());
    
    $smarty->display('link.htm');
} 

/**
 * +----------------------------------------------------------
 * 友情链接添加处理
 * +----------------------------------------------------------
 */
elseif ($rec == 'insert') {
    if (empty($_POST['link_name']))
        $dou->dou_msg($_LANG['link_name'] . $_LANG['is_empty']);
    
    // 格式化数据
    $link_url = $dou->auto_http(trim($_POST['link_url']));
        
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);
    
    $sql = "INSERT INTO " . $dou->table('link') . " (id, link_name, link_url, sort)" . " VALUES (NULL, '$_POST[link_name]', '$link_url', '$_POST[sort]')";
    $dou->query($sql);
    $dou->create_admin_log($_LANG['link_add'] . ': ' . $_POST['link_name']);
    $dou->dou_msg($_LANG['link_add_succes'], 'link.php');
} 

/**
 * +----------------------------------------------------------
 * 友情链接编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : '';
    
    $query = $dou->select($dou->table('link'), '*', '`id` = \'' . $id . '\'');
    $link = $dou->fetch_array($query);
    
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());
    
    // 赋值给模板
    $smarty->assign('id', $id);
    $smarty->assign('link', $link);
    
    $smarty->display('link.htm');
} 

elseif ($rec == 'update') {
    if (empty($_POST['link_name']))
        $dou->dou_msg($_LANG['link_name'] . $_LANG['is_empty']);
    
    // 格式化数据    
    $link_url = $dou->auto_http(trim($_POST['link_url']));
    
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);
    
    $sql = "update " . $dou->table('link') . " SET link_name='$_POST[link_name]', link_url='$link_url', sort='$_POST[sort]' WHERE id='$_POST[id]'";
    $dou->query($sql);
    $dou->create_admin_log($_LANG['link_edit'] . ': ' . $_POST['link_name']);
    $dou->dou_msg($_LANG['link_edit_succes'], 'link.php');
} 

/**
 * +----------------------------------------------------------
 * 友情链接删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'link.php');
    
    $link_name = $dou->get_one("SELECT link_name FROM " . $dou->table('link') . " WHERE id = '$id'");
    
    if ($_POST['confirm']) {
        $dou->create_admin_log($_LANG['link_del'] . ': ' . $link_name);
        $dou->delete($dou->table('link'), "id = $id", "link.php");
    } else {
        $_LANG['del_check'] = preg_replace('/d%/Ums', $link_name, $_LANG['del_check']);
        $dou->dou_msg($_LANG['del_check'], 'link.php', '', '30', "link.php?rec=del&id=$id");
    }
}

/**
 * +----------------------------------------------------------
 * 获取下级友情链接列表
 * +----------------------------------------------------------
 */
function get_link_list() {
    $sql = "SELECT * FROM " . $GLOBALS['dou']->table('link') . " ORDER BY sort ASC, id ASC";
    $query = $GLOBALS['dou']->query($sql);
    while ($row = $GLOBALS['dou']->fetch_array($query)) {
        $link_list[] = array(
                "id" => $row['id'],
                "link_name" => $row['link_name'],
                "link_url" => $row['link_url'],
                "sort" => $row['sort'] 
        );
    }
    
    return $link_list;
}
?>