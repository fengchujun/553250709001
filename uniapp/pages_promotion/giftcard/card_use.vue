<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<view class="order-container" :class="{ 'safe-area': isIphoneX }">
			<!-- #ifdef MP -->
			<view class="payment-navbar" :style="{ 'padding-top': menuButtonBounding.top + 'px',height: menuButtonBounding.height + 'px' }">
				<view class="nav-wrap">
					<text class="iconfont icon-back_light" @click="back"></text>
					<view class="navbar-title">确认订单</view>
				</view>
			</view>
			<view class="payment-navbar-block" :style="{ height: menuButtonBounding.bottom + 'px' }"></view>
			<!-- #endif -->

			<scroll-view scroll-y="true" class="order-scroll-container">
				<view class="payment-navbar-block"></view>
				<template v-if="paymentData">
					<template v-if="paymentData.is_virtual">
						<!-- 虚拟商品联系方式 -->
						<view class="mobile-wrap">
							<view class="tips color-base-text">
								<text class="iconfont icon-gantanhao"></text>
								购买虚拟类商品需填写手机号，方便商家与您联系
							</view>
							<view class="form-group">
								<text class="icon">
									<image :src="$util.img('public/uniapp/order/icon-mobile.png')" mode="widthFix"/>
								</text>
								<text class="text">手机号码</text>
								<input type="number" maxlength="11" placeholder="请输入您的手机号码" placeholder-class="color-tip placeholder" class="input" v-model="orderCreateData.delivery.member_address.mobile" />
							</view>
						</view>
					</template>
					<template v-else>
						<!-- 配送方式 -->
						<view class="delivery-mode" v-if="goodsData.delivery.express_type.length > 1">
							<view class="action">
								<view :class="{ active: item.name == orderCreateData.delivery.delivery_type }" v-for="(item, index) in goodsData.delivery.express_type" :key="index" @click="selectDeliveryType(item)">
									{{ item.title }}
									<!-- 外圆角 -->
									<view class="out-radio"></view>
								</view>
							</view>
						</view>

						<view class="address-box" :class="{ 'not-delivery-type': goodsData.delivery.express_type.length <= 1 }" v-if="orderCreateData.delivery.delivery_type == 'express'">
							<view class="info-wrap" v-if="memberAddress" @click="selectAddress">
								<view class="content">
									<text class="name">{{ memberAddress.name ? memberAddress.name : '' }}</text>
									<text class="mobile">{{ memberAddress.mobile ? memberAddress.mobile : '' }}</text>
									<view class="desc-wrap">
										{{ memberAddress.full_address ? memberAddress.full_address : '' }}
										{{ memberAddress.address ? memberAddress.address : '' }}
									</view>
								</view>
								<text class="cell-more iconfont icon-right"></text>
							</view>
							<view class="empty-wrap" v-else @click="selectAddress">
								<view class="info">请设置收货地址</view>
								<view class="cell-more">
									<view class="iconfont icon-right"></view>
								</view>
							</view>
							<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')">
							</image>
						</view>

						<view class="address-box" :class="{ 'not-delivery-type': goodsData.delivery.express_type.length <= 1 }" v-if="orderCreateData.delivery.delivery_type == 'local'">
							<view v-if="localMemberAddress">
								<block v-if="storeList && Object.keys(storeList).length > 1">
									<view class="local-delivery-store" v-if="storeInfo" @click="$refs.deliveryPopup.open()">
										<view class="info">
											由
											<text class="store-name">{{ storeInfo.store_name }}</text>
											提供配送
										</view>
										<view class="cell-more">
											<text>点击切换</text>
											<text class="iconfont icon-right"></text>
										</view>
									</view>
									<view v-else class="local-delivery-store">
										<view class="info"><text class="store-name">您的附近没有可配送的门店，请选择其他配送方式</text></view>
									</view>
								</block>
								<view class="info-wrap local" @click="selectAddress">
									<view class="content">
										<text class="name">{{ localMemberAddress.name ? localMemberAddress.name : '' }}</text>
										<text class="mobile">{{ localMemberAddress.mobile ? localMemberAddress.mobile : '' }}</text>
										<view class="desc-wrap">
											{{ localMemberAddress.full_address ? localMemberAddress.full_address : '' }}
											{{ localMemberAddress.address ? localMemberAddress.address : '' }}
										</view>
									</view>
									<text class="cell-more iconfont icon-right"></text>
								</view>
								<view class="local-box" v-if="calculateGoodsData.config.local.is_use && calculateGoodsData.delivery.local.info.time_is_open == 1">
									<view class="pick-block" @click="localtime('')">
										<view class="title font-size-base">送达时间</view>
										<view class="time-picker">
											<text :class="{ 'color-tip': !deliveryTime }">{{ deliveryTime ? deliveryTime : '请选择送达时间' }}</text>
											<text class="iconfont icon-right cell-more"></text>
										</view>
									</view>
								</view>
							</view>
							<view class="empty-wrap" v-else @click="selectAddress">
								<view class="info">请设置收货地址</view>
								<view class="cell-more">
									<view class="iconfont icon-right"></view>
								</view>
							</view>

							<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')">
							</image>
						</view>

						<!-- 门店信息 -->
						<view class="store-box" :class="{ 'not-delivery-type': goodsData.delivery.express_type.length <= 1 }" v-if="orderCreateData.delivery.delivery_type == 'store'">
							<block v-if="storeInfo">
								<view @click="$refs.deliveryPopup.open()" class="store-info">
									<view class="store-address-info">
										<view class="info-wrap">
											<view class="title">
												<text>{{ storeInfo.store_name }}</text>
											</view>
											<view class="store-detail">
												<view v-if="storeInfo.open_date">营业时间：{{ storeInfo.open_date }}</view>
												<view class="address">{{ storeInfo.full_address }} {{ storeInfo.address }}</view>
											</view>
										</view>
										<view class="cell-more iconfont icon-right"></view>
									</view>
								</view>
								<view class="mobile-wrap store-mobile">
									<view class="form-group">
										<text class="text">姓名</text>
										<input type="text" placeholder-class="color-tip placeholder" class="input" disabled v-model="orderCreateData.member_address.name" />
									</view>
								</view>
								<view class="mobile-wrap store-mobile">
									<view class="form-group">
										<text class="text">预留手机</text>
										<input type="number" maxlength="11" placeholder="请输入您的手机号码" placeholder-class="color-tip placeholder" class="input" v-model="orderCreateData.member_address.mobile" />
									</view>
								</view>
								<view class="store-time" @click="storetime('')">
									<view class="left">提货时间</view>
									<view class="right">
										{{ deliveryTime }}
										<text class="iconfont icon-right"></text>
									</view>
								</view>
							</block>
							<view v-else class="empty">当前无自提门店，请选择其它配送方式</view>
							<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')"/>
						</view>
					</template>

					<!-- 店铺 -->
					<view class="site-wrap order-goods">
						<view class="site-body">
							<!-- 商品 -->
							<view class="goods-item" v-for="(goodsItem, goodsIndex) in goodsData.goods_list" :key="goodsIndex">
								<view class="goods-wrap">
									<view class="goods-img" @click="$util.redirectTo('/pages/goods/detail', { goods_id: goodsItem.goods_id })">
										<image :src="$util.img(goodsItem.sku_image, { size: 'mid' })" @error="imageError(goodsIndex)" mode="aspectFill"/>
									</view>
									<view class="goods-info">
										<view @click="$util.redirectTo('/pages/goods/detail', { goods_id: goodsItem.goods_id })" class="goods-name">{{ goodsItem.sku_name }}</view>
										<view class="sku" v-if="goodsItem.sku_spec_format">
											<view class="goods-spec">
												<block v-for="(x, i) in goodsItem.sku_spec_format" :key="i">
													<view>{{ x.spec_value_name }}</view>
												</block>
											</view>
										</view>
										<block v-if="goodsItem.is_virtual == 0">
											<view class="error-tips" v-if="orderCreateData.delivery &&
													orderCreateData.delivery.delivery_type &&
													goodsItem.support_trade_type &&
													goodsItem.support_trade_type.indexOf(orderCreateData.delivery.delivery_type) == -1
												">
												<text class="iconfont icon-gantanhao"></text>
												<text>该商品不支持{{ orderCreateData.delivery.delivery_type_name }}</text>
											</view>
										</block>
										<view class="error-tips" v-if="goodsItem.error && goodsItem.error.message">
											<text class="iconfont icon-gantanhao"></text>
											<text>{{ goodsItem.error.message }}</text>
										</view>
										<view class="goods-sub-section">
											<view class="color-base-text">
												<text class="unit price-style small"></text>

												<text class="goods-price price-style large"></text>
												<text class="unit price-style small"></text>
											</view>
											<view>
												<text class="font-size-tag">x</text>
												<text class="font-size-base">{{ goodsItem.num }}</text>
											</view>
										</view>
									</view>
								</view>
								<view class="goods-form" v-if="goodsItem.goods_form" @click="editForm(goodsIndex)">
									<ns-form :data="goodsItem.goods_form.json_data" ref="goodsForm" :custom-attr="{ sku_id: goodsItem.sku_id, form_id: goodsItem.goods_form.id }"></ns-form>
									<text class="cell-more iconfont icon-right"></text>
									<view class="shade"></view>
								</view>
							</view>
						</view>
					</view>

					<view class="site-wrap buyer-message">
						<view class="order-cell">
							<text class="tit">买家留言</text>
							<view class="box text-overflow " @click="openPopup('buyerMessagePopup')">
								<text v-if="orderCreateData.buyer_message">{{ orderCreateData.buyer_message }}</text>
								<text class="color-sub" v-else>无留言</text>
							</view>
							<text class="iconfont icon-right"></text>
						</view>
					</view>

					<view v-if="paymentData.system_form" class="system-form-wrap">
						<ns-form :data="paymentData.system_form.json_data" ref="form"></ns-form>
					</view>

					<!-- 订单金额 -->
					<template v-if="calculateData">
						<view class="order-submit bottom-safe-area">
							<view class="submit-btn">
								<button type="primary" class="sava-btn mini" size="mini" @click="create()">立即兑换</button>
							</view>
						</view>
						<view class="order-submit-block"></view>

						<payment ref="choosePaymentPopup" @close="payClose" v-if="calculateData"></payment>
					</template>

					<!-- 门店列表弹窗 -->
					<uni-popup ref="deliveryPopup" type="bottom" v-if="storeList">
						<view class="delivery-popup popup">
							<view class="popup-header">
								<text class="tit">已为您甄选出附近所有相关门店</text>
								<text class="iconfont icon-close" @click="closePopup('deliveryPopup')"></text>
							</view>
							<view class="popup-body store-popup" :class="{ 'safe-area': isIphoneX }">
								<view class="delivery-content">
									<view class="item-wrap" v-for="(item, index) in storeList" :key="index" @click="selectPickupPoint(item)">
										<view class="detail">
											<view class="name" :class="item.store_id == orderCreateData.delivery.store_id ? 'color-base-text' : ''">
												<text>{{ item.store_name }}</text>
												<text v-if="item.distance">({{ item.distance }}km)</text>
											</view>
											<view class="info">
												<view :class="item.store_id == orderCreateData.delivery.store_id ? 'color-base-text' : ''" class="font-size-goods-tag">
													营业时间：{{ item.open_date }}
												</view>
												<view :class="item.store_id == orderCreateData.delivery.store_id ? 'color-base-text' : ''" class="font-size-goods-tag">
													地址：{{ item.full_address }}{{ item.address }}
												</view>
											</view>
										</view>
										<view class="icon" v-if="item.store_id == orderCreateData.delivery.store_id">
											<text class="iconfont icon-yuan_checked color-base-text"></text>
										</view>
									</view>
									<view v-if="!storeList" class="empty">所选择收货地址附近没有可以自提的门店</view>
								</view>
							</view>
						</view>
					</uni-popup>

					<!-- 留言弹窗 -->
					<uni-popup ref="buyerMessagePopup" type="bottom">
						<view style="height: auto;" class="buyermessag-popup popup" @touchmove.prevent.stop>
							<view class="popup-header">
								<text class="tit">买家留言</text>
								<text class="iconfont icon-close" @click="closePopup('buyerMessagePopup')"></text>
							</view>
							<scroll-view scroll-y="true" class="popup-body" :class="{ 'safe-area': isIphoneX }">
								<view>
									<view class="buyermessag-cell">
										<view class="buyermessag-form-group">
											<textarea type="text" maxlength="100" placeholder="留言前建议先与商家协调一致" placeholder-class="color-tip" v-model="orderCreateData.buyer_message"></textarea>
										</view>
									</view>
								</view>
							</scroll-view>
							<view class="popup-footer" @click="saveBuyerMessage" :class="{ 'bottom-safe-area': isIphoneX }">
								<view class="confirm-btn color-base-bg">确定</view>
							</view>
						</view>
					</uni-popup>

					<!-- 表单修改弹窗 -->
					<uni-popup ref="editFormPopup" type="bottom">
						<view style="height: auto;" class="form-popup popup" @touchmove.prevent.stop>
							<view class="popup-header">
								<text class="tit">买家信息</text>
								<text class="iconfont icon-close" @click="$refs.editFormPopup.close()"></text>
							</view>
							<scroll-view scroll-y="true" class="popup-body" :class="{ 'safe-area': isIphoneX }">
								<ns-form v-if="tempFormData" :data="tempFormData.json_data" ref="tempForm"></ns-form>
							</scroll-view>
							<view class="popup-footer" @click="saveForm" :class="{ 'bottom-safe-area': isIphoneX }">
								<view class="confirm-btn color-base-bg">确定</view>
							</view>
						</view>
					</uni-popup>

					
				</template>
			</scroll-view>
					<!-- 门店自提时间 -->
					<ns-select-time @selectTime="selectPickupTime" ref="timePopup"></ns-select-time>
			<ns-login ref="login"></ns-login>
			<loading-cover ref="loadingCover"></loading-cover>
		</view>
	</view>
