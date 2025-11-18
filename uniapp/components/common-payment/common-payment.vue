<template>
	<view class="order-container" :class="{ 'safe-area': isIphoneX }">
		<!-- #ifdef MP-WEIXIN -->
		<view class="payment-navbar" :style="{ 'padding-top': menuButtonBounding.top + 'px', height: menuButtonBounding.height + 'px' }">
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
							<text class="iconfont icongantanhao"></text>
							购买虚拟类商品需填写手机号，方便商家与您联系
						</view>
						<view class="form-group">
							<text class="icon">
								<image :src="$util.img('public/uniapp/order/icon-mobile.png')" mode="widthFix"></image>
							</text>
							<text class="text">手机号码</text>
							<input type="number" maxlength="11" placeholder="请输入您的手机号码" placeholder-class="color-tip placeholder" class="input" v-model="orderCreateData.member_address.mobile" />
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
						<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')"></image>
					</view>

					<view class="address-box" :class="{ 'not-delivery-type': goodsData.delivery.express_type.length <= 1 }" v-if="orderCreateData.delivery.delivery_type == 'local'">
						<view v-if="localMemberAddress">
							<block v-if="storeList && Object.keys(storeList).length > 0">
								<view class="local-delivery-store">
									<view class="info" v-if="storeInfo">
										由
										<text class="store-name">{{ storeInfo.store_name }}</text>
										提供配送
										<view>营业时间：{{ storeInfo.open_date }}</view>
									</view>
									<view class="info" v-else>
										<text class="store-name">超出配送范围，请选择其他门店</text>
									</view>
									<view class="cell-more" v-if="Object.keys(storeList).length > 1" @click="openPopup('deliveryPopup')">
										<text>点击切换</text>
										<text class="iconfont icon-right"></text>
									</view>
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
							<view class="local-box" v-if="calculateGoodsData.config.local && calculateGoodsData.delivery.local.info.time_is_open == 1">
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

						<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')"></image>
					</view>

					<!-- 门店信息 -->
					<view class="store-box" :class="{ 'not-delivery-type': goodsData.delivery.express_type.length <= 1 }" v-if="orderCreateData.delivery.delivery_type == 'store'">
						<block v-if="storeInfo">
							<view @click="openPopup('deliveryPopup')" class="store-info">
								<view class="store-address-info">
									<view class="info-wrap">
										<view class="title">
											<text>{{ storeInfo.store_name }}</text>
										</view>
										<view class="store-detail">
											<view class="close-desc" v-if="storeInfo.status == 0 && storeInfo.close_desc">{{ storeInfo.close_desc }}</view>
											<view v-if="storeInfo.open_date">营业时间：{{ storeInfo.open_date }}</view>
											<view class="address">{{ storeInfo.full_address }} {{ storeInfo.address }}</view>
										</view>
									</view>
									<view class="cell-more iconfont icon-right" v-if="storeList && Object.keys(storeList).length > 1"></view>
								</view>
							</view>
							<view class="mobile-wrap store-mobile" v-if="orderCreateData.member_address">
								<view class="form-group">
									<text class="text">姓名</text>
									<input type="text" placeholder-class="color-tip placeholder" class="input" disabled v-model="orderCreateData.member_address.name" />
								</view>
							</view>
							<view class="mobile-wrap store-mobile" v-if="orderCreateData.member_address">
								<view class="form-group">
									<text class="text">预留手机</text>
									<input type="number" maxlength="11" placeholder="请输入您的手机号码" placeholder-class="color-tip placeholder" class="input" v-model="orderCreateData.member_address.mobile" />
								</view>
							</view>
							<view class="store-time"  v-if="goodsData.jielong_id">
								<view class="left">提货时间</view>
								<view class="right">
									{{ $util.timeStampTurnTime(goodsData.jielong_info.take_start_time,'Y/m/d') }} ~ {{ $util.timeStampTurnTime(goodsData.jielong_info.take_end_time,'Y/m/d') }}
								</view>
							</view>
							
							<view class="store-time" @click="storetime('')" v-else>
								<view class="left">提货时间</view>
								<view class="right">
									{{ deliveryTime }}
									<text class="iconfont icon-right"></text>
								</view>
							</view>
						</block>
						<view v-else class="empty">当前无自提门店，请选择其它配送方式</view>
						<image class="address-line" :src="$util.img('public/uniapp/order/address-line.png')"></image>
					</view>
				</template>

				<!-- 店铺 -->
				<view class="site-wrap order-goods" v-if="calculateGoodsData">
					<view class="site-body">
						<!-- 商品 -->
						<view class="goods-item" v-for="(goodsItem, goodsIndex) in calculateGoodsData.goods_list" :key="goodsIndex">
							<view class="goods-wrap">
								<view class="goods-img" @click="$util.redirectTo('/pages/goods/detail', { goods_id: goodsItem.goods_id })">
									<image :src="$util.img(goodsItem.sku_image, { size: 'mid' })" @error="imageError(goodsIndex)" mode="aspectFill"/>
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
							<view class="member-goods-card order-cell" v-if="calculateGoodsData.goods_list[goodsIndex].member_card_list" @click="selectMemberGoodsCard(goodsIndex)">
								<text class="tit">次卡抵扣</text>
								<view class="box text-overflow">
									<block v-if="calculateGoodsData.goods_list[goodsIndex].card_promotion_money">
										<text class="text">次卡抵扣{{ calculateGoodsData.goods_list[goodsIndex].card_use_num }}张/{{ calculateGoodsData.goods_list[goodsIndex].card_use_num }}次</text>
										<text class="price-font">-￥{{ calculateGoodsData.goods_list[goodsIndex].card_promotion_money | moneyFormat }}</text>
									</block>
									<text class="color-tip" v-else>请选择次卡</text>
								</view>
								<text class="iconfont icon-right"></text>
							</view>
							<view class="goods-form" v-if="goodsData.goods_list[goodsIndex].goods_form" @click="editForm(goodsIndex)">
								<ns-form :data="goodsData.goods_list[goodsIndex].goods_form.json_data" ref="goodsForm" :custom-attr="{ sku_id: goodsItem.sku_id, form_id: goodsData.goods_list[goodsIndex].goods_form.id }"/>
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
					<view class="order-cell">
						<text class="tit">{{ paymentData.system_form.form_name }}</text>
					</view>
					<ns-form :data="paymentData.system_form.json_data" ref="form"/>
				</view>

				<view class="site-wrap" v-if="calculateGoodsData  || promotionInfo || (calculateGoodsData && calculateGoodsData.max_usable_point > 0) || goodsData.invoice">
					<view class="site-footer">
						<view class="order-cell coupon" v-if="modules.indexOf('coupon') != -1">
							<text class="tit">优惠券</text>
							<view class="box text-overflow" @click="openPopup('couponPopup')">
								<template v-if="orderCreateData.coupon && orderCreateData.coupon.coupon_id">
									<text>已使用优惠券，优惠</text>
									<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
									<text class="money price-font">{{ (calculateData && calculateData.coupon_money ? calculateData.coupon_money : 0) | moneyFormat }}</text>
								</template>
								<text v-else>不使用优惠券</text>
							</view>
							<text class="iconfont icon-right"></text>
						</view>
						<view class="order-cell" v-if="promotionInfo">
							<text class="tit">活动优惠</text>
							<view class="box text-overflow" @click="openPopup('promotionPopup')">
								<text>{{ promotionInfo.title }}</text>
							</view>
							<text class="iconfont icon-right"></text>
						</view>
						<view class="order-cell point" v-if="calculateGoodsData && calculateGoodsData.max_usable_point > 0">
							<text class="tit">
								<text>使用{{ parseInt(calculateGoodsData.max_usable_point) }}积分可抵扣</text>
								<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="money price-font">{{ calculateData.point_money | moneyFormat }}</text>
							</text>
							<view class="box"></view>
							<ns-switch class="balance-switch" @change="usePoint" :checked="orderCreateData.is_point == 1"/>
						</view>
						<view class="order-cell order-invoice-cell" v-if="goodsData.invoice.invoice_status == 1">
							<text class="tit">发票</text>
							<view class="box text-overflow" @click="openPopup('invoicePopup')">
								<text v-if="orderCreateData.is_invoice == 1">{{ orderCreateData.invoice_type == 1 ? '纸质' : '电子' }}发票({{ orderCreateData.invoice_content }})</text>
								<text v-else>无需发票</text>
							</view>
							<text class="iconfont icon-right"></text>
						</view>
					</view>
				</view>

				<view class="site-wrap box member-card-wrap" v-if="paymentData.recommend_member_card && Object.keys(paymentData.recommend_member_card).length > 0">
					<view class="head" @click="selectMemberCard">
						<text class="iconfont icon-huiyuan"></text>
						<view class="info">
							开通{{ paymentData.recommend_member_card.level_name }}
							<text>本单预计可省</text>
							<text class="price-color">{{ paymentData.recommend_member_card.discount_money | moneyFormat }}</text>
							<text>元</text>
						</view>
						<text class="iconfont" :class="orderCreateData.is_open_card == 1 ? 'icon-yuan_checked color-base-text' : 'icon-yuan_checkbox'"></text>
					</view>
					<view class="body" v-if="orderCreateData.is_open_card">
						<view class="item" :class="{ 'active color-base-border': item.key == orderCreateData.member_card_unit }" v-for="(item, index) in cardChargeType" :key="index" @click="selectMemberCardUnit(item.key)">
							<view class="title">{{ item.title }}</view>
							<view class="price price-font">{{ $lang('common.currencySymbol') }}{{ parseFloat(item.value) }}/{{ item.unit }}</view>
							<text class="iconfont icon-icon color-base-text price-font identify" v-if="item.key == orderCreateData.member_card_unit"></text>
						</view>
					</view>
				</view>

				<!-- 订单金额 -->
				<template v-if="calculateData">
					<view class="order-money">
						<view class="order-cell">
							<text class="tit">商品金额</text>
							<view class="box">
								<text class="unit color-title price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="money color-title price-font">{{ calculateData.goods_money | moneyFormat }}</text>
							</view>
						</view>
						<view class="order-cell" v-if="calculateData.is_virtual == 0 && calculateData.delivery_money > 0">
							<text class="tit">运费</text>
							<view class="box color-base-text">
								<text class="operator">+</text>
								<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="money price-font">{{ calculateData.delivery_money | moneyFormat }}</text>
							</view>
						</view>
						<view class="order-cell" v-if="orderCreateData.is_invoice && calculateData.invoice_money > 0">
							<text class="tit">
								<text>税费</text>
								<text class="color-base-text font-bold price-font">({{ goodsData.invoice.invoice_rate }}%)</text>
							</text>
							<view class="box color-base-text">
								<text class="operator">+</text>
								<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="money price-font">{{ calculateData.invoice_money | moneyFormat }}</text>
							</view>
						</view>
						<view class="order-cell" v-if="orderCreateData.is_invoice && calculateData.invoice_delivery_money > 0">
							<text class="tit">发票邮寄费</text>
							<view class="box color-base-text">
								<text class="operator">+</text>
								<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="money price-font">{{ calculateData.invoice_delivery_money | moneyFormat }}</text>
							</view>
						</view>
						<view class="order-cell" v-if="calculateData.promotion_money > 0">
							<text class="tit">优惠</text>
							<view class="box color-base-text">
								<text class="operator">-</text>
								<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="money price-font">{{ calculateData.promotion_money | moneyFormat }}</text>
							</view>
						</view>
						<view class="order-cell" v-if="calculateData.coupon_money">
							<text class="tit">优惠券</text>
							<view class="box color-base-text">
								<text class="operator">-</text>
								<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="money price-font">{{ calculateData.coupon_money | moneyFormat }}</text>
							</view>
						</view>
						<view class="order-cell" v-if="calculateData.point_money > 0">
							<text class="tit">积分抵扣</text>
							<view class="box color-base-text">
								<text class="operator">-</text>
								<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="money price-font">{{ calculateData.point_money | moneyFormat }}</text>
							</view>
						</view>
						<view class="order-cell" v-if="calculateData.member_card_money > 0">
							<text class="tit">会员卡</text>
							<view class="box color-base-text">
								<text class="operator">+</text>
								<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="money price-font">{{ calculateData.member_card_money | moneyFormat }}
								</text>
							</view>
						</view>
					</view>

					<view v-if="transactionAgreement.title && transactionAgreement.content" class="agreement">购买前请先阅读<text @click="$refs.agreementPopup.open()">《{{ transactionAgreement.title }}》</text>，下单即代表同意该协议</view>

					<view class="order-submit bottom-safe-area">
						<view class="order-settlement-info">
							<text class="font-size-base color-tip margin-right">共{{ calculateData.goods_num }}件</text>
							<text class="font-size-base">合计：</text>
							<text class=" unit price-font">{{ $lang('common.currencySymbol') }}</text>
							<text class=" money price-font">{{ parseFloat(calculateData.pay_money).toFixed(2).split('.')[0] }}</text>
							<text class=" unit price-font">.{{ parseFloat(calculateData.pay_money).toFixed(2).split('.')[1] }}</text>
						</view>
						<view class="submit-btn">
							<button type="primary" class="mini" size="mini" @click="create()" v-if="!surplusStartMoney()">提交订单</button>
							<button v-else class="no-submit mini" size="mini">差{{ surplusStartMoney() | moneyFormat }}起送</button>
						</view>
					</view>
					<view class="order-submit-block"></view>

					<payment ref="choosePaymentPopup" @close="payClose" v-if="calculateData"></payment>
				</template>

				<!-- 发票弹窗 -->
				<uni-popup ref="invoicePopup" type="bottom" :mask-click="false">
					<view :style="orderCreateData.is_invoice == 1 ? 'height: 83vh;' : 'height: 48vh;'" class="invoice-popup popup" @touchmove.prevent.stop>
						<view class="popup-header">
							<text class="tit">发票</text>
							<text class="iconfont icon-close" @click="closePopup('invoicePopup')"></text>
						</view>
						<scroll-view scroll-y="true" class="popup-body" :class="{ 'safe-area': isIphoneX }">
							<view>
								<view class="invoice-cell" v-if="goodsData.invoice">
									<text class="tit">需要发票</text>
									<view class="option-grpup">
										<view class="option-item" :class="{ 'color-base-bg active': orderCreateData.is_invoice == 0 }" @click="changeIsInvoice">不需要</view>
										<view class="option-item" :class="{ 'color-base-bg active': orderCreateData.is_invoice == 1 }" @click="changeIsInvoice">需要</view>
									</view>
								</view>
								<block v-if="orderCreateData.is_invoice == 1">
									<view class="invoice-cell">
										<text class="tit">发票类型</text>
										<view class="option-grpup">
											<view class="option-item" :class="{ 'color-base-bg active': orderCreateData.invoice_type == item }" @click="changeInvoiceType(item)" v-for="(item, index) in goodsData.invoice.invoice_type.split(',')" :key="index">
												{{ item == 1 ? '纸质' : '电子' }}
											</view>
										</view>
									</view>
									<view class="invoice-cell">
										<text class="tit">抬头类型</text>
										<view class="option-grpup">
											<view class="option-item" :class="{ 'color-base-bg active': orderCreateData.invoice_title_type == 1 }" @click="changeInvoiceTitleType(1)">
												个人
											</view>
											<view class="option-item" :class="{ 'color-base-bg active': orderCreateData.invoice_title_type == 2 }" @click="changeInvoiceTitleType(2)">
												企业
											</view>
										</view>
									</view>
									<view class="invoice-cell">
										<text class="tit">发票信息</text>
										<view class="invoice-form-group">
											<input type="text" placeholder="请填写抬头名称" v-model.trim="orderCreateData.invoice_title" />
											<input v-if="orderCreateData.invoice_title_type == 2" type="text" placeholder="请填写纳税人识别号" v-model.trim="orderCreateData.taxpayer_number" />
											<input type="text" placeholder="请填写邮寄地址" v-model.trim="orderCreateData.invoice_full_address" v-if="orderCreateData.invoice_type == 1" />
											<input type="text" placeholder="请填写邮箱" v-model.trim="orderCreateData.invoice_email" v-if="orderCreateData.invoice_type == 2" />
										</view>
									</view>
									<view class="invoice-cell">
										<text class="tit">发票内容</text>
										<view class="option-grpup">
											<view :key="index" v-for="(item, index) in goodsData.invoice.invoice_content_array" :class="{ 'color-base-bg active': item == orderCreateData.invoice_content }" @click="changeInvoiceContent(item)" class="option-item content">
												{{ item }}
											</view>
										</view>
									</view>
								</block>
								<view class="invoice-tops">发票内容将以根据税法调整，具体请以展示为准，发票内容显示详细商品名 称及价格信息</view>
							</view>
						</scroll-view>
						<view class="popup-footer" @click="saveInvoice" :class="{ 'bottom-safe-area': isIphoneX }">
							<view class="confirm-btn color-base-bg">确定</view>
						</view>
					</view>
				</uni-popup>

				<!-- 活动优惠弹窗 -->
				<uni-popup ref="promotionPopup" type="bottom" v-if="promotionInfo">
					<view class="promotion-popup popup">
						<view class="popup-header">
							<text class="tit">活动优惠</text>
							<text class="iconfont icon-close" @click="closePopup('promotionPopup')"></text>
						</view>
						<scroll-view scroll-y="true" class="popup-body" :class="{ 'safe-area': isIphoneX }">
							<view class="order-cell" style="align-items: baseline;">
								<view class="tit">
									<text class="promotion-mark ns-gradient-promotionpages-payment">{{ promotionInfo.title }}</text>
								</view>
								<view class="promotion-content">
									<view class="tit tit-content" style="white-space: pre-line;" v-html="promotionInfo.content"></view>
								</view>
							</view>
						</scroll-view>
						<view class="popup-footer" :class="{ 'bottom-safe-area': isIphoneX }">
							<view class="confirm-btn color-base-bg" @click="closePopup('promotionPopup')">确定</view>
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

							<mescroll-uni @getData="getStore" ref="mescroll" top="50px">
								<block slot="list">
									<view class="delivery-content">
										<block v-if="storeData">
											<view class="item-wrap" v-for="(item, index) in storeData" :key="index" @click="selectPickupPoint(item)">
												<view class="detail">
													<view class="name" :class="item.store_id == orderCreateData.delivery.store_id ? 'color-base-text' : ''">
														<text>{{ item.store_name }}</text>
														<text v-if="item.distance">({{ item.distance }}km)</text>
													</view>
													<view class="info">
														<view v-if="item.status == 0 && item.close_desc" class="close-desc">{{ item.close_desc }}</view>
														<view :class="item.store_id == orderCreateData.delivery.store_id ? 'color-base-text' : ''" class="font-size-goods-tag">营业时间：{{ item.open_date }}</view>
														<view :class="item.store_id == orderCreateData.delivery.store_id ? 'color-base-text' : ''" class="font-size-goods-tag">地址：{{ item.full_address }}{{ item.address }}</view>
													</view>
												</view>
												<view class="icon" v-if="item.store_id == orderCreateData.delivery.store_id">
													<text class="iconfont icon-yuan_checked color-base-text"></text>
												</view>
											</view>
										</block>
										<view v-else class="empty-wrap">
											<ns-empty text="所选择收货地址附近没有可以自提的门店" :isIndex="false"></ns-empty>
										</view>
									</view>
									<!-- <block v-if="storeList">
										<view class="item-wrap" v-for="(item, index) in storeList" :key="index"
											@click="selectPickupPoint(item)">
											<view class="detail">
												<view class="name"
													:class="item.store_id == orderCreateData.delivery.store_id ? 'color-base-text' : ''">
													<text>{{ item.store_name }}</text>
													<text v-if="item.distance">({{ item.distance }}km)</text>
												</view>
												<view class="info">
													<view
														:class="item.store_id == orderCreateData.delivery.store_id ? 'color-base-text' : ''"
														class="font-size-goods-tag">
														营业时间：{{ item.open_date }}
													</view>
													<view
														:class="item.store_id == orderCreateData.delivery.store_id ? 'color-base-text' : ''"
														class="font-size-goods-tag">
														地址：{{ item.full_address }}{{ item.address }}
													</view>
												</view>
											</view>
											<view class="icon" v-if="item.store_id == orderCreateData.delivery.store_id">
												<text class="iconfont icon-yuan_checked color-base-text"></text>
											</view>
										</view>
									</block> -->

								</block>
							</mescroll-uni>

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
										<textarea type="text" maxlength="100" placeholder="留言前建议先与商家协调一致" placeholder-class="color-tip" v-model="orderCreateData.buyer_message"/>
									</view>
								</view>
							</view>
						</scroll-view>
						<view class="popup-footer" @click="saveBuyerMessage" :class="{ 'bottom-safe-area': isIphoneX }">
							<view class="confirm-btn color-base-bg">确定</view>
						</view>
					</view>
				</uni-popup>

				<!-- 优惠券弹窗 -->
				<uni-popup ref="couponPopup" type="bottom" v-if="calculateGoodsData" :mask-click="false">
					<view class="coupon-popup popup" @touchmove.prevent.stop>
						<view class="popup-header">
							<text class="tit">优惠券</text>
							<text class="iconfont icon-close" @click="closePopup('couponPopup')"></text>
						</view>

						<scroll-view scroll-y="true" class="popup-body" :class="{ 'safe-area': isIphoneX }">
							<view v-if="coupon_list.length > 0">
								<view class="coupon-item" v-for="(couponItem, couponIndex) in coupon_list" :key="couponIndex" @click="selectCoupon(couponItem)">
									<view class="coupon-info" :style="{ backgroundColor: 'var(--main-color-shallow)' }">
										<view class="info-wrap">
											<image class="coupon-line" mode="heightFix" :src="$util.img('public/uniapp/coupon/coupon_line.png')"/>
											<view class="coupon-money">
												<template v-if="couponItem.type == 'divideticket'">
													<text class="unit">{{ $lang('common.currencySymbol') }}</text>
													<text class="money">{{ parseFloat(couponItem.money) }}</text>
												</template>
												<template v-else-if="couponItem.type == 'reward'">
													<text class="unit">{{ $lang('common.currencySymbol') }}</text>
													<text class="money">{{ parseFloat(couponItem.money) }}</text>
												</template>
												<template v-else-if="couponItem.type == 'discount'">
													<text class="money">{{ parseFloat(couponItem.discount) }}</text>
													<text class="unit">折</text>
												</template>
												<view class="at-least">
													<template v-if="couponItem.at_least > 0">满{{ couponItem.at_least }}可用</template>
													<template v-else>无门槛</template>
												</view>
											</view>
										</view>
										<view class="desc-wrap">
											<view class="coupon-name">{{ couponItem.coupon_name }}</view>
											<view v-if="couponItem.type == 'discount' && couponItem.discount_limit > 0" class="limit">最多可抵￥{{ couponItem.discount_limit }}</view>
											<view class="time font-size-goods-tag">有效期：{{ couponItem.end_time ? $util.timeStampTurnTime(couponItem.end_time) : '长期有效' }}</view>
										</view>
										<view class="iconfont" :class="orderCreateData.coupon.coupon_id == couponItem.coupon_id ? 'icon-yuan_checked color-base-text' : 'icon-yuan_checkbox'"></view>
									</view>
								</view>
							</view>
							<view v-else class="coupon-empty">暂无可用的优惠券</view>
						</scroll-view>

						<view class="popup-footer" :class="{ 'bottom-safe-area': isIphoneX }">
							<view class="confirm-btn color-base-bg" @click="useCoupon">确定</view>
						</view>
					</view>
				</uni-popup>

				<!-- 交易协议 -->
				<view @touchmove.prevent>
					<uni-popup ref="agreementPopup" type="center" :maskClick="false">
						<view class="agreement-conten-box">
							<view class="close">
								<text class="iconfont icon-close" @click="$refs.agreementPopup.close()"></text>
							</view>
							<view class="title">{{ transactionAgreement.title }}</view>
							<view class="con">
								<scroll-view scroll-y="true" class="con">
									<ns-mp-html :content="transactionAgreement.content"></ns-mp-html>
								</scroll-view>
							</view>
						</view>
					</uni-popup>
				</view>

				<!-- 表单修改弹窗 -->
				<uni-popup ref="editFormPopup" type="bottom">
					<view style="height: auto;" class="form-popup popup" @touchmove.prevent.stop>
						<view class="popup-header">
							<text class="tit">买家信息</text>
							<text class="iconfont icon-close" @click="$refs.editFormPopup.close()"></text>
						</view>
						<scroll-view scroll-y="true" class="popup-body" :class="{ 'safe-area': isIphoneX }">
							<ns-form v-if="tempFormData" :data="tempFormData.json_data" ref="tempForm" />
						</scroll-view>
						<view class="popup-footer" @click="saveForm" :class="{ 'bottom-safe-area': isIphoneX }">
							<view class="confirm-btn color-base-bg">确定</view>
						</view>
					</view>
				</uni-popup>

				<uni-popup ref="memberGoodsCardPopup" type="bottom">
					<view class="member-card-popup popup" @touchmove.prevent.stop>
						<view class="popup-header">
							<text class="tit">选择次卡</text>
							<text class="iconfont icon-close" @click="$refs.memberGoodsCardPopup.close()"></text>
						</view>
						<scroll-view scroll-y="true" class="popup-body" :class="{ 'safe-area': isIphoneX }">
							<view v-for="(item, index) in selectGoodsCard.cardList" class="card-item" @click="selectGoodsCard.click(item.item_id)">
								<view class="content">
									<view class="title">{{ item.goods_name }}</view>
									<view class="info">
										<text v-if="item.card_type == 'timecard'">不限次数</text>
										<text v-if="item.card_type == 'oncecard'">剩余{{ item.num - item.use_num }}次</text>
										<text v-if="item.card_type == 'commoncard'">剩余{{ item.total_num - item.total_use_num }}次</text>
										<text>|</text>
										<text>{{ item.end_time ? $util.timeStampTurnTime(item.end_time) : '长期有效' }}</text>
									</view>
								</view>
								<view class="iconfont" :class="selectGoodsCard.itemId == item.item_id ? 'icon-yuan_checked color-base-text' : 'icon-yuan_checkbox'"></view>
							</view>
						</scroll-view>
						<view class="popup-footer" @click="saveMemberGoodsCard" :class="{ 'bottom-safe-area': isIphoneX }">
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
</template>

<script>
	import payment from './payment.js';

	export default {
		name: 'common-payment',
		data() {
			return {};
		},
		props: {
			api: Object,
			createDataKey: String
		},
		mixins: [payment]
	};
</script>

<style lang="scss">
	@import '@/common/css/order_parment.scss';

	.order-cell .promotion-content {
		flex: 1;
	}
</style>
