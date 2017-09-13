<?php
if (!defined('IN_LOTHAR')) die('Hacking attempt');

/**
* Memory 缓存 
* memcache简单使用
* hello memcache!<br>
* 判断是否开启memcache扩展
* extension_loaded('memcache');
* get_loaded_extensions();//查看所有已开启的扩展
* get_extension_funcs('memcache');// 查看此扩展所有方法
* class_exists('memcache');//判断此扩展是否存在
* dl(memcache.dll);
* memcached简单使用
if (extension_loaded('Memcached')) {
    $memoryd = new memcached();
    $memoryd->addServer('127.0.0.1',11211);
    $memoryd->set('memcached','hello memcached!<br>');
    echo 'Memcached扩展开启：<br>';
    echo $memoryd->get('memcached');
} else {
    echo 'Memcached扩展未开启！';
}
*/
class Memory
{
    // const host = '106.14.74.155';
    const host = 'localhost';
    const port = 11211;
    // public $mhost;// 可以不预先定义
    public $mport;
    public $error = 'Memcache can not connect';
    private $memory;

    function __construct($mhost=self::host,$mport=self::port)
    {
        $this->mhost = $mhost;// 用类中定义的变量去接外面传进来的变量
        $this->mport = $mport;
        if ($this->exists()) {
            $this->memory = new Memcache();
            // $this->memory = new Memcache;
            // $this->memory = new memcache();
            // Memcache::setCompressThreshold();// 开启大值自动压缩
            // $this->memory->getVersion();
            $this->memory->connect($this->mhost,$this->mport) or die($this->error);
            // if (!$this->memory->connect($this->mhost,$this->mport)) {
            //     die($this->error);
            // }
        } else {
            // return '';
            throw new Exception('Memcache not exists ');
        }
    }

    /* 如果服务器 端口错误会出现 502 Bad Gateway*/
    public function exists()
    {
        if (extension_loaded('memcache')) {
            // echo 'Memcache扩展开启了！';
            if(!class_exists('Memcache')){
                return false;
            }
            return true;
        } else {
            // echo 'Memcache扩展未开启！';
            return false;
        }
    }

    /**
    * 把数据添加到缓存
    * @param string $mkey 
    * @param array or string $var 
    * 保存数组 $var = array('aaa','bbb','ccc','ddd');
    * @param string $flag MEMCACHE_COMPRESSED
    * @param string $expire 30
    */
    public function set($mkey, $var='', $flag=0, $expire=0)
    {
        return $this->memory->set($mkey,$var,$flag,$expire);
    }

    public function add($mkey,$var='',$flag=0,$expire=0)
    {
        return $this->memory->add($mkey.$var,$flag,$expire);
    }

    public function replace($mkey,$var='',$flag=0,$expire=0)
    {
        return $this->memory->replace($mkey.$var,$flag,$expire);
    }

    public function get($mkey)
    {
        return $this->memory->get($mkey);
    }

    public function rm($mkey)
    {
        return $this->memory->delete($mkey);
    }

    public function clear()
    {
        // return Memcache::flush();
        // return $memory->flush();
        return $this->memory->flush();
    }

    public function close()
    {
        return $this->memory->close();
    }

    public function addServer($host,$port)
    {
        return $this->memory->addServer($host,$port);
    }

    /*memcache_debug()已过时，请使用调试器（如Eclipse + CDT）*/
    public function debug($on_off=false)
    {
        memcache_debug($on_off);
    }
}




/*
* 实现TCP端口检测的方法
* 确认当前端口是否可用
*/
class Health {
    public static $status;
    public function __construct()
    {
    }
    public function check($ip, $port){
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_nonblock($sock);
        socket_connect($sock,$ip, $port);
        socket_set_block($sock);
        self::$status = socket_select($r = array($sock), $w = array($sock), $f = array($sock), 5);
        return(self::$status); 
    }
    public function checklist($lst){
    }
    public function status(){
        switch(self::$status)
        {
            case 2:
            echo "Closed\n";
            break;
            case 1:
            echo "Openning\n";
            break;
            case 0:
            echo "Timeout\n";
            break;
        }
    }
}
// $ip='192.168.2.10';
// $port=80;
// $health = new Health();
// $health->check($ip, $port);
// $health->status();
?>