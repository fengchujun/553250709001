<template>
	<view>
		<view class="status">
			<image v-if="status == 0" :src="$util.img('/public/uniapp/store/withdraw/withdraw_process.png')" mode="widthFix" class="img"></image>
			<image v-else-if="status == 1" :src="$util.img('/public/uniapp/store/withdraw/withdraw_success.png')" mode="widthFix" class="img"></image>
			<image v-else-if="status == 2" :src="$util.img('/public/uniapp/store/withdraw/withdraw_fail.png')" mode="widthFix" class="img"></image>
			<image v-else-if="status == 3" :src="$util.img('/public/uniapp/store/withdraw/withdraw_cancel.png')" mode="widthFix" class="img"></image>
			{{ showStatus() }}
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				status:0,
				id: 0,
				withdrawInfo: {},
			}
		},
		async onLoad(option) {
			this.id = option.id || 0;
			await this.getWithdrawConfig()
			if(option.id) this.merchantTransfer();
		},
		methods: {
			showStatus() {
				switch (this.status){
					case 0:
						return '提现中';
						break;
					case 1:
						return '提现成功';
						break;
					case 2:
						return '提现失败';
						break;
					case 3:
						return '您已取消，请重新扫码';
						break;
					default:
						break;
				}
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
						transfer_type: 'store_withdraw',
						id: this.id,
					},
					{
						mch_id: this.withdrawInfo.mch_id,
						app_id: app_id,
					},
					(res)=>{
						// 收款结束后
						if (res.err_msg === 'requestMerchantTransfer:ok') {
							this.status = 1;
						}else if(res.err_msg === 'requestMerchantTransfer:fail'){
							this.status = 2;
						}else{
							this.status = 3;
						}
					}
				);
			},
		},
	}
</script>

<style lang="scss" scoped>
	.status{
		display: flex;
		align-items: center;
		flex-direction: column;
		padding-top: 20vh;
		font-size: 30rpx;
		.img{
			width: 30vw;
			display: block;
			margin-bottom: 30rpx;
		}
	}
</style>