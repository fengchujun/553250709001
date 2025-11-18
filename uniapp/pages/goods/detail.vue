<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<!-- #ifndef H5 -->
		<view class="page-header" v-if="goodsSkuDetail && goodsSkuDetail.config && goodsSkuDetail.config.nav_bar_switch == 0">
			<ns-navbar :data="navbarData" :isBack="true"></ns-navbar>
		</view>
		<!-- #endif -->

		<goods-detail-view :goodsSkuDetail="goodsSkuDetail" ref="goodsDetailView">
			<!-- 价格区域 -->
			<template v-slot:price>
				<!-- 限时折扣 -->
				<view v-if="showDiscount && goodsSkuDetail.discountTimeMachine" class="goods-promotion">
					<view class="price-info">
						<view class="icon-box"><text class="iconfont icon-tutechan"></text></view>
						<view class="price-box">
							<view class="promotion-text">限时折扣</view>
							<view class="sale-num" v-if="goodsSkuDetail.sale_show">已售{{ goodsSkuDetail.sale_num }}{{ goodsSkuDetail.unit }}</view>
						</view>
					</view>

					<view class="countdown">
						<view class="txt">距结束仅剩</view>
						<view class="clockrun">
							<uni-count-down
								:day="goodsSkuDetail.discountTimeMachine.d"
								:hour="goodsSkuDetail.discountTimeMachine.h"
								:minute="goodsSkuDetail.discountTimeMachine.i"
								:second="goodsSkuDetail.discountTimeMachine.s"
								splitorColor="#ffffff"
								backgroundColor="#ffffff"
							/>
						</view>
					</view>
				</view>

				<view class="group-wrap padding-top">
					<view class="goods-module-wrap">
						<template v-if="showDiscount">
							<text class="price-symbol price-font">{{ $lang('common.currencySymbol') }}</text>
							<text class="price price-font">{{ parseFloat(goodsSkuDetail.discount_price).toFixed(2).split('.')[0] }}</text>
							<text class="price-symbol price-font">.{{ parseFloat(goodsSkuDetail.discount_price).toFixed(2).split('.')[1] }}</text>
						</template>
						<template v-else>
							<template v-if="goodsSkuDetail.member_price > 0">
								<text class="price-symbol price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="price price-font">{{ parseFloat(goodsSkuDetail.member_price).toFixed(2).split('.')[0] }}</text>
								<text class="price-symbol price-font">.{{ parseFloat(goodsSkuDetail.member_price).toFixed(2).split('.')[1] }}</text>
								<view class="member-vip-wrap">
									<image :src="$util.img('public/uniapp/goods/member_vip.png')" mode="aspectFit"/>
								</view>
								<view class="member-price-wrap" v-if="!goodsSkuDetail.market_price_show">
									<text class="unit price-font">原价</text>
									<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
									<text class="money price-font">{{ goodsSkuDetail.price }}</text>
								</view>
							</template>
							<template v-else>
								<text class="price-symbol price-font">{{ $lang('common.currencySymbol') }}</text>
								<text class="price price-font">{{ parseFloat(goodsSkuDetail.price).toFixed(2).split('.')[0] }}</text>
								<text class="price-symbol price-font">.{{ parseFloat(goodsSkuDetail.price).toFixed(2).split('.')[1] }}</text>
							</template>
						</template>
						<view class="market-price-wrap" v-if="goodsSkuDetail.market_price_show">
							<text class="unit price-font" v-if="(showDiscount && goodsSkuDetail.price > 0) || goodsSkuDetail.market_price > 0">
								{{ $lang('common.currencySymbol') }}
							</text>
							<text class="money price-font" v-if="showDiscount && goodsSkuDetail.price > 0">{{ goodsSkuDetail.price }}</text>
							<text class="money price-font" v-else-if="goodsSkuDetail.market_price > 0">{{ goodsSkuDetail.market_price }}</text>
						</view>
						<view class="follow-and-share">
							<text class="follow iconfont icon-share" @click="openSharePopup()"></text>
							<text class="share iconfont" @click="editCollection()" :class="whetherCollection == 1 ? 'icon-likefill color-base-text' : 'icon-guanzhu'"></text>
						</view>
					</view>
					<view class="goods-module-wrap info">
						<text class="sku-name-wrap">{{ goodsSkuDetail.goods_name }}</text>
						<text class="introduction" v-if="goodsSkuDetail.introduction" :style="{ color: goodsSkuDetail.config ? goodsSkuDetail.config.introduction_color : '' }">
							{{ goodsSkuDetail.introduction }}
						</text>
						<view class="goods-tag-list" v-if="goodsSkuDetail.label_name">
							<text class="tag-item">{{ goodsSkuDetail.label_name }}</text>
						</view>
						<view class="logistics-wrap">
							<text v-if="goodsSkuDetail.stock_show">库存 {{ goodsSkuDetail.stock +goodsSkuDetail.unit}}</text>
							<text v-if="goodsSkuDetail.sale_show">销量 {{ goodsSkuDetail.sale_num+goodsSkuDetail.unit }}</text>
						</view>
						<!-- 会员卡 -->
						<view class="member-card-wrap" @click="$util.redirectTo('/pages_tool/member/card_buy')" v-if="membercard">
							<text class="iconfont icon-huiyuan"></text>
							<view class="info">开通{{ membercard.level_name }}仅需{{ membercard.member_price }}元</view>
							<text class="btn">立即开通</text>
						</view>
					</view>
				</view>
				
				<!-- 卡项套餐 -->
				<view v-if="cardGoods.length && cardGoods[0].goods_name">
					<view class="group-wrap" @click="openCardGoodsPopup()">
						<view class="goods-cell" @click="openCardGoodsPopup()">
							<view class="box">
								<text class="tit">卡项套餐</text>
								<text>{{ cardGoods[0].goods_name }}({{cardGoods[0].card_type_name}})</text>
							</view>
							<text class="iconfont icon-right"></text>
						</view>
						<scroll-view class="combo-goods-wrap color-tip" scroll-x="true">
							<view class="goods-wrap">
								<block v-for="(item, index) in cardGoods[0].goods" :key="index">
									<template v-if="index < 3">
										<view class="goods-item" @click="toGoodsDetail(item.sku_id)">
											<view class="combo-img">
												<image :src="$util.img(item.sku_image, { size: 'mid' })" @error="cardGoodsImageError(0, index)" />
												<view class="price-wrap">
													<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
													<text class="price price-font">{{ item.price }}</text>
													<text class="num" v-if="item.num">* {{ item.num }}</text>
												</view>
											</view>
											<text class="name">{{ item.sku_name }}</text>
										</view>
									</template>
								</block>
							</view>
						</scroll-view>
					</view>
				
					
				</view>
				
				
				<!-- 当前商品参与的营销活动入口 -->
				<view class="group-wrap"><ns-goods-promotion ref="goodsPromotion" promotion="discount"></ns-goods-promotion></view>

				<view class="group-wrap fenxiao-detail"
					v-if="
						goodsSkuDetail.fenxiao_detail &&
							goodsSkuDetail.fenxiao_detail.words_account &&
							goodsSkuDetail.fenxiao_detail.commission_money > 0 &&
							goodsSkuDetail.fenxiao_detail.is_commission_money == 1
					">
					<view class="title color-base-text">{{ goodsSkuDetail.fenxiao_detail.words_account }}详情</view>
					<view class="commission-ratio">
						<view class="item">
							<view>一级{{ goodsSkuDetail.fenxiao_detail.words_account }}</view>
							<view class="price-color">
								<text class="unit">￥</text>
								<text class="money">{{ parseFloat(goodsSkuDetail.fenxiao_detail.commission_money).toFixed(2).split('.')[0] }}</text>
								<text class="unit">.{{ parseFloat(goodsSkuDetail.fenxiao_detail.commission_money).toFixed(2).split('.')[1] }}</text>
							</view>
						</view>
					</view>
				</view>

				<view class="group-wrap card-info" v-if="goodsSkuDetail.goods_class == 5 && goodsSkuDetail.card_info && goodsSkuDetail.card_info.relation_goods.length">
					<view class="card-title">- 套餐包含以下的服务及商品 -</view>
					<view class="card-desc" v-if="goodsSkuDetail.card_info.card_type == 'timecard' || goodsSkuDetail.card_info.card_type == 'commoncard'">
						{{ goodsSkuDetail.card_info.card_type == 'commoncard' ? '卡项内项目/商品总的可用次数为' + goodsSkuDetail.card_info.common_num + ' ,' : '' }}
						{{ goodsSkuDetail.card_info.card_type == 'timecard' ? '次数不限 ,' : '' }} 有效期为{{
							(goodsSkuDetail.card_info.validity_type == 0 && '永久') ||
								(goodsSkuDetail.card_info.validity_type == 1 && goodsSkuDetail.card_info.validity_day + '天') ||
								(goodsSkuDetail.card_info.validity_type == 2 && $util.timeStampTurnTime(goodsSkuDetail.card_info.validity_time))
						}}
					</view>
					<view class="card-desc" v-if="goodsSkuDetail.card_info.card_type == 'oncecard'">
						有效期为{{
							(goodsSkuDetail.card_info.validity_type == 0 && '永久') ||
								(goodsSkuDetail.card_info.validity_type == 1 && goodsSkuDetail.card_info.validity_day + '天') ||
								(goodsSkuDetail.card_info.validity_type == 2 && $util.timeStampTurnTime(goodsSkuDetail.card_info.validity_time))
						}}
					</view>
					<view class="card-content" :style="{ height: [cardOff ? 'auto' : '198rpx'] }">
						<view class="card-item"
							v-for="(item, index) in goodsSkuDetail.card_info.relation_goods"
							@click="$util.redirectTo('/pages/goods/detail', { goods_id: item.goods_id })"
						>
							<image :src="$util.img(item.sku_image)" mode="aspectFill"/>
							<view class="content">
								<view class="name multi-hidden">{{ item.sku_name }}</view>
								<view class="price">￥{{ item.price }}</view>
								<text class="num" v-if="goodsSkuDetail.card_info.card_type == 'oncecard'">x{{ item.num }}</text>
							</view>
						</view>
					</view>
					<view class="card-off" @click="cardOff = !cardOff" v-if="goodsSkuDetail.card_info.relation_goods.length > 1">
						<text>{{ cardOff ? '收起' : '展开' }}</text>
						<text class="icondiy" :class="[cardOff ? 'icon-system-jiantoushang' : 'icon-system-jiantouxia']"></text>
					</view>
				</view>
			</template>
			
			
			<!-- 入口区域 -->
			<template v-slot:entrance>
				<view class="item coupon" v-if="couponList.length && couponList[0]['useState'] != 2" @click="openCouponPopup()">
					<view class="label">领券</view>
					<view class="coupon-list">
						<view class="coupon-item" v-for="(item, index) in couponList" :key="index" v-if="index < 3 && item.useState != 2">
							<view v-if="item.type == 'reward'">
								{{
									parseFloat(item.at_least) > 0
										? '满' + decimalPointFormat(item.at_least) + '减' + decimalPointFormat(item.money)
										: '无门槛使用' + decimalPointFormat(item.money)
								}}
							</view>
							<view v-else-if="item.type == 'discount'">{{ '满' + decimalPointFormat(item.at_least) + '打' + parseFloat(item.discount)+'折' }}</view>
						</view>
					</view>
					<text class="iconfont icon-right"></text>
					<!-- <view class="img-wrap"><image :src="$util.img('public/uniapp/goods/detail_more.png')" mode="aspectFit" /></view> -->
				</view>
				<!-- 已选规格 -->
				<view class="item selected-sku-spec" v-if="goodsSkuDetail.sku_spec_format" @click="choiceSku">
					<view class="label">选择</view>
					<view class="box">
						<text v-for="(item, index) in goodsSkuDetail.sku_spec_format" :key="index">{{ item.spec_name }}/{{ item.spec_value_name }}</text>
					</view>
					<text class="iconfont icon-right"></text>
					<!-- <view class="img-wrap"><image :src="$util.img('public/uniapp/goods/detail_more.png')" mode="aspectFit" /></view> -->
				</view>
				<view class="item free" v-if="manjian.rule_json !== null && addonIsExist.manjian" @click="openManjianPopup()">
					<view class="label">促销</view>
					<text class="free-tip color-base-text">满减</text>
					<text class="font-size-base value">{{ manjian.manjian_name }}</text>
					<text class="iconfont icon-right"></text>
					<!-- <view class="img-wrap"><image :src="$util.img('public/uniapp/goods/detail_more.png')" mode="aspectFit" /></view> -->
				</view>
			</template>

			<!-- 业务区域 -->
			<template v-slot:business>
				<!-- SKU选择 -->
				<ns-goods-sku
					v-if="goodsSkuDetail.goods_id"
					ref="goodsSku"
					@refresh="refreshGoodsSkuDetail"
					:goods-id="goodsSkuDetail.goods_id"
					:goods-detail="goodsSkuDetail"
					:goods-form-val="goodsFormVal"
					:max-buy="goodsSkuDetail.max_buy"
					:min-buy="goodsSkuDetail.min_buy"
					@getSkuId="setSkuId"
					@detailChangeVal="detailChangeVal"
				></ns-goods-sku>
				
				
				<!-- 组合套餐 -->
				<view v-if="bundling.length && bundling[0].bl_name && addonIsExist.bundling">
					<view class="group-wrap" @click="openBundlingPopup()">
						<view class="goods-cell" @click="openBundlingPopup()">
							<view class="box">
								<text class="tit">组合套餐</text>
								<text>{{ bundling[0].bl_name }}</text>
							</view>
							<text class="iconfont icon-right"></text>
							<!-- <view class="more-img-wrap"><image :src="$util.img('public/uniapp/goods/detail_more.png')" mode="aspectFit" /></view> -->
						</view>
						<scroll-view class="combo-goods-wrap color-tip" scroll-x="true">
							<view class="goods-wrap">
								<block v-for="(item, index) in bundling[0].bundling_goods" :key="index">
									<template v-if="index < 3">
										<view class="goods-item" @click="toGoodsDetail(item.sku_id)">
											<view class="combo-img">
												<image :src="$util.img(item.sku_image, { size: 'mid' })" @error="bundlingImageError(0, index)" />
												<view class="price-wrap">
													<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
													<text class="price price-font">{{ item.price }}</text>
												</view>
											</view>
											<text class="name">{{ item.sku_name }}</text>
										</view>
									</template>
								</block>
							</view>
						</scroll-view>
					</view>

					<view @touchmove.prevent.stop>
						<uni-popup ref="bundlingPopup" type="bottom">
							<view class="bundling-popup-layer popup-layer">
								<view class="head-wrap" @click="closeBundlingPopup()">
									<text>组合套餐</text>
									<text class="iconfont icon-close"></text>
								</view>
								<scroll-view scroll-y class="bundling-body">
									<view class="bundling-view">
										<block v-for="(item, index) in bundling" :key="index">
											<view class="bundling-item">
												<view class="title" @click="toComoDetail(item.bl_id)">
													<text>{{ item.bl_name }}</text>
													<text class="iconfont icon-right"></text>
												</view>
												<scroll-view scroll-x>
													<view class="goods-wrap">
														<block v-for="(goods, goods_index) in item.bundling_goods" :key="goods_index">
															<template v-if="goods_index < 3">
																<view class="goods-item" @click="toGoodsDetail(goods.sku_id)">
																	<view class="combo-img">
																		<image :src="$util.img(goods.sku_image, { size: 'mid' })" @error="bundlingImageError(index, goods_index)" />
																		<view class="price-wrap">
																			<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
																			<text class="price price-font">{{ goods.price }}</text>
																		</view>
																	</view>
																	<text class="name">{{ goods.sku_name }}</text>
																</view>
															</template>
														</block>
													</view>
												</scroll-view>
												<view class="bundling-price-wrap">
													<text class="label">套餐价</text>
													<text class="unit price-color">{{ $lang('common.currencySymbol') }}</text>
													<text class="price price-color">{{ item.bl_price }}</text>
													<button type="primary" size="mini" class="mini" @click="toComoDetail(item.bl_id)">立即购买</button>
												</view>
											</view>
										</block>
									</view>
								</scroll-view>
							</view>
						</uni-popup>
					</view>
				</view>

				<!-- 优惠券 -->
				<view @touchmove.prevent.stop>
					<uni-popup ref="couponPopup" type="bottom">
						<view class="goods-coupon-popup-layer popup-layer">
							<view class="head-wrap" @click="closeCouponPopup()">
								<text>优惠券</text>
								<text class="iconfont icon-close"></text>
							</view>
							<scroll-view class="coupon-body" scroll-y>
								<view class="coupon-item ns-gradient-diy-goods-list" v-for="(item, index) in couponList" :key="index" v-if="hackReset">
									<view class="coupon-info" :style="{ backgroundColor: item.useState == 2 ? '#F2F2F2' : 'var(--main-color-shallow)' }">
										<view class="info-wrap" :class="{ disabled: item.useState == 2 }">
											<image class="coupon-line" mode="heightFix" :src="$util.img('public/uniapp/coupon/coupon_line.png')"></image>
											<view class="coupon-money">
												<template v-if="item.type == 'reward'">
													<text class="unit">{{ $lang('common.currencySymbol') }}</text>
													<text class="money">{{ parseFloat(item.money) }}</text>
												</template>
												<template v-else-if="item.type == 'discount'">
													<text class="money">{{ parseFloat(item.discount) }}</text>
													<text class="unit">折</text>
												</template>
												<view class="at-least">
													<template v-if="item.at_least > 0">满{{ item.at_least }}可用</template>
													<template v-else>无门槛</template>
												</view>
											</view>
										</view>
										<view class="desc-wrap">
											<view class="coupon-name">{{ item.coupon_name }}</view>
											<view v-if="item.type == 'discount' && item.discount_limit > 0" class="limit">最多可抵￥{{ item.discount_limit }}</view>
											<view class="time font-size-goods-tag" v-if="item.validity_type == 0">有效期：{{ $util.timeStampTurnTime(item.end_time) }}</view>
											<view class="time font-size-goods-tag" v-else-if="item.validity_type == 1">有效期：领取之日起{{ item.fixed_term }}天内有效</view>
											<view class="time font-size-goods-tag" v-else>有效期：长期有效</view>
										</view>

										<button type="primary" v-if="item.received_type && item.received_type == 'expire'" disabled>已过期</button>
										<template v-else>
											<button type="primary" v-if="item.useState == 2" disabled>已抢光</button>
											<button type="primary" v-else-if="item.useState == 1" disabled>已领取</button>
											<button type="primary" v-else @click="receiveCoupon(item)">领取</button>
										</template>
									</view>
								</view>
							</scroll-view>
							<view class="button-box"><button type="primary" @click="closeCouponPopup()">确定</button></view>
						</view>
					</uni-popup>
				</view>

				<!-- 促销 -->
				<view @touchmove.prevent.stop v-if="addonIsExist.manjian">
					<uni-popup ref="manjianPopup" type="bottom">
						<view class="manjian-popup-layer popup-layer">
							<view class="head-wrap" @click="closeManjianPopup()">
								<text>促销</text>
								<text class="iconfont icon-close"></text>
							</view>
							<scroll-view scroll-y class="manjian-body">
								<view class="item" v-if="manjian.manjian != undefined" style="display: flex;">
									<view class="free-tip color-base-text color-base-border">满减</view>
									<text class="font-size-base">{{ manjian.manjian }}</text>
								</view>
								<view class="item" v-if="manjian.mansong != undefined">
									<text class="free-tip color-base-text color-base-border">满送</text>
									<text class="font-size-base">{{ manjian.mansong }}</text>
								</view>
								<view class="item" v-if="manjian.free_shipping != undefined" style="display: flex;">
									<view class="free-tip color-base-text color-base-border">包邮</view>
									<text class="font-size-base">{{ manjian.free_shipping }}</text>
								</view>
							</scroll-view>
							<view class="button-box"><button type="primary" @click="closeManjianPopup()">确定</button></view>
						</view>
					</uni-popup>
				</view>
				
				<!-- 卡项套餐 -->
				<view @touchmove.prevent.stop>
					<uni-popup ref="cardGoodsPopup" type="bottom">
						<view class="bundling-popup-layer popup-layer">
							<view class="head-wrap" @click="closeCardGoodsPopup()">
								<text>卡项套餐</text>
								<text class="iconfont icon-close"></text>
							</view>
							<scroll-view scroll-y class="bundling-body">
								<view class="bundling-view">
									<block v-for="(item, index) in cardGoods" :key="index">
										<view class="bundling-item">
											<view class="title" @click="$util.redirectTo('/pages/goods/detail',{goods_id: item.goods_id})">
												<text>{{ item.goods_name }}({{item.card_type_name}})</text>
												<text class="iconfont icon-right"></text>
											</view>
											<scroll-view scroll-x>
												<view class="goods-wrap">
													<block v-for="(goods, goods_index) in item.goods" :key="goods_index">
														<template v-if="goods_index < 3">
															<view class="goods-item" @click="toGoodsDetail(goods.sku_id)">
																<view class="combo-img">
																	<image :src="$util.img(goods.sku_image, { size: 'mid' })" @error="cardGoodsImageError(index, goods_index)" />
																	<view class="price-wrap">
																		<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
																		<text class="price price-font">{{ goods.price }}</text>
																		<text class="num" v-if="goods.num">* {{ goods.num }}</text>
																	</view>
																</view>
																<text class="name">{{ goods.sku_name }}</text>
															</view>
														</template>
													</block>
												</view>
											</scroll-view>
											<view class="bundling-price-wrap">
												<text class="label">套餐价</text>
												<text class="unit price-color">{{ $lang('common.currencySymbol') }}</text>
												<text class="price price-color">{{ showPrice(item) }}</text>
												<button type="primary" size="mini" class="mini" @click="$util.redirectTo('/pages/goods/detail',{goods_id: item.goods_id})">立即购买</button>
											</view>
										</view>
									</block>
								</view>
							</scroll-view>
						</view>
					</uni-popup>
				</view>
			</template>

			<!-- 操作区域 -->
			<template v-slot:action>
				<!-- 商品底部导航 -->
				<ns-goods-action :safeArea="isIphoneX">
					<template v-if="goodsSkuDetail.goods_state == 1">
						<block v-if="goodsSkuDetail.store_goods_status != undefined && goodsSkuDetail.store_goods_status != 1">
							<ns-goods-action-icon text="首页" icon="icon-shouye1" @click="goHome" />
							<ns-goods-action-icon text="客服" icon="icon-kefu" :send-data="contactData" :chatParam="chatRoomParams" />
							<ns-goods-action-button
								class="goods-action-button active3"
								disabled-text="该商品已下架"
								:disabled="true"
							/>
						</block>
						<block v-else>
							<ns-goods-action-icon text="首页" icon="icon-shouye1" @click="goHome" />
							<ns-goods-action-icon text="客服" icon="icon-kefu" :send-data="contactData" :chatParam="chatRoomParams" />
							<ns-goods-action-icon
								text="购物车"
								:cornerMarkBg="themeStyle.goods_detail.goods_cart_num_corner"
								icon="icon-gouwuche2"
								:corner-mark="cartNumber > 0 ? cartNumber + '' : ''"
								@click="goCart"
							/>
							<block v-if="goodsSkuDetail.stock == 0 && !goodsSkuDetail.sku_spec_format">
								<ns-goods-action-button class="goods-action-button active3" disabled-text="库存不足" :disabled="true" />
								<!-- <ns-goods-action-button v-if="goodsSkuDetail.sku_spec_format" class="goods-action-button active3" disabled-text="库存不足" :disabled="true" @click="joinCart" /> -->
								<!-- <ns-goods-action-button v-else class="goods-action-button active3" disabled-text="库存不足" :disabled="true" /> -->
							</block>
							<block v-else-if="
									goodsSkuDetail.is_limit == 1 &&
										goodsSkuDetail.limit_type == 2 &&
										goodsSkuDetail.max_buy != 0 &&
										goodsSkuDetail.purchased_num >= goodsSkuDetail.max_buy
								">
								<!-- (goodsSkuDetail.is_limit == 1 && goodsSkuDetail.limit_type == 1 && goodsSkuDetail.max_buy != 0) -->
								<ns-goods-action-button class="goods-action-button active3" disabled-text="已达最大限购数量" :disabled="true" />
							</block>
							<block v-else>
								<!-- :class="goodsSkuDetail.is_virtual == 0 ? 'active1' : ''" -->
								<ns-goods-action-button
									class="goods-action-button"
									text="加入购物车"
									:backgroundColor="themeStyle.goods_detail.goods_btn_color_shallow"
									@click="joinCart"
									v-if="goodsSkuDetail.is_virtual == 0"
								/>
								<!-- :class="goodsSkuDetail.is_virtual == 0 ? 'active2' : 'active4'" -->
								<ns-goods-action-button
									class="goods-action-button"
									:backgroundColor="themeStyle.goods_detail.goods_btn_color"
									:textColor="themeStyle.btn_text_color"
									text="立即购买"
									@click="buyNow"
								/>
							</block>
						</block>
					</template>
					<template v-else>
						<ns-goods-action-icon text="首页" icon="icon-shouye1" @click="goHome" />
						<ns-goods-action-icon text="客服" icon="icon-kefu" :send-data="contactData" :chatParam="chatRoomParams" />
						<ns-goods-action-button class="goods-action-button active3" disabled-text="该商品已下架" :disabled="true" />
					</template>
				</ns-goods-action>
			</template>
		</goods-detail-view>
		<to-top v-if="showTop" @toTop="scrollToTopNative()"></to-top>
		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
