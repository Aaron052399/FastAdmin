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
            $output->writeln('');
            $output->writeln(date('Y-m-d H:i:s') . ' Crontab job start...');
            $this->taskStatus();
            $output->writeln(date('Y-m-d H:i:s') . ' Crontab job end...');
            $output->writeln('');
            sleep(5);
        }

    }

    private function taskStatus()
    {
        // 查询任务拆分日志表对应信息
        $task_split_log_list = Db::table('tbl_task_split_log')->field('task_id,status,order_id')->where(['is_inform' => 0])->select();
        if (count($task_split_log_list) > 0) {
            // 定义任务信息数组
            $task_info_arr['data'] = [];
            foreach ($task_split_log_list as $item) {
                $task_record = Db::table('tbl_task')->field('id,status')->where(['id' => $item['task_id']])->find();

                // 判断任务状态是否已过待执行和执行中
                if ($task_record['status'] != 1 && $task_record['status'] != 2 && $item['status'] != $task_record['status']) {
                    // 修改任务拆分日志表对应信息
                    Db::table('tbl_task_split_log')->where(['task_id' => $task_record['id']])->update(['status' => $task_record['status'], 'updatetime' => time()]);

                    echo "========================Split line========================\n";
                    // 输出对应日志信息
                    echo "Modified table name: tbl_task_split_log\n";
                    echo "task_id: {$task_record['id']}\n";
                    echo "order_id: {$item['order_id']}\n";
                    echo "source_status: {$item['status']}\n";
                    echo "task_status: {$task_record['status']}\n";

                    $task_info_arr['data']['order_info'] = [
                        'order_id' => $item['order_id'],
                        'status' => $this->status_arr[$task_record['status']],
                    ];
                } elseif ($item['status'] != 1 && $item['status'] != 2 && empty($item['is_inform'])) {
                    echo "========================Split line========================\n";
                    // 输出对应日志信息
                    echo "Table information was not modified\n";
                    echo "task_id: {$task_record['id']}\n";
                    echo "order_id: {$item['order_id']}\n";
                    echo "source_status: {$item['status']}\n";

                    $task_info_arr['data']['order_info'] = [
                        'order_id' => $item['order_id'],
                        'status' => $this->status_arr[$item['status']],
                    ];
                }
            }

            // 判断是否有已完成的任务
            if (count($task_info_arr['data']) > 0) {
                $request_time = time();
                $task_info_arr['data']['token'] = md5($this->public_key . $request_time);
                $task_info_arr['data']['request_time'] = $request_time;

                // 实例化Curl工具类
                $Curl = new Curl();
                // 发送POST请求
                $res = json_decode($Curl->post('http://api.morsx.cn:8888/api/addTask', json_encode($task_info_arr)), true);
                echo "\n";
                // 判断是否有数据
                if ($res['code'] == 1 && count($res['data']) > 0) {
                    foreach ($res['data'] as $v) {
                        echo "========================Split line========================\n";
                        // 如果返回状态为成功
                        if ($v['status'] == 1) {
                            // 修改任务拆分日志表对应信息
                            Db::table('tbl_task_split_log')->where(['order_id' => $v['order_id']])->update(['is_inform' => 0, 'updatetime' => time()]);
                            // 输出对应日志信息
                            echo "Order ID for successful notification: {$v['order_id']}\n";
                        } else {
                            // 输出对应日志信息
                            echo "Order ID of failed notification: {$v['order_id']}\n";
                        }
                        echo "status: {$v['status']}\n";
                    }
                } else {
                    // 输出对应日志信息
                    echo "API request error\n";
                    $res = json_encode($res);
                    echo "{$res}\n";
                }
                echo "\n";
            } else {
                echo "No data execution\n";
            }
        } else {
            echo "No data execution\n";
        }
    }
}
