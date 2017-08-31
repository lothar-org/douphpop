<?php
// if (!defined('IN_LOTHAR')) die('Hacking attempt');
/*// 获取 \include\common.class.php
function dou_module() {
    // 读取模块初始化文件
    $init_list = glob(ROOT_PATH . 'include/' . '*.init.php');
    if (is_array($init_list)) {
        foreach ($init_list as $init) {
            $module['init'][] = $init;
        }
    }
}*/
/*// 会被以下操作循环载入
foreach ((array)$_MODULE['init'] as $init_file) {
    require ($init_file);
}*/


/*语言包统一配置*/
// 后台 中英文切换
// $lang_mark = array(1=>'zh_cn',2=>'en_us');
// $lang_mark = get_subdirs(ROOT_PATH .'languages');
// var_dump(session_id());
// if (isset($_GET['lpost'])) {
//     if (in_array($_GET['lpost'],$lang_mark)) {
//         if ($_SESSION['lang_identifier'] != $_GET['lpost']) {
//             $_SESSION['lang_identifier'] = $_GET['lpost'];
//             $dou->dou_header($_SERVER['HTTP_REFERER']);
//         }
//     }
// }

// 前台 中英文切换 取反
// if (isset($_GET['langchange'])) {
//     if ($_GET['langchange']==1) {
//         $_SESSION['lang_identifier'] = $lang_mark[2];
//     } else {
//         $_SESSION['lang_identifier'] = $lang_mark[1];
//     }
//     $dou->dou_header($_SERVER['HTTP_REFERER']);
// }

// 统一
// if (!isset($_SESSION['lang_identifier']) || $_SESSION['lang_identifier']=='zh_cn') {
//     $_CFG['lang_type'] = $lang_type = 1;
// } elseif ($_SESSION['lang_identifier']=='en_us') {
//     $_CFG['lang_type'] = $lang_type = 2;
// }

/*语言包控制管理*/
// if (IS_ADMIN===true) {
// } elseif (IS_MOBILE) {
// } else {
//     // 语言切换
//     // $GLOBALS['_CFG']['language'] = 'zh_cn';
//     // $GLOBALS['_CFG']['language'] = 'en_us';
//     // $GLOBALS['_CFG']['language'] = $_SESSION['lang_identifier'];
//     // echo $GLOBALS['_CFG']['language'];
//     // 风格模板
//     // echo $_CFG['site_theme'];
//     // $_CFG['site_theme'] = 'default';
//     // $_CFG['site_theme'] = 'en_us';
//     // $_CFG['site_theme'] = $_SESSION['lang_identifier'];
//     /*读取 theme/$_CFG[site_theme]/style.css 的头信息
//     \admin\include\action.class.php      get_theme_info()
//     \admin\theme.php
//     $theme_enable = $dou->get_theme_info($_CFG['site_theme']);
//     $theme_array = $dou->get_subdirs(ROOT_PATH . 'theme/');*/
// }
?>