import nsGoodsAction from '@/components/ns-goods-action/ns-goods-action.vue';
import nsGoodsActionIcon from '@/components/ns-goods-action-icon/ns-goods-action-icon.vue';
import nsGoodsActionButton from '@/components/ns-goods-action-button/ns-goods-action-button.vue';
import uniPopup from '@/components/uni-popup/uni-popup.vue';
import nsGoodsSku from '@/components/ns-goods-sku/ns-goods-sku.vue';
import uniCountDown from '@/components/uni-count-down/uni-count-down.vue';
import detail from './public/js/detail.js';
import scroll from '@/common/js/scroll-view.js';
import toTop from '@/components/toTop/toTop.vue';
import nsGoodsPromotion from '@/components/ns-goods-promotion/ns-goods-promotion.vue';
import goodsDetailBase from '@/common/js/goods_detail_base.js';
import goodsDetailView from '@/components/goods-detail-view/goods-detail-view.vue';

export default {
	components: {
		nsGoodsAction,
		nsGoodsActionIcon,
		nsGoodsActionButton,
		uniPopup,
		nsGoodsSku,
		uniCountDown,
		nsGoodsPromotion,
		goodsDetailView,
		toTop
	},
	mixins: [goodsDetailBase, detail, scroll]
};
</script>
<style lang="scss">
@import '@/common/css/goods_detail.scss';
@import './public/css/detail.scss';
</style>
<style scoped>
/deep/ .action-icon-wrap .iconfont.icon-shouye1 {
	font-size: 40rpx;
}
/deep/ .uni-video-cover {
	background: none;
}

