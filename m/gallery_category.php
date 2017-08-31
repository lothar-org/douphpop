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

// 验证并获取合法的ID，如果不合法将其设定为-1
$cat_id = $firewall->get_legal_id('gallery_category', $_REQUEST['id'], $_REQUEST['unique_id']);
if ($cat_id == -1) {
    $dou->dou_msg($GLOBALS['_LANG']['page_wrong'], M_URL);
} else {
    $where = ' WHERE cat_id IN (' . $cat_id . $dou->dou_child_id('gallery_category', $cat_id) . ')';
}
    
// 获取分页信息
$page = $check->is_number($_REQUEST['page']) ? trim($_REQUEST['page']) : 1;
$limit = $dou->pager('gallery', ($_DISPLAY['gallery'] ? $_DISPLAY['gallery'] : 10), $page, $dou->rewrite_url('gallery_category', $cat_id), $where);

/* 获取案例列表 */
$sql = "SELECT id, title, image, cat_id, add_time, click, description FROM " . $dou->table('gallery') . $where . " ORDER BY id DESC" . $limit;
$query = $dou->query($sql);

while ($row = $dou->fetch_array($query)) {
    $url = $dou->rewrite_url('gallery', $row['id']);
    $add_time = date("Y-m-d", $row['add_time']);
    $add_time_short = date("m-d", $row['add_time']);
    $image = $row['image'] ? ROOT_URL . $row['image'] : '';
    
    $gallery_list[] = array(
            "id" => $row['id'],
            "cat_id" => $row['cat_id'],
            "title" => $row['title'],
            "image" => $image,
            "add_time" => $add_time,
            "add_time_short" => $add_time_short,
            "click" => $row['click'],
            "description" => $row['description'],
            "url" => $url 
    );
}

// 获取分类信息
$sql = "SELECT cat_id, cat_name, parent_id FROM " . $dou->table('gallery_category') . " WHERE cat_id = '$cat_id'";
$query = $dou->query($sql);
$cate_info = $dou->fetch_array($query);

// 如果为末级分类则显示当前同级分类
if ($dou->dou_child_id('gallery_category', $cat_id)) {
    $parent_id = $cat_id;
} else {
    $parent_id = $dou->get_one("SELECT parent_id FROM " . $dou->table('gallery_category') . " WHERE cat_id = '$cat_id'");;
}

// 赋值给模板-meta和title信息
$smarty->assign('page_title', $dou->page_title('gallery_category', $cat_id));
$smarty->assign('keywords', $cate_info['keywords']);
$smarty->assign('description', $cate_info['description']);

// 赋值给模板-导航栏
$smarty->assign('nav_list', $dou->get_nav_mobile('mobile', '0', 'gallery_category', $cat_id, $cate_info['parent_id']));

// 赋值给模板-数据
$smarty->assign('head', $dou->head($_LANG['gallery_category']));
$smarty->assign('ur_here', $dou->ur_here('gallery_category', $cat_id));
$smarty->assign('cate_info', $cate_info);
$smarty->assign('gallery_category', $dou->get_category('gallery_category', $parent_id, $cat_id));
$smarty->assign('gallery_list', $gallery_list);

$smarty->display('gallery_category.dwt');
?>