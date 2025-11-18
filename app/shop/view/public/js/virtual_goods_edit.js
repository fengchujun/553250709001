requestAdd = 'shop/virtualgoods/addGoods';
requestEdit = 'shop/virtualgoods/editGoods';

// 追加刷新商品sku数据
appendRefreshGoodsSkuData = {
	verify_num: 1
};

// 追加刷新规格表格
function appendSkuTableData() {
	return {
		isNeedVerify: $('[name="virtual_deliver_type"]:checked').val() == 'verify' ? 1 : 0
	};
}

// 追加单规格数据
function appendSingleGoodsData(data) {
	return {
		verify_num: data.field.verify_num
	};
}

// 追加保存数据
function appendSaveData(data) {
	return {
		is_need_verify: $('[name="virtual_deliver_type"]:checked').val() == 'verify' ? 1 : 0,
		verify_validity_type: $('[name="verify_validity_type"]:checked').val()
	}
}

$(function () {

	layui.use(['element', 'laytpl', 'form', 'laydate'], function () {
		form = layui.form;
		element = layui.element;
		laytpl = layui.laytpl;
		laydate = layui.laydate;
		form.render();

		var time = new Date();
		var currentTime = time.toLocaleDateString + " " + time.getHours() + ":" + time.getMinutes() + ":" + time.getSeconds();

		//核销有效期
		laydate.render({
			elem: '#virtual_time', //指定元素
			type: 'datetime',
			min: currentTime
		});

		// 是否需核销
		form.on("radio(virtual_deliver_type)", function (data) {
			if (data.value == 'verify') {
				$('.need-receive').addClass('layui-hide');
				$('.need-verify').removeClass('layui-hide');
			} else {
				$('.need-verify').addClass('layui-hide');
				$('.need-receive').removeClass('layui-hide');
			}
			refreshSkuTable();
		});

		//核销有效期类型
		form.on('radio(verify_validity_type)', function (data) {
			var value = parseInt(data.value);
			$('.validity-type').addClass('layui-hide');
			$('.validity-type.validity-type-' + value).removeClass('layui-hide');
		});

		form.verify({
			// 总核销次数
			goods_verify_num: function (value) {
				if ($('input[name="spec_type"]').is(":checked") === false) {
					var is_need_verify = $('[name="virtual_deliver_type"]:checked').val() == 'verify' ? 1 : 0;
					if (is_need_verify) {
						if (isNaN(value) || !regExp.number.test(value)) {
							element.tabChange('goods_tab', "price-stock");
							return '[核销次数]格式输入错误';
						}
						if (value < 1) {
							element.tabChange('goods_tab', "price-stock");
							return '核销次数不能小于1';
						}
					}
				}

			},
			//有效期
			virtual_indate: function (value) {
				var is_need_verify = $('[name="virtual_deliver_type"]:checked').val() == 'verify' ? 1 : 0;
				var verify_validity_type = $('[name="verify_validity_type"]:checked').val();
				if (is_need_verify && verify_validity_type == 1) {
					if (isNaN(value) || !regExp.number.test(value)) {
						element.tabChange('goods_tab', "basic");
						return '[核销有效期]格式输入错误';
					}
					if (value < 1) {
						element.tabChange('goods_tab', "basic");
						return '核销有效期不能小于1天';
					}
				}
			},
			virtual_time: function (value) {
				var is_need_verify = $('[name="virtual_deliver_type"]:checked').val() == 'verify' ? 1 : 0;
				var verify_validity_type = $('[name="verify_validity_type"]:checked').val();
				if (is_need_verify && verify_validity_type == 2 && value.length == 0) {
					element.tabChange('goods_tab', "basic");
					return "请输入有效期";
				}
			},
			verify_num: function (value) {
				var is_need_verify = $('[name="virtual_deliver_type"]:checked').val() == 'verify' ? 1 : 0;
				if (is_need_verify) {
					if (isNaN(value) || !regExp.number.test(value)) {
						element.tabChange('goods_tab', "basic");
						return '[核销次数]格式输入错误';
					}
					if (value < 1) {
						element.tabChange('goods_tab', "basic");
						return '核销次数不能小于1';
					}
				}
			}
		});

	});

});