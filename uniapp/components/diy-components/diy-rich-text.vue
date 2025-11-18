<template>
	<view :style="value.pageStyle">
		<view class="rich-text-box" :style="richTextWarpCss">
			<ns-mp-html :content="html"></ns-mp-html>
		</view>
	</view>
</template>

<script>
// 富文本
import htmlParser from '@/common/js/html-parser';
export default {
	name: 'diy-rich-text',
	props: {
		value: {
			type: Object
		}
	},
	data() {
		return {
			html: '',
		};
	},
	created() {
		// this.html = htmlParser(this.value.html);
		this.html = this.value.html;
	},
	watch: {
		// 组件刷新监听
		componentRefresh: function(nval) {
			this.html = this.value.html;
		}
	},
	computed: {
		richTextWarpCss: function() {
			var obj = '';
			obj += 'background-color:' + this.value.componentBgColor + ';';
			if (this.value.componentAngle == 'round') {
				obj += 'border-top-left-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
				obj += 'border-top-right-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
				obj += 'border-bottom-left-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
				obj += 'border-bottom-right-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
			}
			return obj;
		}
	},
	mounted() {},
	methods: {}
};
</script>

<style lang="scss">
.rich-text-box {
	padding: $padding;
	box-sizing: border-box;
	height: auto;
	line-height: 1.5;
	white-space: pre-wrap;
	word-break: break-all;
}
</style>
