<?php
define('IN_LOTHAR', true);
require (dirname(__FILE__) . '/include/init.php');
require ROOT_PATH .'public.php';

// 验证并获取合法的ID，如果不合法将其设定为-1
$id = $firewall->get_legal_id('product', $_REQUEST['id'], $_REQUEST['unique_id']);
if ($id == -1)
    $dou->dou_msg($GLOBALS['_LANG']['page_wrong'], ROOT_URL);
    
/* 获取产品信息 */
$sql = sprintf('SELECT a.*,b.parent_id from %s a left join %s b on a.cat_id=b.cat_id where id=%d',$dou->table('product'),$dou->table('product_category'),$id);
$product = $dou->fetchRow($sql);
$cat_id = $product['cat_id'];
$parent_id = $product['parent_id'];
// $query = $dou->select($dou->table('product'), '*', '`id` = \'' . $id . '\'');
// $product = $dou->fetch_assoc($query);

// 格式化数据
$product['price'] = $product['price'] > 0 ? $dou->price_format($product['price']) : $_LANG['price_discuss'];
$product['add_time'] = date("Y-m-d", $product['add_time']);

// 生成缩略图的文件名
$image = explode(".", $product['image']);
$product['thumb'] = ROOT_URL . $image[0] . "_thumb." . $image[1];
$product['image'] = ROOT_URL . $product['image'];

// 格式化自定义参数
foreach (explode(',', $product['defined']) as $row) {
    $row = explode('：', str_replace(":", "：", $row));
    
    if ($row['1']) {
        $defined[] = array(
                "arr" => $row['0'],
                "value" => $row['1'] 
        );
    }
}

// 赋值给模板-meta和title信息
$smarty->assign('page_title', $dou->page_title('product_category', $cat_id, $product['name']));
$smarty->assign('keywords', $product['keywords']);
$smarty->assign('description', $product['description']);

// 赋值给模板-导航栏
$smarty->assign('nav_top_list', $dou->get_nav('top'));
$smarty->assign('nav_middle_list', $dou->get_nav('middle', '0', 'product_category', $cat_id, $parent_id));
$smarty->assign('nav_bottom_list', $dou->get_nav('bottom'));

// 赋值给模板-数据
$smarty->assign('ur_here', $dou->ur_here('product_category', $cat_id, $product['name']));
$smarty->assign('product_category', $dou->get_category('product_category', 0, $cat_id));
$smarty->assign('product', $product);
$smarty->assign('defined', $defined);

// $_CFG['deftpl']['product'] = 'product.dwt';
// $thistpl = $product['template']?$product['template']:$_CFG['deftpl']['product'];
// $smarty->display($thistpl);
$smarty->display('product.dwt');
?>