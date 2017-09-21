<?php
if (!defined('IN_LOTHAR')) die('Hacking attempt');
// 显示除了E_NOTICE(提示)和E_WARNING(警告)外的所有错误
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
// error_reporting(E_ERROR);
// error_reporting(0);
// 关闭 set_magic_quotes_runtime
@set_magic_quotes_runtime(0);
// 调整时区
if (PHP_VERSION >= '5.1') date_default_timezone_set('PRC');
if (empty(session_id())) session_start();

// 前台标记
define('SMARTY_CONF', 1);

// 定义DouPHP基础常量
define('ROOT_PATH', str_replace('include/init.php', '', str_replace('\\', '/', __FILE__)));
define('ROOT_HOST', 'http://' . $_SERVER['HTTP_HOST']);
$root_url = dirname(ROOT_HOST . $_SERVER['PHP_SELF']) .'/';
define('ROOT_URL', !defined('ROUTE') ? $root_url : str_replace('include/', '', $root_url)); // 区分route.php作为入口的情况来分别赋值

if (!file_exists(ROOT_PATH . "data/system.dou")) {
      header("Location: " . ROOT_URL . "install/index.php\n");
      exit();
}
// 日期时间
define('CTIME', $_SERVER['REQUEST_TIME']);

// 载入DouPHP核心文件
require_once (ROOT_PATH . 'data/config.php'); // 伪静态下config.php会在route.php中第一次被调用
require (ROOT_PATH . 'include/mysql.class.php');
require (ROOT_PATH . 'include/common.class.php');
require (ROOT_PATH . 'include/action.class.php');
require (ROOT_PATH . 'include/check.class.php');
require (ROOT_PATH . 'include/firewall.class.php');
require (ROOT_PATH . 'include/memory.class.php');

// 定义DouPHP其它常量
// DS 在 Smarty 里定义了
// M_PATH \data\config.php
// define('M_URL', ROOT_URL . M_PATH . '/');
define('M_URL', ROOT_HOST .'/'. M_PATH . '/');

// 实例化DouPHP核心类
$dou = new Action($dbhost, $dbuser, $dbpass, $dbname, $prefix, DOU_CHARSET);
$check = new Check();
$firewall = new Firewall();
$memory = new Memory();
// $memory = new Memory('localhost',11211);

// 定义系统标识
define('DOU_SHELL', $dou->get_one("SELECT value FROM " . $dou->table('config') . " WHERE name = 'hash_code'"));
define('DOU_ID', 'dou_' . substr(md5(DOU_SHELL . 'dou'), 0, 5));

// 读取站点信息
$_CFG = $dou->get_config();
// $GLOBALS['_CFG'] 自动等于 $_CFG ？


// !EXIT_INIT 网站常规模式， 验证码 captcha.php 中有定义 define('EXIT_INIT', true);
if (!defined('EXIT_INIT')) {
    // 设置页面缓存和编码
    header('Cache-control: private');
    header('Content-type: text/html; charset='. DOU_CHARSET);

    // 判断是否跳转到手机版（条件：是移动端、没有强制显示PC版、手机版没有关闭）
    if ($dou->is_mobile() && $_COOKIE['client']!='pc' && !$_CFG['mobile_closed']) {
        $mobile_url = str_replace(ROOT_URL, '', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        $dou->dou_header(ROOT_URL . M_PATH . '/' . $mobile_url);
    }

    // 豆壳防火墙 ，过滤机制 dou_magic_quotes()
    $firewall->dou_firewall();
    // SMARTY配置
    // \include\smarty.init.php
    // 系统模块
    $_MODULE = $dou->dou_module();

    // 语言包控制管理
    // \include\lang.init.php
    // 载入语言文件
    foreach ((array) $_MODULE['lang'] as $lang_file) {
        require ($lang_file);
    }
    // 判断是否关闭站点
    if ($_CFG['site_closed']) {
        // 设置页面编码
        header('Content-type: text/html; charset='. DOU_CHARSET);
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=". DOU_CHARSET ."\"><div style=\"margin: 200px; text-align: center; font-size: 14px\"><p>" . $_LANG['site_closed'] . "</p><p></p></div>";
        exit();
    }
    // $_LANG['copyright'] = preg_replace('/d%/Ums', $_CFG['site_name'].' 2017', $_LANG['copyright']);

    // 载入模块文件
    foreach ((array)$_MODULE['init'] as $init_file) {
        require ($init_file);
    }
    // 如果存在自定义类则载入
    if (file_exists($other_class_file = ROOT_PATH . 'include/other.class.php')) {
          require ($other_class_file);
          $other = new Other();
    }

    // 网站字符集
    $_CFG['dou_charset'] = DOU_CHARSET;
    // 通用信息调用
    $smarty->assign("lang", $_LANG);
    $smarty->assign("site", $_CFG);
    $smarty->assign("url", $_URL = $dou->dou_url()); // 模块URL
    $smarty->assign("open", $_OPEN = $_MODULE['open']); // 模块开启状态
    $_DISPLAY = unserialize($_CFG['display']); // 显示设置
    $_DEFINED = unserialize($_CFG['defined']); // 自定义属性
}

// 开启缓冲区。 ob_end_flush();在哪？\admin\include\cloud.class.php handle()
ob_start();
?>