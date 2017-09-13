<?php
define('IN_LOTHAR', true);
require (dirname(__FILE__) . '/include/init.php');
require ROOT_PATH .'public.php';

// 验证并获取合法的ID，如果不合法将其设定为-1
$cat_id = $firewall->get_legal_id('product_category', $_REQUEST['id'], $_REQUEST['unique_id']);
if ($cat_id == -1) {
    $dou->dou_msg($GLOBALS['_LANG']['page_wrong'], ROOT_URL);
} else {
    $cat_ids = $cat_id . $dou->dou_child_id('product_category', $cat_id);
    if (is_numeric($cat_ids)) {
        $where = ' WHERE cat_id='.$cat_ids;
    } else {
        $where = ' WHERE cat_id IN ('. $cat_ids .')';
    }
}
    
// 获取分页信息
$page = $check->is_number($_REQUEST['page']) ? trim($_REQUEST['page']) : 1;
$limit = $dou->pager('product', ($_DISPLAY['product'] ? $_DISPLAY['product'] : 10), $page, $dou->rewrite_url('product_category', $cat_id), $where);
/* 获取产品列表 */
$sql = "SELECT id,cat_id,name,price,content,image,add_time,description FROM " . $dou->table('product') . $where . " ORDER BY id DESC" . $limit;
$query = $dou->query($sql,MYSQL_ASSOC);
while ($row = $dou->fetch_array($query)) {
    $row['url'] = $dou->rewrite_url('product', $row['id']); // 获取经过伪静态产品链接
    $row['add_time'] = date("Y-m-d", $row['add_time']);
    // 如果描述不存在则自动从详细介绍中截取
    $row['description'] = $row['description'] ? $row['description'] : $dou->dou_substr($row['content'], 150, false);
    if ($row['image']) {
        // 生成缩略图的文件名
        $image = explode('.', $row['image']);
        $row['thumb'] = ROOT_URL . $image[0] . "_thumb." . $image[1];
        // $row['image'] = ROOT_URL . $row['image'];
    }
    // 格式化价格
    $row['price'] = $row['price'] > 0 ? $dou->price_format($row['price']) : $_LANG['price_discuss'];
    $product_list[] = $row;
}

// 获取分类信息
$sql = "SELECT * FROM " . $dou->table('product_category') . " WHERE cat_id = '$cat_id'";
$query = $dou->query($sql);
$cate_info = $dou->fetch_assoc($query);

// 赋值给模板-meta和title信息
$smarty->assign('page_title', $dou->page_title('product_category', $cat_id));
$smarty->assign('keywords', $cate_info['keywords']);
$smarty->assign('description', $cate_info['description']);

// 赋值给模板-导航栏
$smarty->assign('nav_top_list', $dou->get_nav('top'));
$smarty->assign('nav_middle_list', $dou->get_nav('middle', '0', 'product_category', $cat_id, $cate_info['parent_id']));
$smarty->assign('nav_bottom_list', $dou->get_nav('bottom'));

// 赋值给模板-数据
$smarty->assign('ur_here', $dou->ur_here('product_category', $cat_id));
$smarty->assign('cate_info', $cate_info);
$smarty->assign('product_category', $dou->get_category('product_category', 0, $cat_id));
$smarty->assign('product_list', $product_list);

// $_CFG['deftpl']['product_category'] = 'product_category.dwt';
// $thistpl = $cate_info['template']?$cate_info['template']:$_CFG['deftpl']['product_category'];
// $smarty->display($thistpl);
$smarty->display('product_category.dwt');
?>