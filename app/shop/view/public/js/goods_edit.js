requestAdd = 'shop/goods/addGoods';
requestEdit = 'shop/goods/editGoods';

// 追加刷新商品sku数据
appendRefreshGoodsSkuData = {
	weight: "", // 重量
	volume: "", // 体积
};

// 追加单规格数据
function appendSingleGoodsData(data) {
	return {
		weight: data.field.weight,
		volume: data.field.volume
	};
}

// 追加保存数据
function appendSaveData(data) {
	var supportTradeType = [];
	$('[name="support_trade_type"]:checked').each(function () {
		supportTradeType.push($(this).val())
	});

	return {
		support_trade_type: supportTradeType.toString()
	}
}

$(function () {

	layui.use(['element', 'laytpl', 'form'], function () {
		form = layui.form;
		element = layui.element;
		laytpl = layui.laytpl;
		form.render();
		element.render();

		form.on('checkbox(support_trade_type)', function (data) {
			if (data.value == 'express') {
				if ($(data.elem).is(':checked')) {
					$('.trade-type.express').show()
				} else {
					$('.trade-type.express').hide()
				}
			}
		});

		//是否免邮
		form.on("radio(is_free_shipping)", function (data) {
			if (data.value == 0) {
				$(".js-shipping-template").show();
			} else {
				$(".js-shipping-template").hide();
			}
		});

		// 运费模板刷新
		$('.delivery-refresh').click(function () {
			$.ajax({
				url: ns.url('shop/goods/getexpresstemplatelist'),
				dataType: 'JSON',
				type: 'POST',
				success: function (res) {
					if (res.code == 0) {
						var html = $("#deliveryHtml").html();
						laytpl(html).render({
							list: res.data,
							shipping_template: $('select[name="shipping_template"] option:selected').val()
						}, function (html) {
							$('select[name="shipping_template"]').html(html);
							form.render();
						});
					}
				}
			});
		});

		form.verify({
			//重量
			weight: function (value) {
				if (!$("input[name='spec_type']").is(":checked")) {
					if (value.length > 0) {
						if (isNaN(value) || !ns.getRegexp('>0float3').test(value)) {
							element.tabChange('goods_tab', "price-stock");
							return '重量必须为正数，且最多保留三位小数';
						}
					}
				}
			},
			//体积
			volume: function (value) {
				if (!$("input[name='spec_type']").is(":checked")) {
					if (value.length > 0) {
						if (isNaN(value) || !ns.getRegexp('>0float3').test(value)) {
							element.tabChange('goods_tab', "price-stock");
							return '体积必须为正数，且最多保留三位小数';
						}
					}
				}
			},
			//sku重量
			sku_weight: function (value) {
				if (value.length > 0) {
					if (isNaN(value) || !ns.getRegexp('>0float3').test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '重量必须为正数，且最多保留三位小数';
					}
				}
			},
			//sku体积
			sku_volume: function (value) {
				if (value.length > 0) {
					if (isNaN(value) || !ns.getRegexp('>0float3').test(value)) {
						element.tabChange('goods_tab', "price-stock");
						return '体积必须为正数，且最多保留三位小数';
					}
				}
			},
			express_type: function () {
				if ($('[name="support_trade_type"]').val() == undefined) return '请先配置配送方式';
				if (!$('[name="support_trade_type"]:checked').val()) return '请选择配送方式';
			},
			//运费模板
			shipping_template: function (value) {
				if ($('[name="support_trade_type"][value="express"]').is(':checked') && $("input[name='is_free_shipping']:checked").val() == 0) {
					if (value == "") {
						element.tabChange('goods_tab', "basic");
						return '请选择运费模板';
					}
				}
			}
		});

	});

});