</template>

<script>
	export default {
		options: {
			styleIsolation: 'shared'
		},
		data() {
			return {
				api: {
					payment: '/giftcard/api/giftcardordercreate/payment',
					calculate: '/giftcard/api/giftcardordercreate/calculate',
					create: '/giftcard/api/giftcardordercreate/create'
				},
				createDataKey: 'giftcarduse',
				outTradeNo: '',
				isIphoneX: false,
				orderCreateData: {
					is_balance: 0,
					is_point: 1,
					delivery: {},
				},
				paymentData: null,
				calculateData: null,
				tempData: null,
				storeId: 0,
				deliveryTime: '', // 提货时间
				memberAddress: null, // 会员收货地址
				localMemberAddress: null, // 会员本地配送收货地址
				isRepeat: false,
				promotionInfo: null,
				tempFormData: null,
				menuButtonBounding: {}, // 小程序胶囊属性
				storeConfig: null,
				LocalConfig: null
			};
		},
		// inject: ['promotion'],
		created() {
			// #ifdef MP
			this.menuButtonBounding = uni.getMenuButtonBoundingClientRect();
			// #endif
			this.isIphoneX = this.$util.uniappIsIPhoneX();
			if (this.storeToken) {
				Object.assign(this.orderCreateData, uni.getStorageSync(this.createDataKey));
				if (this.location) {
					this.orderCreateData.latitude = this.location.latitude;
					this.orderCreateData.longitude = this.location.longitude;
				}
				this.payment();
			} else {
				this.$nextTick(() => {
					this.$refs.loadingCover.hide();
					this.$refs.login.open(this.$util.getCurrentRoute().path);
				});
			}
		},
		computed: {
			goodsData() {
				if (this.paymentData) {
					this.paymentData.goods_list.forEach(item => {
						if (item.sku_spec_format) item.sku_spec_format = JSON.parse(item.sku_spec_format);
					});
					return this.paymentData;
				}
			},
			calculateGoodsData() {
				if (this.calculateData) return this.calculateData;
			},
			// 余额可抵扣金额
			balanceDeduct() {
				if (this.calculateData) {
					if (this.calculateData.member_account.balance_total <= parseFloat(this.calculateData.order_money).toFixed(2)) {
						return parseFloat(this.calculateData.member_account.balance_total).toFixed(2);
					} else {
						return parseFloat(this.calculateData.order_money).toFixed(2);
					}
				}
			},
			// 门店列表
			storeList() {
				return this.getStoreList();
			},
			// 门店信息
			storeInfo() {
				let storeList = this.getStoreList();
				if (storeList && this.orderCreateData.delivery && this.orderCreateData.delivery.delivery_type != 'express' && this.storeId) {
					return storeList[this.orderCreateData.delivery.store_id];
				}
				return null;
			}
		},
		watch: {
			storeToken: function(nVal, oVal) {
				this.payment();
			},
			deliveryTime: function(nVal) {
				if (!nVal) this.$refs.timePopup.refresh();
			},
			location: function(nVal) {
				if (nVal) {
					this.orderCreateData.latitude = nVal.latitude;
					this.orderCreateData.longitude = nVal.longitude;
					this.payment();
				}
			},
			calculateGoodsData(nVal) {
				if (nVal && nVal.config.local && nVal.delivery.local.info.time_is_open && !this.deliveryTime) this.localtime('no');
			}
		},
		methods: {
			/**
			 * 父级页面onShow调用
			 */
			pageShow() {
				if (uni.getStorageSync('addressBack')) {
					uni.removeStorageSync('addressBack');
					this.payment();
				}
			},
			/**
			 * 获取订单结算数据
			 */
			payment() {
				this.$api.sendRequest({
					url: this.api.payment,
					data: this.orderCreateData,
					success: res => {
						if (res.code == 0 && res.data) {
							let data = res.data;

							// #ifdef MP-WEIXIN
							var scene = uni.getStorageSync('is_test') ? 1175 : wx.getLaunchOptionsSync().scene;
							if ([1175, 1176, 1177, 1191, 1195].indexOf(scene) != -1 && data.delivery.express_type) {
								data.delivery.express_type = data.delivery.express_type.filter(item => item.name == 'express');
							}
							// #endif

							// 配送方式
							if (data && data.delivery.express_type && data.delivery.express_type.length) {
								let deliveryStorage = uni.getStorageSync('delivery');
								let delivery = data.delivery.express_type[0];
								if (deliveryStorage) {
									data.delivery.express_type.forEach(item => {
										if (item.name == deliveryStorage.delivery_type) {
											delivery = item;
										}
										if (item.name == 'local') this.localConfig = item;
										if (item.name == 'store') this.storeConfig = item;
									});
								}

								// 配送方式
								this.selectDeliveryType(delivery, false, data.member_account);

								if (
									uni.getStorageSync('deliveryTime') &&
									uni.getStorageSync('deliveryTime')['delivery_type'] &&
									uni.getStorageSync('deliveryTime')['delivery_type'] == this.orderCreateData.delivery.delivery_type
								) {
									this.deliveryTime = uni.getStorageSync('deliveryTime')['deliveryTime'];
									this.orderCreateData.delivery.buyer_ask_delivery_time = uni.getStorageSync('deliveryTime')['buyer_ask_delivery_time'];
								}
							}
							// 地址、手机号
							if (data.is_virtual) {
								this.orderCreateData.delivery.member_address = {
									name: data.member_account.nickname,
									mobile: data.member_account.mobile ?? ''
								};
							}
							//记录订单key
							this.orderCreateData.order_key = data.order_key;
							// 处理表单数据
							data = this.handleGoodsFormData(data);

							this.paymentData = data;
							this.$forceUpdate();
							this.calculate();
						} else {
							// this.$util.showToast({
							// 	title: res.message
							// });
							// setTimeout(() => {
							// 	this.$util.redirectTo('/pages/index/index');
							// }, 1000)
						}
					}
				});
			},
			/**
			 * 处理商品表单数据
			 * @param {Object} data
			 */
			handleGoodsFormData(data) {
				let goodsFormData = uni.getStorageSync('goodFormData');
				data.goods_list.forEach(item => {
					if (item.goods_form) {
						let formData = {};
						if (item.form_data) {
							item.form_data.map(formIem => {
								formData[formIem.id] = formIem;
							});
						} else if (goodsFormData && goodsFormData.goods_id == item.goods_id) {
							goodsFormData.form_data.map(formIem => {
								formData[formIem.id] = formIem;
							});
						}
						if (Object.keys(formData).length) {
							item.goods_form.json_data.forEach(formIem => {
								if (formData[formIem.id]) {
									formIem.val = formData[formIem.id].val;
								}
							});
						}
					}
				});
				return data;
			},
			/**
			 * 订单创建
			 */
			calculate() {
				this.$api.sendRequest({
					url: this.api.calculate,
					data: this.handleCreateData(),
					success: res => {
						if (this.$refs.loadingCover && this.$refs.loadingCover.isShow) this.$refs.loadingCover.hide();
						if (res.code == 0 && res.data) {
							this.calculateData = res.data;
							if (res.data.delivery) {
								if (res.data.delivery.delivery_type == 'express') this.memberAddress = res.data.delivery.member_address;
								if (res.data.delivery.delivery_type == 'local') this.localMemberAddress = res.data.delivery.member_address;
							}
							this.$forceUpdate();
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			},
			/**
			 * 订单创建
			 */
			create() {
				if (!this.verify() || this.isRepeat) return;
				this.isRepeat = true;
				uni.showLoading({
					title: ''
				});
				this.$api.sendRequest({
					url: this.api.create,
					data: this.handleCreateData(),
					success: res => {
						uni.hideLoading();
						if (res.code == 0) {
							this.outTradeNo = res.data;
							uni.removeStorageSync('deliveryTime');
							uni.removeStorageSync('goodFormData');
							if (this.calculateData.pay_money == 0) {
								// #ifdef MP-WEIXIN
								if (this.paymentData.is_virtual || this.orderCreateData.delivery.delivery_type == 'store') {
									this.$util.subscribeMessage('ORDER_VERIFY_OUT_TIME,VERIFY_CODE_EXPIRE,VERIFY');
								}
								// #endif

								this.$api.sendRequest({
									url: '/api/pay/info',
									data: {
										out_trade_no: this.outTradeNo
									},
									success: res => {
										if (res.code >= 0 && res.data && res.data.order_id > 0) {
											this.$util.redirectTo('/pages/order/detail', {
												order_id: res.data.order_id
											}, 'redirectTo');
										} else {
											this.$util.redirectTo('/pages/order/list', {}, 'redirectTo');
										}
									},
									fail: res => {
										this.$util.redirectTo('/pages/order/list', {}, 'redirectTo');
									}
								});
							} else {
								this.openChoosePayment();
							}
						} else {
							this.$util.showToast({
								title: res.message
							});
							this.isRepeat = false;
						}
					}
				});
			},
			/**
			 * 处理订单计算、创建传参
			 */
			handleCreateData() {
				let data = this.$util.deepClone(this.orderCreateData);
				// 订单表单
				if (this.$refs.form) {
					data.form_data = {
						form_id: this.paymentData.system_form.id,
						form_data: this.$util.deepClone(this.$refs.form.formData)
					};
				}
				// 商品表单
				if (this.$refs.goodsForm) {
					if (!data.form_data) data.form_data = {};
					data.form_data.goods_form = {};
					this.$refs.goodsForm.forEach(item => {
						data.form_data.goods_form[item._props.customAttr.sku_id] = {
							form_id: item._props.customAttr.form_id,
							form_data: this.$util.deepClone(item.formData)
						};
					});
				}
				Object.keys(data).forEach(key => {
					let item = data[key];
					if (typeof item == 'object') data[key] = JSON.stringify(item);
				});
				if (data.member_address && this.orderCreateData.delivery && this.orderCreateData.delivery.delivery_type != 'store') delete data.member_address;
				return data;
			},
			/**
			 * 打开支付弹窗
			 */
			openChoosePayment() {
				uni.setStorageSync('paySource', '');
				// #ifdef MP
				if (this.paymentData.is_virtual) {
					this.$util.subscribeMessage('ORDER_URGE_PAYMENT,ORDER_PAY');
				} else {
					switch (this.orderCreateData.delivery.delivery_type) {
						case 'express': //物流配送
							this.$util.subscribeMessage('ORDER_URGE_PAYMENT,ORDER_PAY,ORDER_DELIVERY');
							break;
						case 'store': //门店自提
							this.$util.subscribeMessage('ORDER_URGE_PAYMENT,ORDER_PAY');
							break;
						case 'local': //同城配送
							this.$util.subscribeMessage('ORDER_URGE_PAYMENT,ORDER_PAY,ORDER_DELIVERY');
							break;
					}
				}
				// #endif
				this.$refs.choosePaymentPopup.getPayInfo(this.outTradeNo);
			},
			verify() {
				if (this.paymentData.is_virtual == 1) {
					if (!this.orderCreateData.member_address.mobile.length) {
						this.$util.showToast({
							title: '请输入您的手机号码'
						});
						return false;
					}
					if (!this.$util.verifyMobile(this.orderCreateData.member_address.mobile)) {
						this.$util.showToast({
							title: '请输入正确的手机号码'
						});
						return false;
					}
				} else {
					if (!this.orderCreateData.delivery || !this.orderCreateData.delivery.delivery_type) {
						this.$util.showToast({
							title: '商家未设置配送方式'
						});
						return false;
					}
					if (
						(this.orderCreateData.delivery.delivery_type == 'express' && !this.memberAddress) ||
						(this.orderCreateData.delivery.delivery_type == 'local' && !this.localMemberAddress)
					) {
						this.$util.showToast({
							title: '请先选择您的收货地址'
						});
						return false;
					}

					if (this.orderCreateData.delivery.delivery_type == 'store') {
						if (!this.orderCreateData.delivery.store_id) {
							this.$util.showToast({
								title: '没有可提货的门店,请选择其他配送方式'
							});
							return false;
						}
						if (!this.orderCreateData.member_address.mobile) {
							this.$util.showToast({
								title: '请输入预留手机'
							});
							return false;
						}

						if (!this.$util.verifyMobile(this.orderCreateData.member_address.mobile)) {
							this.$util.showToast({
								title: '请输入正确的手机号'
							});
							return false;
						}
						if (!this.deliveryTime) {
							this.$util.showToast({
								title: '请选择提货时间'
							});
							return false;
						}
					}

					if (this.orderCreateData.delivery.delivery_type == 'local') {
						if (!this.orderCreateData.delivery.store_id) {
							this.$util.showToast({
								title: '没有可配送的门店,请选择其他配送方式'
							});
							return false;
						}
						if (this.calculateGoodsData.config.local.is_use && this.calculateGoodsData.delivery.local.info.time_is_open == 1 && !this.deliveryTime) {
							this.$util.showToast({
								title: '请选择送达时间'
							});
							return false;
						}
					}
				}

				if (this.$refs.goodsForm) {
					let formVerify = true;
					for (let i = 0; i < this.$refs.goodsForm.length; i++) {
						let item = this.$refs.goodsForm[i];
						formVerify = item.verify();
						if (!formVerify) {
							break;
						}
					}
					if (!formVerify) return false;
				}
				if (this.paymentData.system_form) {
					let formVerify = this.$refs.form.verify();
					if (!formVerify) return false;
				}
				return true;
			},
			/**
			 * 选择收货地址
			 */
			selectAddress() {
				var params = {
					back: this.$util.getCurrentRoute().path,
					local: 0,
					type: 1
				};
				// 外卖配送需要定位地址
				if (this.orderCreateData.delivery.delivery_type == 'local') {
					params.local = 1;
					params.type = 2;
				}
				this.$util.redirectTo('/pages_tool/member/address', params);
			},
			/**
			 * 选择配送方式
			 * @param {Object} data
			 */
			selectDeliveryType(data, calculate = true, member_account = null) {
				if (this.orderCreateData.delivery && this.orderCreateData.delivery.delivery_type == data.name) return;
					this.orderCreateData.delivery.buyer_ask_delivery_time = {
					start_date: '',
					end_date: ''
				};
				let delivery = {
					delivery_type: data.name,
					delivery_type_name: data.title
				};
				// 如果是门店配送
				if (data.name == 'store' || data.name == 'local') {
					if (data.store_list[0]) {
						delivery.store_id = data.store_list[0].store_id;
					}
					this.storeId = delivery.store_id ? delivery.store_id : 0;
					if (!this.orderCreateData.member_address) {
						if (this.paymentData) {
							this.orderCreateData.member_address = {
								name: this.paymentData.member_account.nickname,
								mobile: this.paymentData.member_account.mobile
							};
						} else if (member_account) {
							this.orderCreateData.member_address = {
								name: member_account.nickname,
								mobile: member_account.mobile
							};

						}
					}
				}
				this.$set(this.orderCreateData, 'delivery', delivery);
				this.orderCreateData.delivery.buyer_ask_delivery_time = {
					start_date: '',
					end_date: ''
				};
				this.deliveryTime = '';
				uni.removeStorageSync('deliveryTime');
				uni.setStorageSync('delivery', delivery);

				// 配送方式不为门店配送时
				if (this.orderCreateData.delivery.delivery_type != 'express' && !this.location) this.$util.getLocation();
				if (calculate) this.calculate();
				
				if (data.name == 'store') this.storetime('no');
				if (data.name == 'local') this.localtime('no');
			},
			/**
			 * 图片错误
			 * @param {Object} index
			 */
			imageError(index) {
				this.paymentData.goods_list[index].sku_image = this.$util.getDefaultImage().goods;
				this.$forceUpdate();
			},
			/**
			 * 选择门店
			 * @param {Object} data
			 */
			selectPickupPoint(data) {
				if (data.store_id != this.storeId) {
					this.storeId = data.store_id;
					this.orderCreateData.delivery.store_id = data.store_id;
					this.calculate();
					this.resetDeliveryTime();
					// 存储所选门店
					let delivery = uni.getStorageSync('delivery');
					delivery.store_id = data.store_id;
					uni.setStorageSync('delivery', delivery)
				}
				this.$refs.deliveryPopup.close();
			},
			/**
			 * 重置提货时间
			 */
			resetDeliveryTime() {
				this.orderCreateData.delivery.buyer_ask_delivery_time = {
					start_date: '',
					end_date: ''
				};
				this.deliveryTime = '';
				uni.removeStorageSync('deliveryTime');
			},
			/**
			 * 门店
			 */
			// storetime(type = '') {
			// 	console.log(this.calculateData,'storetime')
			// 	if (this.calculateData && this.calculateData.delivery.delivery_store_info) {
			// 		let data = this.$util.deepClone(this.storeInfo);
			// 		if (data.delivery_time) {
			// 			data.delivery_time = JSON.parse(data.delivery_time);
			// 			data.end_time = data.delivery_time[(data.delivery_time.length - 1)].end_time;
			// 		} else {
			// 			data.delivery_time = [{
			// 				start_time: data.start_time,
			// 				end_time: data.end_time
			// 			}]
			// 		}
			// 		let obj = {
			// 			delivery: this.orderCreateData.delivery,
			// 			dataTime: data
			// 		};
			// 		this.$refs.timePopup.open(obj, type);
			// 		this.$forceUpdate();
			// 	}
			// },
			storetime(type = '') {
				if (this.storeInfo) {
					let data = this.$util.deepClone(this.storeInfo);
					data.delivery_time = typeof data.delivery_time == 'string' && data.delivery_time ? JSON.parse(data.delivery_time) : data.delivery_time;
					if (!data.delivery_time || data.delivery_time.length == undefined && !data.delivery_time.length) {
						data.delivery_time = [{
							start_time: data.start_time,
							end_time: data.end_time
						}]
					}
					let obj = {
						delivery: this.orderCreateData.delivery,
						dataTime: data
					};
					this.$refs.timePopup.open(obj, type);
					this.$forceUpdate();
				}
			},
			/**
			 * 选择配送时间、自提时间
			 * @param {Object} data
			 */
			selectPickupTime(data) {

				this.deliveryTime = data.data.month + '(' + data.data.time + ')';
				this.orderCreateData.delivery.buyer_ask_delivery_time = {
					start_date: data.data.start_date,
					end_date: data.data.end_date
				};

				//将时间缓存，避免切换地址时重置
				uni.setStorageSync('deliveryTime', {
					'deliveryTime': this.deliveryTime,
					'buyer_ask_delivery_time': this.orderCreateData.delivery.buyer_ask_delivery_time,
					'delivery_type': this.orderCreateData.delivery.delivery_type
				});
			},
			openPopup(ref) {
				this.tempData = this.$util.deepClone(this.orderCreateData);
				this.$refs[ref].open();
			},
			closePopup(ref) {
				this.orderCreateData = this.$util.deepClone(this.tempData);
				this.$refs[ref].close();
				this.tempData = null;
			},
			/**
			 * 保存留言
			 */
			saveBuyerMessage() {
				this.calculate();
				this.$refs.buyerMessagePopup.close();
			},
			/**
			 * 支付弹窗关闭
			 */
			payClose() {
				this.$util.redirectTo('/pages/order/detail', {
					order_id: this.$refs.choosePaymentPopup.payInfo.order_id
				}, 'redirectTo');
			},
			/**
			 * 同城配送送达时间
			 */
			localtime(type = '') {
				if (this.calculateGoodsData && this.calculateGoodsData.config.local) {
					let data = this.$util.deepClone(this.calculateGoodsData.delivery.local.info);
					if (Object.keys(data).length) {
						if (data.delivery_time) {
							data.end_time = data.delivery_time[(data.delivery_time.length - 1)].end_time;
						}
				
						let obj = {
							delivery: this.orderCreateData.delivery,
							dataTime: data
						};
				
						this.$refs.timePopup.open(obj, type);
					}
				
				}
	
			},
			editForm(index) {
				this.tempFormData = {
					index: index,
					json_data: this.$util.deepClone(this.goodsData.goods_list[index].goods_form.json_data)
				};
				this.$refs.editFormPopup.open();
			},
			saveForm() {
				if (this.$refs.tempForm.verify()) {
					this.$set(this.paymentData.goods_list[this.tempFormData.index].goods_form, 'json_data', this.$refs.tempForm.formData);
					this.$refs.editFormPopup.close();
				}
			},
			back() {
				uni.navigateBack({
					delta: 1
				});
			},
			getStoreList() {
				let storeList = null;
				if (this.orderCreateData.delivery) {
					if (this.orderCreateData.delivery.delivery_type == 'local' && this.localConfig) {
						storeList = this.localConfig.store_list;
						storeList = storeList.reduce((res, item) => {
							return {
								...res,
								[item.store_id]: item
							};
						}, {});
					}
					if (this.orderCreateData.delivery.delivery_type == 'store' && this.storeConfig) {
						storeList = this.storeConfig.store_list;
						storeList = storeList.reduce((res, item) => {
							return {
								...res,
								[item.store_id]: item
							};
						}, {});
					}
				}
				return storeList;
			}
		},
		filters: {
			// 金额格式化输出
			moneyFormat(money) {
				return parseFloat(money).toFixed(2);
			}
		}
	};
</script>

<style lang="scss">
	@import '@/common/css/order_parment.scss';
</style>

<style scoped lang="scss">
	/deep/ .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
		background: none;
		max-height: unset !important;
		overflow-y: hidden !important;
	}

	/deep/ .uni-popup__wrapper {
		border-radius: 20rpx 20rpx 0 0;
	}

	/deep/ .uni-popup {
		z-index: 8;
	}

	.sava-btn,
	.submit-btn {
		width: 100% !important;
	}
</style>