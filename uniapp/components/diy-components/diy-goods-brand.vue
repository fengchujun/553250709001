<template>
	<view :style="value.pageStyle" v-if="loading || (list && list.length)">
		<x-skeleton type="waterfall" :loading="loading" :configs="skeletonConfig">
			<view :class="['brand-wrap', value.ornament.type]" :style="warpCss">
				<view :class="[value.style]">
					<view class="title-wrap" v-show="value.title" :style="{ color: value.textColor, fontWeight: value.fontWeight ? 'bold' : '' }">{{ value.title }}</view>
					<view class="ul-wrap">
						<view class="li-item" v-for="(item, index) in list" :key="index">
							<image class="brand-pic" :src="$util.img(item.image_url)" mode="aspectFit" @click="toDetail(item)" @error="imgError(index)" :style="itemCss"/>
						</view>
					</view>
				</view>
			</view>
		</x-skeleton>
	</view>
</template>

<script>
	// 商品品牌
	import uniGrid from '@/components/uni-grid/uni-grid.vue';
	import uniGridItem from '@/components/uni-grid-item/uni-grid-item.vue';

	export default {
		name: 'diy-goods-brand',
		props: {
			value: {
				type: Object,
				default: () => {
					return {};
				}
			}
		},
		components: {
			uniGrid,
			uniGridItem
		},
		data() {
			return {
				list: [],
				loading: true,
				skeletonConfig: {
					gridRows: 2,
					gridColumns: 4,
					gridRowsGap: '20rpx',
					headWidth: '120rpx',
					headHeight: '120rpx',
					textShow: false
				}
			};
		},
		created() {
			this.getBrandList();
		},
		watch: {
			// 组件刷新监听
			componentRefresh: function(nval) {
				this.getBrandList();
			}
		},
		computed: {
			warpCss() {
				var obj = '';
				obj += 'background-color:' + this.value.componentBgColor + ';';
				if (this.value.componentAngle == 'round') {
					obj += 'border-top-left-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
				}
				if (this.value.ornament.type == 'shadow') {
					obj += 'box-shadow:' + '0 0 10rpx ' + this.value.ornament.color;
				}
				if (this.value.ornament.type == 'stroke') {
					obj += 'border:' + '2rpx solid ' + this.value.ornament.color;
				}
				return obj;
			},
			// 子项样式
			itemCss() {
				var obj = '';
				if (this.value.elementAngle == 'round') {
					obj += 'border-top-left-radius:' + this.value.topElementAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.value.topElementAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.value.bottomElementAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.value.bottomElementAroundRadius * 2 + 'rpx;';
				}
				return obj;
			}
		},
		methods: {
			getBrandList() {
				var data = {
					page: 1,
					page_size: this.value.count
				};
				if (this.value.sources == 'diy') {
					data.page_size = 0;
					data.brand_id_arr = this.value.brandIds.toString();
				}

				this.$api.sendRequest({
					url: '/api/goodsbrand/page',
					data: data,
					success: res => {
						if (res.code == 0 && res.data) {
							let data = res.data;
							this.list = data.list;
						}
						this.loading = false;
					}
				});
			},
			toDetail(item) {
				this.$util.redirectTo('/pages/goods/list', {
					brand_id: item.brand_id
				});
			},
			imgError(index) {
				if (this.list[index]) this.list[index].image_url = this.$util.getDefaultImage().goods;
			}
		}
	};
</script>

<style lang="scss">
	.brand-wrap {
		&.shadow {
			margin-left: 8rpx;
			margin-right: 8rpx;
			margin-top: 8rpx;
			margin-bottom: 8rpx;
		}

		.style-1 {
			.title-wrap {
				text-align: center;
				padding: 20rpx 0 10rpx;
			}

			.ul-wrap {
				display: flex;
				flex-wrap: wrap;
				padding: 20rpx;

				.li-item {
					display: flex;
					align-items: center;
					justify-content: center;
					width: calc(100% / 4 - 20rpx) !important;
					height: 124rpx;
					margin: 10rpx;
					background-color: #fff;

					.brand-pic {
						width: 100%;
						height: 100%;
					}
				}
			}
		}
	}
</style>