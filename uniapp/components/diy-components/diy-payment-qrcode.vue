<template>
	<view :style="value.pageStyle">
		<view class="payment-qrocde-wrap" :style="warpCss">
			<view class="payment-qrocde-box">
				<view class="qrocde-left">
					<view class="qrocde-desc">
						<text>门店消费时使用，支付时点击下方展示付款码</text>
						<!-- <text class="iconfont icon-shuaxin"></text> -->
					</view>
					<view class="qrocde-action">
						<button type="primary" @click="toLink">
							<text class="iconfont icon-fukuanma"></text>
							<text class="action-name">付款码</text>
						</button>
						<button type="primary" @click="openPaymentPopup">
							<text class="iconfont icon-saomafu"></text>
							<text class="action-name">扫码付</text>
						</button>
					</view>
				</view>
				<view class="qrocde-right">
					<text class="iconfont icon-zhifu"></text>
					<text class="name">门店支付</text>
				</view>
			</view>

			<view @touchmove.prevent.stop>
				<uni-popup ref="paymentPopup" type="center">
					<view class="payment-popup">
						<view class="head-wrap" @click="closePaymentPopup">
							<text>提示</text>
							<text class="iconfont icon-close"></text>
						</view>
						<view class="content-wrap">扫码付请退出程序后直接使用微信扫一扫或返回上一页使用付款码进行支付</view>
						<button type="primary" @click="closePaymentPopup">我知道了</button>
					</view>
				</uni-popup>
			</view>
		</view>
	</view>
</template>

<script>
// 付款码
export default {
	name: 'diy-payment-qrcode',
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
	created() {},
	computed: {
		warpCss() {
			var obj = '';
			return obj;
		}
	},
	watch: {
		// 组件刷新监听
		componentRefresh: function(nval) {}
	},
	methods: {
		toLink() {
			this.$util.redirectTo('/pages_tool/store/payment_qrcode');
		},
		scanCodeFn() {
			// #ifdef APP-PLUS
			this.toLink();
			// #endif
			// #ifndef H5
			// 允许从相机和相册扫码,h5端不起作用
			uni.scanCode({
				success: function(res) {
					console.log('条码类型：' + res.scanType);
					console.log('条码内容：' + res.result);
				}
			});
			// #endif
		},
		openPaymentPopup() {
			this.$refs.paymentPopup.open();
		},
		closePaymentPopup() {
			this.$refs.paymentPopup.close();
		}
	}
};
</script>

<style lang="scss">
.payment-qrocde-box {
	overflow: hidden;
	display: flex;
	background-color: #fff;
	border-radius: 16rpx;
	.qrocde-left {
		flex: 1;
		.qrocde-desc {
			margin-top: 20rpx;
			margin-bottom: 26rpx;
			display: flex;
			align-items: center;
			justify-content: center;
			color: $color-tip;
			font-size: $font-size-tag;
			.iconfont {
				margin-left: 10rpx;
				font-size: $font-size-tag;
				font-weight: bold;
			}
		}
		.qrocde-action {
			padding-bottom: 36rpx;
			display: flex;
			justify-content: center;
			button {
				display: flex;
				align-items: center;
				justify-content: center;
				margin: 0;
				width: 230rpx;
				height: 86rpx;
				border-radius: 50rpx;
				&:first-of-type {
					margin-right: 46rpx;
					background-color: $base-color;
				}
				&:last-of-type {
					background-color: #999;
				}
				.iconfont {
					margin-right: 10rpx;
				}
				.action-name {
					font-size: 30rpx;
				}
			}
		}
	}
	.qrocde-right {
		position: relative;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding-left: 16rpx;
		width: 90rpx;
		z-index: 2;
		box-sizing: border-box;
		.name {
			font-size: $font-size-sub;
			writing-mode: tb-rl;
			color: #fff;
			letter-spacing: 6rpx;
		}
		.iconfont {
			color: #fff;
		}
		&::after {
			content: '';
			position: absolute;
			top: 50%;
			left: 0;
			width: 500rpx;
			height: 500rpx;
			border-radius: 50%;
			background-color:  $base-color;
			transform: translateY(-50%);
			z-index: -1;
		}
	}
}
.payment-popup {
	padding: 0 30rpx 40rpx;
	background-color: #fff;

	.head-wrap {
		font-size: $font-size-toolbar;
		line-height: 100rpx;
		height: 100rpx;
		display: block;
		text-align: center;
		position: relative;
		border-bottom: 2rpx solid $color-line;
		margin-bottom: 20rpx;
		.iconfont {
			position: absolute;
			float: right;
			right: 0;
			font-size: $font-size-toolbar;
		}
	}
	.content-wrap {
		max-height: 600rpx;
		overflow-y: auto;
	}
	button {
		margin-top: 40rpx;
	}
}
</style>
