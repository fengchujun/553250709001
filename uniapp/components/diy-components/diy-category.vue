<template>
	<view :class="['category-page-wrap', 'category-template-' + value.template]"
		:style="{height: 'calc(100vh - '+ tabBarHeight +')' }">
		
		<!-- #ifdef MP-WEIXIN -->
		<block v-if="value.template == 4">
			<view class="search-box" v-if="value.search" @click="$util.redirectTo('/pages_tool/goods/search')" :style="navbarInnerStyle">
				<view class="search-content">
					<input type="text" class="uni-input font-size-tag" maxlength="50" placeholder="商品搜索" confirm-type="search" readonly="true" />
					<text class="iconfont icon-sousuo3"></text>
				</view>
			</view>
			<view :style="navbarInnerStyle" v-if="!value.search">商品分类</view>
		</block>
		<block v-if="value.template != 4">
			<view :style="navbarInnerStyle">商品分类</view>
			<view class="search-box" v-if="value.search" @click="$util.redirectTo('/pages_tool/goods/search')" :style="wxSearchHeight">
				<view class="search-content">
					<input type="text" class="uni-input" maxlength="50" placeholder="商品搜索" confirm-type="search" readonly="true" />
					<text class="iconfont icon-sousuo3"></text>
				</view>
			</view>
		</block>
		<!-- #endif -->
		<!-- #ifdef H5 -->
		<view class="search-box" v-if="value.search" @click="$util.redirectTo('/pages_tool/goods/search')">
			<view class="search-content">
				<input type="text" class="uni-input" maxlength="50" placeholder="商品搜索" confirm-type="search" readonly="true" />
				<text class="iconfont icon-sousuo3"></text>
			</view>
		</view>
		<!-- #endif -->
		
		<view class="template-four wx" v-if="value.template == 4">
			<scroll-view scroll-x="true" class="template-four-wrap" :scroll-with-animation="true" :scroll-into-view="'category-one-' + oneCategorySelect" enable-flex="true">
				<view class="category-item" :id="'category-one-' + index" v-for="(item, index) in templateFourData" :key="index" :class="{ select: oneCategorySelect == index }" @click="templateFourOneFn(index)">
					<view class="image-warp" :class="[{ 'color-base-border': oneCategorySelect == index }]">
						<image :src="$util.img(item.image)" mode="aspectFill"/>
					</view>
					<view :class="['text', { 'color-base-bg': oneCategorySelect == index}]">{{ item.category_name }}</view>
				</view>
			</scroll-view>
			<view class="category-item-all" @click="$refs.templateFourPopup.open()">
				<view class="category-item-all-wrap">
					<text class="text">展开</text>
					<image class="img" :src="$util.img('/public/uniapp/category/unfold.png')" mode="aspectFill"></image>
				</view>
			</view>
			<uni-popup type="top" ref="templateFourPopup" :top="uniPopupTop">
				<view class="template-four-popup">
					<scroll-view scroll-y="true" class="template-four-scroll" enable-flex="true">
						<view class="item" :class="{ selected: oneCategorySelect == index }" @click="templateFourOneFn(index)" v-for="(item, index) in templateFourData" :key="index">
							<view class="image-warp" :class="[{ 'color-base-border': oneCategorySelect == index }]">
								<image :src="$util.img(item.image)" mode="aspectFill"></image>
							</view>
							<view :class="['text', { 'color-base-bg': oneCategorySelect == index }]">{{ item.category_name }}</view>
						</view>
					</scroll-view>
					<view class="pack-up" @click="$refs.templateFourPopup.close()">
						<text>点击收起</text>
						<text class="iconfont icon-iconangledown-copy"></text>
					</view>
				</view>
			</uni-popup>
		</view>

		<view class="content-box" v-if="categoryTree">
			<block v-if="categoryTree.length">
				<scroll-view scroll-y="true" class="tree-wrap">
					<view class="category-item-wrap">
						<view class="category-item" v-for="(item, index) in categoryTree" :key="index" :class="[
								{ select: select == index },
								{ 'border-bottom': value.template == 4 && select + 1 === index },
								{ 'border-top': value.template == 4 && select - 1 === index }
							]" @click="switchOneCategory(index)">
							<view>{{ item.category_name }}</view>
						</view>
					</view>
				</scroll-view>

				<view class="right-flex-wrap">
					
					<scroll-view scroll-y="true" class="content-wrap" v-if="value.template == 1 || loadType == 'all'"
						ref="contentWrap" :scroll-into-view="categoryId" :scroll-with-animation="true"
						@scroll="listenScroll" @touchstart="touchStart" :refresher-enabled="true"
						refresher-default-style="none" :refresher-triggered="triggered" @refresherrefresh="onRefresh"
						@refresherrestore="onRestore">
						<view class="child-category" v-for="(item, index) in categoryTree" :key="index" :id="'category-' + index">
							<diy-category-item :category="item" :value="value" ref="categoryItem" :index="index"
								:select="select" :oneCategorySelect="oneCategorySelect" @tologin="toLogin"
								@selectsku="selectSku($event, index)" @addCart="addCartPoint"
								@loadfinish="getHeightArea"></diy-category-item>
						</view>
						<view class="end-tips" ref="endTips" :style="{ opacity: endTips }">已经到底了~</view>
					</scroll-view>

					<view class="content-wrap"
						v-if="(value.template == 2 || value.template == 3 || value.template == 4) && loadType == 'part'">
						<view class="child-category-wrap" v-for="(item, index) in categoryTree" :key="index"
							:id="'category-' + index" :style="{ display: select == index ? 'block' : 'none' }">
							<diy-category-item :category="item" :value="value" ref="categoryItem" :index="index"
								:last="index == categoryTree.length - 1 ? true : false" :select="select"
								:oneCategorySelect="oneCategorySelect" @tologin="toLogin"
								@selectsku="selectSku($event, index)" @addCart="addCartPoint"
								@switch="switchOneCategory"></diy-category-item>
						</view>
					</view>
				</view>
			</block>
			<view class="category-empty" v-else>
				<image :src="$util.img('public/uniapp/category/empty.png')" mode="widthFix"></image>
				<view class="tips">暂时没有分类哦！</view>
			</view>
		</view>
		<view class="category-empty" v-else>
			<image :src="$util.img('public/uniapp/category/empty.png')" mode="widthFix"></image>
			<view class="tips">暂时没有分类哦！</view>
		</view>
		<view class="cart-bottom-block" v-if="(value.template == 2 || value.template == 4) && value.quickBuy && storeToken && categoryTree && categoryTree.length"></view>
		<view class="cart-box" v-if="(value.template == 2 || value.template == 4) && value.quickBuy && storeToken && categoryTree && categoryTree.length" :style="{ bottom: tabBarHeight }" :class="{ active: isIphoneX }">
			<view class="left-wrap">
				<view class="cart-icon" ref="cartIcon" :animation="cartAnimation" @click="$util.redirectTo('/pages/goods/cart')">
					<text class="iconfont icon-ziyuan1"></text>
					<view class="num" v-if="cartNumber">{{ cartNumber < 99 ? cartNumber : '99+' }}</view>
				</view>
				<view class="price">
					<text class="title">总计：</text>
					<text class="unit font-size-tag price-font">￥</text>
					<text class="money font-size-toolbar price-font">{{ cartTotalMoney[0] }}</text>
					<text class="unit font-size-tag price-font">.{{ cartTotalMoney[1] ? cartTotalMoney[1] : '00' }}</text>
				</view>
			</view>
			<view class="right-wrap">
				<button type="primary" class="settlement-btn" @click="settlement">去结算</button>
			</view>
		</view>

		<view class="cart-point" :style="{ left: item.left + 'px', top: item.top + 'px' }" :key="index" v-for="(item, index) in carIconList"></view>

		<ns-goods-sku-category ref="skuSelect" @refresh="refreshData" @addCart="addCartPoint"></ns-goods-sku-category>
	</view>
