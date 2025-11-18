var laytpl, stepTab, element, form, upload, laydate, repeat_flag = false;//防重复标识
var tab = ["basic", "price-stock", "detail", "attr", "senior"];
var specSearchableSelectArr = [];//规格项下拉搜索集合
var specValueSearchableSelectArr = [];//规格值下拉搜索集合
var goodsSpecFormat = [];//商品规格格式
var goodsSkuData = [];//商品sku列表
var GOODS_SPEC_MAX = 4;//规格项数量
var goodsContent;//商品详情
var goodsAttrFormat = [];//商品参数json
var goodsImage = [];//商品主图
var GOODS_IMAGE_MAX = 10;//商品主图数量
var GOODS_SKU_MAX = 10;//商品SKU数量
var attribute_img_type = 0;//规格项是否保存图片
var skuSort = [];
var multiCategorySelect;

var batchSetSkuImage = [];

// 可重写
var requestAdd = ''; // 添加商品请求地址
var requestEdit = ''; // 编辑商品请求地址
var goodsTag = '商品'; // 关键词，商品、项目、卡项
var appendRefreshGoodsSkuData = null; // 追加刷新商品sku数据
var appendSkuTableData = null; // 追加刷新规格表格
var appendSingleGoodsData = null; // 追加单规格数据
var appendSaveData = null; // 追加保存数据
var initEditDataCallBack = null; // 编辑初始化数据回调

//正则表达式
var regExp = {
	required: /[\S]+/,
	number: /^\d{0,10}$/,
	digit: /^\d{0,10}(.?\d{0,2})$/,
	special: /^\d{0,10}(.?\d{0,3})$/
};

// 验证规则
var verifyData = {
	market_price: regExp.digit, // 划线价
	price: regExp.digit, // 销售价
	cost_price: regExp.digit, // 成本价
	weight: regExp.digit, // 重量
	volume: regExp.digit, // 体积
	stock: regExp.number, // 库存
	stock_alarm: regExp.number, // 库存预警
	goods_stock: regExp.number, // 总库存
	sku_stock: regExp.number, // sku库存
	virtual_sale: regExp.number, // 已售出数量
};

//规格属性id对象
var specIdObj = {
	minSpecId:0,
	minSpecValueId:0,
	init:function (){
		goodsSpecFormat.forEach((spec)=>{
			if(spec.spec_id < this.minSpecId){
				this.minSpecId = spec.spec_id;
			}
			spec.value.forEach((spec_value)=>{
				if(spec_value.spec_value_id < this.minSpecValueId){
					this.minSpecValueId = spec_value.spec_value_id;
				}
			})
		})
	},
	getSpecId:function (){
		this.minSpecId --;
		return this.minSpecId;
	},
	getSpecValueId:function (){
		this.minSpecValueId --;
		return this.minSpecValueId;
	}
}

// 监听窗口
$(window).resize(function () {
	var width = $(".layui-tab-content").outerWidth();
	$(".layui-form .layui-tab-title").css('width', (width - 30));
	$(".fixed-btn").css("width", width);
});

