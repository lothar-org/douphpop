<?php
if (!defined('IN_LOTHAR')) die('Hacking attempt');

/* 插件唯一ID
----------------------------------------------------------------------------- */
$plugin['unique_id'] = 'wxpaynative';

/* 插件名称
----------------------------------------------------------------------------- */
$plugin['name'] = $plugin_mysql['name'] ? $plugin_mysql['name'] : '微信扫码支付';

/* 插件描述
----------------------------------------------------------------------------- */
$plugin['description'] = $plugin_mysql['description'] ? $plugin_mysql['description'] : '网上交易时，买家的交易资金直接打入卖家微信账户，快速回笼交易资金。申请前必须拥有服务号的微信账号。';

/* 插件版本
----------------------------------------------------------------------------- */
$plugin['ver'] = '1.0';

/* 所属组
----------------------------------------------------------------------------- */
$plugin['plugin_group'] = 'payment';

/* 插件参数提交表单
 * type默认为'text'及文本框，可选"text,select,textarea"
 * option默认为空，select默认选项，如array("选项一" => 0,"选项二" => 1)
----------------------------------------------------------------------------- */
// 收款微信账号
$plugin['config'][] = array(
        "field" => 'account',
        "name" => '微信帐户',
        "value" => $plugin_mysql['config']['account']
);

// 绑定支付的APPID（必须配置，开户邮件中可查看）
$plugin['config'][] = array(
        "field" => 'appid',
        "name"  => '安全校验码(APPID)',
        "desc"  => 'APPID：以数字和字母组成的18位字符',
        "value" => $plugin_mysql['config']['appid']
);
// 公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置）
$plugin['config'][] = array(
        "field" => 'appsecret',
        "name"  => '安全校验码(APPSECRET)',
        "desc"  => 'APPSECRET：公众帐号secert,32位字符串（仅JSAPI支付的时候需要配置，登录公众平台，进入开发者中心可设置）。获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN',
        "value" => $plugin_mysql['config']['appsecret']
);

// MCHID：商户号（必须配置，开户邮件中可查看）
$plugin['config'][] = array(
        "field" => 'mchid',
        "name"  => '商户号(MCHID)',
        "desc"  => 'MCHID：商户号（必须配置，开户邮件中可查看）',
        "value" => $plugin_mysql['config']['mchid']
);
// 安全检验码，以数字和字母组成的32位字符
$plugin['config'][] = array(
        "field" => 'key',
        "name"  => '商户支付密钥(KEY)',
        "desc"  => 'KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）。设置地址：https://pay.weixin.qq.com/index.php/account/api_cert',
        "value" => $plugin_mysql['config']['key']
);
?>