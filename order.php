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

$sub = 'cart|checkout|update|del|success';
$subbox = array(
        "module" => 'order',
        "sub" => $sub
);

require (dirname(__FILE__) . '/include/init.php');

// 引入和实例化订单功能
include_once (ROOT_PATH . 'include/order.class.php');
$dou_order = new Order();

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'cart';

// 赋值给模板-meta和title信息
$smarty->assign('keywords', $_CFG['site_keywords']);
$smarty->assign('description', $_CFG['site_description']);

// 赋值给模板-导航栏
$smarty->assign('nav_top_list', $dou->get_nav('top'));
$smarty->assign('nav_middle_list', $dou->get_nav('middle', 0, 'order', 0));
$smarty->assign('nav_bottom_list', $dou->get_nav('bottom'));

// 赋值给模板-数据
$smarty->assign('rec', $rec);

/**
 * +----------------------------------------------------------
 * 购物车商品列表
 * +----------------------------------------------------------
 */
if ($rec == 'cart') {
    // 赋值给模板-数据
    $smarty->assign('cart', $dou_order->get_cart($_SESSION[DOU_ID]['cart']));
    $smarty->assign('page_title', $dou->page_title('order_cart'));

    $smarty->display('order.dwt');
}

/**
 * +----------------------------------------------------------
 * 商品加入购物车
 * +----------------------------------------------------------
 */
elseif ($rec == 'insert') {
    $product_id = $check->is_number($_REQUEST['product_id']) ? $_REQUEST['product_id'] : '';
    $number = $check->is_number($_REQUEST['number']) ? $_REQUEST['number'] : 1;

    // 是否第一次使用购物车
    if(!isset($_SESSION[DOU_ID]['cart'])){
        $_SESSION[DOU_ID]['cart'] = array();
    }
    
    // 如果商品已存在则增加其数量
    if (isset($_SESSION[DOU_ID]['cart'][$product_id])) {
        $_SESSION[DOU_ID]['cart'][$product_id] = $number + $_SESSION[DOU_ID]['cart'][$product_id];
    } else {
        $_SESSION[DOU_ID]['cart'][$product_id] = $number;
    }

    $dou->dou_header($_URL['cart']);
}

/**
 * +----------------------------------------------------------
 * 更新购物车商品数量
 * +----------------------------------------------------------
 */
elseif ($rec == 'update') {
    $product_id = $check->is_number($_REQUEST['product_id']) ? $_REQUEST['product_id'] : '';

    $_SESSION[DOU_ID]['cart'][$product_id] = $_POST['number'];
    
    $cart = $dou_order->get_cart($_SESSION[DOU_ID]['cart']);
    $product_price = $dou->get_one("SELECT price FROM " . $GLOBALS['dou']->table('product') . " WHERE id = '$product_id'");
    $subtotal = $product_price > 0 ? $dou->price_format($product_price * $_POST['number']) : $_LANG['price_discuss'];
    
    $order = array(
            "subtotal" => $subtotal,
            "total" => $cart['total'],
            "product_amount" => $dou->price_format($cart['product_amount'])
    );
    
    echo json_encode($order);
}

/**
 * +----------------------------------------------------------
 * 删除商品
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    $product_id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : '';
    
    // 删除对应商品
    unset($_SESSION[DOU_ID]['cart'][$product_id]);

    $dou->dou_header($_URL['cart']);
}

/**
 * +----------------------------------------------------------
 * 结算页面
 * +----------------------------------------------------------
 */
elseif ($rec == 'checkout') {
    // 验证是否登录
    if (!is_array($_USER)) {
        $dou->dou_header($_URL['login']);
    }

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->set_token('order_checkout'));
    
    // 获取默认配送方式信息
    $shipping = $dou->get_one("SELECT config FROM " . $GLOBALS['dou']->table('plugin') . " WHERE plugin_group = 'shipping'");
    $shipping = unserialize($shipping);
    
    // 获取购物车信息
    $cart = $dou_order->get_cart($_SESSION[DOU_ID]['cart']);
    
    // 免费额度
    if ($shipping['free'] && $cart['product_amount'] >= $shipping['free']) $shipping['fee'] = 0;
    
    // 获取订单信息
    $order = $dou_user->get_user_info($_USER['user_id']);
    $order['shipping_fee_format'] = $dou->price_format($shipping['fee']);
    $order['order_amount_format'] = $dou->price_format($cart['product_amount'] + $shipping['fee']);

    // 赋值给模板-数据
    $smarty->assign('page_title', $dou->page_title('order_checkout'));
    $smarty->assign('cart', $cart);
    $smarty->assign('shipping_list', $dou_order->get_shipping_list());
    $smarty->assign('payment_list', $dou_order->get_payment_list());
    $smarty->assign('order', $order);
    $smarty->display('order.dwt');
}

/**
 * +----------------------------------------------------------
 * 更改运费和订单总额
 * +----------------------------------------------------------
 */