$(function () {

	var width = $(".layui-tab-content").outerWidth();
	$(".layui-form .layui-tab-title").css('width', (width - 30));
	$(".fixed-btn").css("width", width);

	goodsContent = UE.getEditor('editor', {autoHeightEnabled: false});

	//分类选择初始化
	let category_data_dom = $("#category_data");
	multiCategorySelect = new MultiCategorySelect({
		data:category_data_dom.length ? JSON.parse(category_data_dom.val()) : [[]],
	})

	layui.use(['element', 'laytpl', 'form', 'laydate'], function () {
		form = layui.form;
		element = layui.element;
		laytpl = layui.laytpl;
		laydate = layui.laydate;
		form.render();
		element.render();

		stepTab = 'basic';

		element.tabChange('goods_tab', stepTab);

		if ($("input[name='goods_image']").val()) {
			goodsImage = $("input[name='goods_image']").val().split(",");
		}
		setTimeout(()=>{
			let goods_image = new MultiImageUpload({
				image_list: goodsImage,
				max_num: GOODS_IMAGE_MAX,
				container: ".js-goods-image",
			})
		}, 400)


		//渲染商品主图列表
		//refreshGoodsImage();

		element.on('tab(goods_tab)', function () {
			stepTab = this.getAttribute('lay-id');
			refreshStepButton();
		});

		var time = new Date();
		var currentTime = time.toLocaleDateString + " " + time.getHours() + ":" + time.getMinutes() + ":" + time.getSeconds();

		//定时上架时间
		laydate.render({
			elem: '#timer_on', //指定元素
			type: 'datetime',
			min: currentTime
		});

		//定时下架时间
		laydate.render({
			elem: '#timer_off', //指定元素
			type: 'datetime',
			min: currentTime
		});

		//是否限购
		form.on('radio(is_limit)', function (data) {
			value = parseInt(data.value);
			if (value == 1) {

				$('.limit_type').remove();
				$('#max_buy').attr("style", "display:block;");
				var html = '<div class="layui-form-item limit_type">' +
					'<label class="layui-form-label">限购：</label>' +
					'<div class="layui-input-block">' +
					'<input type="radio" name="limit_type" value="1" title="单次限购" lay-filter="limit_type" checked>' +
					'<input type="radio" name="limit_type" value="2" title="长期限购" lay-filter="limit_type">' +
					'<input type="number" name="max_buy" placeholder="" lay-verify="max_buy" class="layui-input len-short" autoComplete="off">' +
					'<div class="layui-form-mid">' + '&nbsp件' + '</div>' +
					'</div>' +
					'<div class="word-aux">单次限购是针对于每次下单不能超过限购数量，长期限购是针对于会员账号购买这个商品的总数不能超过限购数量。</div>' +
					'</div>';
				$('.is_limit').after(html);
				//定时上架时间
				// laydate.render({
				// 	elem: '#timer_on', //指定元素
				// 	type: 'datetime',
				// 	min: currentTime
				// });
				form.render();
			} else {
				$('#max_buy').attr("style", "display:none;");
				$('.limit_type').remove();
			}
		});

		//是否上架
		form.on('radio(goods_state)', function (data) {
			value = parseInt(data.value);
			if (value == 0) {
				$('.timer_on').remove();
				$('.timer_on_time').remove();
				var html = '<div class="layui-form-item timer_on">' +
					'<label class="layui-form-label">定时上架：</label>' +
					'<div class="layui-input-block">' +
					'<input type="radio" name="timer_on_status" value="1" title="启用" lay-filter="timer_on" checked>' +
					'<input type="radio" name="timer_on_status" value="2" title="不启用" lay-filter="timer_on">' +
					'</div>' +
					'<div class="word-aux">启用定时上架后，到达设定时间，此商品将自动上架。</div>' +
					'</div>' +
					'<div class="layui-form-item timer_on_time">' +
					'<label class="layui-form-label"></label>' +
					'<div class="layui-input-inline">' +
					'<input type="text" id="timer_on" name="timer_on" lay-verify="required" class="layui-input len-mid" autocomplete="off" readonly>' +
					'<i class=" iconrili iconfont calendar"></i>' +
					'</div>' +
					'</div>';
				$('.goods_state').after(html);
				//定时上架时间
				laydate.render({
					elem: '#timer_on', //指定元素
					type: 'datetime',
					min: currentTime
				});
				form.render();
			} else {
				$('.timer_on').remove();
				$('.timer_on_time').remove();
			}
		});

		//定时上架
		form.on('radio(timer_on)', function (data) {
			value = parseInt(data.value);
			if (value == 1) {
				$('.timer_on_time').removeClass('layui-hide');
				$("input[name='timer_on']").attr("lay-verify", "required");
			} else {
				$("input[name='timer_on']").attr("lay-verify", "");
				$("input[name='timer_on']").val('');
				$('.timer_on_time').addClass('layui-hide');
			}
		});

		//定时下架
		form.on('radio(timer_off)', function (data) {
			value = parseInt(data.value);
			if (value == 1) {
				$('.timer_off').show();
				$("input[name='timer_off']").attr("lay-verify", "required");
			} else {

				$("input[name='timer_off']").attr("lay-verify", "");
				$("input[name='timer_off']").val('');
				$('.timer_off').hide();
			}
		});

		//编辑商品
		initEditData();
		isNullTable();

		//选择商品分类点击事件
		$("body").off("click", ".category-list .item li").on("click", ".category-list .item li", function () {
			var category_id = $(this).attr("data-category-id");
			var level = parseInt($(this).attr("data-level").toString());

			$(this).addClass('selected').siblings("").removeClass("selected");

			if (level < 3) {
				//查询二级商品分类
				getCategoryList(category_id, level, function () {
					refreshCategory();
				});
			} else {
				refreshCategory();
			}

		});

		//启用多规格
		form.on("switch(spec_type)", function (data) {
			var status = data.elem.checked ? 1 : 0;
			if (status) {
				$(".js-more-spec").show();
				$(".js-single-spec").hide();
				$(".js-goods-stock-wrap").hide();
				$("input[name='goods_stock']").attr("disabled", true).val("");
				$("input[name='goods_stock_alarm']").attr("disabled", true).val("");
			} else {
				$(".js-single-spec").show();
				$(".js-more-spec").hide();
				$(".js-goods-stock-wrap").show();
				$("input[name='goods_stock']").removeAttr("disabled");
				$("input[name='goods_stock_alarm']").removeAttr("disabled").val("");
			}
		});

		//添加规格项
		$(".js-add-spec button").click(function () {
			addSpec();
		});

		//是否添加规格图片，复选框
		form.on("checkbox(add_spec_img)", function (data) {
			var div = data.othis[0];
			if ($(div).attr("class") == "layui-unselect layui-form-checkbox layui-form-checked") {
				attribute_img_type = 1;
			} else {
				attribute_img_type = 0;
			}
			refreshSpec(false, true);
		});

		// 批量规格操作
		$(".js-more-spec .batch-operation-sku span").click(function () {
			refreshBatchOperate()
			var field = $(this).attr("data-field");
			var verify = $(this).attr("data-verify") || "";
			var placeholder = $(this).text();
			$("input[name='batch_operation_sku']").attr("data-field", field).attr("placeholder", placeholder).attr("data-verify", verify).val("");

			$(".batch-operation-sku span").hide();
			$(".batch-operation-sku input, .batch-operation-sku button").show();

			if (field === "sku_images"){
				batchSetSkuImage = [];
				let batch_set_sku_image = new MultiImageUpload({
					image_list: batchSetSkuImage,
					max_num: GOODS_SKU_MAX,
					container: "#batch_set_sku_image",
				})
				$(".batch-operation-sku input[name=batch_operation_sku]").hide();
				$("#batch_set_sku_image").show();
			}else{
				$(".batch-operation-sku input[name=batch_operation_sku]").show().focus();
				$("#batch_set_sku_image").hide();
			}
		});

		//批量操作sku输入框
		$(".js-more-spec .batch-operation-sku input").keyup(function (event) {
			if (event.keyCode == 13) $(this).next().click();
		});

		//批量操作确定按钮
		$(".js-more-spec .batch-operation-sku .confirm").click(function () {
			var input = $("input[name='batch_operation_sku']");
			var field = input.attr("data-field");
			var verify = input.attr("data-verify");
			var placeholder = input.attr("placeholder");
			var value = input.val();

			if(field == 'sku_images'){
				value = batchSetSkuImage.join(",");
				if (value.length == 0) {
					value = ''
				}
			}else{
				if (value.length == 0) {
					layer.msg("请输入" + placeholder);
					$(this).focus();
					return;
				}
			}

			if (verify) {
				var reg = verifyData[verify];
				if (!reg.test(value)) {
					layer.msg('[' + placeholder + ']格式输入错误');
					$(this).focus();
					return;
				}
			}


			//统计库存数量
			var stock = 0;
			var stock_alarm = 0;

			//执行批量更新
			var selectedValues = {};
			let sel_spec_res = true
			$('.select_spec_value select').each(function() {
				var selectId = $(this).attr('id'); // 获取 select 的 ID
				selectedValues[selectId] = $(this).val(); // 存储值
				if(sel_spec_res && selectedValues[selectId] == ''){
					let spec_name = $(this).attr('data-spec-name');
					layer.msg('请选择'+spec_name);
					sel_spec_res = false;
					return;
				}
			});
			console.log(selectedValues)
			if(!sel_spec_res){
				return;
			}

			for (var j = 0; j < goodsSkuData.length;j++) {
				let temp_res = true;
				for (let i in selectedValues) {
					if(selectedValues[i] !== 'all'){
						let res = $('.sku-table .layui-input-block table tbody tr:eq(' + j + ')').attr('spec_value_id:'+selectedValues[i])
						if(typeof res === 'undefined'){
							temp_res = false
						}
					}
				}
				if(temp_res){
					if(field == "sku_images"){
						goodsSkuData[j]["sku_images_arr"] = JSON.parse(JSON.stringify(batchSetSkuImage));
						goodsSkuData[j]["sku_image"] = JSON.parse(JSON.stringify(batchSetSkuImage)).shift();
					}
					goodsSkuData[j][field] = value;
					if (field == "stock") stock += parseFloat(value);
					if (field == "stock_alarm") stock_alarm += parseFloat(stock_alarm);
				}
			}

			if (field == "stock") {
				$("input[name='goods_stock']").val(stock);
			}

			if (field == "stock_alarm") {
				$("input[name='goods_stock_alarm']").val(stock_alarm);
			}



			refreshSkuTable();
			$(this).next().click();
		});

		//批量操作取消按钮
		$(".js-more-spec .batch-operation-sku .cancel").click(function () {
			$(".batch-operation-sku input, .batch-operation-sku button , .select_spec_value,#batch_set_sku_image").hide();
			$(".batch-operation-sku span").show();
		});

		//添加商品主图
		// $("body").off("click",".js-add-goods-image").on("click", ".js-add-goods-image", function(){
		//
		// 	openAlbum(function (data) {
		// 		for (var i = 0; i < data.length; i++) {
		// 			if (goodsImage.length < GOODS_IMAGE_MAX) goodsImage.push(data[i].pic_path);
		// 		}
		// 		refreshGoodsImage();
		// 	}, GOODS_IMAGE_MAX, 1);
		// });

		//添加商品视频
		$("body").off("click",".js-add-goods-video").on("click", ".js-add-goods-video", function () {
			openAlbum(function (data) {
				if (data.length > 0) {
					$("input[name='video_url']").val(data[0]['pic_path']);
					loadVideo();
				}
			}, 1, 0, 'video');
		});

		//替换商品主图
		// $("body").off("click",".replace_img").on("click", ".replace_img", function () {
		// 	var index = $(this).data('index');
		// 	openAlbum(function (data) {
		// 		for (var i = 0; i < data.length; i++) {
		// 			goodsImage[index] = data[i].pic_path
		// 		}
		// 		refreshGoodsImage();
		// 	}, 1, 1);
		// });

		// 商品类型选择查询属性
		form.on("select(goods_attr_class)", function (data) {
			var is_exsit = isHasAttr(data.value);

			if (is_exsit) delAttrTemplate(data.value);

			if (data.value) {
				$.ajax({
					url: ns.url("shop/goods/getAttributeList"),
					data: {attr_class_id: data.value},
					dataType: 'JSON',
					type: 'POST',
					success: function (res) {
						var list = res.data;
						var attr_template = $("#attrTemplate").html();
						if (goodsAttrFormat.length > 0) {
							for (var i = 0; i < list.length; i++) {
								if (list[i].attr_type == 1 || list[i].attr_type == 2) {
									for (var j = 0; j < list[i].attr_value_format.length; j++) {
										for (var k = 0; k < goodsAttrFormat.length; k++) {
											// 单选、多选
											if (list[i].attr_value_format[j].attr_value_id == goodsAttrFormat[k].attr_value_id) {
												list[i].attr_value_format[j].checked = true;
												list[i].sort = goodsAttrFormat[k].sort;
											}
										}
									}
								} else if (list[i].attr_type == 3) {
									for (var k = 0; k < goodsAttrFormat.length; k++) {
										if (list[i].attr_id == goodsAttrFormat[k].attr_id) {
											list[i].attr_value_format = goodsAttrFormat[k].attr_value_name;
											list[i].sort = goodsAttrFormat[k].sort;
										}
									}
								}
							}
						}
						var data = {
							list: list
						};
						laytpl(attr_template).render(data, function (html) {
							$(".attr-new tr[data-attr-class-id][data-attr-class-id!='" + data.value + "']").remove();
							$(".attr-new").append(html);
							form.render();
							isNullTable();
						});

					}
				});

				if (data.value) $("input[name='goods_attr_name']").val($(data.elem).find("option:selected").text());
			} else {
				goodsAttrFormat = [];
				$(".attr-new .goods-attr-temp").each(function () {
					$(this).remove();
				});
				isNullTable();
				$("input[name='goods_attr_format']").val("");
			}
		});

		var upload = new Upload({
			elem: '#videoUpload',
			url: ns.url("shop/upload/video"),
			accept: "video",
			callback: function (res) {
				if (res.code >= 0) {
					$("input[name='video_url']").val(res.data.path);
					loadVideo();
				}
			}
		});

		//视频地址输入加载
		$("input[name='video_url']").blur(function () {
			loadVideo();
		});

		// 商品编码验证
		$("body").off("blur", 'input[name="sku_no"]').on("blur", 'input[name="sku_no"]', function () {
			var sku_no = $(this).val();
			if (sku_no.length === 0) return;
			verifySkuNo(sku_no);
		});

		//上一步
		$("button.js-prev").on('click', function (){
			var prev = tab[tab.indexOf(stepTab) - 1];
			if (prev) element.tabChange('goods_tab', prev);
			refreshStepButton();
		})

		//下一步
		form.on('submit(next)', function (data) {
			var next = tab[tab.indexOf(stepTab) + 1];

			if (next == 'detail') {

				if (goodsImage.length == 0) {
					layer.msg("请上传" + goodsTag + "主图");
					element.tabChange('goods_tab', "basic");
					return false;
				}

				if ($("input[name='add_spec_img']").is(":checked")) {
					for (var i = 0; i < goodsSpecFormat[0].value.length; i++) {
						if (goodsSpecFormat[0].value[i].image == '') {
							layer.msg("请上传规格图片");
							element.tabChange('goods_tab', "basic");
							return false;
						}
					}
					// for (var i = 0; i < goodsSkuData.length; i++) {
					// 	for (var j = 0; j < goodsSkuData[i].sku_spec_format.length; j++) {
					// 		if (goodsSkuData[i].sku_spec_format[j].image == "") {
					// 			layer.msg("请上传规格图片");
					// 			element.tabChange('goods_tab', "basic");
					// 			return false;
					// 		}
					// 	}
					// }
				}

				// if ($("input[name='spec_type']").is(":checked")) {
				// 	for (var i = 0; i < goodsSkuData.length; i++) {
				// 		if (goodsSkuData[i].sku_image == "") {
				// 			layer.msg("请上传SKU商品图片");
				// 			element.tabChange('goods_tab', "basic");
				// 			return false;
				// 		}
				// 	}
				// }
			}

			if (next) element.tabChange('goods_tab', next);
			refreshStepButton();
			return false;

		});

		form.on('radio(sale_store)', function (data) {
			if (data.value != 'all') {
				$('.sale-store-select').show();
			} else {
				$('.sale-store-select').hide();
			}
		});

		form.on('radio(service_mode)', function (data) {
			if (data.value == 'onsite') {
				$('.onsite-price').show();
			} else {
				$('.onsite-price').hide();
			}
		});

		// 选择门店
		$('.select-store').click(function () {
			var storeId = [];
			$('.sale-store tr').each(function () {
				storeId.push($(this).attr('data-store'));
			});
			storeSelect(function (store) {
				fetchStore(store);
			}, {store_id: storeId.toString()})
		});

		// 删除门店
		$('body').off('click', '.sale-store .del').on('click', '.sale-store .del', function () {
			$(this).parents('tr').remove();
		});

		/**
		 * 渲染门店
		 * @param store
		 */
		function fetchStore(store) {
			var h = '';
			store.forEach(function (item) {
				h += `<tr data-store="` + item.store_id + `">
					<td>` + item.store_name + `</td>
					<td>` + item.full_address + item.address + `</td>
					<td><a href="javascript:;" class="del">删除</a></td>
				</tr>`;
			});
			$('.sale-store').html(h);
		}

		form.verify({
			//商品名称
			goods_name: function (value) {
				if (value.length == 0) {
					element.tabChange('goods_tab', "basic");
					return "请输入" + goodsTag + "名称";
				}
				if (value.length > 60) {
					element.tabChange('goods_tab', "basic");
					return goodsTag + "名称不能超过60个字符";
				}
			},
			//促销语
			introduction: function (value) {
				if (value.length > 100) {
					element.tabChange('goods_tab', "basic");
					return '促销语不能超过100个字符';
				}
			},
			//销售价
			price: function (value) {
				if (!$("input[name='spec_type']").is(":checked")) {
					if (value.length == 0) {
						element.tabChange('goods_tab', "price-stock");
						return "请输入销售价";
					}

					if (isNaN(value) || !regExp.digit.test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '[销售价]格式输入错误';
					}

				}
			},
			//划线价
			market_price: function (value) {
				if (!$("input[name='spec_type']").is(":checked")) {
					if (value.length > 0) {
						if (isNaN(value) || !regExp.digit.test(value)) {
							element.tabChange('goods_tab', "price-stock");
							return '[划线价]格式输入错误';
						}
					}
				}
			},
			//成本价
			cost_price: function (value) {
				if (!$("input[name='spec_type']").is(":checked")) {
					if (value.length > 0) {
						if (isNaN(value) || !regExp.digit.test(value)) {
							element.tabChange('goods_tab', "price-stock");
							return '[成本价]格式输入错误';
						}
					}
				}
			},
			// 总库存
			goods_stock: function (value) {
				if ($('input[name="spec_type"]').is(":checked") === false) {
					if (value.length == 0) {
						element.tabChange('goods_tab', "price-stock");
						return "请输入库存";
					}
					if (isNaN(value) || !verifyData.goods_stock.test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '[库存]格式输入错误';
					}
				}
			},
			// 库存预警
			goods_stock_alarm: function (value) {
				if (value.length > 0) {
					if (isNaN(value) || !regExp.number.test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '[库存预警]格式输入错误';
					}
					if (parseInt(value) < 0) {
						element.tabChange('goods_tab', "price-stock");
						return '[库存预警]不能小于0';
					}
				}
			},
			//sku销售价
			sku_price: function (value) {
				if (value.length == 0) {
					element.tabChange('goods_tab', "price-stock");
					return "请输入销售价";
				}
				if (isNaN(value) || !regExp.digit.test(value)) {
					element.tabChange('goods_tab', "price-stock");
					return '[销售价]格式输入错误';
				}
			},
			//sku划线价
			sku_market_price: function (value) {
				if (value.length > 0) {
					if (isNaN(value) || !regExp.digit.test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '[划线价]格式输入错误';
					}
				}
			},
			//sku成本价
			sku_cost_price: function (value) {
				if (value.length > 0) {
					if (isNaN(value) || !regExp.digit.test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '[成本价]格式输入错误';
					}
				}
			},
			//sku库存
			sku_stock: function (value) {
				if (value.length == 0) {
					element.tabChange('goods_tab', "price-stock");
					return "请输入库存";
				}
				if (isNaN(value) || !verifyData.sku_stock.test(value)) {
					element.tabChange('goods_tab', "price-stock");
					return '[库存]格式输入错误';
				}
			},
			//sku库存预警
			sku_stock_alarm: function (value, obj) {
				if (value.length > 0) {
					if (isNaN(value) || !regExp.digit.test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '[库存预警]格式输入错误';
					}
					if (parseInt(value) < 0) {
						element.tabChange('goods_tab', "price-stock");
						return '[库存预警]不能小于0';
					}
				}
			},
			// 开启多规格后，必须编辑规格信息
			spec_type: function (value) {
				if ($("input[name='spec_type']").is(":checked")) {
					if (goodsSkuData.length == 0) {
						element.tabChange('goods_tab', "price-stock");
						return '请编辑规格信息';
					} else {
						var flag = false;
						for (var i = 0; i < goodsSkuData.length; i++) {
							if (goodsSkuData[i].sku_spec_format.length != $(".spec-edit-list .spec-item").length) {
								flag = true;
								break;
							}
						}
						if (flag) {
							element.tabChange('goods_tab', "price-stock");
							return '请编辑规格信息';
						}
					}
				}
			},
			// 已售出数量
			virtual_sale: function (value) {
				if (value.length > 0) {
					if (isNaN(value) || !verifyData.virtual_sale.test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '[已售出数量]格式输入错误';
					}
					if (value < 0) {
						element.tabChange('goods_tab', "price-stock");
						return '已售出数量不能小于0';
					}
				}
			},
			// 限购
			max_buy: function (value) {
				var is_limit = $('[name="is_limit"]:checked').val();
				if (is_limit) {
					if (isNaN(value) || !regExp.number.test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '[限购]格式输入错误';
					}
					if (value < 1) {
						element.tabChange('goods_tab', "price-stock");
						return '限购数量不能小于1';
					}
				}
			},
			// 起购数
			min_buy: function (value) {
				if (value.length > 0) {
					if (isNaN(value) || !regExp.number.test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '[起售]格式输入错误';
					}
					if (value < 0) {
						element.tabChange('goods_tab', "price-stock");
						return '起售数量不能小于0';
					}
					var is_limit = $('[name="is_limit"]:checked').val();
					if (is_limit && parseInt(value) > parseInt($('[name="max_buy"]').val()) && $('[name="max_buy"]').val() > 0) {
						element.tabChange('goods_tab', "price-stock");
						return '起售数量不能大于限购数量';
					}
				}
			},
			sale_store: function () {
				if ($('[name="sale_store"]:checked').val() != 'all' && !$('.sale-store tr').length) return '请选择适用的门店';
			},
			category_id: function (){
				let message = multiCategorySelect.verify();
				if(message){
					element.tabChange('goods_tab', "basic");
					return message;
				}
			}
		});

		form.on('submit(save)', function (data) {

			if (goodsImage.length == 0) {
				layer.msg("请上传" + goodsTag + "主图");
				element.tabChange('goods_tab', "basic");
				return false;
			}

			data.field.goods_image = goodsImage.toString();//商品主图

			// 商品分类
			data.field.category_id = multiCategorySelect.getData();
			if (data.field.category_id.length == 0) {
				layer.msg("请选择商品分类");
				element.tabChange('goods_tab', "basic");
				return false;
			}

			if ($("input[name='goods_service_ids']:checked").length) {
				data.field.goods_service_ids = [];
				$("input[name='goods_service_ids']:checked").each(function () {
					data.field.goods_service_ids.push($(this).val());
				});
				data.field.goods_service_ids = data.field.goods_service_ids.toString();
			}

			if ($("input[name='add_spec_img']").is(":checked")) {
				for (var i = 0; i < goodsSpecFormat[0].value.length; i++) {
					if (goodsSpecFormat[0].value[i].image == '') {
						layer.msg("请上传规格图片");
						element.tabChange('goods_tab', "basic");
						return false;
					}
				}
				// for (var i = 0; i < goodsSkuData.length; i++) {
				// 	for (var j = 0; j < goodsSkuData[i].sku_spec_format.length; j++) {
				// 		if (goodsSkuData[i].sku_spec_format[j].image == "") {
				// 			layer.msg("请上传规格图片");
				// 			element.tabChange('goods_tab', "basic");
				// 			return false;
				// 		}
				// 	}
				// }
			}

			// if ($("input[name='spec_type']").is(":checked")) {
			// 	for (var i = 0; i < goodsSkuData.length; i++) {
			// 		if (goodsSkuData[i].sku_image == "") {
			// 			layer.msg("请上传SKU商品图片");
			// 			element.tabChange('goods_tab', "basic");
			// 			return false;
			// 		}
			// 	}
			// }

			//商品sku列表
			var spec_type = 0;
			if ($("input[name='spec_type']").is(":checked")) {
				spec_type = 1;
			}

			if (spec_type == 0) {
				// 单规格
				var sku_data = {
					sku_id: (data.field.goods_id ? $("input[name='edit_sku_id']").val() : 0),
					sku_name: data.field.goods_name,
					spec_name: '',
					sku_no: data.field.sku_no,
					sku_spec_format: '',
					price: data.field.price,
					market_price: data.field.market_price,
					cost_price: data.field.cost_price,
					stock: data.field.goods_stock,
					stock_alarm: data.field.goods_stock_alarm,
					sku_image: goodsImage[0],
					sku_images: data.field.goods_image
				};

				if (appendSingleGoodsData) Object.assign(sku_data, appendSingleGoodsData(data));
				sku_data = JSON.stringify([sku_data]);
				data.field.goods_sku_data = sku_data;
				data.field.goods_spec_format = '';//商品规格格式
			} else {
				//多规格
				data.field.goods_sku_data = JSON.stringify(goodsSkuData);

				if (goodsSpecFormat.length) data.field.goods_spec_format = JSON.stringify(goodsSpecFormat);//商品规格格式
			}

			var spec_type_status = $('#spec_type_status').val();
			if (spec_type_status == spec_type) {
				data.field.spec_type_status = 0;
			} else {
				data.field.spec_type_status = 1;
			}

			var goods_content = goodsContent.getContent();

			if (goods_content == "") {
				layer.msg("请填写" + goodsTag + "详情");
				element.tabChange('goods_tab', "detail");
				return false;
			} else if (goods_content.length < 5 || goods_content.length > 50000) {
				$(".goods-nav ul li:eq(3)").click();
				layer.msg(goodsTag + "描述字符数应在5～50000之间");
				element.tabChange('goods_tab', "detail");
				return false;
			}

			data.field.goods_content = goods_content;//商品详情

			//刷新商品参数格式json
			refreshGoodsAttrData();

			// 属性模板
			$(".attr-new .goods-attr-temp").each(function () {
				var attr_class_id = $(this).attr("data-attr-class-id");
				var attr_id = $(this).attr("data-attr-id");
				var sort = $(this).find(".attr-sort").val();

				$.each(goodsAttrFormat, function (index, item) {
					if (item.attr_class_id == attr_class_id && item.attr_id == attr_id) {
						item.sort = sort;
					}
				})
			});

			// 自定义属性
			$(".attr-new .goods-new-attr-tr").each(function (i) {
				var attr_name = $(this).find(".add-attr-name").val();
				var attr_value = $(this).find(".add-attr-value").val();
				var sort = $(this).find(".add-attr-sort").val();
				var attr = {};
				if (attr_name != "" && attr_value != "") {
					attr.attr_class_id = -(i + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds()));
					attr.attr_id = attr.attr_class_id + -(i + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds()));
					attr.attr_name = attr_name;
					attr.attr_value_id = attr.attr_id + -(i + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds()));
					attr.attr_value_name = attr_value;
					attr.sort = sort;
					goodsAttrFormat.push(attr);
				}
			});

			data.field.goods_attr_format = JSON.stringify(goodsAttrFormat);//商品参数格式

			if (data.field.sale_store == '') {
				var storeId = [];
				$('.sale-store tr').each(function () {
					storeId.push($(this).attr('data-store'));
				});
				data.field.sale_store = ',' + storeId.toString() + ',';
			}

			if (appendSaveData) Object.assign(data.field, appendSaveData(data));

			var url = ns.url(requestAdd);
			if (data.field.goods_id) url = ns.url(requestEdit);

			// data.field.attribute_img_type = attribute_img_type;

			if (repeat_flag) return false;
			repeat_flag = true;

			$.ajax({
				url: url,
				data: data.field,
				dataType: 'JSON',
				type: 'POST',
				success: function (data) {
					layer.msg(data.message);
					if (data.code == 0) {
						location.hash = ns.hash("shop/goods/lists");
					} else {
						repeat_flag = false;
					}
				}
			});
		});

	});

});