</template>

<script>
	// 获取系统状态栏的高度
	let systemInfo = uni.getSystemInfoSync();
	let menuButtonInfo = {};
	// 如果是小程序，获取右上角胶囊的尺寸信息，避免导航栏右侧内容与胶囊重叠(支付宝小程序非本API，尚未兼容)
	// #ifdef MP-WEIXIN || MP-BAIDU || MP-TOUTIAO || MP-QQ
	menuButtonInfo = uni.getMenuButtonBoundingClientRect();
	// #endif

	import nsGoodsSkuCategory from '@/components/ns-goods-sku/ns-goods-sku-category.vue';
	var contentWrapHeight, query, cartPosition;
	export default {
		components: {
			nsGoodsSkuCategory
		},
		name: 'diy-category',
		props: {
			value: {
				type: Object,
				default: () => {
					return {};
				}
			}
		},
		data() {
			return {
				oneCategorySelect: 0,
				select: 0,
				categoryId: 'category-0',
				categoryTree: null,
				scrollLock: false,
				triggered: true,
				heightArea: [],
				isSub: false,
				carIconList: {},
				endTips: 0,
				cartAnimation: {},
				loadType: '',
				templateFourData: [],
				isIphoneX: false, //判断手机是否是iphoneX以上,
			};
		},
		created() {
			this.isIphoneX = this.$util.uniappIsIPhoneX();
			this.getCategoryTree();
			this.loadType = this.value.goodsLevel == 1 && this.value.loadType == 'all' ? 'all' : 'part';
		},
		mounted() {
			query = uni.createSelectorQuery().in(this);
			query.select('.content-wrap').boundingClientRect(data => {
				if (data) contentWrapHeight = data.height;
			}).exec();
			setTimeout(() => {
				query.select('.end-tips').boundingClientRect(data => {
					if (data && data.top > contentWrapHeight) this.endTips = 1;
				}).exec();
				query.select('.cart-icon').boundingClientRect(data => {
					if (data) cartPosition = data;
				}).exec();
				if (this.value.template == 1) this.getHeightArea(-1);
			}, 500);
		},
		watch: {
			// 组件刷新监听
			componentRefresh: function(nval) {}
		},
		computed: {
			cartTotalMoney() {
				let money = parseFloat(this.cartMoney).toFixed(2);
				return money.split('.');
			},
			// 转换字符数值为真正的数值
			navbarHeight() {
				// #ifdef APP-PLUS || H5
				return 44;
				// #endif
				// #ifdef MP
				// 小程序特别处理，让导航栏高度 = 胶囊高度 + 两倍胶囊顶部与状态栏底部的距离之差(相当于同时获得了导航栏底部与胶囊底部的距离)
				// 此方法有缺陷，暂不用(会导致少了几个px)，采用直接固定值的方式
				// return menuButtonInfo.height + (menuButtonInfo.top - this.navbarHeight) * 2;//导航高度
				// let height = systemInfo.platform == 'ios' ? 44 : 48;
				let height = menuButtonInfo.top;
				// height += systemInfo.navbarHeight;
				return height;
				// #endif
			},
			// 导航栏内部盒子的样式
			navbarInnerStyle() {
				let style = '';
				// 导航栏宽度，如果在小程序下，导航栏宽度为胶囊的左边到屏幕左边的距离
				// style += 'height:' + this.navbarHeight + 'px;';
				// // 如果是各家小程序，导航栏内部的宽度需要减少右边胶囊的宽度
				// #ifdef MP
				if (this.value.template == 4 && this.value.search) {
					style += 'height:' + menuButtonInfo.height + 'px;';
					let rightButtonWidth = menuButtonInfo.width ? menuButtonInfo.width * 2 + 'rpx' : '70rpx';
					style += 'padding-right:calc(' + rightButtonWidth + ' + 30rpx);';
					style += 'padding-top:' + this.navbarHeight + 'px;';
				}
				// #endif
				if (this.value.template != 4 || (this.value.template == 4 && !this.value.search)) {
					style += 'height:' + menuButtonInfo.height * 2 + 'rpx;';
					style += 'padding-top:' + this.navbarHeight + 'px;';
					style += 'text-align: center;';
					style += 'line-height:' + menuButtonInfo.height * 2 + 'rpx;';
					style += 'font-size: 16px;';
					style += 'padding-bottom: 10rpx;';
				}
				return style;
			},
			wxSearchHeight() {
				let style = '';
				// #ifdef MP
				style += 'height: 64rpx;';
				// #endif
				return style;
			},
			uniPopupTop() {
				let top = 0;
				// #ifdef MP
				top = this.navbarHeight + menuButtonInfo.height + 'px';
				// #endif

				// #ifdef H5
				top = '100rpx';
				// #endif

				return top;
			}
		},
		methods: {
			/**
			 * 页面显示
			 */
			pageShow() {
				this.$store.dispatch('getCartNumber');
				if (!this.heightArea.length) this.getHeightArea(-1);
				this.dealCategoryData()
			},
			dealCategoryData() {
				if (uni.getStorageSync('tabBarParams')) {
					if (this.value.template != 4) {
						this.categoryTree.forEach((item,index) => {
							if(item.category_id == uni.getStorageSync('tabBarParams').split('=')[1]) {
								this.select = index;
								this.categoryId = 'category-' + index;
								// 阻止切换分类之后滚动事件也立即执行
							}
						})
					} else {
						this.templateFourData.forEach((item,index) => {
							if(item.category_id == uni.getStorageSync('tabBarParams').split('=')[1]) {
								this.oneCategorySelect = index;
								this.categoryId = 'category-' + index;
								// 阻止切换分类之后滚动事件也立即执行
								this.categoryTree = this.templateFourData[index].child_list || [];
								this.select = 0;
							}
						})
					}
					uni.removeStorageSync('tabBarParams')
				}
			},
			/**
			 * 获取高度区间
			 */
			getHeightArea(index) {
				let heightArea = [];
				query.selectAll('.content-wrap .child-category').boundingClientRect(data => {
					if (data && data.length) {
						data.forEach((item, index) => {
							if (index == 0) heightArea.push([0, item.height]);
							else heightArea.push([heightArea[index - 1][1], heightArea[index - 1][1] + item.height]);
						});
					}
				}).exec();
				this.heightArea = heightArea;
				if (index != -1 && index < this.categoryTree.length - 1) this.$refs.categoryItem[index + 1].getGoodsList();
				this.refreshData();
			},
			/**
			 * 获取全部分类
			 */
			getCategoryTree() {
				this.$api.sendRequest({
					url: '/api/goodscategory/tree',
					data: {
						level: 3
					},
					success: res => {
						if (res.code == 0) {
							this.categoryTree = res.data;
							if (this.value.template == 4) {
								this.templateFourData = JSON.parse(JSON.stringify(this.categoryTree));
								this.categoryTree = this.templateFourData[0].child_list;
							}
							this.dealCategoryData()
						}
					}
				});
			},
			/**
			 * 切换一级分类
			 * @param {Object} index
			 */
			switchOneCategory(index) {
				if (index >= this.categoryTree.length) return;
				this.select = index;
				this.categoryId = 'category-' + index;
				// 阻止切换分类之后滚动事件也立即执行
				this.scrollLock = true;
			},
			touchStart() {
				this.scrollLock = false;
			},
			/**
			 * 监听滚动
			 * @param {Object} event
			 */
			listenScroll(event) {
				if (this.scrollLock) return;
				let scrollTop = event.detail.scrollTop;
				if (this.heightArea.length) {
					for (let i = 0; i < this.heightArea.length; i++) {
						if (scrollTop >= this.heightArea[i][0] && scrollTop <= this.heightArea[i][1]) {
							this.select = i;
							break;
						}
					}
					if (this.value.template != 1 && this.value.loadType == 'all' && this.heightArea[this.select][1] - scrollTop - contentWrapHeight < 300) {
						this.$refs.categoryItem[this.select].getGoodsList();
					}
				}
			},
			onRefresh() {
				this.triggered = false;
			},
			onRestore() {
				this.triggered = 'restore'; // 需要重置
			},
			toLogin() {
				this.$emit('tologin');
			},
			/**
			 * sku选择
			 * @param {Object} data
			 * @param {Object} index
			 */
			selectSku(data, index) {
				this.$api.sendRequest({
					url: '/api/goodssku/getInfoForCategory',
					data: {
						sku_id: data.sku_id
					},
					success: res => {
						if (res.code >= 0) {
							let item = res.data;
							item.unit = item.unit || '件';

							if (item.sku_images) item.sku_images = item.sku_images.split(',');
							else item.sku_images = [];

							// 多规格时合并主图
							if (item.goods_spec_format && item.goods_image) {
								item.goods_image = item.goods_image.split(',');
								item.sku_images = item.goods_image.concat(item.sku_images);
							}

							// 当前商品SKU规格
							if (item.sku_spec_format) item.sku_spec_format = JSON.parse(item.sku_spec_format);

							// 商品SKU格式
							if (item.goods_spec_format) item.goods_spec_format = JSON.parse(item.goods_spec_format);

							// 限时折扣
							if (item.promotion_type == 1) {
								item.discountTimeMachine = this.$util.countDown(item.end_time - res.timestamp);
							}

							if (item.promotion_type == 1 && item.discountTimeMachine) {
								if (item.member_price > 0 && Number(item.member_price) <= Number(item.discount_price)) {
									item.show_price = item.member_price;
								} else {
									item.show_price = item.discount_price;
								}
							} else if (item.member_price > 0) {
								item.show_price = item.member_price;
							} else {
								item.show_price = item.price;
							}

							this.$refs.skuSelect.show(item);
						}
					}
				});
			},
			settlement() {
				
				// 是否有商品库存不足 不足最小购买数 超过最大购买数
				var no_buy = false;
				
				for (let k in this.cartList) {
					let item = this.cartList[k];
					
					for (let sku in item) {
						if (item.max_buy && item.num > item.max_buy){
							no_buy = true;
							this.$util.showToast({title: '商品' + item.goods_name+'商品最多可购买'+item.max_buy+'件'})
							break;
						}
						if (typeof item[sku] == 'object') {
							if (item[sku].num > item[sku].stock){
								no_buy = true;
								this.$util.showToast({title: '商品' + item.goods_name+'库存不足'})
								break;
							}
							if (item[sku].min_buy && item[sku].num < item[sku].min_buy){
								no_buy = true;
								this.$util.showToast({title: '商品' + item.goods_name+'商品最少要购买'+item[sku].min_buy+'件'})
								break;
							}
							
						}
					}
				}
				
				if(no_buy) return;
				
				if (!this.cartIds.length || this.isSub) return;
				this.isSub = true;

				uni.removeStorageSync('delivery');
				uni.setStorage({
					key: 'orderCreateData',
					data: {
						cart_ids: this.cartIds.toString()
					},
					success: () => {
						this.$util.redirectTo('/pages/order/payment');
						this.isSub = false;
					}
				});
			},
			/**
			 * 添加点
			 * @param {Object} left
			 * @param {Object} top
			 */
			addCartPoint(left, top) {
				if (this.value.template != 2 && !this.value.quickBuy) return;

				let key = new Date().getTime();
				this.$set(this.carIconList, key, {
					left: left,
					top: top,
					index: 0,
					bezierPos: this.$util.bezier([{
							x: left,
							y: top
						}, {
							x: left - 200,
							y: left - 120
						},
						{
							x: cartPosition.left + 10,
							y: cartPosition.top
						}
					], 6).bezier_points,
					timer: null
				});
				this.startAnimation(key);
			},
			/**
			 * 执行动画
			 * @param {Object} key
			 */
			startAnimation(key) {
				let bezierPos = this.carIconList[key].bezierPos,
					index = this.carIconList[key].index;

				this.carIconList[key].timer = setInterval(() => {
					if (index < 6) {
						this.carIconList[key].left = bezierPos[index].x;
						this.carIconList[key].top = bezierPos[index].y;
						index++;
					} else {
						clearInterval(this.carIconList[key].timer);
						delete this.carIconList[key];
						this.$forceUpdate();

						// 购物车图标
						setTimeout(() => {
							this.$store.commit('setCartChange');
						}, 100);
						let animation = uni.createAnimation({
							duration: 200,
							timingFunction: 'ease'
						});
						animation.scale(1.2).step();
						this.cartAnimation = animation.export();
						setTimeout(() => {
							animation.scale(1).step();
							this.cartAnimation = animation.export();
						}, 300);
					}
				}, 50);
			},
			// 风格四头部切换
			templateFourOneFn(index) {
				this.categoryTree = this.templateFourData[index].child_list || [];
				this.oneCategorySelect = index;
				this.select = 0;
			},
			// 操作多规格商品弹框后，刷新商品数据
			refreshData() {
				if(this.$refs.categoryItem) this.$refs.categoryItem[this.select].loadGoodsCartNum(true);
			}
		}
	};
