<?php
/*
|---------------------------------------------------------------
|  Copyright (c) 2016
|---------------------------------------------------------------
| 文件名称：数据库连接池类
| 功能 :用户信息操作
| 作者：qieangel2013
| 联系：qieangel2013@gmail.com
| 版本：V1.0
| 日期：2016/3/25
|---------------------------------------------------------------
*/
class DbServer
{
    public static $instance;
    public static $link=null;
    public $http;
    private $application;
    private $config;
    private $Serconfig;
    private $isasync;
    private $multiprocess;
    protected $pool_size = 20;
    protected $idle_pool = array(); 
    protected $busy_pool = array(); 
    protected $wait_queue = array(); 
    protected $wait_queue_max = 100; 
    public function __construct() {
        define('APPLICATION_PATH', dirname(dirname(__DIR__)). "/application");
        define('MYPATH', dirname(APPLICATION_PATH));
        $this->application = new Yaf_Application(dirname(APPLICATION_PATH). "/conf/application.ini");
        $this->application->bootstrap();
        $config_obj=Yaf_Registry::get("config");
        $this->config=$config_obj->database->config->toArray();
        $this->Serconfig=$config_obj->DbServer->toArray();
        $this->pool_size=isset($this->Serconfig['pool_num'])?$this->Serconfig['pool_num']:20;
        $this->isasync=isset($this->Serconfig['async'])?$this->Serconfig['async']:true;
        $this->multiprocess=isset($this->Serconfig['multiprocess'])?$this->Serconfig['multiprocess']:false;
        $this->http = new swoole_server("0.0.0.0", $this->Serconfig['port']);
        if($this->isasync){
            $this->http->set(
            array(
            'worker_num' => 1,
            //'task_worker_num' => 10,
            'max_request' => 0,
            'daemonize' => true,
            'dispatch_mode' => 1,
            'log_file' => $this->Serconfig['logfile']
            )
            );
        }else{
            $this->http->set(
            array(
            'worker_num' => 10,
            'task_worker_num' => $this->pool_size,
            'max_request' => 0,
            'daemonize' => true,
            'dispatch_mode' => 1,
            'log_file' => $this->Serconfig['logfile']
            )
        );
        }
        
         //$this->process = new swoole_process(function($process) use($http) {
            /*for ($i = 0; $i < $this->pool_size; $i++) {
            $db = new mysqli;
            $db->connect($this->config['host'],$this->config['user'],$this->config['pwd'],$this->config['name']);
            //设置数据库编码
            $db->query("SET NAMES '".$this->config['charset']."'");
            $db_sock = swoole_get_mysqli_sock($db);
            swoole_event_add($db_sock, array($this, 'onSQLReady'));
            $this->idle_pool[] = array(
                'mysqli' => $db,
                'db_sock' => $db_sock,
                'fd' => 0,
            );
        }*/
           // print_r($this->idle_pool);
            //print_r($this->taskid);
           //$http->sendMessage('zqf \n',0);
       //});
         //$this->process->start();
        //$http->addProcess($this->process);
        if($this->isasync){
            $this->http->on('WorkerStart',array(&$this , 'onStart'));
        }else{
            $this->http->on('Task',array(&$this , 'onTask'));
            $this->http->on('Finish',array(&$this , 'onFinish'));
        }
        //$http->on('pipeMessage',array($this , 'onpipeMessage'));
        
        $this->http->on('Receive',array(&$this , 'onReceive'));
        $this->http->start();
    }
     public function onStart($serv)
    {
            for ($i = 0; $i < $this->pool_size; $i++) {
            $db = new swoole_mysql;
            $db->connect(array('host' => $this->config['host'],'user' => $this->config['user'],'password' => $this->config['pwd'],'database' => $this->config['name']),function($db,$result){
                //var_dump($result);
                //print_r($db);
            });
            //设置数据库编码
            $db_sock = $db->sock;
            //print_r($db->sock);
            //swoole_event_add($db_sock, array(&$this, 'onSQLReady'));
            /*$db->query("SET NAMES '".$this->config['charset']."'",function($link,$result){
                var_dump($result);
            });*/
            $this->idle_pool[] = array(
                'mysqli' => $db,
                'db_sock' => $db_sock,
                'fd' => 0,
            );
        }        

    }
     public function onpipeMessage($serv, $src_worker_id, $data)
    {
       //echo "#{$serv->worker_id} message from #$src_worker_id: $data\n";
       
        //$this->idle_pool=json_decode($data,true);

    }
    public function doSQL($link,$result)
    {
            $data_select=array('status' =>'ok','error'=>0,'errormsg'=>'','result'=>'');
            $db_sock=$link->sock;
            $db_res = $this->busy_pool[$db_sock];
            $mysqli = $db_res['mysqli'];
            $fd = $db_res['fd'];
             if($result){
                if(is_array($result)){
                    $data_select['result']=$result;
                    $this->http->send($fd,json_encode($data_select));
                }else{
                    $data_select['result']=$link->affected_rows;
                    $this->http->send($fd,json_encode($data_select));
                }
            }else{
                $data_select['error']=1;
                $data_select['status']='error';
                $data_select['result']=array();
                $this->http->send($fd,json_encode($data_select));
            }
            array_unshift($this->idle_pool,$db_res);
            unset($this->busy_pool[$db_sock]);
            //$data_select=array('status' =>'ok','error'=>0,'errormsg'=>'','result'=>'');
            //这里可以取出一个等待请求
            if (count($this->wait_queue) > 0) {
                $idle_n = count($this->idle_pool);
                for ($i = 0; $i < $idle_n; $i++) {
                    $req = array_shift($this->wait_queue);
                    $this->doQuery($req['fd'], $req['sql']);
                }
            }
       
        
        /*if ($result = $mysqli->reap_async_query()) {
             if (is_object($result)){
                        $data_result=$result->fetch_all(MYSQLI_ASSOC);
                        mysqli_free_result($result);
                    } else {
                        $data_result=$result;
                    }

            $data_select['result']=$data_result;
            $this->http->send($fd,json_encode($data_select));
        } else {
            $data_select['error']=1;
            $data_select['status']='error';
           // $data_select['errormsg']=sprintf("MySQLi Error: %s\n", mysqli_error($mysqli));
            $data_select['result']=array();
            $this->http->send($fd,json_encode($data_select));
        }*/
        //release mysqli object
        //$this->idle_pool[] = $db_res;
        
        
    }

