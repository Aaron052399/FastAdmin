define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'task_split_log/index' + location.search,
                    add_url: 'task_split_log/add',
                    edit_url: 'task_split_log/edit',
                    del_url: 'task_split_log/del',
                    multi_url: 'task_split_log/multi',
                    import_url: 'task_split_log/import',
                    table: 'task_split_log',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'task_id', title: __('Task_id')},
                        {field: 'order_id', title: __('Order_id')},
                        {field: 'status', title: __('Status')},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'is_inform', title: __('Is_inform')},
                        {field: 'task.id', title: __('Task.id')},
                        {field: 'task.masterid', title: __('Task.masterid')},
                        {field: 'task.taskname', title: __('Task.taskname'), operate: 'LIKE'},
                        {field: 'task.tasktype', title: __('Task.tasktype'), operate: 'LIKE'},
                        {field: 'task.status', title: __('Task.status')},
                        {field: 'task.updatetime', title: __('Task.updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'task.pubtime', title: __('Task.pubtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'task.endtime', title: __('Task.endtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'task.reward', title: __('Task.reward')},
                        {field: 'task.amount', title: __('Task.amount')},
                        {field: 'task.published_amount', title: __('Task.published_amount')},
                        {field: 'task.finished_amount', title: __('Task.finished_amount')},
                        {field: 'task.comment_file', title: __('Task.comment_file'), operate: false},
                        {field: 'task.busitype', title: __('Task.busitype')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});