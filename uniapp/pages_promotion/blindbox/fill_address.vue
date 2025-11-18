<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="order-container" :class="{ 'safe-area': isIphoneX }">
		<!-- #ifdef MP -->
		<view class="payment-navbar" :style="{
				'padding-top': menuButtonBounding.top + 'px',
				height: menuButtonBounding.height + 'px'
			}">
			<view class="nav-wrap">
				<text class="iconfont icon-back_light" @click="back"></text>
				<view class="navbar-title">填写收货信息</view>
			</view>
		</view>
		<view class="payment-navbar-block" :style="{ height: menuButtonBounding.bottom + 'px' }"></view>
		<!-- #endif -->

		<scroll-view scroll-y="true" class="order-scroll-container">
			<view class="payment-navbar-block"></view>
			<template v-if="goodsData">
				<template v-if="orderPaymentData.is_virtual == 0">
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
						<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')"></image>
					</view>

					<view class="address-box" :class="{ 'not-delivery-type': goodsData.delivery.express_type.length <= 1 }" v-if="orderCreateData.delivery.delivery_type == 'local'">
						<view v-if="localMemberAddress">
							<block v-if="storeInfo.storeList && Object.keys(storeInfo.storeList).length > 1">
								<view class="local-delivery-store" v-if="storeInfo.currStore" @click="$refs.deliveryPopup.open()">
									<view class="info">
										由
										<text class="store-name">{{ storeInfo.currStore.store_name }}</text>
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
							<view class="local-box" v-if="orderPaymentData.config.local && orderPaymentData.config.local.is_use && orderPaymentData.delivery.local && orderPaymentData.delivery.local.info.time_is_open == 1">
								<view class="pick-block" @click="localtime('')">
									<view class="title font-size-base">送达时间</view>
									<view class="time-picker">
										<text :class="{ 'color-tip': !orderCreateData.buyer_ask_delivery_title }">
											{{ orderCreateData.buyer_ask_delivery_title ? orderCreateData.buyer_ask_delivery_title : '请选择送达时间' }}
										</text>
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

						<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')"></image>
					</view>

					<!-- 门店信息 -->
					<view class="store-box" :class="{ 'not-delivery-type': goodsData.delivery.express_type.length <= 1 }" v-if="orderCreateData.delivery.delivery_type == 'store'">
						<block v-if="storeInfo.currStore">
							<view @click="$refs.deliveryPopup.open()" class="store-info">
								<view class="store-address-info">
									<view class="info-wrap">
										<view class="title">
											<text>{{ storeInfo.currStore.store_name }}</text>
										</view>
										<view class="store-detail">
											<view v-if="storeInfo.currStore.open_date">
												营业时间：{{ storeInfo.currStore.open_date }}</view>
											<view class="address">{{ storeInfo.currStore.full_address }}
												{{ storeInfo.currStore.address }}
											</view>
										</view>
									</view>
									<view class="cell-more iconfont icon-right"></view>
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
							<view class="store-time" @click="storetime('')">
								<view class="left">提货时间</view>
								<view class="right">
									<text :class="{ 'color-tip': !orderCreateData.buyer_ask_delivery_title }">
										{{ orderCreateData.buyer_ask_delivery_title ? orderCreateData.buyer_ask_delivery_title : '请选择提货时间' }}
									</text>
									<text class="iconfont icon-right"></text>
								</view>
							</view>
						</block>
						<view v-else class="empty">当前无自提门店，请选择其它配送方式</view>
						<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')"></image>
					</view>
				</template>

				<view class="site-wrap order-goods">
					<view class="site-body">
						<!-- 商品 -->
						<view class="goods-item" v-for="(goodsItem, goodsIndex) in goodsData.goods_list" :key="goodsIndex">
							<view class="goods-wrap">
								<view class="goods-img" @click="$util.redirectTo('/pages/goods/detail', { goods_id: goodsItem.goods_id })">
									<image :src="$util.img(goodsItem.sku_image, { size: 'mid' })" @error="imageError(goodsIndex)" mode="aspectFill"></image>
								</view>
								<view class="goods-info">
									<view class="top-wrap">
										<view @click="$util.redirectTo('/pages/goods/detail', { goods_id: goodsItem.goods_id })" class="goods-name">{{ goodsItem.sku_name }}</view>
										<view class="sku" v-if="goodsItem.sku_spec_format">
											<view class="goods-spec">
												<block v-for="(x, i) in goodsItem.sku_spec_format" :key="i">
													<view>{{ x.spec_value_name }}</view>
												</block>
											</view>
										</view>
										<block v-if="goodsItem.is_virtual == 0">
											<view class="error-tips" v-if="
													orderCreateData.delivery &&
														goodsItem.support_trade_type &&
														goodsItem.support_trade_type.indexOf(orderCreateData.delivery.delivery_type) == -1
												">
												<text class="iconfont icon-gantanhao"></text>
												<text>该商品不支持{{ orderCreateData.delivery.delivery_type_name }}</text>
											</view>
										</block>
									</view>
									<view class="goods-sub-section">
										<view class="color-base-text">
											<text class="unit price-style small">{{ $lang('common.currencySymbol') }}</text>
											<text class="goods-price price-style large">{{ parseFloat(goodsItem.price).toFixed(2).split('.')[0] }}</text>
											<text class="unit price-style small">.{{ parseFloat(goodsItem.price).toFixed(2).split('.')[1] }}</text>
										</view>
										<view>
											<text class="font-size-tag">x</text>
											<text class="font-size-base">{{ goodsItem.num }}</text>
										</view>
									</view>
								</view>
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

				<view class="order-submit bottom-safe-area">
					<view class="order-settlement-info"></view>
					<view class="submit-btn">
						<button type="primary" class="mini" size="mini" @click="orderCreate()">确定</button>
					</view>
				</view>
				<view class="order-submit-block"></view>

				<ns-select-time @selectTime="selectPickupTime" ref="timePopup"></ns-select-time>
			</template>
		</scroll-view>

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
				<view class="popup-footer" @click="closePopup('buyerMessagePopup')" :class="{ 'bottom-safe-area': isIphoneX }">
					<view class="confirm-btn color-base-bg">确定</view>
				</view>
			</view>
		</uni-popup>

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
							<view class="icon" v-if="item.store_id == orderPaymentData.delivery.store_id">
								<text class="iconfont icon-yuan_checked color-base-text"></text>
							</view>
						</view>
						<view v-if="!storeInfo.storeList" class="empty">所选择收货地址附近没有可以自提的门店</view>
					</view>
				</view>
			</view>
		</uni-popup>

		<loading-cover ref="loadingCover"></loading-cover>
		<ns-login ref="login"></ns-login>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
	import payment from './public/js/payment.js';
	export default {
		mixins: [payment]
	};
</script>

<style lang="scss">
	@import '@/common/css/order_parment.scss';
</style>
<style>
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