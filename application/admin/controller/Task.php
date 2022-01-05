<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Task extends Backend
{
    /**
     * Task模型对象
     * @var \app\admin\model\Task
     */
    protected $model = null;

    protected $task_field_arr = [
        'tk' => [
            [
                'field_name' => 'tasktype',
                'is_show' => 1,
                'is_required' => 1,
            ],
            [
                'field_name' => 'taskname',
                'is_show' => 1,
                'is_required' => 1,
            ],
            [
                'field_name' => 'busitype',
                'is_show' => 1,
                'is_required' => 1,
            ],
            [
                'field_name' => 'taskname',
                'is_show' => 1,
                'is_required' => 1,
            ],
        ],
        'ks' => [

        ],
        'hy' => [

        ],
        'dy' => [

        ],
    ];

    protected $task_type_name = [
        'tk' => '抖音',
        'ks' => '快手',
        'hy' => '虎牙',
        'dy' => '斗鱼',
    ];

    protected $task_operatea_name = [
        'tk' => [
            [
                'name' => '抖音直播任务',
                'type' => 'tk.live.enter',
            ],
            [
                'name' => '抖音视频任务',
                'type' => 'tk.vlog.enter',
            ],
        ],
        'ks' => [
            [
                'name' => '快手直播任务',
                'type' => 'ks.live.enter',
            ],
//            [
//                'name' => '快手视频任务',
//                'type' => 'ks.vlog.enter',
//            ],
        ],
        'hy' => [
            [
                'name' => '虎牙直播任务',
                'type' => 'hy.live.enter',
            ],
//            [
//                'name' => '虎牙视频任务',
//                'type' => 'hy.vlog.enter',
//            ],
        ],
        'dy' => [
            [
                'name' => '斗鱼直播任务',
                'type' => 'dy.live.enter',
            ],
//            [
//                'name' => '斗鱼视频任务',
//                'type' => 'dy.vlog.enter',
//            ],
        ],
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Task;

    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 下架任务
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count = $v->save(['status' => -99]);
//                    $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No sold out were task'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /***
     * 新增任务
     */
    public function add()
    {
        $task_type = $this->request->get('task_type');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                // 处理taskcode字段
                $taskcode = $params['taskcode'];
                $taskcode['comment_content'] = '[NONSENSE]';
                $taskcode['ss_policy'] = 'default';
                $taskcode['operate'] = $params['taskname'];
                $taskcode['endtime'] = strtotime($params['endtime']);
                $taskcode['delay'] = explode('.', $params['taskname'])[1] == 'vlog' ? $taskcode['delay'] * 60 : strtotime($params['endtime']) - strtotime($params['pubtime']);
                $taskcode_arr[] = $taskcode;

                // 处理剩余字段
                $params['status'] = 1;
                $params['taskcode'] = json_encode($taskcode_arr,JSON_UNESCAPED_UNICODE);
                $params['tasktype'] = strtoupper($params['tasktype']);
                $params['amount'] = floor($taskcode['manually_amount'] * $taskcode['amount_multi']);
                $params['updatetime'] = time();

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success(__('Insert task successful'),'task/add',$this->model->id);
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $this->view->assign("row", '');
        $this->view->assign("task_type", $task_type);
        $this->view->assign("task_type_name", $this->task_type_name[$task_type]);
        $this->view->assign("task_name", $this->task_operatea_name[$task_type]);

        return $this->view->fetch();
    }

    /**
     * 编辑任务
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids)->getData();
        $row['taskcode'] = json_decode($row['taskcode'],true)[0];
        $task_type = strtolower($row['tasktype']);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                // 处理taskcode字段
                $taskcode = $params['taskcode'];
                $taskcode['comment_content'] = '[NONSENSE]';
                $taskcode['ss_policy'] = 'default';
                $taskcode['operate'] = $params['taskname'];
                $taskcode['endtime'] = strtotime($params['endtime']);
                $taskcode['delay'] = explode('.', $params['taskname'])[1] == 'vlog' ? $taskcode['delay'] * 60 : strtotime($params['endtime']) - strtotime($params['pubtime']);
                $taskcode_arr[] = $taskcode;

                // 处理剩余字段
                $params['status'] = 1;
                $params['taskcode'] = json_encode($taskcode_arr,JSON_UNESCAPED_UNICODE);
                $params['tasktype'] = strtoupper($params['tasktype']);
                $params['amount'] = floor($taskcode['manually_amount'] * $taskcode['amount_multi']);
                $params['updatetime'] = time();

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $row = $this->model->get($ids);
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success(__('Update task successful'),'task/edit',$row->id);
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);

        $this->view->assign("task_type", $task_type);
        $this->view->assign("task_type_name", $this->task_type_name[$task_type]);
        $this->view->assign("task_name", $this->task_operatea_name[$task_type]);
        return $this->view->fetch();
    }

    public function detail(){
        $task_id = $this->request->get('task_id');
        $type = $this->request->get('type');

        $task_info = [];
        $task_record = \app\admin\model\Task::get(['id' => $task_id])->getData();
        $task_code = json_decode($task_record['taskcode'],true);

        $pubtime = time() > $task_record['pubtime'] ? time() : $task_record['pubtime'];

        $task_info['verify_key'] = $task_code[0]['verify_key'];
        $task_info['pubtime'] = date('Y-m-d H:i:s', $pubtime);
        $task_info['endtime'] = date('Y-m-d H:i:s', $task_record['endtime']);

        // 如果当前时间大于发布时间

        $task_info['viewing_duration'] = ($task_record['endtime'] - $pubtime) / 60;
        $task_info['manually_amount'] = $task_code[0]['manually_amount'];
        $task_info['manually_comment_cnt'] = $task_code[0]['manually_comment_cnt'];

        $type = explode('/',$type);
        $this->view->assign("task_info", $task_info);
        $this->view->assign("type", $type[2]);

        return $this->view->fetch();
    }

    public function livepublish($ids = null){
        $row = $this->model->get($ids)->getData();
        $row['taskcode'] = json_decode($row['taskcode'],true)[0];
        $task_type = strtolower($row['tasktype']);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                // 处理taskcode字段
                $taskcode = $params['taskcode'];
                $taskcode['comment_content'] = '[NONSENSE]';
                $taskcode['ss_policy'] = 'default';
                $taskcode['operate'] = $params['taskname'];
                $taskcode['endtime'] = strtotime($params['endtime']);
                $taskcode['delay'] = explode('.', $params['taskname'])[1] == 'vlog' ? $taskcode['delay'] * 60 : strtotime($params['endtime']) - strtotime($params['pubtime']);
                $taskcode_arr[] = $taskcode;

                // 处理剩余字段
                $params['status'] = 1;
                $params['taskcode'] = json_encode($taskcode_arr,JSON_UNESCAPED_UNICODE);
                $params['tasktype'] = strtoupper($params['tasktype']);
                $params['amount'] = floor($taskcode['manually_amount'] * $taskcode['amount_multi']);
                $params['updatetime'] = time();

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $row = $this->model->get($ids);
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success(__('Update task successful'),'task/edit',$row->id);
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);

        $this->view->assign("task_type", $task_type);
        $this->view->assign("task_type_name", $this->task_type_name[$task_type]);
        $this->view->assign("task_name", $this->task_operatea_name[$task_type]);
        return $this->view->fetch();
    }

}
