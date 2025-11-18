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
				<view v-if="goodsSkuDetail.discountTimeMachine" class="goods-promotion">
					<view class="price-info">
						<view class="icon-box"><text class="iconfont icon-seckill"></text></view>
						<view class="price-box">
							<view class="seckill-text">限时秒杀</view>
							<view class="sale-num" v-if="goodsSkuDetail.sale_show">已抢{{ goodsSkuDetail.sale_num }}{{ goodsSkuDetail.unit }}</view>
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
				<view v-if="goodsSkuDetail.discountTimeStart" class="goods-promotion">
					<view class="price-info">
						<view class="icon-box"><text class="iconfont icon-seckill"></text></view>
						<view class="price-box">
							<view class="seckill-text">限时秒杀</view>
							<view class="sale-num" v-if="goodsSkuDetail.sale_show">已抢{{ goodsSkuDetail.sale_num }}{{ goodsSkuDetail.unit }}</view>
						</view>
					</view>
				
					<view class="countdown">
						<view class="txt">距开始仅剩</view>
						<view class="clockrun">
							<uni-count-down
								:day="goodsSkuDetail.discountTimeStart.d"
								:hour="goodsSkuDetail.discountTimeStart.h"
								:minute="goodsSkuDetail.discountTimeStart.i"
								:second="goodsSkuDetail.discountTimeStart.s"
								splitorColor="#ffffff"
								backgroundColor="#ffffff"
							/>
						</view>
					</view>
				</view>
				<view class="group-wrap padding-top">
					<view class="goods-module-wrap">
						<text class="promotion-tag">秒杀价</text>
						<text class="price-symbol price-font">{{ $lang('common.currencySymbol') }}</text>
						<text class="price price-font">{{ parseFloat(goodsSkuDetail.show_price).toFixed(2).split('.')[0] }}</text>
						<text class="price-symbol price-font">.{{ parseFloat(goodsSkuDetail.show_price).toFixed(2).split('.')[1] }}</text>
						<view class="market-price-wrap" v-if="goodsSkuDetail.price > 0">
							<text class="unit price-font">{{ $lang('common.currencySymbol') }}</text>
							<text class="money price-font">{{ goodsSkuDetail.price }}</text>
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
							<text v-if="goodsSkuDetail.stock_show">库存 {{ goodsSkuDetail.stock }} {{ goodsSkuDetail.unit }}</text>
							<text v-if="goodsSkuDetail.sale_show">销量 {{ goodsSkuDetail.sale_num }} {{ goodsSkuDetail.unit }}</text>
						</view>
					</view>
				</view>
			</template>

			<!-- 入口区域 -->
			<template v-slot:entrance>
				<!-- 已选规格 -->
				<view class="item selected-sku-spec" v-if="goodsSkuDetail.sku_spec_format" @click="seckill">
					<view class="label">选择</view>
					<view class="box">
						<text v-for="(item, index) in goodsSkuDetail.sku_spec_format" :key="index">{{ item.spec_name }}/{{ item.spec_value_name }}</text>
					</view>
					<text class="iconfont icon-right"></text>
					<!-- <view class="img-wrap"><image :src="$util.img('public/uniapp/goods/detail_more.png')" mode="aspectFit" /></view> -->
				</view>
			</template>

			<!-- 参与流程 -->
			<template v-slot:articipation>
				<!-- 活动规则 -->
				<view class="goods-detail-tab rule-wrap" v-if="goodsSkuDetail.remark">
					<view class="detail-tab"><view class="tab-item">活动规则</view></view>
					<view class="content">{{ goodsSkuDetail.remark }}</view>
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
					:max-buy="goodsSkuDetail.max_buy"
				></ns-goods-sku>
			</template>

			<!-- 操作区域 -->
			<template v-slot:action>
				<!-- 商品底部导航 -->
				<ns-goods-action :safeArea="isIphoneX">
					<template v-if="goodsSkuDetail.goods_state == 1">
						<ns-goods-action-icon text="首页" icon="icon-shouye1" @click="goHome" />
						<ns-goods-action-icon text="客服" icon="icon-kefu" :send-data="contactData" :chatParam="chatRoomParams" />
						<ns-goods-action-icon
							text="购物车"
							icon="icon-gouwuche2"
							:cornerMarkBg="themeStyle.goods_detail.goods_cart_num_corner"
							:corner-mark="cartNumber > 0 ? cartNumber + '' : ''"
							@click="goCart"
						/>
						
						<ns-goods-action-button
							v-if="goodsSkuDetail.discountTimeStart"
							class="goods-action-button "
							:textColor="themeStyle.btn_text_color"
							disabled-text="立即抢购"
							:disabled="true"
							:backgroundColor="themeStyle.goods_detail.goods_btn_color"
						/>
						<ns-goods-action-button
							v-else
							class="goods-action-button "
							:textColor="themeStyle.btn_text_color"
							:backgroundColor="themeStyle.goods_detail.goods_btn_color"
							text="立即抢购"
							@click="seckill"
						/>
					</template>
					<template v-else>
						<ns-goods-action-button class="goods-action-button active2" disabled-text="该商品已下架" :disabled="true" />
					</template>
				</ns-goods-action>
			</template>
		</goods-detail-view>
		<to-top v-if="showTop" @toTop="scrollToTopNative()"></to-top>
		<loading-cover ref="loadingCover"></loading-cover>
		<ns-login ref="login"></ns-login>

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
import goodsDetailBase from '@/common/js/goods_detail_base.js';
import goodsDetailView from '@/components/goods-detail-view/goods-detail-view.vue';
import toTop from '@/components/toTop/toTop.vue';

export default {
	components: {
		nsGoodsAction,
		nsGoodsActionIcon,
		nsGoodsActionButton,
		uniPopup,
		nsGoodsSku,
		uniCountDown,
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
/deep/ .uni-countdown__splitor {
	line-height: 1.4;
	padding: 0 6rpx;
}
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

/deep/ .uni-countdown__number {
	border: none;
}

/deep/ .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
	max-height: unset !important;
}

/deep/ .goods-action-button.active1 {
	padding: 0 10px;
}

/deep/ .goods-action-button.active1 .action-buttom-wrap {
	height: 72rpx;
	line-height: 72rpx;
}

/deep/ .goods-action-button.active2 {
}

/deep/ .goods-action-button.active2 .action-buttom-wrap {
	height: 72rpx;
	line-height: 72rpx;
	margin: 10px 0;
}

.goods-promotion .countdown .clockrun {
	margin-top: 10rpx;
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
</style>
