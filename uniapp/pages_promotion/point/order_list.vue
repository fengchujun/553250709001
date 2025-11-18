<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="order-container">
		<view class="order-nav" v-if="storeToken">
			<view v-for="(statusItem, statusIndex) in statusList" :key="statusIndex" class="uni-tab-item" :id="statusItem.id" :data-current="statusIndex" @click="ontabtap">
				<text class="uni-tab-item-title" :class="statusItem.status == orderStatus ? 'uni-tab-item-title-active color-base-text' : ''">
					{{ statusItem.name }}
				</text>
			</view>
		</view>
		
		<mescroll-uni ref="mescroll" @getData="getListData" top="80rpx" >
			<block slot="list">
				<block v-if="orderList.length">
					<view class="order-item" v-for="(orderItem, orderIndex) in orderList" :key="orderIndex" @click="detail(orderItem)">
						<view class="order-header">
							<text class="order-no">订单号：{{ orderItem.order_no }}</text>
							<text class="status-name">
								{{ orderItem.order_status == 0 ? '待支付' : orderItem.order_status == 1 ? '已完成' : orderItem.order_status == -1 ? '已关闭' : '' }}
							</text>
						</view>
						<view class="order-body">
							<view class="goods-wrap">
								<view class="goods-img">
									<block v-if="orderItem.type == 2">
										<image :src="$util.img(orderItem.exchange_image) ? $util.img(orderItem.exchange_image) : $util.img('public/uniapp/point/coupon.png')"
										 @error="imageError(orderIndex)" mode="aspectFill" :lazy-load="true"></image>
									</block>
									<block v-else-if="orderItem.type == 3">
										<image :src="$util.img(orderItem.exchange_image) ? $util.img(orderItem.exchange_image) : $util.img('public/uniapp/point/hongbao.png')"
										 @error="imageError(orderIndex)" mode="aspectFill" :lazy-load="true"></image>
									</block>
									<block v-else>
										<image :src="$util.img(orderItem.exchange_image)" @error="imageError(orderIndex)" mode="aspectFill" :lazy-load="true"></image>
									</block>
								</view>
								<view class="goods-info">
									<view class="pro-info">
										<view class="goods-name">{{ orderItem.exchange_name }}</view>
										<view class="goods-sub-section">
											<text class="goods-price">
												<text class="price-style large">{{ orderItem.point }}</text>
												<text class="unit price-style small">积分</text>
												<block v-if="orderItem.price > 0">
													+
													<text class="unit price-style small">{{ $lang('common.currencySymbol') }}</text>
													<text class="price-style large">{{ parseFloat(orderItem.price).toFixed(2).split(".")[0] }}</text>
													<text class="unit price-style small">.{{ parseFloat(orderItem.price).toFixed(2).split(".")[1] }}</text>
												</block>
											</text>
											<text class="goods-num">
												<text class="iconfont icon-close"></text>
												{{ orderItem.num }}
											</text>
										</view>
									</view>
								</view>
							</view>
						</view>
						<view class="order-footer">
							<view class="order-action" v-if="orderItem.order_status == 0 && orderItem.type == 1">
								<view class="order-box-btn font-size-tag" @click.stop="orderClose(orderItem.order_id, orderIndex)">关闭</view>
								<view class="order-box-btn color-base-bg color-base-border"  @click.stop="openChoosePayment(orderItem.out_trade_no, orderItem.price)">支付</view>
							</view>
							<view class="order-action" v-else>
								<view class="order-box-btn font-size-tag">查看详情</view>
							</view>
						</view>
					</view>
				</block>
				<block v-if="showEmpty && !orderList.length">
					<view class="cart-empty">
						<ns-empty :isIndex="true" :emptyBtn ="{url: '/pages_promotion/point/list',text: '去逛逛'}" text="暂无积分兑换订单"></ns-empty>
					</view>
				</block>
			</block>
		</mescroll-uni>

		<!-- 选择支付方式弹窗 -->
		<ns-payment ref="choosePaymentPopup" :payMoney="payMoney" @confirm="orderPay"></ns-payment>
		<loading-cover ref="loadingCover"></loading-cover>
		<ns-login ref="login"></ns-login>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				orderList: [],
				showEmpty: false,
				outTradeNo: '',
				payMoney: 0,
				statusList: [
					{status: 'all', id: 'all', name: '全部'},
					{status: 0, id: 'pay', name: '待支付'},
					{status: 1, id: 'complete', name: '已完成'},
				],
				orderStatus: 'all',
			};
		},
		onLoad() {
			setTimeout( () => {
				if (!this.addonIsExist.pointexchange) {
					this.$util.showToast({
						title: '商家未开启积分商城',
						mask: true,
						duration: 2000
					});
					setTimeout(() => {
						this.$util.redirectTo('/pages/index/index', {}, 'redirectTo');
					}, 2000);
				}
			},1000);

			if (!this.storeToken) {
				this.$nextTick(() => {
					this.$refs.login.open('/pages_promotion/point/order_list');
				});
			}
		},
		onShow() {
			if (this.$refs.mescroll) this.$refs.mescroll.refresh();
		},
		watch: {
			storeToken: function(nVal, oVal) {
				if (nVal) {
					this.$refs.mescroll.refresh();
				}
			}
		},
		methods: {
			ontabtap(e) {
				let index = e.target.dataset.current || e.currentTarget.dataset.current;
				this.orderStatus = this.statusList[index].status;
				this.$refs.loadingCover.show();
				this.$refs.mescroll.refresh();
			},
			getListData(mescroll) {
				this.showEmpty = false;
				this.$api.sendRequest({
					url: '/pointexchange/api/order/page',
					data: {
						page: mescroll.num,
						page_size: mescroll.size,
						order_status: this.orderStatus
					},
					success: res => {
						this.showEmpty = true;
						let newArr = [];
						let msg = res.message;
						if (res.code == 0 && res.data) {
							newArr = res.data.list;
						} else {
							this.$util.showToast({
								title: msg
							});
						}
						mescroll.endSuccess(newArr.length);
						//设置列表数据
						if (mescroll.num == 1) this.orderList = []; //如果是第一页需手动制空列表
						this.orderList = this.orderList.concat(newArr); //追加新数据
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail: res => {
						mescroll.endErr();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			orderClose(order_id, index) {
				uni.showModal({
					title: '提示',
					content: '确定关闭此次兑换？',
					success: res => {
						if (res.confirm) {
							this.$api.sendRequest({
								url: '/pointexchange/api/order/close',
								data: {
									order_id: order_id
								},
								success: res => {
									if (res.code >= 0) {
										this.orderList[index].order_status = -1;
										this.$util.showToast({
											title: '关闭成功'
										});
										this.$forceUpdate();
									}
								}
							});
						}
					}
				});
			},
			// 显示选择支付方式弹框
			openChoosePayment(out_trade_no, price) {
				this.outTradeNo = out_trade_no;
				this.payMoney = parseFloat(price);
				this.$refs.choosePaymentPopup.open();
			},
			orderPay() {
				this.$refs.choosePaymentPopup.getPayInfo(this.outTradeNo);
			},
			detail(item) {
				if (item.type == 1 && item.relate_order_id) {
					this.$util.redirectTo('/pages/order/detail', {
						order_id: item.relate_order_id
					});
				} else {
					this.$util.redirectTo('/pages/order/detail_point', {
						order_id: item.order_id
					});
				}
			},
			imageError(index) {
				this.orderList[index].exchange_image = this.$util.getDefaultImage().goods;
				this.$forceUpdate();
			}
		}
	};
</script>

<style lang="scss" scoped>
	/deep/ .fixed {
		position: relative;
		top: 0;
	}

	/deep/ .empty {
		padding-top: 0 !important;
	}

	.order-container {
		width: 100vw;
		height: 100vh;
	}

	.align-right {
		text-align: right;
	}

	.order-item {
		margin: $margin-updown 24rpx;
		border-radius: 12rpx;
		background: #fff;
		position: relative;
	
		.order-header {
			display: flex;
			align-items: center;
			position: relative;
			padding: $padding 24rpx 26rpx 24rpx;
	
			&.waitpay {
				padding-left: 70rpx;
	
				.iconyuan_checked,
				.iconyuan_checkbox {
					font-size: $font-size-toolbar;
					position: absolute;
					top: 48%;
					left: 20rpx;
					transform: translateY(-50%);
				}
				.iconyuan_checkbox {
					color: $color-tip;
				}
			}
	
			.icondianpu {
				display: inline-block;
				line-height: 1;
				margin-right: 12rpx;
				font-size: $font-size-base;
			}
			
			.order-no {
				font-size: 26rpx;
			}
	
			.status-name {
				flex: 1;
				text-align: right;
				font-size: 26rpx;
				font-weight: 600;
			}
		}
	
		.order-body {
			.goods-wrap {
				display: flex;
				position: relative;
				padding: 0 24rpx 30rpx 24rpx;
	
				&:last-of-type {
					margin-bottom: 0;
				}
	
				.goods-img {
					width: 160rpx;
					height: 160rpx;
					margin-right: 20rpx;
	
					image {
						width: 100%;
						height: 100%;
						border-radius: $border-radius;
					}
				}
	
				.goods-info {
					flex: 1;
					position: relative;
					max-width: calc(100% - 180rpx);
					display: flex;
					flex-direction: column;
	
					.pro-info {
						flex: 1;
					}
	
					.goods-name {
						display: -webkit-box;
						-webkit-box-orient: vertical;
						-webkit-line-clamp: 2;
						overflow: hidden;
						line-height: 1.5;
						font-size: $font-size-base;
						color: $color-title;
					}
	
					.goods-sub-section {
						width: 100%;
						line-height: 1.3;
						display: flex;
						margin-top: 14rpx;
	
						.goods-price {
							font-size: $font-size-tag;
							color: var(--price-color);
							flex: 1;
							font-weight: bold;
						}
						.goods-num {
							font-size: $font-size-tag;
							color: $color-tip;
							flex: 1;
							text-align: right;
							line-height: 1;
	
							.iconfont {
								font-size: $font-size-tag;
							}
						}
						.goods-type {
							font-size: $font-size-tag;
						}
	
						.unit {
							font-size: $font-size-tag;
							margin-right: 2rpx;
						}
	
						view {
							flex: 1;
							line-height: 1.3;
							display: flex;
							flex-direction: column;
	
							&:last-of-type {
								text-align: right;
	
								.iconfont {
									line-height: 1;
									font-size: $font-size-tag;
								}
							}
						}
					}
	
					.goods-action {
						text-align: right;
	
						.action-btn {
							line-height: 1;
							padding: 14rpx 20rpx;
							color: $color-title;
							display: inline-block;
							border-radius: $border-radius;
							background: #fff;
							border: 2rpx solid #999;
							font-size: $font-size-tag;
							margin-left: 10rpx;
						}
					}
				}
			}
			
			.multi-order-goods {
				width: calc(100vw - 96rpx);
				white-space: nowrap;
				margin: 0 24rpx 30rpx 24rpx!important;
				position: relative;
				
				.scroll-view {
					width: 100%;
				}
				
				.goods-wrap {
					padding: 0;
				}
				
				.goods-img {
					min-width: 160rpx;
				}
				
				.shade {
					position: absolute;
					z-index: 5;
					height: 100%;
					width: 44rpx;
					right: 0;
					top: 0;
					
					image {
						width: 100%;
						height: 100%;
					}
				}
			}
		}
	
		.order-footer {
			.order-base-info {
				.total {
					padding: $padding;
					font-size: $font-size-tag;
					background: rgba(248, 248, 248, 0.5);
					display: flex;
					margin: 0 24rpx;
	
					& > text {
						flex: 1;
						line-height: 1;
						margin-left: 10rpx;
					}
				}
	
				.order-type {
					padding-top: 20rpx;
					flex: 0.5;
	
					& > text {
						line-height: 1;
					}
				}
			}
	
			.order-action {
				text-align: right;
				padding: 30rpx 24rpx;
				position: relative;
				
				.order-time {
					position: absolute;
					top: 35rpx;
					left: 30rpx;
					display: flex;
					align-items: center;
					font-size: 10px;
					color:#b5b6b9;
					
					image {
						width: 26rpx;
						height: 26rpx;
						margin-right: 6rpx;
					}
				}
	
				.action-btn {
					line-height: 1;
					padding: 20rpx 26rpx;
					color: #333;
					display: inline-block;
					border-radius: $border-radius;
					background: #fff;
					border: 2rpx solid #999;
					font-size: $font-size-tag;
					margin-left: 10rpx;
				}
			}
		}
	}

	.empty {
		padding-top: 200rpx;
		text-align: center;

		.empty-image {
			width: 180rpx;
			height: 180rpx;
		}
	}
	
	.order-nav {
		width: 100vw;
		height: 80rpx;
		flex-direction: row;
		/* #ifndef APP-PLUS */
		white-space: nowrap;
		/* #endif */
		background: #fff;
		display: flex;
		position: fixed;
		left: 0;
		z-index: 998;
		justify-content: space-around;
		border-radius: 0px 0px 24rpx 24rpx;
	
		.uni-tab-item {
			width: 120rpx;
			text-align: center;
		}
	
		.uni-tab-item-title {
			display: inline-block;
			height: 80rpx;
			line-height: 80rpx;
			border-bottom: 1px solid #fff;
			flex-wrap: nowrap;
			/* #ifndef APP-PLUS */
			white-space: nowrap;
			/* #endif */
			text-align: center;
			font-size: 30rpx;
			position: relative;
		}
	
		.uni-tab-item-title-active::after {
			content: " ";
			display: block;
			position: absolute;
			left: 0;
			bottom: 0;
			width: 100%;
			height: 6rpx;
			background: linear-gradient(270deg, var(--base-color-light-9) 0%, var(--base-color) 100%);
		}
	
		::-webkit-scrollbar {
			width: 0;
			height: 0;
			color: transparent;
		}
	}
</style>