//商品分类弹出框
var isOpenSelectedCategoryPopup = false;//防止重复弹出商品分类框
function selectedCategoryPopup(obj) {
	$(".goods-category-list").show();
	var parent = $(obj).parents(".layui-block");
	var i = $(obj).parents(".layui-block").index();

	if (isOpenSelectedCategoryPopup) return;
	var selected_category = $("#selectedCategory").html();
	var data = {
		category_id: $(parent).find(".category_id").val(),
		category_id_1: $(parent).find(".category_id_1").val(),
		category_id_2: $(parent).find(".category_id_2").val(),
		category_id_3: $(parent).find(".category_id_3").val()
	};

	$('body').on('click',function(e){
		var flag = true;
		$(parent).parents(".goods-cate").find(".category_name").each(function() {
			var con = $(this);
			if(con.is(e.target) || con.has(e.target).length != 0 || $(".goods-category-list").has(e.target).length != 0) {//设置目标区域外
				flag = false;
			}
		});

		if (flag) {
			if($(".goods-category-list").is(":visible")){
				$(".goods-category-list").hide();
			}
		}
	});

	laytpl(selected_category).render(data, function (html) {
		var layerIndex = layer.open({
			title: '选择商品分类',
			skin: 'layer-tips-class',
			type: 1,
			area: ['810px', '500px'],
			content: html,
			btn: ['保存', '关闭'],
			yes: function () {
				refreshCategory(obj, true);
				var li_level_1 = 0, li_level_2 = 0, li_level_3 = 0, id_1 = "", id_2 = "", id_3 = "",
					len_level_1 = $(".category-list .item li[data-level=1]").length;
				len_level_2 = $(".category-list .item li[data-level=2]").length;
				len_level_3 = $(".category-list .item li[data-level=3]").length;

				$(".category-list .item li[data-level=1]").each(function () {
					if ($(this).hasClass("selected")) {
						li_level_1 = 1;
						id_1 = $(this).attr("data-category-id");
					}
				});

				$(".category-list .item li[data-level=2]").each(function () {
					if ($(this).hasClass("selected")) {
						li_level_2 = 1;
						id_2 = $(this).attr("data-category-id");
					}
				});

				$(".category-list .item li[data-level=3]").each(function () {
					if ($(this).hasClass("selected")) {
						li_level_3 = 1;
						id_3 = $(this).attr("data-category-id");
					}
				});

				if (len_level_1 == 0) {
					layer.msg("暂无商品分类，请先添加商品分类");
					return;
				} else if (li_level_1 == 0 && len_level_1 != 0) {
					layer.msg("请选择商品分类");
					return;
				} else if (li_level_2 == 0 && len_level_2 != 0) {
					// layer.msg("请选择二级分类");
					// return;
				} else if (li_level_3 == 0 && len_level_3 != 0) {
					// layer.msg("请选择三级分类");
					// return;
				}

				var bool = false;
				$(".layui-block").each(function () {
					var cate_id_1 = $(this).find(".category_id_1").val(),
						cate_id_2 = $(this).find(".category_id_2").val(),
						cate_id_3 = $(this).find(".category_id_3").val();
					var j = $(this).index();

					if (cate_id_1 == id_1 && cate_id_2 == id_2 && cate_id_3 == id_3 && i != j) bool = true;
				});

				if (bool) {
					layer.msg("该分类已被选中");
					refreshCategory(obj, bool);
					return;
				} else {
					refreshCategory(obj, false);
				}

				layer.close(layerIndex);
				isOpenSelectedCategoryPopup = false;
			},
			btn2: function () {
				isOpenSelectedCategoryPopup = false;
			},
			cancel: function (index, layero) {
				isOpenSelectedCategoryPopup = false;
			},
			success: function () {
				isOpenSelectedCategoryPopup = true;
				if (data.category_id_1) {

					//查询二级商品分类
					getCategoryList(data.category_id_1, 1, function () {
						if (data.category_id_2) {
							$(".category-list .item li[data-level='2'][data-category-id='" + data.category_id_2 + "']").addClass('selected').siblings("").removeClass("selected");

							//查询三级分类
							getCategoryList(data.category_id_2, 2, function () {
								if (data.category_id_3) {
									$(".category-list .item li[data-level='3'][data-category-id='" + data.category_id_3 + "']").addClass('selected').siblings("").removeClass("selected");
								}
								refreshCategory(obj);
							});

						}
						refreshCategory(obj);
					});
				}

			}
		});
	});
}

