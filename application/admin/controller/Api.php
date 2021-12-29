<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 示例接口
 */
class Api extends Backend
{
    /**
     * Task模型对象
     * @var \app\admin\model\Task
     */
    protected $model = null;

    protected $public_key = '39Cd8eJe7vggnikR';

    protected $task_price_list = [
        'tk' => [
            'live' => 2,
            'vlog' => 1,
        ],
        'ks' => [
            'live' => 2,
            'vlog' => 1,
        ],
        'hy' => [
            'live' => 2,
            'vlog' => 1,
        ],
        'dy' => [
            'live' => 2,
            'vlog' => 1,
        ],
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->task_model = new \app\admin\model\Task;
        $this->task_split_log = new \app\admin\model\TaskSplitLog;
    }

    public function import()
    {
        parent::import();
    }

    /***
     * 新增任务
     */
    public function addTask()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("data/a");

            // 验证Token是否正确
            if ($params['token'] != md5($this->public_key . $params['request_time'])) {
                $this->returnError(__('Token verification error'));
            }

            if ($params) {
                $params = $this->preExcludeFields($params);

                $order_code = json_decode($params['ordercode'], true);

                $res = $this->downFile($order_code['comment_file']);
                if (!$res) $this->returnError(__('Comment file verification error'));

                $task_name_arr = explode('.', $order_code['operate']);
                $params['busitype'] = 1;
                $params['status'] = 1;
                $params['reward'] = $this->task_price_list[$task_name_arr[0]][$task_name_arr[1]];
                $params['pubtime'] = $order_code['pubtime'];
                $params['endtime'] = $order_code['endtime'];
                $params['taskname'] = $order_code['operate'];
                $params['tasktype'] = strtoupper($task_name_arr[0]);
                $params['amount'] = $order_code['manually_comment_cnt'];

                $task_code[] = [
                    'follow_mintime' => $order_code['follow_mintime'],
                    'follow_maxtime' => $order_code['follow_maxtime'],
                    'search_key' => $order_code['search_key'],
                    'search_nickname' => $order_code['search_nickname'],
                    'search_homepage' => $order_code['search_homepage'],
                    'verify_key' => $order_code['verify_key'],
                    'delay' => isset($order_code['delay']) ? $order_code['delay'] : 0,
                    'operate' => $order_code['operate'],
                    'comment_content' => '[NONSENSE]',
                    'endtime' => $order_code['endtime'],
                    'ss_policy' => 'default',
                    'manually_amount' => $order_code['manually_amount'],
                    'amount_multi' => $order_code['amount_multi'],
                    'manually_follow_cnt' => $order_code['manually_follow_cnt'],
                    'manually_comment_cnt' => $order_code['manually_comment_cnt'],
                    'manually_praise_cnt' => $order_code['manually_praise_cnt'],
                    'manually_cart_cnt' => $order_code['manually_cart_cnt'],
                ];

                $params['taskcode'] = json_encode($task_code);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    $result = $this->task_model->allowField(true)->save($params);

                    if ($result) {
                        try {
                            $data = [
                                'task_id' => $this->task_model->id,
                                'order_id' => $params['order_id'],
                                'order_number' => $params['order_number'],
                                'ordercode' => $params['ordercode'],
                                'taskcode' => $params['taskcode'],
                                'status' => 1,
                                'updatetime' => time(),
                                'createtime' => time(),
                                'is_inform' => 1,
                            ];
                            var_dump($data);
                            exit;
                            $result = $this->task_split_log->allowField(true)->save($data);
                        } catch (ValidateException $e) {
                            Db::rollback();
                            $this->returnError($e->getMessage());
                        } catch (PDOException $e) {
                            Db::rollback();
                            $this->returnError($e->getMessage());
                        } catch (Exception $e) {
                            Db::rollback();
                            $this->returnError($e->getMessage());
                        }
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->returnError($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->returnError($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->returnError($e->getMessage());
                }
                if ($result !== false) {
                    $this->returnSuccess('任务创建成功！');
                } else {
                    $this->returnError(__('No rows were inserted'));
                }
            }
            $this->returnError(__('Parameter %s can not be empty', ''));
        } else {
            return $this->returnError(__('Wrong request mode'));
        }
    }

    /**
     * CURL下载文件 成功返回文件名，失败返回false
     * @param $url
     * @param string $savePath
     * @return bool|string
     * @author Zou Yiliang
     */
    private function downFile($url, $savePath = './comment_files')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);  //需要response header
        curl_setopt($ch, CURLOPT_NOBODY, FALSE);  //需要response body
        $response = curl_exec($ch);
        //分离header与body
        $header = '';
        $body = '';
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE); //头信息size
            $header = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);
        }
        curl_close($ch);

        //文件名
        $arr = array();
        if (preg_match('/filename=(.*)/', $header, $arr)) {
            $file = date('Ym') . '/' . date('d') . '/' . trim($arr[1]);
            $fullName = rtrim($savePath, '/') . '/' . $file;
            //创建目录并设置权限
            $basePath = dirname($fullName);
            if (!file_exists($basePath)) {
                @mkdir($basePath, 0777, true);
                @chmod($basePath, 0777);
            }
            if (file_put_contents($fullName, $body)) {
                return true;
            }
        }
        return false;
    }

    private function validationField($params)
    {
        if (empty($params['taskcode'])) ;
        $this->returnError(__('任务串不能为空！'));
        if (empty($params['taskname'])) ;
        $this->returnError(__('任务名称不能为空！'));
        if (empty($params['tasktype'])) ;
        $this->returnError(__('任务类型不能为空！'));
        if (empty($params['updatetime'])) ;
        $this->returnError(__('更新时间不能为空！'));
        if (empty($params['pubtime'])) ;
        $this->returnError(__('发布时间不能为空！'));
        if (empty($params['endtime'])) ;
        $this->returnError(__('结束时间不能为空！'));
        if (empty($params['amount'])) ;
        $this->returnError(__('发布数量不能为空！'));
    }

}
