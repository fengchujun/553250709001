// 商品选择弹出框
var form, laytpl, element,table;
var goodsSelectObj = {
	mode: 'spu', // 商品模式，spu：商品，sku：商品项
	maxNum: 0, // 最大商品数量
	minNum: 0, // 最小商品数量
	disabled: 0, // 不可选中
	cols: [], // 列名数据源
	filterData: {promotion_type: '', label_id: '', goods_name: ''}, //筛选数据
	goodsIdArr: [],
	skuIdAll: [],
	list:{} // 新方案
};

$(function () {

	layui.use(['form', 'laytpl', 'element'], function () {
		form = layui.form, laytpl = layui.laytpl, element = layui.element;

		$('.select-goods input[type="hidden"]').each(function () {
			goodsSelectObj[$(this).attr('name')] = $(this).val();
		});

		setCols();

		checkGoods();

		table = new Table({
			elem: '#goods_list',
			url: ns.url('shop/goods/goodsSelect'),
			where: {
				is_virtual: goodsSelectObj.is_virtual,
				promotion: goodsSelectObj.promotion,
				goods_class: goodsSelectObj.goods_class,
				is_weigh: goodsSelectObj.is_weigh,
				sale_channel: goodsSelectObj.sale_channel
			},
			cols: goodsSelectObj.cols,
			callback: function (res) {

				var tempGoodsIdArr = [].concat(goodsSelectObj.goodsIdArr);
				var tempSkuIdAll = [].concat(goodsSelectObj.goodsIdArr);

				for (var key in goodsSelectObj.list){
					if (goodsSelectObj.mode == "spu") {
						if(tempGoodsIdArr.indexOf(goodsSelectObj.list[key].goods_id) == -1) {
							tempGoodsIdArr.push(goodsSelectObj.list[key].goods_id);
						}
					}else if (goodsSelectObj.mode == "sku") {
						var skuArr = Object.keys(goodsSelectObj.list[key].selected_sku_list);
						skuArr.forEach(function(item){
							if(tempSkuIdAll.indexOf(parseInt(item.replace('sku_', ''))) == -1) {
								tempSkuIdAll.push(parseInt(item.replace('sku_', '')));
							}
						});
					}
				}
				$.unique(tempGoodsIdArr);
				$.unique(tempSkuIdAll);

				if (goodsSelectObj.mode == "sku") {
					//存储这sku的具体id
					$("input[name='goods_checkbox'][data-goods-id]").each(function () {
						var goods_id = $(this).attr("data-goods-id");
						var tr = $(this).parent().parent().parent();
						var data = getGoodsSkuData(goods_id);
						laytpl(data.sku_list).render(data, function (html) {
							tr.after(html);
							form.render();
							layer.photos({
								photos: '.img-wrap',
								anim: 5
							});
						});
					});
				}

				var isSelectAll = false;

				// 更新商品复选框状态
				if (goodsSelectObj.mode == 'spu') {
					for (var i = 0; i < tempGoodsIdArr.length; i++) {
						var goods = $("input[name='goods_checkbox'][data-goods-id='" + tempGoodsIdArr[i] + "']");
						if (goods.length) {
							goods.prop("checked", true);
							if (goodsSelectObj.disabled == 1) {
								goods.attr("disabled", "disabled");
							}
						}
					}
					if($("input[name='goods_checkbox']").length == $("input[name='goods_checkbox']:checked").length){
						isSelectAll = true;
					}
				} else if (goodsSelectObj.mode == 'sku') {
					for (var i = 0; i < tempSkuIdAll.length; i++) {
						var selected_goods_sku = $("input[name='goods_sku_checkbox'][data-sku-id='" + tempSkuIdAll[i] + "']");
						selected_goods_sku.prop("checked", true);
						if (selected_goods_sku.length) {
							var goods = $("input[name='goods_checkbox'][data-goods-id='" + selected_goods_sku.attr("data-goods-id") + "']");
							goods.prop("checked", true);
							if (goodsSelectObj.disabled == 1) {
								goods.attr("disabled", "disabled");
							}
						}
					}
					if($("input[name='goods_sku_checkbox']").length == $("input[name='goods_sku_checkbox']:checked").length){
						isSelectAll = true;
					}
				}

				if (isSelectAll){
					$('input[name="goods_checkbox_all"]').prop('checked',true);
				}

				form.render();
				dealWithTableSelectedNum();
			}
		});

		//修改一级分类箭头切换
		element.on('collapse(oneCategory)', function (data) {
			$(".layui-colla-title").removeClass("active");
			if (data.show) {
				$(data.title).addClass("active");
			}
		});

		//修改二级分类箭头切换
		element.on('collapse(twoCategory)', function (data) {
			$(".select-goods-classification .select-goods-classification .layui-colla-title").removeClass("active");
			if (data.show) {
				$(data.title).addClass("active");
			}
		});

		//搜索商品名称或编码
		form.on('submit(search)', function (data) {
			formSearch();
		});

		//搜索类型切换
		form.on('select(select_type)', function (data) {
			formSearch();
		});

		//商品标签筛选
		form.on('select(label_id)', function (data) {
			formSearch();
		});

		//商品类型筛选
		form.on('select(goods_class)', function (data) {
			formSearch();
		});

		// 勾选商品
		form.on('checkbox(goods_checkbox_all)', function (data) {
			var all_checked = data.elem.checked;
			$("input[name='goods_checkbox']").each(function () {
				var checked = $(this).prop('checked');
				if (all_checked != checked) {
					$(this).next().click();
				}
			});
		});

		// 勾选商品
		form.on('checkbox(goods_checkbox)', function (data) {
			var goods_id = $(data.elem).attr("data-goods-id"),
				json = {};

			$("input[name='goods_sku_checkbox'][data-goods-id='" + goods_id + "']").prop("checked", data.elem.checked);
			form.render();

			var spuLen = $("input[name='goods_checkbox'][data-goods-id=" + goods_id + "]:checked").length;
			if (spuLen) {
				json = JSON.parse($("input[name='goods_json'][data-goods-id=" + goods_id + "]").val());
				json.selected_sku_list = {};

				delete json.LAY_INDEX;
				delete json['LAY_TABLE_INDEX'];

				goodsSelectObj.list['goods_' + goods_id] = json;
			} else {
				delete goodsSelectObj.list['goods_' + goods_id];
			}

			// 选择商品多规格项
			if (goodsSelectObj.mode == "sku" && goodsSelectObj.list['goods_' + goods_id]) {
				$("input[name='goods_sku_json'][data-goods-id=" + goods_id + "]").each(function () {
					var item = JSON.parse($(this).val());
					goodsSelectObj.list['goods_' + goods_id].selected_sku_list['sku_' + item.sku_id] = item;
				});
			}
			dealWithTableSelectedNum();
		});

		// 勾选商品SKU
		form.on('checkbox(goods_sku_checkbox)', function (data) {
			var goods_id = $(data.elem).attr("data-goods-id"),
				sku_id = $(data.elem).attr("data-sku-id"),
				json = {};

			if ($("input[name='goods_sku_checkbox'][data-goods-id='" + goods_id + "']:checked").length) {

				json = JSON.parse($("input[name='goods_json'][data-goods-id=" + goods_id + "]").val());
				json.selected_sku_list = {};

				delete json.LAY_INDEX;
				delete json.LAY_TABLE_INDEX;

				if(!goodsSelectObj.list['goods_' + goods_id]){
					goodsSelectObj.list['goods_' + goods_id] = json;
				}

				var skuVal = JSON.parse($("input[name='goods_sku_json'][data-sku-id=" + sku_id + "]").val());
				if(data.elem.checked){
					goodsSelectObj.list['goods_' + goods_id].selected_sku_list['sku_' + sku_id] = skuVal;
				}else{
					delete goodsSelectObj.list['goods_' + goods_id].selected_sku_list['sku_' + sku_id];
				}

				$("input[name='goods_checkbox'][data-goods-id='" + goods_id + "']").prop("checked", true);
			} else {

				$("input[name='goods_checkbox'][data-goods-id='" + goods_id + "']").prop("checked", false);
				if(goodsSelectObj.list['goods_' + goods_id]){
					delete goodsSelectObj.list['goods_' + goods_id].selected_sku_list['sku_' + sku_id];
				}

				// 没有选中，则清空整个商品对象
				if(Object.keys(goodsSelectObj.list['goods_' + goods_id].selected_sku_list).length == 0){
					delete goodsSelectObj.list['goods_' + goods_id];
				}
			}

			goodsSelectObj.filterData.goods_ids = goodsSelectObj.goodsIdArr.toString();

			dealWithTableSelectedNum();
			form.render();
		});

		$(".select-goods .select-goods-left dd").hover(function () {
			$(this).addClass("active");
		}, function () {
			$(this).removeClass("active");
		});

		$("body").off("click", ".select-goods-left .marketing-campaign dd").on("click", ".select-goods-left .marketing-campaign dd", function () {
			$(this).addClass("text-color").siblings().removeClass("text-color");
			goodsSelectObj.filterData.promotion_type = $(this).attr("data-type");
			table.reload({
				page: {
					curr: 1
				},
				where: goodsSelectObj.filterData
			});
		});

		$("body").off("click", ".select-goods-left .commodity-group dd").on("click", ".select-goods-left .commodity-group dd", function () {
			$(this).addClass("text-color").siblings().removeClass("text-color");
			goodsSelectObj.filterData.label_id = $(this).attr("data-group-id");
			table.reload({
				page: {
					curr: 1
				},
				where: goodsSelectObj.filterData
			});
		});

		$("body").off("click", ".select-goods-left dl").on("click", ".select-goods-left dl", function () {
			if ($(this).hasClass("fold")) {
				$(this).removeClass("fold");
			} else {
				$(this).addClass("fold");
			}
		});

		$("body").off("click", ".select-goods-left dd").on("click", ".select-goods-left dd", function (event) {
			$(this).parents("dl").removeClass("fold");
			$(this).parents("dl").siblings().addClass("fold");
			event.stopPropagation();
		});

		//分类切换
		$("body").off("click", ".classification-item").on("click", ".classification-item", function (event) {
			var categoryId = $(this).attr("data-category_id");
			$(".classification-item").removeClass("text-color border-after-color");
			$(this).addClass("text-color border-after-color");
			$("input[name='category_id']").val(categoryId);
			formSearch();
			event.stopPropagation();
		});

		// 商品信息展开
		$("body").off("click", ".contraction").on("click", ".contraction", function () {
			var goods_id = $(this).attr("data-goods-id");
			var open = $(this).attr("data-open");
			if (open == 1) {
				$(this).children("span").text("+");
				$(".js-sku-list-" + goods_id).hide();
			} else {
				$(this).children("span").text("-");
				$(".js-sku-list-" + goods_id).show();
			}
			$(this).attr("data-open", (open == 0 ? 1 : 0));
		});

	});

});

