<template>
	<view :style="value.pageStyle" v-if="loading || (list && list.length)">
		<x-skeleton type="waterfall" :loading="loading" :configs="skeletonConfig">
			<view v-if="list.length" :class="['goods-list', goodsValue.style]" :style="goodsListWarpCss">
				<view class="top-wrap" v-if="goodsValue.topStyle.support">
					<text :class="['js-icon', goodsValue.topStyle.icon.value]" :style="{ backgroundColor: goodsValue.topStyle.icon.bgColor, color: goodsValue.topStyle.icon.color }"></text>
					<text class="title" :style="{ color: goodsValue.topStyle.color }">{{ goodsValue.topStyle.title }}</text>
					<text class="line" :style="{ color: goodsValue.topStyle.subColor }"></text>
					<text class="sub" :style="{ color: goodsValue.topStyle.subColor }">{{ goodsValue.topStyle.subTitle }}</text>
				</view>
				<swiper :autoplay="false" class="swiper" :style="{ height: swiperHeight }">
					<swiper-item v-for="(item, index) in page" :key="index" :class="['swiper-item', [list[index].length / 3] >= 1 && 'flex-between']">
						<view class="goods-item" v-for="(dataItem, dataIndex) in list[index]" :key="dataIndex" @click="toDetail(dataItem)" :class="[goodsValue.ornament.type]" :style="goodsItemCss">
							<div class="goods-img-wrap">
								<image class="goods-img" :style="{ borderRadius: value.imgAroundRadius * 2 + 'rpx' }" :src="$util.img(dataItem.goods_image, { size: 'mid' })" mode="widthFix" @error="imgError(index,dataIndex)" :lazy-load="true"/>
								<view class="sell-out" v-if="dataItem.goods_stock <= 0">
									<text class="iconfont icon-shuqing"></text>
								</view>
							</div>
							<view :class="['info-wrap', { 'multi-content': value.nameLineMode == 'multiple' }]" v-if="goodsValue.goodsNameStyle.control || goodsValue.priceStyle.mainControl || goodsValue.priceStyle.lineControl || goodsValue.labelStyle.support">
								<view v-if="goodsValue.goodsNameStyle.control" class="goods-name"
									:style="{ color: goodsValue.theme == 'diy' ? goodsValue.goodsNameStyle.color : '', fontWeight: goodsValue.goodsNameStyle.fontWeight ? 'bold' : '' }"
									:class="[{ 'using-hidden': goodsValue.nameLineMode == 'single' }, { 'multi-hidden': goodsValue.nameLineMode == 'multiple' }]">
									{{ dataItem.goods_name }}
								</view>
								<view class="pro-info">
									<view class="label-wrap" v-if="goodsValue.labelStyle.support" :style="{ background: goodsValue.labelStyle.bgColor, color: goodsValue.labelStyle.color }">
										<image :src="$util.img('app/component/view/goods_recommend/img/label.png')" mode="widthFix"/>
										<text>{{ goodsValue.labelStyle.title }}</text>
									</view>
									<view class="discount-price">
										<view class="price-wrap" v-if="goodsValue.priceStyle.mainControl">
											<text class="unit price-style small" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">￥</text>
											<text class="price price-style large" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">{{ showPrice(dataItem).split('.')[0] }}</text>
											<text class="unit price-style small" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">{{ '.' + showPrice(dataItem).split('.')[1] }}</text>
										</view>
										<view v-if="goodsValue.priceStyle.lineControl && showMarketPrice(dataItem)" class="delete-price price-font" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.lineColor : '' }">￥{{ showMarketPrice(dataItem) }}</view>
										<view class="sale" v-if="goodsValue.saleStyle.control" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.saleStyle.color : '' }">
											售{{ dataItem.sale_num }}{{ dataItem.unit ? dataItem.unit : '件' }}
										</view>
									</view>
								</view>
							</view>
						</view>
					</swiper-item>
				</swiper>
			</view>
		</x-skeleton>
	</view>
