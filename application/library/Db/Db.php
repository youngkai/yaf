<?php

    class db_Db{

        static private  $instance   =  array();     //  数据库连接实例

        static private  $_instance  =  null;   //  当前数据库连接实例

        // 当前连接ID
        protected $_linkID    = null;


        protected $model    =   null;

        // 是否使用永久连接
        protected $pconnect   = false;


        /**
        * 取得数据库类实例
        * @static
        * @access public
        * @param mixed $config 连接配置
        * @return Object 返回数据库驱动类
        */
        static public function getInstance($config=array()) {

            $md5 = md5(serialize($config));

            if(!isset(self::$instance[$md5])) {

                // 解析连接参数 支持数组和字符串
                $options = self::parseConfig($config);

                // 兼容mysqli
                if('mysqli' == $options['type']){

                    $options['type'] = 'mysql';

                }

                // 如果采用lite方式 仅支持原生SQL 包括query和execute方法
                $class  =  'db_'.ucwords(strtolower($options['type']));

                if(class_exists($class)){

                    self::$instance[$md5]   =   new $class($options);

                }else{
                    // 类没有定义
                    throw new Exception("不存在的类");
                }
            }
            self::$_instance    =   self::$instance[$md5];

            return self::$_instance;
        }

        /**
        * 数据库连接参数解析
        * @static
        * @access private
        * @param mixed $config
        * @return array
        */
        static private function parseConfig($config){
            if(!empty($config)){
                if(is_string($config)) {
                    return self::parseDsn($config);
                }
                $config =   array_change_key_case($config);
                $config = array (
                    'type'          =>  $config['type'],
                    'username'      =>  $config['username'],
                    'password'      =>  $config['password'],
                    'hostname'      =>  $config['hostname'],
                    'hostport'      =>  $config['hostport'],
                    'database'      =>  $config['database'],
                    'dsn'           =>  isset($config['dsn'])?$config['dsn']:null,
                    'params'        =>  isset($config['params'])?$config['params']:null,
                    'charset'       =>  isset($config['charset'])?$config['charset']:'utf8',
                    'deploy'        =>  isset($config['deploy_type'])?$config['deploy_type']:0,
                    'rw_separate'   =>  isset($config['rw_separate'])?$config['rw_separate']:false,
                    'master_num'    =>  isset($config['master_num'])?$config['master_num']:1,
                    'slave_no'      =>  isset($config['slave_no'])?$config['slave_no']:'',
                    'lite'          =>  isset($config['lite'])?$config['lite']:false,
                );
            //没有传入配置array，那么需要用Yaf去获取
            }else {

                $_config = \Yaf_Registry::get("config");

                $config = $_config->database->config->toArray();

            }
            return $config;
        }

        /**
        * DSN解析
        * 格式： mysql://username:passwd@localhost:3306/DbName?param1=val1&param2=val2#utf8
        * @static
        * @access private
        * @param string $dsnStr
        * @return array
        */
        static private function parseDsn($dsnStr) {

            if( empty($dsnStr) ){return false;}

            $info = parse_url($dsnStr);

            if(!$info) {

                return false;

            }
            $dsn = array(
                'type'      =>  $info['type'],
                'username'  =>  isset($info['username']) ? $info['username'] : '',
                'password'  =>  isset($info['password']) ? $info['password'] : '',
                'hostname'  =>  isset($info['hostname']) ? $info['hostname'] : '',
                'hostport'  =>  isset($info['hostport']) ? $info['hostport'] : '',
                'database'  =>  isset($info['database']) ? substr($info['database'],1) : '',
                'charset'   =>  isset($info['charset']) ? $info['charset']:'utf8',
            );

            if(isset($info['query'])) {

                parse_str($info['query'],$dsn['params']);

            }else{

                $dsn['params']  =   array();

            }
                return $dsn;
        }


        // 调用驱动类的方法
        static public function __callStatic($method, $params){
            return call_user_func_array(array(self::$_instance, $method), $params);
        }
    }