// 设置列名
function setCols() {
	switch (goodsSelectObj.promotion) {
		case '':
		case 'all':
		case 'module':
		case 'fenxiao':
		case 'bargain':
			goodsSelectObj.cols = [
				[
					{
						title: '<input type="checkbox" name="goods_checkbox_all" lay-skin="primary" lay-filter="goods_checkbox_all">',
						unresize: 'false',
						width: '8%',
						templet: '#checkbox',
					},
					{
						title: '商品',
						unresize: 'false',
						width: '62%',
						templet: '#goods_info'
					},
					{
						field: 'goods_stock',
						title: '库存',
						unresize: 'false',
						width: '15%'
					},
					{
						field: 'goods_class_name',
						title: '商品类型',
						unresize: 'false',
						width: '15%'
					}
				]
			];
			break;
		case 'pintuan':
			goodsSelectObj.cols = [
				[{
					title: '<input type="checkbox" name="goods_checkbox_all" lay-skin="primary" lay-filter="goods_checkbox_all">',
					unresize: 'false',
					width: '8%',
					templet: '#checkbox'
				}, {
					field: 'pintuan_name',
					title: '拼团活动',
					unresize: 'false',
					width: '20%',
				}, {
					title: '拼团商品',
					unresize: 'false',
					width: '33%',
					templet: '#goods_info'
				}, {
					field: 'pintuan_num',
					title: '参团人数',
					unresize: 'false',
					width: '13%'
				}, {
					field: 'group_num',
					title: '开团团队',
					unresize: 'false',
					width: '13%'
				}, {
					field: 'order_num',
					title: '购买人数',
					unresize: 'false',
					width: '13%'
				}]
			];
			break;
		case 'groupbuy':
			goodsSelectObj.cols = [
				[{
					title: '<input type="checkbox" name="goods_checkbox_all" lay-skin="primary" lay-filter="goods_checkbox_all">',
					unresize: 'false',
					width: '8%',
					templet: '#checkbox'
				}, {
					title: '团购商品',
					unresize: 'false',
					width: '47%',
					templet: '#goods_info'
				}, {
					field: 'price',
					title: '商品原价',
					unresize: 'false',
					width: '15%',
					templet: function (data) {
						return '￥' + data.price;
					}
				}, {
					field: 'groupbuy_price',
					title: '团购价格',
					unresize: 'false',
					width: '15%',
					templet: function (data) {
						return '￥' + data.groupbuy_price;
					}
				}, {
					field: 'buy_num',
					title: '起购量',
					unresize: 'false',
					width: '15%'
				}]
			];
			break;
		case 'presale':
			goodsSelectObj.cols = [
				[{
					title: '<input type="checkbox" name="goods_checkbox_all" lay-skin="primary" lay-filter="goods_checkbox_all">',
					unresize: 'false',
					width: '8%',
					templet: '#checkbox'
				}, {
					field: 'presale_name',
					title: '活动名称',
					unresize: 'false',
					width: '20%',
				}, {
					title: '预售商品',
					unresize: 'false',
					width: '50%',
					templet: '#goods_info'
				}, {
					field: 'presale_stock',
					title: '库存',
					unresize: 'false',
					width: '13%'
				}]
			];
			break;
		case 'pinfan':
			goodsSelectObj.cols = [
				[{
					title: '<input type="checkbox" name="goods_checkbox_all" lay-skin="primary" lay-filter="goods_checkbox_all">',
					unresize: 'false',
					width: '8%',
					templet: '#checkbox'
				}, {
					field: 'pintuan_name',
					title: '拼团返利',
					unresize: 'false',
					width: '20%',
				}, {
					title: '拼团商品',
					unresize: 'false',
					width: '33%',
					templet: '#goods_info'
				}, {
					field: 'pintuan_num',
					title: '参团人数',
					unresize: 'false',
					width: '13%'
				}, {
					field: 'group_num',
					title: '开团团队',
					unresize: 'false',
					width: '13%'
				}, {
					field: 'order_num',
					title: '购买人数',
					unresize: 'false',
					width: '13%'
				}]
			];
			break;
	}

	// 服务项目商品展示列名
	/*
	if(goodsSelectObj.goods_class == 4){
		goodsSelectObj.cols = [
			[
				{
					title: '<input type="checkbox" name="goods_checkbox_all" lay-skin="primary" lay-filter="goods_checkbox_all">',
					unresize: 'false',
					width: '8%',
					templet: '#checkbox',
				},
				{
					title: '商品',
					unresize: 'false',
					width: '62%',
					templet: '#goods_info'
				},
				{
					field: 'goods_stock',
					title: '库存',
					unresize: 'false',
					width: '15%'
				},
				{
					field: 'goods_class_name',
					title: '商品类型',
					unresize: 'false',
					width: '15%'
				}
			]
		];
	}
	*/

}

