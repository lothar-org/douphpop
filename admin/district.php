<?php
define('IN_LOTHAR', true);
define('CMOD', 'district');
require (dirname(__FILE__) . '/include/init.php');

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

// 赋值给模板
$smarty->assign('rec', $rec);
$smarty->assign('cur', CMOD);

/**
 * +----------------------------------------------------------
 * 分类列表
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    $smarty->assign('ur_here', $_LANG['district']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['district_add'],
            'href' => 'district.php?rec=add'
    ));

    // 赋值给模板
    $smarty->assign('district', $dou->get_category_nolevel('district'));

    $smarty->display('district.htm');
}

/**
 * +----------------------------------------------------------
 * 分类添加
 * +----------------------------------------------------------
 */
elseif ($rec == 'add') {
    $smarty->assign('ur_here', $_LANG['district_add']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['district'],
            'href' => 'district.php'
    ));

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('form_action', 'insert');
    $smarty->assign('district', $dou->get_category_nolevel('district'));

    $smarty->display('district.htm');
}

elseif ($rec == 'insert') {
    if (empty($_POST['cat_name']))
        $dou->dou_msg($_LANG['district_name'] . $_LANG['is_empty']);
    if (!$check->is_unique_id($_POST['unique_id']))
        $dou->dou_msg($_LANG['unique_id_wrong']);

    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    $data = array(
            'unique_id'     => $_POST['unique_id'],
            // 'parent_id'     => $_POST['parent_id'],
            'cat_name'      => $_POST['cat_name'],
            'keywords'      => $_POST['keywords'],
            'description'   => $_POST['description'],
            'sort'          => $_POST['sort'],
        );
    $dou->insert('district',$data);

    $dou->create_admin_log($_LANG['district_add'] . ': ' . $_POST['cat_name']);
    $dou->dou_msg($_LANG['district_add_succes'], 'district.php');
}

/**
 * +----------------------------------------------------------
 * 分类编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit') {
    $smarty->assign('ur_here', $_LANG['district_edit']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['district'],
            'href' => 'district.php'
    ));

    // 获取分类信息
    $cat_id = $check->is_number($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : '';
    $query = $dou->select($dou->table('district'), '*', '`cat_id`=\''. $cat_id .'\'');
    $cat_info = $dou->fetch_array($query);

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('form_action', 'update');
    $smarty->assign('district', $dou->get_category_nolevel('district', '0', '0', $cat_id));
    $smarty->assign('cat_info', $cat_info);

    $smarty->display('district.htm');
}

elseif ($rec == 'update') {
    $cat_id = $check->is_number($_POST['cat_id'])?intval($_POST['cat_id']):$dou->dou_msg('ID 非法');
    if (empty($_POST['cat_name']))
        $dou->dou_msg($_LANG['district_name'] . $_LANG['is_empty']);
    if (!$check->is_unique_id($_POST['unique_id']))
        $dou->dou_msg($_LANG['unique_id_wrong']);

    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    $data = array(
            'unique_id'     => $_POST['unique_id'],
            // 'parent_id'     => $_POST['parent_id'],
            'cat_name'      => $_POST['cat_name'],
            'keywords'      => $_POST['keywords'],
            'description'   => $_POST['description'],
            'sort'          => $_POST['sort'],
        );
    $dou->update('district',$data,'cat_id='.$cat_id);

    $dou->create_admin_log($_LANG['district_edit'] . ': ' . $_POST['cat_name']);
    $dou->dou_msg($_LANG['district_edit_succes'], 'district.php');
}

/**
 * +----------------------------------------------------------
 * 分类删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    $cat_id = $check->is_number($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : $dou->dou_msg($_LANG['illegal'], 'district.php');
    $is_parent = null;
    if ($is_parent) {
        $_LANG['district_del_is_parent'] = preg_replace('/d%/Ums', $cates['cat_name'], $_LANG['district_del_is_parent']);
        $dou->dou_msg($_LANG['district_del_is_parent'], 'district.php', '', '3');
    } else {
        if (isset($_POST['confirm']) ? $_POST['confirm'] : '') {
            $dou->create_admin_log($_LANG['district_del'] . ': ' . $cates['cat_name']);
            $dou->delete($dou->table('district'), "cat_id={$cat_id}", 'district.php');
        } else {
            $_LANG['del_check'] = preg_replace('/d%/Ums', $cates['cat_name'], $_LANG['del_check']);
            $dou->dou_msg($_LANG['del_check'], 'district.php', '', '30', "district.php?rec=del&cat_id=$cat_id");
        }
    }
}
?>