// 添加商品分类
function addCategory() {
	if ($(".goods-cate .layui-block").length < 10) {
		var html = `<div class="layui-block">
			<div class="layui-input-inline cate-input-default">
			<input type="text" readonly onfocus="selectedCategoryPopup(this)" lay-verify="required" autocomplete="off" class="layui-input len-mid category_name" />
			<input type="hidden" class="category_id" />
			<input type="hidden" class="category_id_1" />
			<input type="hidden" class="category_id_2" />
			<input type="hidden" class="category_id_3" />
			<button class="layui-btn layui-btn-primary" onclick="selectedCategoryPopup(this)">选择</button>
			</div>
			<a href="javascript:;" class="default text-color input-text" onclick="delCategory(this)">删除</a>
		</div>`;

		$(".goods-cate").append(html);
	}
	refreshAddCategory();
}

// 删除商品分类
function delCategory(obj) {
	$(obj).parents(".layui-block").remove();
	refreshAddCategory();
}

// 刷新添加商品分类按钮是否显示
function refreshAddCategory() {
	if ($(".goods-cate .layui-block").length < 10) {
		$(".js-add-category").show();
	} else {
		$(".js-add-category").hide();
	}
}

/**
 * 获取商品分类列表
 * @param category_id 分类id
 * @param level 层级
 * @param callback 回调
 */
function getCategoryList(category_id, level, callback) {

	level = parseInt(level) + 1;

	$.ajax({
		url: ns.url("shop/goods/getCategoryList"),
		data: {category_id: category_id},
		dataType: 'json',
		type: 'post',
		success: function (res) {
			var data = res.data;
			if (data) {
				var h = '';
				for (var i = 0; i < data.length; i++) {

					h += '<li data-category-id="' + data[i].category_id + '" data-commission-rate="' + data[i].commission_rate + '" data-level="' + data[i].level + '">';
					h += '<span class="category-name">' + data[i].category_name + '</span>';
					h += '<span class="right-arrow"></span>';
					h += '</li>';

				}

				if (level == 2) {
					$(".category-list .item[data-level='3'] ul").html("");
				}
				$(".category-list .item[data-level='" + level + "'] ul").html(h);

				if (callback) callback();

			}
		}
	});
}

//刷新商品分类数据
function refreshCategory(obj, bool) {
	if (bool) {
		return;
	}
	var parent = $(obj).parents(".layui-block");
	var li = $(".category-list .item li.selected");

	if (li.length > 0) {
		$(parent).find(".category_id").val("");
		$(parent).find(".category_id_1").val("");
		$(parent).find(".category_id_2").val("");
		$(parent).find(".category_id_3").val("");

		var selected = [];//已选商品分类
		var selected_id = []; // 已选商品分类id

		li.each(function (i) {
			selected.push($(this).children(".category-name").text());
			var level = $(this).attr("data-level");
			var category_id = $(this).attr("data-category-id");
			$(parent).find(".category_id_" + level).val(category_id);
			selected_id.push(category_id);
		});

		$(parent).find(".category_id").val(selected_id);
		$(".js-selected-category").html(selected.join(`<span class="right-arrow"></span>`));
		$(parent).find(".category_name").val(selected.join("/"));
	}

}

//刷新步骤按钮
function refreshStepButton() {
	var index = tab.indexOf(stepTab) + 1;
	switch (index) {
		case 1:
			$(".js-prev").hide();
			$(".js-next").show();
			break;
		case 2:
		case 3:
		case 4:
			$(".js-prev").show();
			$(".js-next").show();
			break;
		case 5:
			$(".js-prev").show();
			$(".js-next").hide();
			break;
	}
}

