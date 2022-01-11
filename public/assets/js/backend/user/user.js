define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index' + location.search,
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    import_url: 'user/user/import',
                    table: 'user',
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
                        {field: 'tel', title: __('Tel'), operate: 'LIKE'},
                        {field: 'deviceid', title: __('Deviceid'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'token', title: __('Token'), operate: 'LIKE'},
                        {field: 'status', title: __('Status')},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'openid', title: __('Openid'), operate: 'LIKE'},
                        {field: 'wename', title: __('Wename'), operate: 'LIKE'},
                        {field: 'weimg', title: __('Weimg'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'tkname', title: __('Tkname'), operate: 'LIKE'},
                        {field: 'praised', title: __('Praised'), operate: 'LIKE'},
                        {field: 'fans', title: __('Fans'), operate: 'LIKE'},
                        {field: 'follow', title: __('Follow'), operate: 'LIKE'},
                        {field: 'prefer', title: __('Prefer')},
                        {field: 'usertag', title: __('Usertag'), operate: 'LIKE'},
                        {field: 'pwd', title: __('Pwd'), operate: 'LIKE'},
                        {field: 'priority', title: __('Priority')},
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