/**
 * 渲染订单列表
 */
Order = function () {};

/**
 * 设置数据集
 */
Order.prototype.setData = function (data) {
	Order.prototype.data = data;
};

/**
 * 列名数据
 */
Order.prototype.cols = [
	{
		type: 'checkbox',
		fixed: 'left',
		width: '3%',
		merge: true,
		template: function (orderitem, order) {
			var json = {}
			json.order_id = order.order_id;
			json.order_no = order.order_no;
			json.giftcard_id = order.giftcard_id;
				var h = '<div class="sub-selected-checkbox" data-json='+ JSON.stringify(json) +' data-id='+ order.order_id +' >';
				h += '<input type="checkbox" lay-skin="primary" lay-filter="subCheckbox" name="" >';
				h += '</div>';
			return h;
		}
	},
	{
		title: '<span>兑换内容</span>',
		width: "30%",
		align: "left",
		className: "card-info",
		template: function (orderitem, order) {
			var h = '';
			var img = orderitem.exchange_image ? ns.img(orderitem.exchange_image.split(",")[0]) : '__STATIC__/img/shape.png'

			h += '<div class="order-goods">';
			h += '<div class="img-block">';
			h += '<img layer-src="' + img + '" src="' + img + '">';
			h += '</div>';
			h += '<div class="info">';
			h += '<span title="' + orderitem.exchange_name + '" class="multi-line-hiding text-color-sub">' + orderitem.exchange_name + '</span>';
			h += '</div>';
			h += '</div>';

			return h;
		}
	},
	{
		title: "数量",
		width: "6%",
		align: "center",
		className: "order-price",
		template: function (orderitem, order) {
			var h = '<div style="text-align: center;">';
				h += '<span>' + orderitem.num + '件</span>';
			h += '</div>';
			return h;
		}
	},
	{
		title: "兑换类型",
		width: "10%",
		align: "left",
		className: "card-right-type",
		template: function (orderitem, order) {
			var h = '<div style="padding-left: 15px;">';
			h += '<span>' + orderitem.type_name + '</span>';
			h += '</div>';
			return h;
		}
	},
	{
		title: "积分价格",
		width: "14%",
		align: "left",
		className: "order-money",
		merge: true,
		template: function (orderitem, order) {
			var h = '<div style="padding-left: 15px;">';
			h += '<div>积分：' + orderitem.point + '</div>';
			if(orderitem.exchange_price > 0) {
				h += '<div>实付：' + orderitem.exchange_price + '元</div>';
			}
			h += '</div>';
			return h;
		}
	},
	{
		title: "买家",
		width: "14%",
		align: "left",
		className: "table-member-td",
		template: function (orderitem, order) {
			var h = '';
			h += '<p>';
			h += '<a class="text-color" target="_blank" href="' + ns.href("shop/member/editmember?member_id=") + order.member_id + '">' + order.nickname + '</a>';
			h += '</p>';
			h += '<p>';
			h += '<span>' + order.name + '&nbsp;&nbsp;</span>';
			h += '<span style="">' + order.mobile + '</span>';
			h += '</p>';
			h += '<span class="line-hiding address_box" title="' + order.full_address + ' ' + order.address + '">' + order.full_address + " " + order.address +'</span>';
			return h;
		}
	},
	{
		title: "状态",
		width: "10%",
		align: "left",
		className: "transaction-status",
		merge: true,
		template: function (orderitem, order) {
			switch (orderitem.order_status){
				case 0:
					return '<div style="padding-left: 15px;">待支付</div>';
				case 1:
					return '<div style="padding-left: 15px;">已支付</div>';
				case -1:
					return '<div style="padding-left: 15px;">已关闭</div>';
			}
		}
	},
	{
	    title : "兑换时间",
	    width : "15%",
	    align : "center",
	    className : "create-time",
	    merge : true,
	    template : function(orderitem,order){
	        return '<div>' + ns.time_to_date(order.pay_time) + '</div>';
	    }
	},
	{
		title: "操作",
		align: "right",
		className: "operation",
		merge: true,
		template: function (orderitem, order) {
			var html='';
			html += '<div>';
				html += '<a class="layui-btn  text-color" href="'+ns.href("pointexchange://shop/pointexchange/detail", {order_id: orderitem.order_id}) +'">详情</a>';
				if(orderitem.relate_order_id > 0){
					html += '<a class="layui-btn  text-color" href="'+ns.href("shop/order/detail", {order_id: orderitem.relate_order_id}) +'">查看订单</a>';
				}
			html += '</div>';
			return html;

		}
	}
];
/**
 * 渲染表头
 */
