<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="container">
		<view class="bank-account-wrap" @click="goAccount()">
			<view class="tx-wrap" v-if="bankAccountInfo.withdraw_type && !isBalance">
				<text class="tx-to">提现到</text>
				<view class="tx-bank" v-if="bankAccountInfo.withdraw_type == 'wechatpay'">微信默认钱包</view>
				<view class="tx-bank" v-else>{{ bankAccountInfo.bank_account }} </view>
				<view class="tx-img" v-if="bankAccountInfo.withdraw_type == 'alipay'">
					<image :src="$util.img('public/uniapp/member/apply_withdrawal/alipay.png')" mode="widthFix"/>
				</view>
				<view class="tx-img" v-else-if="bankAccountInfo.withdraw_type == 'bank'">
					<image :src="$util.img('public/uniapp/member/apply_withdrawal/bank.png')" mode="widthFix"/>
				</view>
				<view class="tx-img" v-else-if="bankAccountInfo.withdraw_type == 'wechatpay'">
					<image :src="$util.img('public/uniapp/member/apply_withdrawal/wechatpay.png')" mode="widthFix"/>
				</view>
			</view>
			<text class="tx-to" v-else-if="!isBalance">请添加提现账户</text>
			<view class="tx-wrap" v-else="isBalance">
				<text class="tx-to">提现到</text>
				<view class="tx-bank">余额</view>
				<view class="tx-img">
					<image :src="$util.img('public/uniapp/member/apply_withdrawal/tixian.png')" mode="widthFix"></image>
				</view>
			</view>
			<view class="iconfont icon-right"></view>
		</view>
		<view class="empty-box"></view>
		<view class="withdraw-wrap">
			<view class="withdraw-wrap-title">提现金额：</view>
			<view class="money-wrap">
				<text class="unit">{{ $lang('common.currencySymbol') }}</text>
				<input type="digit" class="withdraw-money" v-model="withdrawMoney" />
				<view class="delete" @click="remove" v-if="withdrawMoney">
					<image :src="$util.img('public/uniapp/member/apply_withdrawal/close.png')" mode="widthFix"></image>
				</view>
			</view>
			<view class="bottom">
				<view>
					<text class="color-tip">可提现佣金：{{ $lang('common.currencySymbol') }}{{ withdrawInfo.account | moneyFormat }}</text>
					<text class="all-tx color-base-text" @click="allTx">全部提现</text>
				</view>
			</view>
			<view class="desc">
				<text v-if="withdrawConfigInfo.withdraw > 0">最小提现金额为{{ $lang('common.currencySymbol') }}{{ withdrawConfigInfo.withdraw | moneyFormat }}，</text>
				<text v-if="withdrawConfigInfo.max > 0">最大提现金额为{{ $lang('common.currencySymbol') }}{{ (withdrawConfigInfo.max > withdrawInfo.account ? withdrawInfo.account : withdrawConfigInfo.max) | moneyFormat }}，</text>
				<text>手续费为{{ withdrawConfigInfo.withdraw_rate + '%' }}</text>
			</view>
		</view>

		<view class="btn color-base-border ns-gradient-otherpages-member-widthdrawal-withdrawal" :class="{ disabled: withdrawMoney == '' || withdrawMoney == 0 }" @click="withdraw">
			提现
		</view>

		<view class="recoend" @click="toWithdrawal">
			<view class="recoend-con">提现记录</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				withdrawInfo: {},
				withdrawConfigInfo: {},
				bankAccountInfo: {},
				withdrawMoney: '',
				isSub: false,
				isBalance: 0,
				payList: null
			};
		},
		onLoad(option) {
			if (option.is_balance) this.isBalance = option.is_balance;
		},
		onShow() {
			if (this.storeToken) {
				this.getTransferType();
				this.getWithdrawConfigInfo();
				this.getBankAccountInfo();
				this.getWithdrawInfo();
			} else {
				this.$util.redirectTo('/pages_tool/login/index', {
					back: '/pages_promotion/fenxiao/withdraw_apply'
				});
			}
		},
		methods: {
			toWithdrawal() {
				this.$util.redirectTo('/pages_promotion/fenxiao/withdraw_list');
			},
			//全部提现
			allTx() {
				this.withdrawMoney = this.withdrawInfo.account;
			},
			// 删除提现金额
			remove() {
				this.withdrawMoney = '';
			},
			/**
			 * 获取提现信息
			 */
			getWithdrawInfo() {
				this.$api.sendRequest({
					url: '/fenxiao/api/fenxiao/detail',
					success: res => {
						if (res.code >= 0 && res.data) {
							this.withdrawInfo = res.data;
						}
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail: res => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},

			/**
			 * 获取提现配置信息
			 */
			getWithdrawConfigInfo() {
				this.$api.sendRequest({
					url: '/fenxiao/api/config/withdraw',
					success: res => {
						if (res.code >= 0 && res.data) {
							this.withdrawConfigInfo = res.data;
						}
					},
					fail: res => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			/**
			 * 银行账号信息
			 */
			getBankAccountInfo() {
				this.$api.sendRequest({
					url: '/api/memberbankaccount/defaultinfo',
					success: res => {

						if (res.code >= 0) {
							if (res.data)
								this.bankAccountInfo = res.data;
							else if (this.payList && this.payList.balance) {
								// 如果信息为空，将默认为余额
								this.isBalance = 1;
							}
						}
					}
				});
			},
			// 获取提现方式
			getTransferType() {
				this.payList = null;
				this.$api.sendRequest({
					url: "/fenxiao/api/withdraw/transferType",
					success: res => {
						if (res.code >= 0 && res.data) {
							this.payList = res.data;
						}
					}
				});
			},
			verify() {
				if (this.withdrawMoney == '' || this.withdrawMoney < 0 || isNaN(parseFloat(this.withdrawMoney))) {
					this.$util.showToast({
						title: '请输入提现金额'
					});
					return false;
				}

				let money = this.withdrawInfo.account;
				if (this.withdrawConfigInfo.max > 0 && this.withdrawInfo.account > this.withdrawConfigInfo.max) {
					money = this.withdrawConfigInfo.max;
				}
				if (parseFloat(this.withdrawMoney) > 0 && (parseFloat(this.withdrawMoney) > parseFloat(money))) {
					this.$util.showToast({
						title: '提现金额超出可提现金额'
					});
					return false;
				}
				if (parseFloat(this.withdrawMoney) > 0 && (parseFloat(this.withdrawMoney) < parseFloat(this.withdrawConfigInfo.withdraw))) {
					this.$util.showToast({
						title: '提现金额小于最低提现金额'
					});
					return false;
				}
				return true;
			},
			withdraw() {
				if (!this.bankAccountInfo.withdraw_type && !this.isBalance) {
					this.$util.showToast({
						title: '请先添加提现方式'
					});
					return;
				}
				if (this.verify()) {
					if (this.isSub) return;
					this.isSub = true;

					var applet_type = 0;
					if (this.bankAccountInfo.withdraw_type == 'wechatpay') {
						// #ifdef MP
						applet_type = 1;
						// #endif
					}

					let withdrawType = this.isBalance ? 'balance' : this.bankAccountInfo.withdraw_type;
					let branchBankName = this.isBalance ? "余额" : this.bankAccountInfo.branch_bank_name;
					// #ifdef MP-WEIXIN
					// this.subscribeMessage(() => {
					this.$api.sendRequest({
						url: '/fenxiao/api/withdraw/apply',
						data: {
							apply_money: this.withdrawMoney,
							transfer_type: withdrawType, //转账提现类型
							realname: this.bankAccountInfo.realname,
							mobile: this.bankAccountInfo.mobile,
							bank_name: branchBankName,
							account_number: this.bankAccountInfo.bank_account,
							applet_type: applet_type
						},
						success: res => {
							if (res.code >= 0) {
								this.$util.showToast({
									title: '提现申请成功'
								});
								setTimeout(() => {
									this.$util.redirectTo('/pages_promotion/fenxiao/withdraw_list', {},
										'redirectTo');
								}, 1500);
							} else {
								this.isSub = false;
								this.$util.showToast({
									title: res.message
								});
							}
						},
						fail: res => {
							this.isSub = false;
						}
					});
					// });
					// #endif

					// #ifndef MP-WEIXIN
					this.$api.sendRequest({
						url: '/fenxiao/api/withdraw/apply',
						data: {
							apply_money: this.withdrawMoney,
							transfer_type: withdrawType, //转账提现类型
							realname: this.bankAccountInfo.realname,
							mobile: this.bankAccountInfo.mobile,
							bank_name: branchBankName,
							account_number: this.bankAccountInfo.bank_account,
							applet_type: applet_type
						},
						success: res => {
							if (res.code >= 0) {
								this.$util.showToast({
									title: '提现申请成功'
								});
								setTimeout(() => {
									this.$util.redirectTo('/pages_promotion/fenxiao/withdraw_list', {},
										'redirectTo');
								}, 1500);
							} else {
								this.isSub = false;
								this.$util.showToast({
									title: res.message
								});
							}
						},
						fail: res => {
							this.isSub = false;
						}
					});
					// #endif
				}
			},
			goAccount() {
				this.$util.redirectTo(
					'/pages_tool/member/account', {
						back: '/pages_promotion/fenxiao/withdraw_apply',
						type: 'fenxiao'
					},
					'redirectTo'
				);
			},
			/**
			 * 微信订阅消息
			 */
			subscribeMessage(callback) {
				this.$util.subscribeMessage('USER_WITHDRAWAL_SUCCESS',callback);
			}
		},
	};
</script>

<style lang="scss">
	.container {
		width: 100vw;
		height: 100vh;
		background: #fff;
	}

	.empty-box {
		height: 20rpx;
	}

	.bank-account-wrap {
		margin: 0 20rpx;
		padding: 20rpx 30rpx;
		border-bottom: 2rpx solid #f7f7f7;
		position: relative;

		.tx-wrap {
			display: flex;
			justify-content: space-between;
			margin-right: 60rpx;

			.tx-bank {
				margin-right: 60rpx;
				flex: 1;
				margin-left: 10rpx;
				text-align: right;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}

			.tx-img {
				position: absolute;
				right: 100rpx;
				top: 50%;
				transform: translateY(-50%);
				width: 40rpx;
				height: 40rpx;

				image {
					width: 100%;
					height: 100%;
				}
			}
		}

		.iconfont {
			position: absolute;
			right: 40rpx;
			top: 50%;
			transform: translateY(-50%);
		}
	}

	.withdraw-wrap {
		margin: 0 20rpx;
		padding: 30rpx;
		border-radius: 16rpx;
		box-shadow: rgba(110, 110, 110, 0.09) 0 0 20rpx 0;

		.money-wrap {
			padding: 20rpx 0;
			border-bottom: 2rpx solid #eee;
			display: flex;
			align-items: baseline;

			.unit {
				font-size: 60rpx;
				line-height: 1.3;
			}

			.withdraw-money {
				height: 70rpx;
				line-height: 70rpx;
				min-height: 70rpx;
				padding-left: 20rpx;
				font-size: 60rpx;
				flex: 1;
				font-weight: bolder;
			}

			.delete {
				width: 40rpx;
				height: 40rpx;

				image {
					width: 100%;
					height: 100%;
				}
			}
		}

		.bottom {
			display: flex;
			padding-top: 20rpx;

			text {
				line-height: 1;
				flex: 2;
			}

			.all-tx {
				padding-left: 10rpx;
			}
		}
	}

	.btn {
		margin: 0 30rpx;
		margin-top: 60rpx;
		height: 80rpx;
		line-height: 80rpx;
		border-radius: $border-radius;
		color: #fff;
		text-align: center;
		background-color: var(--main-color);

		&.disabled {
			background: #ccc;
			border-color: #ccc;
			color: #fff;
		}
	}

	.recoend {
		margin-top: 40rpx;

		.recoend-con {
			text-align: center;
		}
	}

	.desc {
		font-size: $font-size-tag;
		color: #999;
	}
</style>