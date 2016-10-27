<?php

class Base {

    /**
     * 设计模式之单例模式
     * $_instance必须声明为静态的私有变量
     */
//保存例实例在此属性中
    private static $_instance;

//单例方法
    public static function getInstance() {

        $class_name = get_called_class();
        if (!isset(self::$_instance[$class_name])) {

            self::$_instance[$class_name] = new $class_name;
        }

        /*@var $var $class_name*/
        return self::$_instance[$class_name];
    }

//阻止用户复制对象实例
    public function __clone() {
        trigger_error('Clone is not allow', E_USER_ERROR);
    }

}