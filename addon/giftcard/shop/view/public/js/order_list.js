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
			var json = {};
			json.order_id = order.order_id;
			json.order_no = order.order_no;
			json.giftcard_id = order.giftcard_id;
			// if(order.order_type == 4 && order.order_data_status == 3){
				// var h = '<div class="sub-selected-checkbox" data-json='+ JSON.stringify(json) +' data-id='+ order.order_id +' disabled>';
				// h += '<input type="checkbox" lay-skin="primary" lay-filter="subCheckbox" name="" disabled>';
				// h += '</div>';
			// }else{
				var h = '<div class="sub-selected-checkbox" data-json='+ JSON.stringify(json) +' data-id='+ order.order_id +' >';
				h += '<input type="checkbox" lay-skin="primary" lay-filter="subCheckbox" name="" >';
				h += '</div>';
			// }
			return h;
		}
	},
	{
		title: '<span>礼品卡</span>',
		width: "30%",
		className: "card-info",
		template: function (orderitem, order) {
			var h = '';
			h += '<div class="order-goods">';
				h += '<div class="img-block">';
					h += '<img layer-src="' + ns.img(orderitem.card_cover.split(",")[0]) + '" src="' + ns.img(orderitem.card_cover.split(",")[0]) + '">';
				h += '</div>';
				h += '<div class="info">';
					h += '<a href="' + ns.href("giftcard://shop/order/detail", {order_id: orderitem.order_id}) + '" target="_blank" title="' + orderitem.order_name + '" class="multi-line-hiding text-color-sub">' + orderitem.order_name + '</a>';
				h += '</div>';
			h += '</div>';
			
			return h;
		}
	},
	{
		title: "数量",
		width: "8%",
		align: "right",
		className: "order-price",
		template: function (orderitem, order) {
			var h = '<div style="text-align: center;">';
				h += '<span>' + orderitem.card_price + '</span><br>';
				h += '<span>' + orderitem.num + '件</span>';
			h += '</div>';
			return h;
		}
	},
	{
		title: "权益类型",
		width: "10%",
		align: "right",
		className: "card-right-type",
		template: function (orderitem, order) {
			var h = '<div style="padding-right: 15px;">';
			h += '<span>' + (orderitem.card_right_type=='goods' ? '礼品卡' : '储值卡') + '</span>';
			h += '</div>';
			return h;
		}
	},
	{
		title: "实付金额(元)",
		width: "8%",
		align: "right",
		className: "order-money",
		merge: true,
		template: function (orderitem, order) {
			var h = '<div style="padding-right: 15px;">';
			h += '<span>' + orderitem.pay_money + '</span>';
			h += '</div>';
			return h;
		}
	},
	{
		title: "买家",
		width: "14%",
		align: "left",
		className: "goods-num",
		template: function (orderitem, order) {
			var h = '<div style="text-align: left;">';
				h += '<a href="' + ns.href("shop/member/editmember", {member_id: orderitem.member_id}) + '" target="_blank" class="multi-line-hiding text-color-sub text-color">' +orderitem.nickname + '</a>';
				h += '<span>' + orderitem.mobile  + '</span>';
			h += '</div>';
			return h;
		}
	},
	{
		title: "订单状态",
		width: "10%",
		align: "center",
		className: "transaction-status",
		merge: true,
		template: function (orderitem, order) {
			var html = '<div>' + order.order_status_name + '</div>';
			return html;
		}
	},
	{
	    title : "支付时间",
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
		width:"11%",
		merge: true,
		template: function (orderitem, order) {
			var html='';
			html += '<div>';
				html += '<a class="layui-btn  text-color" href="'+ns.href("giftcard://shop/order/detail", {order_id: orderitem.order_id}) +'">详情</a>';
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

		tbody += '<span class="order-item-header" style="margin-right:50px;">支付方式：'+ order.pay_type_name +'</span>';

		tbody += '</td>';
		tbody += '</tr>';

		var orderitemHtml = '';
		loadImgMagnify();
		orderitemHtml += '<tr class="content-row">';
		for (var k = 0; k < this.cols.length; k++) {
			orderitemHtml += '<td class="' + (this.cols[k].className || "") + '" align="' + (this.cols[k].align || "") + '" style="' + (this.cols[k].style || "") + '" rowspan="' + order.length + '">';
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