<?php 
/**
* RBAC 权限管理
*/
class RBAC
{
    // public $oop;
    // const guest = 1;

    // function __construct()
    // {
    //     parent::__construct();
    // }

    /**
    * 权限验证
    * @string $rule 待验证的模块 
    * @string $user 管理者信息
        array (
          'user_id' => '1',
          'user_name' => 'admin',
          'email' => '',
          'action_level' => '99',
        )
    */
    public function access_jump($rule,$user)
    {
        if ($user['action_level']==99) {
            return true;
        }
        $_access = $this->access_check($rule,$user['user_id']);
        if (empty($_access)) {
            $GLOBALS['dou']->dou_msg('权限不足，无法访问！');
        }
        return true;
    }

    /**
    * 权限验证
    * @string $rule 待验证的模块 
    * @string $uid 验证的用户ID
    */
    public function access_check($rule,$uid)
    {
        $allow_module = $this->access_module($uid);
        if (empty($allow_module)) return false;
        foreach ($allow_module as $value) {
            $allow[] = $value['code'];
        }
        // return $allow;
        if (in_array($rule,$allow)) {
            return true;
        }
        return false;
    }

    /**
    * 获取用户合法模块
    * @string $uid 用户ID
    */
    public function access_module($uid)
    {
        //根据用户ID查角色ID,考虑了用户属于不同角色时
        $sql = 'select rid from '.$GLOBALS['dou']->table('rbac_awr')." where uid='{$uid}'";
        $ajs = $GLOBALS['dou']->fetchAll($sql,MYSQL_NUM);
        if (empty($ajs)) return;

        //定义一个存放模块代号的数组
        $arr = array();
        //根据角色代号查模块代号
        foreach($ajs as $vjs) {
            $roleid = $vjs[0]; //角色代号
            $sql = "select mid from ".$GLOBALS['dou']->table('rbac_rwm')." where rid='{$roleid}'";
            $attr = $GLOBALS['dou']->fetchAll($sql);
            $str = '';
            // 获取模块代号字符组
            foreach($attr as $v) {
                $str .= implode('^',$v).'|';// 当count($v)==1时，implode('^',$v)相当于$v[0]或$v['mid']
                // $str .= $str?'|'.$v[0]:$v[0];// 不建议在循环体内做判断
            }
            // return $str;
            $strgn = substr($str,0,strlen($str)-1);// 删掉最后一个 '|'
            // 将模块字符组变成数组
            $agn = explode('|',$strgn);
            foreach($agn as $vgn) {
                array_push($arr,$vgn);
            }
        }

        //去重
        $arr = array_unique($arr);
        // $GLOBALS['dou']->debug($arr,1);

        // 结果集：第一种
        $sqlin = implode(',', $arr);
        $sql = 'SELECT code,name from '. $GLOBALS['dou']->table('rbac_module') .' where mid in ('.$sqlin.')';
        $rules = $GLOBALS['dou']->fetchAll($sql);

        // 结果集：第二种
        // foreach($arr as $v) {
        //     $sql = "select code,name from ".$GLOBALS['dou']->table('rbac_module')." where mid='{$v}'";
        //     $attr = $GLOBALS['dou']->fetchAll($sql,MYSQL_ASSOC);
        //     foreach ($attr as $val) {
        //         // $val['name'] = iconv('gb2312','utf-8',$val['name']);
        //         $rules[] = $val;
        //     }
        // }

        // $GLOBALS['dou']->debug($rules,1);
        return $rules;
    }
}
?>