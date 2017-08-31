<?php
define('IN_LOTHAR', true);

require (dirname(__FILE__) . '/include/init.php');

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

// 图片上传
include_once (ROOT_PATH . 'include/upload.class.php');
$images_dir = 'images/article/'; // 文件上传路径，结尾加斜杠
$thumb_dir = ''; // 缩略图路径（相对于$images_dir） 结尾加斜杠，留空则跟$images_dir相同
$img = new Upload(ROOT_PATH . $images_dir, $thumb_dir); // 实例化类文件
if (!file_exists(ROOT_PATH . $images_dir)) {
    mkdir(ROOT_PATH . $images_dir, 0777);
}

// 赋值给模板
$smarty->assign('rec', $rec);
$smarty->assign('cur', 'article');

/*if (in_array($rec,array('default','add','edit'))) {
    // 允许指定模板
    $allow_tpl_name = array('默认','新闻','服务中心');
    $allow_tpl_tpl = array('article.dwt','news_info.html','article.dwt');
    foreach ($allow_tpl_name as $key => $value) {
        $allow_tpl[$key]['name'] = $allow_tpl_name[$key];
        $allow_tpl[$key]['tpl'] = $allow_tpl_tpl[$key];
    }
    $smarty->assign('allow_tpl',$allow_tpl);
}*/

/**
 * +----------------------------------------------------------
 * 文章列表
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    $smarty->assign('ur_here', $_LANG['article']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['article_add'],
            'href' => 'article.php?rec=add' 
    ));

    // 获取参数
    $cat_id = $check->is_number($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : 0;
    $keyword = isset($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
    
    // 筛选条件
    if ($cat_id) {
        $where = ' WHERE a.cat_id IN ('.$cat_id . $dou->dou_child_id('article_category',$cat_id).')';
    }
    // $where = $where ? $where : '';
    if ($keyword) {
        $where .= ($where ? ' AND ' : ' WHERE ') . "a.title LIKE '%$keyword%'";
        $get = '&keyword=' . $keyword;
    }
    
    // 分页
    $page = $check->is_number($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    $page_url = 'article.php' . ($cat_id ? '?cat_id='.$cat_id : '');
    $where2 = str_replace('a.', '', $where);
    $limit = $dou->pager('article', 15, $page, $page_url, $where2, $get);
    // 查询数据
    $fields = $dou->create_fields_quote('id,title,cat_id,image,add_time,template,sort','a');
    $sql = sprintf("SELECT %s,b.cat_name from %s a left join %s b on a.cat_id=b.cat_id %s ORDER BY %s %s", $fields,$dou->table('article'),$dou->table('article_category'),$where,'a.cat_id,a.sort,a.add_time DESC',$limit);
    $query = $dou->query($sql);
    while ($row = $dou->fetch_array($query,MYSQL_ASSOC)) {
        $row['add_time'] = date("Y-m-d", $row['add_time']);
        $article_list[] = $row;
    }
    
    // 首页显示文章数量限制框
    for($i=1; $i<=$_CFG['home_display_article']; $i++) {
        $sort_bg .= "<li><em></em></li>";
    }
    
    // 赋值给模板
    $smarty->assign('if_sort', $_SESSION['if_sort']);
    $smarty->assign('sort', get_sort_article());
    $smarty->assign('sort_bg', $sort_bg);
    $smarty->assign('cat_id', $cat_id);
    $smarty->assign('keyword', $keyword);
    $smarty->assign('article_category', $dou->get_category_nolevel('article_category'));
    $smarty->assign('article_list', $article_list);
    
    $smarty->display('article.htm');
} 

/**
 * +----------------------------------------------------------
 * 文章添加
 * +----------------------------------------------------------
 */
elseif ($rec == 'add') {
    $smarty->assign('ur_here', $_LANG['article_add']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['article'],
            'href' => 'article.php' 
    ));
    
    // 格式化自定义参数，并存到数组$article，文章编辑页面中调用文章详情也是用数组$article，
    if ($_DEFINED['article']) {
        $defined = explode(',', $_DEFINED['article']);
        foreach ($defined as $row) {
            $defined_article .= $row . "：\n";
        }
        $article['defined'] = trim($defined_article);
        $article['defined_count'] = count(explode("\n", $article['defined'])) * 2;
    }
    
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());
    
    // 赋值给模板
    $smarty->assign('form_action', 'insert');
    $smarty->assign('article_category', $dou->get_category_nolevel('article_category'));
    $smarty->assign('article', $article);
    
    $smarty->display('article.htm');
} 

