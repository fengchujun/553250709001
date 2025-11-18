var selectedSkuId = [], sku_list = [], time_list = [], form, laydate, timeTable, minDate, currentDate = new Date(),
	repeat_flag = false; //防重复标识;

layui.use(['form', 'laydate'], function () {
	form = layui.form;
	laydate = layui.laydate;
	minDate = "";
	form.render();

	currentDate.setDate(currentDate.getDate() + 30);

	if ($("input[name='id']").val()) {
		time_list = JSON.parse($('input[name="time_list"]').val());
		sku_list = JSON.parse($('input[name="sku_list"]').val());
		
		laydate.render({
			elem: '#start_time',
			type: 'datetime',
			done: function (value) {
				minDate = value;
				reRender();
			}
		});
		laydate.render({
			elem: '#end_time',
			type: 'datetime'
		});
	} else {
		laydate.render({
			elem: '#start_time',
			type: 'datetime',
			value: new Date(),
			done: function (value) {
				minDate = value;
				reRender();
			}
		});
		laydate.render({
			elem: '#end_time',
			type: 'datetime',
			value: new Date(currentDate)
		});
	}

	renderTimeTable();
	initTimeList();
	renderTable();

	form.on('submit(save)', function (data) {
		if (verify()) {
			var goods_data = [];
			if (data.field.id) {
				for (var i in sku_list) {
					if (sku_list[i]['is_select'] == 1) {
						goods_data.push(sku_list[i]);
					}
				}
				data.field.sku_list = goods_data;
			} else {
				for (var i in sku_list) {
					var goods = {};
					goods.goods_id = sku_list[i]['goods_id'];
					if (goods_data.length == 0) {
						goods_data.push(goods);
					} else {
						$.each(goods_data, function (index, event) {
							if (goods_data.length == index + 1 && event.goods_id != goods.goods_id) {
								goods_data.push(goods);
							}
						})
					}
				}
				$.each(goods_data, function (i, e) {
					goods_data[i]['sku_list'] = [];
					$.each(sku_list, function (index, event) {
						if (event.goods_id == e.goods_id) {
							goods_data[i]['sku_list'].push(event);
						}
					})
				});
				data.field.goods_data = goods_data;
			}

			var goods_ids = [];
			goods_data.forEach(function (item) {
				goods_ids.push(item.goods_id);
			});
			data.field.goods_ids = goods_ids.toString();

			if (repeat_flag) return;
			repeat_flag = true;

			$.ajax({
				url: data.field.id ? ns.url("seckill://shop/seckill/updateGoods") : ns.url("seckill://shop/seckill/addGoods"),
				data: data.field,
				dataType: 'JSON',
				type: 'POST',
				success: function (res) {
					repeat_flag = false;

					if (res.code == 0) {
						layer.confirm(data.field.id ? '编辑成功' : '添加成功', {
							title: '操作提示',
							btn: ['返回列表', data.field.id ? '继续操作' : '继续添加'],
							closeBtn: 0,
							yes: function (index, layero) {
								location.hash = ns.hash("seckill://shop/seckill/goodslist")
								layer.close(index);
							},
							btn2: function (index, layero) {
								listenerHash(); // 刷新页面
								layer.close(index);
							}
						});
					} else {
						layer.msg(res.message);
					}
				}
			});
		}
	});

	form.verify({
		time: function (value) {
			var now_time = (new Date()).getTime();
			var start_time = (new Date($("#start_time").val())).getTime();
			var end_time = (new Date(value)).getTime();
			var old_end_time = $("#old_end_time").val();
			if (now_time > end_time) {
				return '结束时间不能小于当前时间!'
			}
			if (start_time > end_time) {
				return '结束时间不能小于开始时间!';
			}
			if (old_end_time && old_end_time > end_time) {
				return '结束时间不能小于之前设置的结束时间!';
			}
		},
		seckilltime: function (value, item) {
			if (value == "" || value == 0) {
				return '请选择时间段';
			}
		}
	});

	$("body").off("click", ".no-participation").on("click", ".no-participation", function () {
		$(this).text("参与");
		$(this).parents("tr").find("input").each(function (index, item) {
			$(item).attr("readonly", true);
			$(item).attr("disabled", true);
			$(item).addClass("forbidden");
			$(item).attr("lay-verify", "");
		});

		$(this).addClass("participation").removeClass("no-participation");
		sku_list[$(this).parents("tr").attr("data-index")].is_select = 0;
	});

	$("body").off("click", ".participation").on("click", ".participation", function () {
		$(this).text("不参与");
		$(this).parents("tr").find("input").each(function (index, item) {
			$(item).attr("readonly", false);
			$(item).attr("disabled", false);
			$(item).removeClass("forbidden");
		});

		$(this).removeClass("participation").addClass("no-participation");
		sku_list[$(this).parents("tr").attr("data-index")].is_select = 1;
	});

});

