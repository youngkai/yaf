<?php
/**
 * Created by PhpStorm.
 * User: Huoyunren
 * Date: 2016/10/13
 * Time: 18:30
 */

class TestController extends Yaf_Controller_Abstract {

    private $user;

    public function init(){

        $this->user = new userModel();

    }


    public function indexAction(){
        //$user = new userModel();
        $data = $this->user->getAll();
        //$user->insertInfo(array('name'=>'leexiang333','email'=>'lee333@qq.com'));
        var_dump($data);
    }
}