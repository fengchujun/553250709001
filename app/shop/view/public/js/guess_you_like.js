/**
 * 手机端自定义模板Vue对象
 */
var vue = new Vue({
	el: "#diyView",
	data: {
		lazyLoad: false,
		global: {
			title: "商品推荐",
			pageBgColor: "#ffffff", // 页面背景颜色
			topNavColor: "#ffffff",
			navStyle: 1, // 导航栏风格
			textNavColor: "#333333",
			textImgPosLink: 'center',
		},
		data: {
			title: '猜你喜欢',
			sources: 'sort',
			supportPage: [],
			goodsIds: [],

			fontWeight: false,
			padding: 10,
			cartEvent: "detail",
			text: "购买",
			textColor: "#FFFFFF",
			theme: "default",
			aroundRadius: 25,
			control: true,
			bgColor: "#FF6A00",
			style: "button",
			nameLineMode: "single",
			iconDiy: {
				iconType: "icon",
				icon: "",
				style: {
					fontSize: "60",
					iconBgColor: [],
					iconBgColorDeg: 0,
					iconBgImg: "",
					bgRadius: 0,
					iconColor: [
						"#000000"
					],
					iconColorDeg: 0
				}
			}
		},
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
	},
	created: function () {
		if ($("#guessYouLikeConfig").val()) {
			$('#diyView').css('visibility', 'visible');
			$('.preview-wrap .preview-restore-wrap').css('visibility', 'visible');
			var self = this;
			setTimeout(() => {
				this.data = JSON.parse($("#guessYouLikeConfig").val());
				fullScreenSize(function () {
					self.lazyLoad = true;
					self.fetchIconColor();
				});
			}, 10);
		} else {
			$('#diyView').css('visibility', 'visible');
			$('.preview-wrap .preview-restore-wrap').css('visibility', 'visible');
			this.lazyLoad = true;
			this.fetchIconColor();
		}
	},
	methods: {
		//转换图片路径
		changeImgUrl: function (url) {
			if (url == null || url === "") return '';
			if (url.indexOf("static/img/") > -1) return ns.img(ns_url.staticImg + "/" + url.replace("public/static/img/", ""));
			else return ns.img(url);
		},
		/**
		 * 选择图标风格
		 * @param event
		 */
		iconStyle(event) {
			var self = this;
			selectIconStyle({
				elem: event.currentTarget,
				icon: self.data.iconDiy.icon,
				callback: function (data) {
					if (data) {
						self.data.iconDiy.style = data;
					} else {
						iconStyleSet({
							style: JSON.stringify(self.data.iconDiy.style),
							query: {
								icon: self.data.iconDiy.icon
							}
						}, function (style) {
							self.data.iconDiy.style = style;
						})
					}
				}
			})
		},
		/**
		 * 渲染颜色组件
		 * @param id
		 * @param color
		 * @param callback
		 */
		colorRender(id, color, callback) {
			setTimeout(function () {
				Colorpicker.create({
					el: id,
					color: color,
					change: function (elem, hex) {
						callback(elem, hex)
					}
				});
			})
		},
		// 渲染图标颜色选择器
		fetchIconColor() {
			var self = this;
			self.colorRender('goods-list-color', '', function (elem, color) {
				if (self.data.iconDiy.style.iconBgColor.length || self.data.iconDiy.style.iconBgImg) {
					self.data.iconDiy.style.iconBgColor = [color];
				} else {
					self.data.iconDiy.style.iconColor = [color];
				}
				self.$forceUpdate();
			});
		},
		addSupportPage(page) {
			var index = this.data.supportPage.indexOf(page);
			if (index != -1) {
				this.data.supportPage.splice(index, 1);
			} else {
				this.data.supportPage.push(page);
			}
		},
		addGoods() {
			var self = this;
			goodsSelect(function (data) {

				self.data.goodsIds = [];

				for (var key in data) {
					self.data.goodsIds.push(data[key].goods_id);
				}

			}, self.data.goodsIds, {mode: "spu", disabled: 0});
		}
	}
});

// 自适应全屏宽高
function fullScreenSize(callback) {
	if (callback) callback();
	setTimeout(function () {
		var size = 139; // 公式：二级面包屑layui-header-crumbs-second （55px）+ 自定义模板区域上内边距diyview（20px） + 底部保存按钮（90px）
		var commonHeight = $(window).height() - size;
		['.preview-wrap .preview-restore-wrap .div-wrap'].forEach(function (obj) {
			$(obj).css("height", (commonHeight) + "px");
		});
		$(".edit-attribute .attr-wrap").css("height", (commonHeight - 1) + "px");// 1px是上边框
		$(".preview-block").css("min-height", (commonHeight - 104) + "px"); // 公式：高度 - 自定义模板区域上内边距（20px） - 自定义模板区域下外编辑（20px）- 自定义模板头部（64px）
	}, 30);
}

layui.use(['form'], function () {
	var form, repeat_flag = false;//防重复标识
	form = layui.form;
	form.render();

	fullScreenSize();

	$("body").off("click", ".edit-attribute .attr-wrap .restore-wrap .attr-title .tab-wrap span").on("click", ".edit-attribute .attr-wrap .restore-wrap .attr-title .tab-wrap span", function () {
		$(this).addClass('active bg-color').siblings().removeClass('active bg-color');
		var type = $(this).attr('data-type');
		$(this).parent().parent().parent().find('.edit-content-wrap').hide();
		$(this).parent().parent().parent().find('.edit-style-wrap').hide();
		$(this).parent().parent().parent().find(`.edit-${type}-wrap`).show();
	});

	form.on('submit(save)', function (data) {
		if (repeat_flag) return;
		repeat_flag = true;

		$.ajax({
			type: "post",
			url: ns.url('shop/goods/guessyoulike'),
			data: {
				value: JSON.stringify(vue.data)
			},
			dataType: "JSON",
			success: function (res) {
				repeat_flag = false;
				layer.msg(res.message);
			}
		});
	});

});