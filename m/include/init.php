<?php
if (!defined('IN_LOTHAR')) die('Hacking attempt');
// 显示除了E_NOTICE(提示)和E_WARNING(警告)外的所有错误
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
// 关闭 set_magic_quotes_runtime
@set_magic_quotes_runtime(0);
// 调整时区
if (PHP_VERSION >= '5.1') date_default_timezone_set('PRC');

// 移动版标记
define('IS_MOBILE', true);
define('SMARTY_CONF', 3);

// 定义DouPHP基础常量
$m_path = str_replace('/', '', strrchr(str_replace('/include/init.php', '', str_replace('\\', '/', __FILE__)), '/'));
$m_url = dirname('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']) . "/";
define('M_URL', !defined('ROUTE') ? $m_url : str_replace('include/', '', $m_url)); // 区分route.php作为入口的情况来分别赋值
define('ROOT_PATH', str_replace($m_path . '/include/init.php', '', str_replace('\\', '/', __FILE__)));
define('ROOT_URL', preg_replace('/\/'.$m_path.'\/*$/', '/', str_replace('http://' . $m_path, 'http://www', M_URL)));

if (!file_exists(ROOT_PATH . "data/system.dou")) {
    header("Location: " . ROOT_URL . "install/index.php\n");
    exit();
}

// 载入DouPHP核心文件
require_once (ROOT_PATH . 'data/config.php');
require (ROOT_PATH . 'include/mysql.class.php');
require (ROOT_PATH . 'include/common.class.php');
require (ROOT_PATH . M_PATH . '/include/action.class.php');
require (ROOT_PATH . 'include/check.class.php');
require (ROOT_PATH . 'include/firewall.class.php');
// require (ROOT_PATH . 'include/memory.class.php');

// 实例化DouPHP核心类
$dou = new Action($dbhost, $dbuser, $dbpass, $dbname, $prefix, DOU_CHARSET);
$check = new Check();
$firewall = new Firewall();
// $memory = new Memory();

// 定义系统标识
define('DOU_SHELL', $dou->get_one("SELECT value FROM " . $dou->table('config') . " WHERE name = 'hash_code'"));
define('DOU_ID', 'mobile_' . substr(md5(DOU_SHELL . 'mobile'), 0, 5));

// 读取站点信息
$_CFG = $dou->get_config();


if (!defined('EXIT_INIT')) {
    // 设置页面缓存和编码
    header('Cache-control: private');
    header('Content-type: text/html; charset=' . DOU_CHARSET);

    // 判断是否关闭手机版
    if ($_CFG['mobile_closed'])
        $dou->dou_header(ROOT_URL);

    // 豆壳防火墙
    $firewall->dou_firewall();
    // SMARTY配置
    // \include\smarty.init.php
    // 系统模块
    $_MODULE = $dou->dou_module();

    // 语言包控制管理
    // \include\lang.init.php
    // 载入语言文件
    foreach ((array) $_MODULE['lang'] as $lang_file) {
        require ($lang_file); // 载入系统语言文件
    }
    // 判断是否关闭站点
    if ($_CFG['site_closed']) {
        // 设置页面编码
        header('Content-type: text/html; charset=' . DOU_CHARSET);
        
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><div style=\"margin: 200px; text-align: center; font-size: 14px\"><p>" . $_LANG['site_closed'] . "</p><p></p></div>";
        exit();
    }
    $_LANG['copyright'] = preg_replace('/d%/Ums', $_CFG['site_name'], $_LANG['copyright']);

    // 载入模块文件
    foreach ((array) $_MODULE['init'] as $init_file) {
        require ($init_file);
    }
    // 如果存在自定义类则载入
    if (file_exists($other_class_file = ROOT_PATH . 'include/other.class.php')) {
          require ($other_class_file);
          $other = new Other();
    }
    
    // 通用信息调用
    $smarty->assign("lang", $_LANG);
    $smarty->assign("site", $_CFG);
    $_URL = $dou->dou_url();
    $_URL['catalog'] = $dou->rewrite_url('catalog');
    $smarty->assign("url", $_URL); // 模块URL
    $smarty->assign("open", $_OPEN = $_MODULE['open']); // 模块开启状态
    $_DISPLAY = unserialize($_CFG['mobile_display']); // 显示设置
    $_DEFINED = unserialize($_CFG['defined']); // 自定义属性
}
// 开启缓冲区
ob_start();
?>