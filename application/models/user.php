<?php

class userModel extends Db_Mysql{

    public $tablename = 'test';


    public function getAll(){

        return $this->query("select * from test");

    }


    public function insertInfo($args){
        return $this->insert($args);
    }


}