// 重新渲染结束时间
function reRender() {
	$("#end_time").remove();
	$(".end-time").html('<input type="text" id="end_time" name="end_time" placeholder="请输入结束时间" lay-verify="required|time" class = "layui-input len-mid" autocomplete="off"> ');
	laydate.render({
		elem: '#end_time',
		type: 'datetime',
		min: minDate
	});
}

function verify() {

	if ($('input[name="seckill_time_id"]').val() == '') {
		layer.msg('请选择场次', {icon: 5, anim: 6});
		return false;
	}

	if (sku_list.length == 0) {
		layer.msg('请选择商品', {icon: 5, anim: 6});
		return false;
	}

	for (var i in sku_list) {
		if (sku_list[i]['max_buy'] < 0) {
			layer.msg('商品限购不可小于0', {icon: 5, anim: 6});
			return false;
		}

		if (sku_list[i]['seckill_price'] <= 0) {
			layer.msg('秒杀价不可小于等于0', {icon: 5, anim: 6});
			return false;
		}
		if (sku_list[i]['seckill_stock'] > sku_list[i]['stock']) {
			layer.msg('秒杀库存不能大于商品库存', {icon: 5, anim: 6});
			return false;
		}
	}

	return true;
}

function delGoods(obj, id) {
	var sku_ids = [];
	for (let i = 0; i < sku_list.length; i++) {
		if (sku_list[i].sku_id == parseInt(id)) {
			sku_list.splice(i, 1);
		}
	}
	for (let i = 0; i < sku_list.length; i++) {
		sku_ids.push(sku_list[i].sku_id);
	}
	$(obj).parents("tr").remove();
	$("#goods_num").html(sku_list.length);
	selectedSkuId = sku_ids.toString();
}

// 表格渲染
function renderTable() {
	//展示已知数据
	table = new Table({
		elem: '#selected_goods_list',
		page: false,
		limit: Number.MAX_VALUE,
		cols: [
			[
				{
					width: "3%",
					type: 'checkbox',
					unresize: 'false'
				},
				{
					field: 'sku_name',
					title: '商品名称',
					width: '22%',
					unresize: 'false',
					templet: function (data) {
						var html = '';
						html += `
							<div class="goods-title">
								<div class="goods-img">
									<img src="${ns.img(data.sku_image)}" alt="">
								</div>
								<p class="multi-line-hiding goods-name" data-goods_id="${data.goods_id}" data-sku_id="${data.sku_id}" title="${data.sku_name}">${data.sku_name}</p>
							</div>
						`;
						return html;
					}
				}, {
				field: 'price',
				title: '原价',
				unresize: 'false',
				align: 'left',
				width: '14%',
				templet: function (data) {
					return '<p class="line-hiding" title="' + data.price + '">￥<span>' + data.price + '</span></p>';
				}
			}, {
				field: 'stock',
				title: '库存',
				unresize: 'false',
				width: '9%',
				templet: function (data) {
					return '<p class="stock">' + data.stock + '</p>';
				}
			}, {
				field: 'max_buy',
				title: '<span>限购（0为不限购）</span>',
				unresize: 'false',
				width: '14%',
				templet: '#maxBuy'
			}, {
				title: '<span title="秒杀价">秒杀价</span>',
				unresize: 'false',
				width: '15%',
				templet: '#seckillPrice'
			}, {
				title: '<span title="秒杀库存">秒杀库存</span>',
				unresize: 'false',
				width: '12%',
				templet: '#seckillStock'
			}, {
				title: '操作',
				toolbar: '#operation',
				unresize: 'false',
			}]
		],
		data: sku_list,
		toolbar: '#toolbarOperation'
	});

	table.toolbar(function (obj) {
		if (obj.data.length < 1) {
			layer.msg('请选择要操作的数据');
			return;
		}
		switch (obj.event) {
			case "seckill-purchase":
				editInput(0, obj);
				break;
			case "seckill-price":
				editInput(1, obj);
				break;
			case "seckill-stock":
				editInput(2, obj);
				break;
		}
	});
}

