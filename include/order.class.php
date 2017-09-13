<?php
if (!defined('IN_LOTHAR')) die('Hacking attempt');

class Order {
    /**
    * 购物车 信息
    * 检测数据库中是否有值
    */
    public function user_cart($uid='all')
    {
        if (empty($_SESSION[DOU_ID]['cart'])) {
            if ($uid=='all') {
                $us = $GLOBALS['dou']->fetchAll(sprintf('SELECT a.id,a.pro_ids,a.num_ids,a.uid,a.status,a.addtime,b.nickname from %s a join %s b on a.uid=b.user_id',$GLOBALS['dou']->table('cart'),$GLOBALS['dou']->table('user')));
                foreach ((array)$us as $key => $value) {
                    $pro_ids = explode(',', $value['pro_ids']);
                    $num_ids = explode(',', $value['num_ids']);
                    foreach ($pro_ids as $k => $v) {
                        $user_cart[$v] = $num_ids[$k];
                    }
                    $usa_c = $this->get_cart($user_cart);//只能循环从数据库获取了，子查询似乎不好使
                    $usa[] = array(
                        'id'            => $value['id'],
                        'uid'           => $value['uid'],
                        'nickname'      => $value['nickname'],
                        'status'        => $value['status'],
                        'addtime'       => $value['addtime'],
                        'url'           => ROOT_URL .'user.php?rec=login&fuid='.$value['uid'],
                        'total'         => $usa_c['total'],
                        'product_amount'=> $usa_c['product_amount'],
                    );
                }
                return $usa;
            } else {
                $user_cart_c = $GLOBALS['dou']->fetchRow('SELECT pro_ids,num_ids from '. $GLOBALS['dou']->table('cart') .' where uid='.$uid);
                if (empty($user_cart_c)) {
                    return array();
                }
                $pro_ids = explode(',', $user_cart_c['pro_ids']);
                $num_ids = explode(',', $user_cart_c['num_ids']);
                foreach ($pro_ids as $k => $v) {
                    $user_cart[$v] = $num_ids[$k];
                }
                $_SESSION[DOU_ID]['cart'] = $user_cart;
            }
        } else {
            $user_cart = $_SESSION[DOU_ID]['cart'];
        }
        return $this->get_cart($user_cart);
    }

    /**
     * +----------------------------------------------------------
     * 用户权限判断
     * +----------------------------------------------------------
     * $session_cart session储存的产品信息  $_SESSION[DOU_ID]['cart']
     * key value都是未知的，所以不能直接获取key
     * $shell 选择需要的
     * +----------------------------------------------------------
     */
    function get_cart($session_cart,$shell='') {
        if (empty($session_cart)) 
            return array();
        // 获取产品ID组 和 产品信息
        // if (is_array($shell)) {
        //     foreach ($shell as $v) {
        //         unset($session_cart[$v]);
        //     }
        // }
        // $pro_ids = array_keys($session_cart);
        // $pro_ids = array_diff($pro_ids,(array)$shell);// 差集
        $pro_ids = is_array($shell) ? $shell : array_keys($session_cart);
        // 开始查库
        // $fields = $GLOBALS['dou']->create_fields_quote($fields,'a');
        $sql = sprintf('SELECT id,name,price,image,defined from %s where id ',$GLOBALS['dou']->table('product'));
        if (count($session_cart)>1) {
            sort($pro_ids);
            $pro_ids = join(',',$pro_ids);
            // $pro_ids = implode(',',$pro_ids);
            // $pro_ids = strrev($pro_ids);
            $products = $GLOBALS['dou']->fetchAll($sql ."IN ({$pro_ids})");
        } else {
            // 硬凑一个二维数组
            $pro_ids = intval($pro_ids[0]);
            $products[0] = $GLOBALS['dou']->fetchRow($sql .'='. $pro_ids);
        }

        // return $pro_ids;
        // return $products;

        // 获取购物车产品信息
        if (is_array($products)) {
            foreach ($products as $pro) {
                $pro['number'] = $session_cart[$pro['id']];
                $pro['price_normal'] = $pro['price'];
                $pro['price'] = $pro['price']>0 ? $GLOBALS['dou']->price_format($pro['price']) : $GLOBALS['_LANG']['price_discuss'];
                $pro['url'] = $GLOBALS['dou']->rewrite_url('product', $pro['id']);
                $image = explode('.', $pro['image']);
                $pro['image'] = ROOT_URL . $pro['image'];
                $pro['thumb'] = ROOT_URL . $image[0] . "_thumb." . $image[1];
                $pro['subtotal'] = $pro['price_normal'] > 0 ? $GLOBALS['dou']->price_format($pro['price_normal'] * $pro['number']) : $GLOBALS['_LANG']['price_discuss'];
                // 产品列表
                $cart['list'][] = $pro;

                // 产品总数量
                $cart['total'] += $pro['number'];
                // 产品总金额
                $cart['product_amount'] += ($pro['price_normal'] * $pro['number']);
                $cart['product_amount_format'] = $GLOBALS['dou']->price_format($cart['product_amount']);
            }
            return $cart;
        }
        return array();
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
        $query = $GLOBALS['dou']->query("SELECT a.product_id,a.name,a.price,a.product_number,a.defined,b.image FROM " . $GLOBALS['dou']->table('order_product') . " as a LEFT JOIN ". $GLOBALS['dou']->table('product') ." as b ON a.product_id=b.id WHERE a.order_id='$order_id' ORDER BY a.id DESC");
        while ($row = $GLOBALS['dou']->fetch_assoc($query)) {
            // 格式化价格
            $row['price'] = $GLOBALS['dou']->price_format($row['price']);
            $image = explode(".", $row['image']);
            $row['thumb'] = ROOT_URL . $image[0] . "_thumb." . $image[1];
            $row['url'] = $GLOBALS['dou']->rewrite_url('product', $row['product_id']);
            
            $product_list[] = $row;
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