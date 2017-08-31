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