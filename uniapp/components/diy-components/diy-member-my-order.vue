<template>
	<view :style="value.pageStyle">
		<view class="common-wrap" :style="warpCss">
			<view class="order-wrap">
				<view class="status-wrap">
					<view class="item-wrap" @click="redirect('/pages/order/list?status=waitpay')">
						<view class="icon-block">
							<template v-if="value.style == 3">
								<image :src="$util.img('public/uniapp/member/order/wait_pay.png')" mode="widthFix"/>
								<view class="icon-shade" :style="'-webkit-mask-image: url(' + $util.img('public/uniapp/member/order/wait_pay_shade.png') + ');-webkit-mask-box-image: url(' + $util.img('public/uniapp/member/order/wait_pay_shade.png') + ');'"></view>
							</template>
							<template v-else>
								<diy-icon :icon="value.icon.waitPay.icon" v-if="value.icon.waitPay" :value="value.icon.waitPay.style ? value.icon.waitPay.style : null"></diy-icon>
							</template>
							<text v-if="orderNum.waitpay > 0" class="order-num color-base-bg price-font">{{ orderNum.waitpay > 99 ? '99+' : orderNum.waitpay }}</text>
						</view>
						<view class="title">{{ value.icon.waitPay.title }}</view>
					</view>
					<view class="item-wrap" @click="redirect('/pages/order/list?status=waitsend')">
						<view class="icon-block">
							<template v-if="value.style == 3">
								<image :src="$util.img('public/uniapp/member/order/wait_send.png')" mode="widthFix"></image>
								<view class="icon-shade" :style="'-webkit-mask-image: url(' + $util.img('public/uniapp/member/order/wait_send_shade.png') + ');-webkit-mask-box-image: url(' + $util.img('public/uniapp/member/order/wait_send_shade.png') + ');'"></view>
							</template>
							<template v-else>
								<diy-icon :icon="value.icon.waitSend.icon" v-if="value.icon.waitSend" :value="value.icon.waitSend.style ? value.icon.waitSend.style : null"></diy-icon>
							</template>
							<text v-if="orderNum.waitsend > 0" class="order-num color-base-bg price-font">{{ orderNum.waitsend > 99 ? '99+' : orderNum.waitsend }}</text>
						</view>
						<view class="title">{{ value.icon.waitSend.title }}</view>
					</view>
					<view class="item-wrap" @click="redirect('/pages/order/list?status=waitconfirm')">
						<view class="icon-block">
							<template v-if="value.style == 3">
								<image :src="$util.img('public/uniapp/member/order/wait_confirm.png')" mode="widthFix"/>
								<view class="icon-shade" :style="'-webkit-mask-image: url(' + $util.img('public/uniapp/member/order/wait_confirm_shade.png') + ');-webkit-mask-box-image: url(' + $util.img('public/uniapp/member/order/wait_confirm_shade.png') + ');'"></view>
							</template>
							<template v-else>
								<diy-icon :icon="value.icon.waitConfirm.icon" v-if="value.icon.waitConfirm" :value="value.icon.waitConfirm.style ? value.icon.waitConfirm.style : null"></diy-icon>
							</template>
							<text v-if="orderNum.waitconfirm > 0" class="order-num color-base-bg price-font">{{ orderNum.waitconfirm > 99 ? '99+' : orderNum.waitconfirm }}</text>
						</view>
						<view class="title">{{ value.icon.waitConfirm.title }}</view>
					</view>
					<view class="item-wrap" @click="redirect('/pages/order/list?status=wait_use')">
						<view class="icon-block">
							<template v-if="value.style == 3">
								<image :src="$util.img('public/uniapp/member/order/wait_use.png')" mode="widthFix"/>
								<view class="icon-shade" :style="'-webkit-mask-image: url(' + $util.img('public/uniapp/member/order/wait_rate_shade.png') + ');-webkit-mask-box-image: url(' + $util.img('public/uniapp/member/order/wait_rate_shade.png') + ');'"></view>
							</template>
							<template v-else>
								<diy-icon :icon="value.icon.waitUse.icon" v-if="value.icon.waitUse" :value="value.icon.waitUse.style ? value.icon.waitUse.style : null"></diy-icon>
							</template>
							<text v-if="orderNum.wait_use > 0" class="order-num color-base-bg price-font">{{ orderNum.wait_use > 99 ? '99+' : orderNum.wait_use }}</text>
						</view>
						<view class="title">{{ value.icon.waitUse.title }}</view>
					</view>
					<view class="item-wrap" @click="redirect('/pages_tool/order/activist')">
						<view class="icon-block">
							<template v-if="value.style == 3">
								<image :src="$util.img('public/uniapp/member/order/refunding.png')" mode="widthFix"/>
								<view class="icon-shade" :style="'-webkit-mask-image: url(' + $util.img('public/uniapp/member/order/refunding_shade.png') + ');-webkit-mask-box-image: url(' + $util.img('public/uniapp/member/order/refunding_shade.png') + ');'"></view>
							</template>
							<template v-else>
								<diy-icon :icon="value.icon.refunding.icon" v-if="value.icon.refunding" :value="value.icon.refunding.style ? value.icon.refunding.style : null"></diy-icon>
							</template>
							<text v-if="orderNum.refunding > 0" class="order-num color-base-bg price-font">{{ orderNum.refunding > 99 ? '99+' : orderNum.refunding }}</text>
						</view>
						<view class="title">{{ value.icon.refunding.title }}</view>
					</view>
				</view>
			</view>
			<ns-login ref="login"></ns-login>
		</view>
	</view>
