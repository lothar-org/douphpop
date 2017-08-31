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
$cat_id = $firewall->get_legal_id('product_category', $_REQUEST['id'], $_REQUEST['unique_id']);
if ($cat_id == -1) {
    $dou->dou_msg($GLOBALS['_LANG']['page_wrong'], M_URL);
} else {
    $where = ' WHERE cat_id IN (' . $cat_id . $dou->dou_child_id('product_category', $cat_id) . ')';
}
    
// 获取分页信息
$page = $check->is_number($_REQUEST['page']) ? trim($_REQUEST['page']) : 1;
$limit = $dou->pager('product', ($_DISPLAY['product'] ? $_DISPLAY['product'] : 10), $page, $dou->rewrite_url('product_category', $cat_id), $where);

/* 获取产品列表 */
$sql = "SELECT id, cat_id, name, price, content, image, add_time, description FROM " . $dou->table('product') . $where . " ORDER BY id DESC" . $limit;
$query = $dou->query($sql);

while ($row = $dou->fetch_array($query)) {
    $url = $dou->rewrite_url('product', $row['id']); // 获取经过伪静态产品链接
    $add_time = date("Y-m-d", $row['add_time']);
    
    // 如果描述不存在则自动从详细介绍中截取
    $description = $row['description'] ? $row['description'] : $dou->dou_substr($row['content'], 150);
    
    // 生成缩略图的文件名
    $image = explode(".", $row['image']);
    $thumb = ROOT_URL . $image[0] . "_thumb." . $image[1];
    
    // 格式化价格
    $price = $row['price'] > 0 ? $dou->price_format($row['price']) : $_LANG['price_discuss'];
    
    $product_list[] = array(
            "id" => $row['id'],
            "cat_id" => $row['cat_id'],
            "name" => $row['name'],
            "price" => $price,
            "thumb" => $thumb,
            "add_time" => $add_time,
            "description" => $description,
            "url" => $url 
    );
}

// 获取分类信息
$sql = "SELECT cat_id, cat_name, parent_id FROM " . $dou->table('product_category') . " WHERE cat_id = '$cat_id'";
$query = $dou->query($sql);
$cate_info = $dou->fetch_assoc($query);

// 如果为末级分类则显示当前同级分类
if ($dou->dou_child_id('product_category', $cat_id)) {
    $parent_id = $cat_id;
} else {
    $parent_id = $dou->get_one("SELECT parent_id FROM " . $dou->table('product_category') . " WHERE cat_id = '$cat_id'");;
}

// 赋值给模板-meta和title信息
$smarty->assign('page_title', $dou->page_title('product_category', $cat_id));
$smarty->assign('keywords', $cate_info['keywords']);
$smarty->assign('description', $cate_info['description']);

// 赋值给模板-导航栏
$smarty->assign('nav_list', $dou->get_nav_mobile('mobile', '0', 'product_category', $cat_id, $cate_info['parent_id']));

// 赋值给模板-数据
$smarty->assign('head', $dou->head($_LANG['product_category']));
$smarty->assign('ur_here', $dou->ur_here('product_category', $cat_id));
$smarty->assign('cate_info', $cate_info);
$smarty->assign('product_category', $dou->get_category('product_category', $parent_id, $cat_id));
$smarty->assign('product_list', $product_list);

$smarty->display('product_category.dwt');
?>