function editInput(textIndex = 0, data) {
	var text = [{
		name: '限购',
		value: 'max_buy'
	}, {
		name: '秒杀价',
		value: 'seckill_price'
	}, {
		name: '秒杀库存',
		value: 'seckill_stock'
	}];

	console.log(text[textIndex].value)
	if(text[textIndex].value == 'seckill_price'){
		var html =`
			<div class="layui-form form-wrap">
			<div class="layui-form-item">
             <label class="layui-form-label"><span class="required">*</span>优惠方式：</label>
				<div class="layui-input-block">
						<input type="radio" name="seckill_type" value="1" class="seckill_type_input" onclick="switch_type(1)" title="固定价格" lay-filter="seckill_type" checked>
						<input type="radio" name="seckill_type" value="2" class="seckill_type_input" onclick="switch_type(2)"  title="打折" lay-filter="seckill_type">
						<input type="radio" name="seckill_type" value="3" class="seckill_type_input" onclick="switch_type(3)" title="减价" lay-filter="seckill_type">
				</div>
				<label class="layui-form-label seckill-title"><span class="required">*</span>固定价格：</label>
				<div class="layui-input-block">
					<input type="text" name="bargain_edit_input" lay-verify="required" autocomplete="off" class="layui-input len-mid" placeholder="请输入固定价格">
				</div>
				<div class="word-aux seckill-word"></div>
			</div>
			</div>
		`
	}else{
		var html =`<div class="layui-form-item">
				<label class="layui-form-label"><span class="required">*</span>${text[textIndex].name}：</label>
				<div class="layui-input-block">
					<input type="text" name="bargain_edit_input" lay-verify="required" autocomplete="off" class="layui-input len-mid" placeholder="请输入${text[textIndex].name}">
				</div>
			</div>	`
	}

	layer.open({
		type: 1,
		title: "修改" + text[textIndex].name,
		area: ['600px'],
		btn: ["保存", "返回"],
		content:html,
		success:function(layero, index){
			form.on('radio(seckill_type)', function(data){
				switch_type(data.value)
			});
			form.render();
		},
		yes: function (index, layero) {
			var val = $("input[name='bargain_edit_input']").val();
			if (!val) {
				layer.msg("请输入" + text[textIndex].name);
				return false;
			}
			var type =  $("input[name='seckill_type']:checked").val();
			if(type == 1 && val<= 0){
				layer.msg("固定价格需大于0")
				return false;
			}
			if(type == 2 && (val <= 0 || val > 10)){
				layer.msg("折扣率需大于0且小于等于10，可保留两位小数")
				return false;
			}
			if(type == 3 && val< 0){
				layer.msg("减价金额需大于等于0")
				return false;
			}
			var error_info = '';
			data.data.forEach(function (item, index) {
				sku_list.forEach(function (skuItem, skuIndex) {
					if (item.sku_id == skuItem.sku_id) {
						if(text[textIndex].value == 'seckill_price'){
							var new_val = 0;
							switch (type) {
								case '1': //固定价格
									new_val = val;
									break;
								case '2'://打折
									new_val = (sku_list[skuIndex]['price'] * 0.1 * val).toFixed(2)
									break;
								case '3': //减价
									new_val= (sku_list[skuIndex]['price'] -val).toFixed(2);
									break;
							}
							if(new_val <=0){
								  console.log(sku_list[skuIndex])
								  error_info = sku_list[skuIndex]['sku_name']+'设置后秒杀价为0元,请检查';
                                  return false;
							}
							sku_list[skuIndex][text[textIndex].value] = new_val;
						}else{
							sku_list[skuIndex][text[textIndex].value] = val;
						}
					}
				})
			});

			if(error_info){
				layer.msg(error_info)
				return
			}

			renderTable();
			layer.closeAll();
		}
	});
}



function switch_type(obj){
	$(".seckill-word").text("")
	 switch (obj) {
		 case '1':
		 	 $(".seckill-title").html("<span class=\"required\">*</span>固定价格：")
			 $("input[name='bargain_edit_input']").attr("placeholder",'请输入固定价格')
		 	break;
		 case '2':
			 $(".seckill-title").html("<span class=\"required\">*</span>折扣率：")
			 $("input[name='bargain_edit_input']").attr("placeholder",'请输入折扣率')
			 $(".seckill-word").text("折扣率需大于0且小于等于10，可保留两位小数")
		 	break;
		 case '3':
			 $(".seckill-title").html("<span class=\"required\">*</span>优惠金额：")
			 $("input[name='bargain_edit_input']").attr("placeholder",'请输入优惠金额')
		 	break;
	 }
}

/* 商品 */
function addGoods() {
	goodsSelect(function (data) {

		sku_list = [];

		var sku_ids = [];
		for (var key in data) {
			for (var sku in data[key].selected_sku_list) {
				var item = data[key].selected_sku_list[sku];
				item.seckill_price = item.price;
				item.max_buy = 1;
				item.seckill_stock = item.stock;
				sku_ids.push(item.sku_id);
				sku_list.push(item);
			}
		}
		renderTable();
		selectedSkuId = sku_ids;
		$("#goods_num").html(sku_list.length)
	}, selectedSkuId, {mode: "sku"});
}

