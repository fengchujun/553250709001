<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="cashier">
		<block v-if="payInfo">
			<block v-if="payInfo.pay_status == 0">
				<text class="content">{{ payInfo.pay_body }}</text>
				<view class="money-wrap">
					<text class="unit price-font">￥</text>
					<text class="money price-font">{{ payInfo.pay_money | moneyFormat }}</text>
				</view>
				
				<block v-if="payTypeList.length > 0">
					<view class="pay-type">
						<view class="payment-item" v-for="(item, index) in payTypeList" :key="index" @click="payIndex = index">
							<view>
								<text class="iconfont" :class="item.icon"></text>
								<text class="name">{{ item.name }}</text>
							</view>
							<text class="iconfont" :class="payIndex == index ? 'icon-yuan_checked color-base-text' : 'icon-checkboxblank'"></text>
						</view>
					</view>
					<button type="primary" @click="confirm">确认支付</button>
				</block>
				<view v-else class="empty">店铺尚未配置支付方式！</view>
			</block>
			<ns-empty text="该支付单据已支付" :is-index="true" v-else></ns-empty>
		</block>
		<ns-empty text="未获取到支付信息" :is-index="true" v-else></ns-empty>
		<ns-login ref="login"></ns-login>
	</view>
</template>

<script>
import { Weixin } from 'common/js/wx-jssdk.js';

export default {
	data() {
		return {
			payIndex: 0,
			// #ifdef H5
			payTypeList: [
				{
					name: '支付宝支付',
					icon: 'icon-zhifubaozhifu-',
					type: 'alipay'
				},
				{
					name: '微信支付',
					icon: 'icon-weixin1',
					type: 'wechatpay'
				}
			],
			timer: null,
			// #endif
			// #ifdef MP-WEIXIN
			payTypeList: [{
				name: '微信支付',
				provider: 'wxpay',
				icon: 'icon-weixin1',
				type: 'wechatpay'
			}],
			// #endif
			payInfo: null,
			outTradeNo: ''
		};
	},
	onLoad(option) {
		this.getPayType();
		this.outTradeNo = option.out_trade_no || '';
		this.getPayInfo();
	},
	methods: {
		getPayInfo() {
			this.$api.sendRequest({
				url: '/api/pay/info',
				data: {
					out_trade_no: this.outTradeNo
				},
				success: res => {
					if (res.code >= 0 && res.data) {
						this.payInfo = res.data;
						if (this.payInfo.pay_status == 0) {
							setTimeout(() => {
								this.autoPay();
							}, 500)
						}
					} 
				}
			});
		},
		getPayType() {
			this.$api.sendRequest({
				url: '/api/pay/type',
				success: res => {
					if (res.data.pay_type == '') {
						this.payTypeList = [];
					} else {
						this.payTypeList.forEach((val, key) => {
							if (res.data.pay_type.indexOf(val.type) == -1) {
								this.payTypeList.splice(key, 1);
							}
						});
					}
				}
			});
		},
		autoPay(){
			if (!this.payTypeList.length) return;
			if (this.$util.isWeiXin()) {
			 	this.payTypeList.forEach((item, index) => {
					if (item.type == 'wechatpay') {
						this.payIndex = index;
						this.confirm();
					}
				})
			} else if (/AlipayClient/.test(window.navigator.userAgent)) {
				this.payTypeList.forEach((item, index) => {
					if (item.type == 'alipay') {
						this.payIndex = index;
						this.confirm();
					}
				})
			}
		},
		confirm() {
			if (!this.storeToken) {
				this.$nextTick(() => {
					this.$refs.login.open('/pages_tool/pay/cashier?out_trade_no=' + this.outTradeNo);
				});
				return;
			}
			if (this.payTypeList.length == 0 && this.payInfo.pay_money > 0) {
				this.$util.showToast({
					title: '请选择支付方式！'
				});
				return;
			}
			uni.showLoading({
				title: '支付中...',
				mask: true
			});
			this.pay();
		},
		// #ifdef H5
		pay() {
			var payType = this.payTypeList[this.payIndex];
			if (!payType) return;

			let returnUrl = encodeURIComponent(this.$config.h5Domain + '/pages_tool/pay/result?code=' + this.payInfo.out_trade_no);

			this.$api.sendRequest({
				url: '/api/pay/pay',
				data: {
					out_trade_no: this.payInfo.out_trade_no,
					pay_type: payType.type,
					return_url: returnUrl
				},
				success: res => {
					uni.hideLoading();
					if (res.code >= 0) {
						switch (payType.type) {
							case 'alipay':
								if (this.$util.isWeiXin()) {
									var wx_alipay = encodeURIComponent(res.data.data);
									this.$util.redirectTo('/pages/pay/wx_pay/wx_pay', { wx_alipay: wx_alipay, out_trade_no: this.payInfo.out_trade_no }, 'redirectTo');
								} else {
									location.href = res.data.data;
									this.checkPayStatus();
								}
								break;
							case 'wechatpay':
								if (this.$util.isWeiXin()) {
									if (uni.getSystemInfoSync().platform == 'ios') {
										var url = uni.getStorageSync('initUrl');
									} else {
										var url = location.href;
									}
									// 获取jssdk配置
									this.$api.sendRequest({
										url: '/wechat/api/wechat/jssdkconfig',
										data: { url: url },
										success: jssdkRes => {
											var wxJS = new Weixin(),
												payData = res.data.data;
											wxJS.init(jssdkRes.data);
											wxJS.pay(
												{
													timestamp: payData.timestamp,
													nonceStr: payData.nonceStr,
													package: payData.package,
													signType: payData.signType,
													paySign: payData.paySign
												},
												res => {
													if (res.errMsg == 'chooseWXPay:ok') {
														if (!this.back) this.$util.redirectTo('/pages_tool/pay/result', { code: this.payInfo.out_trade_no }, 'redirectTo');
														else location.replace(this.back + '/pages_tool/pay/result?code=' + this.payInfo.out_trade_no);
													} else {
														this.$util.showToast({ title: res.errMsg });
													}
												}
											);
										}
									});
								} else {
									location.href = res.data.url;
									this.checkPayStatus();
								}
								break;
						}
					} else {
						this.$util.showToast({ title: res.message });
					}
				},
				fail: res => {
					uni.hideLoading();
					this.$util.showToast({ title: 'request:fail' });
				}
			});
		},
		checkPayStatus() {
			this.timer = setInterval(() => {
				this.$api.sendRequest({
					url: '/api/pay/status',
					data: { out_trade_no: this.payInfo.out_trade_no },
					success: res => {
						if (res.code == 0) {
							if (res.data.pay_status == 2) {
								clearInterval(this.timer);
								this.$util.redirectTo('/pages_tool/pay/result', { code: this.payInfo.out_trade_no }, 'redirectTo');
							}
						} else {
							clearInterval(this.timer);
						}
					}
				});
			}, 1000);
		},
		// #endif
		// #ifdef MP-WEIXIN
		pay() {
			var payType = this.payTypeList[this.payIndex];
			if (!payType) return;

			this.$api.sendRequest({
				url: '/api/pay/pay',
				data: {
					out_trade_no: this.payInfo.out_trade_no,
					pay_type: payType.type
				},
				success: res => {
					uni.hideLoading();
					if (res.code >= 0) {
						var payData = res.data.data;
						uni.requestPayment({
							provider: payType.provider,
							timeStamp: payData.timeStamp,
							nonceStr: payData.nonceStr,
							package: payData.package,
							signType: payData.signType,
							paySign: payData.paySign,
							success: res => {
								this.$util.redirectTo('/pages_tool/pay/result', { code: this.payInfo.out_trade_no }, 'redirectTo');
							},
							fail: res => {
								this.flag = false;
								if (res.errMsg == 'requestPayment:fail cancel') {
									this.$util.showToast({ title: '您已取消支付' });
								} else {
									uni.showModal({ content: '支付失败,失败原因: ' + res.errMsg, showCancel: false });
								}
							}
						});
					} else {
						this.$util.showToast({ title: res.message });
					}
				},
				fail: res => {
					uni.hideLoading();
					this.$util.showToast({ title: 'request:fail' });
				}
			});
		}
		// #endif
	},
	watch: {
		storeToken: function(nVal, oVal) {
			if (nVal) {
				this.getPayInfo();
			}
		}
	},
	filters: {
		/**
		 * 金额格式化输出
		 * @param {Object} money
		 */
		moneyFormat(money) {
			return parseFloat(money).toFixed(2);
		}
	}
};
</script>