// 初始化选中商品，并且赋值
function checkGoods() {

	// 已选中的商品id
	var selectId = localStorage.getItem('goods_select_id') ? localStorage.getItem('goods_select_id').split(',') : [];

	if(selectId.length == 0) return;

	// 查询已选商品集合，并且赋值
	$.ajax({
		url: ns.url("shop/goods/checkgoods"),
		data: {goods_ids: selectId.toString(), mode: goodsSelectObj.mode},
		dataType: 'JSON',
		type: 'POST',
		async: false,
		success: function (res) {
			if (res.code >= 0 && res.data) {
				var data = res.data;
				if (!data) return;
				if (goodsSelectObj.mode == 'spu') {
					data.forEach(function (item) {
						goodsSelectObj.list['goods_' + item.goods_id] = item;
						goodsSelectObj.goodsIdArr.push(item.goods_id);
					});
				} else {
					data.forEach(function (item) {
						var goods = {
							goods_id: item.goods_id,
							goods_name: item.goods_name,
							goods_class_name: item.goods_class_name,
							goods_image: item.goods_image,
							price: item.goods_price,
							goods_stock: item.goods_stock,
							is_virtual: item.is_virtual
						};
						var sku = {
							sku_id: item.sku_id,
							sku_name: item.sku_name,
							price: item.price,
							stock: item.stock,
							sku_image: item.sku_image,
							goods_id: item.goods_id,
							goods_class_name: item.goods_class_name
						};

						if (!goodsSelectObj.list['goods_' + item.goods_id]) {
							goods.selected_sku_list = {};
							goodsSelectObj.list['goods_' + item.goods_id] = goods;
						}

						goodsSelectObj.list['goods_' + item.goods_id].selected_sku_list['sku_' + item.sku_id] = sku;
						goodsSelectObj.skuIdAll.push(item.sku_id);

					});
				}

				goodsSelectObj.filterData.goods_ids = goodsSelectObj.goodsIdArr.toString();

				dealWithTableSelectedNum();
			}
		}
	});
}

