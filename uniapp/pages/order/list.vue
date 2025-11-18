<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="order-container">
		<view class="cate-search" v-if="storeToken">
			<view class="search-box">
				<input class="uni-input" maxlength="50" v-model="searchText" confirm-type="search" placeholder="请输入商品名称/订单编号" @confirm="search()" />
				<text class="iconfont icon-sousuo3" @click="search()"></text>
			</view>
		</view>
		<view class="order-nav" v-if="storeToken">
			<view v-for="(statusItem, statusIndex) in statusList" :key="statusIndex" class="uni-tab-item" :id="statusItem.id" :data-current="statusIndex" @click="ontabtap">
				<text class="uni-tab-item-title" :class="statusItem.status == orderStatus ? 'uni-tab-item-title-active color-base-text' : ''">
					{{ statusItem.name }}
				</text>
			</view>
		</view>

		<!-- #ifdef MP -->
		<mescroll-uni ref="mescroll" @getData="getListData" top="176rpx" v-if="storeToken">
		<!-- #endif -->
		<!-- #ifndef MP -->
		<mescroll-uni ref="mescroll" @getData="getListData" top="196rpx" v-if="storeToken">
		<!-- #endif -->
			<block slot="list">
				<view class="order-list" v-if="orderList.length > 0">
					<view class="order-item" v-for="(orderItem, orderIndex) in orderList" :key="orderIndex">
						<view class="order-header" :class="{ waitpay: orderStatus == 'waitpay' && orderItem.order_status == 0 }">
							<!-- <view class="iconfont"
								:class="$util.inArray(orderItem.order_id, mergePayOrder) == -1 ? 'icon-yuan_checkbox' : 'icon-yuan_checked color-base-text'"
								@click="selectOrder(orderItem.order_id, orderItem.pay_money)"
								v-if="orderStatus == 'waitpay' && orderItem.order_status == 0"></view> -->
							<text class="order-no">订单号：{{ orderItem.order_no }}</text>
							<text class="order-type-name">{{ orderItem.order_type_name }}</text>
							<text class="status-name">{{ orderItem.order_status_name }}</text>
						</view>
						<view class="order-body" @click="orderDetail(orderItem)">
							<block v-if="orderItem.order_goods.length == 1">
								<view class="goods-wrap" v-for="(goodsItem, goodsIndex) in orderItem.order_goods" :key="goodsIndex">
									<view class="goods-img">
										<image :src="$util.img(goodsItem.sku_image, { size: 'mid' })" @error="imageError(orderIndex, goodsIndex)" mode="aspectFill" :lazy-load="true"/>
									</view>
									<view class="goods-info">
										<view class="pro-info">
											<view class="goods-name" v-if="goodsItem.goods_class == 2">{{ goodsItem.goods_name }}</view>
											<view class="goods-name" v-else>{{ goodsItem.sku_name }}</view>
											<view class="sku" v-if="goodsItem.sku_spec_format">
												<view class="goods-spec">
													<block v-for="(x, i) in goodsItem.sku_spec_format" :key="i">
														{{ x.spec_value_name }}
														{{ i < goodsItem.sku_spec_format.length - 1 ? '; ' : '' }}
													</block>
												</view>
											</view>
										</view>
										<!-- <view class="goods-sub-section">
										<text class="goods-price">
											<text class="unit price-style small">{{ $lang('common.currencySymbol') }}</text>
											<text class="price-style large">{{ parseFloat(goodsItem.price).toFixed(2).split(".")[0] }}</text>
											<text class="unit price-style small">.{{ parseFloat(goodsItem.price).toFixed(2).split(".")[1] }}</text>
											
										</text>
										<text class="goods-num">
											<text class="iconfont icon-close"></text>
											{{ goodsItem.num }}
										</text>
									</view> -->
										<view class="goods-action"><!-- <view class="action-btn">加购物车</view> -->
										</view>
									</view>
								</view>
							</block>
							<block v-else>
								<view class="multi-order-goods">
									<scroll-view scroll-x="true" class="scroll-view">
										<view class="goods-wrap">
											<view class="goods-img" v-for="(goodsItem, goodsIndex) in orderItem.order_goods" :key="goodsIndex">
												<image :src="$util.img(goodsItem.sku_image, { size: 'mid' })" @error="imageError(orderIndex, goodsIndex)" mode="aspectFill" :lazy-load="true"/>
											</view>
										</view>
									</scroll-view>
									<view class="shade">
										<image :src="$util.img('public/uniapp/order/order-shade.png')"></image>
									</view>
								</view>
							</block>
						</view>
						<view class="order-footer">
							<view class="order-base-info">
								<view class="total">
									<text class="font-size-sub">共{{ orderItem.goods_num }}件商品</text>
									<text class="align-right font-size-base">
										实付款：
										<text class="font-size-base price-font">{{ $lang('common.currencySymbol') }}{{ orderItem.order_money }}</text>
									</text>
								</view>
							</view>
							<view class="order-action" v-if="orderItem.action.length > 0">
								<view class="order-time" v-if="orderItem.order_status == 0 && orderItem.pay_type !== 'offlinepay'" id="action-date">
									<image :src="$util.img('public/uniapp/order/time.png')"></image>
									剩余时间：
									<uni-count-down :day="orderItem.discountTimeMachine.d"
										:hour="orderItem.discountTimeMachine.h"
										:minute="orderItem.discountTimeMachine.i"
										:second="orderItem.discountTimeMachine.s" color="#FF4644"
										splitorColor="#FF4644" />
								</view>
								<view class="order-box-btn"
									v-if="evaluateConfig.evaluate_status == 1 && orderItem.is_evaluate == 1"
									@click="operation('memberOrderEvaluation', orderItem)">
									<text v-if="orderItem.evaluate_status == 0">评价</text>
									<text v-else-if="orderItem.evaluate_status == 1">追评</text>
								</view>
								<view class="order-box-btn"
									:class="{ 'color-base-border color-base-bg': operationItem.action == 'orderPay' }"
									v-for="(operationItem, operationIndex) in orderItem.action"
									:key="operationIndex" @click="operation(operationItem.action, orderItem)">
									{{ operationItem.title }}
								</view>
							</view>
							<view class="order-action" v-else-if="orderItem.action.length == 0 && orderItem.is_evaluate == 1 && evaluateConfig.evaluate_status == 1">
								<view class="order-box-btn" v-if="orderItem.is_evaluate == 1" @click="operation('memberOrderEvaluation', orderItem)">
									<text v-if="orderItem.evaluate_status == 0">评价</text>
									<text v-else-if="orderItem.evaluate_status == 1">追评</text>
								</view>
							</view>
							<view class="order-action" v-else>
								<view class="order-box-btn" @click="orderDetail(orderItem)">查看详情</view>
							</view>
						</view>
					</view>
				</view>
				<view v-else><ns-empty :isIndex="false" :text="$lang('emptyTips')"></ns-empty></view>
			</block>
		</mescroll-uni>
		<view v-if="!storeToken" class="no-login">
			<view><ns-empty :isIndex="false" :text="$lang('emptyTips')"></ns-empty></view>
			<button type="primary" size="mini" class="button mini" @click="toLogin">去登录</button>
		</view>
		
		

		<!-- 选择支付方式弹窗 -->
		<payment ref="choosePaymentPopup"></payment>

		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
	import orderMethod from './public/js/orderMethod.js';
	import payment from '@/components/payment/payment.vue';

	export default {
		data() {
			return {
				scrollInto: '',
				orderStatus: 'all',
				statusList: [],
				orderList: [],
				contentText: {},
				mergePayOrder: [],
				isIphoneX: false,
				evaluateConfig: {
					evaluate_audit: 1,
					evaluate_show: 0,
					evaluate_status: 1
				},
				orderData: {},
				payMoney: 0,
				payMoneyMerge: 0,
				order_id: 0,
				searchText: "",
				pageText: "",
				payConfig: null,
				isTradeManaged: false // 检测微信小程序是否已开通发货信息管理服务
			};
		},
		components: {
			payment
		},
		mixins: [orderMethod],
		onLoad(option) {
			if (option.status) this.orderStatus = option.status;
			if (option.order_id) this.order_id = option.order_id;
		},
		onShow() {
			this.isIphoneX = this.$util.uniappIsIPhoneX();
			this.getEvaluateConfig();
			this.getOrderStatus();

			if (this.storeToken) {
				if (this.$refs.mescroll) this.$refs.mescroll.refresh();
			} else {
				this.$nextTick(() => {
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					// this.$refs.login.open('/pages/order/list?status=' + this.orderStatus);
				})
			}
			if(this.$refs.choosePaymentPopup) this.$refs.choosePaymentPopup.pageShow()
		},
		onUnload() {
			if (!this.storeToken && this.$refs.login) this.$refs.login.cancelCompleteInfo();
		},
		methods: {
			toLogin() {
				this.$refs.login.open();
			},
			ontabtap(e) {
				let index = e.target.dataset.current || e.currentTarget.dataset.current;
				this.orderStatus = this.statusList[index].status;
				if (this.orderStatus == '') this.mergePayOrder = [];
				this.$refs.loadingCover.show();
				this.$refs.mescroll.refresh();
			},
			getListData(mescroll) {

				this.$api.sendRequest({
					url: '/api/order/lists',
					data: {
						page: mescroll.num,
						page_size: mescroll.size,
						order_status: this.orderStatus,
						order_id: this.order_id,
						searchText: this.pageText
					},
					success: res => {
						let newArr = [];
						let msg = res.message;
						let auto_close = 0
						if (res.code == 0 && res.data) {
							newArr = res.data.list;
							auto_close = res.data.auto_close;
							this.payConfig = res.data.pay_config;
							this.isTradeManaged = res.data.is_trade_managed;
						} else {
							this.$util.showToast({
								title: msg
							});
						}
						mescroll.endSuccess(newArr.length);
						//设置列表数据
						if (mescroll.num == 1) {
							this.orderList = []; //如果是第一页需手动制空列表
							this.order_id = 0
						}
						this.orderList = this.orderList.concat(newArr); //追加新数据
						let date = (Date.parse(new Date())) / 1000
						this.orderList.forEach(v => {
							v.discountTimeMachine = this.$util.countDown((v.create_time + auto_close) - date);
							v.order_goods.forEach(vo => {
								if (vo.sku_spec_format) {
									try {
										vo.sku_spec_format = JSON.parse(vo.sku_spec_format);
									} catch (e) {
										vo.sku_spec_format = vo.sku_spec_format;
									}
								} else {
									vo.sku_spec_format = [];
								}
							});
						});
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail: res => {
						mescroll.endErr();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			/**
			 * 获取订单状态
			 */
			getOrderStatus() {
				this.statusList = [{
						status: 'all',
						name: this.$lang('all'),
						id: 'status_0'
					},
					{
						status: 'waitpay',
						name: this.$lang('waitPay'),
						id: 'status_1'
					},
					{
						status: 'waitsend',
						name: this.$lang('readyDelivery'),
						id: 'status_2'
					},
					{
						status: 'waitconfirm',
						name: this.$lang('waitDelivery'),
						id: 'status_3'
					},
					{
						status: 'wait_use',
						name: this.$lang('waitUse'),
						id: 'status_4'
					}
				];
			},
			operation(action, orderData) {
				let index = this.status;
				switch (action) {
					case 'orderDelete':
						this.orderDelete(orderData.order_id, () => {
							this.$refs.mescroll.refresh();
						});
						break;
					case 'orderPay': // 支付
						this.orderData = orderData;
						this.payMoney = parseFloat(orderData.pay_money);
						this.orderPay(orderData);
						break;
					case 'orderClose': //关闭
						this.orderClose(orderData.order_id, () => {
							this.$refs.mescroll.refresh();
						});
						break;
					case 'memberTakeDelivery': //收货
						this.orderData = orderData;
						this.orderData.pay_config = {};
						this.orderData.pay_config.mch_id = this.payConfig.mch_id;
						this.orderData.is_trade_managed = this.isTradeManaged;
						this.orderDelivery(this.orderData, () => {
							this.$refs.mescroll.refresh();
						});
						break;
					case 'trace': //查看物流
						this.$util.redirectTo('/pages_tool/order/logistics', {
							order_id: orderData.order_id
						});
						break;
					case 'memberOrderEvaluation': //评价
						this.$util.redirectTo('/pages_tool/order/evaluate', {
							order_id: orderData.order_id
						});
						break;
					case 'memberVirtualTakeDelivery':
						this.orderData = orderData;
						this.orderData.pay_config = {};
						this.orderData.pay_config.mch_id = this.payConfig.mch_id;
						this.orderData.is_trade_managed = this.isTradeManaged;
						this.orderVirtualDelivery(this.orderData, () => {
							this.$refs.mescroll.refresh();
						});
						break;
					case 'orderOfflinePay':
						this.orderData = orderData;
						this.$util.redirectTo('/pages_tool/pay/offlinepay', {
						    outTradeNo: this.orderData.out_trade_no
						});
						break;
				}
			},
			orderDetail(data) {
				this.$util.redirectTo('/pages/order/detail', {
					order_id: data.order_id
				});
			},
			/**
			 * 选择订单
			 * @param {Object} orderId
			 */
			selectOrder(orderId, pay_money) {
				if (this.$util.inArray(orderId, this.mergePayOrder) != -1) {
					this.mergePayOrder.splice(this.$util.inArray(orderId, this.mergePayOrder), 1);
					this.payMoneyMerge -= parseFloat(pay_money);
				} else {
					this.payMoneyMerge += parseFloat(pay_money);
					this.mergePayOrder.push(orderId);
				}
			},
			imageError(orderIndex, goodsIndex) {
				this.orderList[orderIndex].order_goods[goodsIndex].sku_image = this.$util.getDefaultImage().goods;
				this.$forceUpdate();
			},
			getEvaluateConfig() {
				this.$api.sendRequest({
					url: '/api/goodsevaluate/config',
					success: res => {
						if (res.code == 0) {
							var data = res.data;
							this.evaluateConfig = data;
						}
					}
				});
			},
			search() {
				this.pageText = this.searchText;
				this.$refs.mescroll.refresh();
			}
		},
		computed: {
			mpOrderList() {
				if (!this.orderList[this.status]) return;
				return this.orderList[this.status].list || [];
			}
		},
		watch: {
			storeToken: function(nVal, oVal) {
				if (nVal) {
					this.$refs.mescroll.refresh();
				}
			}
		}
	};
</script>

<style lang="scss">
	@import './public/css/list.scss';
</style>
<style scoped>
	/deep/ .uni-page {
		overflow: hidden;
	}

	/deep/ .mescroll-upwarp {
		padding-bottom: 100rpx;
	}
	.no-login{
		display: flex;
		flex-direction: column;
		align-items: center;
	}
	.no-login .button{
		width: 300rpx;
		margin-top: 100rpx;
		height: 70rpx;
		line-height: 70rpx !important;
		font-size: 28rpx;
		border-radius: 50rpx;
	}
	
</style>