<style lang="scss" scoped>
.cashier {
	display: flex;
	align-items: center;
	flex-direction: column;
	padding: 80rpx 26rpx;
	.content {
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		width: 100%;
		padding: 0 60rpx;
		text-align: center;
	}
	.money-wrap {
		font-weight: bold;
		margin: 40rpx 0;
		.unit {
			font-size: 40rpx;
			margin-right: 6rpx;
		}
		.money {
			font-size: 70rpx;
		}
	}

	.pay-type {
		width: 100%;
		background: #fff;
		border-radius: 20rpx;
		.payment-item {
			display: flex;
			align-items: center;
			justify-content: space-between;
			height: 90rpx;
			border-bottom: 2rpx solid $color-line;
			padding: 20rpx 30rpx;
			&:last-of-type {
				border-bottom: none;
			}
			> view {
				display: flex;
				align-items: center;
				.name {
					margin-left: 20rpx;
				}
			}
			.iconfont {
				font-size: 64rpx;
			}
			.icon-weixin1 {
				color: #24af41;
			}
			.icon-zhifubaozhifu- {
				color: #00a0e9;
			}
			.icon-yuan_checked {
				font-size: 40rpx;
				color: $base-color;
			}
			.icon-checkboxblank {
				font-size: 40rpx;
			}
		}
	}
	button {
		width: 100%;
		margin-top: 80rpx !important;
		background: $base-color;
		height: 90rpx;
		line-height: 90rpx;
		border-radius: 90rpx;
	}
}
</style>
