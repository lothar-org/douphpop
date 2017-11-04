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



/*Smarty模板统一配置*/
require (ROOT_PATH . 'include/smarty/Smarty.class.php');
// require (ROOT_PATH . 'include/smarty/SmartyBC.class.php');// 兼容低版本 无效？
$smarty = new Smarty();
$smarty->left_delimiter = '{'; // 左定界符
$smarty->right_delimiter = '}'; // 右定界符
// $smarty->config_dir = ROOT_PATH . 'include/smarty/Config_File.class.php'; // 目录变量
// $smarty->config_dir = ROOT_PATH . 'configs/';// 指定smarty加载外部配置文件应当存放与此目录下
// $smarty->allow_php_tag = true;// 开启php语法支持。 Smarty2.6
// $smarty->php_handling = true;// 开启php语法支持。Smarty3.1

if (SMARTY_CONF==1) { // 前台
    /*
    * 由原版的 2.6 向 3.1 转
    * 是否可写在一块？ smarty.init.php
    */
    // SMARTY配置
    $smarty->template_dir = ROOT_PATH . 'theme'. DS . $_CFG['site_theme'];// html模板地址
    $smarty->compile_dir = ROOT_PATH . 'template_c';// 模板编译生成的文件（能让php脚本编译的）
    // $smarty->cache_dir = ROOT_PATH . 'cache';// 缓存（数据库查询、临时数据）
    // 以下是开启缓存的两个必要配置。通常不用smarty的缓存机制。
    // $smarty->caching = true;// 开启缓存,很笨的缓存
    // $smarty->cache_lifetime = 60;//缓存时间
    // 如果编译和缓存目录不存在则建立 需要有目录操作权限
    if (!file_exists($smarty->compile_dir)) mkdir($smarty->compile_dir, 0777);
    if (!file_exists($smarty->cache_dir)) mkdir($smarty->cache_dir, 0777);
    /*// Smarty 3.x 系列对象型配置
    $smarty ->setTemplateDir(ROOT_PATH . 'theme'. DS . $_CFG['site_theme']) //设置所有模板文件存放的目录
            // ->addTemplateDir(ROOT_PATH.'templates2/') //可以添加多个模板目录（前后台各一个）
            ->setCompileDir(ROOT_PATH . 'cache') //设置所有编译过的模板文件存放的目录
            // ->setPluginsDir(ROOT_PATH.'plugins/') //设置为模板扩充插件存放的目录
            // ->setConfigDir(ROOT_PATH.'configs')//设置模板配置文件存放的目录
            ->setCacheDir(ROOT_PATH . 'cache'.DS.'data'); //设置缓存文件存放的目录
    // $smarty->caching = false; //设置Smarty缓存开关功能
    // $smarty->cache_lifetime = 60*60*24; //设置模板缓存有效时间段的长度为1天
    $smarty->left_delimiter = '<{'; //设置模板语言中的左结束符
    $smarty->right_delimiter = '}>'; //设置模板语言中的右结束符*/

    /*
    * Smarty 过滤器 ， 改变了原始路径
    * PCRE(正则)：
                /……/ 是开始结束符，\ 是转义符，.匹配任意字符，*重复前面内容
                U 去除贪婪模式，m 多行匹配，s 是让.能匹配换行
    * 虐杀原形：// link后面直接匹配href的，不能随意颠倒顺序
            <script type="text/javascript" src="images/jquery.min.js"></script>
            <link href="style.css" rel="stylesheet" type="text/css" />
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=". DOU_CHARSET ."\">
    * 原版： function remove_html_comments($source, & $smarty) {}
            & $smarty 起什么作用？
    */
    // function remove_html_comments($source, & $smarty) {
    function remove_html_comments($source) {
        global $_CFG;
        $theme_path = ROOT_URL . 'theme';
        $source = preg_replace('/\"\.*\/images\//Ums', '"images/', $source);
        $source = preg_replace('/\"images\//Ums', "\"theme/$_CFG[site_theme]/images/", $source);
        // $source = preg_replace('/\"\.*\/images\//Ums', "\"theme/$_CFG[site_theme]/images/", $source);// 上两个合并？不成功

        // $source = preg_replace('/\"\.*\/img\//Ums', '"img/', $source);
        // $source = preg_replace('/\"img\//Ums', "\"theme/$_CFG[site_theme]/img/", $source);
        // $source = preg_replace('/\"\.*\/css\//Ums', '"css/', $source);
        // $source = preg_replace('/\"css\//Ums', "\"theme/$_CFG[site_theme]/css/", $source);
        // $source = preg_replace('/\"\.*\/js\//Ums', '"js/', $source);
        // $source = preg_replace('/\"js\//Ums', "\"theme/$_CFG[site_theme]/js/", $source);

        $source = preg_replace('/link href\=\"([A-Za-z0-9_-]+)\.css/Ums', "link href=\"theme/$_CFG[site_theme]/$1.css", $source);
        $source = preg_replace('/img src\=\"sys\//Ums', "img src=\"theme/$_CFG[site_theme]/sys/", $source);// 系统文件
        // $source = preg_replace('/img src=\"sys\//Ums', "img src=\"theme/$_CFG[site_theme]/sys/", $source);
        // $source = preg_replace('/\"style\//Ums', "\"theme/$_CFG[site_theme]/style/", $source);// 自定义1
        // $source = preg_replace('/link href\=\"style\.css/Ums', "link href=\"theme/$_CFG[site_theme]/style.css", $source);// 自定义2
        $source = preg_replace('/theme\//Ums', "$theme_path/", $source);
        $source = preg_replace('/^<meta\shttp-equiv=["|\']Content-Type["|\']\scontent=["|\']text\/html;\scharset=(?:.*?)["|\'][^>]*?>\r?\n?/i', '', $source);
        return $source = preg_replace('/<!--.*{(.*)}.*-->/U', '{$1}', $source);
    }
    // Smarty用于模板编译的过滤器函数
    // $smarty->register_prefilter('remove_html_comments');// Smarty 2.6
    $smarty->registerFilter('pre','remove_html_comments');// "pre"前置, "post"后置, "output"输出 ，"variable"。
    // $smarty->registerPlugin('function','remove_html_comments','remove_html_comments');// 需要被使用
    // $smarty->register->preFilter('remove_html_comments');// 不存在的
    // $smarty->clearCompiledTemplate('filter.html');//清除编译目录下的编译文件或者指定条件的编译文件。
    /*无法实现？直接忽略注册函数*/
    // $_DPS = array("img"=>"","js"=>"","css"=>"","org"=>"");
    // $smarty->assign('dps',$_DPS);

} elseif (SMARTY_CONF==2 && IS_ADMIN===true) {// 后台
    $smarty->template_dir = ROOT_PATH . ADMIN_PATH . '/templates'; // 模板存放目录
    $smarty->compile_dir = ROOT_PATH . 'template_c/' . ADMIN_PATH; // 编译目录
    // 如果编译和缓存目录不存在则建立
    if (!file_exists($smarty->compile_dir)) mkdir($smarty->compile_dir, 0777);
    // Smarty 过滤器
    function remove_html_comments($source) {
        return $source = preg_replace('/<!--.*{(.*)}.*-->/U', '{$1}', $source);
    }
    $smarty->registerFilter('pre','remove_html_comments');

} elseif (SMARTY_CONF==3 && IS_MOBILE) { // 手机
    // SMARTY配置
    $smarty->template_dir = ROOT_PATH . M_PATH . '/theme/' . $_CFG['mobile_theme']; // 模板存放目录
    $smarty->compile_dir = ROOT_PATH . 'template_c/' . M_PATH; // 编译目录
    // 如果编译和缓存目录不存在则建立
    if (!file_exists($smarty->compile_dir)) mkdir($smarty->compile_dir, 0777);
    // Smarty 过滤器
    function remove_html_comments($source) {
        global $_CFG;
        $theme_path = M_URL . 'theme';
        $source = preg_replace('/\"\.*\/images\//Ums', '"images/', $source);
        $source = preg_replace('/\"images\//Ums', "\"theme/$_CFG[mobile_theme]/images/", $source);
        $source = preg_replace('/link href\=\"([A-Za-z0-9_-]+)\.css/Ums', "link href=\"theme/$_CFG[mobile_theme]/$1.css", $source);
        $source = preg_replace('/theme\//Ums', "$theme_path/", $source);
        $source = preg_replace('/^<meta\shttp-equiv=["|\']Content-Type["|\']\scontent=["|\']text\/html;\scharset=(?:.*?)["|\'][^>]*?>\r?\n?/i', '', $source);
        return $source = preg_replace('/<!--.*{(.*)}.*-->/U', '{$1}', $source);
    }
    $smarty->registerFilter('pre','remove_html_comments');
}
?>