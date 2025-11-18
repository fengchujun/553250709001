<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="page">
		<view>
			<view class="recharge-title">充值</view>
			<view class="account-box">
				<view class="label">账户余额</view>
				<view class="value"><text class="price-font">{{balanceInfo.balance}}</text><text>元</text></view>
			</view>
			
			<view class="recharge-box">
				<view class="recharge-box-title">选择充值金额
					<view @click="toOrderList"><text class="color-base-text">充值记录</text></view>
				</view>
				<view class="box-content">
					
					<view v-if="list.length" class="content-item" v-for="(item, index) in list" :key="index" @click="itemClick(index, item.recharge_id, item.buy_price)" :class="isIndex == index ? 'active' : ''">
						<view class="price1 ">
							<text class="price-font">{{ parseFloat(item.face_value).toFixed(2) }}</text>
							<text>元</text>
						</view>
						<view class="price2">售价 {{ item.buy_price }} 元</view>
					</view>
					
					<view class="content-item" @click="openRecharge()">
						<view class="price1 ">
							<text class="other">其他金额</text>
						</view>
					</view>
					
				</view>
				
				<view class="box-text" v-if="isIndex !== -1">
					注：实际到账 {{ list[isIndex].face_value }} 元
					<template v-if="list[isIndex].point > 0 || list[isIndex].growth > 0 || list[isIndex].coupon_id">
						，赠送：
						<text v-if="list[isIndex].point > 0">{{ list[isIndex].point }} 积分，</text>
						<text v-if="list[isIndex].growth > 0">{{ list[isIndex].growth }} 成长值</text>
						<text v-if="list[isIndex].coupon_id != ''">，优惠券X{{ list[isIndex].coupon_id.split(',').length }}</text>
					</template>
				</view>
			</view>
 
		</view>
		<view class="explain">
			<view class="title">充值说明</view>
			<view class="explain_list">
				<view v-for="(item,index) in list" :key="index"  v-if="item.point || item.growth || item.coupon_id != ''">
					充值 {{ item.face_value }} 元赠送：
					<text v-if="item.point">{{ item.point }} 积分，</text>
					<text v-if="item.growth">{{ item.growth }} 成长值</text>
					<text v-if="item.coupon_id != ''">，优惠券X{{ item.coupon_id.split(',').length }}</text>
				</view>
				<view>
					充值任意金额后，会存到您的账户资金中
				</view>
			</view>
		</view>
		<uni-popup ref="rechargePopup" type="bottom">
			<view class="rechargeList">
				<view class="tip">请输入充值金额</view>
				<view class="input color-tip" v-if="keywordsInfo.price !== ''">
					{{keywordsInfo.price?keywordsInfo.price:'金额'}}<text class="color-base-text">元</text>
				</view>
				<view class="keywords">
					<view class="keywords-left">
						<view class="active" @click="keywordsDown(1)">1</view>
						<view class="active" @click="keywordsDown(2)">2</view>
						<view class="active" @click="keywordsDown(3)">3</view>
						<view class="active" @click="keywordsDown(4)">4</view>
						<view class="active" @click="keywordsDown(5)">5</view>
						<view class="active" @click="keywordsDown(6)">6</view>
						<view class="active" @click="keywordsDown(7)">7</view>
						<view class="active" @click="keywordsDown(8)">8</view>
						<view class="active" @click="keywordsDown(9)">9</view>
						<view></view>
						<view class="active" @click="keywordsDown(0)">0</view>
						<view class="active" @click="keywordsDown('.')">.</view>
					</view>
					<view class="keywords-right">
						<view @click="delPrice"><text class="iconfont icon-close"></text></view>
						<view class="color-base-bg" @click="keywordsPayment()">确认充值</view>
					</view>
				</view>
			</view>
		</uni-popup>
		<!-- 选择支付方式弹窗 -->
		<ns-payment ref="choosePaymentPopup" :payMoney="payMoney" @confirm="toPay"></ns-payment>
		<button class="add-account" type="primary" :disabled="recharge_id > 0 ? false : true" @click="openChoosePayment">充值</button>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				list: [],
				balanceInfo: {
					balance: 0,
					balance_money: 0
				},
				isIndex: -1,
				recharge_id: '',
				amount: '',
				payMoney: 0,
				keywordsInfo: {
					price: 0,
					minPrice: 1,
					maxPrice: 30
				}
			};
		},
		onShow() {
			this.getMemberrechargeConfig();
			this.getUserInfo();
			this.getData();
		},
		methods: {
			/**
			 * 获取充值提现配置
			 */
			getMemberrechargeConfig() {
				this.$api.sendRequest({
					url: '/memberrecharge/api/memberrecharge/config',
					success: res => {
						if (res.code >= 0 && res.data) {
							let memberrechargeConfig = res.data;
							if(!(this.addonIsExist.memberrecharge && memberrechargeConfig && memberrechargeConfig.is_use)){
								this.$util.showToast({
									title: "充值服务未开启"
								})
								setTimeout(() => {
									uni.navigateBack({
										delta: 1
									});
								}, 1000);
							}
						}
					}
				});
			},
			openRecharge() {
				this.isIndex = -1;
				this.payMoney = 0;
				this.keywordsInfo.price = '';
				this.recharge_id = '';
				this.$refs.rechargePopup.open()
			},
			toOrderList() {
				this.$util.redirectTo('/pages_tool/recharge/order_list');
			},
			//点击每一个
			itemClick(index, id, buy_price) {
				if (this.amount) this.amount = '';
				this.isIndex = index;
				this.recharge_id = id;
				this.payMoney = parseFloat(buy_price);
			},
			//键盘点击
			keywordsDown(val) {
				this.keywordsInfo.price = this.keywordsInfo.price.toString();
				val = val.toString();
				//已经有两位小数不可以继续输入
				var arr = this.keywordsInfo.price.split('.');
				if(arr.length == 2 && arr[1].length == 2) return;
				//已经有小数点不能继续输入
				if(val == '.' && this.keywordsInfo.price.indexOf('.') > -1) return;
				//不能一直输入0
				if(this.keywordsInfo.price === '0' && val === '0') return;
				this.keywordsInfo.price += val;
				this.payMoney = this.keywordsInfo.price;
			},
			delPrice() {
				var temp = this.keywordsInfo.price.toString()
				if (temp.length) {
					this.keywordsInfo.price = temp.slice(0, temp.length - 1)
					if (this.keywordsInfo.price.length > 0) {
						this.payMoney = this.keywordsInfo.price;
					} else {
						this.payMoney = '';
					}
				}
			},
			keywordsPayment() {
				if (this.keywordsInfo.price > 0) {
					this.amount = this.payMoney;
					this.$refs.rechargePopup.close()
					this.openChoosePayment()
				} else {
					this.$util.showToast({
						title: "请输入充值金额"
					})
				}

			},
			//输入框聚焦
			cumberFocus() {
				this.isIndex = -1;
			},
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
					}
				});
			},
			getData() {
				this.$api.sendRequest({
					url: '/memberrecharge/api/memberrecharge/page',
					data: {
						page_size: 100,
						page: 1
					},
					success: res => {
						let newArr = [];
						let msg = res.message;
						if (res.code == 0 && res.data) {
							newArr = res.data.list;
						} else {
							this.$util.showToast({
								title: msg
							});
						}
						//设置列表数据
						this.list = newArr; //追加新数据
						
						if(this.list.length > 0){
							this.isIndex = 0;
							this.recharge_id = this.list[0]['recharge_id'];
							this.payMoney = parseFloat(this.list[0]['buy_price']);
						}
						
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail: () => {
						//联网失败的回调
						mescroll.endErr();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			toPay() {
				if (this.recharge_id !== '') {
					this.$api.sendRequest({
						url: '/memberrecharge/api/ordercreate/create',
						data: {
							recharge_id: this.recharge_id
						},
						success: res => {
							if (res.data && res.code == 0) {
								this.$refs.choosePaymentPopup.getPayInfo(res.data);
							} else {
								this.$util.showToast({
									title: res.message
								});
							}
						}
					});
				} else if (this.amount !== '') {
					this.$api.sendRequest({
						url: '/memberrecharge/api/ordercreate/create',
						data: {
							recharge_id: 0,
							face_value: this.amount
						},
						success: res => {
							if (res.data && res.code == 0) {
								this.$refs.choosePaymentPopup.getPayInfo(res.data);
							} else {
								this.$util.showToast({
									title: res.message
								});
							}
						}
					});
				} else {
					this.$util.showToast({
						title: '请选择套餐'
					});
				}
			},
			imageError(index) {
				this.list[index].cover_img = this.$util.getDefaultImage().goods;
				this.$forceUpdate();
			},
			// 显示选择支付方式弹框
			openChoosePayment() {
				uni.setStorageSync('paySource', 'recharge');
				if (this.amount !== '') this.payMoney = parseFloat(this.amount);
				this.$refs.choosePaymentPopup.open();
			}
		}
	};
</script>

<style lang="scss">
	.page{
		min-height: 100vh;
		width: 100%;
		background-color: #FFFFFF;
		padding: 32rpx;
		box-sizing: border-box;
	}
	/deep/ .mescroll-uni-fixed {
		bottom: 280rpx !important;
	}
	.recharge-title{
		font-size: 60rpx;
		font-weight: bold;
	}
	.account-box{
		margin-top: 30rpx;
		display: flex;
		justify-content: space-between;
		align-items: baseline;
		border-bottom: 2rpx solid #F6F6F6;
		padding-bottom: 20rpx;
		.label{
			font-size:30rpx;
			font-weight:bold;
		}
		.value{
			
			text{
				font-size: 28rpx;
				font-weight: bold;
			}
			.price-font{
				font-size: 60rpx;
				font-weight: bold;
			}
			
		}
	}
	.explain {
		margin-top: 80rpx;
		padding: $padding 0;
		background-color: #fff;
		border-radius: $border-radius;

		.title {
			font-size: 32rpx;
			font-weight: bold;
		}

		.explain_list {

			view {
				font-size: $font-size-sub;
				color: $color-tip;
			}
		}
	}

	.rechargeList {
		.tip {
			padding: $margin-both;
			text-align: center;
			font-size: $font-size-toolbar;
		}

		.input {
			text-align: center;

			text {
				margin-left: 10rpx;
			}

			margin-bottom: $margin-updown;
		}

		.keywords {
			display: flex;
			border-top: 1px solid $color-line;
			margin-top: $margin-updown;

			.keywords-left {
				flex: 1;
				display: flex;
				flex-wrap: wrap;

				>view {
					width: calc((100% - 3px) / 3);
					text-align: center;
					height: 112rpx;
					line-height: 112rpx;
					border-right: 1px solid $color-line;
					border-bottom: 1px solid $color-line;
					font-size: 40rpx;

					&.active:active {
						background-color: rgba($color: #000000, $alpha: 0.5);
					}
				}
			}

			.keywords-right {
				display: flex;
				flex-wrap: wrap;
				flex-direction: column;
				width: 200rpx;

				text-align: center;

				>view {
					flex: 1;
					line-height: 228rpx;

					&:last-child {
						color: #fff
					}
				}
			}
		}
	}

	.recharge-price {
		width: calc(100% - 60rpx);
		background-color: #ffffff;
		margin: 20rpx 30rpx 0;
		border-radius: 10rpx;
		padding: 30rpx 30rpx 25rpx;
		box-sizing: border-box;

		.recharge-price-title {
			font-size: $font-size-base;
			color: $color-title;
			line-height: 1;
		}

		.recharge-price-custom {
			border-bottom: 1px solid #dddddd;
			padding-bottom: 20rpx;
			display: flex;
			align-items: center;
			margin-top: 45rpx;

			text {
				font-size: 54rpx;
				color: $color-title;
				line-height: 1;
			}

			input {
				font-size: 54rpx;
				line-height: 1;
			}
		}

		.recharge-price-desc {
			display: flex;
			margin-top: 16rpx;
			align-items: center;

			image {
				width: 34rpx;
				height: 34rpx;
				margin-right: 13rpx;
			}

			text {
				font-size: $font-size-sub;
				color: $color-tip;
				line-height: 1;
			}
		}
	}

	.recharge-box {
		
		padding: 36rpx 0;
		background: #fff;
		height: 100%;
		box-sizing: border-box;
		border-radius: 10rpx;

		.recharge-box-title {
			font-size: $font-size-base;
			color: #888888;
			line-height: 1;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}

		.box-title {
			text-align: center;
			font-size: $font-size-toolbar;
		}

		.box-custom {
			width: 226rpx;
			border-bottom: 1px solid #dddddd;
			margin: 0 auto;
			margin-top: 49rpx;
			line-height: 1;
			padding-bottom: 10rpx;
			box-sizing: border-box;

			.pla-number {
				font-size: 30rpx;
			}

			input {
				height: 97rpx;
				width: 100%;
				font-size: 62rpx;
				color: #000;
				text-align: center;
				line-height: 1;
			}
		}

		.box-content {
			width: 100%;
			box-sizing: border-box;
			display: flex;
			flex-wrap: wrap;
			max-height: 50vh;
			overflow-y: scroll;

			.content-item {
				width: calc((100% - 48rpx) / 3);
				margin-right: 24rpx;
				height: 142rpx;
				margin-top: 25rpx;
				border-radius: 16rpx;
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				color: $color-title;
				box-sizing: border-box;
				text-align: center;
				background: #F7F7FB;
				
				&:nth-child(3n) {
					margin-right: 0;
				}

				.price1 {
					font-size: $font-size-base;
					display: flex;
					align-items: flex-end;
					color: #333333;
					text:first-child {
						font-size: 40rpx;
						line-height: 1;
					}

					text:nth-child(2) {
						font-size: $font-size-base;
						line-height: 1;
					}
					.other{
						font-size: 32rpx!important;
						color: #666666;
					}
				}

				.price2 {
					font-size: $font-size-tag;
					color: #666666;
					line-height: 1;
					margin-top: 16rpx;
				}

				&.color-base-bg {

					.price1,
					.price2 {
						color: #ffffff !important;
					}
				}
				&.active{
					background: linear-gradient(136deg, var(--promotion-aux-color) 0%, var(--promotion-color) 100%);
					.price1,
					.price2 {
						color: #ffffff !important;
					}
				}
			}
			
		}

		.box-text {
			margin-top: 40rpx;
			font-size: $font-size-sub;
			color: var(--main-color);
		}
	}
	.add-account {
		margin: 0;
		margin-top: 5vh;
		height: 90rpx;
		line-height: 90rpx;
		width: 100%;
		border-radius: 90rpx;
	}
</style>
