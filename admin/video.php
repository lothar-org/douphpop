<?php
/**
 * WincomtechPHP
 * --------------------------------------------------------------------------------------------------
 * 版权所有 2013-2035 XXX网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.wowlothar.cn
 * --------------------------------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在遵守授权协议前提下对程序代码进行修改和使用；不允许对程序代码以任何形式任何目的的再发布。
 * 授权协议：http://www.wowlothar.cn/license.html
 * --------------------------------------------------------------------------------------------------
 * Author: Lothar
 * Release Date: 2015-10-16
 */
define('IN_LOTHAR', true);

require (dirname(__FILE__) . '/include/init.php');

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

// 图片上传
include_once (ROOT_PATH . 'include/upload.class.php');
$images_dir = 'images/video/'; // 文件上传路径，结尾加斜杠
$img = new Upload(ROOT_PATH . $images_dir); // 实例化类文件
if (!file_exists(ROOT_PATH . $images_dir))
    mkdir(ROOT_PATH . $images_dir, 0777);

// 赋值给模板
$smarty->assign('rec', $rec);
$smarty->assign('cur', 'video');

/**
 * +----------------------------------------------------------
 * 视频列表
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    $smarty->assign('ur_here', $_LANG['video']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['video_add'],
            'href' => 'video.php?rec=add' 
    ));
    
    // 获取参数
    $cat_id = $check->is_number($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : 0;
    $keyword = isset($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
    
    // 筛选条件
    $where = ' WHERE cat_id IN (' . $cat_id . $dou->dou_child_id('video_category', $cat_id) . ')';
    if ($keyword) {
        $where = $where . " AND title LIKE '%$keyword%'";
        $get = '&keyword=' . $keyword;
    }
    
    // 分页
    $page = $check->is_number($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    $page_url = 'video.php' . ($cat_id ? '?cat_id=' . $cat_id : '');
    $limit = $dou->pager('video', 15, $page, $page_url, $where, $get);
    
    $sql = "SELECT id, title, cat_id, image, add_time FROM " . $dou->table('video') . $where . " ORDER BY id DESC" . $limit;
    $query = $dou->query($sql);
    while ($row = $dou->fetch_array($query)) {
        $cat_name = $dou->get_one("SELECT cat_name FROM " . $dou->table('video_category') . " WHERE cat_id = '$row[cat_id]'");
        $add_time = date("Y-m-d", $row['add_time']);
        
        $video_list[] = array(
                "id" => $row['id'],
                "cat_id" => $row['cat_id'],
                "cat_name" => $cat_name,
                "title" => $row['title'],
                "image" => $row['image'],
                "add_time" => $add_time 
        );
    }
    
    // 首页显示视频数量限制框
    for($i=1; $i<=$_CFG['home_display_video']; $i++) {
        $sort_bg .= "<li><em></em></li>";
    }
    
    // 赋值给模板
    $smarty->assign('if_sort', $_SESSION['if_sort']);
    $smarty->assign('sort', get_sort_video());
    $smarty->assign('sort_bg', $sort_bg);
    $smarty->assign('cat_id', $cat_id);
    $smarty->assign('keyword', $keyword);
    $smarty->assign('video_category', $dou->get_category_nolevel('video_category'));
    $smarty->assign('video_list', $video_list);
    
    $smarty->display('video.htm');
} 

/**
 * +----------------------------------------------------------
 * 视频添加
 * +----------------------------------------------------------
 */
elseif ($rec == 'add') {
    $smarty->assign('ur_here', $_LANG['video_add']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['video'],
            'href' => 'video.php' 
    ));
    
    // 格式化自定义参数，并存到数组$video，视频编辑页面中调用视频详情也是用数组$video，
    if ($_DEFINED['video']) {
        $defined = explode(',', $_DEFINED['video']);
        foreach ($defined as $row) {
            $defined_video .= $row . "：\n";
        }
        $video['defined'] = trim($defined_video);
        $video['defined_count'] = count(explode("\n", $video['defined'])) * 2;
    }
    
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());
    
    // 赋值给模板
    $smarty->assign('form_action', 'insert');
    $smarty->assign('video_category', $dou->get_category_nolevel('video_category'));
    $smarty->assign('video', $video);
    
    $smarty->display('video.htm');
} 

elseif ($rec == 'insert') {
    // 验证标题
    if (empty($_POST['title'])) $dou->dou_msg($_LANG['video_title'] . $_LANG['is_empty']);
    
    // 图片上传
    if ($_FILES['image']['name'] != '')
        $image = $images_dir . $img->upload_image('image', $img->create_file_name('video'));
    
    // 数据格式化
    $add_time = time();
    $_POST['defined'] = str_replace("\r\n", ',', $_POST['defined']);
        
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    $data = array(
            'cat_id'        => $_POST['cat_id'],
            'title'         => $_POST['title'],
            'defined'       => $_POST['defined'],
            'file'          => $_POST['file'],
            'image'         => $image,
            'content'       => $_POST['content'],
            'keywords'      => $_POST['keywords'],
            'description'   => $_POST['description'],
            'add_time'      => $add_time,
        );
    $dou->insert('video',$data);
    
    $dou->create_admin_log($_LANG['video_add'] . ': ' . $_POST['title']);
    $dou->dou_msg($_LANG['video_add_succes'], 'video.php');
} 

