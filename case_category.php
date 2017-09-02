<?php
define('IN_LOTHAR', true);
require (dirname(__FILE__) . '/include/init.php');

// 验证并获取合法的ID，如果不合法将其设定为-1
$cat_id = $firewall->get_legal_id('case_category', $_REQUEST['id'], $_REQUEST['unique_id']);
if ($cat_id == -1) {
    $dou->dou_msg($GLOBALS['_LANG']['page_wrong'], ROOT_URL);
} else {
    $cat_ids = $cat_id . $dou->dou_child_id('case_category', $cat_id);
    if (is_numeric($cat_ids)) {
        $where = ' WHERE cat_id='.$cat_ids;
    } else {
        $where = ' WHERE cat_id IN ('. $cat_ids .')';
    }
}
    
// 获取分页信息
$page = $check->is_number($_REQUEST['page']) ? trim($_REQUEST['page']) : 1;
$limit = $dou->pager('case', ($_DISPLAY['case'] ? $_DISPLAY['case'] : 10), $page, $dou->rewrite_url('case_category', $cat_id), $where);
/* 获取案例列表 */
$sql = "SELECT id,title,content,image,cat_id,add_time,click,description FROM " . $dou->table('case') . $where . " ORDER BY id DESC" . $limit;
$query = $dou->query($sql);
while ($row = $dou->fetch_assoc($query)) {
    $row['url'] = $dou->rewrite_url('case', $row['id']);
    $row['add_time'] = date("Y-m-d", $row['add_time']);
    $row['add_time_short'] = date("m-d", $row['add_time']);
    $row['image'] = $row['image'] ? ROOT_URL . $row['image'] : '';
    // 如果描述不存在则自动从详细介绍中截取
    $row['description'] = $row['description'] ? $row['description'] : $dou->dou_substr($row['content'], 200);
    $case_list[] = $row;
}

// 获取分类信息
$sql = "SELECT cat_id,cat_name,parent_id FROM " . $dou->table('case_category') . " WHERE cat_id='$cat_id'";
$cate_info = $dou->fetchRow($sql);

// 赋值给模板-meta和title信息
$smarty->assign('page_title', $dou->page_title('case_category', $cat_id));
$smarty->assign('keywords', $cate_info['keywords']);
$smarty->assign('description', $cate_info['description']);

// 赋值给模板-导航栏
$smarty->assign('nav_top_list', $dou->get_nav('top'));
$smarty->assign('nav_middle_list', $dou->get_nav('middle', '0', 'case_category', $cat_id, $cate_info['parent_id']));
$smarty->assign('nav_bottom_list', $dou->get_nav('bottom'));

// 赋值给模板-数据
$smarty->assign('ur_here', $dou->ur_here('case_category', $cat_id));
$smarty->assign('cate_info', $cate_info);
$smarty->assign('case_category', $dou->get_category('case_category', 0, $cat_id));
$smarty->assign('case_list', $case_list);

$smarty->display('case_category.dwt');
?>