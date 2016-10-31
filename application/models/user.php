<?php
/**
 * Created by PhpStorm.
 * User: Huoyunren
 * Date: 2016/10/31
 * Time: 16:33
 */

use model\phpmodel;

class userModel extends phpmodel{

    private $model;

    public $tableName = 'user';

    public function __construct(){

        $this->model = new phpmodel();

    }


    public function search(){

        return $this->model->find(["*",'table'=>'user','model'=>'user']);

        //return "haha";
    }


}