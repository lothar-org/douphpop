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

include_once ('../../../data/config.php');

// 定义常量
define('ROOT_PATH', str_replace(ADMIN_PATH . '/include/ajaxupload.dou/action.php', '', str_replace('\\', '/', __FILE__)));
define('ROOT_URL', preg_replace('/admin\/include\/ajaxupload.dou' . '\//Ums', '', dirname('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']) . "/"));

// 引入主系统类
require (ROOT_PATH . 'include/check.class.php');
$check = new Check();

// 初始化
$file = $_FILES['fileupload'];
$module = $check->is_letter($_REQUEST['module']) ? $_REQUEST['module'] : 'other';
$filename = preg_match("/^[0-9_]+$/", $_REQUEST['filename']) ? $_REQUEST['filename'] : '';
$module_upload_path = ROOT_PATH . 'images/' . $module . '/files/';
if (!file_exists($module_upload_path) && $file['name'] != '') {
    mkdir($module_upload_path, 0777);
}
$max_file_size = 1024000; // 最大上传文件，单位kb

// 文件上传处理
if ($file['name'] != '') {
    if ($file['size'] > $max_file_size * 1024) {
        echo '文件大小超过限制，网站限制大小为' . $max_file_size/1024 . 'M';
        exit;
    }
    $file_type = strstr($file['name'], '.');
    if ($file_type != '.flv' && $file_type != '.swf' && $file_type != '.avi' && $file_type != '.mp4' && $file_type != '.wmv') {
        echo '允许上传的视频格式：flv、swf、avi、mp4、wmv，建议使用flv！';
        exit;
    }
    
    // 缓存文件移动到上传目录
    $new_file_name = $filename ? $filename . $file_type : date("Ymd") . '_' . date("dHis") . rand(100, 999). $file_type;
    $new_file_url = 'images/' . $module . '/files/' . $new_file_name;
    $new_file = $module_upload_path . $new_file_name;
    move_uploaded_file($file['tmp_name'], $new_file);
} else {
    echo '非法操作';
    exit;
}

$size = round($file['size']/1024);
$file_info = array(
    'file' => $new_file_url,
    'size' => $size
);
echo json_encode($file_info);
?>