</template>

<script>
	export default {
		name: 'diy-goods-recommend',
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
				loading: true,
				skeletonConfig: {
					gridRows: 1,
					gridColumns: 3,
					headWidth: '200rpx',
					headHeight: '200rpx',
					textRows: 2,
					textWidth: ['100%', '60%'],
				},
				list: [],
				goodsValue: {},
				page: 1
			};
		},
		created() {
			this.goodsValue = this.value;
			this.getGoodsList();
		},
		watch: {
			'globalStoreInfo.store_id': {
				handler(nval, oval) {
					if (nval != oval) {
						this.getGoodsList();
					}
				},
				deep: true
			},
			// 组件刷新监听
			componentRefresh: function(nval) {
				this.getGoodsList();
			}
		},
		computed: {
			goodsListWarpCss() {
				var obj = '';
				obj += 'background-color:' + this.goodsValue.componentBgColor + ';';
				if (this.goodsValue.componentAngle == 'round') {
					obj += 'border-top-left-radius:' + this.goodsValue.topAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.goodsValue.topAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.goodsValue.bottomAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.goodsValue.bottomAroundRadius * 2 + 'rpx;';
				}
				if (this.goodsValue.bgUrl) {
					obj += `background-image: url('${this.$util.img(this.goodsValue.bgUrl)}');`;
				}
				return obj;
			},
			// 商品项样式
			goodsItemCss() {
				var obj = '';
				obj += 'background-color:' + this.value.elementBgColor + ';';
				if (this.goodsValue.elementAngle == 'round') {
					obj += 'border-top-left-radius:' + this.goodsValue.topElementAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.goodsValue.topElementAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.goodsValue.bottomElementAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.goodsValue.bottomElementAroundRadius * 2 + 'rpx;';
				}
				if (this.goodsValue.ornament.type == 'shadow') {
					obj += 'box-shadow:' + '0 0 10rpx ' + this.goodsValue.ornament.color + ';';
				}
				if (this.goodsValue.ornament.type == 'stroke') {
					obj += 'border:' + '2rpx solid ' + this.goodsValue.ornament.color + ';';
				}
				const screenWidth = uni.getSystemInfoSync().windowWidth;
				var width = '';
				if (this.goodsValue.style != 'style-2') {
					width = [screenWidth - this.rpxUpPx(20) * 2 - this.rpxUpPx(200) * 3 - this.rpxUpPx(this.value.margin.both * 2) * 2] / 6;
				} else {
					width = [screenWidth - this.rpxUpPx(20) * 2 - this.rpxUpPx(20) * 2 - this.rpxUpPx(200) * 3 - this.rpxUpPx(this.value.margin.both * 2) * 2] / 6;
				}
				obj += 'margin-left:' + width + 'px;';
				obj += 'margin-right:' + width + 'px;';
				return obj;
			},
			swiperHeight() {
				if (this.goodsValue.style == 'style-3') {
					return '330rpx';
				} else if (this.goodsValue.style != 'style-2') {
					if (this.value.nameLineMode == 'multiple') {
						return '348rpx';
					}
					return '312rpx';
				} else {
					if (this.value.nameLineMode == 'multiple') {
						return '360rpx';
					}
					return '320rpx';
				}
			}
		},
		methods: {
			rpxUpPx(res) {
				const screenWidth = uni.getSystemInfoSync().windowWidth;
				var data = (screenWidth * parseInt(res)) / 750;
				return Math.floor(data);
			},
			getGoodsList() {
				var data = {
					num: this.goodsValue.count
				};
				if (this.goodsValue.sources == 'category') {
					data.category_id = this.goodsValue.categoryId;
					data.category_level = 1;
				} else if (this.goodsValue.sources == 'diy') {
					data.num = 0;
					data.goods_id_arr = this.goodsValue.goodsId.toString();
				}
				data.order = this.goodsValue.sortWay;

				this.$api.sendRequest({
					url: '/api/goodssku/components',
					data: data,
					success: res => {
						if (res.code == 0 && res.data) {
							let data = res.data;
							this.list = data;

							// 切屏滚动，每页显示固定数量
							let size = 3;
							let temp = [];
							this.page = Math.ceil(this.list.length / size);
							for (var i = 0; i < this.page; i++) {
								temp[i] = [];
								for (var j = i * size; j < this.list.length; j++) {
									if (temp[i].length == size) break;
									temp[i].push(this.list[j]);
								}
							}
							this.list = temp;
						}
						this.loading = false;
					}
				});
			},
			toDetail(item) {
				this.$util.redirectTo('/pages/goods/detail', {
					goods_id: item.goods_id
				});
			},
			imgError(pageIndex, index) {
				this.list[pageIndex][index].goods_image = this.$util.getDefaultImage().goods;
			},
			showPrice(data) {
				let price = data.discount_price;
				if (data.member_price && parseFloat(data.member_price) < parseFloat(price)) price = data.member_price;
				return price;
			},
			showMarketPrice(item) {
				let price = this.showPrice(item);
				if (item.market_price > 0) {
					return item.market_price;
				} else if (item.price > price) {
					return item.price;
				}
				return '';
			},
		}
	};
