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
$id = $firewall->get_legal_id('download', $_REQUEST['id'], $_REQUEST['unique_id']);
$cat_id = $dou->get_one("SELECT cat_id FROM " . $dou->table('download') . " WHERE id = '$id'");
$parent_id = $dou->get_one("SELECT parent_id FROM " . $dou->table('download_category') . " WHERE cat_id = '" . $cat_id . '\'');
if ($id == -1)
    $dou->dou_msg($GLOBALS['_LANG']['page_wrong'], M_URL);
    
    /* 获取详细信息 */
$query = $dou->select($dou->table('download'), '*', '`id` = \'' . $id . '\'');
$download = $dou->fetch_array($query);

// 格式化数据
$download['add_time'] = date("Y-m-d", $download['add_time']);
$download['image'] = $download['image'] ? ROOT_URL . $download['image'] : '';

// 格式化自定义参数
foreach (explode(',', $download['defined']) as $row) {
    $row = explode('：', str_replace(":", "：", $row));
    
    if ($row['1']) {
        $defined[] = array(
                "arr" => $row['0'],
                "value" => $row['1'] 
        );
    }
}

// 访问统计
$click = $download['click'] + 1;
$dou->query("update " . $dou->table('download') . " SET click = '$click' WHERE id = '$id'");

// 赋值给模板-meta和title信息
$smarty->assign('page_title', $dou->page_title('download_category', $cat_id, $download['title']));
$smarty->assign('keywords', $download['keywords']);
$smarty->assign('description', $download['description']);

// 赋值给模板-导航栏
$smarty->assign('nav_list', $dou->get_nav_mobile('mobile', '0', 'download_category', $cat_id, $parent_id));

// 赋值给模板-数据
$smarty->assign('head', $dou->head($_LANG['download']));
$smarty->assign('ur_here', $dou->ur_here('download_category', $cat_id));
$smarty->assign('download_category', $dou->get_category('download_category', 0, $cat_id));
$smarty->assign('download', $download);
$smarty->assign('defined', $defined);

$smarty->display('download.dwt');
?>