//添加规格项
function addSpec() {
	if (goodsSpecFormat.length < GOODS_SPEC_MAX) {
		//var spec_id = -(($(".spec-edit-list .spec-item").length - 1) + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds()));
		var spec_id = specIdObj.getSpecId();
		var spec = {
			spec_id: spec_id,
			spec_name: "",
			value: []
		};

		goodsSpecFormat.push(spec);
		refreshSpec();

		if (goodsSpecFormat.length >= GOODS_SPEC_MAX) $(".js-add-spec").hide();
	} else {
		$(".js-add-spec").hide();
	}

}

/**
 * 刷新规格数据
 * @param isCheckedAddSpecImg 是否选择规格商品
 * @param isRefreshSkuData 是否刷新规格数据，false：刷新，true：不刷新
 */
function refreshSpec(isCheckedAddSpecImg,isRefreshSkuData) {

	var spec_template = $("#specTemplate").html();

	if (isCheckedAddSpecImg){
		attribute_img_type = 1;
	 	$("input[name='add_spec_img']").prop("checked", isCheckedAddSpecImg);
	}else{
		isCheckedAddSpecImg = $("input[name='add_spec_img']").is(":checked");
	}

	var data = {
		list: goodsSpecFormat,
		add_spec_img: isCheckedAddSpecImg,
		hasStock: hasStock()
	};

	laytpl(spec_template).render(data, function (html) {
		$(".spec-edit-list").html(html);
		form.render();

		// 只有添加时可以进行拖拽
		// if ($("input[name='goods_id']").length == 0) {
		// 规格项拖拽
		$('.spec-edit-list .spec-item').arrangeable({
			//拖拽结束后执行回调
			callback: function (e) {
				var indexBefore = $(e).attr("data-index");//拖拽前的原始位置
				var indexAfter = $(e).index();//拖拽后的位置

				var temp = goodsSpecFormat[indexBefore];
				goodsSpecFormat[indexBefore] = goodsSpecFormat[indexAfter];
				goodsSpecFormat[indexAfter] = temp;

				refreshSpec();
			}
		});
		// }

		//删除规格项
		$(".spec-edit-list .spec-item .spec .layui-icon-close").click(function () {
			var index = $(this).attr("data-index");
			goodsSpecFormat.splice(index, 1);
			if (goodsSpecFormat.length <= GOODS_SPEC_MAX) {
				$(".js-add-spec").show();
			}
			refreshSpec();
			var stock = 0;
			//统计库存数量
			$(".sku-table .layui-input-block input[name='stock']").each(function () {
				if ($(this).val()) stock += parseFloat($(this).val().toString());
			});
			$("input[name='goods_stock']").val(stock);

			var stock_alarm = 0;
			//统计库存数量
			$(".sku-table .layui-input-block input[name='stock_alarm']").each(function () {
				if ($(this).val()) stock_alarm += parseFloat($(this).val().toString());
			});
			$("input[name='goods_stock_alarm']").val(stock_alarm);
		});

		//添加规格值
		$(".spec-edit-list .spec-item .spec-value > a").click(function () {
			var index = $(this).attr("data-index");
			$(".spec-edit-list .spec-item .add-spec-value-popup").hide();
			$(".spec-edit-list .spec-item[data-index='" + index + "'] .add-spec-value-popup").show();
			//根据当前规格项查询规格值列表
			setTimeout(function () {
				specValueSearchableSelectArr[index].show();
			}, 1);

		});

		//删除规格值
		$(".spec-edit-list .spec-item .spec-value .layui-icon-close").click(function () {
			var parentIndex = $(this).attr("data-parent-index");
			var index = $(this).attr("data-index");
			goodsSpecFormat[parentIndex].value.splice(index, 1);
			refreshSpec();
			var stock = 0;
			//统计库存数量
			$(".sku-table .layui-input-block input[name='stock']").each(function () {
				if ($(this).val()) stock += parseFloat($(this).val().toString());
			});
			$("input[name='goods_stock']").val(stock);

			var stock_alarm = 0;
			//统计库存数量
			$(".sku-table .layui-input-block input[name='stock_alarm']").each(function () {
				if ($(this).val()) stock_alarm += parseFloat($(this).val().toString());
			});
			$("input[name='goods_stock_alarm']").val(stock_alarm);
		});

		//修改规格值
		$(".spec-edit-list .spec-item .spec-value .spec-txt").blur(function () {
			var spec_value_name = $(this).text().trim();
			var parentIndex = $(this).attr("data-parent-index");
			var index = $(this).attr("data-index");

			//判断是否为空
			if(spec_value_name === ''){
				layer.msg('名称不能为空', {icon: 5, anim: 6});
				refreshSpec(false, true);
				return;
			}

			//检测是否已存在相同名称规格值
			var sameNameCount = 0;
			goodsSpecFormat[parentIndex].value.forEach((item,item_index)=>{
				if(item.spec_value_name === spec_value_name && item_index != index){
					sameNameCount ++;
				}
			})
			if(sameNameCount > 0){
				layer.msg('名称不能重复', {icon: 5, anim: 6});
				refreshSpec(false, true);
				return;
			}

			goodsSpecFormat[parentIndex].value[index]['spec_value_name'] = spec_value_name;
			var specNameChangeData = {};
			for (var i in goodsSkuData){
				var sku_spec_format = goodsSkuData[i]['sku_spec_format'];
				var is_need_change = false;
				var origin_spec_name = getSpecNameBySkuSpecFormat(sku_spec_format);
				for (var j in sku_spec_format){
					 if(sku_spec_format[j]['spec_value_id'] == goodsSpecFormat[parentIndex].value[index]['spec_value_id']){
						 is_need_change = true;
						 sku_spec_format[j]['spec_value_name'] = spec_value_name;
						 break;
					 }
				}
				if(is_need_change){
					goodsSkuData[i]['spec_name'] = getSpecNameBySkuSpecFormat(sku_spec_format);
				}
				//旧的指向新的
				specNameChangeData[origin_spec_name] = goodsSkuData[i]['spec_name'];
			}

			refreshSpec(false, true);
		});

		//取消
		$(".js-cancel-spec-value").click(function () {
			$(this).parent().hide();
		});

		// 只有添加时可以进行拖拽
		// if ($("input[name='goods_id']").length == 0) {
		// 规格值拖拽
		$(".spec-edit-list .spec-item .spec-value ul li").arrangeable({
			//拖拽结束后执行回调
			callback: function (e) {
				var parentIndex = $(e).attr("data-parent-index");//父级下标
				var temp = JSON.parse(JSON.stringify(goodsSpecFormat[parentIndex].value));
				$(".spec-edit-list .spec-item[data-index='" + parentIndex + "'] .spec-value ul li").each(function () {
					var indexBefore = $(this).attr("data-index");//拖拽前的原始位置
					var indexAfter = $(this).index();//拖拽后的位置
					goodsSpecFormat[parentIndex].value[indexAfter] = temp[indexBefore];
				});

				refreshSpec();
			}
		});
		// }

		//规格值上传图片
		$(".spec-edit-list .spec-item .spec-value ul li .img-wrap").click(function () {
			var parentIndex = $(this).parent().attr("data-parent-index");
			var index = $(this).parent().attr("data-index");
			openAlbum(function (data) {
				for (var i = 0; i < data.length; i++) {
					goodsSpecFormat[parentIndex].value[index].image = data[i].pic_path;
				}
				refreshSpec(false,true);
			}, 1, 1);
		});

		if (attribute_img_type == 0) {
			for (var q = 0; q < goodsSpecFormat.length; q++) {
				for (var r = 0; r < goodsSpecFormat[q]["value"].length; r++) {
					goodsSpecFormat[q]["value"][r]["image"] = "";
				}
			}
		}

		//绑定规格项下拉搜索
		bindSpecSearchableSelect();

		//绑定规格值下拉搜索
		bindSpecValueSearchableSelect();

		//刷新SKU列表
		if(!isRefreshSkuData) refreshGoodsSkuData();

		//刷新SKU表格
		refreshSkuTable();

		$(".js-more-spec .batch-operation-sku .cancel").click()
	});
}