</script>

<style lang="scss">
	.category-page-wrap {
		width: 100vw;
		height: 100vh;
		box-sizing: border-box;
		display: flex;
		flex-direction: column;
		background-color: #fff;
	}

	.content-box {
		flex: 1;
		height: 0;
		display: flex;

		.tree-wrap {
			width: 170rpx;
			height: 100%;
			background-color: #f5f5f5;
		}

		.right-flex-wrap {
			flex: 1;
			width: 0;
			height: 100%;
			background: #fff;
			display: flex;
			flex-direction: column;
			transform: translateX(0px);

			.content-wrap {
				display: flex;
				flex: 1;
				height: 0;
				width: 100%;
			}

			.child-category-wrap {
				width: 100%;
				height: 100%;
			}
		}
	}

	.tree-wrap .category-item-wrap {
		height: auto;
		background-color: #fff;
	}

	.tree-wrap .category-item {
		line-height: 1.5;
		padding: 26rpx 28rpx;
		box-sizing: border-box;
		position: relative;
		background-color: #f5f5f5;

		view {
			color: #222222;
			width: 100%;
			// white-space: nowrap;
			// text-overflow: ellipsis;
			line-height: 1.3;
			overflow: hidden;
			text-align: center;
			// display: -webkit-box;
			// -webkit-line-clamp: 2;
			// -webkit-box-orient: vertical;
			word-break: break-all;
			max-height: 100rpx;
		}

		&.border-top {
			border-bottom-right-radius: 12rpx;
		}

		&.border-bottom {
			border-top-right-radius: 12rpx;
		}

		&.select {
			background: #fff;

			view {
				color: #333;
				font-weight: bold;
			}

			&::before {
				content: ' ';
				width: 8rpx;
				height: 34rpx;
				background: var(--base-color);
				display: block;
				position: absolute;
				left: 0;
				top: 50%;
				transform: translateY(-50%);
			}
		}
	}

	.search-box {
		position: relative;
		padding: 20rpx 30rpx;
		display: flex;
		align-items: center;
		background: #fff;

		.search-content {
			position: relative;
			/* #ifndef MP-WEIXIN */
			height: 64rpx;
			/* #endif */
			/* #ifdef MP-WEIXIN */
			height: 100%;
			/* #endif */
			border-radius: 40rpx;
			flex: 1;
			background-color: #f5f5f5;

			input {
				box-sizing: border-box;
				display: block;
				height: 100%;
				width: 100%;
				padding: 0 20rpx 0 40rpx;
				background: #f5f5f5;
				color: #333;
				border-radius: 40rpx;
			}

			.iconfont {
				position: absolute;
				top: 50%;
				right: 10rpx;
				transform: translateY(-50%);
				font-size: $font-size-toolbar;
				z-index: 10;
				color: #89899a;
				width: 80rpx;
				text-align: center;
			}
		}
	}

	.cart-box {
		height: 100rpx;
		width: 100%;
		position: fixed;
		left: 0;
		bottom: var(--tab-bar-height, 0);
		// bottom: calc( constant(safe-area-inset-bottom) + 110rpx );
		// bottom: calc( env(safe-area-inset-bottom) + 110rpx );
		background: #fff;
		border-top: 1px solid #f5f5f5;
		box-sizing: border-box;
		padding: 0 30rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;

		.left-wrap {
			display: flex;
			align-items: center;
		}

		.cart-icon {
			width: 70rpx;
			height: 70rpx;
			position: relative;

			.iconfont {
				color: var(--btn-text-color);
				width: inherit;
				height: inherit;
				background-color: $base-color;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
			}

			.num {
				position: absolute;
				top: 0;
				right: 0;
				transform: translate(60%, 0);
				display: inline-block;
				box-sizing: border-box;
				color: #fff;
				line-height: 1.2;
				text-align: center;
				font-size: 24rpx;
				padding: 0 6rpx;
				min-width: 30rpx;
				border-radius: 16rpx;
				background-color: var(--price-color);
				border: 2rpx solid #fff;
			}
		}

		.price {
			margin-left: 30rpx;

			.title {
				color: #333;
			}

			.money,
			.unit {
				font-weight: bold;
				color: var(--price-color);
			}
		}

		.settlement-btn {
			margin: 0 0 0 20rpx;
			width: 200rpx;
			font-weight: bold;
			border-radius: 50rpx;
			height: 70rpx;
			line-height: 70rpx;
		}
	}

	.cart-box.active {
		bottom: calc(constant(safe-area-inset-bottom) + 110rpx) !important;
		bottom: calc(env(safe-area-inset-bottom) + 110rpx) !important;
	}
	
	.cart-point {
		width: 26rpx;
		height: 26rpx;
		position: fixed;
		z-index: 1000;
		background: #f00;
		border-radius: 50%;
		transition: all 0.05s;
	}

	.category-empty {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-direction: column;

		image {
			width: 380rpx;
		}

		.tips {
			font-size: 26rpx;
			font-weight: 500;
			color: #999;
			margin-top: 50rpx;
		}
	}

	.end-tips {
		text-align: center;
		color: #999;
		font-size: 24rpx;
		padding: 20rpx 0;
		opacity: 0;
	}

	// 风格四
	.category-template-4 {
		.search-box {
			.search-content input {
				background-color: #f1f1f1;
			}
		}

		.cart-box {
			// position: relative;
			z-index: 2;
		}

		/deep/ .template-four {
			position: relative;
			z-index: 1;

			.template-four-wrap {
				position: relative;
				z-index: 1;
				padding-left: 20rpx;
				padding-right: 80rpx;
				padding-bottom: 10rpx;
				display: flex;
				height: 172rpx;
				align-items: baseline;
				box-sizing: border-box;
				box-shadow: 0 4rpx 4rpx rgba(123, 123, 123, 0.1);
			}

			.template-four-popup {
				display: flex;
				flex-direction: column;
				overflow: hidden;

				.title {
					line-height: 1;
					margin-bottom: 20rpx;
					font-weight: bold;
				}

				.template-four-scroll {
					display: flex;
					flex-wrap: wrap;
					align-items: baseline;
					align-content: baseline;
					padding: 20rpx;
					white-space: nowrap;
					height: 380rpx;
					box-sizing: border-box;

					.uni-scroll-view-content {
						flex-wrap: wrap;
						align-items: baseline;
						align-content: baseline;
					}

					.item {
						display: flex;
						flex-direction: column;
						align-items: center;
						padding: 4rpx 0;
						color: #666;
						margin-right: 16rpx;
						border-radius: 40rpx;
						margin-bottom: 10rpx;
						width: calc((100% - 64rpx) / 5);

						&:nth-child(5n + 5) {
							margin-right: 0;
						}

						.image-warp {
							margin-bottom: 6rpx;
							padding: 4rpx;
							display: flex;
							align-items: center;
							justify-content: center;
							border-radius: 42rpx;
							border: 4rpx solid transparent;
						}

						image {
							width: 84rpx;
							height: 84rpx;
							border-radius: 32rpx;
						}

						.text {
							padding: 2rpx 0;
							border-radius: 40rpx;
							font-size: $font-size-tag;
							box-sizing: border-box;
							width: 100%;
							box-sizing: border-box;
							text-align: center;
							overflow: hidden;
							
						}
						.ellipsis {
							// text-overflow: ellipsis;
							display: -webkit-box;
							-webkit-line-clamp: 1;
							-webkit-box-orient: vertical;
						}

						&.selected {
							.text {
								background-color: $base-color;
								color: var(--btn-text-color);
								line-height: 1.3;
								border: 4rpx solid transparent;
								border-color: $base-color;
							}
						}
					}
				}

				.pack-up {
					font-size: $font-size-tag;
					color: #888888;
					height: 74rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					border-top: 2rpx solid #f2f2f2;

					.iconfont {
						font-size: 40rpx;
						margin-left: -4rpx;
					}
				}
			}

			.category-item-all {
				position: absolute;
				bottom: 0;
				z-index: 1;
				right: 0;
				top: 0;
				width: 72rpx;
				line-height: 1;
				background-color: #fff;

				.category-item-all-wrap {
					position: absolute;
					bottom: 0;
					right: 0;
					top: 0;
					left: 0;
					display: flex;
					flex-direction: column;
					justify-content: center;
					align-items: center;
					z-index: 2;
				}

				.text {
					writing-mode: tb-rl;
					margin-bottom: 6rpx;
					letter-spacing: 4rpx;
					font-size: $font-size-tag;
					font-weight: bold;
				}

				.img {
					width: 20rpx;
					height: 20rpx;
				}

				&::after {
					content: '';
					// box-shadow: -4rpx 10rpx 20rpx rgba(0, 0, 0, 0.1);
					position: absolute;
					left: 0;
					width: 10rpx;
					// height: 100rpx;
					top: 20%;
					bottom: 20%;
					// transform: translateY(-50%);
					// background-color: #F0F5FF;
				}
			}

			.uni-scroll-view-content {
				display: flex;
			}

			.category-item {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				min-width: 130rpx;
				flex-shrink: 0;
				margin-right: 20rpx;
				padding: 4rpx 0;

				&:last-of-type {
					margin-right: 0;
				}

				.image-warp {
					margin-bottom: 6rpx;
					padding: 4rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					border-radius: 42rpx;
					border: 4rpx solid transparent;
				}

				image {
					width: 84rpx;
					height: 84rpx;
					border-radius: 32rpx;
				}

				.text {
					font-size: $font-size-tag;
				}
			}

			.select {
				.text {
					padding: 8rpx 16rpx;
					border-radius: 40rpx;
					color: #fff;
					font-size: $font-size-tag;
					line-height: 1;
				}
			}
		}

		.content-wrap .categoty-goods-wrap .goods-list {
			margin-top: 30rpx;
		}

		.tree-wrap .category-item.select::before {
			border-top-right-radius: 8rpx;
			border-bottom-right-radius: 8rpx;
		}
	}
	.cart-bottom-block {
	    height: 100rpx;
	}
</style>