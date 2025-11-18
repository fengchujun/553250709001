<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<view class="money-wrap">
			<text>{{ detail.apply_money }}</text>
		</view>

		<!-- 状态0待审核1.待转账2已转账 -1拒绝' -->
		<view class="item">
			<view class="line-wrap">
				<text class="label">当前状态</text>
				<text class="value">{{ detail.status_name }}</text>
			</view>
			<view class="line-wrap">
				<text class="label">交易号</text>
				<text class="value">{{ detail.withdraw_no }}</text>
			</view>
			<view class="line-wrap">
				<text class="label">手续费</text>
				<text class="value">￥{{ detail.service_money }}</text>
			</view>
			<view class="line-wrap">
				<text class="label">申请时间</text>
				<text class="value">{{ $util.timeStampTurnTime(detail.apply_time) }}</text>
			</view>
			<view class="line-wrap" v-if="detail.status">
				<text class="label">审核时间</text>
				<text class="value">{{ $util.timeStampTurnTime(detail.audit_time) }}</text>
			</view>
			<view class="line-wrap" v-if="detail.bank_name">
				<text class="label">银行名称</text>
				<text class="value">{{ detail.bank_name }}</text>
			</view>
			<view class="line-wrap">
				<text class="label">收款账号</text>
				<text class="value">{{ detail.account_number }}</text>
			</view>
			<view class="line-wrap" v-if="detail.status == -1 && detail.refuse_reason">
				<text class="label">拒绝理由</text>
				<text class="value">{{ detail.refuse_reason }}</text>
			</view>
			<view class="line-wrap" v-if="detail.status == 2">
				<text class="label">转账方式名称</text>
				<text class="value">{{ detail.transfer_type_name }}</text>
			</view>
			<view class="line-wrap" v-if="detail.status == 2">
				<text class="label">转账时间</text>
				<text class="value">{{ $util.timeStampTurnTime(detail.payment_time) }}</text>
			</view>
		</view>
		<!-- #ifdef H5 -->
			<view class="operations" v-if="$util.isWeiXin() && withdrawInfo.transfer_type && detail.transfer_type == 'wechatpay' && detail.status == 1">
				<button class="operation" type="primary" @click="merchantTransfer()">收款</button>
			</view>
		<!-- #endif -->
		<!-- #ifdef MP-WEIXIN -->
			<view class="operations" v-if="withdrawInfo.transfer_type && detail.transfer_type == 'wechatpay' && detail.status == 1">
				<button class="operation" type="primary" @click="merchantTransfer()">收款</button>
			</view>
		<!-- #endif -->
		
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>

export default {
	data() {
		return {
			id: 0,
			detail: {},
			withdrawInfo: {},
			requestCount: 0
		};
	},
	async onLoad(option) {
		this.id = option.id || 0;
		await this.getWithdrawConfig()
		if(option.action) this.merchantTransfer();
	},
	onShow() {
		if (this.storeToken) {
			this.getDetail();
		} else {
			this.$util.redirectTo('/pages_tool/login/index', {
				back: '/pages_tool/member/point'
			}, 'redirectTo');
		}
	},
	onPullDownRefresh() {
		this.getDetail();
	},
	methods: {
		merchantTransfer() {
			uni.showLoading({})
			var app_id = ''
			// #ifdef MP
				app_id = this.withdrawInfo.weapp_appid;
			// #endif
			// #ifdef H5
				if(this.$util.isWeiXin()){
					app_id = this.withdrawInfo.wechat_appid;
				}
			// #endif
			this.$util.merchantTransfer(
				{
					transfer_type: 'member_withdraw',
					id: this.id,
				},
				{
					mch_id: this.withdrawInfo.mch_id,
					app_id: app_id,
				},
				(res)=>{
					if (res.err_msg === 'requestMerchantTransfer:ok') {
						this.updateStatusToInProcess(()=>{
							this.getDetail(true);
						});
					}
					// #ifdef MP
					if (res.errMsg === 'requestMerchantTransfer:ok') {
						this.updateStatusToInProcess(()=>{
							this.getDetail(true);
						});
					}
					// #endif
				}
			);
		},
		async getWithdrawConfig() {
			let res = await this.$api.sendRequest({
				url: '/wechatpay/api/transfer/getWithdrawConfig',
				async: false,
			});
			if (res.code == 0){
				this.withdrawInfo = res.data;
			}
		},
		//修改收款状态为收款中
		updateStatusToInProcess(callback){
			if (this.$refs.loadingCover) this.$refs.loadingCover.show();
			this.$api.sendRequest({
				url: '/wechatpay/api/transfer/inprocess',
				data: {
					from_type: 'member_withdraw',
					relate_tag : this.id,
				},
				success: (res)=>{
					if(res.code >= 0){
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
						typeof callback == 'function' && callback();
					}
				}
			});
		},
		getDetail(is_loop = false) {
			this.$api.sendRequest({
				url: '/api/memberwithdraw/detail',
				data: {
					id: this.id
				},
				success: res => {
					if (res.data) {
						this.detail = res.data;
						if(is_loop && this.requestCount < 10 && this.detail.status == 3){
							this.requestCount++;
							setTimeout(()=>{
								this.getDetail(true)
							},1000)
						}
						// if(mode && this.detail.status == 1){
						// 	// 提现状态还没有修改
						// 	if(this.requestCount < 10 && this.detail.status == 1){
						// 		this.requestCount++;
						// 		setTimeout(()=>{
						// 			this.getDetail('transferComplete')
						// 		},1000)
						// 	}
						// }
						uni.stopPullDownRefresh();
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				},
				fail: res => {
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		}
	}
};
</script>

<style lang="scss">
.money-wrap {
	text-align: center;
	font-size: 50rpx;
	font-weight: bold;
	margin: 40rpx;
	border-bottom: 2rpx solid $color-line;
	padding: 40rpx;
}

.item {
	margin: 40rpx;

	.line-wrap {
		margin-bottom: 20rpx;

		.label {
			display: inline-block;
			width: 200rpx;
			color: $color-tip;
			font-size: $font-size-base;
		}

		.value {
			display: inline-block;
			font-size: $font-size-base;
		}
	}
}

.operations {
	margin-top: 60rpx;
	bottom: 0;
	width: 100%;
	// background: #fff;
	position: fixed;
	padding: 0 30rpx;
	box-sizing: border-box;
	padding-bottom: constant(safe-area-inset-bottom);
	padding-bottom: env(safe-area-inset-bottom);
	z-index: 10;

	.operation {
		height: 80rpx;
		line-height: 80rpx;
		border-radius: 80rpx;
		margin: 30rpx 0 30rpx;
		font-size: $font-size-toolbar;

		text {
			margin-right: 10rpx;
			font-size: $font-size-base;
		}
	}
}
</style>
