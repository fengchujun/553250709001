<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="container">
		<view v-if="payInfo && memberInfo">
			<view class="paycode-wrap">
				<view class="member-wrap">
					<view class="headimg" @click="getWxAuth">
						<image :src="memberInfo.headimg ? $util.img(memberInfo.headimg) : $util.getDefaultImage().head" mode="widthFix" @error="memberInfo.headimg = $util.getDefaultImage().head"/>
					</view>
					<view class="info-wrap">
						<view class="nickname">{{ memberInfo.nickname }}</view>
						<view class="member-level" v-if="memberInfo.member_level" @click="$util.redirectTo(memberInfo.member_level_type ? '/pages_tool/member/card' : '/pages_tool/member/level')">
							<image :src="$util.img('app/component/view/member_info/img/style_4_vip_tag.png')" mode="widthFix" class="level-icon"></image>
							<view class="level-name">{{ memberInfo.member_level_name }}</view>
						</view>
					</view>
					<view class="recharge" v-if="addonIsExist.memberrecharge && memberrechargeConfig && memberrechargeConfig.is_use" @click="$util.redirectTo('/pages_tool/recharge/list')">
						去充值
					</view>
				</view>
				<view class="body-wrap">
					<view class="barcode-wrap">
						<image :src="payInfo.barcode" class="barcode"></image>
					</view>
					<view class="auth-code">
						<text class="price-font">{{ show ? splitFn(payInfo.auth_code) : payInfo.auth_code.substring(0, 5) + '******' }}</text>
						<text class="show" v-if="!show" @click="showAuthCode(true)">查看数字</text>
						<text class="show" v-else @click="showAuthCode(false)">隐藏数字</text>
					</view>
					<image :src="payInfo.qrcode" mode="widthFix" class="qrcode"></image>
					<view class="dynamic-code" @click="getPayAuthCode">
						<view class="code">
							动态码
							<text>{{ payInfo.dynamic_code }}</text>
							<text class="iconfont icon-shuaxin"></text>
						</view>
					</view>
					<view class="tips">付款码仅用于支付时向收银员出示，请勿发送给他人</view>
				</view>
				<view class="footer-wrap">
					<view class="account-item" @click="$util.redirectTo('/pages_tool/member/point')">
						<view class="value price-font">{{ parseInt(memberInfo.point) }}</view>
						<view class="title">积分</view>
					</view>
					<view class="split"></view>
					<view class="account-item" @click="$util.redirectTo('/pages_tool/member/balance')">
						<view class="value price-font">
							{{ (parseFloat(memberInfo.balance) + parseFloat(memberInfo.balance_money)) | moneyFormat }}
						</view>
						<view class="title">余额</view>
					</view>
					<view class="split"></view>
					<view class="account-item" @click="$util.redirectTo('/pages_tool/member/coupon')">
						<view class="value price-font">{{ memberInfo.coupon_num ? memberInfo.coupon_num : 0 }}</view>
						<view class="title">优惠券</view>
					</view>
				</view>
			</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
		<ns-login ref="login"></ns-login>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				isRepeat: false,
				payInfo: null,
				error: 0,
				timer: null,
				show: false,
				memberrechargeConfig: null,
				screenBrightness: 0
			};
		},
		onShow() {
			uni.setStorageSync('paySource', '');
			if (this.storeToken) {
				this.getCouponNum();
				this.getMemberrechargeConfig();
				this.getPayAuthCode();

				// #ifndef H5
				uni.getScreenBrightness({
					success: res => {
						this.screenBrightness = res.value;
					}
				});

				uni.setScreenBrightness({
					value: 1,
					success: function() {}
				});
				// #endif

			} else {
				this.$nextTick(() => {
					this.$refs.login.open('/pages_tool/store/payment_qrcode');
				});
			}
		},
		onLoad() {},
		methods: {
			getPayAuthCode() {
				if (this.isRepeat) return;
				this.isRepeat = true;

				if (this.timer) clearInterval(this.timer);

				this.$api.sendRequest({
					url: '/api/pay/memberpaycode',
					success: res => {
						this.isRepeat = false;
						if (res.code == 0 && res.data) {
							this.payInfo = res.data;
							this.error = 0;
							this.show = false;
							// this.refreshPaymentCode();
							setTimeout(() => {
								if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
							}, 100);
						} else if (this.error < 5) {
							this.error++;
							this.getPayAuthCode();
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			},
			refreshPaymentCode() {
				this.timer = setInterval(() => {
					this.getPayAuthCode();
				}, 30000);
			},
			showAuthCode(bool) {
				this.show = bool;
			},
			/**
			 * 获取充值提现配置
			 */
			getMemberrechargeConfig() {
				this.$api.sendRequest({
					url: '/memberrecharge/api/memberrecharge/config',
					success: res => {
						if (res.code >= 0 && res.data) {
							this.memberrechargeConfig = res.data;
						}
					}
				});
			},
			/**
			 * 查询优惠券数量
			 */
			getCouponNum() {
				this.$api.sendRequest({
					url: '/coupon/api/coupon/num',
					success: res => {
						if (res.code == 0) {
							this.memberInfo.coupon_num = res.data;
							this.$forceUpdate();
							this.$store.commit('setMemberInfo', this.memberInfo);
						}
					}
				});
			},
			splitFn(str, length = 4) {
				let reg = new RegExp('[^\n]{1,' + length + '}', 'g');
				let res = str.match(reg);
				return res.join(' ');
			}
		},
		watch: {
			storeToken: function(nVal, oVal) {
				this.getPayAuthCode();
			}
		},
		onHide() {
			if (this.timer) clearInterval(this.timer);
			uni.setScreenBrightness({
				value: this.screenBrightness,
				success: function() {}
			});
		},
		onUnload() {
			if (this.timer) clearInterval(this.timer);

			uni.setScreenBrightness({
				value: this.screenBrightness,
				success: function() {}
			});
		}
	};
</script>

<style lang="scss">
	.container {
		width: 100vw;
		min-height: 100vh;
		background: $base-color;
		padding: 30rpx;
		box-sizing: border-box;
		overflow-y: auto;
	}

	.paycode-wrap {
		overflow: hidden;
		background: #fff;
		border-radius: 20rpx;

		.member-wrap {
			padding: 36rpx 32rpx;
			background: #f6f6f6;
			display: flex;
			align-items: center;

			.headimg {
				width: 88rpx;
				height: 88rpx;
				overflow: hidden;
				border-radius: 50%;
				margin-right: 20rpx;

				image {
					width: 88rpx;
					height: 88rpx;
				}
			}

			.info-wrap {
				flex: 1;
				width: 0;
			}

			.nickname {
				font-size: 30rpx;
				font-weight: 600;
				white-space: nowrap;
				overflow: hidden;
				line-height: 1;
			}

			.member-level {
				background: #474758;
				padding: 0;
				margin: 16rpx 0 0 0;
				height: 40rpx;
				border-radius: 40rpx;
				display: inline-flex;
				align-items: center;

				.level-icon {
					width: 40rpx;
					vertical-align: middle;
					margin-left: -2rpx;
				}

				.level-name {
					padding: 0 20rpx 0 6rpx;
					color: #ddc095;
					font-size: 24rpx;
					display: inline-block;
					line-height: 1;
				}
			}

			.recharge {
				color: $base-color;
				border: 2rpx solid $base-color;
				height: 64rpx;
				line-height: 64rpx;
				border-radius: 64rpx;
				font-size: 26rpx;
				padding: 0 30rpx;
				letter-spacing: 4rpx;
			}
		}

		.body-wrap {
			margin: 40rpx 40rpx 0 40rpx;
			width: calc(100% -80rpx);
			box-sizing: border-box;
			text-align: center;
			padding-bottom: 40rpx;
			position: relative;
			border-bottom: 2rpx dashed #dedede;

			.barcode-wrap {
				width: 590rpx;
				height: 200rpx;
				overflow: hidden;
				margin: 0 auto;

				.barcode {
					width: 590rpx;
					height: 250rpx;
				}
			}

			.qrcode {
				width: 320rpx;
				margin-top: 30rpx;
			}

			.tips {
				color: #999999;
				font-size: 24rpx;
				margin-top: 20rpx;
			}

			.dynamic-code {
				display: flex;
				align-items: center;
				justify-content: center;

				.code {
					background: #f6f6f6;
					color: #666;
					padding: 4rpx 26rpx;
					border-radius: 60rpx;

					text {
						margin-left: 10rpx;
					}
				}
			}

			.auth-code {
				color: #999999;
				font-size: 24rpx;
				margin-top: 20rpx;

				.price-font {
					letter-spacing: 2rpx;
				}

				.show {
					color: #163d8f;
					font-size: 26rpx;
					margin-left: 20rpx;
				}
			}

			&:after,
			&:before {
				content: ' ';
				width: 40rpx;
				height: 40rpx;
				background: $base-color;
				border-radius: 50%;
				z-index: 5;
				bottom: 0;
				display: block;
				position: absolute;
			}

			&:after {
				right: 0;
				transform: translate(calc(50% + 40rpx), 50%);
			}

			&:before {
				left: 0;
				transform: translate(calc(-50% - 40rpx), 50%);
			}
		}

		.footer-wrap {
			padding: 50rpx 0;
			display: flex;
			align-items: center;

			.split {
				width: 2rpx;
				background: #dddddd;
				height: 50rpx;
			}

			.account-item {
				flex: 1;
				text-align: center;

				.value {
					font-size: 32rpx;
					color: $base-color;
					line-height: 1.5;
				}

				.title {
					color: #999999;
					font-size: 24rpx;
					margin-top: 10rpx;
				}
			}
		}
	}
</style>