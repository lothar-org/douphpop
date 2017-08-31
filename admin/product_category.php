<?php
define('IN_LOTHAR', true);
require (dirname(__FILE__) . '/include/init.php');

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

// 赋值给模板
$smarty->assign('rec', $rec);
$smarty->assign('cur', 'product_category');

/**
 * +----------------------------------------------------------
 * 分类列表
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    $smarty->assign('ur_here', $_LANG['product_category']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['product_category_add'],
            'href' => 'product_category.php?rec=add' 
    ));
    
    // 赋值给模板
    $smarty->assign('product_category', $dou->get_category_nolevel('product_category'));
    $smarty->display('product_category.htm');
} 

/**
 * +----------------------------------------------------------
 * 分类添加
 * +----------------------------------------------------------
 */
elseif ($rec == 'add') {
    $smarty->assign('ur_here', $_LANG['product_category_add']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['product_category'],
            'href' => 'product_category.php' 
    ));
    
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());
    
    // 赋值给模板
    $smarty->assign('form_action', 'insert');
    $smarty->assign('product_category', $dou->get_category_nolevel('product_category'));
    
    $smarty->display('product_category.htm');
} 

elseif ($rec == 'insert') {
    if (empty($_POST['cat_name']))
        $dou->dou_msg($_LANG['product_category_name'] . $_LANG['is_empty']);
    
    if (!$check->is_unique_id($_POST['unique_id']))
        $dou->dou_msg($_LANG['unique_id_wrong']);
        
    if ($dou->value_exist('product_category', 'unique_id', $_POST['unique_id']))
        $dou->dou_msg($_LANG['unique_id_existed']);
        
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);
    
    $data = array(
            'unique_id'  => $_POST['unique_id'],
            'parent_id'  => $_POST['parent_id'],
            'cat_name'  => $_POST['cat_name'],
            'keywords'  => $_POST['keywords'],
            'description'  => $_POST['description'],
            'sort'  => $_POST['sort']
        );
    $dou->insert('product_category',$data);
    
    $dou->create_admin_log($_LANG['product_category_add'] . ': ' . $_POST['cat_name']);
    $dou->dou_msg($_LANG['product_category_add_succes'], 'product_category.php');
} 

/**
 * +----------------------------------------------------------
 * 分类编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit') {
    $smarty->assign('ur_here', $_LANG['product_category_edit']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['product_category'],
            'href' => 'product_category.php' 
    ));
    
    // 获取分类信息
    $cat_id = $check->is_number($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : '';
    $query = $dou->select($dou->table('product_category'), '*', '`cat_id` = \'' . $cat_id . '\'');
    $cat_info = $dou->fetch_array($query);
    
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());
    
    // 赋值给模板
    $smarty->assign('form_action', 'update');
    $smarty->assign('product_category', $dou->get_category_nolevel('product_category', '0', '0', $cat_id));
    $smarty->assign('cat_info', $cat_info);
    
    $smarty->display('product_category.htm');
} 

elseif ($rec == 'update') {
    if (empty($_POST['cat_name']))
        $dou->dou_msg($_LANG['product_category_name'] . $_LANG['is_empty']);

    if (!$check->is_unique_id($_POST['unique_id']))
        $dou->dou_msg($_LANG['unique_id_wrong']);

    if ($dou->value_exist('product_category', 'unique_id', $_POST['unique_id'], "AND cat_id != '$_POST[cat_id]'"))
        $dou->dou_msg($_LANG['unique_id_existed']);
        
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);
    
    $data = array(
            'cat_name'  => $_POST['cat_name'],
            'unique_id'  => $_POST['unique_id'],
            'parent_id'  => $_POST['parent_id'],
            'keywords'  => $_POST['keywords'],
            'description'  => $_POST['description'],
            'sort'  => $_POST['sort']
        );
    $dou->update('product_category',$data,'cat_id='.$_POST['cat_id']);
    
    $dou->create_admin_log($_LANG['product_category_edit'] . ': ' . $_POST['cat_name']);
    $dou->dou_msg($_LANG['product_category_edit_succes'], 'product_category.php');
} 

/**
 * +----------------------------------------------------------
 * 分类删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    // $cat_id = $check->is_number($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : $dou->dou_msg($_LANG['illegal'], 'product_category.php');
    // $cat_name = $dou->get_one("SELECT cat_name FROM " . $dou->table('product_category') . " WHERE cat_id = '$cat_id'");
    // $is_parent = $dou->get_one("SELECT id FROM " . $dou->table('product') . " WHERE cat_id = '$cat_id'") .
    //          $dou->get_one("SELECT cat_id FROM " . $dou->table('product_category') . " WHERE parent_id = '$cat_id'");
    // 查三次改成一次性查出
    $cat_id = $check->is_number($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : $dou->dou_msg($_LANG['illegal'], 'product_category.php');
    $cates = $dou->fetchRow(sprintf("SELECT b.id,a.cat_name,(SELECT cat_id FROM %s WHERE parent_id=%d) as cat_id FROM %s a JOIN %s b ON a.cat_id=b.cat_id WHERE a.cat_id=%d;",$dou->table('product_category'),$cat_id,$dou->table('product_category'),$dou->table('product'),$cat_id));
    $is_parent = $cates['id'] .$cates['cat_id'];
    // $cat_name = $cates['cat_name'];
    
    if ($is_parent) {
        $_LANG['product_category_del_is_parent'] = preg_replace('/d%/Ums', $cates['cat_name'], $_LANG['product_category_del_is_parent']);
        $dou->dou_msg($_LANG['product_category_del_is_parent'], 'product_category.php', '', '3');
    } else {
        if (isset($_POST['confirm']) ? $_POST['confirm'] : '') {
            $dou->create_admin_log($_LANG['product_category_del'] . ': ' . $cates['cat_name']);
            $dou->delete($dou->table('product_category'), "cat_id = $cat_id", 'product_category.php');
        } else {
            $_LANG['del_check'] = preg_replace('/d%/Ums', $cates['cat_name'], $_LANG['del_check']);
            $dou->dou_msg($_LANG['del_check'], 'product_category.php', '', '30', "product_category.php?rec=del&cat_id=$cat_id");
        }
    }
}
?>