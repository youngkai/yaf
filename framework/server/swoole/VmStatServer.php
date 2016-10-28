<?php
/*
|---------------------------------------------------------------
|  Copyright (c) 2016
|---------------------------------------------------------------
| 作者：qieangel2013
| 联系：qieangel2013@gmail.com
| 版本：V1.0
| 日期：2016/4/25
|---------------------------------------------------------------
*/
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);
class VmStatServer
{
	public static $instance;
	private $application;
	private $vmstat_handle;
	private $process;
	private $server;
	public function __construct() {
		define('APPLICATION_PATH', dirname(dirname(__DIR__)). "/application");
		define('MYPATH', dirname(APPLICATION_PATH));
		$this->application = new Yaf_Application(dirname(APPLICATION_PATH). "/conf/application.ini");
		$this->application->bootstrap();
		$config_obj=Yaf_Registry::get("config");
		$vmstat_config=$config_obj->vmstat->toArray();
		$this->server = new swoole_websocket_server($vmstat_config['ServerIp'], $vmstat_config['port']);
		if(isset($vmstat_config['logfile'])){
			$this->server->set(
			array(
				'worker_num' => 1,
				'daemonize' => true,
				'log_file' => $vmstat_config['logfile']
			)
			);
		}else{
			$this->server->set(
			array(
				'worker_num' => 1,
				'daemonize' => true
			)
			);
		}
		
		$this->process = new swoole_process(array(&$this,'vmstata_call'),true);
		$this->process->name('vmstat监控服务器');
        $this->process->start();
		//$this->server->addProcess($this->process);
		$this->server->on('Start',array(&$this , 'onStart'));
		$this->server->on('Open',array(&$this , 'onOpen'));

		$this->server->on('Message',array(&$this , 'onMessage'));

		$this->server->on('Close',array(&$this , 'onClose'));

		$this->server->start();
	}

	public function onStart($server) {
			$pro_vs=$this->process;
			$server=$this->server;
			$application_vm=$this->application;
			swoole_event_add($pro_vs->pipe,function($pipe) use($pro_vs,$server,$application_vm){
				$str = $pro_vs->read();
				ob_start();
				$application_vm->execute(array('swoole_socket','getfd'),'vmstat');
				$result = ob_get_contents();
				ob_end_clean();
				$result_fd=json_decode($result,true);
				foreach($result_fd as $id=>$fd){
        			$server->push($fd,$str);
    			}
			});
	}

	public function vmstata_call($worker) {
			$worker->exec('/usr/bin/vmstat', array(1,1000000000));
	}

	public function onOpen($server, $req) {
		$this->application->execute(array('swoole_socket','savefd'),$req->fd,'vmstat');
		$this->server->push($req->fd, "procs -----------memory---------- ---swap-- -----io---- -system-- ----cpu----\n");
		$this->server->push($req->fd, "r  b   swpd   free   buff  cache   si   so    bi    bo   in   cs us sy id wa\n");
	}

	public function onMessage($server, $frame) {
    	
	}
	public function onClose($server, $fd) {
		//@shell_exec('killall vmstat');
		$this->application->execute(array('swoole_socket','removefd'),$fd,'vmstat');
	}
	public static function getInstance() {
		if (!self::$instance) {
            self::$instance = new VmStatServer;
        }
        return self::$instance;
	}
}

VmStatServer::getInstance();