elseif ($rec == 'insert') {
    // 验证标题
    if (empty($_POST['title'])) $dou->dou_msg($_LANG['article_name'] . $_LANG['is_empty']);
    if ($dou->get_one("SELECT id from ".$dou->table('article')." where title='{$_POST[title]}'")) {
        $dou->dou_msg('存在同名标题！');
    }

    // 图片上传
    if ($_FILES['image']['name'] != '') {
        $image_name = $img->upload_image('image', $img->create_file_name('article'));
        $image = $images_dir . $image_name;
        $img->make_thumb($image_name, $_CFG['thumb_width'], $_CFG['thumb_height']);
    }
    
    // 数据格式化
    $add_time = time();
    $_POST['defined'] = str_replace("\r\n", ',', $_POST['defined']);
        
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    $data = array(
            'cat_id'    => $_POST['cat_id'],
            'title'    => $_POST['title'],
            'defined'    => $_POST['defined'],
            'content'    => $_POST['content'],
            'image'    => $image,
            'keywords'    => $_POST['keywords'],
            'description'    => $_POST['description'],
            'add_time'    => $add_time,
            'sort' => $_POST['sort'],
            'template'    => $template,
        );
    $res = $dou->insert('article',$data);
    if ($res) {
        $dou->create_admin_log($_LANG['article_add'] . ': ' . $_POST['title']);
        $dou->dou_msg($_LANG['article_add_succes'], 'article.php');
    } else {
        $dou->dou_msg('添加失败！');
    }
} 

/**
 * +----------------------------------------------------------
 * 文章编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit') {
    $smarty->assign('ur_here', $_LANG['article_edit']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['article'],
            'href' => 'article.php' 
    ));
    
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : '';
    
    $query = $dou->select($dou->table('article'), '*', '`id` = \'' . $id . '\'');
    $article = $dou->fetch_array($query);
    
    // 格式化自定义参数
    // if ($_DEFINED['article'] || $article['defined']) {
    if ($_DEFINED['article']) {
        $defined = explode(',', $_DEFINED['article']);
        foreach ($defined as $row) {
            $defined_article .= $row . "：\n";
        }
        // 如果文章中已经写入自定义参数则调用已有的
        $article['defined'] = $article['defined'] ? str_replace(",", "\n", $article['defined']) : trim($defined_article);
        $article['defined_count'] = count(explode("\n", $article['defined'])) * 2;
    }
    
    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());
    
    // 赋值给模板
    $smarty->assign('form_action', 'update');
    $smarty->assign('article_category', $dou->get_category_nolevel('article_category'));
    $smarty->assign('article', $article);
    
    $smarty->display('article.htm');
} 

elseif ($rec == 'update') {
    // 验证并获取合法的ID
    if ($check->is_number($_POST['id'])){
        $id = intval($_POST['id']);
    } else {
        $dou->dou_msg('ID 非法');
    }
    // 验证标题
    if (empty($_POST['title'])) $dou->dou_msg($_LANG['article_name'] . $_LANG['is_empty']);

    // 图片上传
    if ($_FILES['image']['name'] != '') {
        $image_name = $img->upload_image('image', $img->create_file_name('article', $id, 'image'));
        $image = $images_dir . $image_name;
        $img->make_thumb($image_name, $_CFG['thumb_width'], $_CFG['thumb_height']);
        // 因为这里文件名始终相同，会直接覆盖源文件，所以不可以做额外删除
        // 但是如果文件的后缀不一样，就不能删除旧文件了哦！
        // $old_pic = $dou->get_one("SELECT image from ".$dou->table('article')." where id={$id} ");
        // if ($old_pic) { $dou->del_image($old_pic); }
    }
    // 格式化自定义参数
    $_POST['defined'] = str_replace("\r\n", ',', $_POST['defined']);
    
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);
    
    $data = array(
            'cat_id'  => $_POST['cat_id'],
            'title'  => $_POST['title'],
            'defined'  => $_POST['defined'],
            'content'  => $_POST['content'],
            'image'  => ($image?$image:$_POST['image_old']),
            'keywords'  => $_POST['keywords'],
            'description'  => $_POST['description'],
            'sort' => $_POST['sort'],
            'template'  => $_POST['template'],
        );
    // if ($image) 
    //     $data['image'] = $image;
    $res = $dou->update('article',$data,"id='$id'");
    if ($res) {
        $dou->create_admin_log($_LANG['article_edit'] . ': ' . $_POST['title']);
        $dou->dou_msg($_LANG['article_edit_succes'], 'article.php');
    } else {
        $dou->dou_msg('内容无变化 或 修改失败！');
    }
} 

/**
 * +----------------------------------------------------------
 * 首页商品筛选
 * +----------------------------------------------------------
 */
