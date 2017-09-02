<?php
define('IN_LOTHAR', true);
require (dirname(__FILE__) . '/include/init.php');
require ROOT_PATH .'public.php';

// 验证并获取合法的ID，如果不合法将其设定为-1
$id = $firewall->get_legal_id('page', $_REQUEST['id'], $_REQUEST['unique_id']);
if ($id == -1)
    $dou->dou_msg($GLOBALS['_LANG']['page_wrong'], ROOT_URL);
    
    // 获取单页面信息
$page = $dou->get_page_info($id);
$top_id = $page['parent_id'] == 0 ? $id : $page['parent_id'];

// 赋值给模板-meta和title信息
$smarty->assign('page_title', $dou->page_title('page', '', $page['page_name']));
$smarty->assign('keywords', $page['keywords']);
$smarty->assign('description', $page['description']);

// 赋值给模板-导航栏
$smarty->assign('nav_top_list', $dou->get_nav('top'));
$smarty->assign('nav_middle_list', $dou->get_nav('middle', '0', 'page', $id));
$smarty->assign('nav_bottom_list', $dou->get_nav('bottom'));

// 赋值给模板-数据
$smarty->assign('ur_here', $dou->ur_here('page', '', $page['page_name']));
$smarty->assign('page_list', $dou->get_page_list($top_id, $id));
$smarty->assign('top', $dou->get_page_info($top_id));
$smarty->assign('page', $page);
if ($top_id == $id)
    $smarty->assign("top_cur", 'top_cur');

$_CFG['deftpl']['page'] = 'page.dwt';
$thistpl = $page['template']?$page['template']:$_CFG['deftpl']['page'];
$smarty->display($thistpl);
?>