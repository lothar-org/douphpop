<?php

define('IN_LOTHAR', true);

require (dirname(__FILE__) . '/include/init.php');

// 验证并获取合法的ID，如果不合法将其设定为-1
$id = $firewall->get_legal_id('gallery', $_REQUEST['id'], $_REQUEST['unique_id']);
$cat_id = $dou->get_one("SELECT cat_id FROM " . $dou->table('gallery') . " WHERE id = '$id'");
$parent_id = $dou->get_one("SELECT parent_id FROM " . $dou->table('gallery_category') . " WHERE cat_id = '" . $cat_id . '\'');
if ($id == -1)
    $dou->dou_msg($GLOBALS['_LANG']['page_wrong'], M_URL);
    
/* 获取详细信息 */
$query = $dou->select($dou->table('gallery'), '*', '`id` = \'' . $id . '\'');
$gallery = $dou->fetch_array($query);

// 格式化数据
$gallery['add_time'] = date("Y-m-d", $gallery['add_time']);
$gallery['array'] = unserialize($gallery['gallery']);

// 格式化自定义参数
foreach (explode(',', $gallery['defined']) as $row) {
    $row = explode('：', str_replace(":", "：", $row));
    
    if ($row['1']) {
        $defined[] = array(
                "arr" => $row['0'],
                "value" => $row['1'] 
        );
    }
}

// 访问统计
$click = $gallery['click'] + 1;
$dou->query("update " . $dou->table('gallery') . " SET click = '$click' WHERE id = '$id'");

// 赋值给模板-meta和title信息
$smarty->assign('page_title', $dou->page_title('gallery_category', $cat_id, $gallery['title']));
$smarty->assign('keywords', $gallery['keywords']);
$smarty->assign('description', $gallery['description']);

// 赋值给模板-导航栏
$smarty->assign('nav_list', $dou->get_nav_mobile('mobile', '0', 'gallery_category', $cat_id, $parent_id));

// 赋值给模板-数据
$smarty->assign('head', $dou->head($_LANG['gallery']));
$smarty->assign('ur_here', $dou->ur_here('gallery_category', $cat_id));
$smarty->assign('gallery_category', $dou->get_category('gallery_category', 0, $cat_id));
$smarty->assign('gallery', $gallery);
$smarty->assign('defined', $defined);

$smarty->display('gallery.dwt');
?>