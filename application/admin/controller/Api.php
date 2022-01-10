<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

class Api extends Backend
{
    /**
     * Task模型对象
     * @var \app\admin\model\Task
     */
    protected $task_model = null;
    protected $task_split_log = null;

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
            $params = $this->request->param();

            // 验证所有参数
            if (!isset($params['token'])) $this->returnError(__('Token is empty!'));
            if (!isset($params['request_time'])) $this->returnError(__('Request time is empty!'));
            if (!isset($params['ordercode'])) $this->returnError(__('Ordercode is empty!'));
            if (!isset($params['order_id'])) $this->returnError(__('Order_id is empty!'));
            if (!isset($params['order_number'])) $this->returnError(__('Order_number is empty!'));

            // 校验Token是否正确
            if ($params['token'] != md5($this->public_key . $params['request_time'])) {
                $this->returnError(__('Token verification error'));
            }

            if ($params) {
                $params = $this->preExcludeFields($params);

                $order_code = json_decode($params['ordercode'], true);

                // 判断文件地址是否存在
                if (!empty($order_code['comment_file'])){
                    // 将文件下载到本地，并返回是否成功
                    $res = $this->downFile($order_code['comment_file']);
                    if (!$res) $this->returnError(__('Comment file verification error'));
                }

                $task_name_arr = explode('.', $order_code['operate']);
                $params['busitype'] = 1;
                $params['status'] = 1;
                $params['reward'] = $this->task_price_list[$task_name_arr[0]][$task_name_arr[1]];
                $params['pubtime'] = $order_code['pubtime'];
                $params['endtime'] = $order_code['endtime'];
                $params['taskname'] = $order_code['operate'];
                $params['tasktype'] = strtoupper($task_name_arr[0]);
                $params['amount'] = $order_code['manually_comment_cnt'];
                $params['comment_file'] = isset($res) ? $res : '';

                $task_code[] = [
                    'follow_mintime' => $order_code['follow_mintime'] ?? 0,
                    'follow_maxtime' => $order_code['follow_maxtime'] ?? 0,
                    'search_key' => $order_code['search_key'],
                    'search_nickname' => $order_code['search_nickname'],
                    'search_homepage' => $order_code['search_homepage'],
                    'verify_key' => $order_code['verify_key'],
                    'delay' => $order_code['delay'] ?? 0,
                    'operate' => $order_code['operate'],
                    'comment_content' => '[NONSENSE]',
                    'endtime' => $order_code['endtime'],
                    'ss_policy' => 'default',
                    'manually_amount' => $order_code['manually_amount'],
                    'amount_multi' => $order_code['amount_multi'] ?? 0,
                    'manually_follow_cnt' => $order_code['manually_follow_cnt'] ?? 0,
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
                                'is_inform' => 0,
                            ];
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
            $this->returnError(__('Wrong request mode'));
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
            $file_name_arr = explode('.',$arr[1]);
            $file_name_arr[0] = $file_name_arr[0] . '_' . uniqid();
            $file_name = trim(implode('.',$file_name_arr));
            $file = date('Ym') . '/' . date('d') . '/' . $file_name;
            $fullName = rtrim($savePath, '/') . '/' . $file;
            //创建目录并设置权限
            $basePath = dirname($fullName);
            if (!file_exists($basePath)) {
                @mkdir($basePath, 0777, true);
                @chmod($basePath, 0777);
            }

            if (file_put_contents($fullName, $body)) {
                $fullName = explode('/',$fullName);
                unset($fullName[0]);
                return implode('/',$fullName);
            }
        }
        return false;
    }

}
