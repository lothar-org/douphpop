<?php
define('IN_LOTHAR', true);
define('CMOD', 'page');
require (dirname(__FILE__) . '/include/init.php');

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

// 图片上传
include_once (ROOT_PATH . 'include/upload.class.php');
$images_dir = 'images/'.CMOD.'/'; // 文件上传路径，结尾加斜杠
$thumb_dir = ''; // 缩略图路径（相对于$images_dir） 结尾加斜杠，留空则跟$images_dir相同
$img = new Upload(ROOT_PATH . $images_dir, $thumb_dir); // 实例化类文件
if (!file_exists(ROOT_PATH . $images_dir)) {
    mkdir(ROOT_PATH . $images_dir, 0777);
}

$smarty->assign('rec', $rec);
$smarty->assign('cur', CMOD);

/**
 * +----------------------------------------------------------
 * 单页面列表
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    $smarty->assign('ur_here', $_LANG['page_list']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['page_add'],
            'href' => 'page.php?rec=add'
    ));

    // 赋值给模板
    $smarty->assign('page_list', $dou->get_page_nolevel());

    $smarty->display('page.htm');
}

/**
 * +----------------------------------------------------------
 * 单页面添加
 * +----------------------------------------------------------
 */
elseif ($rec == 'add') {
    $smarty->assign('ur_here', $_LANG['page_add']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['page_list'],
            'href' => 'page.php'
    ));

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('form_action', 'insert');
    $smarty->assign('page_list', $dou->get_page_nolevel());

    $smarty->display('page.htm');
}

elseif ($rec == 'insert') {
    if (empty($_POST['page_name']))
        $dou->dou_msg($_LANG['page_name'] . $_LANG['is_empty']);

    if (!$check->is_unique_id($_POST['unique_id']))
        $dou->dou_msg($_LANG['unique_id_wrong']);

    if ($dou->value_exist('page', 'unique_id', $_POST['unique_id']))
        $dou->dou_msg($_LANG['unique_id_existed']);

    // 图片上传
    if ($_FILES['image']['name'] != '') {
        $image_name = $img->upload_image('image', $img->create_file_name('page'));
        $image = $images_dir . $image_name;
        $img->make_thumb($image_name, $_CFG['thumb_width'], $_CFG['thumb_height']);
    }

    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    $data = array(
            'unique_id'  => $_POST['unique_id'],
            'parent_id'  => $_POST['parent_id'],
            'page_name'  => $_POST['page_name'],
            'content'  => $_POST['content'],
            'keywords'  => $_POST['keywords'],
            'description'  => $_POST['description'],
            'image'  => $image,
            'template'  => $_POST['template'],
        );
    $res = $dou->insert('page',$data);
    if ($res) {
        $dou->create_admin_log($_LANG['page_add'] . ': ' . $_POST['page_name']);
        $dou->dou_msg($_LANG['page_add_succes'], 'page.php');
    } else {
        $dou->dou_msg('添加失败！');
    }
}

/**
 * +----------------------------------------------------------
 * 单页面编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit') {
    $smarty->assign('ur_here', $_LANG['page_edit']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['page_list'],
            'href' => 'page.php'
    ));

    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : '';

    $query = $dou->select($dou->table('page'), '*', '`id` = \'' . $id . '\'');
    $page = $dou->fetch_array($query);

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('form_action', 'update');
    $smarty->assign('page_list', $dou->get_page_nolevel(0, 0, $id));
    $smarty->assign('page', $page);

    $smarty->display('page.htm');
}

elseif ($rec == 'update') {
    // 验证并获取合法的ID
    if ($check->is_number($_POST['id'])){
        $id = intval($_POST['id']);
    } else {
        $dou->dou_msg('ID 非法');
    }

    if (empty($_POST['page_name']))
        $dou->dou_msg($_LANG['page_name'] . $_LANG['is_empty']);

    if (!$check->is_unique_id($_POST['unique_id']))
        $dou->dou_msg($_LANG['unique_id_wrong']);

    if ($dou->value_exist('page', 'unique_id', $_POST['unique_id'], "AND id != {$id}"))
        $dou->dou_msg($_LANG['unique_id_existed']);

    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);
    // 图片上传
    if ($_FILES['image']['name'] != '') {
        $image_name = $img->upload_image('image', $img->create_file_name('page', $id, 'image'));
        $image = $images_dir . $image_name;
        $data = array('image'=>$image);
        $img->make_thumb($image_name, $_CFG['thumb_width'], $_CFG['thumb_height']);
        // 因为这里文件名始终相同，会直接覆盖源文件，所以不可以做额外删除
        // $old_pic = $dou->get_one("SELECT image from ".$dou->table('page')." where id={$id} ");
        // if ($old_pic) { $dou->del_image($old_pic); }
    }
    $data = array(
            'unique_id'  => $_POST['unique_id'],
            'parent_id'  => $_POST['parent_id'],
            'page_name'  => $_POST['page_name'],
            'content'  => $_POST['content'],
            'keywords'  => $_POST['keywords'],
            'description'  => $_POST['description'],
            'image'  => $image,
            'template'  => $_POST['template'],
        );
    $res = $dou->update('page',$data,'id='.$id);
    if ($res) {
        $dou->create_admin_log($_LANG['page_edit'] . ': ' . $_POST['page_name']);
        $dou->dou_msg($_LANG['page_edit_succes'], 'page.php', '', '3');
    } else {
        $dou->dou_msg('数据无变化 或 修改失败！');
    }
}

/**
 * +----------------------------------------------------------
 * 单页面删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'page.php');

    $page_name = $dou->get_one("SELECT page_name FROM " . $dou->table('page') . " WHERE id = '$id'");
    $is_parent = $dou->get_one("SELECT id FROM " . $dou->table('page') . " WHERE parent_id = '$id'");

    if ($id == '1') {
        $dou->dou_msg($_LANG['page_del_wrong'], 'page.php', '', '3');
    } elseif ($is_parent) {
        $_LANG['page_del_is_parent'] = preg_replace('/d%/Ums', $page_name, $_LANG['page_del_is_parent']);
        $dou->dou_msg($_LANG['page_del_is_parent'], 'page.php', '', '3');
    } else {
        if (isset($_POST['confirm']) ? $_POST['confirm'] : '') {
            $dou->create_admin_log($_LANG['page_del'] . ': ' . $page_name);
            $dou->delete($dou->table('page'), "id = $id", 'page.php');
        } else {
            $_LANG['del_check'] = preg_replace('/d%/Ums', $page_name, $_LANG['del_check']);
            $dou->dou_msg($_LANG['del_check'], 'page.php', '', '30', "page.php?rec=del&id=$id");
        }
    }
}
?>