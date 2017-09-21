<?php
define('IN_LOTHAR', true);
define('CMOD', 'case');
require (dirname(__FILE__) . '/include/init.php');

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

// 图片上传
include_once (ROOT_PATH . 'include/upload.class.php');
$images_dir = 'images/'.CMOD.'/'; // 文件上传路径，结尾加斜杠
$thumb_dir = ''; // 缩略图路径（相对于$images_dir）
$img = new Upload(ROOT_PATH . $images_dir, $thumb_dir); // 实例化类文件
if (!file_exists(ROOT_PATH . $images_dir))
    mkdir(ROOT_PATH . $images_dir, 0777);

// 赋值给模板
$smarty->assign('rec', $rec);
$smarty->assign('cur', CMOD);

/**
 * +----------------------------------------------------------
 * 案例列表
 * +----------------------------------------------------------
 */
if ($rec == 'default') {
    $smarty->assign('ur_here', $_LANG['case']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['case_add'],
            'href' => 'case.php?rec=add'
    ));

    // 获取参数
    $cat_id = $check->is_number($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : 0;
    $keyword = isset($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';

    // 筛选条件
    $where = ' WHERE cat_id IN (' . $cat_id . $dou->dou_child_id('case_category', $cat_id) . ')';
    if ($keyword) {
        $where .= ($where ? ' AND ' : ' WHERE ') . "a.title LIKE '%$keyword%'";
        $get = '&keyword=' . $keyword;
    }

    // 分页
    $page = $check->is_number($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    $page_url = 'case.php' . ($cat_id ? '?cat_id=' . $cat_id : '');
    $where2 = str_replace('a.', '', $where);
    $limit = $dou->pager('case', 15, $page, $page_url, $where2, $get);
    // 查询数据
    $fields = $dou->create_fields_quote('id,title,cat_id,image,add_time','a');
    $sql = sprintf("SELECT %s,b.cat_name from %s a left join %s b on a.cat_id=b.cat_id %s ORDER BY %s %s", $fields,$dou->table('case'),$dou->table('case_category'),$where,'a.id DESC',$limit);
    $query = $dou->query($sql);
    while ($row = $dou->fetch_array($query,MYSQL_ASSOC)) {
        $row['add_time'] = date("Y-m-d", $row['add_time']);
        $case_list[] = $row;
    }

    // 首页显示案例数量限制框
    for($i=1; $i<=$_CFG['home_display_case']; $i++) {
        $sort_bg .= "<li><em></em></li>";
    }

    // 赋值给模板
    $smarty->assign('if_sort', $_SESSION['if_sort']);
    $smarty->assign('sort', get_sort_case());
    $smarty->assign('sort_bg', $sort_bg);
    $smarty->assign('cat_id', $cat_id);
    $smarty->assign('keyword', $keyword);
    $smarty->assign('case_category', $dou->get_category_nolevel('case_category'));
    $smarty->assign('case_list', $case_list);

    $smarty->display('case.htm');
}

/**
 * +----------------------------------------------------------
 * 案例添加
 * +----------------------------------------------------------
 */
elseif ($rec == 'add') {
    $smarty->assign('ur_here', $_LANG['case_add']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['case'],
            'href' => 'case.php'
    ));

    // 格式化自定义参数，并存到数组$case，案例编辑页面中调用案例详情也是用数组$case，
    if ($_DEFINED['case']) {
        $defined = explode(',', $_DEFINED['case']);
        foreach ($defined as $row) {
            $defined_case .= $row . "：\n";
        }
        $case['defined'] = trim($defined_case);
        $case['defined_count'] = count(explode("\n", $case['defined'])) * 2;
    }

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('form_action', 'insert');
    $smarty->assign('case_category', $dou->get_category_nolevel('case_category'));
    $smarty->assign('case', $case);

    $smarty->display('case.htm');
}

elseif ($rec == 'insert') {
    // 验证标题
    if (empty($_POST['title'])) $dou->dou_msg($_LANG['case_name'] . $_LANG['is_empty']);

    // 图片上传
    if ($_FILES['image']['name'] != '')
        $image = $images_dir . $img->upload_image('image', $img->create_file_name('case'));

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
        );
    $res = $dou->insert('article',$data);

    $dou->create_admin_log($_LANG['case_add'] . ': ' . $_POST['title']);
    $dou->dou_msg($_LANG['case_add_succes'], 'case.php');
}

/**
 * +----------------------------------------------------------
 * 案例编辑
 * +----------------------------------------------------------
 */