/**
 * +----------------------------------------------------------
 * 视频编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit') {
    $smarty->assign('ur_here', $_LANG['video_edit']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['video'],
            'href' => 'video.php' 
    ));
    
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : '';
    
    $query = $dou->select($dou->table('video'), '*', '`id` = \'' . $id . '\'');
    $video = $dou->fetch_array($query);
    
    // 格式化自定义参数
    if ($_DEFINED['video'] || $video['defined']) {
        $defined = explode(',', $_DEFINED['video']);
        foreach ($defined as $row) {
            $defined_video .= $row . "：\n";
        }
        // 如果视频中已经写入自定义参数则调用已有的
        $video['defined'] = $video['defined'] ? str_replace(",", "\n", $video['defined']) : trim($defined_video);
        $video['defined_count'] = count(explode("\n", $video['defined'])) * 2;
    }

    // 格式化文件名
    $basename = basename($video['file']);
    $video['filename'] = substr($basename, 0, strrpos($basename, '.'));
    
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());
    
    // 赋值给模板
    $smarty->assign('form_action', 'update');
    $smarty->assign('video_category', $dou->get_category_nolevel('video_category'));
    $smarty->assign('video', $video);
    
    $smarty->display('video.htm');
} 

elseif ($rec == 'update') {
    // 验证标题
    if (empty($_POST['title'])) $dou->dou_msg($_LANG['video_title'] . $_LANG['is_empty']);
        
    // 图片上传
    if ($_FILES['image']['name'] != '') {
        $image = $images_dir . $img->upload_image('image', $img->create_file_name('video', $_POST['id'], 'image'));
    }
    
    // 格式化自定义参数
    $_POST['defined'] = str_replace("\r\n", ',', $_POST['defined']);
    
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    $data = array(
            'cat_id'  => $_POST['cat_id'],
            'title'  => $_POST['title'],
            'defined'  => $_POST['defined'],
            'file'  => $_POST['file'],
            'content'  => $_POST['content'],
            'keywords'  => $_POST['keywords'],
            'description'  => $_POST['description'],
        );
    $dou->update('video',$data,'id='.$_POST['id']);
    if ($image) {
        $data['image'] = $image;
    }
    
    $dou->create_admin_log($_LANG['video_edit'] . ': ' . $_POST['title']);
    $dou->dou_msg($_LANG['video_edit_succes'], 'video.php');
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
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'video.php');
    
    $max_sort = $dou->get_one("SELECT sort FROM " . $dou->table('video') . " ORDER BY sort DESC");
    $new_sort = $max_sort + 1;
    $dou->query("UPDATE " . $dou->table('video') . " SET sort = '$new_sort' WHERE id = '$id'");
    
    $dou->dou_header($_SERVER['HTTP_REFERER']);
} 

/**
 * +----------------------------------------------------------
 * 取消首页显示商品
 * +----------------------------------------------------------
 */
elseif ($rec == 'del_sort') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'video.php');
    
    $dou->query("UPDATE " . $dou->table('video') . " SET sort = '' WHERE id = '$id'");
    $dou->dou_header($_SERVER['HTTP_REFERER']);
} 

/**
 * +----------------------------------------------------------
 * 视频删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'video.php');
    $title = $dou->get_one("SELECT title FROM " . $dou->table('video') . " WHERE id = '$id'");
    
    if (isset($_POST['confirm']) ? $_POST['confirm'] : '') {
        // 删除相应商品图片
        $image = $dou->get_one("SELECT image FROM " . $dou->table('video') . " WHERE id = '$id'");
        $dou->del_image($image);
        
        $dou->create_admin_log($_LANG['video_del'] . ': ' . $title);
        $dou->delete($dou->table('video'), "id = $id", 'video.php');
    } else {
        $_LANG['del_check'] = preg_replace('/d%/Ums', $title, $_LANG['del_check']);
        $dou->dou_msg($_LANG['del_check'], 'video.php', '', '30', "video.php?rec=del&id=$id");
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
            // 批量视频删除
            $dou->del_all('video', $_POST['checkbox'], 'id', 'image');
        } elseif ($_POST['action'] == 'category_move') {
            // 批量移动分类
            $dou->category_move('video', $_POST['checkbox'], $_POST['new_cat_id']);
        } else {
            $dou->dou_msg($_LANG['select_empty']);
        }
    } else {
        $dou->dou_msg($_LANG['video_select_empty']);
    }
}

/**
 * +----------------------------------------------------------
 * 获取首页显示视频
 * +----------------------------------------------------------
 */
function get_sort_video() {
    $limit = $GLOBALS['_DISPLAY']['home_video'] ? ' LIMIT ' . $GLOBALS['_DISPLAY']['home_video'] : '';
    $sql = "SELECT id, title FROM " . $GLOBALS['dou']->table('video') . " WHERE sort > 0 ORDER BY sort DESC" . $limit;
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