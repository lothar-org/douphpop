<?php
define('IN_LOTHAR', true);
define('CMOD', 'gallery');
require (dirname(__FILE__) . '/include/init.php');

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

// 图片上传
include_once (ROOT_PATH . 'include/upload.class.php');
$images_dir = 'images/'.CMOD.'/'; // 文件上传路径，结尾加斜杠
$img = new Upload(ROOT_PATH . $images_dir); // 实例化类文件
if (!file_exists(ROOT_PATH . $images_dir))
    mkdir(ROOT_PATH . $images_dir, 0777);

// 下一个自动生成的ID
$auto_id = $dou->get_one("SELECT auto_increment FROM information_schema.`TABLES` WHERE  TABLE_SCHEMA='" . $dbname . "' AND TABLE_NAME = '" . trim($dou->table('gallery'), '`') . '\'');

// 赋值给模板
$smarty->assign('rec', $rec);
$smarty->assign('cur', CMOD);

/**
 * +----------------------------------------------------------
 * 图片列表
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    $smarty->assign('ur_here', $_LANG['gallery']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['gallery_add'],
            'href' => 'gallery.php?rec=add'
    ));

    // 获取参数
    $cat_id = $check->is_number($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : 0;
    $keyword = isset($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';

    // 筛选条件
    $where = ' WHERE cat_id IN (' . $cat_id . $dou->dou_child_id('gallery_category', $cat_id) . ')';
    if ($keyword) {
        $where = $where . " AND title LIKE '%$keyword%'";
        $get = '&keyword=' . $keyword;
    }

    // 分页
    $page = $check->is_number($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    $page_url = 'gallery.php' . ($cat_id ? '?cat_id=' . $cat_id : '');
    $limit = $dou->pager('gallery', 15, $page, $page_url, $where, $get);

    $sql = "SELECT id, title, cat_id, image, add_time FROM " . $dou->table('gallery') . $where . " ORDER BY id DESC" . $limit;
    $query = $dou->query($sql);
    while ($row = $dou->fetch_array($query)) {
        $cat_name = $dou->get_one("SELECT cat_name FROM " . $dou->table('gallery_category') . " WHERE cat_id = '$row[cat_id]'");
        $add_time = date("Y-m-d", $row['add_time']);

        $gallery_list[] = array(
                "id" => $row['id'],
                "cat_id" => $row['cat_id'],
                "cat_name" => $cat_name,
                "title" => $row['title'],
                "image" => $row['image'],
                "add_time" => $add_time
        );
    }

    // 首页显示图片数量限制框
    for($i=1; $i<=$_CFG['home_display_gallery']; $i++) {
        $sort_bg .= "<li><em></em></li>";
    }

    // 赋值给模板
    $smarty->assign('if_sort', $_SESSION['if_sort']);
    $smarty->assign('sort', get_sort_gallery());
    $smarty->assign('sort_bg', $sort_bg);
    $smarty->assign('cat_id', $cat_id);
    $smarty->assign('keyword', $keyword);
    $smarty->assign('gallery_category', $dou->get_category_nolevel('gallery_category'));
    $smarty->assign('gallery_list', $gallery_list);

    $smarty->display('gallery.htm');
}

/**
 * +----------------------------------------------------------
 * 图片添加
 * +----------------------------------------------------------
 */
elseif ($rec == 'add') {
    $smarty->assign('ur_here', $_LANG['gallery_add']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['gallery'],
            'href' => 'gallery.php'
    ));

    // 格式化自定义参数，并存到数组$gallery，图片编辑页面中调用图片详情也是用数组$gallery，
    if ($_DEFINED['gallery']) {
        $defined = explode(',', $_DEFINED['gallery']);
        foreach ($defined as $row) {
            $defined_gallery .= $row . "：\n";
        }
        $gallery['defined'] = trim($defined_gallery);
        $gallery['defined_count'] = count(explode("\n", $gallery['defined'])) * 2;
    }

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('form_action', 'insert');
    $smarty->assign('gallery_category', $dou->get_category_nolevel('gallery_category'));
    $smarty->assign('gallery', $gallery);
    $smarty->assign('gallery_list', get_gallery_list());

    $smarty->display('gallery.htm');
}

