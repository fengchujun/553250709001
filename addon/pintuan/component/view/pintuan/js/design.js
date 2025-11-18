var pintuanListHtml = '<div style="display:none;"></div>';

Vue.component("pintuan-list-sources", {
	template: pintuanListHtml,
	data: function () {
		return {
			data: this.$parent.data,
			goodsSources: {
				initial: {
					text: "默认",
					icon: "iconmofang"
				},
				diy: {
					text: "手动选择",
					icon: "iconshoudongxuanze"
				},
			},
			templateList: {
				"row1-of1": {
					text: "单列",
					icon: "iconiPhone86",
					styleList: [
						{
							text: "样式1",
							value: "style-1",
							cartSupport: true, // 是否支持按钮
							saleSupport: true, // 是否支持商品销量
							lineSupport: true, // 是否支持划线价
						},
					],
				},
				"horizontal-slide": {
					text: "横向滑动",
					icon: "iconshangpinliebiaohengxianghuadong",
					styleList: [
						{
							text: "样式1",
							value: "style-1",
							cartSupport: false, // 是否支持按钮
							saleSupport: false, // 是否支持商品销量
							lineSupport: false, // 是否支持划线价
						},
						{
							text: "样式2",
							value: "style-2",
							cartSupport: false, // 是否支持按钮
							saleSupport: false, // 是否支持商品销量
							lineSupport: false, // 是否支持划线价
						},
						{
							text: "样式3",
							value: "style-3",
							cartSupport: false, // 是否支持按钮
							saleSupport: false, // 是否支持商品销量
							lineSupport: false, // 是否支持划线价
						}
					],
				},
			},
			ornamentList: [
				{
					type: 'default',
					text: '默认',
				},
				{
					type: 'shadow',
					text: '投影',
				},
				{
					type: 'stroke',
					text: '描边',
				},
			],
			nameLineModeList: [
				{
					text: "单行",
					value: "single"
				},
				{
					text: "多行",
					value: "multiple"
				}
			],
			styleList: {
				"style-1": {
					styleName:'风格1',
					leftStyle: "img",
					leftImg: pintuanRelativePath + '/img/horizontal_slide_name.png',
					leftText: "超值拼团",
					backgroundImage: pintuanRelativePath + '/img/horizontal_slide_bg.png',
					bgColorStart: "#6236FF",
					bgColorEnd: "#0091FF",
					textColor: "#FFFFFF",
					moreColor: "#FFFFFF",
					more: "更多"
				},
				"style-2": {
					styleName:'风格2',
					leftStyle: "img",
					leftImg: pintuanRelativePath + '/img/style_2_title.png',
					leftText: "超值拼团",
					backgroundImage: "",
					bgColorStart: "#FFFFFF",
					bgColorEnd: "#FFFFFF",
					textColor: "#888888",
					moreColor: "#FFFFFF",
					more: "更多"
				}
			}
		}
	},
	created: function () {
		if (!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法
		
		this.$parent.data.ignore = ['textColor']; //加载忽略内容 -- 其他设置中的属性设置
		this.$parent.data.ignoreLoad = true; // 等待忽略数组赋值后加载

		var previewList = {};
		for (var i = 1; i < 4; i++) {
			previewList["goods_id_" + ns.gen_non_duplicate(i)] = {
				goods_name: "拼团商品",
				discount_price: (Math.random() * 100 * i + 10).toFixed(2), // 随机价格
				line_price: (Math.random() * 100 * i + 100 + 10).toFixed(2), // 随机价格
				sale_num: Math.floor((Math.random() * 100 * i + 10 + 10))
			};
		}

		// 组件所需的临时数据
		this.$parent.data.tempData = {
			goodsSources: this.goodsSources,
			templateList: this.templateList,
			ornamentList: this.ornamentList,
			nameLineModeList: this.nameLineModeList,
			styleList: this.styleList,
			previewList: previewList,
			methods: {
				addGoods: this.addGoods,
				selectTemplate: this.selectTemplate,
				selectTopStyle: this.selectTopStyle
			}
		};

		loadImgMagnify();
	},
	methods: {
		verify: function (index) {
			var res = {code: true, message: ""};
			if (vue.data[index].sources === 'diy' && vue.data[index].goodsId.length === 0) {
				res.code = false;
				res.message = "请选择商品";
			}
			return res;
		},
		addGoods: function () {
			var self = this;
			goodsSelect(function (res) {
				self.$parent.data.goodsId = res;
			}, self.$parent.data.goodsId, {mode: "spu", promotion: "pintuan", disabled: 0, post: ns.appModule});
		},
		selectTemplate(template, item) {
			if (template) {
				this.$parent.data.template = template;
				item = this.templateList[template].styleList[0];
			}
			this.$parent.data.style = item.value;
			this.$parent.data.btnStyle.support = item.cartSupport;
			this.$parent.data.btnStyle.control = item.cartSupport;
			this.$parent.data.saleStyle.support = item.saleSupport;
			this.$parent.data.saleStyle.control = item.saleSupport;
			this.$parent.data.priceStyle.lineSupport = item.lineSupport;
			this.$parent.data.priceStyle.lineControl = item.lineSupport;
		},
		selectTopStyle: function () {
			var self = this;
			layer.open({
				type: 1,
				title: '风格选择',
				area: ['910px', '350px'],
				btn: ['确定', '返回'],
				content: $(".draggable-element[data-index='" + self.data.index + "'] .edit-attribute .style-list-box-pintuan").html(),
				success: function (layero, index) {
					$(".layui-layer-content input[name='style']").val(self.data.titleStyle.style);
					$(".layui-layer-content input[name='style_name']").val(self.data.titleStyle.styleName);
					$("body").off("click", ".layui-layer-content .style-list-con-pintuan .style-li-pintuan").on("click", ".layui-layer-content .style-list-con-pintuan .style-li-pintuan", function () {
						$(this).addClass("selected border-color").siblings().removeClass("selected border-color bg-color-after");
						$(".layui-layer-content input[name='style']").val($(this).attr("data_key"));
						$(".layui-layer-content input[name='style_name']").val($(this).find("span").text());
					});
				},
				yes: function (index, layero) {
					self.data.titleStyle.style = $(".layui-layer-content input[name='style']").val();
					Object.assign(self.data.titleStyle, self.styleList[self.data.titleStyle.style]);
					self.data.titleStyle.styleName = $(".layui-layer-content input[name='style_name']").val();
					layer.closeAll()
				}
			});
		}
	}
});