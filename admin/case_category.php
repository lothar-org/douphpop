<?php
define('IN_LOTHAR', true);
define('CMOD', 'case_category');
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
    $smarty->assign('ur_here', $_LANG['case_category']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['case_category_add'],
            'href' => 'case_category.php?rec=add'
    ));

    // 赋值给模板
    $smarty->assign('case_category', $dou->get_category_nolevel('case_category'));

    $smarty->display('case_category.htm');
}

/**
 * +----------------------------------------------------------
 * 分类添加
 * +----------------------------------------------------------
 */
elseif ($rec == 'add') {
    $smarty->assign('ur_here', $_LANG['case_category_add']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['case_category'],
            'href' => 'case_category.php'
    ));

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('form_action', 'insert');
    $smarty->assign('case_category', $dou->get_category_nolevel('case_category'));

    $smarty->display('case_category.htm');
}

elseif ($rec == 'insert') {
    if (empty($_POST['cat_name']))
        $dou->dou_msg($_LANG['case_category_name'] . $_LANG['is_empty']);
    if (!$check->is_unique_id($_POST['unique_id']))
        $dou->dou_msg($_LANG['unique_id_wrong']);

    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    $data = array(
            'unique_id' => $_POST['unique_id'],
            'parent_id' => $_POST['parent_id'],
            'cat_name' => $_POST['cat_name'],
            'keywords' => $_POST['keywords'],
            'description' => $_POST['description'],
            'sort' => $_POST['sort'],
        );
    $res = $dou->insert('case_category',$data);

    $dou->create_admin_log($_LANG['case_category_add'] . ': ' . $_POST['cat_name']);
    $dou->dou_msg($_LANG['case_category_add_succes'], 'case_category.php');
}

/**
 * +----------------------------------------------------------
 * 分类编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit') {
    $smarty->assign('ur_here', $_LANG['case_category_edit']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['case_category'],
            'href' => 'case_category.php'
    ));

    // 获取分类信息
    $cat_id = $check->is_number($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : '';
    $query = $dou->select($dou->table('case_category'), '*', '`cat_id` = \'' . $cat_id . '\'');
    $cat_info = $dou->fetch_assoc($query);

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('form_action', 'update');
    $smarty->assign('case_category', $dou->get_category_nolevel('case_category', '0', '0', $cat_id));
    $smarty->assign('cat_info', $cat_info);

    $smarty->display('case_category.htm');
}

elseif ($rec == 'update') {
    if (empty($_POST['cat_name']))
        $dou->dou_msg($_LANG['case_category_name'] . $_LANG['is_empty']);
    if (!$check->is_unique_id($_POST['unique_id']))
        $dou->dou_msg($_LANG['unique_id_wrong']);

    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    $data = array(
            'unique_id' => $_POST['unique_id'],
            'parent_id' => $_POST['parent_id'],
            'cat_name' => $_POST['cat_name'],
            'keywords' => $_POST['keywords'],
            'description' => $_POST['description'],
            'sort' => $_POST['sort'],
        );
    $res = $dou->update('case_category',$data,"cat_id = '$_POST[cat_id]'");

    $dou->create_admin_log($_LANG['case_category_edit'] . ': ' . $_POST['cat_name']);
    $dou->dou_msg($_LANG['case_category_edit_succes'], 'case_category.php');
}

/**
 * +----------------------------------------------------------
 * 分类删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    // 查三次改成一次性查出
    $cat_id = $check->is_number($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : $dou->dou_msg($_LANG['illegal'], 'article_category.php');
    $cates = $dou->fetchRow(sprintf("SELECT b.id,a.cat_name,(SELECT cat_id FROM %s WHERE parent_id=%d) as cat_id FROM %s a JOIN %s b ON a.cat_id=b.cat_id WHERE a.cat_id=%d;",$dou->table('article_category'),$cat_id,$dou->table('article_category'),$dou->table('article'),$cat_id));
    $is_parent = $cates['id'] . $cates['cat_id'];

    if ($is_parent) {
        $_LANG['case_category_del_is_parent'] = preg_replace('/d%/Ums', $cates['cat_name'], $_LANG['case_category_del_is_parent']);
        $dou->dou_msg($_LANG['case_category_del_is_parent'], 'case_category.php', '', '3');
    } else {
        if (isset($_POST['confirm']) ? $_POST['confirm'] : '') {
            $dou->create_admin_log($_LANG['case_category_del'] . ': ' . $cates['cat_name']);
            $dou->delete($dou->table('case_category'), "cat_id = $cat_id", 'case_category.php');
        } else {
            $_LANG['del_check'] = preg_replace('/d%/Ums', $cates['cat_name'], $_LANG['del_check']);
            $dou->dou_msg($_LANG['del_check'], 'case_category.php', '', '30', "case_category.php?rec=del&cat_id=$cat_id");
        }
    }
}
?>