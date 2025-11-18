<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="contact">
		<image :src="$util.img('public/uniapp/member/contact_service.png')" mode="widthFix"></image>
		<view class="tips">请点击下方按钮，联系客服</view>
		<ns-contact><button type="primary">联系客服</button></ns-contact>
	</view>
</template>

<script>
export default {
	data() {
		return {};
	},
	onLoad(options) {},
	onShow() {},
	methods: {}
};
</script>

<style lang="scss">
.contact {
	width: 80%;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	margin: 150rpx auto 0;
	image {
		width: 500rpx;
	}
	.tips{
		font-size: 24rpx;
		color: #999;
		margin-bottom: 20rpx;
	}
}
</style>
