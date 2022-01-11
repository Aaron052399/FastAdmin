define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            $(".btn-addtask,.btn-edit").data("area",["100%","100%"]);
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'task/index' + location.search,
                    add_url: 'task/add',
                    edit_url: 'task/edit',
                    del_url: 'task/del',
                    livepublish_url: 'task/livepublish',
                    multi_url: 'task/multi',
                    import_url: 'task/import',
                    download_url: 'task/download',
                    table: 'task',
                }
            });

            var table = $("#table");

            table.on('post-body.bs.table',function(){
                $(".btn-editone").data("area",["100%","100%"]);
            });

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
            $(document).on("change", "#c-taskname", function () {
                var that = $(this);
                Controller.api.events(that);
            });

            $(document).on('change','#c-search_nickname',function () {
                var that = $(this).val();
                if ($('#c-verify_key').val() == '')
                {
                    $('#c-verify_key').focus().val(that);
                }
            });
        },
        edit: function () {
            Controller.api.bindevent();

            var val = $('#c-taskname');
            Controller.api.events(val);

            $(document).on("change", "#c-taskname", function () {
                //变更后的回调事件
                var that = $(this);
                Controller.api.events(that);
            });

            $(document).on('change','#c-search_nickname',function () {
                var that = $(this).val();
                if ($('#c-verify_key').val() == '')
                {
                    $('#c-verify_key').focus().val(that);
                }
            });
        },
        detail: function () {
            $('.back-btn').on('click',function () {
                parent.$('.layui-layer-close').trigger("click");
            });
        },
        livepublish: function () {
            $('.clear-task').on('click', function () {
                var operation_type = $(this).data('operation_type');
                $.ajax({
                    url: "/inlive/get_inlive_publish_url",
                    type: "POST",
                    data: {
                        'operation_type': operation_type,
                    },
                    success: function (data) {
                        window.open(data['url'])
                    }
                })
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"), function (data, ret) {
                    // $('.layui-layer-footer', window.parent.document)
                    // $(window.parent.document).find(".layui-layer-footer").remove();
                    top.Toastr.success(ret.msg);
                    parent.$(".btn-refresh").trigger("click");
                    window.location.href = '/task/detail?task_id=' + data + '&type=' + ret.url;
                    return false;
                },function (data,ret) {
                    
                },function (success, error) {
                    if ($('#c-search_key').val() == '' && $('#c-search_nickname').val() == '' && $('#c-search_homepage').val() == ''){
                        Toastr.error('直播间搜索串、用户昵称、用户主页，至少填写一个！');
                        $('#c-search_key,#c-search_nickname,#c-search_homepage').css({'border':'1px red solid'}).focus();
                        return false;
                    }
                    $('#c-search_key,#c-search_nickname,#c-search_homepage').css({'border':'1px solid #ccc'});
                });
            },
            events: function (that) {
                var manually_praise_cnt_tips = $('#manually_praise_cnt_tips').text();
                var manually_comment_cnt_tips = $('#manually_comment_cnt_tips').text();
                var manually_cart_cnt_tips = $('#manually_cart_cnt_tips').text();

                if (that.val().split('.')[1] === 'vlog') {
                    $('.delay').css({'display': 'block'});
                    $('#c-reward').val(30);
                    $('#manually_praise_cnt_tips').text(manually_praise_cnt_tips.replace('每小时',''));
                    $('#manually_comment_cnt_tips').text(manually_comment_cnt_tips.replace('每小时',''));
                    $('#manually_cart_cnt_tips').text(manually_cart_cnt_tips.replace('每小时',''));
                    $('#reward_title').text('每次');
                } else {
                    $('#manually_praise_cnt_tips').text('每小时' + manually_praise_cnt_tips);
                    $('#manually_comment_cnt_tips').text('每小时' + manually_comment_cnt_tips);
                    $('#manually_cart_cnt_tips').text('每小时' + manually_cart_cnt_tips);
                    $('#reward_title').text('每分钟');
                    $('.delay').css({'display': 'none'});
                    $('#c-reward').val(3);
                }
            },
        }
    };
    return Controller;
});