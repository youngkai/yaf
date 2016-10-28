<?php


//启动分布式服务器，获取单例
require_once str_replace('\\', '/', dirname(dirname(__DIR__)).'/distributed/server/DistributedServer.php');

DistributedServer::getInstance();
?>
