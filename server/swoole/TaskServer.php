<?php
/*
|---------------------------------------------------------------
|  Copyright (c) 2016
|---------------------------------------------------------------
| 作者：qieangel2013
| 联系：qieangel2013@gmail.com
| 版本：V1.0
| 日期：2016/3/25
|---------------------------------------------------------------
*/
class TaskServer
{
	public static $instance;
	private $application;
	public function __construct() {
		define('APPLICATION_PATH', dirname(dirname(__DIR__)). "/application");
		define('MYPATH', dirname(APPLICATION_PATH));
		$this->application = new Yaf_Application(dirname(APPLICATION_PATH). "/conf/application.ini");
		$this->application->bootstrap();
		$config_obj=Yaf_Registry::get("config");
		$task_config=$config_obj->task->toArray();
		$server = new swoole_server($task_config['ServerIp'], $task_config['port']);
		if(isset($task_config['logfile'])){
			$server->set(
			array(
				'worker_num'  => 8,
				'daemonize' => true,
				'task_worker_num' => 8,
				'log_file' => $task_config['logfile']
			)
			);
		}else{
			$server->set(
			array(
				'worker_num'  => 8,
				'daemonize' => true,
				'task_worker_num' => 8
			)
			);
		}
		

		$server->on('Receive',array($this , 'onReceive'));

		$server->on('Task',array($this , 'onTask'));

		$server->on('Finish',array($this , 'onFinish'));

		$server->start();
	}

	public function onReceive($serv, $fd, $from_id, $data) {
		$param = array(
            'fd' => $fd,
            'data'=>json_decode($data, true)
        );
        // start a task
        $serv->task(json_encode($param));
	}
	public function onTask($serv, $task_id, $from_id, $data) {
        $fd = json_decode($data, true);
        $tmp_data=$fd['data'];
        $this->application->execute(array('swoole_task','demcode'),$tmp_data);
        $serv->send($fd['fd'] , "Data in Task {$task_id}");
        return  'ok';
	}
	public function onFinish($serv, $task_id, $data) {
		echo "Task {$task_id} finish\n";
        echo "Result: {$data}\n";
	}
	public static function getInstance() {
		if (!self::$instance) {
            self::$instance = new TaskServer;
        }
        return self::$instance;
	}
}

TaskServer::getInstance();
