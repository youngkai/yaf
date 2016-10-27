<?php
use test\Test1;
/**
 * @name SampleModel
 * @desc sample数据获取类, 可以访问数据库，文件，其它系统等
 * @author yangkai\huoyunren
 */
class SampleModel {

    public function selectSample() {
        return 'Hello World!';
    }

    public function insertSample($arrInfo) {
        return true;
    }

    public function tt1(){
        $t = new Test();
        $d = new Demo1();
        $t_str = $t->tt();
        $d_str = $d->mydemo();
        return $d_str.$t_str;
        //$test = new Test1();
        //$test = new test_test1();
        //return $test->tt();
    }
}
