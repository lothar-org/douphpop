<?php 
/**
* RBAC
*/
class RBAC
{
    public $oop;
    const guest = 1;
    // function __construct()
    // {
    //     parent::__construct();
    // }

    /**
    * 权限验证
    * @string $rule 验证的模块 
    * @string $uid 验证的用户ID
    */
    public function access_check($rule='user',$uid=1)
    {
        //根据用户名查角色
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

        //去重，显示
        $arr = array_unique($arr);
        // $GLOBALS['dou']->debug($arr,1);
        foreach($arr as $v) {
            $sql = "select code,name from ".$GLOBALS['dou']->table('rbac_module')." where mid='{$v}'";
            $attr = $GLOBALS['dou']->fetchAll($sql,MYSQL_ASSOC);
            // $attr_code = iconv('gb2312', 'utf-8', $attr[0][0]);
            // $attr_name = iconv('gb2312', 'utf-8', $attr[0][1]);
            foreach ($attr as $val) {
                // $val['name'] = iconv('gb2312','utf-8',$val['name']);
                $rules[] = $val;
            }
        }
        // $GLOBALS['dou']->debug($rules,1);
        return $rules;
    }
}

?>