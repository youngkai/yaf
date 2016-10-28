<?php
/**
 * @name IndexController
 * @author yangkai\huoyunren
 * @desc 默认控制器
 * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
 */
use log\Log;


class IndexController extends Yaf_Controller_Abstract {

	/** 
     * 默认动作
     * Yaf支持直接把Yaf_Request_Abstract::getParam()得到的同名参数作为Action的形参
     * 对于如下的例子, 当访问http://yourhost/firstyaf/index/index/index/name/yangkai\huoyunren 的时候, 你就会发现不同
     */
	public function indexAction() {

        $msg = 'youngk';

        $this->getView()->assign('name',$msg);

        Log::trance($msg);



        echo 'hello,world';

		//4. render by Yaf, 如果这里返回FALSE, Yaf将不会调用自动视图引擎Render模板
        //return TRUE;
	}
}
