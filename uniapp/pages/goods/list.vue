<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="content">
		<view class="head-wrap">
			<!-- 搜索区域 -->
			<view class="search-wrap uni-flex uni-row">
				<view class="flex-item input-wrap">
					<input class="uni-input" maxlength="50" v-model="keyword" @confirm="search()" placeholder="请输入您要搜索的商品" />
					<text class="iconfont icon-sousuo3" @click.stop="search()"></text>
				</view>
				<view class="iconfont" :class="{ 'icon-apps': isList, 'icon-list': !isList }" @click="changeListStyle()"></view>
			</view>

			<!-- 排序 -->
			<view class="sort-wrap">
				<view class="comprehensive-wrap" :class="{ 'color-base-text': orderType === '' }" @click="sortTabClick('')">
					<text :class="{ 'color-base-text': orderType === '' }">综合</text>
				</view>

				<view :class="{ 'color-base-text': orderType === 'sale_num' }" @click="sortTabClick('sale_num')">销量
				</view>

				<view class="price-wrap" @click="sortTabClick('discount_price')">
					<text :class="{ 'color-base-text': orderType === 'discount_price' }">价格</text>
					<view class="iconfont-wrap">
						<view class="iconfont icon-iconangledown-copy asc" :class="{ 'color-base-text': priceOrder === 'asc' && orderType === 'discount_price' }"></view>
						<view class="iconfont icon-iconangledown desc" :class="{ 'color-base-text': priceOrder === 'desc' && orderType === 'discount_price' }"></view>
					</view>
				</view>

				<view :class="{ 'color-base-text': orderType === 'screen' }" class="screen-wrap">
					<text @click="sortTabClick('screen')">筛选</text>
					<view @click="sortTabClick('screen')" class="iconfont-wrap">
						<view class="iconfont icon-shaixuan color-tip"></view>
					</view>
				</view>
			</view>
		</view>

		<mescroll-uni top="180" ref="mescroll" @getData="getGoodsList">
			<block slot="list">
				<view class="goods-list single-column" :class="{ show: isList }">
					<view class="goods-item margin-bottom" v-for="(item, index) in goodsList" :key="index" @click="toDetail(item)">
						<view class="goods-img">
							<image :src="goodsImg(item.goods_image)" mode="widthFix" @error="imgError(index)"></image>
							<view class="color-base-bg goods-tag" v-if="goodsTag(item) != ''">{{ goodsTag(item) }}</view>
							<view class="sell-out" v-if="item.goods_stock <= 0">
								<text class="iconfont icon-shuqing"></text>
							</view>
						</view>
						<view class="info-wrap">
							<view class="name-wrap">
								<view class="goods-name" :class="[{ 'using-hidden': config.nameLineMode == 'single' }, { 'multi-hidden': config.nameLineMode == 'multiple' }]">
									{{ item.goods_name }}
								</view>
							</view>

							<view class="lineheight-clear">
								<view class="discount-price">
									<text class="unit price-style small">{{ $lang('common.currencySymbol') }}</text>
									<text class="price price-style large">{{ parseFloat(showPrice(item)).toFixed(2).split('.')[0] }}</text>
									<text class="unit price-style small">.{{ parseFloat(showPrice(item)).toFixed(2).split('.')[1] }}</text>
								</view>
								<view class="member-price-tag" v-if="item.member_price && item.member_price == showPrice(item)">
									<image :src="$util.img('public/uniapp/index/VIP.png')" mode="widthFix"></image>
								</view>
								<view class="member-price-tag" v-else-if="item.promotion_type == 1">
									<image :src="$util.img('public/uniapp/index/discount.png')" mode="widthFix"></image>
								</view>
							</view>
							<view class="pro-info">
								<view class="delete-price color-tip price-font" v-if="showMarketPrice(item)">
									<text class="unit">{{ $lang('common.currencySymbol') }}</text>
									<text>{{ showMarketPrice(item) }}</text>
								</view>
								<view class="block-wrap">
									<view class="sale color-tip" v-if="item.sale_show">已售{{ item.sale_num }}{{ item.unit ? item.unit : '件' }}</view>
								</view>
								<view class="cart-action-wrap" v-if="config.control && item.is_virtual == 0">
									<!-- 购物车图标 -->
									<view v-if="config.style == 'icon-cart'" :style="{
											color: config.theme == 'diy' ? config.textColor : '',
											borderColor: config.theme == 'diy' ? config.textColor : ''
										}" class="cart shopping-cart-btn iconfont icon-gouwuche click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(config.cartEvent, item, $event)">
										<view class="click-event"></view>
									</view>

									<!--加号图标 -->
									<view v-else-if="config.style == 'icon-add'" :style="{
											color: config.theme == 'diy' ? config.textColor : '',
											borderColor: config.theme == 'diy' ? config.textColor : ''
										}" class="cart plus-sign-btn iconfont icon-add1 click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(config.cartEvent, item, $event)">
										<view class="click-event"></view>
									</view>

									<!-- 按钮 -->
									<view v-else-if="config.style == 'button'" :style="{
											backgroundColor: config.theme == 'diy' ? config.bgColor : '',
											color: config.theme == 'diy' ? config.textColor : '',
											fontWeight: config.theme == 'diy' ? (config.fontWeight ? 'bold' : 'normal') : '',
											padding: config.theme == 'diy' ? '12rpx ' + config.padding * 2 + 'rpx' : ''
										}" class="cart buy-btn click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(config.cartEvent, item, $event)">
										{{ config.text }}
										<view class="click-event"></view>
									</view>

									<!--自定义图标 -->
									<view v-else-if="config.style == 'icon-diy'" :style="{
											color: config.theme == 'diy' ? config.textColor : ''
										}" class="icon-diy click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(config.cartEvent, item, $event)">
										<view class="click-event"></view>
										<diy-icon :icon="config.iconDiy.icon" :value="config.iconDiy.style ? config.iconDiy.style : null"></diy-icon>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="goods-list double-column" :class="{ show: !isList }">
					<view class="goods-item margin-bottom" v-for="(item, index) in goodsList" :key="index"
						@click="toDetail(item)" :style="{ left: listPosition[index] ? listPosition[index].left : '', top: listPosition[index] ? listPosition[index].top : '' }">
						<view class="goods-img">
							<image :src="goodsImg(item.goods_image)" mode="widthFix" @error="imgError(index)"></image>
							<view class="color-base-bg goods-tag" v-if="goodsTag(item) != ''">{{ goodsTag(item) }}</view>
							<view class="sell-out" v-if="item.stock <= 0">
								<text class="iconfont icon-shuqing"></text>
							</view>
						</view>
						<view class="info-wrap">
							<view class="goods-name" :class="[{ 'using-hidden': config.nameLineMode == 'single' }, { 'multi-hidden': config.nameLineMode == 'multiple' }]">
								{{ item.goods_name }}
							</view>
						
							<view class="lineheight-clear">
								<view class="discount-price">
									<text class="unit price-style small">{{ $lang('common.currencySymbol') }}</text>
									<text class="price price-style large">{{ parseFloat(showPrice(item)).toFixed(2).split('.')[0] }}</text>
									<text class="unit price-style small">.{{ parseFloat(showPrice(item)).toFixed(2).split('.')[1] }}</text>
								</view>
								<view class="member-price-tag" v-if="item.member_price && item.member_price == showPrice(item)">
									<image :src="$util.img('public/uniapp/index/VIP.png')" mode="widthFix"></image>
								</view>
								<view class="member-price-tag" v-else-if="item.promotion_type == 1">
									<image :src="$util.img('public/uniapp/index/discount.png')" mode="widthFix"></image>
								</view>
								<view class="delete-price color-tip price-font" v-if="showMarketPrice(item)">
									<text class="unit">{{ $lang('common.currencySymbol') }}</text>
									<text>{{ showMarketPrice(item) }}</text>
								</view>
							</view>
							<view class="pro-info">
								<view class="block-wrap">
									<view class="sale color-tip" v-if="item.sale_show">已售{{ item.sale_num }}{{ item.unit ? item.unit : '件' }}</view>
								</view>
								<view class="cart-action-wrap" v-if="config.control && item.is_virtual == 0">
									<!-- 购物车图标 -->
									<view v-if="config.style == 'icon-cart'" :style="{
											color: config.theme == 'diy' ? config.textColor : '',
											borderColor: config.theme == 'diy' ? config.textColor : ''
										}" class="cart shopping-cart-btn iconfont icon-gouwuche click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(config.cartEvent, item, $event)">
										<view class="click-event"></view>
									</view>
						
									<!--加号图标 -->
									<view v-else-if="config.style == 'icon-add'" :style="{
											color: config.theme == 'diy' ? config.textColor : '',
											borderColor: config.theme == 'diy' ? config.textColor : ''
										}" class="cart plus-sign-btn iconfont icon-add1 click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(config.cartEvent, item, $event)">
										<view class="click-event"></view>
									</view>
						
									<!-- 按钮 -->
									<view v-else-if="config.style == 'button'" :style="{
											backgroundColor: config.theme == 'diy' ? config.bgColor : '',
											color: config.theme == 'diy' ? config.textColor : '',
											fontWeight: config.theme == 'diy' ? (config.fontWeight ? 'bold' : 'normal') : '',
											padding: config.theme == 'diy' ? '12rpx ' + config.padding * 2 + 'rpx' : ''
										}" class="cart buy-btn click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(config.cartEvent, item, $event)">
										{{ config.text }}
										<view class="click-event"></view>
									</view>
						
									<!--自定义图标 -->
									<view v-else-if="config.style == 'icon-diy'" :style="{
											color: config.theme == 'diy' ? config.textColor : ''
										}" class="icon-diy click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(config.cartEvent, item, $event)">
										<view class="click-event"></view>
										<diy-icon :icon="config.iconDiy.icon" :value="config.iconDiy.style ? config.iconDiy.style : null"></diy-icon>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view v-if="goodsList.length == 0 && emptyShow"><ns-empty text="暂无商品"></ns-empty></view>
			</block>
		</mescroll-uni>

		<ns-goods-sku-index ref="goodsSkuIndex" @addCart="addCart"></ns-goods-sku-index>

		<!-- 筛选弹出框 -->
		<uni-drawer :visible="showScreen" mode="right" @close="showScreen = false" class="screen-wrap">
			<view class="title color-tip">筛选</view>
			<scroll-view scroll-y>
				<!-- 包邮 -->
				<view class="item-wrap">
					<view class="label"><text>是否包邮</text></view>
					<view class="list">
						<view class="list-wrap" @click="isFreeShipping = !isFreeShipping" >
							<text :class="{ 'color-base-text': isFreeShipping }">包邮</text>
						</view>
					</view>
				</view>

				<!-- 价格筛选项 -->
				<view class="item-wrap">
					<view class="label"><text>价格区间(元)</text></view>
					<view class="price-wrap">
						<input class="uni-input" type="digit" v-model="minPrice" placeholder="最低价" />
						<view class="h-line"></view>
						<input class="uni-input" type="digit" v-model="maxPrice" placeholder="最高价" />
					</view>
				</view>

				<!-- 品牌筛选项 -->
				<view class="item-wrap" v-if="brandList.length > 0">
					<view class="label"><text>品牌</text></view>
					<view class="list">
						<view class="list-wrap" v-for="(item, index) in brandList" :key="index"
							@click="brandId == item.brand_id ? (brandId = 0) : (brandId = item.brand_id)" >
							<text :class="{ 'color-base-text': item.brand_id == brandId }">{{ item.brand_name }}</text>
						</view>
					</view>
				</view>

				<!-- 分类筛选项 -->
				<view class="category-list-wrap">
					<text class="first">全部分类</text>
					<view class="class-box">
						<view @click="selectedCategory('')" class="list-wrap">
							<text :class="{ selected: !categoryId, 'color-base-text': !categoryId }">全部</text>
						</view>
						<view @click="selectedCategory(item.category_id)" v-for="(item, index) in categoryList" :key="index" class="list-wrap">
							<text :class="{ selected: item.category_id == categoryId, 'color-base-text': item.category_id == categoryId }">{{ item.category_name }}</text>
						</view>
					</view>
				</view>
			</scroll-view>
			<view class="footer" :class="{ 'safe-area': isIphoneX }">
				<button type="default" class="footer-box" @click="resetData">重置</button>
				<button type="primary" class="footer-box1" @click="screenData">确定</button>
			</view>
		</uni-drawer>
		<!-- 悬浮按钮 -->
		<hover-nav></hover-nav>
		<loading-cover ref="loadingCover"></loading-cover>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
	import uniDrawer from '@/components/uni-drawer/uni-drawer.vue';
	import uniTag from '@/components/uni-tag/uni-tag.vue';
	import nsGoodsSkuIndex from '@/components/ns-goods-sku/ns-goods-sku-index.vue';
	import list from './public/js/list.js';

	export default {
		components: {
			uniDrawer,
			uniTag,
			nsGoodsSkuIndex
		},
		data() {
			return {};
		},
		mixins: [list]
	};
</script>

<style lang="scss">
	@import './public/css/list.scss';
</style>
<style scoped>
	>>>.uni-tag--primary.uni-tag--inverted {
		background-color: #f5f5f5 !important;
	}

	/deep/ .sku-layer .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
		max-height: unset !important;
	}
</style>