//刷新规格表格
function refreshSkuTable() {

	var sku_template = $("#skuTableTemplate").html();
	var length = 0;
	//统计有效规格数量
	for (var i = 0; i < goodsSpecFormat.length; i++) {
		if (goodsSpecFormat[i].spec_name != '' && goodsSpecFormat[i].value.length > 0) {
			length++;
		}
	}

	var colSpan = length == 0 ? 1 : length;
	var rowSpan = colSpan == 1 ? 1 : 2;

	if (goodsSkuData.length) {
		$(".js-more-spec .batch-operation-sku").show();
		$(".sku-table").show();
	} else {
		$(".js-more-spec .batch-operation-sku").hide();
		$(".sku-table").hide();
	}

	var showSpecName = true;
	for (var j = 0; j < goodsSkuData.length; j++) {
		if (goodsSkuData[j].sku_spec_format.length != length) {
			showSpecName = false;
			break;
		}
	}

	var data = {
		specList: goodsSpecFormat,
		skuList: goodsSkuData,
		colSpan: colSpan,
		rowSpan: rowSpan,
		length: length,
		goods_sku_max: GOODS_SKU_MAX,
		showSpecName: showSpecName,
		hasStock: hasStock(),
	};

	console.log(data)
	// 合并数据
	if(appendSkuTableData) Object.assign(data,appendSkuTableData());

	laytpl(sku_template).render(data, function (html) {

		$(".sku-table .layui-input-block").html(html);
		form.render();

		if (showSpecName) {
			var c_n = 1;
			for (var x = length - 1; x >= 0; x--) {
				for (var i = 0; i < goodsSkuData.length;) {

					if (goodsSpecFormat[x]['value'].length > 0) {
						for (ele of goodsSpecFormat[x]['value']) {
							$('.sku-table .layui-input-block table tbody tr:eq(' + i + ')').prepend('<td rowspan="' + c_n + '">' + ele.spec_value_name + '</td>');
							i = i + c_n;
						}
					} else {
						i++;
					}
				}
				c_n = c_n * goodsSpecFormat[x]['value'].length;
			}
		}

		for (var i = 0; i < goodsSkuData.length;i++) {
			//动态添加规格值属性,便于批量修改
			for (ele of goodsSkuData[i]['sku_spec_format']) {
				$('.sku-table .layui-input-block table tbody tr:eq(' + i + ')').attr('spec_value_id:' + ele['spec_value_id'], ele['spec_value_name'])
			}
		}

		//加载图片放大
		loadImgMagnify();

		//绑定SKU列表中输入框键盘事件
		$(".sku-table .layui-input-block input").keyup(function () {
			var index = $(this).attr("data-index");
			var field = $(this).attr("name");
			var value = $(this).val();
			goodsSkuData[index][field] = value;
			//规格特殊处理
			if (field == "stock") {
				var stock = 0;
				//统计库存数量
				$(".sku-table .layui-input-block input[name='stock']").each(function () {
					if ($(this).val()) stock += parseFloat($(this).val().toString());
				});
				$("input[name='goods_stock']").val(stock);
			}

			if (field == "stock_alarm") {
				var stock_alarm = 0;
				//统计库存数量
				$(".sku-table .layui-input-block input[name='stock_alarm']").each(function () {
					if ($(this).val()) stock_alarm += parseFloat($(this).val().toString());
				});
				$("input[name='goods_stock_alarm']").val(stock_alarm);
			}
		}).blur(function () {
			$(this).keyup();
		});

		//切换默认规格
		form.on('switch(is_default)', function(data){
			var index = $(data.elem).data('index');
			goodsSkuData.forEach((item)=>{
				item.is_default = 0;
			})
			goodsSkuData[index]['is_default'] = data.elem.checked ? 1 : 0;
			refreshSkuTable();
		});

		//SKU图片放大预览
		$(".sku-table .layui-input-block .img-wrap .operation .js-preview").click(function () {
			$(this).parent().prev().find("img").click();
		});

		//SKU图片删除
		$(".sku-table .layui-input-block .img-wrap .operation .js-delete").click(function () {
			console.log("SKU图片删除")
			var index = $(this).parent().parent().attr("data-index");
			var parentIndex = $(this).parent().parent().attr("data-parent-index");
			goodsSkuData[parentIndex].sku_images_arr.splice(index, 1);
			if (goodsSkuData[parentIndex].sku_images_arr.length == 0) goodsSkuData[parentIndex].sku_image = "";
			goodsSkuData[parentIndex].sku_images = goodsSkuData[parentIndex].sku_images_arr.toString();
			refreshSkuTable();
		});

		//SKU上传图片
		$(".sku-table .layui-input-block .upload-sku-img").click(function () {
			var index = $(this).attr("data-index");
			openAlbum(function (data) {
				for (var i = 0; i < data.length; i++) {
					if (goodsSkuData[index].sku_images_arr.length < GOODS_SKU_MAX) goodsSkuData[index].sku_images_arr.push(data[i].pic_path)
				}
				goodsSkuData[index].sku_image = goodsSkuData[index].sku_images_arr[0];
				goodsSkuData[index].sku_images = goodsSkuData[index].sku_images_arr.toString();
				refreshSkuTable();
			}, GOODS_SKU_MAX, 1);
		});

		// SKU商品图片拖拽排序
		$('.sku-table .img-wrap').arrangeable({
			//拖拽结束后执行回调
			callback: function (e) {
				var parentIndex = $(e).attr("data-parent-index");//拖拽前的原始位置
				var indexBefore = $(e).attr("data-index");//拖拽前的原始位置
				var indexAfter = $(e).index();//拖拽后的位置
				var temp = goodsSkuData[parentIndex].sku_images_arr[indexBefore];
				goodsSkuData[parentIndex].sku_images_arr[indexBefore] = goodsSkuData[parentIndex].sku_images_arr[indexAfter];
				goodsSkuData[parentIndex].sku_images_arr[indexAfter] = temp;
				goodsSkuData[parentIndex].sku_image = goodsSkuData[parentIndex].sku_images_arr[0];
				goodsSkuData[parentIndex].sku_images = goodsSkuData[parentIndex].sku_images_arr.toString();
			}
		});

		$(".js-more-spec .batch-operation-sku .cancel").click()
	});
}

//刷新商品sku数据
refreshGoodsSkuData = function () {
	var arr = goodsSpecFormat;
	var tempGoodsSkuData = JSON.parse(JSON.stringify(goodsSkuData));// 记录原始数据，后续用作对比
	goodsSkuData = [];
	for (var ele_1 of arr) {
		var item_prop_arr = [];
		if (goodsSkuData.length > 0) {

			for (var ele_2 of goodsSkuData) {

				for (var ele_3 of ele_1['value']) {

					var sku_spec_format = JSON.parse(JSON.stringify(ele_2.sku_spec_format));// 防止对象引用
					sku_spec_format.push(ele_3);
					var item = {
						spec_name: `${ele_2.spec_name} ${ele_3.spec_value_name}`,
						sku_no: "",
						sku_spec_format: sku_spec_format,
						price: "",
						market_price: "",
						cost_price: "",
						stock: "",
						stock_alarm: "",
						sku_image: "",
						sku_images: "",
						sku_images_arr: [],
						is_default: 0,
					};
					if(appendRefreshGoodsSkuData) Object.assign(item,appendRefreshGoodsSkuData);
					item_prop_arr.push(item);
				}
			}
		} else {
			for (var ele_3 of ele_1['value']) {

				var spec_name = ele_3.spec_value_name;
				var item = {
					spec_name: spec_name,
					sku_no: "",
					sku_spec_format: [ele_3],
					price: "",
					market_price: "",
					cost_price: "",
					stock: "",
					stock_alarm: "",
					sku_image: "",
					sku_images: "",
					sku_images_arr: [],
					is_default: 0,
				};
				if(appendRefreshGoodsSkuData) Object.assign(item,appendRefreshGoodsSkuData);
				item_prop_arr.push(item);
			}
		}

		goodsSkuData = item_prop_arr.length > 0 ? item_prop_arr : goodsSkuData;
	}

	// 比对已存在的规格项/值，并且赋值
	for (var i = 0; i < tempGoodsSkuData.length; i++) {
		for (var j = 0; j < goodsSkuData.length; j++) {
			var count = matchSkuSpecCount(tempGoodsSkuData[i].sku_spec_format, goodsSkuData[j].sku_spec_format);
			if (count === goodsSkuData[j].sku_spec_format.length) {
				var spec_name = goodsSkuData[j].spec_name;
				var sku_spec_format = goodsSkuData[j].sku_spec_format;
				Object.assign(goodsSkuData[j], tempGoodsSkuData[i]);
				goodsSkuData[j].spec_name = goodsSkuData[j].spec_name;
				goodsSkuData[j].sku_spec_format = sku_spec_format;
				break;
			}
		}
	}

	for (var k = 0; k < goodsSkuData.length; k++) {
		skuSort.push({"spec_name": goodsSkuData[k].spec_name, "sort": k + 1});
	}


	return goodsSkuData;
};

// 匹配规格值
function matchSkuSpecCount(oVal,nVal) {
	var count = 0;// 匹配次数，与规格值相等时为匹配成功
	for (var i = 0; i < oVal.length; i++) {
		for (var j = 0; j < nVal.length; j++) {
			if (oVal[i].spec_value_name === nVal[j].spec_value_name) {
				count++;
				break;
			}
		}
	}
	return count;
}

//通过属性数据获取属性名称
function getSpecNameBySkuSpecFormat(sku_spec_format){
	var arr = [];
	sku_spec_format.forEach((item) => {
		arr.push(item.spec_value_name);
	})
	return arr.join(' ');
}

//绑定规格项下拉搜索
function bindSpecSearchableSelect() {

	//规格项搜索
	specSearchableSelectArr = [];

	$(".spec-edit-list .spec-item").each(function (i) {
		var _this = this;
		var options = {
			placeholder: "输入规格项，按回车键完成",
			//回车回调
			enterCallback: function (input) {
				var selected = input.next().find(".searchable-select-item.selected");//搜索到到规格
				//var spec_id = -(($(".spec-edit-list .spec-item").length - 1) + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds()));
				var spec_id = specIdObj.getSpecId();
				var spec_name = input.val().trim();
				if (spec_name.length == 0) {
					layer.msg("请输入规格项");
					return;
				}

				var options = '<option value="' + spec_id + '" data-attr-name="' + spec_name + '">' + spec_name + '</option>';
				$(_this).find("select[name='spec_item']").html(options);
				specSearchableSelectArr[i].buildItems();
				goodsSpecFormat[i].spec_id = spec_id;
				goodsSpecFormat[i].spec_name = spec_name;

				//更新规格值
				for (var j = 0; j < goodsSpecFormat[i].value.length; j++) {
					goodsSpecFormat[i].value[j].spec_id = spec_id;
					goodsSpecFormat[i].value[j].spec_name = spec_name;
				}

				refreshSpec();

			},
			//option回调
			optionCallback: function (spec_id, spec_name) {
				goodsSpecFormat[i].spec_id = spec_id;
				goodsSpecFormat[i].spec_name = spec_name;

				//更新规格值
				for (var j = 0; j < goodsSpecFormat[i].value.length; j++) {
					goodsSpecFormat[i].value[j].spec_id = spec_id;
					goodsSpecFormat[i].value[j].spec_name = spec_name;
				}

				refreshSpec();
			}

		};

		specSearchableSelectArr.push($(this).find("select[name='spec_item']").searchableSelect(options));
		$(this).find(".searchable-select-input").attr("data-index", i);

	});

}

