<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\User;
use app\common\controller\Backend;
use app\common\model\Attachment;
use fast\Date;
use think\Db;

/**
 * 控制台
 *
 * @icon   fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        try {
            \think\Db::execute("SET @@sql_mode='';");
        } catch (\Exception $e) {

        }
        $column = [];
        $starttime = Date::unixtime('day', -6);
        $endtime = Date::unixtime('day', 0, 'end');

        for ($time = $starttime; $time <= $endtime;) {
            $column[] = date("Y-m-d", $time);
            $time += 86400;
        }

        $dbTableList = Db::query("SHOW TABLE STATUS");
        $this->view->assign([
            'totaladdon'      => count(get_addon_list()),
            'totaladmin'      => Admin::count(),
            'totalcategory'   => \app\common\model\Category::count(),
            'totaluser'       => User::count(),
            'dbtablenums'     => count($dbTableList),
            'dbsize'          => array_sum(array_map(function ($item) {
                return $item['Data_length'] + $item['Index_length'];
            }, $dbTableList)),
            'attachmentnums'  => Attachment::count(),
            'attachmentsize'  => Attachment::sum('filesize'),
            'picturenums'     => Attachment::where('mimetype', 'like', 'image/%')->count(),
            'picturesize'     => Attachment::where('mimetype', 'like', 'image/%')->sum('filesize'),
        ]);


        return $this->view->fetch();
    }

}
