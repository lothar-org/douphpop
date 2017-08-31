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
if (!defined('IN_LOTHAR')) {
    die('Hacking attempt');
}
class Order {
    /**
     * +----------------------------------------------------------
     * 用户权限判断
     * +----------------------------------------------------------
     * $session_cart session储存的商品信息
     * +----------------------------------------------------------
     */
    function get_cart($session_cart) {
        if (is_array($session_cart)) {
            // 获取购物车商品信息
            foreach ($session_cart as $product_id=>$number) {
                $query = $GLOBALS['dou']->select($GLOBALS['dou']->table('product'), '*', "id = '" . $product_id . '\'');
                $product = $GLOBALS['dou']->fetch_array($query);
                $price = $product['price'] > 0 ? $GLOBALS['dou']->price_format($product['price']) : $GLOBALS['_LANG']['price_discuss'];
                $url = $GLOBALS['dou']->rewrite_url('product', $product['id']);
                $image = explode(".", $product['image']);
                $thumb = ROOT_URL . $image[0] . "_thumb." . $image[1];
                $subtotal = $product['price'] > 0 ? $GLOBALS['dou']->price_format($product['price'] * $number) : $GLOBALS['_LANG']['price_discuss'];
                
                // 商品列表
                $cart['list'][] = array(
                        "id" => $product['id'],
                        "name" => $product['name'],
                        "price_normal" => $product['price'],
                        "price" => $price,
                        "url" => $url,
                        "thumb" => $thumb,
                        "defined" => $product['defined'],
                        "subtotal" => $subtotal,
                        "number" => $number
                );
                
                // 商品总数量
                $cart['total'] += $number;
                
                // 商品总金额
                $cart['product_amount'] += ($product['price'] * $number);
                $cart['product_amount_format'] = $GLOBALS['dou']->price_format($cart['product_amount']);
            }
            
            return $cart;
        }
    }
    
    /**
     * +----------------------------------------------------------
     * 生成唯一的订单编号
     * +----------------------------------------------------------
     */
    function create_order_sn() {
        // 随机生成订单号
        $order_sn = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        if ($GLOBALS['dou']->get_one("SELECT id FROM " . $GLOBALS['dou']->table('order') . " WHERE order_sn = '$order_sn'")) {
            $this->create_order_sn();
        }

        return $order_sn;
    }

    /**
     * +----------------------------------------------------------
     * 格式化支付和配送方式
     * +----------------------------------------------------------
     */
    function payship_format($data) {
        if ($data) {
            foreach (explode("\r\n", $data) as $value) {
                $arr = explode('/', $value);
                $item['name'] = $arr['0'];
                $item['id'] = $arr['1'];
                $array[] = $item;
            }
        }
        
        return $array;
    }

    /**
     * +----------------------------------------------------------
     * 获取订单商品
     * +----------------------------------------------------------
     * $order_id 订单编号
     * +----------------------------------------------------------
     */
    function get_order_product($order_id) {
        /* 获取产品列表 */
        $query = $GLOBALS['dou']->query("SELECT product_id, name, price, product_number, defined FROM " . $GLOBALS['dou']->table('order_product') . " WHERE order_id = '$order_id' ORDER BY id DESC");
    
        while ($row = $GLOBALS['dou']->fetch_array($query)) {
            // 格式化价格
            $price = $GLOBALS['dou']->price_format($row['price']);
            $image = $GLOBALS['dou']->get_one("SELECT image FROM " . $GLOBALS['dou']->table('product') . " WHERE id = '$row[product_id]'");
            $image = explode(".", $image);
            $thumb = ROOT_URL . $image[0] . "_thumb." . $image[1];
            $url = $GLOBALS['dou']->rewrite_url('product', $row['product_id']);
            
            $product_list[] = array(
                    "product_id" => $row['product_id'],
                    "name" => $row['name'],
                    "thumb" => $thumb,
                    "url" => $url,
                    "product_number" => $row['product_number'],
                    "name" => $row['name'],
                    "price" => $price,
                    "defined" => $defined
            );
        }
        
        return $product_list;
    }
    
    /**
     * +----------------------------------------------------------
     * 改变订单状态
     * +----------------------------------------------------------
     * $order_sn 订单编号
     * $status 由数字表示的订单状态
     * +----------------------------------------------------------
     */
    function change_status($order_sn, $status) {
        // 取消所选订单
        $GLOBALS['dou']->query("UPDATE " . $GLOBALS['dou']->table('order') . " SET status = '$status' WHERE order_sn = '$order_sn'");
    }
    
    /**
     * +----------------------------------------------------------
     * 批量取消订单
     * +----------------------------------------------------------
     */
    function cancel_all($checkbox) {
        $sql_in = $GLOBALS['dou']->create_sql_in($_POST['checkbox']);
        
        // 取消所选订单
        $GLOBALS['dou']->query("UPDATE " . $GLOBALS['dou']->table('order') . " SET status = '-1' WHERE order_id " . $sql_in);
        
        $GLOBALS['dou']->create_admin_log($GLOBALS['_LANG']['order_cancel'] . ': ' . strtoupper('order') . ' ' . addslashes($sql_in));
        $GLOBALS['dou']->dou_msg($GLOBALS['_LANG']['order_cancel_success'], 'order.php');
    }
    
    /**
     * +----------------------------------------------------------
     * 获取支付
     * +----------------------------------------------------------
     */
    function get_payment_list() {
        /* 获取产品列表 */
        $query = $GLOBALS['dou']->query("SELECT * FROM " . $GLOBALS['dou']->table('plugin') . " WHERE plugin_group = 'payment'");
    
        while ($row = $GLOBALS['dou']->fetch_array($query)) {
            $image = ROOT_URL . 'include/plugin/' . $row['unique_id'] . '/icon.gif';
            $payment_list[] = array(
                    "id" => $row['unique_id'],
                    "name" => $row['name'],
                    "image" => $image
            );
        }
        
        return $payment_list;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取支付或配送方式
     * +----------------------------------------------------------
     */
    function get_shipping_list() {
        /* 获取产品列表 */
        $query = $GLOBALS['dou']->query("SELECT * FROM " . $GLOBALS['dou']->table('plugin') . " WHERE plugin_group = 'shipping'");
    
        while ($row = $GLOBALS['dou']->fetch_array($query)) {
            $config = unserialize($row['config']);
            $fee_format = $config['fee'] ? $GLOBALS['dou']->price_format($config['fee']) : $GLOBALS['_LANG']['order_shipping_free'];
            $free_format = preg_replace('/d%/Ums', $GLOBALS['dou']->price_format($config['free']), $GLOBALS['_LANG']['order_shipping_free_cue']);
            $shipping_list[] = array(
                    "unique_id" => $row['unique_id'],
                    "name" => $row['name'],
                    "description" => $row['other'],
                    "fee" => $config['fee'],
                    "fee_format" => $fee_format,
                    "free" => $config['free'],
                    "free_format" => $free_format
            );
        }
        
        return $shipping_list;
    }
    

}
?>