elseif ($rec == 'sort') {
    $_SESSION['if_sort'] = $_SESSION['if_sort'] ? false : true;
    
    // 跳转到上一页面
    $dou->dou_header($_SERVER['HTTP_REFERER']);
} 

/**
 * +----------------------------------------------------------
 * 设为首页显示商品
 * +----------------------------------------------------------
 */
elseif ($rec == 'set_sort') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'article.php');
    
    $max_sort = $dou->get_one("SELECT sort FROM " . $dou->table('article') . " ORDER BY sort DESC");
    $new_sort = $max_sort + 1;
    $dou->query("UPDATE " . $dou->table('article') . " SET sort = '$new_sort' WHERE id = '$id'");
    
    $dou->dou_header($_SERVER['HTTP_REFERER']);
} 

/**
 * +----------------------------------------------------------
 * 取消首页显示商品
 * +----------------------------------------------------------
 */
elseif ($rec == 'del_sort') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'article.php');
    
    $dou->query("UPDATE " . $dou->table('article') . " SET sort = '' WHERE id = '$id'");
    
    $dou->dou_header($_SERVER['HTTP_REFERER']);
} 

/**
 * +----------------------------------------------------------
 * 文章删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'article.php');
    $title = $dou->get_one("SELECT title FROM " . $dou->table('article') . " WHERE id = '$id'");
    
    if (isset($_POST['confirm']) ? $_POST['confirm'] : '') {
        // 删除相应商品图片
        $image = $dou->get_one("SELECT image FROM " . $dou->table('article') . " WHERE id = '$id'");
        $dou->del_image($image);
        
        $dou->create_admin_log($_LANG['article_del'] . ': ' . $title);
        $dou->delete($dou->table('article'), "id = $id", 'article.php');
    } else {
        $_LANG['del_check'] = preg_replace('/d%/Ums', $title, $_LANG['del_check']);
        $dou->dou_msg($_LANG['del_check'], 'article.php', '', '30', "article.php?rec=del&id=$id");
    }
} 

/**
 * +----------------------------------------------------------
 * 批量操作选择
 * +----------------------------------------------------------
 */
elseif ($rec == 'action') {
    if (is_array($_POST['checkbox'])) {
        if ($_POST['action'] == 'del_all') {
            // 批量文章删除
            $dou->del_all('article', $_POST['checkbox'], 'id', 'image');
        } elseif ($_POST['action'] == 'category_move') {
            // 批量移动分类
            $dou->category_move('article', $_POST['checkbox'], $_POST['new_cat_id']);
        } else {
            $dou->dou_msg($_LANG['select_empty']);
        }
    } else {
        $dou->dou_msg($_LANG['article_select_empty']);
    }
}

/**
 * +----------------------------------------------------------
 * 获取首页显示文章
 * +----------------------------------------------------------
 */
function get_sort_article() {
    $limit = $GLOBALS['_DISPLAY']['home_article'] ? ' LIMIT ' . $GLOBALS['_DISPLAY']['home_article'] : '';
    $sql = "SELECT id, title FROM " . $GLOBALS['dou']->table('article') . " WHERE sort > 0 ORDER BY sort DESC" . $limit;
    $query = $GLOBALS['dou']->query($sql);
    while ($row = $GLOBALS['dou']->fetch_array($query)) {
        $sort[] = array(
                "id" => $row['id'],
                "title" => $row['title'] 
        );
    }
    
    return $sort;
}
?>