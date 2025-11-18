<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="container">
		<template v-if="payInfo.pay_status != undefined">
			<view class="result-box">
				<template v-if="payInfo.pay_status">
					<image :src="$util.img('public/uniapp/pay/pay_success.png')" mode="widthFix" lazy-load="true" class="result-image"/>
					<view class="msg success">{{ $lang('paymentSuccess') }}</view>
					<view class="pay-amount">
						<text class="unit price-style small">{{ $lang('common.currencySymbol') }}</text>
						<text class="price-style large">{{ parseFloat(payInfo.pay_money).toFixed(2).split(".")[0] }}</text>
						<text class="price-style small">.{{ parseFloat(payInfo.pay_money).toFixed(2).split(".")[1] }}</text>
					</view>
				</template>
				<template v-else>
					<image :src="$util.img('public/uniapp/pay/pay_fail.png')" mode="widthFix" class="result-image"/>
					<view class="msg fail">{{ $lang('paymentFail') }}</view>
				</template>

				<view class="consume-box" v-if="addonIsExist.memberconsume && consumeInfo.is_reward == 1 && payInfo.pay_status">
					<view class="consume-head">
						<view class="consume-head-text">恭喜您获得</view>
					</view>
					<view class="consume-list">
						<view class="consume-item" v-if="consumeInfo.point_num > 0" @click="toMemberPoint()">
							<image :src="$util.img('public/uniapp/pay/point.png')" mode="widthFix"></image>
							<view class="consume-value color-base-text">{{ consumeInfo.point_num }}</view>
							<view class="consume-type">积分</view>
						</view>
						<view class="consume-item" v-if="consumeInfo.growth_num > 0" @click="toMemberLevel()">
							<image :src="$util.img('public/uniapp/pay/growth.png')" mode="widthFix"></image>
							<view class="consume-value color-base-text">{{ consumeInfo.growth_num }}</view>
							<view class="consume-type">成长值</view>
						</view>
						<view class="consume-item" v-if="consumeInfo.coupon_list.length > 0" @click="toMemberCoupon()">
							<image :src="$util.img('public/uniapp/pay/coupon.png')" mode="widthFix"></image>
							<view class="consume-value color-base-text">{{ consumeInfo.coupon_list.length }}</view>
							<view class="consume-type">张优惠券</view>
						</view>
					</view>
				</view>

				<view class="action">
					<template v-if="storeToken">
						<view v-if="paySource == 'recharge'" class="btn" @click="toRecharge()">充值记录</view>
						<view v-else-if="paySource == 'membercard'" class="btn" @click="toCard()">会员卡</view>
						<view v-else-if="paySource == 'presale'" class="btn" @click="toPresaleOrder()">查看订单</view>
						<view v-else-if="paySource == 'giftcard'" class="btn" @click="toOrder()">查看订单</view>
						<view v-else-if="paySource == 'pointexchange'" class="btn" @click="toExchangeOrder()">查看订单
						</view>
						<view v-else class="btn" @click="toOrderDetail(payInfo.order_id)">查看订单</view>
					</template>
					<view class="btn go-home" @click="goHome()">{{ $lang('goHome') }}</view>
				</view>
			</view>

			<ns-goods-recommend route="pay"></ns-goods-recommend>
		</template>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				payInfo: {},
				outTradeNo: '',
				paySource: '',
				consumeInfo: {
					is_reward:0,
				},
				pollTimer: null,
				pollStartTime: null,
				maxPollDuration: 5000, // 最多轮询5秒
			};
		},
		onLoad(option) {
			if (option.code) this.outTradeNo = option.code;
			this.paySource = uni.getStorageSync('paySource');
		},
		onShow() {
			this.startPayStatusPolling();
			this.getConsume();
		},
		methods: {
			consume(type) {
				switch (type) {
					case 'point':
						this.$util.redirectTo('/pages_tool/member/point_detail', {});
						break;
					case 'growth':
						this.$util.redirectTo('/pages_tool/member/level', {});
						break;
					case 'coupon':
						this.$util.redirectTo('/pages_tool/member/coupon', {});
						break;
					default:
						this.$util.redirectTo('/pages/member/index', {}, 'reLaunch');
						break;
				}
			},
			getConsume() {
				this.$api.sendRequest({
					url: '/memberconsume/api/config/info',
					data: {
						out_trade_no: this.outTradeNo
					},
					success: res => {
						if (res.code >= 0) {
							this.consumeInfo = res.data;
						}
					},
					fail: res => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			startPayStatusPolling() {
				this.pollStartTime = Date.now();
				this.getPayInfo();
			},
			getPayInfo() {
				this.$api.sendRequest({
					url: '/api/pay/info',
					data: {
						out_trade_no: this.outTradeNo
					},
					success: res => {
						if (res.code >= 0 && res.data) {
							this.payInfo = res.data;
							this.payInfo.pay_money = parseFloat(res.data.pay_money);
							this.payInfo.pay_money += parseFloat(res.data.balance);
							this.payInfo.pay_money += parseFloat(res.data.balance_money);
							
							// 检查支付状态
							if (this.payInfo.pay_status) {
								// 支付成功，停止轮询
								this.clearPolling();
								if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
							} else {
								// 支付未成功，继续轮询
								this.continuePolling();
								
							}
						} else {
							this.$util.showToast({
								title: '未获取到支付信息！'
							});
							setTimeout(() => {
								this.$util.redirectTo('/pages/index/index', {}, 'reLaunch');
							}, 1500);
						}
					},
					fail: res => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
						this.continuePolling();
					}
				});
			},
			continuePolling() {
				const currentTime = Date.now();
				const elapsedTime = currentTime - this.pollStartTime;
				
				if (elapsedTime < this.maxPollDuration) {
					// 还在5秒内，继续轮询
					this.pollTimer = setTimeout(() => {
						this.getPayInfo();
					}, 1000); // 每1秒查询一次
				} else {
					// 超过5秒，停止轮询
					this.clearPolling();
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			},
			clearPolling() {
				if (this.pollTimer) {
					clearTimeout(this.pollTimer);
					this.pollTimer = null;
				}
			},
			goHome() {
				this.$util.redirectTo('/pages/index/index', {}, 'reLaunch');
			},
			toOrderDetail(id) {
				this.$util.redirectTo('/pages/order/detail', {
					order_id: id
				}, 'redirectTo');
			},
			toOrder(id) {
				this.$util.redirectTo('/pages_promotion/giftcard/order_list', {}, 'redirectTo');
				uni.setStorageSync('paySource', '');
			},
			toRecharge() {
				this.$util.redirectTo('/pages_tool/recharge/order_list', {}, 'redirectTo');
				uni.setStorageSync('paySource', '');
			},
			toCard() {
				this.$util.redirectTo('/pages_tool/member/card', {}, 'redirectTo');
				uni.setStorageSync('paySource', '');
			},
			toPresaleOrder() {
				this.$util.redirectTo('/pages_promotion/presale/order_list', {}, 'redirectTo');
				uni.setStorageSync('paySource', '');
			},
			toExchangeOrder() {
				this.$util.redirectTo('/pages_promotion/point/order_list', {}, 'redirectTo');
				uni.setStorageSync('paySource', '');
			},
			toMemberPoint() {
				this.$util.redirectTo('/pages_tool/member/point');
			},
			toMemberCoupon() {
				this.$util.redirectTo('/pages_tool/member/coupon');
			},
			toMemberLevel() {
				this.$util.redirectTo('/pages_tool/member/level');
			},
		},
		beforeDestroy() {
			this.clearPolling();
		}
	};
</script>

<style lang="scss">
	.consume-box {
		padding: $padding;
		background: #F8F8F8;
		width: calc(100% - 48rpx);
		margin: 0 24rpx 0 24rpx;
		box-sizing: border-box;
		border-radius: 20rpx;

		.consume-head {
			display: flex;
			justify-content: center;
			font-weight: 500;
			font-size: 26rpx;

			.consume-head-text {
				line-height: 1;
			}
		}

		.consume-list {
			display: flex;
		}

		.consume-item {
			flex: 1;
			display: flex;
			align-items: center;
			justify-content: center;
			color: $color-title;
			font-size: $font-size-base;
			margin-top: 10rpx;

			image {
				width: 24rpx;
				margin-right: 4rpx;
			}

			.consume-value {
				font-size: 26rpx;
			}
		}

		.consume-remark {
			color: $color-tip;
			font-size: $font-size-tag;
			padding: 10rpx 20rpx;
		}
	}

	.clear {
		clear: both;
	}

	.container {
		display: flex;
		flex-direction: column;
		align-items: center;

		.result-box {
			padding-top: 94rpx;
			display: flex;
			flex-direction: column;
			align-items: center;
			background-color: #FFFFFF;
			width: 100%;
			padding-bottom: 40rpx;
		}

		.result-image {
			width: 80rpx;
			height: auto;
			will-change: transform;
		}

		.msg {
			font-size: 32rpx;
			margin-top: 25rpx;

			&.success {
				color: #09BB07;
			}

			&.fail {
				color: #FF4646;
			}
		}

		.pay-amount {
			font-size: 30rpx;
			margin: 40rpx 0 24rpx 0;
			font-weight: 600;
			line-height: 50rpx;

			text {
				color: #333333 !important;
				font-weight: bold !important;
			}

			.unit {
				margin-right: 4rpx;
			}

			.large {
				font-size: 60rpx !important;
			}

			.small {
				font-size: 36rpx !important;
			}
		}

		.action {
			width: 100%;
			height: 80rpx;
			display: flex;
			justify-content: center;
			box-sizing: border-box;
			margin-top: 24rpx;

			.btn {
				font-size: 30rpx;
				width: 200rpx;
				height: 66rpx;
				line-height: 66rpx;
				text-align: center;
				border-radius: 66rpx;
				border: 1px solid $color-tip;
				box-sizing: border-box;

				&:last-child {
					margin-left: 40rpx;
				}
			}

			.go-home {
				background-color: $base-color;
				color: #fff;
				border-color: $base-color;
			}
		}
	}

	/deep/ .goods-recommend {
		margin-top: 30rpx;
	}
</style>

<style scoped>
	/deep/ .sku-layer .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
		max-height: unset !important;
	}
</style>