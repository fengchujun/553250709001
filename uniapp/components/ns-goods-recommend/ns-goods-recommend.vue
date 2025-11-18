<template>
	<view class="goods-recommend" v-if="list.length">
		<view class="goods-recommend-title">
			<text class="title">{{ config.title }}</text>
		</view>

		<view class="goods-list double-column">
			<view class="goods-item margin-bottom" v-for="(item, index) in list" :key="index" @click="toDetail(item)">
				<view class="goods-img">
					<image :src="goodsImg(item.goods_image)" mode="widthFix" @error="imgError(index)" :lazy-load="true" />
					<view class="color-base-bg goods-tag" v-if="goodsTag(item) != ''">{{ goodsTag(item) }}</view>
					<view class="sell-out" v-if="item.goods_stock <= 0">
						<text class="iconfont icon-shuqing"></text>
					</view>
				</view>
				<view class="info-wrap">
					<view class="goods-name" :class="[{ 'using-hidden': config.nameLineMode == 'single' }, { 'multi-hidden': config.nameLineMode == 'multiple' }]">
						{{ item.goods_name }}
					</view>

					<view class="lineheight-clear">
						<view class="discount-price">
							<text class="unit  price-style small">{{ $lang('common.currencySymbol') }}</text>
							<text class="price price-style large">{{ parseFloat(showPrice(item)).toFixed(2).split('.')[0] }}</text>
							<text class="unit price-style small">.{{ parseFloat(showPrice(item)).toFixed(2).split('.')[1] }}</text>
						</view>
						<view class="member-price-tag" v-if="item.member_price && item.member_price == showPrice(item)">
							<image :src="$util.img('public/uniapp/index/VIP.png')" mode="widthFix"/>
						</view>
						<view class="member-price-tag" v-else-if="item.promotion_type == 1">
							<image :src="$util.img('public/uniapp/index/discount.png')" mode="widthFix"/>
						</view>
						<view class="delete-price font-size-activity-tag color-tip price-font" v-if="showMarketPrice(item)">
							<text class="unit">{{ $lang('common.currencySymbol') }}</text>
							<text>{{ showMarketPrice(item) }}</text>
						</view>
					</view>
					<view class="pro-info">
						<view class="block-wrap">
							<view class="sale font-size-activity-tag color-tip" v-if="item.sale_show">已售{{ item.sale_num }}{{ item.unit ? item.unit : '件' }}</view>
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
		<view class="circle-box" v-if="showLoading && load"><ns-loading></ns-loading></view>

		<ns-goods-sku-index ref="goodsSkuIndex" @cartListChange="cartListChange" @addCart="addCart"></ns-goods-sku-index>
	</view>
</template>

<script>
	import nsLoading from '@/components/ns-loading/ns-loading.vue';
	import nsGoodsSkuIndex from '@/components/ns-goods-sku/ns-goods-sku-index.vue';

	// 商品推荐
	export default {
		name: 'ns-goods-recommend',
		components: {
			nsLoading,
			nsGoodsSkuIndex
		},
		data() {
			return {
				list: [],
				config: {
					title: '猜你喜欢',
					sources: 'sort',
					supportPage: [],
					goodsIds: [],
					fontWeight: false,
					padding: 0,
					cartEvent: 'detail',
					text: '购买',
					textColor: '#FFFFFF',
					theme: 'default',
					aroundRadius: 25,
					control: true,
					bgColor: '#FF6A00',
					style: 'button',
					iconDiy: {
						iconType: 'icon',
						icon: '',
						style: {
							fontSize: '60',
							iconBgColor: [],
							iconBgColorDeg: 0,
							iconBgImg: '',
							bgRadius: 0,
							iconColor: ['#000000'],
							iconColorDeg: 0
						}
					}
				},
				page: 1,
				isAll: true,
				isClick: true,
				showLoading: false
			};
		},
		props: {
			isLike: {
				type: Boolean,
				default: true
			},
			size: {
				type: [Number, String],
				default: 10
			},
			auto: {
				type: Boolean,
				default: true
			},
			load: {
				type: Boolean,
				default: true
			},
			route: {
				type: String,
				default: ''
			}
		},
		mounted() {
			if (this.auto) {
				this.getLikeList();
			}
		},
		methods: {
			init() {
				this.list = [];
				this.page = 1;
			},
			toDetail(e) {
				let data = {
					goods_id: e.goods_id
				};
				this.$util.redirectTo('/pages/goods/detail', data);
			},
			getLikeList(size) {
				let that = this;
				if (!this.isClick) return;
				if (!this.isAll) return;
				this.isClick = false;
				if (this.page > 1) this.showLoading = true;
				return new Promise((resolve, reject) => {
					that.$api.sendRequest({
						url: '/api/goodssku/recommend',
						data: {
							page: this.page,
							page_size: this.auto ? this.size : size,
							route: this.route
						},
						success: res => {
							this.showLoading = false;
							this.isClick = true;
							if (res.code == 0) {
								if (this.page == 1) {
									this.list = [];
								}
								this.config = res.data.config;
								this.list = this.list.concat(res.data.list);
								if (this.list.length == res.data.count) this.isAll = false;
								this.page += 1;
								resolve(res.data.list);
							}
						}
					});
				});
			},
			goodsImg(imgStr) {
				let imgs = imgStr.split(',');
				return imgs[0] ? this.$util.img(imgs[0], {
					size: 'mid'
				}) : this.$util.getDefaultImage().goods;
			},
			imgError(index) {
				this.list[index].goods_image = this.$util.getDefaultImage().goods;
			},
			showPrice(data) {
				let price = data.discount_price;
				if (data.member_price && parseFloat(data.member_price) < parseFloat(price)) price = data.member_price;
				return price;
			},
			showMarketPrice(item) {
				if (item.market_price_show) {
					let price = this.showPrice(item);
					if (item.market_price > 0) {
						return item.market_price;
					} else if (parseFloat(item.price) > parseFloat(price)) {
						return item.price;
					}
				}
				return '';
			},
			goodsTag(data) {
				return data.label_name || '';
			},
			// 监听加入购物车变化
			cartListChange(e) {
				if (this.route == 'cart' && this.storeToken) {
					this.$root.getCartData();
				}
			},
			/**
			 * 添加购物车回调
			 */
			addCart(id) {}
		}
	};