//绑定规格值下拉搜索
function bindSpecValueSearchableSelect() {

	//规格值下拉搜索集合
	specValueSearchableSelectArr = [];

	$(".spec-edit-list .spec-item .add-spec-value-popup").each(function (i) {
		var _this = this;
		var index = $(_this).attr("data-index");
		var count = $(_this).parent().find('li').length;

		var options = {
			placeholder: "输入规格值，按回车键完成",
			//回车回调
			enterCallback: function (input) {
				var selected = input.next().find(".searchable-select-item.selected");//搜索到到规格
				//var spec_value_id = -(Math.abs(goodsSpecFormat[index].spec_id) + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds())) + count;
				var spec_value_id = specIdObj.getSpecValueId();
				var spec_value_name = input.val().trim();
				if (spec_value_name.length == 0) {
					layer.msg("请输入规格值");
					return;
				}
				var options = '<option value="' + spec_value_id + '" data-attr-name="' + spec_value_name + '">' + spec_value_name + '</option>';
				$(_this).find("select[name='spec_item']").html(options);
				specValueSearchableSelectArr[index].buildItems();

				var item = {
					"spec_id": goodsSpecFormat[index].spec_id,
					"spec_name": goodsSpecFormat[index].spec_name,
					"spec_value_id": spec_value_id,
					"spec_value_name": spec_value_name,
				};
				if (index == 0) {
					item.image = "";
				}
				for (var s = 0; s < goodsSpecFormat[index].value.length; s++){
					if(spec_value_name == goodsSpecFormat[index].value[s].spec_value_name){
                        layer.msg("规格值不能相同");
                        return;
					}
				}
                goodsSpecFormat[index].value.push(item);
				refreshSpec();

			},
			//option回调
			optionCallback: function (spec_value_id, spec_value_name) {

				var item = {
					"spec_id": goodsSpecFormat[index].spec_id,
					"spec_name": goodsSpecFormat[index].spec_name,
					"spec_value_id": spec_value_id,
					"spec_value_name": spec_value_name,
				};
				if (index == 0) {
					item.image = "";
				}
				goodsSpecFormat[index].value.push(item);
				refreshSpec();
			}

		};

		specValueSearchableSelectArr.push($(this).find("select[name='spec_value_item']").searchableSelect(options));
		$(this).find(".searchable-select-input").attr("data-index", index);
	});
}

//刷新商品参数json
function refreshGoodsAttrData() {
	goodsAttrFormat = [];

	$(".goods-attr-temp").each(function () {
		var attr_class_id = $(this).attr("data-attr-class-id");
		// var attr_class_name = $(this).attr("data-attr-class-name");
		var attr_id = $(this).attr("data-attr-id");
		var attr_name = $(this).attr("data-attr-name");
		var attr_type = parseInt($(this).attr("data-attr-type").toString());// 属性类型（1.单选 2.多选 3. 输入）

		var item = {
			attr_class_id: attr_class_id,
			attr_id: attr_id,
			attr_name: attr_name,
			attr_value_id: "",
			attr_value_name: ""
		};

		switch (attr_type) {
			case 1:
				var input = $(this).find("input:checked");
				if (input.length > 0) {
					item.attr_value_id = input.val();
					item.attr_value_name = input.attr("data-attr-value-name");
					goodsAttrFormat.push(item);
				}
				break;
			case 2:
				$(this).find("input:checked").each(function () {
					item = JSON.parse(JSON.stringify(item));
					item.attr_value_id = $(this).val();
					item.attr_value_name = $(this).attr("data-attr-value-name");
					goodsAttrFormat.push(item);
				});
				break;
			case 3:
				item.attr_value_name = $(this).find("input").val();
				if (item.attr_value_name) {
					goodsAttrFormat.push(item);
				}
				break;
		}
	});

}

function deleteVideoClass(){
	$('#goods_video').removeClass("vjs-error");
}

//删除已选择的视频
function deleteVideo() {
	var src = $("input[name='video_url']").val();
	if (src != "") {
		var video = 'goods_video';
		var myPlayer = videojs(video);
		videojs(video).ready(function () {
			var myPlayer = this;
			myPlayer.pause();
		});

		$("#goods_video_html5_api").attr('src', "");
		$(".vjs-modal-dialog-content").hide();
		$(".vjs-error-display").hide();
		setTimeout("deleteVideoClass()",30 );
		$('#goods_video_html5_api').attr('controls', true);
		$(".vjs-poster").hide();
		
		$("input[name='video_url']").val('');
	}
}

$('body').off('mouseover', '#videoUpload2').on('mouseover', '#videoUpload2', function () {
	$(this).addClass('mask');
	var src = $('#goods_video_html5_api').attr('src');
	if(src) {
		$(".delete-video").removeClass('hide');
		$(".replace-video").removeClass('hide').removeClass('replace-video2');
	} else {
		$(".replace-video").removeClass('hide').addClass('replace-video2');
	}
	
});

$('body').off('mouseout', '#videoUpload2').on('mouseout', '#videoUpload2', function () {
	$(this).removeClass('mask');
	$(".delete-video").addClass('hide');
	$(".replace-video").addClass('hide');
});

//渲染商品主图列表
// function refreshGoodsImage() {
// 	var goods_image_template = $("#goodsImage").html();
// 	var data = {
// 		list: goodsImage,
// 		max: GOODS_IMAGE_MAX
// 	};
//
// 	laytpl(goods_image_template).render(data, function (html) {
//
// 		$(".js-goods-image").html(html);
//
// 		//加载图片放大
// 		loadImgMagnify();
//
// 		if (goodsImage.length) {
//
// 			//预览
// 			$(".js-goods-image .js-preview").click(function () {
// 				$(this).parent().prev().find("img").click();
// 			});
//
// 			//图片删除
// 			$(".js-goods-image .js-delete").click(function () {
// 				var index = $(this).attr("data-index");
// 				goodsImage.splice(index, 1);
// 				refreshGoodsImage();
// 			});
//
// 			// 拖拽
// 			$('.js-goods-image .upload_img_square_item').arrangeable({
// 				//拖拽结束后执行回调
// 				callback: function (e) {
// 					var indexBefore = $(e).attr("data-index");//拖拽前的原始位置
// 					var indexAfter = $(e).index();//拖拽后的位置
// 					var temp = goodsImage[indexBefore];
// 					goodsImage[indexBefore] = goodsImage[indexAfter];
// 					goodsImage[indexAfter] = temp;
// 					refreshGoodsImage();
// 				}
// 			});
// 		}
//
// 		//最多传十张图
// 		if (goodsImage.length < GOODS_IMAGE_MAX) {
// 			$(".js-add-goods-image").show();
// 		} else {
// 			$(".js-add-goods-image").hide();
// 		}
//
// 		// 清空规格的图片
// 		for (var i=0;i<goodsSkuData.length;i++){
// 			if(goodsSkuData[i].sku_images.length == 0) goodsSkuData[i].sku_image = '';
// 		}
//
// 	});
// }

//加载编辑商品的数据
function initEditData() {
	// 商品分类
	$(".layui-block").each(function () {
		var _this = this;
		var ids = $(this).find(".category_id").val().split(",");
		$.each(ids, function (index, item) {
			$(_this).find(".category_id_" + (index + 1)).val(item);
		})
	});

	if ($("input[name='goods_id']").length == 0) return;

	if ($("input[name='spec_type']").is(":checked")) {
		goodsSpecFormat = JSON.parse($("input[name='goods_spec_format']").val().toString());
		specIdObj.init();
		for (var i = 0; i < goodsSpecFormat.length; i++) {
			for (var j = 0; j < goodsSpecFormat[i].value.length; j++) {
				goodsSpecFormat[i].value[j].hasStock = hasStock();
				// goodsSpecFormat[i].value[j].is_delete = 0;
			}
		}

		var isCheckedAddSpecImg = false;// 是否勾选规格图片复选框

		if (goodsSpecFormat.length > 0) {
			for (var i = 0; i < goodsSpecFormat[0].value.length; i++) {
				if (goodsSpecFormat[0].value[i].image) {
					isCheckedAddSpecImg = true;
					break;
				}
			}
		}

		refreshSpec(isCheckedAddSpecImg);

		goodsSkuData = [];
		$(".js-edit-sku-list>div").each(function () {
			var item = {};
			$(this).children('input').each(function () {
				var field = $(this).attr('name').replace('edit_', '');
				var value = $(this).val();
				if (field == 'sku_spec_format') {
					if (value) {
						value = JSON.parse(value)
					} else {
						value = '';
					}
				}
				item[field] = value;
			});
			item.sku_images_arr = item.sku_images ? item.sku_images.split(',') : [];
			for (var s = 0; s < skuSort.length; s++) {
				if (item.spec_name === skuSort[s].spec_name) {
					item.sort = skuSort[s].sort;
				}
			}
			goodsSkuData.push(item);
		});

		goodsSkuData = goodsSkuData.sort(ns.compare('sort'));

		refreshGoodsSkuData();
		refreshSkuTable();
	}

	// 加载商品主图
	//goodsImage = $("input[name='goods_image']").val().split(",");
	//refreshGoodsImage();

	loadVideo(true);

	// 加载商品详情
	goodsContent.ready(function () {
		goodsContent.setContent($("input[name='goods_content']").val());
	});

	// 加载商品参数关联
	var goods_attr_format = $("input[name='goods_attr_format']").val().toString();

	if (goods_attr_format) {

		try {
			goodsAttrFormat = JSON.parse(goods_attr_format);
		} catch (e) {
			console.log(e);
		}

		var new_attr = [];
		$.each(goodsAttrFormat, function (index, item) {
			if (item.attr_class_id < 0) {
				new_attr.push(item);
			}
		});

		var html = "";
		$.each(new_attr, function (index, item) {
			html += '<tr class="goods-attr-tr goods-new-attr-tr">' +
				'<td>' +
				'<input type="text" class="layui-input add-attr-name" value="' + item.attr_name + '" />' +
				'</td>' +
				'<td>' +
				'<input type="text" class="layui-input add-attr-value" value="' + item.attr_value_name + '" />' +
				'</td>' +
				'<td>' +
				'<input type="text" class="layui-input add-attr-sort" value="' + item.sort + '" />' +
				'</td>' +
				'<td>' +
				'<div class="table-btn"><a class="layui-btn" onclick="delAttr(this)">删除</a></div>' +
				'</td>' +
				'</tr>';
		});
		$(".attr-new").append(html);
	} else {
		var html = '<tr class="null-data"><td colspan="3" align="center">无数据</td></tr>';
		$(".attr-new").html(html);
	}

	//刷新商品参数页面
	setTimeout(function () {
		$("select[name='goods_attr_class']").next().find(".layui-anim.layui-anim-upbit .layui-this").click();
	}, 10);

	if (!hasStock()) {
		// 存在库存盘点，禁止操作规格项、库存
		$('input[name="spec_type"]').parent().parent().hide();
		$(".js-add-spec").hide();
		$('input[name="goods_stock"]').prop('disabled', true);
		$('.batch-operation-sku span[data-field="stock"]').hide();
	}

	if (initEditDataCallBack) initEditDataCallBack();
}

