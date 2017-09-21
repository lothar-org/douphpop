<?php
if (!defined('IN_LOTHAR')) {
    die('Hacking attempt');
}
class Common extends DbMysql {
    /*
    * 选定字段
    */
    public function get_dao_fields($cat_id='0', $table='product')
    {
        $dkey = 'indusid,proid,account_type,fans,moneys,trans,id_number,reads,issue_plat,groups,channel,genre,belong_plat,average_plays,nnt,type,put_site,ad_type,pub_type,brief';
        $dkey = explode(',', $dkey);
        $dexplain = '行业,地区,账号类型,粉丝量,价格,转发量,ID号,阅读量,发布平台,受众群体,发布频道,媒体类型,所属平台,平均播放量,人数量,类型,投放位置,广告形式,发布类型,简介';
        $dexplain = explode(',', $dexplain);
        // 字段对应解释
        foreach ($dkey as $key => $value) {
            $designate[$value] = $dexplain[$key];
        }
        // 分类选定字段
        $fields = '';
        if ($cat_id) {
            $fields = $GLOBALS['dou']->get_one('SELECT fields from '.$GLOBALS['dou']->table($table .'_category').' where cat_id='.$cat_id);
        }
        $fieldsarr = explode(',', $fields);

        $GLOBALS['smarty']->assign('designate', $designate);
        $GLOBALS['smarty']->assign('fieldsarr', $fieldsarr);

        return $fields;
    }

    public function get_dao_series($select='')
    {
        // 所有行业
        $industrys = $GLOBALS['dou']->fetchAll(sprintf('SELECT id,title from %s where cat_id=1 order by sort',$GLOBALS['dou']->table('diy')));
        // 所有省份
        $provinces = $GLOBALS['dou']->fetchAll(sprintf('SELECT cat_id,cat_name from %s order by sort',$GLOBALS['dou']->table('district')));
        // 所有账号类型
        $account_types = $GLOBALS['dou']->fetchAll(sprintf('SELECT id,title from %s where cat_id=2',$GLOBALS['dou']->table('diy')));
        $GLOBALS['smarty']->assign('industrys', $industrys);
        $GLOBALS['smarty']->assign('provinces', $provinces);
        $GLOBALS['smarty']->assign('account_types', $account_types);
    }

    /**
     * +----------------------------------------------------------
     * 获取导航菜单
     * +----------------------------------------------------------
     * $type 导航类型
     * $parent_id 默认获取一级导航
     * $current_module 当前页面模型名称，用于高亮导航栏
     * $current_id 当前页面栏目ID
     * +----------------------------------------------------------
     */
    function get_nav($type = 'middle', $parent_id = 0, $current_module = '', $current_id = '', $current_parent_id = '') {
        $nav = array();
        $data = $this->fetch_array_all($this->table('nav'), 'sort ASC');
        foreach ((array) $data as $value) {
            // 根据$parent_id和$type筛选父级导航
            if ($value['parent_id'] == $parent_id && $value['type'] == $type) {
                // 如果是自定义链接则$value['guide']值链接地址，如果是内部导航则值是栏目ID
                if ($value['module'] == 'nav') {
                    if (strpos($value['guide'], 'http://') === 0 || strpos($value['guide'], 'https://') === 0) {
                        $value['url'] = $value['guide'];
                        // 自定义导航如果包含http则在新窗口打开
                        $value['target'] = true;
                    } else {
                        $value['url'] = ROOT_URL . $value['guide'];
                        // 系统会比对自定义链接是否包含在当前URL里，如果包含则高亮菜单，如果不需要此功能，请注释掉下面那行代码
                        $value['cur'] = strpos($_SERVER['REQUEST_URI'], $value['guide']);
                    }
                } else {
                    $value['url'] = $this->rewrite_url($value['module'], $value['guide']);
                    $value['cur'] = $this->dou_current($value['module'], $value['guide'], $current_module, $current_id, $current_parent_id);
                }
                
                foreach ($data as $child) {
                    // 筛选下级导航
                    if ($child['parent_id'] == $value['id']) {
                        $value['child'] = $this->get_nav($type, $value['id']);
                        break;
                    }
                }
                $nav[] = $value;
            }
        }
        return $nav;
    }
    
    /**
     * +----------------------------------------------------------
     * 高亮当前菜单
     * +----------------------------------------------------------
     * $module 模块名称
     * $id 当前要判断的ID
     * $current_module 当前模块名称，例如在获取导航菜单时就会涉及到不同的模块
     * $current_id 当前的ID
     * +----------------------------------------------------------
     */
    function dou_current($module, $id, $current_module, $current_id = '', $current_parent_id = '') {
        if (($id == $current_id || $id == $current_parent_id) && $module == $current_module) {
            return true;
        } elseif (!$id && $module == $current_module) {
            return true;
        }
    }

    /**
     * +----------------------------------------------------------
     * 获取网站信息
     * +----------------------------------------------------------
     */
    function get_config() {
        $query = $this->select_all($this->table('config'));
        while ($row = $this->fetch_array($query)) {
            $config[$row['name']] = $row['value'];
        }
        if ($config['qq'] && !defined('IS_ADMIN')) {
            $config['qq'] = $this->dou_qq($config['qq']);
        }
        $config['root_url'] = ROOT_URL;
        $config['m_url'] = M_URL;
        return $config;
    }
    