elseif ($rec == 'insert') {
    // 验证标题
    if (empty($_POST['title'])) $dou->dou_msg($_LANG['gallery_name'] . $_LANG['is_empty']);

    // 图片上传
    if ($_FILES['image']['name'] != '')
        $image = $images_dir . $img->upload_image('image', $img->create_file_name('gallery'));

    // 数据格式化
    $add_time = time();
    $gallery = serialize($_POST['gallery']);

    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    $data = array(
            'cat_id'    => $_POST['cat_id'],
            'title'    => $_POST['title'],
            'image'    => $image,
            'gallery'    => $gallery,
            'keywords'    => $_POST['keywords'],
            'description'    => $_POST['description'],
            'add_time'    => $add_time,
        );
    $res = $dou->insert('gallery',$data);

    $dou->create_admin_log($_LANG['gallery_add'] . ': ' . $_POST['title']);
    $dou->dou_msg($_LANG['gallery_add_succes'], 'gallery.php');
}

/**
 * +----------------------------------------------------------
 * 图片编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit') {
    $smarty->assign('ur_here', $_LANG['gallery_edit']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['gallery'],
            'href' => 'gallery.php'
    ));

    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : '';

    $query = $dou->select($dou->table('gallery'), '*', '`id` = \'' . $id . '\'');
    $gallery = $dou->fetch_array($query);

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('form_action', 'update');
    $smarty->assign('gallery_category', $dou->get_category_nolevel('gallery_category'));
    $smarty->assign('gallery', $gallery);
    $smarty->assign('gallery_list', get_gallery_list($id));

    $smarty->display('gallery.htm');
}

elseif ($rec == 'update') {
    // 验证标题
    if (empty($_POST['title'])) $dou->dou_msg($_LANG['gallery_name'] . $_LANG['is_empty']);

    // 图片上传
    if ($_FILES['image']['name'] != '') {
        $image = $images_dir . $img->upload_image('image', $img->create_file_name('gallery', $_POST['id'], 'image'));
    }

    // 格式化数据
    $_POST['defined'] = str_replace("\r\n", ',', $_POST['defined']);
    $gallery = serialize($_POST['gallery']);

    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    $data = array(
            'cat_id'  => $_POST['cat_id'],
            'title'  => $_POST['title'],
            'gallery'  => $gallery,
            'keywords'  => $_POST['keywords'],
            'description'  => $_POST['description'],
        );
    if ($image)
        $data['image'] = $image;
    $res = $dou->update('article',$data,"id='$_POST[id]'");

    $dou->create_admin_log($_LANG['gallery_edit'] . ': ' . $_POST['title']);
    $dou->dou_msg($_LANG['gallery_edit_succes'], 'gallery.php');
}

/**
 * +----------------------------------------------------------
 * 首页商品筛选
 * +----------------------------------------------------------
 */
elseif ($rec == 'sort') {
    $_SESSION['if_sort'] = $_SESSION['if_sort'] ? false : true;

    // 跳转到上一页面
    $dou->dou_header($_SERVER['HTTP_REFERER']);
}

/**
 * +----------------------------------------------------------
 * 设为首页显示商品
 * +----------------------------------------------------------
 */
elseif ($rec == 'set_sort') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'gallery.php');

    $max_sort = $dou->get_one("SELECT sort FROM " . $dou->table('gallery') . " ORDER BY sort DESC");
    $new_sort = $max_sort + 1;
    $dou->query("UPDATE " . $dou->table('gallery') . " SET sort = '$new_sort' WHERE id = '$id'");

    $dou->dou_header($_SERVER['HTTP_REFERER']);
}

/**
 * +----------------------------------------------------------
 * 取消首页显示商品
 * +----------------------------------------------------------
 */
elseif ($rec == 'del_sort') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'gallery.php');

    $dou->query("UPDATE " . $dou->table('gallery') . " SET sort = '' WHERE id = '$id'");
    $dou->dou_header($_SERVER['HTTP_REFERER']);
}

