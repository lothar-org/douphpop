<?php
define('IN_LOTHAR', true);
define('CMOD', 'cart');
require (dirname(__FILE__) . '/include/init.php');

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

// 载入并实例化类库
require (ROOT_PATH . 'include/order.class.php');
$dou_order = new Order();

// 赋值给模板
$smarty->assign('rec', $rec);
$smarty->assign('cur', CMOD);

/**
 * +----------------------------------------------------------
 * 订单列表
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    $smarty->assign('ur_here', $_LANG['cart']);

    /*// 生成筛选条件
    $sifter = array('id', 'uid');
    foreach ($sifter as $value) {
        $v['value'] = $value;
        $v['name'] = $_LANG['cart_' . $value];
        $v['cur'] = ($value == $_REQUEST['key']) ? true : false;
        $key[] = $v;
    }
    // 筛选关键词
    $srcval = isset($_REQUEST['srcval']) ? trim($_REQUEST['srcval']) : '';
    if ($srcval)
        $where = " WHERE $_REQUEST[srckey] LIKE '%$srcval%'";
    // 分页
    $page = $check->is_number($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    $page_url = 'cart.php' . ($_REQUEST['key'] ? '?key=' . $_REQUEST['key'] : '') . ($srcval ? '&srcval=' . $srcval : '');
    $limit = $dou->pager('cart', 15, $page, $page_url, $where);
    $query = $dou->query("SELECT * FROM " . $dou->table('cart') . $where . " ORDER BY cart_id DESC" . $limit);
    while ($row = $dou->fetch_array($query)) {
        $row['email'] = $dou->get_one("SELECT email FROM " . $dou->table('user') . " WHERE user_id = '$row[user_id]'");
        $row['add_time'] = date("Y-m-d h:i:s", $row['add_time']);
        $row['status_format'] = $_LANG['cart_status_' . $row['status']];
        $row['cart_amount'] = $dou->price_format($row['cart_amount']);

        $cart_list[] = $row;
    }
    // 赋值给模板
    $smarty->assign('key', $key);
    $smarty->assign('srcval', $srcval);
    $smarty->assign('cart_l ist', $cart_list);*/

    $cart_list_c = $dou_order->user_cart('1=1');
    foreach ((array)$cart_list_c as $v) {
        $v['status_format'] = $_LANG['cart_status_' . $v['status']];
        $cart_list[] = $v;
    }
    // $dou->debug($cart_list,1);

    // 判断是否安装会员导出功能
    if (file_exists(ROOT_PATH . ADMIN_PATH . '/include/phpexcel/excel.class.php'))
        $smarty->assign('excel', true);

    // 赋值给模板
    $smarty->assign('cart_list',$cart_list);

    $smarty->display('cart.htm');
}

/**
 * +----------------------------------------------------------
 * 订单编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'view') {
    $smarty->assign('ur_here', $_LANG['cart_view']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['cart_list'],
            'href' => 'cart.php'
    ));

    // 验证并获取合法的ID
    $cart_id = $check->is_number($_REQUEST['cart_id']) ? $_REQUEST['cart_id'] : '';

    // 获取配送方式
    $shipping = $dou->get_plugin('shipping');

    $query = $dou->select($dou->table('cart'), '*', '`cart_id`=\''. $cart_id .'\'');
    $cart = $dou->fetch_array($query);
    $cart['pay_name'] = $dou->get_one("SELECT name FROM " . $dou->table('plugin') . " WHERE unique_id = '$cart[pay_id]'");
    $cart['shipping_name'] = $dou->get_one("SELECT name FROM " . $dou->table('plugin') . " WHERE unique_id = '$cart[shipping_id]'");
    $cart['product_amount_format'] = $dou->price_format($cart['product_amount']);
    $cart['shipping_fee_format'] = $dou->price_format($cart['shipping_fee']);
    $cart['cart_amount_format'] = $dou->price_format($cart['cart_amount']);

    /* 获取产品列表 */
    $query = $dou->query("SELECT product_id, name, price, product_number, defined FROM " . $dou->table('cart_product') . " WHERE cart_id = '$cart_id' ORDER BY id DESC");

    while ($row = $dou->fetch_array($query)) {
        // 格式化价格
        $price = $dou->price_format($row['price']);

        $product_list[] = array(
                "product_id" => $row['product_id'],
                "name" => $row['name'],
                "product_number" => $row['product_number'],
                "url" => $dou->rewrite_url('product', $row['product_id']),
                "price" => $price,
                "defined" => $defined
        );
    }

    // 格式化订单信息
    $cart['email'] = $dou->get_one("SELECT email FROM " . $dou->table('user') . " WHERE user_id = '$cart[user_id]'");
    $cart['add_time'] = date("Y-m-d h:i:s", $cart['add_time']);
    $cart['status_format'] = $_LANG['cart_status_' . $cart['status']];
    $cart['product_list'] = $product_list;

    // 赋值给模板
    $smarty->assign('cart', $cart);
    $smarty->assign('shipping_list', $dou_order->get_shipping_list());

    $smarty->display('cart.htm');
}