Order.prototype.header = function (hasThead) {
	var colgroup = '<colgroup>';
	var thead = '';
	if (hasThead) thead = '<thead><tr>';
	
	for (var i = 0; i < this.cols.length; i++) {
		var align = this.cols[i].align ? "text-align:" + this.cols[i].align : "";
		
		colgroup += '<col width="' + this.cols[i].width + '">';
		if (hasThead) {
			thead += '<th style="' + align + '" class="' + (this.cols[i].className || "") + '">';
			thead += '<div class="layui-table-cell">';
			if(this.cols[i].type){
				thead += '<div class="all-selected-checkbox">';
				thead += '<input type="checkbox" lay-skin="primary" lay-filter="allCheckbox" name="">';
				thead += '</div>';
			}else{
				thead +=  this.cols[i].title;
			}
			thead += '</div>';
			thead += '</th>';
		}
	}
	colgroup += '</colgroup>';
	if (hasThead) thead += '</tr></thead>';
	return colgroup + thead;
};

/**
 * 渲染内容
 */
Order.prototype.tbody = function () {

	var tbody = '<tbody>';
	for (var i = 0; i < this.data.list.length; i++) {

		var order = this.data.list[i];
		if (i > 0) {
			//分割行
			tbody += '<tr class="separation-row">';
			tbody += '<td colspan="' + this.cols.length + '"></td>';
			tbody += '</tr>';
		}

		//订单项头部
		tbody += '<tr class="header-row">';
		tbody += '<td colspan="9">';
		tbody += '<span class="order-item-header" style="margin-right:10px;">订单号：' + order.order_no + '</span>';
		tbody += '<span class="order-item-header text-color more" style="margin-right:50px;" onclick="showMore(' + order.order_id + ')">更多';
		tbody += '<div class="more-operation" data-order-id="' + order.order_id + '">';
			tbody += '<span>支付流水号：' + order.out_trade_no + '</span>';
		tbody += '</div></span>';

		tbody += '<span class="order-item-header" style="margin-right:50px;">下单时间：' + ns.time_to_date(order.create_time) + '</span>';
		
		tbody += '<span class="order-item-header" style="margin-right:50px;">订单来源：'+ order.order_from_name + (order.is_video_number ? '（视频号）' : '') +'</span>';

		tbody += '</td>';
		tbody += '</tr>';

		var orderitemHtml = '';
		loadImgMagnify();
		orderitemHtml += '<tr class="content-row">';
		for (var k = 0; k < this.cols.length; k++) {
			orderitemHtml += '<td class="' + (this.cols[k].className || "") + '" align="' + (this.cols[k].align || "") + '" style="' + (this.cols[k].style || "") + '" rowspan="1">';
			orderitemHtml += this.cols[k].template(order, order);
			orderitemHtml += '</td>';
		}
		orderitemHtml += '</tr>';

		tbody += orderitemHtml;

		if(order.buyer_message != '') {
			//订单项底部
			tbody += '<tr class="bottom-row">';
			tbody += '<td colspan="9">';
			tbody += '<span class="order-item-header" style="margin-right:10px;">买家备注：' + order.buyer_message + '</span>';
			tbody += '</td>';
			tbody += '</tr>';
		}

	}

	tbody += '</tbody>';
	return tbody;
};

/**
 * 渲染表格
 */
Order.prototype.fetch = function () {
	if (this.data.list.length > 0) {
		return '<table class="layui-table layui-form">' + this.header(true) + '</table><table class="layui-table order-list-table layui-form">' + this.header(false) + this.tbody() + '</table>';
	} else {
		return '<table class="layui-table order-list-table layui-form">' + this.header(true) + '</table>' + '<div class="order-no-data-block"><ul><li><i class="layui-icon layui-icon-tabs"></i> </li><li>暂无订单</li></ul></div>';
	}
};

function showMore(order_id) {
	$(".more-operation[data-order-id]").hide();
	$(".more-operation[data-order-id='" + order_id + "']").show();
	$("body").bind('click',function (e) {
		if (!$(e.target).closest(".order-item-header.more").length) {
			$(".more-operation[data-order-id='" + order_id + "']").hide();
			$("body").unbind('click');
		}
	});
}
$(".layui-colla-title").on("click", function(){
    if($(".layui-colla-title>i").hasClass("layui-icon-down") === false && $(".layui-colla-title>i").hasClass("layui-icon-up") === false){
        $(".layui-colla-title .put-open").html("展开");
    }else if($(".layui-colla-title>i").hasClass("layui-icon-down") === true){
        $(".layui-colla-title .put-open").html("展开");
    }else if($(".layui-colla-title>i").hasClass("layui-icon-up") === true){
        $(".layui-colla-title .put-open").html("收起");
    }
})