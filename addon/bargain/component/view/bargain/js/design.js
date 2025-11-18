var bargainListHtml = '<div style="display:none;"></div>';

Vue.component("bargain-list-sources", {
	template: bargainListHtml,
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
							saleSupport: false, // 是否支持商品销量
							lineSupport: false, // 是否支持划线价
						},
						{
							text: "样式2",
							value: "style-2",
							cartSupport: true, // 是否支持按钮
							saleSupport: false, // 是否支持商品销量
							lineSupport: false, // 是否支持划线价
						},
						{
							text: "样式3",
							value: "style-3",
							cartSupport: true, // 是否支持按钮
							saleSupport: false, // 是否支持商品销量
							lineSupport: false, // 是否支持划线价
						}
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
							saleSupport: true, // 是否支持商品销量
							lineSupport: true, // 是否支持划线价
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
					leftStyle: "img",
					leftImg: bargainRelativePath + '/img/row1_of1_style_2_name.png',
					leftText: "疯狂砍价",
					backgroundImage: bargainRelativePath + '/img/row1_of1_style_2_bg.png',
					bgColorStart: "#FF209D",
					bgColorEnd: "#B620E0",
					textColor: "#FFFFFF",
					moreColor: "#FFFFFF",
					more: "更多"
				},
				"style-2": {
					leftStyle: "img",
					leftImg: bargainRelativePath + '/img/row1_of1_style_3_name.png',
					leftText: "疯狂砍价",
					backgroundImage: '',
					bgColorStart: "#FFFFFF",
					bgColorEnd: "#FFFFFF",
					textColor: "#FFFFFF",
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

		Object.assign(this.data.titleStyle, this.styleList[this.data.titleStyle.style]);

		var previewList = {};
		for (var i = 1; i < 4; i++) {
			previewList["goods_id_" + ns.gen_non_duplicate(i)] = {
				goods_name: "砍价商品",
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
			previewList: previewList,
			styleList: this.styleList,
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
			}, self.$parent.data.goodsId, {mode: "spu", promotion: "bargain", disabled: 0, post: ns.appModule});
		},
		selectTemplate(template, item) {
			if (template) {
				this.$parent.data.template = template;
				item = this.templateList[template].styleList[0];
			}

			// 修改按钮样式
			if(item.value == "style-3"){
				this.$parent.data.btnStyle.bgColorStart = "#3EDB73";
				this.$parent.data.btnStyle.bgColorEnd = "#1DB576";
				this.$parent.data.btnStyle.textColor = "#FFFFFF";
				this.$parent.data.btnStyle.aroundRadius = "4";
				this.$parent.data.btnStyle.text = "去砍价";
			}else{
				this.$parent.data.btnStyle.bgColorStart = "rgb(255, 123, 29)";
				this.$parent.data.btnStyle.bgColorEnd = "rgb(255, 21, 68)";
				this.$parent.data.btnStyle.textColor = "#FFFFFF";
				this.$parent.data.btnStyle.aroundRadius = "25";
				this.$parent.data.btnStyle.text = "立即抢购";
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
				content: $(".draggable-element[data-index='" + self.data.index + "'] .edit-attribute .bargain-style-list-box").html(),
				success: function (layero, index) {
					$(".layui-layer-content input[name='style']").val(self.data.titleStyle.style);
					$(".layui-layer-content input[name='style_name']").val(self.data.titleStyle.styleName);
					$("body").off("click", ".layui-layer-content .bargain-style-list-con .bargain-style-li").on("click", ".layui-layer-content .bargain-style-list-con .bargain-style-li", function () {
						$(this).addClass("selected border-color").siblings().removeClass("selected border-color bg-color-after");
						$(".layui-layer-content input[name='style']").val($(this).attr("data_key"));
						$(".layui-layer-content input[name='style_name']").val($(this).find("span").text());
					});
				},
				yes: function (index, layero) {
					self.data.titleStyle.style = $(".layui-layer-content input[name='style']").val();
					Object.assign(self.data.titleStyle, self.styleList[self.data.titleStyle.style]);
					self.data.titleStyle.styleName = $(".layui-layer-content input[name='style_name']").val();
					layer.closeAll();
				}
			});
		}
	}
});