//公共搜索方法
function formSearch() {
	var data = {};
	data.search_text = $("input[name='search_text']").val();
	data.select_type = $("select[name='select_type']").val();
	data.label_id = $("select[name='label_id']").val();
	data.goods_class = $("select[name='goods_class']").val();
	data.category_id = $("input[name='category_id']").val();
	data.goods_ids = getSelectGoodsIds().toString();

	table.reload({
		page: {
			curr: 1
		},
		where: data
	});
}

function getGoodsSkuData(goods_id) {
	var list = JSON.parse($("input[name='goods_sku_list_json'][data-goods-id='" + goods_id + "']").val().toString());
	var sku_list = $("#skuList").html();
	var checked = $("input[name='goods_checkbox'][data-goods-id='" + goods_id + "']:checked").length ? true : false;
	return {
		checked: checked,
		sku_list: sku_list,
		list: list
	};
}

//在表格底部增加了一个容器
function dealWithTableSelectedNum() {
	if (goodsSelectObj.mode == 'spu') {
		$(".layui-table-bottom-left-container").html('已选择 ' + Object.keys(goodsSelectObj.list).length + ' 个商品');
	} else {
		var count = 0;
		for(var key in goodsSelectObj.list){
			count += Object.keys(goodsSelectObj.list[key].selected_sku_list).length;
		}
		$(".layui-table-bottom-left-container").html('已选择 ' + count + ' 个商品');
	}
}

