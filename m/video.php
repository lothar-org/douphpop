<?php

define('IN_LOTHAR', true);

require (dirname(__FILE__) . '/include/init.php');

// 验证并获取合法的ID，如果不合法将其设定为-1
$id = $firewall->get_legal_id('video', $_REQUEST['id'], $_REQUEST['unique_id']);
$cat_id = $dou->get_one("SELECT cat_id FROM " . $dou->table('video') . " WHERE id = '$id'");
$parent_id = $dou->get_one("SELECT parent_id FROM " . $dou->table('video_category') . " WHERE cat_id = '" . $cat_id . '\'');
if ($id == -1)
    $dou->dou_msg($GLOBALS['_LANG']['page_wrong'], M_URL);
    
    /* 获取详细信息 */
$query = $dou->select($dou->table('video'), '*', '`id` = \'' . $id . '\'');
$video = $dou->fetch_array($query);
$video['format'] = substr($video['file'], strrpos($video['file'], '.'));

// 格式化数据
$video['add_time'] = date("Y-m-d", $video['add_time']);
if (strpos($video['file'], 'http://') === false) $video['file'] = ROOT_URL . $video['file'];


// 格式化自定义参数
foreach (explode(',', $video['defined']) as $row) {
    $row = explode('：', str_replace(":", "：", $row));
    
    if ($row['1']) {
        $defined[] = array(
                "arr" => $row['0'],
                "value" => $row['1'] 
        );
    }
}

// 访问统计
$click = $video['click'] + 1;
$dou->query("update " . $dou->table('video') . " SET click = '$click' WHERE id = '$id'");

// 赋值给模板-meta和title信息
$smarty->assign('page_title', $dou->page_title('video_category', $cat_id, $video['title']));
$smarty->assign('keywords', $video['keywords']);
$smarty->assign('description', $video['description']);

// 赋值给模板-导航栏
$smarty->assign('nav_list', $dou->get_nav_mobile('mobile', '0', 'video_category', $cat_id, $parent_id));

// 赋值给模板-数据
$smarty->assign('head', $dou->head($_LANG['video']));
$smarty->assign('ur_here', $dou->ur_here('video_category', $cat_id));
$smarty->assign('video_category', $dou->get_category('video_category', 0, $cat_id));
$smarty->assign('video', $video);
$smarty->assign('defined', $defined);

$smarty->display('video.dwt');
?>