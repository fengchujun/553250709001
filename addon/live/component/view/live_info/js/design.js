// 显示内容
var showContentHtml = '<div class="layui-form-item goods-show-box checkbox-wrap">';
		showContentHtml += '<div class="layui-input-block">';
		showContentHtml +=		'<div class="layui-input-inline-checkbox">';
		showContentHtml +=			'<span>主播信息</span>';
		showContentHtml +=			'<div @click="changeStatus(\'isShowAnchorInfo\')" class="layui-unselect layui-form-checkbox" :class="{\'layui-form-checked\': (data.isShowAnchorInfo == 1)}" lay-skin="primary"><i class="layui-icon layui-icon-ok"></i></div>';
		showContentHtml +=		'</div>';
		showContentHtml +=		'<div class="layui-input-inline-checkbox">';
		showContentHtml +=			'<span>直播商品</span>';
		showContentHtml +=			'<div @click="changeStatus(\'isShowLiveGood\')" class="layui-unselect layui-form-checkbox" :class="{\'layui-form-checked\': (data.isShowLiveGood == 1)}" lay-skin="primary"><i class="layui-icon layui-icon-ok"></i></div>';
		showContentHtml +=		'</div>';
		showContentHtml += '</div>';
	showContentHtml += '</div>';

Vue.component("live-show-content", {
	template: showContentHtml,
	data: function () {
		return {
			data: this.$parent.data,
			isShowAnchorInfo: this.$parent.data.isShowAnchorInfo,
			isShowLiveGood: this.$parent.data.isShowLiveGood,
		};
	},
	created: function () {
		if(!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法
	},
	methods: {
		verify :function () {
			var res = { code: true, message: "" };
			return res;
		},
		changeStatus: function(field) {
			this.$parent.data[field] = this.$parent.data[field] ? 0 : 1;
		}
	}
});

// 显示内容
var liveSetHtml = '<div></div>';
Vue.component("live-set", {
	template: liveSetHtml,
	data: function () {
		return {};
	},
	created: function () {
		this.$parent.data.ignore = ['marginBoth', 'textColor', 'elementAngle', 'componentAngle', 'elementBgColor', 'componentBgColor', 'pageBgColor']; //加载忽略内容 -- 其他设置中的属性设置
		this.$parent.data.ignoreLoad = true; // 等待忽略数组赋值后加载
	}
});