//表单的选中商品数据
function getSelectGoodsIds(){
	let goods_ids = [];
	Object.values(goodsSelectObj.list).forEach(function (item, index) {
		goods_ids.push(item.goods_id);
	})
	return goods_ids;
}

// 保存回调事件
function selectGoodsListener(callback) {
	goodsSelectObj.goodsIdArr = [];
	goodsSelectObj.skuIdAll = [];

	for (var key in goodsSelectObj.list){
		if (goodsSelectObj.mode == "spu") {
			goodsSelectObj.goodsIdArr.push(goodsSelectObj.list[key].goods_id);
		}else if (goodsSelectObj.mode == "sku") {
			var skuArr = Object.keys(goodsSelectObj.list[key].selected_sku_list);
			skuArr.forEach(function(item){
				goodsSelectObj.skuIdAll.push(parseInt(item.replace('sku_','')));
			});
		}
	}

	var res = goodsSelectObj.list;
	var num = 0;

	if (goodsSelectObj.mode == "spu") {

		num = goodsSelectObj.goodsIdArr.length;

		if (goodsSelectObj.promotion) {
			res = goodsSelectObj.goodsIdArr;
		}

	} else if (goodsSelectObj.mode == "sku") {

		num = goodsSelectObj.skuIdAll.length;

		if (goodsSelectObj.promotion) {
			res = goodsSelectObj.skuIdAll;
		}

	}

	if (num == 0) {
		layer.msg('请选择商品');
		return;
	}
	if (goodsSelectObj.maxNum && goodsSelectObj.maxNum > 0 && num > goodsSelectObj.maxNum) {
		layer.msg("所选商品数量不能超过" + goodsSelectObj.maxNum + '件');
		return;
	}

	if (goodsSelectObj.minNum && goodsSelectObj.minNum > 0 && num < goodsSelectObj.minNum) {
		layer.msg("所选商品数量不能少于" + goodsSelectObj.minNum + '件');
		return;
	}

	callback(res);
}

// 清空 已选商品 回调事件
function clearGoodsListener(callback) {
	var res = {};
	if (goodsSelectObj.promotion) {
		res = [];
	}
	callback(res);
}