/**
 * +----------------------------------------------------------
 * 快递单号填写
 * +----------------------------------------------------------
 */
elseif ($rec == 'tracking') {
    // 插入订单号并在订单状态设定为已发货
    $dou->query("UPDATE " . $dou->table('cart') . " SET shipping_id = '$_POST[shipping_id]', tracking_no = '$_POST[tracking_no]', status = '10' WHERE cart_id = '$_POST[cart_id]'");

    $dou->dou_header('cart.php?rec=view&cart_id=' . $_POST['cart_id']);
}

/**
 * +----------------------------------------------------------
 * 订单删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    // 验证并获取合法的ID
    $order_id = $check->is_number($_REQUEST['order_id']) ? $_REQUEST['order_id'] : '';
    $order_sn = $dou->get_one("SELECT order_sn FROM " . $dou->table('order') . " WHERE order_id = '$order_id'");

    if (isset($_POST['confirm']) ? $_POST['confirm'] : '') {
        $dou->create_admin_log($_LANG['order_del'] . ': ' . $order_sn);
        $dou->delete($dou->table('order'), "order_id = $order_id", 'order.php');
    } else {
        $_LANG['del_check'] = preg_replace('/d%/Ums', $order_sn, $_LANG['del_check']);
        $dou->dou_msg($_LANG['del_check'], 'order.php', '', '30', "order.php?rec=del&order_id=$order_id");
    }
}

/**
 * +----------------------------------------------------------
 * 订单删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    // 验证并获取合法的ID
    $cart_id = $check->is_number($_REQUEST['cart_id']) ? $_REQUEST['cart_id'] : '';
    $pro_ids = $dou->get_one("SELECT pro_ids FROM " . $dou->table('cart') . " WHERE id={$cart_id}");

    if (isset($_POST['confirm']) ? $_POST['confirm'] : '') {
        $dou->create_admin_log($_LANG['cart_del'] . ': ' . $pro_ids);
        $dou->delete($dou->table('cart'), "id=$cart_id", 'cart.php');
    } else {
        $_LANG['del_check'] = preg_replace('/d%/Ums', $pro_ids, $_LANG['del_check']);
        $dou->dou_msg($_LANG['del_check'], 'cart.php', '', '30', "cart.php?rec=del&cart_id=$cart_id");
    }
}

/**
 * +----------------------------------------------------------
 * 批量操作选择
 * +----------------------------------------------------------
 */
elseif ($rec == 'action') {
    // 判断是否安装会员导出功能
    if (file_exists($excel_file = ROOT_PATH . ADMIN_PATH . '/include/PHPExcel/excel.class.php')) {
        include_once($excel_file);
        $excel = new Excel();
    }

    if (is_array($_POST['checkbox'])) {
        if ($_POST['action'] == 'del_all') { // 批量删除会员
            $dou->del_all('cart', $_POST['checkbox'], 'id');
        } elseif ($_POST['action'] == 'excel') { // 导出所选会员
            $data = excel_order_list($_POST['checkbox']);
            $excel->export_excel('cart', $data);
            exit;
        } else {
            $dou->dou_msg($_LANG['select_empty']);
        }
    } else {
        if ($_POST['action'] == 'excel_all') { // 导出所有
            $excel->export_excel('cart', excel_order_list());
            exit;
        }
    }
    $dou->dou_msg($_LANG['cart_select_empty']);
}

/**
 * +----------------------------------------------------------
 * 导出订单数据
 * +----------------------------------------------------------
 * $checkbox 所选的订单
 * $excel_list 列表 $excel_list['head']   $excel_list['list']
 * +----------------------------------------------------------
 */
function excel_order_list($checkbox = '') {
    require ROOT_PATH .'languages/zh_cn/cart.lang.php';

    // 需要导出的字段
    $field_pro = array('id','name','model','price','number');
    $field_user = array('uname','usex','utel','uemail','ucountry','uaddress','ucompany');
    // 导出的字段名称 头
    $field_head = array_merge($field_pro,$field_user);
    foreach ($field_head as $value) {
        $excel_list['head'][] = $_LANG['cart_' . $value];
    }

    // 导出的列表数据 体部
    $wh = $checkbox ? 'a.id in ('. implode(',', $_POST['checkbox']) .')' : '1=1';
    $cart_list_c = $GLOBALS['dou_order']->user_cart($wh);
    // $GLOBALS['dou']->debug($cart_list_c,1);
    foreach ($cart_list_c as $v1) {
        $list_user = array($v1['truename'],$v1['sex_format'],$v1['telephone'],$v1['email'],$v1['country'],$v1['address'],$v1['company']);
        foreach ($v1['list'] as $v2) {
            $list_pro = array();
            foreach ($field_pro as $v) {
                $list_pro[] = $v2[$v];
            }
            $excel_list['list'][] = array_merge($list_pro,$list_user);
        }
    }

    // $GLOBALS['dou']->debug($excel_list,1);
    return $excel_list;
}
?>