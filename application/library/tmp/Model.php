<?php

class Model extends Base {

//主键名称
    public $primaryKey = 'id';
    public $orderKey = 'id';
//数据表字段 字段名 => 字段初始值
    public $fileds = array();
//只写缓存
    protected $onlyCache = false;
//数据表名称
    protected $table;
    protected $readDb = false;
    protected $cacheObj;
    protected $dbObj;



    function __construct() {
        $this->init();
    }

    public function init() {
        $this->table = str_replace('Model', '', get_called_class());
        $this->dbObj = Db_Pdo::getInstance();

//导入db配置
        $config = Common::getConfig('database');

        $this->dbObj->loadConfig($config);
    }



    /**
     * 新增数据
     * @param type $parmas
     * @return type
     */
    public function add($parmas) {
        if (!$parmas || !is_array($parmas))
            return false;

        $parmas = $this->initFields($parmas);

        if (!$parmas)
            return false;

        $time = date("Y-m-d H:i:s");
        $parmas['create_time'] = $time;
        $parmas['update_time'] = $time;
        $id = $this->insertDB($parmas);

        if (!$id) {
            return false;
        }

        return $id;
    }

    /**
     * db新增
     * @param type $parmas
     * @return boolean
     */
    private function insertDB($parmas) {

        if ($this->onlyCache) {
            return true;
        }

        if (!$parmas || !is_array($parmas)) {
            return false;
        }

        $ret = $this->dbObj->insert($this->table, $parmas);

        return $ret;
    }

}

