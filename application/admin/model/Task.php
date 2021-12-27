<?php

namespace app\admin\model;

use think\Model;


class Task extends Model
{


    // 表名
    protected $name = 'task';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    protected $task_name_arr = [
        [
            'tk' => '抖音',
            'ks' => '快手',
            'hy' => '虎牙',
            'dy' => '斗鱼',
        ],
        [
            'live' => '直播',
            'vlog' => '视频',
        ],
        [
            'enter' => '进入'
        ]
    ];

    // 追加属性
    protected $append = [
        'pubtime_text',
        'endtime_text'
    ];

    public function getPubtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pubtime']) ? $data['pubtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['endtime']) ? $data['endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPubtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEndtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function getTaskCodeAttr($value, $data)
    {
        $taskcode = json_decode($data['taskcode'], true);
        return empty($taskcode[0]['verify_key']) ? empty($taskcode[0]['search_key']) ? empty($taskcode[0]['search_nickname']) ? $taskcode[0]['search_homepage'] : $taskcode[0]['search_nickname'] : $taskcode[0]['search_key'] : $taskcode[0]['verify_key'];
    }

    public function getBusitypeAttr($value, $data)
    {
        return $value == 0 ? '补贴任务' : '订单任务';
    }

    public function getTasknameAttr($value, $data)
    {
        $value = explode('.', $value);
        return $this->task_name_arr[0][$value[0]] . $this->task_name_arr[1][$value[1]] . '任务';
    }

    public function getStatusAttr($value, $data)
    {
        $status_name = '';
        switch ($value) {
            case 1:
                $status_name = '待执行';
                break;
            case 2:
                $status_name = '执行中';
                break;
            case 3:
                $status_name = '已完成';
                break;
            case -99:
                $status_name = '已下架';
                break;
            case -98:
                $status_name = '已终止';
                break;
        }

        return $status_name;
    }

    public function getTasktypeAttr($value, $data)
    {
        return $this->task_name_arr[0][strtolower($value)];
    }

//    public function getCommentfileAttr($value, $data)
//    {
//        return "http://api.morsx.cn:8043/download_ss?name=task_". $data['id'];
//    }

}