/**
 * +----------------------------------------------------------
 * 图片删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'gallery.php');
    $title = $dou->get_one("SELECT title FROM " . $dou->table('gallery') . " WHERE id = '$id'");

    if (isset($_POST['confirm']) ? $_POST['confirm'] : '') {
        // 删除相应商品图片
        $image = $dou->get_one("SELECT image FROM " . $dou->table('gallery') . " WHERE id = '$id'");
        $dou->del_image($image);

        $dou->create_admin_log($_LANG['gallery_del'] . ': ' . $title);
        $dou->delete($dou->table('gallery'), "id = $id", 'gallery.php');
    } else {
        $_LANG['del_check'] = preg_replace('/d%/Ums', $title, $_LANG['del_check']);
        $dou->dou_msg($_LANG['del_check'], 'gallery.php', '', '30', "gallery.php?rec=del&id=$id");
    }
}

/**
 * +----------------------------------------------------------
 * 批量操作选择
 * +----------------------------------------------------------
 */
elseif ($rec == 'action') {
    if (is_array($_POST['checkbox'])) {
        if ($_POST['action'] == 'del_all') {
            // 批量图片删除
            $dou->del_all('gallery', $_POST['checkbox'], 'id', 'image');
        } elseif ($_POST['action'] == 'category_move') {
            // 批量移动分类
            $dou->category_move('gallery', $_POST['checkbox'], $_POST['new_cat_id']);
        } else {
            $dou->dou_msg($_LANG['select_empty']);
        }
    } else {
        $dou->dou_msg($_LANG['gallery_select_empty']);
    }
}

/**
 * +----------------------------------------------------------
 * 图册上传
 * +----------------------------------------------------------
 */
elseif ($rec == 'gallery') {
    // 生成图片文件名
    $rand = date('Ymd');
    for($i = 0; $i < 6; $i++) {
        $rand .= chr(mt_rand(97, 122));
    }

    // 验证并获取合法的ID
    $id = $check->is_number($_POST['id']) ? $_POST['id'] : '';
    $action_id = $id ? $id : $auto_id;
    $file_name = $action_id . '_' . $rand;

    $img->upload_gallery('gallery_file', $images_dir, $file_name); // 文件上传
}

/**
 * +----------------------------------------------------------
 * 图片删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'gallery_del') {
    $img_name = $_REQUEST['img_name'];
    $image = $images_dir . $img_name;

    $dou->del_image($image);
}

/**
 * +----------------------------------------------------------
 * 获取相册列表
 * +----------------------------------------------------------
 * $id 编号
 * +----------------------------------------------------------
 */
function get_gallery_list($id = '') {
    if ($id) {
        $gallery = $GLOBALS['dou']->get_one("SELECT gallery FROM " . $GLOBALS['dou']->table('gallery') . " WHERE id = '$id'");
        $images = $gallery ? unserialize($gallery) : glob(ROOT_PATH . $GLOBALS['images_dir'] . $id . '_*.*');
    } else {
        $images = glob(ROOT_PATH . $images_dir . $GLOBALS['auto_id'] . '_*.*');
    }

    foreach ((array)$images as $value) {
        $gallery_list[] = basename($value);
    }

    return $gallery_list;
}

/**
 * +----------------------------------------------------------
 * 获取首页显示图片
 * +----------------------------------------------------------
 */
function get_sort_gallery() {
    $limit = $GLOBALS['_DISPLAY']['home_gallery'] ? ' LIMIT ' . $GLOBALS['_DISPLAY']['home_gallery'] : '';
    $sql = "SELECT id, title FROM " . $GLOBALS['dou']->table('gallery') . " WHERE sort > 0 ORDER BY sort DESC" . $limit;
    $query = $GLOBALS['dou']->query($sql);
    while ($row = $GLOBALS['dou']->fetch_array($query)) {
        $sort[] = array(
                "id" => $row['id'],
                "title" => $row['title']
        );
    }

    return $sort;
}
?>