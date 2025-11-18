<template>
	<view>
		<view class="error-msg">{{errorMsg}}</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				outTradeNo:'',
				errorMsg:'',
			}
		},
		onLoad(option) {
			if (option.merchant_trade_no){
				this.outTradeNo = option.merchant_trade_no;
				this.getOrderDetailPath();
			}else{
				this.errorMsg = '缺少merchant_trade_no参数';
			}
		},
		methods: {
			getOrderDetailPath(){
				this.$api.sendRequest({
					url: '/api/pay/outTradeNoToOrderDetailPath',
					data:{
						out_trade_no : this.outTradeNo,
					},
					success: res => {
						if (res.code < 0) {
							this.errorMsg = res.message || '未知错误';
						}else{
							this.$util.redirectTo(res.data);
						}
					}
				});
			},
		},
	}
</script>

<style>
	.error-msg{
		text-align: center;
		padding-top: 10vh;
	}
</style>