</template>

<script>
	// 自定义会员中心——我的订单
	export default {
		name: 'diy-member-my-order',
		props: {
			value: {
				type: Object
			}
		},
		data() {
			return {
				orderNum: {
					waitpay: 0,
					waitsend: 0,
					waitconfirm: 0,
					refunding: 0,
					wait_use: 0,
					waitrate: 0
				}
			};
		},
		created() {
			this.init();
		},
		watch: {
			storeToken(nVal, oVal) {
				this.init();
			},
			// 组件刷新监听
			componentRefresh: function(nval) {
				this.init();
			}
		},
		computed: {
			warpCss() {
				var obj = '';
				obj += 'background-color:' + this.value.componentBgColor + ';';
				if (this.value.componentAngle == 'round') {
					obj += 'border-top-left-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
				}
				return obj;
			}
		},
		methods: {
			init() {
				if (this.storeToken) {
					this.getOrderNum();
				} else {
					this.orderNum = {
						waitpay: 0,
						waitsend: 0,
						waitconfirm: 0,
						refunding: 0,
						wait_use: 0,
						waitrate: 0
					};
				}
			},
			/**
			 * 获取订单数量
			 */
			getOrderNum() {
				this.$api.sendRequest({
					url: '/api/order/num',
					data: {
						order_status: 'waitpay,waitsend,waitconfirm,refunding,wait_use,waitrate'
					},
					success: res => {
						if (res.code == 0) {
							this.orderNum = res.data;
						} else {
							this.orderNum = {
								waitpay: 0,
								waitsend: 0,
								waitconfirm: 0,
								refunding: 0,
								wait_use: 0,
								waitrate: 0
							};
						}
					}
				});
			},
			/**
			 * 跳转
			 * @param {Object} url
			 */
			redirect(url) {
				if (this.storeToken) {
					this.$util.redirectTo(url);
				} else {
					this.$refs.login.open(url);
				}
			}
		}
	};
</script>

<style lang="scss">
	.common-wrap {
		width: 100%;
		box-sizing: border-box;
	}

	.order-wrap {
		.status-wrap {
			display: flex;
			padding: 30rpx 0;
			align-items: center;
			justify-content: center;
			color: #333;
		}

		.item-wrap {
			flex: 1;
			text-align: center;

			.icon-block {
				width: 60rpx;
				height: 60rpx;
				font-size: 60rpx;
				margin: 4rpx auto;
				position: relative;

				&>image {
					position: absolute;
					top: 5%;
					right: 5%;
					width: 90%;
					height: 90%;
					z-index: 5;
				}

				.icon-shade {
					width: 90%;
					height: 90%;
					position: absolute;
					z-index: 4;
					top: 5%;
					right: 5%;
					background: $base-color;
					-webkit-mask: no-repeat center / contain;
				}
			}

			.order-num {
				position: absolute;
				top: 0;
				right: 0;
				transform: translate(50%, -50%);
				box-sizing: border-box;
				color: #ffffff;
				line-height: 1.2;
				text-align: center;
				font-size: 24rpx;
				padding: 0 6rpx;
				min-width: 30rpx;
				border-radius: 16rpx;
				height: 30rpx;
				display: flex;
				align-items: center;
				justify-content: center;
			}

			.title {
				font-size: 26rpx;
			}
		}
	}
</style>