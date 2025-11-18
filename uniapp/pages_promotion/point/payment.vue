<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="order-container" :class="{ 'safe-area': isIphoneX }">
		<!-- #ifdef MP-WEIXIN -->
		<view class="payment-navbar" :style="{
				'padding-top': menuButtonBounding.top + 'px',
				height: menuButtonBounding.height + 'px'
			}">
			<view class="nav-wrap">
				<text class="iconfont icon-back_light" @click="back"></text>
				<view class="navbar-title">确认订单</view>
			</view>
		</view>
		<view class="payment-navbar-block" :style="{ height: menuButtonBounding.bottom + 'px' }"></view>
		<!-- #endif -->

		<scroll-view scroll-y="true" class="order-scroll-container">
			<view class="payment-navbar-block"></view>
			<!-- 选择地址 -->
			<template v-if="orderPaymentData.exchange_info.type == 1 && orderPaymentData.is_virtual == 0">
				<!-- 配送方式 -->
				<view class="delivery-mode" v-if="orderCreateData.delivery &&orderPaymentData.delivery.express_type.length > 1">
					<view class="action">
						<view v-for="(deliveryItem, deliveryIndex) in orderPaymentData.delivery.express_type"
							:key="deliveryIndex"
							:class="{active: deliveryItem.name == orderCreateData.delivery.delivery_type}"
							@click="selectDeliveryType(deliveryItem)">
							{{ deliveryItem.title }}
							<!-- 外圆角 -->
							<view class="out-radio"></view>
						</view>
					</view>
				</view>

				<view class="address-box" :class="{'not-delivery-type': orderPaymentData.delivery.express_type.length <= 1}" v-if="orderPaymentData.delivery.delivery_type != 'store'">
					<block v-if="storeInfo.storeList.length > 1 && orderPaymentData.delivery.delivery_type == 'local'">
						<view class="local-delivery-store" v-if="Object.keys(storeInfo.currStore).length" @click="$refs.deliveryPopup.open()">
							<view class="info">
								由 <text class="store-name">{{ storeInfo.currStore.store_name }}</text> 提供配送
							</view>
							<view class="cell-more">
								<text>点击切换</text>
								<text class="iconfont icon-right"></text>
							</view>
						</view>
						<view v-else class="local-delivery-store">
							<view class="info">
								<text class="store-name">您的附近没有可配送的门店，请选择其他配送方式</text>
							</view>
						</view>
					</block>
					<view class="info-wrap" :class="{'local': orderPaymentData.delivery.delivery_type == 'local'}" v-if="orderCreateData.member_address" @click="selectAddress">
						<view class="content">
							<text class="name font-size-base">{{ orderCreateData.member_address.name ? orderCreateData.member_address.name : '' }}</text>
							<text class="font-size-base mobile">{{ orderCreateData.member_address.mobile ? orderCreateData.member_address.mobile : '' }}</text>
							<text class="cell-more iconfont icon-right"></text>
							<view class="desc-wrap">
								{{ orderCreateData.member_address.full_address ? orderCreateData.member_address.full_address : '' }}
								{{ orderCreateData.member_address.address ? orderCreateData.member_address.address : '' }}
							</view>
						</view>
					</view>
					<view class="empty-wrap" v-else @click="selectAddress">
						<view class="info">请设置收货地址</view>
						<view class="cell-more">
							<view class="iconfont icon-right"></view>
						</view>
					</view>

					<!-- 外卖配送 -->
					<block v-if="orderPaymentData.delivery.delivery_type == 'local'">
						<view class="local-box" v-if="orderPaymentData.config.local.is_use && orderPaymentData.delivery.local.info && orderPaymentData.delivery.local.info.time_is_open == 1">
							<view class="pick-block" @click="localtime">
								<view class="font-size-base">送达时间</view>
								<view class="time-picker">
									<text :class="{'color-tip': !orderCreateData.buyer_ask_delivery_title}">{{ orderCreateData.buyer_ask_delivery_title ? orderCreateData.buyer_ask_delivery_title : '请选择送达时间' }}</text>
									<text class="iconfont icon-right cell-more"></text>
								</view>
							</view>
						</view>
					</block>
					<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')"></image>
				</view>

				<view class="store-box" :class="{'not-delivery-type': orderPaymentData.delivery.express_type.length <= 1}" v-if="orderPaymentData.delivery.delivery_type == 'store' && storeInfo.currStore">
					<block v-if="storeInfo.currStore">
						<view @click="openSiteDelivery" class="store-info">
							<view class="store-address-info">
								<view class="info-wrap">
									<view class="title">
										<text>{{ storeInfo.currStore.store_name }}</text>
										<view class="cell-more iconfont icon-right"></view>
									</view>
									<view class="store-detail">
										<view v-if="storeInfo.currStore.open_date">
											营业时间：{{ storeInfo.currStore.open_date }}</view>
										<view>地址：{{ storeInfo.currStore.full_address }}
											{{ storeInfo.currStore.address }}
										</view>
									</view>
								</view>
							</view>
						</view>
						<view class="mobile-wrap store-mobile">
							<view class="form-group">
								<text class="text">姓名</text>
								<input type="text" placeholder-class="color-tip placeholder" class="input" v-model="member_address.name" disabled />
							</view>
						</view>
						<view class="mobile-wrap store-mobile">
							<view class="form-group">
								<text class="text">预留手机</text>
								<input type="number" maxlength="11" placeholder="请输入您的手机号码" placeholder-class="color-tip placeholder" class="input" v-model="member_address.mobile" />
							</view>
						</view>
						<view class="store-time">
							<view class="left">自提时间</view>
							<view class="right" @click="storetime">
								{{orderCreateData.buyer_ask_delivery_title}}
								<text class="iconfont icon-right"></text>
							</view>
						</view>
					</block>
					<view v-else class="empty">当前无自提门店，请选择其它配送方式</view>

					<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')"></image>
				</view>
			</template>

			<!-- 虚拟商品展示手机号 -->
			<view class="mobile-wrap" v-if="orderPaymentData.is_virtual == 1 && orderCreateData.member_address">
				<view class="tips color-base-text">
					<text class="iconfont icon-gantanhao"></text>
					购买虚拟类商品需填写手机号，方便商家与您联系
				</view>
				<view class="form-group">
					<text class="iconfont icon-dianhua2"></text>
					<text class="text">手机号码</text>
					<input type="number" maxlength="11" placeholder="请输入您的手机号码" placeholder-class="color-tip placeholder" class="input" v-model="orderCreateData.member_address.mobile" />
				</view>
			</view>

			<!-- 店铺 -->
			<view class="site-wrap" :class="orderPaymentData.exchange_info.type == 2 || orderPaymentData.exchange_info.type == 3 ? 'margin-top' : ''">
				<view class="site-body">
					<view class="goods-wrap">
						<view class="goods-img" v-if="orderPaymentData.exchange_info.type == 2">
							<image :src="orderPaymentData.exchange_info.image ? $util.img(orderPaymentData.exchange_info.image) : $util.img('public/uniapp/point/coupon.png')" @error="imageError()" mode="aspectFill"/>
						</view>
						<view class="goods-img" v-else-if="orderPaymentData.exchange_info.type == 3">
							<image :src="orderPaymentData.exchange_info.image ? $util.img(orderPaymentData.exchange_info.image) : $util.img('public/uniapp/point/hongbao.png')" @error="imageError()" mode="aspectFill"/>
						</view>
						<view class="goods-img" v-else>
							<image :src="$util.img(orderPaymentData.exchange_info.image)" @error="imageError()" mode="aspectFill"/>
						</view>
						<view class="goods-info">
							<view class="goods-name">{{ orderPaymentData.exchange_info.name }}</view>
							<view class="goods-sub-section">
								<view v-if="orderPaymentData.exchange_info.pay_type == 1" class="color-base-text">
									<text class="goods-price">{{ orderPaymentData.exchange_info.point }}</text>
									<text class="unit">积分</text>
									<template v-if="orderPaymentData.exchange_info.price != '0.00'">
										<text class="unit">+{{ $lang('common.currencySymbol') }}</text>
										<text class="goods-price">{{ orderPaymentData.exchange_info.price }}</text>
									</template>
								</view>
								<view>
									<text class="font-size-tag">x</text>
									<text class="font-size-base">{{ orderPaymentData.goods_num }}</text>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="site-footer">
					<view class="order-cell">
						<text class="tit">买家留言</text>
						<view class="box"><input type="text" placeholder="留言前建议先与商家协调一致" placeholder-class="color-tip" v-model="orderCreateData.buyer_message" /></view>
					</view>
				</view>
			</view>

			<!-- 金额 -->
			<view class="order-money">
				<view class="order-cell">
					<text class="tit">所需积分</text>
					<view class="box">
						<text class="money">{{ orderPaymentData.point }}</text>
						<text class="unit">积分</text>
					</view>
				</view>
				<view class="order-cell" v-if="orderPaymentData.exchange_info.type == 1 && orderPaymentData.delivery_money > 0">
					<text class="tit">运费</text>
					<view class="box">
						<text class="unit">{{ $lang('common.currencySymbol') }}</text>
						<text class="money">{{ orderPaymentData.delivery_money | moneyFormat }}</text>
					</view>
				</view>

			</view>

			<view class="error-message" v-if="orderPaymentData.delivery && orderPaymentData.delivery.delivery_type == 'local' && orderPaymentData.delivery && orderPaymentData.delivery.error && orderPaymentData.delivery.error !== ''">
				{{orderPaymentData.delivery.error_msg}}
			</view>
			<view class="order-submit" :class="{ 'bottom-safe-area': isIphoneX }">
				<view class="order-settlement-info">
					<text class="font-size-base color-tip margin-right">共{{ orderPaymentData.goods_num }}件</text>
					<text class="font-size-base">合计：</text>
					<text class="color-base-text money">{{ orderPaymentData.point }}</text>
					<text class="color-base-text unit">积分</text>
					<template v-if="orderPaymentData.exchange_info.type == 1 && orderPaymentData.order_money > 0">
						<text class="color-base-text unit">+{{ $lang('common.currencySymbol') }}</text>
						<text class="color-base-text money">{{ orderPaymentData.order_money | moneyFormat }}</text>
					</template>
				</view>
				<view class="submit-btn">
					<button v-if="createBtn()" type="primary" class="mini" size="mini" @click="openChoosePayment()">提交订单</button>
					<button v-else class="no-submit mini" size="mini">
						<block v-if="orderPaymentData.delivery && orderPaymentData.delivery.delivery_type == 'local' && orderPaymentData.delivery && orderPaymentData.delivery.error && orderPaymentData.delivery.start_money > orderPaymentData.price">
							差{{ orderPaymentData.delivery.start_money-orderPaymentData.price | moneyFormat }}起送
						</block>
						<block v-else >提交订单</block>
					</button>
				</view>
			</view>
			<div class="order-submit-block"></div>

			<!-- 门店列表弹窗 -->
			<uni-popup ref="deliveryPopup" type="bottom">
				<view class="delivery-popup popup">
					<view class="popup-header">
						<text class="tit">已为您甄选出附近所有相关门店</text>
						<text class="iconfont icon-close" @click="closePopup('deliveryPopup')"></text>
					</view>
					<view class="popup-body store-popup" :class="{ 'safe-area': isIphoneX }">
						<view class="delivery-content">
							<view class="item-wrap" v-for="(item, index) in storeInfo.storeList" :key="index" @click="selectPickupPoint(item)">
								<view class="detail">
									<view class="name" :class="item.store_id == orderPaymentData.delivery.store_id ? 'color-base-text' : ''">
										<text>{{ item.store_name }}</text>
										<text v-if="item.distance">({{ item.distance }}km)</text>
									</view>
									<view class="info">
										<view :class="item.store_id == orderPaymentData.delivery.store_id ? 'color-base-text' : ''" class="font-size-goods-tag">
											营业时间：{{ item.open_date }}
										</view>
										<view :class="item.store_id == orderPaymentData.delivery.store_id ? 'color-base-text' : ''" class="font-size-goods-tag">
											地址：{{ item.full_address }}{{ item.address }}
										</view>
									</view>
								</view>
								<view class="icon" v-if="item.store_id == orderPaymentData.delivery.store_id"><text class="iconfont icon-yuan_checked color-base-text"></text></view>
							</view>
							<view v-if="!storeInfo.storeList" class="empty">所选择收货地址附近没有可以自提的门店</view>
						</view>
					</view>
				</view>
			</uni-popup>

		</scroll-view>

		<!-- 选择支付方式弹窗 -->
		<ns-payment ref="choosePaymentPopup" :payMoney="orderPaymentData.order_money" @confirm="orderCreate"></ns-payment>

		<loading-cover ref="loadingCover"></loading-cover>

		<!-- 门店自提、同城配送时间选择 -->
		<ns-select-time @selectTime='selectTime' ref="TimePopup"></ns-select-time>
	</view>
</template>

<script>
	import payment from './public/js/payment.js';
	import uniPopup from '@/components/uni-popup/uni-popup.vue';

	export default {
		components: {
			uniPopup,
		},
		mixins: [payment]
	};
</script>

<style lang="scss">
	@import "@/common/css/order_parment.scss";
</style>
<style scoped>
	/deep/ .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
		background: none;
		max-height: unset !important;
		overflow-y: hidden !important;
	}

	>>>.uni-popup__wrapper {
		border-radius: 20rpx 20rpx 0 0;
	}

	>>>.uni-popup {
		z-index: 8;
	}
</style>