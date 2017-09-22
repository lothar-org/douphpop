<?php
if (!defined('IN_LOTHAR')) die('Hacking attempt');

/* 插件唯一ID
----------------------------------------------------------------------------- */
$plugin['unique_id'] = 'yunpian';

/* 插件名称
----------------------------------------------------------------------------- */
$plugin['name'] = $plugin_mysql['name'] ? $plugin_mysql['name'] : '云片短信';

/* 插件描述
----------------------------------------------------------------------------- */
$plugin['description'] = $plugin_mysql['description'] ? $plugin_mysql['description'] : '及时有效的短信验证码';

/* 插件版本
----------------------------------------------------------------------------- */
$plugin['ver'] = '1.0';

/* 所属组
----------------------------------------------------------------------------- */
$plugin['plugin_group'] = 'msg';

/* 插件参数提交表单
 * type默认为'text'及文本框，可选"text,select,textarea"
 * option默认为空，select默认选项，如array("选项一" => 0,"选项二" => 1)
----------------------------------------------------------------------------- */
// 收款微信账号
$plugin['config'][] = array(
        "field" => 'account',
        "name" => '云片帐户',
        "value" => $plugin_mysql['config']['account']
);

// 签名
$plugin['config'][] = array(
        "field" => 'sign',
        "name" => '签名(sign)',
        "desc" => '【签名】',
        "value" => $plugin_mysql['config']['sign']
);

// 安全检验码，以数字和字母组成的32位字符
$plugin['config'][] = array(
        "field" => 'apikey',
        "name" => '安全校验码(Key)',
        "desc" => '安全检验码，以数字和字母组成的32位字符',
        "value" => $plugin_mysql['config']['apikey']
);

// 重发间隔，纯数字
$plugin['config'][] = array(
        "field" => 'retry_times',
        "name" => '重发间隔',
        "desc" => '短信发送失败时，重新发送的时间间隔。',
        "value" => $plugin_mysql['config']['retry_times']
);
?>