// 添加新属性
function addNewAttr() {
	var html = '<tr class="goods-attr-tr goods-new-attr-tr">' +
		'<td>' +
		'<input type="text" class="layui-input add-attr-name" />' +
		'</td>' +
		'<td>' +
		'<input type="text" class="layui-input add-attr-value" />' +
		'</td>' +
		'<td>' +
		'<input type="number" class="layui-input add-attr-sort" value="' + $('.goods-attr-tr').length + '" />' +
		'</td>' +
		'<td>' +
		'<div class="table-btn"><a class="layui-btn" onclick="delAttr(this)">删除</a></div>' +
		'</td>' +
		'</tr>';

	$(".attr-new").append(html);
	isNullTable();
}

// 删除属性
function delAttr(obj) {
	$(obj).parents("tr").remove();
	isNullTable();
}

// 属性表格是否为空
function isNullTable() {
	var len = $(".attr-new .goods-attr-tr").length;
	if (len == 0) {
		$(".attr-new").html('<tr class="null-data"><td colspan="4" align="center">无数据</td></tr>');
	} else {
		$(".attr-new .null-data").remove();
	}
}

// 判断表格中是否包含某个属性模板
function isHasAttr(id) {
	var is_exsit = 0;
	$(".attr-new .goods-attr-tr").each(function () {
		if ($(this).attr("data-attr-class-id") == id) {
			is_exsit = 1;
		}
	});
	return is_exsit;
}

// 删除属性模板
function delAttrTemplate(id) {
	$(".attr-new .goods-attr-tr").each(function () {
		if ($(this).attr("data-attr-class-id") == id) {
			$(this).remove();
		}
	})
}

/**
 * 加载视频
 * @param flag 是否暂停
 */
function loadVideo(flag) {

	var video_url = $("input[name='video_url']").val();
	if (!video_url.length) return;

	var video = "goods_video";
	var myPlayer = videojs(video);
	var value = ns.img(video_url);

	videojs(video).ready(function () {
		var myPlayer = this;
		myPlayer.src(value);
		myPlayer.load(value);
		myPlayer.play();
		if (flag) {
			setTimeout(function () {
				myPlayer.pause();
			}, 10);
		}
		setTimeout(function () {
			if (!$(".video-thumb .vjs-error-display").hasClass("vjs-hidden")) {
				$("input[name='video_url']").val("");//video.js Line:7873
				layer.msg("媒体不能加载，要么是因为服务器或网络失败，要么是因为格式不受支持。");
			} else {
			}
		}, 1000);
	});
}

function refreshFormList(){
	$.ajax({
		url: ns.url("form://shop/form/getformlist"),
		dataType: 'JSON',
		type: 'POST',
		success: function (res) {
			if(res.code >= 0 && res.data.length){
				var h = '<option value="0">请选择' + goodsTag + '表单</option>';
				res.data.forEach(function (item) {
					h += '<option value="'+ item.id +'">'+ item.form_name +'</option>';
				})
				$('[name="form_id"]').html(h);
				form.render();
			}
		}
	});
}

// 验证商品编码是否重复
function verifySkuNo(sku_no) {
	var count = 0;
	$.ajax({
		url: ns.url("shop/goods/verifySkuNo"),
		dataType: 'JSON',
		type: 'POST',
		data:{
			sku_no : sku_no,
			goods_id:$('input[name="goods_id"]').val()
		},
		async: false,
		success: function (res) {
			if (res.code < 0) {
				count = res.data;
				layer.msg(res.message);
			}
		}
	});
	return count;
}

// 是否允许操作库存，true：允许，false：不允许
function hasStock() {
	var value = parseInt($('input[name="has_stock_records"]').val());
	return value === 0;
}

// 多分类选择操作
function MultiCategorySelect(param = {}) {
	let that = this;
	that.data = param.data || [];
	that.layui = null;
	if($("#category_select_box").length > 0){
		that.init(()=>{
			that.bindEvent();
			that.render();
		});
	}
}
MultiCategorySelect.prototype.bindEvent = function (){
	let that = this;
	$("#category_select_box").off('click', '#add_category').on('click', '#add_category', function (){
		that.data.push([]);
		that.render();
	})
	$("#category_select_box").off('click', '.delete-category').on('click', '.delete-category', function (){
		let index = $(this).parent().attr('data-index');
		that.data.splice(index, 1);
		that.render();
	})
}
MultiCategorySelect.prototype.render = function (){
	let that = this;
	that.layui.laytpl(that.getTemplate()).render(that.data, function(html) {
		$("#category_select_box").html(html);
		that.data.forEach((item, index)=>{
			new CategorySelect({
				elem : '#category_select_'+index,
				data : item,
				level : 'end',//可以选择任意等级
				callback: function(category_data){
					category_data.category_names = that.categoryDataToNames(category_data);
					that.data[index] = category_data;
					$('#category_select_'+index).val(category_data.category_names);
				}
			})
		})
	});
}
MultiCategorySelect.prototype.init = function (callback){
	let that = this;
	that.data.forEach((item)=>{
		item.category_names = that.categoryDataToNames(item);
	})
	layui.use('laytpl', function () {
		that.layui = layui;
		typeof callback == 'function' && callback();
	});
}
MultiCategorySelect.prototype.categoryDataToNames = function (category_data){
	let category_names = [];
	category_data.forEach(function (item, index) {
		category_names.push(item.category_name);
	})
	return category_names.join(' / ');
}
MultiCategorySelect.prototype.getTemplate = function (){
	let template = `
		{{# d.forEach(function(item,index){ }}
			<div data-index="{{index}}" style="margin-bottom: 4px;">
				<input type="text" readonly lay-verify="required" autocomplete="off" class="layui-input len-mid" id="category_select_{{index}}" value="{{item.category_names || ''}}"/>
				<a class="text-color delete-category" style="cursor: pointer;" >删除</a>
			</div>
		{{# }) }}
		{{# if(d.length < 10){ }}
			<div><a class="text-color" style="cursor: pointer;" id="add_category">增加分类</a></div>
		{{# } }}`;
	return template;
}
MultiCategorySelect.prototype.verify = function (){
	let that = this;
	let name_arr = [];
	let message = '';
	that.data.forEach((item,index)=>{
		if(name_arr.indexOf(item.category_names) === -1){
			name_arr.push(item.category_names);
		}else{
			$("#category_select_"+index).focus();
			message = '分类不可重复';
			return false;
		}
	})
	return message;
}
MultiCategorySelect.prototype.getData = function (){
	let that = this;
	let ids_arr = [];
	that.data.forEach((list)=>{
		let ids = [];
		list.forEach((item)=>{
			ids.push(item.category_id);
		})
		ids_arr.push(ids.toString());
	})
	return ids_arr;
}

function refreshBatchOperate(){
	try{
		$(".select_spec_value").show()
		let batchOperateTemplate = $("#batchOperateTemplate").html()
		laytpl(batchOperateTemplate).render(goodsSpecFormat, function (html) {
			$(".select_spec_value").empty().append(html)
		})
		form.render();
	}catch (error){
		console.error("捕获到错误:", error.message);
	}
}

//多图上传
function MultiImageUpload(param) {
	console.log(param, '11111')
	let that = this;
	param = param || {};
	let image_list = param.image_list || [];
	let max_num = param.max_num || 10;
	let container = param.container || null;
	let form,laytpl;
	let template = $("#goodsImage").html();

	function _render(){
		laytpl(template).render({list : image_list, max : max_num}, function (html) {
			$(container).html(html);
			// 拖拽
			$(container).find('.upload_img_square_item').arrangeable({
				//拖拽结束后执行回调
				callback: function (e) {
					var indexBefore = $(e).attr("data-index");//拖拽前的原始位置
					var indexAfter = $(e).index();//拖拽后的位置
					var temp = image_list[indexBefore];
					image_list[indexBefore] = image_list[indexAfter];
					image_list[indexAfter] = temp;
					_render();
				}
			});
		})
	}

	function _init(){
		//添加资源主图
		$(container).off("click", ".js-add-goods-image").on("click", ".js-add-goods-image", function () {
			openAlbum(function (data) {
				for (var i = 0; i < data.length; i++) {
					if (image_list.length < max_num) image_list.push(data[i].pic_path);
				}
				_render();
			}, max_num - image_list.length);
		});

		//预览
		$(container).off('click', ".js-preview").on('click', ".js-preview", function () {
			var index = $(this).parents('.upload_img_square_item').attr("data-index");
			var data = [];
			image_list.forEach((img_path, i) => {
				data.push({
					"alt": "第"+(i+1)+"张",
					"pid": '', //图片id
					"src": ns.img(img_path), //原图地址
					"thumb": "" //缩略图地址
				})
			})
			layer.photos({
				photos: {
					"title": "", //相册标题
					"id": '', //相册id
					"start": index, //初始显示的图片序号，默认0
					"data": data, //相册包含的图片，数组格式
				}
				,anim: 5 //0-6的选择，指定弹出图片动画类型，默认随机（请注意，3.0之前的版本用shift参数）
			});
		});

		//图片删除
		$(container).off('click', ".js-delete").on('click', ".js-delete", function () {
			var index = $(this).parents('.upload_img_square_item').attr("data-index");
			image_list.splice(index, 1);
			_render();
		});

		//替换资源主图
		$(container).off("click", ".replace_img").on("click", ".replace_img", function () {
			var index = $(this).parents('.upload_img_square_item').attr("data-index");
			openAlbum(function (data) {
				for (var i = 0; i < data.length; i++) {
					image_list[index] = data[i].pic_path
				}
				_render();
			}, 1);
		});
	}

	layui.use(['laytpl', 'form'], function () {
		form = layui.form;
		laytpl = layui.laytpl;
		_render();
		_init();
	})

	that.getData = function(){
		return image_list;
	}
}
