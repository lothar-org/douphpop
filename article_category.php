<?php
define('IN_LOTHAR', true);
require (dirname(__FILE__) . '/include/init.php');
require ROOT_PATH .'public.php';

// 验证并获取合法的ID，如果不合法将其设定为-1
$cat_id = $firewall->get_legal_id('article_category', $_REQUEST['id'], $_REQUEST['unique_id']);
if ($cat_id == -1) {
    $dou->dou_msg($GLOBALS['_LANG']['page_wrong'], ROOT_URL);
} else {
    $cat_ids = $cat_id . $dou->dou_child_id('article_category', $cat_id);
    if (strpos($cat_ids,',')) {
        $where = ' WHERE cat_id IN ('. $cat_ids .')';
    } else {
        $where = ' WHERE cat_id='.$cat_ids;
    }
}
    
// 获取分页信息
$page = $check->is_number($_REQUEST['page']) ? trim($_REQUEST['page']) : 1;
$limit = $dou->pager('article', ($_DISPLAY['article'] ? $_DISPLAY['article'] : 10), $page, $dou->rewrite_url('article_category', $cat_id), $where);
/* 获取文章列表 */
$sql = "SELECT id,title,content,image,cat_id,add_time,click,description FROM " . $dou->table('article') . $where . " ORDER BY sort,id DESC" . $limit;
$query = $dou->query($sql);
while ($row = $dou->fetch_array($query,MYSQL_ASSOC)) {
    $row['url'] = $dou->rewrite_url('article', $row['id']);
    $row['add_time'] = date("Y-m-d", $row['add_time']);
    $row['add_time_short'] = date("m-d", $row['add_time']);
    $row['image'] = $row['image'] ? ROOT_URL . $row['image'] : '';
    // 如果描述不存在则自动从详细介绍中截取
    $row['description'] = $row['description'] ? $row['description'] : $dou->dou_substr($row['content'], 200, false);
    $article_list[] = $row;
}

// 获取分类信息
$sql = "SELECT cat_id,parent_id,cat_name,keywords,description,template FROM " . $dou->table('article_category') . " WHERE cat_id = '$cat_id'";
$query = $dou->query($sql);
$cate_info = $dou->fetch_array($query,MYSQL_ASSOC);

// 赋值给模板-meta和title信息
$smarty->assign('page_title', $dou->page_title('article_category', $cat_id));
$smarty->assign('keywords', $cate_info['keywords']);
$smarty->assign('description', $cate_info['description']);

// 赋值给模板-导航栏
$smarty->assign('nav_top_list', $dou->get_nav('top'));
$smarty->assign('nav_middle_list', $dou->get_nav('middle', '0', 'article_category', $cat_id, $cate_info['parent_id']));
$smarty->assign('nav_bottom_list', $dou->get_nav('bottom'));

// 赋值给模板-数据
$smarty->assign('ur_here', $dou->ur_here('article_category', $cat_id));
$smarty->assign('cate_info', $cate_info);
$smarty->assign('article_category', $dou->get_category('article_category', 0, $cat_id));
$smarty->assign('article_list', $article_list);

$_CFG['deftpl']['article_category'] = 'article_category.dwt';
$thistpl = $cate_info['template']?$cate_info['template']:$_CFG['deftpl']['article_category'];
$smarty->display($thistpl);
?>