elseif ($rec == 'change_shipping') {
    // 获取默认配送方式信息
    $shipping = $dou->get_one("SELECT config FROM " . $GLOBALS['dou']->table('plugin') . " WHERE unique_id = '$_REQUEST[unique_id]'");
    $shipping = unserialize($shipping);
    
    // 获取购物车信息
    $cart = $dou_order->get_cart($_SESSION[DOU_ID]['cart']);
    
    // 免费额度
    if ($shipping['free'] && $cart['product_amount'] >= $shipping['free']) $shipping['fee'] = 0;

    $order = array(
            "shipping_fee" => $dou->price_format($shipping['fee']),
            "order_amount" => $dou->price_format($cart['product_amount'] + $shipping['fee'])
    );
    
    echo json_encode($order);
}

/**
 * +----------------------------------------------------------
 * 完成订单操作，提交到数据库
 * +----------------------------------------------------------
 */
elseif ($rec == 'success') {
    // 验证手机
    if (!$check->is_telephone($_POST['telephone']))
        $wrong['telephone'] = $_LANG['user_telephone_cue'];

    // 验证联系人
    if (!$_POST['contact']) {
        $wrong['contact'] = $_LANG['user_contact_empty'];
    } elseif ($check->is_illegal_char($_POST['contact'])) {
        $wrong['contact'] = $_LANG['user_contact'] . $_LANG['illegal_char'];
    }

    // 验证地址
    if (!$_POST['address']) {
        $wrong['address'] = $_LANG['user_address_empty'];
    } elseif ($check->is_illegal_char($_POST['address'])) {
        $wrong['address'] = $_LANG['user_address'] . $_LANG['illegal_char'];
    }

    // 验证邮政编码
    if (isset($_POST['postcode']) && !$check->is_postcode($_POST['postcode']))
        $wrong['postcode'] = $_LANG['user_postcode_cue'];
    
    // AJAX验证表单
    if ($_REQUEST['do'] == 'callback') {
        if ($wrong) {
            foreach ($_POST as $key => $value) {
                $wrong_json[$key] = $wrong[$key];
            }
            echo json_encode($wrong_json);
        }
        exit;
    }

    if ($wrong) {
        foreach ($wrong as $key => $value) {
            $wrong_format[$key] = '<p class="cue">' . $value . '</p>';
        }
        $dou->dou_msg($wrong_format, $_URL['edit']);
    }

    // 验证是否登录
    if (!is_array($_USER)) {
        $dou->dou_header($_URL['login']);
    }

    // CSRF防御令牌验证
    $firewall->check_token($_POST['token'], 'order_checkout');
    
    // 检查购物车是否有商品
    if (!is_array($_SESSION[DOU_ID]['cart'])) {
        $dou->dou_msg($_LANG['order_cart_empty'], ROOT_URL);
    }

    // 获取和格式化数据
    $cart = $dou_order->get_cart($_SESSION[DOU_ID]['cart']);
    $order_sn = $dou_order->create_order_sn();
    $add_time = time();
    
    // 配送方式信息
    $shipping = $dou->get_plugin($_POST['shipping_id']);
    $shipping_fee = $shipping['config']['fee'];

    // 免费额度
    if ($shipping['config']['free'] && $cart['product_amount'] >= $shipping['config']['free']) $shipping_fee = 0;

    // 计算运费和订单总额
    $order_amount = $cart['product_amount'] + $shipping_fee;
    
    // 安全处理用户输入信息
    $_POST = $firewall->dou_foreground($_POST);

    // 订单信息插入
    $dou->query("INSERT INTO " . $dou->table('order') . " (order_id, order_sn, user_id, telephone, contact, address, postcode, pay_id, shipping_id, product_amount, shipping_fee, order_amount, add_time)" . " VALUES (NULL, '$order_sn', '$_USER[user_id]', '$_POST[telephone]', '$_POST[contact]', '$_POST[address]', '$_POST[postcode]', '$_POST[pay_id]', '$_POST[shipping_id]', '$cart[product_amount]', '$shipping_fee', '$order_amount', '$add_time')");

    // 订单商品插入
    $order_id = mysql_insert_id();
    foreach ($cart['list'] as $product) {
        $dou->query("INSERT INTO " . $dou->table('order_product') . " (id, order_id, product_id, name, price, product_number, defined)" . " VALUES (NULL, '$order_id', '$product[id]', '$product[name]', '$product[price_normal]', '$product[number]', '$product[defined]')");
    }

    if ($_POST['update_user_information']) {
        $dou->query("UPDATE " . $dou->table('user') . " SET telephone = '$_POST[telephone]', contact = '$_POST[contact]', address = '$_POST[address]', postcode = '$_POST[postcode]' WHERE user_id = '$_USER[user_id]'");
    }
    
    // 显示订单信息
    $order['order_sn'] = $order_sn;
    $order['order_amount'] = $order_amount;
    $order['order_amount_format'] = $dou->price_format($order_amount);

    // 订单完成，清空购物车
    unset($_SESSION[DOU_ID]['cart']);

    // 订单成功且选择了付款方式则显示付款按钮
    if ($GLOBALS['dou']->value_exist('order', 'order_sn', $order_sn) && $_POST['pay_id']) {
        include_once (ROOT_PATH . 'include/plugin/' . $_POST['pay_id'] . '/work.plugin.php');
        $plugin = new Plugin($order_sn, $order_amount);
        
        // 生成支付按钮
        $smarty->assign('payment', $plugin->work());
    }
    
    $smarty->assign('page_title', $dou->page_title('order_success'));
    $smarty->assign('order', $order);
    
    $smarty->display('order.dwt');
}
?>