    public function onReceive($serv, $fd, $from_id, $data)
    {
        if($this->isasync){
            if (count($this->idle_pool) == 0) {
            //等待队列未满
            if (count($this->wait_queue) < $this->wait_queue_max) {
                $this->wait_queue[] = array(
                    'fd' => $fd,
                    'sql' => $data,
                );
                } else {
                    $this->http->send($fd, "request too many, Please try again later.");
                }
            } else {
                    $this->doQuery($fd, $data);
            }
        }else{
            if($this->multiprocess){
                $result = $this->http->task($data);
            }else{
                 $result = $this->http->taskwait($data);
            }
            $data_resp=array('status' =>'ok','error'=>0,'errormsg'=>'','result'=>'');
            if ($result !== false)
            {
                $data_resp['result']=$result;
                $this->http->send($fd,json_encode($data_resp));
             }   
            else
            {
                $data_resp['error']=1;
                $data_resp['status']='error';
                //$data_resp['errormsg']=sprintf("MySQLi Error: %s\n", mysqli_error($mysqli));
                $data_resp['result']=array();
                $this->http->send($fd,json_encode($data_resp));
            }
        }
    }
    
    public function onTask($serv, $task_id, $from_id, $sql)
    {
         if (!self::$link) {
            self::$link = new mysqli;
            self::$link->connect($this->config['host'],$this->config['user'],$this->config['pwd'],$this->config['name']);
            //设置数据库编码
            self::$link->query("SET NAMES '".$this->config['charset']."'");
        }
        for ($i = 0; $i < 2; $i++) {
            $result = self::$link->query($sql);
            if ($result === false) {
                if (self::$link->errno == 2013 or self::$link->errno == 2006) {
                    self::$link->close();
                    $r = self::$link->connect();
                    //设置数据库编码
                    self::$link->query("SET NAMES '".$this->config['charset']."'");
                    if ($r === true) continue;
                }
            }
            break;
        }
        //var_dump($sql);
        //print_r(self::$link);
        if (is_object($result)){
            $data=$result->fetch_all(MYSQLI_ASSOC);
            mysqli_free_result($result);
        } else {
            $data=$result;
        }
        return $data;
    }

    public function doQuery($fd, $sql)
    {
        //从空闲池中移除
        $db = array_pop($this->idle_pool);
        /**
         * @var mysqli
         */
        $mysqli = $db['mysqli'];
        $mysqli->query($sql,array(&$this, 'doSQL'));
        /*for ($i = 0; $i < 2; $i++) {
            $result = $mysqli->query($sql, MYSQLI_ASYNC);
            if ($result === false) {
                if ($mysqli->errno == 2013 or $mysqli->errno == 2006) {
                    $mysqli->close();
                    $r = $mysqli->connect();
                    if ($r === true) continue;
                }
            }
            break;
        }*/
            $db['fd'] = $fd;
            //加入工作池中
            $this->busy_pool[$db['db_sock']] = $db;
    }
     public function onFinish($serv, $data)
    {

    }
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new DbServer;
        }
        return self::$instance;
    }
}
DbServer::getInstance();