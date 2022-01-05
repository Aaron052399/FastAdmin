<?php

namespace app\index\controller;

use app\common\controller\Frontend;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index()
    {
        // 阻断跳转到前台页面，跳往后台登陆页面
        return $this->redirect('/index/login');
//        return $this->view->fetch();
    }

}
