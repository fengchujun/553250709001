<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="balance">
		<!-- #ifdef MP-WEIXIN -->
		<view class="custom-navbar" :style="{ 'padding-top': menuButtonBounding.top + 'px', 'height': menuButtonBounding.height + 'px' }">
			<view class="navbar-wrap">
				<text class="iconfont icon-back_light back" @click="$util.redirectTo('/pages/member/index')"></text>
				<view class="navbar-title">
					我的余额
				</view>
			</view>
		</view>
		<!-- #endif -->
		
		<view class="head-wrap" :style="{ background: 'url(' + $util.img('public/uniapp/balance/balance_bg.png') + ') no-repeat right bottom/ auto 340rpx, linear-gradient(314deg, #FE7849 0%, #FF1959 100%)' }">
			<view class="balance price-font">{{ (parseFloat(balanceInfo.balance) + parseFloat(balanceInfo.balance_money)).toFixed(2) }}</view>
			<view class="title">账户余额（元）</view>
			<view class="flex-box">
				<view class="flex-item">
					<view class="num price-font">{{  balanceInfo.balance_money|moneyFormat }}</view>
					<view class="font-size-tag">现金余额（元）</view>
				</view>
				<view class="flex-item">
					<view class="num price-font">{{ balanceInfo.balance|moneyFormat }}</view>
					<view class="font-size-tag">储值余额（元）</view>
				</view>
			</view>
		</view>

		<view class="menu-wrap">
			<view class="menu-item" @click="toBalanceDetail">
				<view class="icon">
					<text class="iconfont icon-yuemingxi"></text>
				</view>
				<text class="title">余额明细</text>
				<text class="iconfont icon-right"></text>
			</view>
			<view class="menu-item" @click="toOrderList" v-if="addonIsExist.memberrecharge && memberrechargeConfig && memberrechargeConfig.is_use">
				<view class="icon">
					<text class="iconfont icon-chongzhijilu"></text>
				</view>
				<text class="title">充值记录</text>
				<text class="iconfont icon-right"></text>
			</view>
		</view>
		
		<view class="action">
			<view @click="toList" class="recharge-withdraw" v-if="addonIsExist.memberrecharge && memberrechargeConfig && memberrechargeConfig.is_use">
				{{ $lang('recharge') }}
			</view>
			<view class="withdraw" v-if="addonIsExist.memberwithdraw && withdrawConfig && withdrawConfig.is_use" @click="toWithdrawal">
				{{ $lang('withdrawal') }}
			</view>
		</view>
		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			balanceInfo: {
				balance: 0,
				balance_money: 0
			},
			withdrawConfig: null,
			memberrechargeConfig: null,
			menuButtonBounding: {} // 小程序胶囊属性
		};
	},
	async onShow() {
		this.getWithdrawConfig();
		this.getMemberrechargeConfig();
		if (!this.storeToken) {
			this.$nextTick(() => {
				this.$refs.login.open('/pages_tool/member/balance');
			});
		} else {
			this.getUserInfo();
		}
	},
	onLoad() {
		// #ifdef MP
		this.menuButtonBounding = uni.getMenuButtonBoundingClientRect();
		// #endif
	},
	methods: {
		toWithdrawal() {
			this.$util.redirectTo('/pages_tool/member/apply_withdrawal');
		},
		toOrderList() {
			this.$util.redirectTo('/pages_tool/recharge/order_list');
		},
		toBalanceDetail() {
			this.$util.redirectTo('/pages_tool/member/balance_detail');
		},
		toList() {
			this.$util.redirectTo('/pages_tool/recharge/list');
		},
		//获取余额信息
		getUserInfo() {
			this.$api.sendRequest({
				url: '/api/memberaccount/info',
				data: {
					account_type: 'balance,balance_money'
				},
				success: res => {
					if (res.data) {
						this.balanceInfo = res.data;
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				},
				fail: res => {
					mescroll.endErr();
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		/**
		 * 获取余额提现配置
		 */
		getWithdrawConfig() {
			this.$api.sendRequest({
				url: '/api/memberwithdraw/config',
				success: res => {
					if (res.code >= 0 && res.data) {
						this.withdrawConfig = res.data;
					}
				}
			});
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
		}
	},
	onBackPress(options) {
		if (options.from === 'navigateBack') {
			return false;
		}
		this.$util.redirectTo('/pages/member/index', {}, 'reLaunch');
		return true;
	},
	watch: {
		storeToken: function(nVal, oVal) {
			if (nVal) {
				this.getUserInfo();
			}
		}
	}
};
</script>

<style lang="scss">
@import './public/css/balance.scss';
</style>
