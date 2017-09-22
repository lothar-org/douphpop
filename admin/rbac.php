<?php
define('IN_LOTHAR', true);
define('CMOD', 'rbac');
require (dirname(__FILE__) . '/include/init.php');

// rec操作项的初始化
$rec = $check->is_rec($_REQUEST['rec']) ? $_REQUEST['rec'] : 'default';

$smarty->assign('rec', $rec);
$smarty->assign('cur', CMOD);

$ur_here = '<a href="rbac.php">权限管理</a>';

$gUid = intval($_USER['user_id']);
if (empty($gUid)) $dou->dou_msg('非法操作！');

switch ($rec) {
case 'ajax':
    // AJAX 选择、提交
    $mark = isset($_POST['mark'])?intval($_POST['mark']):'';
    $rid = intval($_POST['rid']);
    if (empty($rid)) {echo null;exit;}
    $lokey = $_POST['lokey'];
    if ($lokey=='uid') {
        $table = $dou->table('rbac_awr');
        $r_code = $dou->get_one('select code from '.$dou->table('rbac_role').' where rid='.$rid);
        switch ($r_code) {
            case 'ALL':$a_level=99;break;
            case 'ADMIN':$a_level=50;break;
            case 'LHJ':$a_level=10;break;
        }
    } elseif ($lokey=='mid') {
        $table = $dou->table('rbac_rwm');
    }
    switch($mark) {
        case 1:
            $sql ='select '.$lokey.' from '.$table." where rid=".$rid;
            $attr = $dou->fetchAll($sql);
            if (empty($attr)){echo null;exit;}
            $str = '';
            foreach((array)$attr as $v){
                $str .= implode('^',$v).'|';
            }
            echo substr($str,0,strlen($str)-1);exit;
        break;
        case 2:
            $rlabel = $_POST['rlabel'];
            $arr = explode('|',$rlabel);
            if (empty($arr)){echo null;exit;}
            $sdel = "delete from ".$table." where rid='{$rid}'";
            $dou->query($sdel);
            $sql = 'INSERT INTO '.$table.' ('.$lokey.',rid) VALUES ';
            foreach((array)$arr as $v) {
                $sql .= "('$v','$rid'),";
                $sqluid .= "$v,";
                echo $v;
            }
            $sql = substr($sql,0,strlen($sql)-1);
            $sqluid = substr($sqluid,0,strlen($sqluid)-1);
            // echo $sql;die;
            $dou->query($sql);
            if ($a_level) {
                $dou->query('update '.$dou->table('admin').' set action_level='.$a_level.' where user_id in ('.$sqluid.');');
            }
            exit();
        break;
    }
    break;

case 'default':// 角色列表
    // echo $rec;die;
    $smarty->assign('ur_here', $ur_here.'>角色列表');
    $smarty->assign('ur_index', '角色列表');
    if ($_USER['action_level']==99) {
        $where = '';
    } elseif($_USER['action_level']==50) {
        $where = "WHERE code='LHJ'";
    }
    $roles = array();
    if ($_USER['action_level']>=50) {
        $smarty->assign('action_link', array(
                'text' => '角色增加',
                'href' => 'rbac.php?rec=op'
        ));
        // 角色列表
        $roles = $dou->fetchAll(sprintf('SELECT * from %s %s',$dou->table('rbac_role'),$where));
        // $dou->debug($roles,1);
    }

    // 赋值给模板
    $smarty->assign('cur_tab', 'role');
    $smarty->assign('roles', $roles);

    $smarty->display('rbac/role.htm');
    break;
case 'op':
    $loid = $check->is_number($_GET['loid'])?intval($_GET['loid']):0;

    if ($loid) {
        $role = $dou->fetchRow('SELECT * from '.$dou->table('rbac_role').' where rid='.$loid);
        $smarty->assign('role',$role);
        $ur_here_c = '修改';
    } else {
        if ($_USER['action_level']==99) {
            // 超管可以不限死
            $action_level = 'ADMIN';
        } else {
            $action_level = 'LHJ';
        }
        $smarty->assign('action_level',$action_level);
        $ur_here_c = '新增';
    }
    $smarty->assign('ur_here', $ur_here.'>'.$ur_here_c.'角色');
    $smarty->assign('ur_index', $ur_here_c.'角色');
    $smarty->assign('action_link', array(
            'text' => '角色列表',
            'href' => 'rbac.php'
    ));

    // CSRF防御令牌生成
    $smarty->assign('token', $firewall->get_token());

    // 赋值给模板
    $smarty->assign('cur_tab', 'role');
    $smarty->assign('roles', $roles);

    $smarty->display('rbac/role.htm');
    break;
case 'form':
    $loid = $check->is_number($_GET['loid'])?intval($_GET['loid']):0;
    // var_dump($_GET);
    // var_dump($_POST);
    // die;
    // CSRF防御令牌验证
    $firewall->check_token($_POST['token']);

    if ($loid) {
        $dou->query("UPDATE ".$dou->table('rbac_role')." SET code='$_POST[code]',name='{$_POST['name']}'");
    } else {
        $dou->query("INSERT INTO ".$dou->table('rbac_role')." (code,name) VALUES ('$_POST[code]','{$_POST['name']}')");
    }

    $dou->dou_msg('提交成功！','rbac.php');
    break;
case 'del':
    $loid = $check->is_number($_GET['loid'])?intval($_GET['loid']):$dou->dou_msg('ID非法');
    $dou->delete($dou->table('rbac_role'),'rid='.$loid,'rbac.php');
    break;

case 'module':
    # code...
    break;
case 'module_op':
    # code...
    break;
case 'module_form':
    # code...
    break;



case 'awr':// 用户与角色关系表
    // 开始
    $smarty->assign('ur_here', $ur_here.'>角色与用户');
    $smarty->assign('ur_index', '角色与管理者的关系');

    $loid = $check->is_number($_GET['loid'])?intval($_GET['loid']):0;

    if ($_USER['action_level']==99) {
        $where = 'WHERE code!=\'ALL\'';
        $where2 = "WHERE user_id!={$gUid}";
    } elseif($_USER['action_level']==50) {
        $where = "WHERE code='LHJ'";
        $where2 = "WHERE pid={$gUid} AND user_id!={$gUid}";
    }
    $roles = $users = array();
    if ($_USER['action_level']>=50) {
        // 角色列表
        $roles = $dou->fetchAll(sprintf('SELECT * from %s %s',$dou->table('rbac_role'),$where));
        // $dou->debug($roles,1);
        // 显示相应所拥有管理者
        $users = $dou->fetchAll(sprintf('SELECT user_id,user_name,action_level from %s %s',$dou->table('admin'),$where2));
    }

    // 赋值给模板
    $smarty->assign('cur_tab', 'awr');
    $smarty->assign('loid', $loid);
    $smarty->assign('lokey', 'uid');
    $smarty->assign('roles', $roles);
    $smarty->assign('users', $users);

    $smarty->display('rbac/awr.htm');
    break;
case 'awr_form':
    # 如果有 AJAX 则不需要
    break;



case 'rwm':// 角色与模块关系表
    // 开始
    $smarty->assign('ur_here', $ur_here.'>角色与模块');
    $smarty->assign('ur_index', '角色与模块关系');

    $loid = $check->is_number($_GET['loid'])?intval($_GET['loid']):0;

    if ($_USER['action_level']==99) {
        $where = 'WHERE code!=\'ALL\'';
    } elseif($_USER['action_level']==50) {
        $where = "WHERE code='LHJ'";
    }
    $roles = $modules = array();
    if ($_USER['action_level']>=50) {
        // 角色列表
        $roles = $dou->fetchAll(sprintf('SELECT * from %s %s',$dou->table('rbac_role'),$where));
        // $dou->debug($roles,1);
        // 显示所有模块
        $modules = $dou->fetchAll(sprintf('SELECT mid,code,name from %s',$dou->table('rbac_module')));
    }

    // 赋值给模板
    $smarty->assign('cur_tab', 'rwm');
    $smarty->assign('loid', $loid);
    $smarty->assign('lokey', 'mid');
    $smarty->assign('roles', $roles);
    $smarty->assign('modules', $modules);

    $smarty->display('rbac/rwm.htm');
    break;
case 'rwm_form':
    # code...
    break;

}
?>