</script>

<style lang="scss" scoped>
	.goods-list {
		.goods-item {
			line-height: 1;

			.sale {
				line-height: 1;
				color: $color-tip;
				font-size: $font-size-activity-tag;
			}

			.info-wrap {
				.goods-name {
					margin-bottom: 10rpx;
					line-height: 1.3;
				}
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
					font-size: 160rpx;
					position: absolute;
					left:50%;
					top:50%;
					transform: translateX(-50%) translateY(-50%);
				}
			}
		}
	}

	// 商品列表横向滚动样式
	.goods-list.style-1 {
		width: 100%;
		white-space: nowrap;
		background-repeat: round;

		.top-wrap {
			display: flex;
			align-items: center;
			padding: 20rpx 0 12rpx;

			.js-icon {
				border-radius: 50%;
				font-size: 40rpx;
				margin-right: 10rpx;
				width: 70rpx;
				height: 70rpx;
				text-align: center;
				line-height: 70rpx;
			}

			.line {
				height: 28rpx;
				margin: 0 10rpx;
				border: 2rpx solid;
			}

			.title {
				font-weight: bold;
				font-size: $font-size-toolbar;
			}

			.sub {
				font-size: $font-size-tag;
			}
		}

		.flex-between {
			justify-content: space-between;
		}

		.swiper {
			display: flex;
			flex-wrap: wrap;
			margin: 0 20rpx;

			.swiper-item {
				display: flex;
				align-items: flex-start;
			}
		}

		.goods-item {
			overflow: hidden;
			width: 200rpx;
			display: inline-block;
			box-sizing: border-box;
			margin-top: 8rpx;
			&:nth-child(3n + 3) {
				width: 198rpx;
			}

			&.shadow {
				margin-top: 8rpx;
			}
			.goods-img, .goods-img-wrap {
				position: relative;
				width: 100%;
				height: 196rpx;
			}

			.info-wrap {
				display: flex;
				flex-direction: column;
				padding: 10rpx;

				&.multi-content {
					height: 130rpx;
					box-sizing: border-box;
				}

				.goods-name {
					font-size: $font-size-sub;

					&.multi-hidden {
						white-space: break-spaces;
					}
				}

				.pro-info {
					margin-top: auto;
					display: flex;
					flex-direction: column;
					justify-content: space-between;

					.discount-price {
						display: flex;
						justify-content: space-between;
						align-items: center;

						.price-wrap {
							line-height: 1;
							white-space: nowrap;

							.unit {
								font-size: $font-size-tag;
								color: $base-color;
							}

							.price {
								font-size: $font-size-toolbar;
							}

							text {
								font-weight: bold;
								color: $base-color;
							}
						}
					}

					.delete-price {
						margin-left: 10rpx;
						text-decoration: line-through;
						flex: 1;
						line-height: 28rpx;
						color: $color-tip;
						font-size: $font-size-activity-tag;
					}
				}
			}
		}
	}

	// 商品列表横向滚动样式
	.goods-list.style-2 {
		width: 100%;
		white-space: nowrap;
		background-repeat: round;
		padding-bottom: 20rpx;

		.top-wrap {
			display: flex;
			align-items: center;
			padding: 20rpx;

			.js-icon {
				border-radius: 50%;
				font-size: 40rpx;
				margin-right: 20rpx;
				width: 70rpx;
				height: 70rpx;
				text-align: center;
				line-height: 70rpx;
			}

			.line {
				height: 28rpx;
				margin: 0 10rpx;
				border: 2rpx solid;
			}

			.title {
				font-weight: bold;
				font-size: $font-size-toolbar;
			}

			.sub {
				font-size: $font-size-tag;
			}
		}

		.swiper {
			display: flex;
			flex-wrap: wrap;
			margin: 0 20rpx;
			padding: 20rpx;
			border-radius: 20rpx;
			background-color: #fff;
		}

		.goods-item {
			overflow: hidden;
			width: 200rpx;
			display: inline-block;
			box-sizing: border-box;

			&.shadow {
				margin-top: 8rpx;
				width: 200rpx;
			}

			.goods-img, .goods-img-wrap {
				position: relative;
				width: 100%;
				height: 200rpx;
			}

			.info-wrap {
				padding: 10rpx;

				.goods-name {
					line-height: 1;

					&.multi-hidden {
						line-height: 1.3;
						height: 68rpx;
						white-space: break-spaces;
					}
				}

				.pro-info {
					display: flex;
					flex-direction: column;
					justify-content: space-between;

					.discount-price {
						display: flex;
						justify-content: space-between;
						align-items: center;

						.price-wrap {
							line-height: 1.3;

							.unit {
								font-size: $font-size-tag;
								color: $base-color;
							}

							text {
								font-weight: bold;
								color: $base-color;

								&:last-of-type {
									font-size: 32rpx;
								}
							}
						}
					}

					.delete-price {
						margin-left: 10rpx;
						text-decoration: line-through;
						flex: 1;
						line-height: 28rpx;
						color: $color-tip;
						font-size: $font-size-activity-tag;
					}
				}
			}
		}
	}

	.goods-list.style-3 {
		background-position: bottom;

		.swiper {
			display: flex;
			flex-wrap: wrap;
			margin: 0 20rpx;
			padding: 10rpx 0;

			.swiper-item {
				display: flex;
				align-items: center;
			}
		}

		.goods-item {
			overflow: hidden;
			width: 200rpx;
			display: inline-block;
			box-sizing: border-box;

			&.shadow {
				// margin-top: 20rpx;
			}

			.goods-img, .goods-img-wrap {
				position: relative;
				width: 100%;
				height: 200rpx;
			}

			.info-wrap {
				display: flex;
				flex-direction: column;
				padding: 10rpx;

				.pro-info {
					text-align: center;

					.label-wrap {
						border-radius: 40rpx;
						display: inline-block;
						margin: 10rpx 0;
						position: relative;
						padding-left: 52rpx;
						padding-right: 16rpx;
						line-height: 1.7;

						image {
							position: absolute;
							top: -2rpx;
							left: -2rpx;
							width: 46rpx;
							height: 46rpx;
						}

						text {
							font-size: $font-size-tag;
						}
					}

					.discount-price {
						.price-wrap {
							line-height: 1;
							white-space: nowrap;

							.unit {
								font-size: $font-size-tag;
								color: $base-color;
							}

							.price {
								font-size: $font-size-toolbar;
							}

							text {
								font-weight: bold;
								color: $base-color;
							}
						}
					}
				}
			}
		}
	}
</style>