    /**
     * +----------------------------------------------------------
     * 重写 URL 地址
     * +----------------------------------------------------------
     * $module 模块
     * $value 根据是数字或字符来判断传递的是ID还是参数
     * +----------------------------------------------------------
     */
    function rewrite_url($module, $value='', $type='') {
        if (is_numeric($value)) {
            $id = $value; // 详细页和分类页会传的id和分类cat_id
        } else {
            $rec = $value; // 单模块会传递操作项值
        }
        if ($GLOBALS['_CFG']['rewrite']) {
            $filename = $module!='page' ? '/'. $id : '';
            $item = (!strpos($module, 'category') && $id) ? $filename .'.html' : '';
            $url = $this->get_unique($module, $id) . $item . ($rec ? '/'. $rec : '');
        } else {
            $req = $rec ? '?rec='.$rec : ($id?'?id='.$id:'');
            $url = $module .'.php' . $req;
        }

        if ($module == 'mobile') {
            // return ROOT_URL . M_PATH; // 手机版链接
            return ROOT_HOST .'/'. M_PATH; // 手机版链接
        } else {
            return ((defined('IS_MOBILE') || $type=='mobile') ? M_URL : ROOT_HOST .'/') . $url; // 移动版和PC版的根网址不同
        }
    }
    
    /**
     * +----------------------------------------------------------
     * 系统模块
     * +----------------------------------------------------------
     */
    function dou_module() {
        // 读取系统文件
        $setting = $this->read_system();
        $module['column'] = array_filter($setting['column_module']);
        $module['single'] = array_filter($setting['single_module']);
        $module['all'] = array_merge($module['column'], $module['single']); 
        
        // 读取模块语言文件
        $lang_path = ROOT_PATH . 'languages/' . (defined('IS_ADMIN') ? 'zh_cn/admin/' : $GLOBALS['_CFG']['language'] . '/');
        $lang_list = glob($lang_path . '*.lang.php');
        if (is_array($lang_list)) {
            foreach ($lang_list as $lang) {
                $module['lang'][] = $lang;
            }
        }
        // 读取模块初始化文件
        $init_list = glob(ROOT_PATH . 'include/' . '*.init.php');
        if (is_array($init_list)) {
            foreach ($init_list as $init) {
                $module['init'][] = $init;
            }
        }
        // 模块开启状态
        foreach ((array) $module['all'] as $module_id) {
            $_OPEN[$module_id] = true;
        }
        $module['open'] = $_OPEN;

        return $module;
    }
     
    /**
     * +----------------------------------------------------------
     * 将系统文件转换为数组
     * +----------------------------------------------------------
     */
    function read_system() {
        $content = file(ROOT_PATH . 'data/system.dou');
        foreach ((array) $content as $line) {
            $line = trim($line);
            if (strpos($line, '//') !== 0) {
                $arr = explode(':', $line);
                $setting[$arr[0]] = explode(',', $arr[1]);
            }
        }
        
        return $setting;
    }
    
