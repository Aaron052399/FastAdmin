define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'task/index' + location.search,
                    add_url: 'task/add',
                    edit_url: 'task/edit',
                    del_url: 'task/del',
                    multi_url: 'task/multi',
                    import_url: 'task/import',
                    download_url: 'task/import',
                    table: 'task',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible: false, // 是否一直显示高级搜索功能
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false,},
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {1: "待执行", 2: "执行中", 3: "已完成", '-99': "已下架", '-98': "已终止"},
                            cellStyle: function (value, row, index) {
                                var status_name = '';
                                switch (value) {
                                    case '待执行':
                                        status_name = 'blue_css';
                                        break;
                                    case '执行中':
                                        status_name = 'green_css';
                                        break;
                                    case '已完成':
                                        status_name = 'black_css';
                                        break;
                                    case '已下架':
                                        status_name = 'orange_css';
                                        break;
                                    case '已终止':
                                        status_name = 'red_css';
                                        break;
                                }

                                return {
                                    'classes': status_name
                                };
                            }
                        },
                        {
                            field: 'tasktype',
                            title: __('Tasktype'),
                            searchList: {"TK": "抖音", "KS": "快手", "HY": "虎牙", "DY": "斗鱼",},
                        },
                        {
                            field: 'taskname',
                            title: __('Taskname'),
                            searchList: {
                                "tk.live.enter": "抖音直播任务",
                                "tk.vlog.enter": "抖音视频任务",
                                "ks.live.enter": "快手直播任务",
                                "ks.vlog.enter": "快手视频任务",
                                "hy.live.enter": "虎牙直播任务",
                                "dy.vlog.enter": "斗鱼视频任务",
                            }
                        },
                        {
                            field: 'taskcode', title: __('Taskcode'), formatter: function (value, row, index) {
                                var div = "<div style='width:240px;white-space:break-spaces'>" + value + "</div>";
                                return div;
                            }
                        },
                        {
                            field: 'updatetime',
                            title: __('Updatetime'),
                            operate: false,
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime,
                            sortable: true,
                        },
                        {
                            field: 'pubtime',
                            title: __('Pubtime'),
                            operate: false,
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime,
                            sortable: true,
                        },
                        {
                            field: 'endtime',
                            title: __('Endtime'),
                            operate: false,
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime,
                            sortable: true,
                        },
                        {field: 'reward', title: __('Reward'), operate: false},
                        {field: 'amount', title: __('Amount'), operate: false},
                        {field: 'published_amount', title: __('Published_amount'), operate: false},
                        {field: 'finished_amount', title: __('Finished_amount'), operate: false},
                        {field: 'busitype', title: __('Busitype'), searchList: {"0": "补贴任务", "1": "订单任务"}},
                        {
                            field: 'operate',
                            title: __('More'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operateMore
                        },
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