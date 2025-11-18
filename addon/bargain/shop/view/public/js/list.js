var laytpl;
$(function () {
    $("body").off("click", ".contraction").on("click", ".contraction", function () {
        var bargain_id = $(this).attr("data-id");
        var open = $(this).attr("data-open");
        var tr = $(this).parent().parent().parent().parent();
        var index = tr.attr("data-index");
        if (open == 1) {
            $(this).children("span").text("+");
            $(".js-list-" + index).remove();
        } else {
            $(this).children("span").text("-");
            $.ajax({
                url: ns.url("bargain://shop/bargain/getSkuList"),
                data: {bargain_id: bargain_id},
                dataType: 'JSON',
                type: 'POST',
                async: false,
                success: function (res) {
                    var sku_list = $("#skuList").html();
                    var data = {
                        list: res.data,
                        index: index
                    };
                    laytpl(sku_list).render(data, function (html) {
                        tr.after(html);
                    });
                    layer.photos({
                        photos: '.img-wrap',
                        anim: 5
                    });
                }
            });
        }
        $(this).attr("data-open", (open == 0 ? 1 : 0));
    });

    layui.use(['form', 'element', 'laytpl', 'laydate'], function () {
        laytpl = layui.laytpl;
        var table,
            form = layui.form,
            element = layui.element,
            laydate = layui.laydate,
            repeat_flag = false; //防重复标识
        form.render();

        element.on('tab(bargain_tab)', function () {
            table.reload({
                page: {
                    curr: 1
                },
                where: {
                    'status': this.getAttribute('data-status')
                }
            });
        });

        //开始时间
        laydate.render({
            elem: '#start_time', //指定元素
            type: 'datetime'
        });
        //结束时间
        laydate.render({
            elem: '#end_time', //指定元素
            type: 'datetime'
        });

        table = new Table({
            elem: '#bargain_list',
            url: ns.url("bargain://shop/bargain/lists"),
            cols: [
                [{
                    type: 'checkbox',
                    width: '3%',
                },{
                    title: '商品信息',
                    unresize: 'false',
                    width: '25%',
                    templet: '#goods_info'
                }, {
                    title: '商品价格',
                    unresize: 'false',
                    width: '8%',
                    templet: function (data) {
                        return '￥' + data.price;
                    }
                }, {
                    field: 'floor_price',
                    title: '底价',
                    unresize: 'false',
                    width: '7%',
                    sort : true,
                    templet: function (data) {
                        return '￥' + data.floor_price;
                    }
                }, {
                    field: 'join_num',
                    title: '参与人数',
                    unresize: 'false',
                    width: '7%',
                    sort : true
                }, {
                    field: 'sale_num',
                    title: '砍价成功人数',
                    unresize: 'false',
                    width: '8%',
                    sort : true
                }, {
                    field: 'bargain_stock',
                    title: '砍价总库存',
                    unresize: 'false',
                    width: '7%'
                    // }, {
                    // 	title: '库存剩余',
                    // 	unresize: 'false',
                    // 	width: '8%',
                    // 	templet: function (data) {
                    // 		return data.bargain_stock - data.sale_num;
                    // 	}
                }, {
                    title: '活动时间',
                    unresize: 'false',
                    width: '15%',
                    templet: '#time'
                }, {
                    field: 'status_name',
                    title: '状态',
                    unresize: 'false',
                    width: '8%'
                }, {
                    title: '操作',
                    toolbar: '#operation',
                    unresize: 'false',
                    align:'right',
                }]
            ],
            toolbar: '#toolbarAction'
        });

        /**
         * 搜索功能
         */
        form.on('submit(search)', function (data) {
            table.reload({
                page: {
                    curr: 1
                },
                where: data.field
            });
        });

        table.on("sort",function (obj) {
            table.reload({
                page: {
                    curr: 1
                },
                where: {
                    order:obj.field,
                    sort:obj.type
                }
            });
        });

        //监听Tab切换
        element.on('tab(status)', function (data) {
            var status = $(this).attr("data-status");
            table.reload({
                page: {
                    curr: 1
                },
                where: {
                    'status': status
                }
            });
        });

        // 监听工具栏操作
        table.toolbar(function (obj) {
            var data = obj.data;
            if(data.length <= 0) return;
            var bargainIdAll = [];
            for (var i in data){
                bargainIdAll.push(data[i].bargain_id);
            }

            switch (obj.event) {
                case 'delete':
                    deleteBargainAll(bargainIdAll)
                    break;
                case 'invalid':
                    closeBargainAll(bargainIdAll)
                    break;
            }
        })

        //批量删除
        function deleteBargainAll(data){
            layer.confirm('确定要删除砍价活动吗?', function (index) {
                if (repeat_flag) return;
                repeat_flag = true;
                layer.close(index);

                $.ajax({
                    url: ns.url("bargain://shop/bargain/deleteAll"),
                    data: {
                        bargain_id: data
                    },
                    dataType: 'JSON',
                    type: 'POST',
                    success: function (res) {
                        layer.msg(res.message);
                        repeat_flag = false;
                        table.reload({
                            page: {
                                curr: 1
                            },
                        });
                    }
                });
            }, function () {
                layer.close();
                repeat_flag = false;
            });
        }

        //批量关闭
        function closeBargainAll(data){
            layer.confirm('确定要结束砍价活动吗?', function (index) {
                if (repeat_flag) return;
                repeat_flag = true;
                layer.close(index);

                $.ajax({
                    url: ns.url("bargain://shop/bargain/finishAll"),
                    data: {
                        bargain_id: data
                    },
                    dataType: 'JSON',
                    type: 'POST',
                    success: function (res) {
                        layer.msg(res.message);
                        repeat_flag = false;
                        table.reload();
                    }
                });
            }, function () {
                layer.close();
                repeat_flag = false;
            });
        }

        /**
         * 监听工具栏操作
         */
        table.tool(function (obj) {
            var data = obj.data;
            switch (obj.event) {
                case 'detail': //详情
                    location.hash = ns.hash("bargain://shop/bargain/detail", {"bargain_id": data.bargain_id});
                    break;
                case 'edit': //编辑
                    location.hash = ns.hash("bargain://shop/bargain/edit", {"bargain_id": data.bargain_id});
                    break;
                case 'del': //删除
                    deleteGroupbuy(data.bargain_id);
                    break;
                case 'select': //推广
                    bargainUrl(data);
                    break;
                case 'close': // 结束
                    closeGroupbuy(data.bargain_id);
                    break;
                case 'launch': //砍价列表
                    location.hash = ns.hash("bargain://shop/bargain/launchlist", {"bargain_id": data.bargain_id});
                    break;
            }
        });

        /**
         * 删除
         */
        function deleteGroupbuy(bargain_id) {
            layer.confirm('确定要删除该砍价活动吗?', function (index) {
                if (repeat_flag) return;
                repeat_flag = true;
				layer.close(index);

                $.ajax({
                    url: ns.url("bargain://shop/bargain/delete"),
                    data: {
                        bargain_id: bargain_id
                    },
                    dataType: 'JSON',
                    type: 'POST',
                    success: function (res) {
                        layer.msg(res.message);
                        repeat_flag = false;
                        if (res.code == 0) {
                            table.reload({
                                page: {
                                    curr: 1
                                },
                            });
                        }
                    }
                });
            }, function () {
                layer.close();
                repeat_flag = false;
            });
        }

        // 结束
        function closeGroupbuy(bargain_id) {

            layer.confirm('确定要结束该砍价活动吗?', function (index) {
                if (repeat_flag) return;
                repeat_flag = true;
				layer.close(index);

                $.ajax({
                    url: ns.url("bargain://shop/bargain/finish"),
                    data: {
                        bargain_id: bargain_id
                    },
                    dataType: 'JSON',
                    type: 'POST',
                    success: function (res) {
                        layer.msg(res.message);
                        repeat_flag = false;
                        if (res.code == 0) {
                            table.reload();
                        }
                    }
                });
            }, function () {
                layer.close();
                repeat_flag = false;
            });
        }

        function bargainUrl(data){
            new PromoteShow({
                url:ns.url("bargain://shop/bargain/bargainurl"),
                param:{bargain_id:data.bargain_id},
            })
        }
    });

});

function add() {
    location.hash = ns.hash("bargain://shop/bargain/add");
}