    /**
     * +----------------------------------------------------------
     * 所有模块URL和当前模块URL生成
     * +----------------------------------------------------------
     */
    function dou_url() {
        // 所有模块URL
        $module = $this->dou_module();
        foreach ((array) $module['column'] as $module_id) {
            $url[$module_id] = $this->rewrite_url($module_id . '_category');
        }
        foreach ((array) $module['single'] as $module_id) {
            $url[$module_id] = $this->rewrite_url($module_id);
        }

        // 购物车URL
        $url['cart'] = $this->rewrite_url('order', 'cart');

        // 会员模块常用URL
        foreach (explode('|', 'login|register|logout|order|order_list') as $value)
            $url[$value] = $this->rewrite_url('user', $value);

        // 当前模块子栏目URL
        if ($GLOBALS['subbox']['sub']) { // 判断模块页面的column值
            foreach (explode('|', $GLOBALS['subbox']['sub']) as $value) {
                $url[$value] = $this->rewrite_url($GLOBALS['subbox']['module'], $value);
            }
        }
        return $url;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取别名
     * +----------------------------------------------------------
     * $module 模块
     * $id 项目ID
     * +----------------------------------------------------------
     */
    function get_unique($module, $id) {
        $filed = $module == 'page' ? id : cat_id;
        $table_module = $module;
        
        // 非单页面和分类模型下获取分类ID
        if (!strpos($module, 'category') && $module != 'page') {
            $id = $this->get_one("SELECT cat_id FROM " . $this->table($module) . " WHERE id = " . $id);
            $table_module = $module . '_category';
        }
        
        $unique_id = $this->get_one("SELECT unique_id FROM " . $this->table($table_module) . " WHERE " . $filed . " = " . $id);
        
        // 把分类页和列表的别名统一
        $module = preg_replace("/\_category/", '', $module);
        
        // 伪静态时使用的完整别名
        if ($module == 'page') {
            $unique = $unique_id;
        } elseif ($module == 'article') {
            $unique = $unique_id ? '/' . $unique_id : $unique_id;
            $unique = 'news' . $unique;
        } else {
            $unique = $unique_id ? '/' . $unique_id : $unique_id;
            $unique = $module . $unique;
        }
        
        return $unique;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取幻灯图片列表
     * +----------------------------------------------------------
     */
    function get_show_list($type = 'pc') {
        if ($type) {
            $where = " WHERE type = '$type'";
        }
        
        $sql = "SELECT * FROM " . $this->table('show') . $where . " ORDER BY type,sort,id ASC";
        $query = $this->query($sql);
        while ($row = $this->fetch_array($query)) {
            $image = explode('.', basename($row['show_img']));
            $thumb = $GLOBALS['images_dir'] . (strpos($row['show_img'],'/m/')?M_PATH.'/':'') . $GLOBALS['thumb_dir'] . $image['0'] . "_thumb." . $image['1'];
            $show_list[] = array(
                    "id" => $row['id'],
                    "show_name" => $row['show_name'],
                    "show_link" => $row['show_link'],
                    "show_img" => ROOT_URL . $row['show_img'],
                    "thumb" => ROOT_URL . $thumb,
                    "type" => $row['type'],
                    "sort" => $row['sort']
            );
        }
        return $show_list;
    }

    /**
     * +----------------------------------------------------------
     * 获取友情链接
     * +----------------------------------------------------------
     */
    function get_link_list() {
        $sql = "SELECT * FROM " . $this->table('link') . " ORDER BY sort ASC, id ASC";
        $query = $this->query($sql);
        while ($row = $this->fetch_array($query)) {
            $link_list[] = array(
                    "id" => $row['id'],
                    "link_name" => $row['link_name'],
                    "link_url" => $row['link_url'],
                    "sort" => $row['sort'] 
            );
        }
        return $link_list;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取当前分类下有层级的子分类
     * +----------------------------------------------------------
     * $table 数据表名
     * $parent_id 父类ID
     * $child_id 子类ID零时存储器，引用指针
     * $level 层级
     * +----------------------------------------------------------
     */
    function dou_child_id($table, $parent_id=0, $quote=',', $level=999, &$child_id='') {
        $data = $this->fetch_array_all($this->table($table), 'sort ASC,cat_id ASC', 'parent_id='.$parent_id, 'cat_id');
        foreach ((array)$data as $value) {
            $child_id .= $child_id?','.$value['cat_id']:$quote.$value['cat_id'];// cat_id不会重复，所以无需去重
            if ($level) {
                $this->dou_child_id($table, $value['cat_id'], $level-1, $child_id);
            }
        }
        return $child_id;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取无层次商品分类，将所有分类存至同一级数组，用$mark作为标记区分
     * +----------------------------------------------------------
     * $table 数据表名
     * $parent_id 默认获取一级导航
     * $level 无限极分类层次
     * $current_id 当前页面栏目ID
     * $category_nolevel 储存分类信息的数组
     * $mark 无限极分类标记
     * +----------------------------------------------------------
     */
    function get_category_nolevel($table, $parent_id=0, $level=0, $current_id='', &$category_nolevel=array(), $mark='-') {
        $data = $this->fetch_array_all($this->table($table), 'sort,cat_id ASC');
        foreach ((array) $data as $value) {
            if ($value['parent_id'] == $parent_id && $value['cat_id'] != $current_id) {
                $value['url'] = $this->rewrite_url($table, $value['cat_id']);
                $value['mark'] = str_repeat($mark, $level);
                $category_nolevel[] = $value;
                $this->get_category_nolevel($table, $value['cat_id'], $level+1, $current_id, $category_nolevel);
            }
        }
        return $category_nolevel;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取无层次单页面列表
     * +----------------------------------------------------------
     * $parent_id 调用该ID下的所有单页面，为空时则调用所有
     * $level 无限极分类层次
     * $current_id 当前页面栏目ID
     * $mark 无限极分类标记
     * +----------------------------------------------------------
     */
    function get_page_nolevel($parent_id = 0, $level = 0, $current_id = '', &$page_nolevel = array(), $mark = '-') {
        $data = $this->fetch_array_all($this->table('page'));
        foreach ((array) $data as $value) {
            if ($value['parent_id'] == $parent_id && $value['id'] != $current_id) {
                $value['url'] = $this->rewrite_url('page', $value['id']);
                $value['mark'] = str_repeat($mark, $level);
                $value['level'] = $level;
                $page_nolevel[] = $value;
                $this->get_page_nolevel($value['id'], $level + 1, $current_id, $page_nolevel);
            }
        }
        return $page_nolevel;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取列表
     * +----------------------------------------------------------
     * $module 模块
     * $cat_id 分类ID
     * $num 调用数量
     * $sort 排序
     * +----------------------------------------------------------
     */
    function get_list($module, $cat_id='ALL', $num='', $sort='', $cutlen=320) {
        $where = $cat_id == 'ALL' ? '' : " WHERE cat_id IN (" . $cat_id . $this->dou_child_id($module . '_category', $cat_id) . ")";
        $sort = $sort ? $sort . ',' : '';
        $limit = $num ? (strpos(strtolower($num),'limit')!==false?$num:' LIMIT '.$num) : '';
        
        $sql = "SELECT * FROM " . $this->table($module) . $where . " ORDER BY " . $sort . "id DESC" . $limit;
        $query = $this->query($sql);
        while ($row = $this->fetch_assoc($query)) {
            $item['id'] = $row['id'];
            if ($row['title']) $item['title'] = $row['title'];
            if ($row['name']) $item['name'] = $row['name'];
            if (!empty($row['price'])) $item['price'] = $row['price'] > 0 ? $this->price_format($row['price']) : $GLOBALS['_LANG']['price_discuss'];
            if ($row['click']) $item['click'] = $row['click'];

            $item['add_time'] = date("Y-m-d", $row['add_time']);
            $item['add_time_short'] = date("m-d", $row['add_time']);
            $item['description'] = $row['description'] ? $row['description'] : ($cutlen?$this->dou_substr($row['content'], $cutlen):$row['content']);
            $item['image'] = $row['image'] ? ROOT_URL . $row['image'] : '';
            $image = explode(".", $row['image']);
            $item['thumb'] = ROOT_URL . $image[0] . "_thumb." . $image[1];
            $item['url'] = $this->rewrite_url($module, $row['id']);
            
            $list[] = $item;
        }
        
        return $list;
    }

    /**
    * +----------------------------------------------------------
    * 获取相关列表  By Lothar
    * +----------------------------------------------------------
    * $module   模块
    * $id       模块id
    * $cat_id   分类ID
    * $sel      字段
    * $where    条件
    * $order    排序
    * $limit    调用数量
    * +----------------------------------------------------------
    */
    function get_douphp_list($cat_id, $where, $order='', $limit='', $sel='*', $module='article', $id='', $target=''){
        // 验证并获取合法的ID，如果不合法将其设定为-1
        $id = $id ? " AND id!='$id'" : '';
        $order = $order ? " ORDER BY ".$order : '';
        $limit = $limit ? $limit : '';
        if ($cat_id == -1) {
            $this->dou_msg($GLOBALS['_LANG']['page_wrong'], ROOT_URL);
        } else {
            $where = $where ? $where : ' WHERE cat_id IN (' . $cat_id . $this->dou_child_id($module.'_category', $cat_id) . ') ' . $id;
        }
        //查找
        // $query = $this->select($this->table($module),$sel,$where,$order,$limit);//$where 中WHERE去除即可
        $sql = "SELECT {$sel} FROM ".$this->table($module) . $where . $order . $limit;
        $query = $this->query($sql);
        while ($row = $this->fetch_array($query)) {
            ###预处理
            $row['url'] = $this->rewrite_url($module, $row['id']);//获取经过伪静态链接
            $row['add_time'] = $row['add_time'] ? date("Y-m-d", $row['add_time']) : '';//格式化时间
            $$row['add_time_short'] = $row['add_time'] ? date("Y-m", $row['add_time']) : '';
            $row['title'] = $row['title'] ? strip_tags($row['title']) : '';//去标签
            $row['name'] = $row['name'] ? strip_tags($row['name']) : '';
            // 图片处理
            if ($row['image']) {
                $image = ROOT_URL . $row['image'];
                // 生成缩略图的文件名
                $thumb = explode(".", $row['image']);
                $thumb = ROOT_URL . $thumb[0] . "_thumb." . $thumb[1];
            } else {
                $image = $_CFG['def_img'];
                $thumb = explode(".", $_CFG['def_img']);
                $thumb = ROOT_URL . $thumb[0] . "_thumb." . $thumb[1];
            }
            $row['image'] = $image;
            $row['thumb'] = $thumb;
            // 如果描述不存在则自动从详细介绍中截取
            $row['description'] = $row['description'] ? $row['description'] : $this->dou_substr($row['content'], 150);
            // 格式化价格
            $row['price'] = ($row['price'] > 0) ? $this->price_format($row['price']) : $_LANG['price_discuss'];

            // 格式化自定义参数
            foreach (explode(',', $row['defined']) as $val) {
                $val = explode('：', str_replace(":", "：", $val));
                if ($val['1']) {
                    $defined[] = array(
                        "arr" => $val['0'],
                        "value" => $val['1'] 
                    );
                }
            }
            $row['defined'] = $defined;

            ###处理为数组
            $douphp_list[] = $row;
            unset($defined,$row['defined']);
        }
        return $douphp_list;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取有层次的栏目分类，有几层分类就创建几维数组
     * +----------------------------------------------------------
     * $table 数据表名
     * $parent_id 默认获取一级导航
     * $current_id 当前页面栏目ID
     * +----------------------------------------------------------
     */
    function get_category($table, $parent_id = 0, $current_id = '') {
        $category = array();
        $data = $this->fetch_array_all($this->table($table), 'sort ASC, cat_id ASC');
        foreach ((array) $data as $value) {
            // $parent_id将在嵌套函数中随之变化
            if ($value['parent_id'] == $parent_id) {
                $value['url'] = $this->rewrite_url($table, $value['cat_id']);
                $value['cur'] = $value['cat_id'] == $current_id ? true : false;
                // if ($value['image']) {
                //     $thumb = explode('.', $value['image']);
                //     $value['thumb'] = ROOT_URL . $thumb[0] . "_thumb." . $thumb[1];
                // }
                
                foreach ($data as $child) {
                    // 筛选下级导航
                    if ($child['parent_id'] == $value['cat_id']) {
                        // 嵌套函数获取子分类
                        $value['child'] = $this->get_category($table, $value['cat_id'], $current_id);
                        break;
                    }
                }
                $category[] = $value;
            }
        }
        
        return $category;
    }

    /**
     * +----------------------------------------------------------
     * 首页自定义模块  
     * 单独 article 栏目调用
     * 支持多个
     * +----------------------------------------------------------
     */
    function article_column() {
        $def_nav = explode('/', $GLOBALS['_CFG']['def_nav']);
        $def_nav_num = explode('/', $GLOBALS['_CFG']['def_nav_num']);
        foreach ($def_nav as $key => $value) {
            $GLOBALS['smarty']->assign('recommend_'.$key, $GLOBALS['dou']->get_list('article', $value, $def_nav_num[$key], 'sort DESC,modtime DESC'));
            $GLOBALS['smarty']->assign('url_'.$key, $GLOBALS['dou']->rewrite_url('article_category', $value));//对应栏目链接
            $GLOBALS['smarty']->assign('column_name_'.$key, $GLOBALS['dou']->rewrite_url('article_category', $value));//对应栏目名

        }
        // return $article_column;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取指定分类单页面列表
     * +----------------------------------------------------------
     * $parent_id 调用该ID下的所有单页面，为空时则调用所有
     * $current_id 当前打开的单页面ID，高亮菜单使用
     * +----------------------------------------------------------
     */
    function get_page_list($parent_id = 0, $current_id = '') {
        $page_list = array();
        $data = $this->fetch_array_all($this->table('page'), 'id ASC');
        foreach ((array) $data as $value) {
            // $parent_id将在嵌套函数中随之变化
            if ($value['parent_id'] == $parent_id) {
                $value['url'] = $this->rewrite_url('page', $value['id']);
                $value['cur'] = $value['id'] == $current_id ? true : false;
                
                foreach ($data as $child) {
                    // 筛选下级单页面
                    if ($child['parent_id'] == $value['id']) {
                        // 嵌套函数获取子分类
                        $value['child'] = $this->get_page_list($value['id'], $current_id);
                        break;
                    }
                }
                $page_list[] = $value;
            }
        }
        return $page_list;
    }

    /**
     * +----------------------------------------------------------
     * 获取单页面信息
     * +----------------------------------------------------------
     */
    function get_page_info($id = 0) {
        $query = $this->select($this->table('page'), '*', '`id` = \'' . $id . '\'');
        $page = $this->fetch_array($query);
        if ($page) {
            $page['url'] = $this->rewrite_url('page', $page['id']);
        }
        return $page;
    }
    
    /**
     * +----------------------------------------------------------
     * 分页
     * +----------------------------------------------------------
     * $table 数据表名
     * $page_size 每页显示数量
     * $page 当前页码
     * $page_url 地址栏中参数传递 article.php?id=1  || $jumpurl
     * $where SQL查询条件
     * $get 地址栏中额外参数传递 &var=v             || $jumpext
     * $close_rewrite 强制关闭伪静态
     * $join 联表
     * $sep 分页符 'page'，用于多分页
     * +----------------------------------------------------------
     */
    function pager($table, $page_size=10, $page, $page_url='', $where='', $get='', $join='', $close_rewrite=false, $sep='page') {
        if (!is_array($table)) 
            $table = explode(',', $table);
        if (count($table)>1) {
            $sql = "SELECT count(*) FROM " . $this->table($table[0]) ." as a LEFT JOIN " . $this->table($table[1]) ." as b ON ". $join . $where;
        } else {
            $sql = "SELECT count(*) FROM " . $this->table($table[0]) . $where;
        }
        $record_count = $this->get_one($sql);
        
        // 调整分页链接样式
        if (!defined('IS_ADMIN') && $GLOBALS['_CFG']['rewrite'] && !$close_rewrite) {
            $get_page = '/o';
            $get = preg_replace('/&/', '?', $get, 1); // 将起始参数标记改为'?'
            $get = '/' . $get; // 起始参数前加'/'
        } else {
            $get_page = strpos($page_url, '?')!==false ? '&'.$sep.'=' : '?'.$sep.'=';
        }

        $page_count = ceil($record_count/$page_size);
        $first = $page_url . $get_page . '1' . $get;
        $previous = $page_url . $get_page . ($page>1?$page-1:0) . $get;
        $next = $page_url . $get_page . ($page_count>$page?$page+1:0) . $get;
        $last = $page_url . $get_page . $page_count . $get;
        
        $spt = $page_url . $get_page;
        $pagep = '';$pagen = '';
        // 上三页
        for ($i=$page-4; $i < $page; $i++) { 
            if ($i > 0) {
                $pagep .= '<a href="'.$spt.$i.$get.'">'.$i.'</a>';
            }
        }
        // 当前页
        // $current = "<a href=\''.$spt.$page.$get."\">".$page."</a>";
        $current = '<span class="active">'.$page.'</span>';
        // 下三页
        for ($i=$page+1; $i < $page+5; $i++) { 
            if ($i <= $page_count) {
                $pagen .= '<a href="'.$spt.$i.$get.'">'.$i.'</a>';
            }
        }
        
        $pager = array(
            "record_count" => $record_count,
            "page_size" => $page_size,
            "page" => $page,
            "page_count" => $page_count,
            "previous" => $previous,
            "next" => $next,
            "first" => $first,
            "last" => $last,
            "pagep" => $pagep,
            "current" => $current,
            "pagen" => $pagen
        );
        
        $start = ($page-1) * $page_size;
        $limit = " LIMIT $start, $page_size";
        $GLOBALS['smarty']->assign('pager'.($sep=='page'?'':'_'.$sep), $pager);
        return $limit;
    }

    /*
    * function pager(){} 写的太死，无法改
    */
    public function pager_style($pager='')
    {
        # code...
    }
    
    /**
     * +----------------------------------------------------------
     * 获取上一项下一项
     * +----------------------------------------------------------
     */
    function lift($module, $id, $cat_id) {
        $field = $this->field_exist($module, 'title') ? 'title' : 'name'; // 判断包含title字段还是name字段
        $screen = $cat_id ? " AND cat_id = $cat_id" : ''; // 判断是否有分类筛选
        // 上一项
        $lift['previous'] = $this->fetch_assoc($this->query("SELECT id, " . $field . " FROM " . $this->table($module) . " WHERE id > $id" . $screen . " ORDER BY id ASC"));
        if ($lift['previous']) $lift['previous']['url'] = $this->rewrite_url($module, $lift['previous']['id']);
        // 下一项
        $lift['next'] = $this->fetch_assoc($this->query("SELECT id, " . $field . " FROM " . $this->table($module) . " WHERE id < $id" . $screen . " ORDER BY id DESC"));
        if ($lift['next']) $lift['next']['url'] = $this->rewrite_url($module, $lift['next']['id']);
        return $lift;
    }

    /**
     * +----------------------------------------------------------
     * 获取第一条记录
     * +----------------------------------------------------------
     * $log 日志内容
     * $desc 是否倒序
     * +----------------------------------------------------------
     */
    function get_first_log($log, $desc = false) {
        $log_array = explode(',', $log);
        $log = $desc ? ($log_array[1] ? $log_array[1] : $log_array[0]) : $log_array[0];
        return $log;
    }

    /**
     * +----------------------------------------------------------
     * 获取插件配置信息
     * +----------------------------------------------------------
     * $unique_id 插件唯一ID
     * +----------------------------------------------------------
     */
    function get_plugin($unique_id) {
        $plugin = $this->fetch_array($this->select($this->table('plugin'), '*', '`unique_id` = \'' . $unique_id . '\''));
        if ($plugin['config'])
            $plugin['config'] = unserialize($plugin['config']);
        
        return $plugin;
    }

    /**
     * +----------------------------------------------------------
     * 邮件发送
     * +----------------------------------------------------------
     * $mailto 收件人地址
     * $title 邮件标题
     * $body 邮件正文
     * +----------------------------------------------------------
     */
    function send_mail($mailto, $subject = '', $body = '') {
        if ($GLOBALS['_CFG']['mail_service'] && file_exists(ROOT_PATH . 'include/mail.class.php')) {
            include_once (ROOT_PATH . 'include/mail.class.php');
            include_once (ROOT_PATH . 'include/smtp.class.php');

            $mail = new PHPMailer;                                // 实例化
            //$mail->SMTPDebug = 3;                               // 启用SMTP调试功能
             
            $mail->CharSet ="UTF-8";                              // 设定邮件编码
            $mail->isSMTP();                                      // 设定使用SMTP服务
            $mail->Host = $GLOBALS['_CFG']['mail_host'];          // SMTP服务器
            $mail->SMTPAuth = true;                               // 启用SMTP验证功能
            $mail->Username = $GLOBALS['_CFG']['mail_username'];  // SMTP服务器用户名
            $mail->Password = $GLOBALS['_CFG']['mail_password'];  // SMTP服务器密码
            if ($GLOBALS['_CFG']['mail_ssl'])
                $mail->SMTPSecure = 'ssl';                        // 安全协议，可以注释掉
            $mail->Port = $GLOBALS['_CFG']['mail_port'];          // SMTP服务器的端口号
            $mail->isHTML(true);                                  // 是否HTML格式邮件
            
            $mail->From = $GLOBALS['_CFG']['mail_username'];      // 发件人地址
            $mail->FromName = $GLOBALS['_CFG']['site_name'];      // 发件人姓名
            $mail->addAddress($mailto, '');                       // 收件地址，可选指定收件人姓名

            $mail->Subject = $subject;                            // 邮件标题
            $mail->Body    = $body;                               // 邮件内容
            // 邮件正文不支持HTML的备用显示
            $mail->AltBody = $GLOBALS['_LANG']['mail_altbody']; 

            if($mail->send()) {
                return true;
            }
        } else {
            // 系统内置Mail服务
            $subject = "=?UTF-8?B?".base64_encode($subject)."?=";          // 解决邮件主题乱码问题，UTF8编码格式
            $header  = "From: ".$GLOBALS['_CFG']['site_name']." <".$GLOBALS['_CFG']['email'].">\n";
            $header .= "Return-Path: <".$GLOBALS['_CFG']['email'].">\n";   // 防止被当做垃圾邮件
            $header .= "MIME-Version: 1.0\n";
            $header .= "Content-type: text/html; charset=utf-8\n";         // 邮件内容为utf-8编码
            $header .= "Content-Transfer-Encoding: 8bit\r\n";              // 注意header的结尾，只有这个后面有\r
            ini_set('sendmail_from', $GLOBALS['_CFG']['email']);           // 解决mail的一个bug
            $body = wordwrap($body, 70);                                   // 每行最多70个字符,这个是mail方法的限制
            if (mail($mailto, $subject, $body, $header))
                return ture;
        }
    }

    /**
     * +----------------------------------------------------------
     * 手机短信验证码
     * 云片网
     * +----------------------------------------------------------
     * $mobile 对象手机号
     * +----------------------------------------------------------
    */
    public function send_msg($mobile='', $plugin_id='yunpian')
    {
        require ROOT_PATH .'include/plugin/'. $plugin_id .'/work.plugin.php';
        $plugin = new Plugin($mobile);

        return $plugin->work();
    }
    


    /**
     * +----------------------------------------------------------
     * 向客户端发送原始的 HTTP 报头
     * +----------------------------------------------------------
     * $url 跳转网址
     * +----------------------------------------------------------
     */
    function dou_header($url='/') {
        header("Location: ". $url);
        exit;
    }
    
    /**
     * +----------------------------------------------------------
     * 格式化商品价格
     * +----------------------------------------------------------
     * $price 需要格式化的价格
     * +----------------------------------------------------------
     */
    function price_format($price = '') {
        $price = number_format($price, $GLOBALS['_CFG']['price_decimal']);
        $price_format = preg_replace('/d%/Ums', $price, $GLOBALS['_LANG']['price_format']);
        
        return $price_format;
    }
    
    /**
     * +----------------------------------------------------------
     * 生成在线客服QQ数组
     * +----------------------------------------------------------
     */
    function dou_qq($im) {
        $im_explode = explode(',', $im);
        foreach ((array) $im_explode as $value) {
            if (strpos($value, '/') !== false) {
                $arr = explode('/', $value);
                $list['number'] = $arr['0'];
                $list['nickname'] = $arr['1'];
                $im_list[] = $list;
            } else {
                $im_list[] = $value;
            }
        }
        
        return $im_list;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取真实IP地址
     * +----------------------------------------------------------
     */
    function get_ip() {
        static $ip;
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $ip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $ip = getenv("HTTP_CLIENT_IP");
            } else {
                $ip = getenv("REMOTE_ADDR");
            }
        }
        if (preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/', $ip)) {
            return $ip;
        } else {
            return '127.0.0.1';
        }
    }
    
    /**
     * +----------------------------------------------------------
     * 判断目录状态
     * +----------------------------------------------------------
     */
    function dir_status($dir) {
        // 判断目录是否存在
        if (file_exists($dir)) {
            // 判断目录是否可写
            if ($fp = @fopen("$dir/test.txt", 'w')) {
                @fclose($fp);
                @unlink("$dir/test.txt");
                $status = 'write';
            } else {
                $status = 'exist';
            }
        } else {
            $status = 'no_exist';
        }
        
        return $status;
    }
    
    /**
     * +----------------------------------------------------------
     * 获取当前目录子文件夹名
     * +----------------------------------------------------------
     * $dir 要检索的目录
     * +----------------------------------------------------------
     */
    function get_subdirs($dir) {
        $subdirs = array();
        if (!$handle  = @opendir($dir)) return $subdirs;
        
        while ($file = @readdir($handle )) {
            if ($file == '.' || $file == '..') continue; // 排除掉当前目录和上一个目录
            $subdirs[] = $file;
        }
        return $subdirs;
    }
    
    /**
     * +----------------------------------------------------------
     * 清除html,换行，空格类并且可以截取内容长度
     * +----------------------------------------------------------
     * $str 要处理的内容
     * $length 要保留的长度
     * $clear_space 判断是否清除空格
     * $dot 省略号
     * $charset 要处理的内容的编码，一般情况无需设置
     * +----------------------------------------------------------
     */
    function dou_substr($str, $length, $clear_space=true, $dot='……', $charset=DOU_CHARSET) {
        $str = trim($str); // 清除字符串两边的空格
        $str = strip_tags($str, ''); // 利用php自带的函数清除html格式
        $str = preg_replace("/\r\n/", '', $str);
        $str = preg_replace("/\r/", '', $str);
        $str = preg_replace("/\n/", '', $str);
        // 判断是否清除空格
        if ($clear_space) {
            $str = preg_replace("/\t/", '', $str);
            $str = preg_replace("/ /", '', $str);
            $str = preg_replace("/&nbsp;/", '', $str); // 匹配html中的空格
        }
        $str = trim($str); // 清除字符串两边的空格
        
        if (function_exists("mb_substr")) {
            $substr = mb_substr($str, 0, $length, $charset);
        } else {
            $c['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $c['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            preg_match_all($c[$charset], $str, $match);
            $substr = join('', array_slice($match[0], 0, $length));
        }
        return $substr . (strlen($str)>$length?$dot:'');
    }
    // 去除首尾空格
    function trim_arr($arr=array())
    {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = trim_arr($value);
            } else {
                $arr[$key] = trim($value);
            }
        }
        return $arr;
    }
    // 将1,2,3,4替换成a,b,c,d
    function replace_preg($str,$sourcearr,$destarr){
        for ($i=0; isset($sourcearr[$i]); $i++) { 
        if($sourcearr[$i]==$str)
            return $destarr[$i];
        }
    }

    /**
     * +----------------------------------------------------------
     * 生成随机数
     * +----------------------------------------------------------
     * $type 随机字符类型
     * $length 长度
     * $prefix 前缀
     * +----------------------------------------------------------
     *  $name = date('Ymd');
        for($i = 0; $i < 6; $i++) {
            $name .= chr(mt_rand(97, 122));
        }
     */
    function create_rand_string($type='nl', $length=6, $prefix='', $custom_chars='') {
        // 去掉了容易混淆的字符oOLl和数字01
        $n = '23456789'; $l = 'abcdefghijkmnpqrstuvwxyz'; $L = 'ABCDEFGHIJKMNPQRSTUVWXYZ';
        switch ($type) {
            case 'n': $chars = '0123456789'; break;
            case 'l': $chars = 'abcdefghijklmnopqrstuvwxyz'; break;
            case 'L': $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; break;
            case 'lL': $chars = $l.$L; break;
            case 'nL': $chars = $n.$L; break;
            case 'nlL': $chars = $n.$l.$L; break;
            default: $chars = $n.$l; break;
        }
        // 如果有自定义的字符则包含进去
        $chars = $chars . $custom_chars;
        $string = '';
        for($i = 0; $i < $length; $i++) {
            $string .= $chars[mt_rand(0, strlen($chars)-1)];
        }
        return $prefix . $string;
    }



    /**
     * +----------------------------------------------------------
     * 标准查询时的字段处理
     * 默认给加反引号 ``
     * $fields 要处理的字段 $fields = 'id,name,sex';
     * $unite 联表标志
     * $lokey 关键key
     * +----------------------------------------------------------
    */
    public static function create_fields_quote($fields='*', $unite='',$lokey='')
    {
        $fields = explode(',', $fields);
        foreach ($fields as $f) {
            if ($f) {
                $fquote .= isset($fquote) ? ',' : '';
                $fquote .= ($unite) ? $unite.'.'.$f : ($f=='*'?$f:'`'.$f.'`');
            }
        }
        if ($fields!='*' && $lokey && !in_array($lokey,$fields)) {
            if ($unite) {
                $fquote = $unite.'.'.$lokey .','. $fquote;
            } else {
                $fquote = '`'.$lokey.'`,'. $fquote;
            }
        }
        return $fquote;
    }
    // 获取指定顶级id
    function get_tid($table='nav',$cid,$pid=0){
        if($table=='nav'){
            $sql = "SELECT parent_id FROM ".$this->table($table)." WHERE id='$cid'";
        }else{
            $sql = "SELECT parent_id FROM ".$this->table($table)." WHERE cat_id=$cid ";
        }
        $res = $this->get_one($sql);
        if($res > $pid){
            return $this->get_tid($table,$res);
        }else{
            return $cid;
        }
    }

    // function get_tid($table,$cid,$pid=0){
    //     $sql = "SELECT parent_id FROM ".$this->table($table)." WHERE cat_id = $cid ";
    //     $res = $this->get_one($sql);
    //     if($res > $pid){
    //         return $this->get_tid($table,$res);
    //     }else{
    //         echo $res."<br>";
    //         echo $cid."<br>";
    //         return $cid;
    //     }
    // }

    //根据子类id, 算最顶级id
    // function get_tid($table,$cid){
    //     $sql = "SELECT cat_id,parent_id FROM ".$this->table($table)." WHERE cat_id='$cid'";
    //     $res = $this->query($sql);
    //     $menu = $this->fetch_row($res);
    //     if($menu[1] != 0){
    //         return $this->get_tid($table,$menu['parent_id']);
    //     }
    //     return $menu['cat_id'];
    // }

    // 优雅法
    // function get_tid($table,$cid){
    //     $sql = "select cat_id,parent_id from `dou_".$table."` ";
    //     // 查询后 将结果处理成 如下数组格式
    //     $arr = [
    //       // cid => pid
    //       1 => 0,
    //       // 省略...
    //       5 => 1,
    //       // 省略...
    //       13 => 5
    //     ];
    //     // 建议将这数组缓存起来
    //     while($arr[$cid]) {
    //       $cid = $arr[$cid];
    //     }
    //     echo $cid; // 1
    // }

    // 获取所有
    // function get_tid($table,$cid,$pid='0'){
    //     $sql = "SELECT cat_id, parent_id FROM " . $this->table($table) . " WHERE cat_id = '$cid'";
    //     $query = $this->query($sql);
    //     $catefoo = $this->fetch_array($query);
    //     // $top_id = ($catefoo['parent_id'] == $pid) ? a : b ;
    //     // echo $catefoo['cat_id'].' - '.$catefoo['parent_id'].'<br>';
    //     $top_id = $catefoo['cat_id'];
    //     echo $top_id;
    //     // unset($catefoo);
    //     if ($catefoo['parent_id']!=$pid) {
    //         unset($top_id);
    //         $this->get_tid($table,$catefoo['parent_id'],$pid);
    //     }
    //     return $top_id;
    // }

    // php递归无限级分类【先序遍历算】，获取任意节点上所有子孩子
    // function getMenuTree($arrCat, $parent_id = 0, $level = 0){
    //     $arrTree =array();//使用static代替global
    // 　　if(empty($arrCat)) return FALSE;
    // 　　$level++;
    // 　　foreach($arrCatas$key => $value){
    //     　　if($value['parent_id'] == $parent_id){
    //         　　$value['level'] = $level;
    //         　　$arrTree[] = $value;
    //         　　unset($arrCat[$key]);//注销当前节点数据，减少已无用的遍历
    //         　　getMenuTree($arrCat, $value['id'], $level);
    //     　　}
    // 　　}
    // 　　return $arrTree;
    // }


    //以下方法匹配出内容中所有图片
    function getImgs($content,$order='ALL'){
        $pattern="/<img.*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
        preg_match_all($pattern,$content,$match);
        if(isset($match[1])&&!empty($match[1])){
            if($order==='ALL'){
                return $match[1];
            }
            if(is_numeric($order)&&isset($match[1][$order])){
                return $match[1][$order];
            }
        }
        return '';
    }



    // 对象数组互转 array
    function obj_arr($data) {
        if(is_object($data)) {
            $data = (array)$data;
        }
        if(is_array($data)) {
            foreach($data as $key=>$value) {
                $data[$key] = $this->obj_arr($value);
            }
        }
        return $data;
    }

    /**
     * +----------------------------------------------------------
     * 对不同系统的换行符进行处理
     * +----------------------------------------------------------
     */
    function line_break_change($str) 
    {
        if (strtoupper(substr(PHP_OS,0,3))==='WIN') {
            if (stripos($str,"\r\n")===false) {
                $str = str_replace("\n",PHP_EOL,$str);
            }
        } else {
            if (stripos($str,"\r\n")===true) {
                $str = str_replace("\r\n",PHP_EOL,$str);
            }
        }
        return $str;
    }

    /*调试专用*/
    function debug($var=null, $die=false, $dump=false, $html = '<hr>') 
    {
        $dump = empty($var) ? true : $dump;
        if ($dump) {
            echo '<pre>';var_dump($var);
        } else {
            if (is_string($var)) {
                echo $var.'<br>';
            } elseif (is_object($var)) {
                echo '<pre>';var_dump($var);
            } elseif (is_array($var)) {
                echo '<pre>';print_r($var);
            }
        }
        if (!is_string($var)) 
            echo '</pre>';
        if ($die)
            exit();
    }

}
?>