/deep/ .uni-video-cover-duration {
	display: none;
}

/deep/ .uni-video-cover-play-button {
	border-radius: 50%;
	border: 4rpx solid #fff;
	width: 120rpx;
	height: 120rpx;
	background-size: 30%;
}

.poster-layer >>> .uni-popup__wrapper-box {
	max-height: initial !important;
}

/deep/ .sku-layer .uni-popup__wrapper-box {
	overflow-y: initial !important;
}

.goods-promotion .countdown .clockrun >>> .uni-countdown__number {
	min-width: 32rpx;
	height: 32rpx;
	text-align: center;
	line-height: 32rpx;
	border-radius: 4px;
	display: inline-block;
	padding: 4rpx;
	margin: 0;
	border: none;
}

.goods-promotion .countdown .clockrun >>> .uni-countdown__splitor {
	width: 10rpx;
	height: 32rpx;
	line-height: 36rpx;
	text-align: center;
}

.goods-promotion .countdown .clockrun >>> .uni-countdown__splitor.day {
	width: initial;
}

/deep/ .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
	max-height: unset !important;
}

/deep/ .goods-action-button.active1 {
	padding-left: 10px;
}

/deep/ .goods-action-button.active2 {
	padding-right: 10px;
}

/deep/ .goods-action-button.active3 {
	padding: 0 10px;
}

/deep/ .goods-action-button.active4 {
	padding: 0 10px;
}
/deep/ .uni-popup__wrapper.bottom {
	border-radius: 24rpx 24rpx 0 0;
}

/deep/ .goods-action-button.active1 .action-buttom-wrap {
	height: 72rpx;
	line-height: 72rpx;
}

/deep/ .goods-action-button.active2 .action-buttom-wrap {
	height: 72rpx;
	line-height: 72rpx;
}

/deep/ .goods-action-button.active3 .action-buttom-wrap {
	height: 72rpx;
	line-height: 72rpx;
	margin: 20rpx 0;
}

/deep/ .goods-action-button.active4 .action-buttom-wrap {
	height: 72rpx;
	line-height: 72rpx;
}
</style>