</script>

<style lang="scss">
	.goods-recommend {
		margin-top: 74rpx;
		width: 100vw;

		.goods-recommend-title {
			text-align: center;
			margin-bottom: 40rpx;

			.title {
				font-size: 30rpx;
				font-weight: 500;
				position: relative;
				color: #333;

				&::before,
				&::after {
					content: ' ';
					width: 80rpx;
					border-top: 2rpx solid #969696;
					position: absolute;
					top: 50%;
					transform: translateY(-50%);
				}

				&::before {
					left: 0;
					transform: translateX(-130%);
				}

				&::after {
					right: 0;
					transform: translateX(130%);
				}
			}
		}
	}

	.hr-view {
		display: flex;
		justify-content: center;
		align-items: center;
		max-width: 100%;
		box-sizing: content-box;
		font-size: $font-size-toolbar;
	}

	.hr-view .hr {
		width: 36%;
		height: 2rpx;
		background: #e5e5e5;
	}

	.hr-view .title {
		padding: 0 $padding;
	}

	// 商品列表双列样式
	.goods-list.double-column {
		display: flex;
		justify-content: space-between;
		flex-wrap: wrap;
		margin: 0 24rpx;

		.goods-item {
			display: flex;
			flex-direction: column;
			position: relative;
			background-color: #fff;
			width: calc(50% - 10rpx);
			margin-bottom: $margin-updown;
			border-radius: $border-radius;

			&:nth-child(2n) {
				margin-right: 0;
			}

			.goods-img {
				position: relative;
				overflow: hidden;
				padding-top: 100%;
				border-top-left-radius: $border-radius;
				border-top-right-radius: $border-radius;

				image {
					width: 100%;
					position: absolute;
					// top: 50%;
					top: 0;
					bottom: 0;
					left: 0;
					right: 0;
					// transform: translateY(-50%);
				}
				.sell-out{
					position: absolute;
					z-index: 1;
					width: 100%;
					height: 100%;
					top: 0;
					left: 0;
					display: flex;
					align-items: center;
					justify-content: center;
					background: rgba(0, 0, 0, 0.5);
					text{
						color: #fff;
						font-size: 220rpx;
					}
				}
			}

			.goods-tag {
				color: #fff;
				line-height: 1;
				padding: 8rpx 16rpx;
				position: absolute;
				border-bottom-right-radius: $border-radius;
				top: 0;
				left: 0;
				font-size: $font-size-goods-tag;
			}

			.goods-tag-img {
				position: absolute;
				border-top-left-radius: $border-radius;
				width: 80rpx;
				height: 80rpx;
				top: 0;
				left: 0;
				z-index: 5;
				overflow: hidden;

				image {
					width: 100%;
					height: 100%;
				}
			}

			.info-wrap {
				padding: 20rpx;
				display: flex;
				flex-direction: column;
				flex: 1;
			}

			.goods-name {
				font-size: $font-size-base;
				line-height: 1.3;
				margin-top: 20rpx;
			}

			.lineheight-clear {
				margin-top: 16rpx;
			}

			.discount-price {
				display: inline-block;
				font-weight: bold;
				line-height: 1;
				color: var(--price-color);

				.unit {
					margin-right: 6rpx;
				}
			}

			.pro-info {
				display: flex;
				margin-top: auto;
				align-items: center;

				.block-wrap {
					flex: 1;
					line-height: 1;
					margin-right: 20rpx;

					.sale {
						font-size: $font-size-tag !important;
					}
				}

				.cart-action-wrap {
					position: relative;

					.shopping-cart-btn {
						font-size: 36rpx;
						border: 2rpx solid $base-color;
						border-radius: 50%;
						padding: 10rpx;
						color: $base-color;
						width: 36rpx;
						height: 36rpx;
						text-align: center;
						line-height: 36rpx;
					}

					.plus-sign-btn {
						font-size: 36rpx;
						border: 2rpx solid $base-color;
						border-radius: 50%;
						padding: 10rpx;
						color: $base-color;
						width: 36rpx;
						height: 36rpx;
						text-align: center;
						line-height: 36rpx;
					}

					.buy-btn {
						background-color: $base-color;
						color: var(--btn-text-color);
						border-radius: 50rpx;
						font-size: $font-size-tag;
						padding: 12rpx 30rpx;
						line-height: 1;
					}

					.icon-diy {
						font-size: 80rpx;
					}
				}
			}

			.delete-price {
				display: inline-block;
				margin-left: 6rpx;
				float: right;
				text-decoration: line-through;

				.unit {
					margin-right: 6rpx;
				}

				text {
					line-height: 1;
					font-size: $font-size-tag !important;
				}
			}

			.member-price-tag {
				display: inline-block;
				width: 60rpx;
				line-height: 1;
				margin-left: 6rpx;

				image {
					width: 100%;
				}
			}
		}
	}
</style>