elseif ($rec == 'edit') {
    $smarty->assign('ur_here', $_LANG['case_edit']);
    $smarty->assign('action_link', array(
            'text' => $_LANG['case'],
            'href' => 'case.php'
    ));

    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : '';

    $query = $dou->select($dou->table('case'), '*', '`id` = \'' . $id . '\'');
    $case = $dou->fetch_array($query);

    // 格式化自定义参数
    if ($_DEFINED['case'] || $case['defined']) {
        $defined = explode(',', $_DEFINED['case']);
        foreach ($defined as $row) {
            $defined_case .= $row . "：\n";
        }
        // 如果案例中已经写入自定义参数则调用已有的
        $case['defined'] = $case['defined'] ? str_replace(",", "\n", $case['defined']) : trim($defined_case);
        $case['defined_count'] = count(explode("\n", $case['defined'])) * 2;
    }

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('form_action', 'update');
    $smarty->assign('case_category', $dou->get_category_nolevel('case_category'));
    $smarty->assign('case', $case);

    $smarty->display('case.htm');
}

elseif ($rec == 'update') {
    // 验证标题
    if (empty($_POST['title'])) $dou->dou_msg($_LANG['case_name'] . $_LANG['is_empty']);

    // 图片上传
    if ($_FILES['image']['name'] != '') {
        $image_name = $img->upload_image('image', $img->create_file_name('case', $_POST['id'], 'image'));
        $image = $images_dir . $image_name;
        // $img->make_thumb($image_name, $_CFG['thumb_width'], $_CFG['thumb_height']);
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
            'keywords'  => $_POST['keywords'],
            'description'  => $_POST['description'],
        );
    // if ($image)
    //     $data['image'] = $image;
    $res = $dou->update('case',$data,"id='$_POST[id]'");

    $dou->create_admin_log($_LANG['case_edit'] . ': ' . $_POST['title']);
    $dou->dou_msg($_LANG['case_edit_succes'], 'case.php');
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
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'case.php');

    $max_sort = $dou->get_one("SELECT sort FROM " . $dou->table('case') . " ORDER BY sort DESC");
    $new_sort = $max_sort + 1;
    $dou->query("UPDATE " . $dou->table('case') . " SET sort = '$new_sort' WHERE id = '$id'");

    $dou->dou_header($_SERVER['HTTP_REFERER']);
}

/**
 * +----------------------------------------------------------
 * 取消首页显示商品
 * +----------------------------------------------------------
 */
elseif ($rec == 'del_sort') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'case.php');

    $dou->query("UPDATE " . $dou->table('case') . " SET sort = '' WHERE id = '$id'");
    $dou->dou_header($_SERVER['HTTP_REFERER']);
}

/**
 * +----------------------------------------------------------
 * 案例删除
 * +----------------------------------------------------------
 */
elseif ($rec == 'del') {
    // 验证并获取合法的ID
    $id = $check->is_number($_REQUEST['id']) ? $_REQUEST['id'] : $dou->dou_msg($_LANG['illegal'], 'case.php');
    $title = $dou->get_one("SELECT title FROM " . $dou->table('case') . " WHERE id = '$id'");

    if (isset($_POST['confirm']) ? $_POST['confirm'] : '') {
        // 删除相应商品图片
        $image = $dou->get_one("SELECT image FROM " . $dou->table('case') . " WHERE id = '$id'");
        $dou->del_image($image);

        $dou->create_admin_log($_LANG['case_del'] . ': ' . $title);
        $dou->delete($dou->table('case'), "id = $id", 'case.php');
    } else {
        $_LANG['del_check'] = preg_replace('/d%/Ums', $title, $_LANG['del_check']);
        $dou->dou_msg($_LANG['del_check'], 'case.php', '', '30', "case.php?rec=del&id=$id");
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
            // 批量案例删除
            $dou->del_all('case', $_POST['checkbox'], 'id', 'image');
        } elseif ($_POST['action'] == 'category_move') {
            // 批量移动分类
            $dou->category_move('case', $_POST['checkbox'], $_POST['new_cat_id']);
        } else {
            $dou->dou_msg($_LANG['select_empty']);
        }
    } else {
        $dou->dou_msg($_LANG['case_select_empty']);
    }
}

/**
 * +----------------------------------------------------------
 * 获取首页显示案例
 * +----------------------------------------------------------
 */
function get_sort_case() {
    $limit = $GLOBALS['_DISPLAY']['home_case'] ? ' LIMIT ' . $GLOBALS['_DISPLAY']['home_case'] : '';
    $sql = "SELECT id, title FROM " . $GLOBALS['dou']->table('case') . " WHERE sort > 0 ORDER BY sort DESC" . $limit;
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