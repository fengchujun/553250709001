<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<view class="iconfont iconshang navigate-back" @click="navigateBack"></view>
		<web-view :src="src"></web-view>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
export default {
	data() {
		return {
			src: ''
		};
	},
	onLoad(option) {
		this.src = decodeURIComponent(option.src);
	},
	methods: {
		navigateBack() {
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>

<style lang="scss">
.navigate-back {
	position: absolute;
	top: 34rpx;
	left: 34rpx;
	z-index: 5;
	font-size: $font-size-toolbar;
}
</style>
