<template>
	<view :style="value.pageStyle">
		<view :style="horzBlankGaugeWrap"></view>
	</view>
</template>

<script>
// 辅助空白
export default {
	name: 'diy-horz-blank',
	props: {
		value: {
			type: Object,
			default: () => {
				return {};
			}
		}
	},
	data() {
		return {};
	},
	watch: {
		// 组件刷新监听
		componentRefresh: function(nval) {}
	},
	computed: {
		horzBlankGaugeWrap: function() {
			var obj = '';
			obj += 'background-color:' + this.value.componentBgColor + ';';
			if (this.value.componentAngle == 'round') {
				obj += 'border-top-left-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
				obj += 'border-top-right-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
				obj += 'border-bottom-left-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
				obj += 'border-bottom-right-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
			}
			obj += 'height:' + this.value.height * 2 + 'rpx';
			return obj;
		}
	},
	created() {},
	methods: {}
};
</script>

<style></style>
