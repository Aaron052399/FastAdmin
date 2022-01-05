<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use app\common\library\Curl;

class Task extends Command
{
    protected $database = null;
    protected $public_key = '39Cd8eJe7vggnikR';
    protected $status_arr = [
        3 => 5,
        -98 => 6,
        -99 => 7,
    ];

    protected function configure()
    {
        $this->setName('Task')->setDescription("任务状态");
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);

        while (true) {
            $this->taskStatus();
            sleep(5);
        }

    }

    private function taskStatus()
    {
        $log = '';
        $log .= date('Y-m-d H:i:s ');
        // 查询任务拆分日志表对应信息
        $task_split_log_list = Db::table('tbl_task_split_log')->field('task_id,status,order_id')->where(['is_inform' => 0])->select();
        if (count($task_split_log_list) > 0) {
            $task_info_arr = [];
            foreach ($task_split_log_list as $item) {
                $task_record = Db::table('tbl_task')->field('id,status')->where(['id' => $item['task_id']])->find();
                // 判断任务状态是否已过待执行和执行中
                if ($task_record['status'] != 1 && $task_record['status'] != 2 && $item['status'] != $task_record['status']) {
                    // 修改任务拆分日志表对应信息
                    Db::table('tbl_task_split_log')->where(['task_id' => $task_record['id']])->update(['status' => $task_record['status'], 'updatetime' => time()]);
                    // 输出对应日志信息
                    $log .= "task_id:{$task_record['id']}；";
                    $log .= "order_id:{$item['order_id']}；";
                    $log .= "source_status:{$item['status']}；";
                    $log .= "task_status:{$task_record['status']};";

                    $task_info_arr['data']['order_info'][] = [
                        'order_id' => $item['order_id'],
                        'status' => $this->status_arr[$task_record['status']],
                    ];
                } elseif ($item['status'] != 1 && $item['status'] != 2 && empty($item['is_inform'])) {
                    // 输出对应日志信息
                    $log .= "task_id:{$task_record['id']}；";
                    $log .= "order_id:{$item['order_id']}；";
                    $log .= "source_status:{$item['status']};";

                    $task_info_arr['data']['order_info'][] = [
                        'order_id' => $item['order_id'],
                        'status' => $this->status_arr[$item['status']],
                    ];
                }
            }

            // 判断是否有已完成的任务
            if (isset($task_info_arr['data']) && count($task_info_arr['data']) > 0) {
                $request_time = time();
                $task_info_arr['data']['token'] = md5($this->public_key . $request_time);
                $task_info_arr['data']['request_time'] = $request_time;

                // 实例化Curl工具类
                $Curl = new Curl();
                // 发送POST请求
                $res = json_decode($Curl->post('http://api.morsx.cn:9000/api/order/index/change_status', http_build_query($task_info_arr)), true);
                // 判断是否有数据
                if ($res['code'] == 1 && !empty($res['data'])) {
                    foreach (json_decode($res['data'],true) as $v) {
                        // 如果返回状态为成功
                        if ($v['status'] == 1) {
                            // 修改任务拆分日志表对应信息
                            Db::table('tbl_task_split_log')->where(['order_id' => $v['order_id']])->update(['is_inform' => 1, 'updatetime' => time()]);
                            // 输出对应日志信息
                            $log .= "successful order_id:{$v['order_id']}；";
                        } else {
                            // 输出对应日志信息
                            $log .= "failed order_id:{$v['order_id']}；";
                        }
                        $log .= "status:{$v['status']};";
                    }
                } else {
                    // 输出对应日志信息
                    $log .= "API request error\n";
                    $res = json_encode($res);
                    $log .= "{$res}\n";
                }
                $log .= "||";
            } else {
                $log .= "No data execution||";
            }
        } else {
            $log .= "No data execution||";
        }
        echo $log;
    }
}
