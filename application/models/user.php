<?php
/**
 * Created by PhpStorm.
 * User: Huoyunren
 * Date: 2016/10/31
 * Time: 16:33
 */

use model\Phpmodel;

class userModel extends Phpmodel{


    public $tableName = 'user';


    public function search(){

        return $this->query("select * from user where UserId=:id",array(':id'=>1));

    }
}