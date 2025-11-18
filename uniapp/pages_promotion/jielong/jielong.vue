<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="body">
		<view class="topHead" :style="{ backgroundImage: 'url(' + $util.img('public/uniapp/jielong/banyuan.png') + ')' }">
			<view class="jielong-head">
				<view class="countdown" v-if="status == 0">
					距活动开始仅剩：
					<uni-count-down :day="time.d" :hour="time.h" :minute="time.i" :second="time.s" color="#fff" splitorColor="#FF4644" backgroundColor="#FF4644" />
				</view>
				<view class="countdown" v-else-if="status == 1">
					距活动结束仅剩：
					<uni-count-down :day="time.d" :hour="time.h" :minute="time.i" :second="time.s" color="#fff" splitorColor="#FF4644" backgroundColor="#FF4644" />
				</view>
				<view class="countdown" v-else-if="status == 2">活动已结束</view>
				<view class="countdown" v-else-if="status == 3">活动已关闭</view>
			</view>

			<!-- 活动介绍 -->
			<view class="move-info">
				<view class="move-title">{{ jielong_info.jielong_name }}</view>
				<view class="move-detail">{{ jielong_info.desc }}</view>
				<view class="move-detail">提货方式：到店自提</view>
				<view class="move-detail">提货时间：{{ $util.timeStampTurnTime(jielong_info.take_start_time,'Y/m/d') }} ~ {{ $util.timeStampTurnTime(jielong_info.take_end_time,'Y/m/d') }}</view>
				<view class="move-detail">活动时间：{{ $util.timeStampTurnTime(jielong_info.start_time,'Y/m/d H:i') }} ~ {{ $util.timeStampTurnTime(jielong_info.end_time,'Y/m/d H:i') }}</view>
			</view>
		</view>

		<!-- 商品内容 -->
		<view class="marketimg-box-con">
			<view class="wrap" v-for="(item, index) in jielong_list" :key="index">
					<view class="img-box">
						<image :src="goodsImg(item.goods_image)" />
					</view>
					<view class="content">
						<view class="title">{{ item.goods_name }}</view>
						<view class="title-text" v-if="item.introduction">{{ item.introduction }}</view>
						<view class="title-text" v-else>精品好物，购物多多，等你来抢</view>
						<view class="content-num" v-if="item.member_headimg.length > 0">
							<view class="content-num-image">
								<image :src="$util.img(data) ? $util.img(data) : $util.getDefaultImage().head" v-for="(data, value) in item.member_headimg.slice(0, 3)" :key="value"/>
							</view>
							<text :class="item.member_num == 2 ? 'text-left' : ''" v-if="item.member_num < 3">{{ item.member_num }}人已买</text>
							<text class="text-leftThree" v-else>...等{{ item.member_num }}人已买</text>
						</view>
						<view class="content-die">
							<text>￥{{ item.discount_price }}</text>
							<text>￥{{ item.market_price }}</text>
						</view>
						<view class="content-button">
							<view v-if="item.goods_spec_format && status != 2" @click="codeView(item.sku_id)" class="color-base-bg">
								选规格
								<view class="color-base-bg-num" v-if="item.cart_num && storeToken != ''">{{ item.cart_num }}</view>
							</view>
							<view v-else-if="item.goods_spec_format && status == 2" class="color-base-bg color-base-bgHover">选规格</view>
							<block v-else>
								<view class="content-button-body" v-if="data.sku_id == item.sku_id" v-for="(data, value) in shoplist" :key="value">
									<view class="content-button-sum iconfont icon-jianshao content-button-left" @click="singleSkuReduce(item)"></view>
									<view class="content-button-center">{{ data.num }}</view>
								</view>
								<view v-if="status != 2" class="content-button-sum iconfont icon-add-fill content-button-right" @click="singleSkuPlus(item)"></view>
								<view v-else class="content-button-sum iconfont icon-add-fill content-button-right content-button-rightNew"></view>
							</block>
						</view>
					</view>
				</view>
		</view>

		<view class="old-buy old-buy-one" v-if="jielong_buy_page.count == 0">
			<view class="head">
				<image :src="$util.img('public/uniapp/jielong/left.png')" mode="" />
				<text class="old-buy-head-text">他们都在买</text>
				<image :src="$util.img('public/uniapp/jielong/right.png')" mode="" />
			</view>
			<view class="content">
				<image :src="$util.img('public/uniapp/jielong/wuren.png')" mode="" />
			</view>
			<view class="old-buy-die">暂无购买记录</view>
		</view>

		<view class="old-buy" v-else>
			<view class="head">
				<image :src="$util.img('public/uniapp/jielong/left.png')" mode="" />
				<text class="old-buy-head-text">他们都在买</text>
				<image :src="$util.img('public/uniapp/jielong/right.png')" mode="" />
			</view>
			<view :class="seeMores ? 'old-buy-head-content' : ''">
				<view class="content" v-for="(item, index) in (jielong_buy_page.list || '').slice(0, cutting)" :key="index">
					<view style="display: flex;">
						<view class="old-buy-content">
							<image class="old-buy-content-image" :src="item.headimg == '' ? $util.getDefaultImage().head : $util.img(item.headimg)"/>
							<view class="old-buy-content-right">
								<view class="old-buy-top">
									<view class="nickname">{{ item.nickname }}</view>
									<view class="buy-goods">{{ $util.timeStampTurnTime(item.pay_time) }}</view>
								</view>
								<view class="buy-goods">购买了{{ item.order_name }}</view>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="see-more" @click="seeMore" v-if="seeMores && jielong_buy_page.count > 5">查看更多</view>
			<view class="see-more" @click="seeMore" v-else-if="jielong_buy_page.count > 5">收起更多</view>
		</view>

		<view :class="zIndex ? 'cart-bottom zIndex' : 'cart-bottom'">
			<view class="cart-bottom-content">
				<view class="cart-bottom-gouwu" @click="CartPopup">
					<view :class="status == 1 && shoplist.length > 0 ? 'cart-bottom-gouwu-content iconhover' : 'cart-bottom-gouwu-content'">
						<view class="cart-bottom-gouwu-content-sum" v-if="status == 1 && shoplist.length > 0">{{ num }}</view>
						<text class="iconfont icon-ziyuan1 "></text>
					</view>
				</view>
				<view class="cart-bottom-text" v-if="shoplist.length == 0">暂未选购商品</view>
				<view class="cart-bottom-textTow" v-else>
					<text>￥</text>
					{{ money }}
				</view>
			</view>
			<view class="cart-gouwu-tijiao cart-gouwu-tijiaohover" v-if="status == 0">活动未开始</view>
			<view :class="status == 1 && shoplist.length > 0 ? 'cart-gouwu-tijiao ' : 'cart-gouwu-tijiao cart-gouwu-tijiaohover'" v-if="status == 1" @click="settlement">接龙购买</view>
			<view class="cart-gouwu-tijiao cart-gouwu-tijiaohover" v-if="status == 2">活动已结束</view>
			<view class="cart-gouwu-tijiao cart-gouwu-tijiaohover" v-if="status == 3">活动已关闭</view>
		</view>

		<view class="shoping-fixed">
			<view class="shoping-fixed-content" @click="$util.redirectTo('/pages/index/index')">
				<text class="iconfont icon-shouye1"></text>
				<view class="shoping-fixed-content-text">首页</view>
			</view>
			<view class="shoping-fixed-content" @click="share">
				<text class="iconfont icon-fenxiang4"></text>
				<view class="shoping-fixed-content-text">分享</view>
			</view>
			<view class="shoping-fixed-content" @click="order">
				<text class="iconfont icon-dingdan2"></text>
				<view class="shoping-fixed-content-text">订单</view>
			</view>
		</view>
		<uni-popup ref="cartPopup" type="bottom" class="cart-popup" style="margin-bottom: 118rpx;">
			<view class="popup-shop">
				<view class="cart-shop-title">
					<view class="cart-shop-title-left">
						已选商品
						<text>（共{{ num }}件）</text>
					</view>
					<view class="cart-shop-title-right">
						<text class="iconfont icon-icon7"></text>
						<text @click="clear">清空</text>
					</view>
				</view>
				<view class="cart-contentOne" v-if="returnShoping.length == 0">
					<image :src="$util.img('public/uniapp/jielong/wurenOne.png')" mode="" />
				</view>
				<view class="popup-content-shop" v-else>
					<view class="wrap" v-for="(item, index) in returnShoping" :key="index">
						<view class="img-box">
							<image :src="bottomImage(item.sku_image) ? item.sku_image : $util.img(item.sku_image)"/>
						</view>
						<view class="content">
							<view class="title">{{ item.goods_name }}</view>
							<view class="title-text">规格：{{ item.sku_name }}</view>
							<view class="content-die">
								<text>{{ item.discount_price }}</text>
								<text>￥{{ item.market_price }}</text>
							</view>
							<view class="content-button">
								<view class="content-button-body" v-if="data.sku_id == item.sku_id" v-for="(data, value) in shoplist" :key="value">
									<view class="content-button-sum iconfont icon-jianshao content-button-left" @click="singleSkuReduce(item)"></view>
									<view class="content-button-center">{{ data.num }}</view>
								</view>
								<view class="content-button-sum iconfont icon-add-fill content-button-right" @click="singleSkuPlus(item)"></view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</uni-popup>

		<uni-popup ref="erWeiPopup" type="center" class="erWeiPopup" :is-mask-click="false">
			<view class="popupContent">
				<view class="popupContent-top">
					<view class="popupContent-top-title">
						<view class="popupContent-top-title-left">
							<image :src="switchImage ? specifications.sku_image : $util.img(specifications.sku_image)" mode=""/>
						</view>
						<view class="popupContent-top-title-right">
							<view class="popupContent-top-title-right-textTop">{{ specifications.sku_name }}</view>
							<view class="popupContent-top-title-right-textBottom" v-if="specifications.stock_show">库存{{ specifications.stock }}件</view>
						</view>
					</view>
					<view class="popupContent-top-center">
						<view class="popupContent-top-center-content" v-for="(items, indexs) in specificationsItem" :key="indexs">
							<view class="popupContent-top-center-content-text">{{ items.spec_name }}</view>
							<view class="popupContent-top-center-content-small">
								<view @click="switchs(item.sku_id)" :class="specifications.sku_id == item.sku_id ? 'small-content small-content-hover' : 'small-content'" v-for="(item, i) in items.value" :key="i">
									{{ item.spec_value_name }}
								</view>
							</view>
						</view>
					</view>
					<view class="popupContent-top-bottom">
						已选择：
						<text>{{ specifications.title }}</text>
						<view class="popupContent-top-bottom-content">
							<view class="popupContent-top-bottom-content-left">
								<text>￥</text>
								<text>{{ specifications.discount_price }}</text>
							</view>
							<view v-if="display" class="popupContent-top-bottom-content-sum">
								<view class="content-button-sum iconfont icon-jianshao content-button-left" @click="singleSkuReduce(specifications)"></view>
								<view v-if="item.sku_id == specifications.sku_id" class="content-button-center" v-for="(item, index) in shoplist" :key="index">{{ item.num }}</view>
								<view class="content-button-sum iconfont icon-add-fill content-button-right" @click="singleSkuPlus(specifications)"></view>
							</view>
							<view v-else>
								<button type="primary" class="popupContent-top-bottom-content-right" v-if="specifications.stock && specifications.stock != 0" @click="shoppingCart(specifications)">
									加入购物车
								</button>
								<button type="primary" class="popupContent-top-bottom-content-right" disabled="true" v-else>确定</button>
							</view>
						</view>
					</view>
				</view>
				<view class="popupContent-bottom">
					<text class="iconfont iconfont-delete icon-close-guanbi" @click="close"></text>
				</view>
			</view>
		</uni-popup>

		<uni-popup ref="share" type="center" class="share-img">
			<view class="share-image">
				<image :src="$util.img(shareImage)" :show-menu-by-longpress="true" />
			</view>
		</uni-popup>

		<ns-login ref="login"></ns-login>

		<view class="fixed-left" v-if="horseRace">
			<view class="fixed-left-content">
				<image :src="lantern.headimg == '' ? $util.getDefaultImage().head : $util.img(lantern.headimg)" />
				<view class="fixed-left-content-text">{{ lantern.content }}刚刚下单成功</view>
			</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
import uniPopup from '@/components/uni-popup/uni-popup.vue';
import jielong from './public/js/jielong.js';

export default {
	components: {
		uniPopup
	},
	mixins: [jielong]
};
</script>

<style scoped lang="scss">
	@import './public/css/jielong.scss';
</style>