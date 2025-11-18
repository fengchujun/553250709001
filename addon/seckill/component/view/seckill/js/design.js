var seckillListHtml = '<div style="display:none;"></div>';

Vue.component("seckill-list-sources", {
	template: seckillListHtml,
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
							lineSupport: true, // 是否支持划线价
							saleSupport: false, // 是否支持销量
							progressSupport: true // 是否支持进度
						},
						{
							text: "样式2",
							value: "style-2",
							cartSupport: true, // 是否支持按钮
							lineSupport: true, // 是否支持划线价
							saleSupport: false, // 是否支持销量
							progressSupport: true // 是否支持进度
						}
					],
				},
				"row1-of2": {
					text: "两列",
					icon: "iconyihanglianglie",
					styleList: [
						{
							text: "样式1",
							value: "style-1",
							cartSupport: true, // 是否支持购物车按钮
							lineSupport: true, // 是否支持划线价
							saleSupport: false, // 是否支持销量
							progressSupport: false // 是否支持进度
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
							lineSupport: true, // 是否支持划线价
							saleSupport: false,// 是否支持销量
							progressSupport: false // 是否支持进度
						},
						{
							text: "样式2",
							value: "style-2",
							cartSupport: false, // 是否支持按钮
							lineSupport: true, // 是否支持划线价
							saleSupport: false,// 是否支持销量
							progressSupport: false, // 是否支持进度
							mainColor: "#FFFFFF", // 销售价颜色
							goodsNameControl: false // 是否显示商品名称
						},
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
					isShow: true,
					leftStyle: "text",
					leftImg: "",
					leftText: "限时秒杀",
					backgroundImage: '',
					bgColorStart: "#FFFFFF",
					bgColorEnd: "#FFFFFF",
					textColor: "#303133",
					timeImageUrl: "",
					timeBgColor: "",
					numBgColorStart: "#303133", // 数字背景色开始
					numBgColorEnd: "#303133", // 数字背景色结束
					numTextColor: "#FFFFFF", // 数字颜色
					colonColor: "#303133", // 冒号颜色
					moreColor: "#999999",
					more: "查看更多",
					moreSupport: true
				},
				"style-2": {
					isShow: true,
					leftStyle: "text",
					leftImg: "",
					leftText: "限时秒杀",
					backgroundImage: '',
					bgColorStart: "#FFFFFF",
					bgColorEnd: "#FFFFFF",
					textColor: "#303133",
					timeBgColor: "#303133",
					timeImageUrl: 'icondiy icon-system-seckill-time',
					numBgColorStart: "#303133",
					numBgColorEnd: "#303133",
					numTextColor: "#FFFFFF",
					colonColor: "#FFFFFF",
					moreColor: "#999999",
					more: "查看更多",
					moreSupport: true
				},
				"style-3": {
					isShow: true,
					leftStyle: "img",
					leftImg: seckillRelativePath + '/img/style_title_3_name.png',
					leftText: "",
					backgroundImage: seckillRelativePath + '/img/style_title_3_bg.png',
					bgColorStart: "#FA6400",
					bgColorEnd: "#FF287A",
					textColor: "#FFFFFF",
					timeBgColor: "",
					timeImageUrl: '',
					numBgColorStart: "#FFFFFF",
					numBgColorEnd: "#FFFFFF",
					numTextColor: "#FD3B54",
					colonColor: "#FFFFFF",
					moreColor: "#FFFFFF",
					more: "更多",
					moreSupport: true
				},
				"style-4": {
					isShow: true,
					leftStyle: "img",
					leftImg: seckillRelativePath + '/img/style_title_4_name.png',
					leftText: "",
					backgroundImage: seckillRelativePath + '/img/style_title_4_bg.png',
					bgColorStart: "#FFFFFF",
					bgColorEnd: "#FFFFFF",
					textColor: "#666666",
					timeBgColor: "",
					timeImageUrl: '',
					numBgColorStart: "#FF5F17",
					numBgColorEnd: "#FE2F18",
					numTextColor: "#FFFFFF",
					colonColor: "#FE3718",
					moreColor: "",
					more: "",
					moreSupport: false
				}
			},
		}
	},
	created: function () {
		if (!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法

		this.$parent.data.ignore = ['textColor']; //加载忽略内容 -- 其他设置中的属性设置
		this.$parent.data.ignoreLoad = true; // 等待忽略数组赋值后加载

		var previewList = {};
		for (var i = 1; i < 5; i++) {
			previewList["goods_id_" + ns.gen_non_duplicate(i)] = {
				goods_name: "秒杀商品",
				discount_price: (Math.random() * 100 * i + 10).toFixed(2), // 随机价格
				line_price: (Math.random() * 100 * i + 100 + 10).toFixed(2), // 随机价格
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
			}, self.$parent.data.goodsId, {mode: "spu", promotion: "seckill", disabled: 0, post: ns.appModule});
		},
		selectTemplate(template, item) {
			if (template) {
				this.$parent.data.template = template;
				item = this.templateList[template].styleList[0];
			}

			this.$parent.data.style = item.value;
			this.$parent.data.btnStyle.support = item.cartSupport;
			this.$parent.data.btnStyle.control = item.cartSupport;
			this.$parent.data.priceStyle.lineSupport = item.lineSupport;
			this.$parent.data.priceStyle.lineControl = item.lineSupport;
			this.$parent.data.saleStyle.support = item.saleSupport;
			this.$parent.data.saleStyle.control = item.saleSupport;
			this.$parent.data.progressStyle.support = item.progressSupport;
			this.$parent.data.progressStyle.control = item.progressSupport;

			if (item.mainColor) {
				this.$parent.data.priceStyle.mainColor = item.mainColor;
			} else {
				this.$parent.data.priceStyle.mainColor = '#FF1745';
			}

			if (item.goodsNameControl !== undefined) {
				this.$parent.data.goodsNameStyle.control = item.goodsNameControl;
			} else {
				this.$parent.data.goodsNameStyle.control = true;
			}

			if (this.data.template == "row1-of1" && this.data.style == "style-2") {
				this.$parent.data.progressStyle.bgColor = "#FFD5D5";
				this.$parent.data.progressStyle.currColor = "#ff0400";
			}
		},
		selectTopStyle: function () {
			var self = this;
			layer.open({
				type: 1,
				title: '风格选择',
				area: ['910px', '360px'],
				btn: ['确定', '返回'],
				content: $(".draggable-element[data-index='" + self.data.index + "'] .edit-attribute .style-list-box-seckill").html(),
				success: function (layero, index) {
					$(".layui-layer-content input[name='style']").val(self.data.titleStyle.style);
					$(".layui-layer-content input[name='style_name']").val(self.data.titleStyle.styleName);
					$("body").off("click", ".layui-layer-content .style-list-con-seckill .style-li-seckill").on("click", ".layui-layer-content .style-list-con-seckill .style-li-seckill", function () {
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
					loadImgMagnify();
				}
			});
		}
	}
});