function setGoodsSku(type, sku_id, obj) {
	$.each(sku_list, function (i, e) {
		if (sku_id == e.sku_id) {
			sku_list[i][type] = $(obj).val();
		}
	})
}

function addSeckillTime() {
	layer.open({
		type: 1,
		title: "场次选择",
		area: ['900px', '620px'],
		btn: ['保存', '返回'],
		content: $('#seckillTime').html(),
		success: function (layero) {
			renderTimeTable();
		},
		yes: function (index, layero) {
			var select_time_id = [];
			time_list = [];
			$('div[lay-id="seckill_time_list"] .time-select').each(function (i, e) {
				if ($(e).is(":checked")) {
					let time = {};
					time.start_time = $(e).attr('data-time-start');
					time.end_time = $(e).attr('data-time-end');
					time.id = $(e).attr('data-time-id');
					time.name = $(e).attr('data-name');

					select_time_id.push($(e).attr('data-time-id'));
					time_list.push(time);
				}
			});
			$('input[name="seckill_time_id"]').val(select_time_id.toString());
			refreshTimeList();
			layer.closeAll();
		}
	});
}

function refreshTimeList() {
	var html = '';
	for (let i in time_list) {
		let start_time = transformSeckillTime(time_list[i]['start_time']);
		let end_time = transformSeckillTime(time_list[i]['end_time']);
		let name = time_list[i]['name'];
		let id = time_list[i]['id'];
		html += `
				<li class="time-label" title="${start_time} - ${end_time}">
				   <span>${name}</span>
				   <i class="layui-icon layui-icon-close" onclick="delTime(${id}, this)"></i>
				</li>
			`;
	}
	$('.time-label-list ul').html(html);
}

function transformSeckillTime(time) {
	time = parseFloat(time);
	var hour = parseInt(time / 3600);
	var minute = parseInt((time % 3600) / 60);
	var second = parseInt(time % 60);

	if (hour < 10) hour = '0' + hour;
	if (minute < 10) minute = '0' + minute;
	if (second < 10) second = '0' + second;

	return hour + ':' + minute + ':' + second;
}

function delTime(id, obj) {
	time_list.splice(time_list.indexOf(id), 1);
	let time = [];
	for (let i in time_list) {
		if (time_list[i]['id'] != id) {
			time.push(time_list[i]['id']);
		}
	}
	$('input[name="seckill_time_id"]').val(time.toString());
	$(obj).parents('li').remove();
}

// 场次渲染
function renderTimeTable() {
	$.ajax({
		url: ns.url("seckill://shop/seckill/lists"),
		dataType: 'JSON',
		type: 'POST',
		success: function (res) {
			if (res.code < 0) {
				layer.msg(res.message);
				return false;
			}

			var time_data = res.data;
			for (let i in time_data) {
				time_data[i]['is_select'] = 0;
				for (let j in time_list) {
					if (time_list[j]['id'] == time_data[i]['id']) {
						time_data[i]['is_select'] = 1;
					}
				}
			}
			timeTable = new Table({
				elem: '#seckill_time_list',
				page: false,
				height: 380,
				limit: Number.MAX_VALUE,
				cols: [
					[{
						title: '<input type="checkbox" name="time_checkbox_all" lay-skin="primary" lay-filter="time_checkbox_all">',
						unresize: 'false',
						width: '10%',
						templet: '#timecheckbox',
					}, {
						field: 'name',
						title: '场次名称',
						unresize: 'false',
						align: 'left',
						width: '30%'
					}, {
						field: 'seckill_start_time_show',
						title: '开始时间',
						unresize: 'false',
						width: '25%'
					}, {
						field: 'seckill_end_time_show',
						title: '结束时间',
						unresize: 'false',
						width: '25%'
					}]
				],
				data: time_data
			});

			// 勾选商品
			form.on('checkbox(time_checkbox_all)', function (data) {
				var all_checked = data.elem.checked;
				$("input[name='time_checkbox']").each(function () {
					var checked = $(this).prop('checked');
					if (all_checked != checked) {
						$(this).next().click();
					}
				});
			});

		}
	});

}

function initTimeList() {
	var html = '';
	for (let i in time_list) {
		let start_time = transformSeckillTime(time_list[i]['seckill_start_time']);
		let end_time = transformSeckillTime(time_list[i]['seckill_end_time']);
		let name = time_list[i]['name'];
		let id = time_list[i]['id'];
		html += `
				<li class="time-label" title="${start_time} - ${end_time}">
				   <span>${name}</span>
				   <i class="layui-icon layui-icon-close" onclick="delTime(${id}, this)"></i>
				</li>
			`;
	}
	$('.time-label-list ul').html(html);

}

function backSeckillGoodsList() {
	location.hash = ns.hash("seckill://shop/seckill/goodslist");
}