<template>
	<view :style="value.pageStyle" v-if="loading || (list && list.length)">
		<x-skeleton type="list" :loading="loading" :configs="skeletonConfig">
			<view class="article-wrap" :style="warpCss">
				<view :class="['list-wrap', value.style]" :style="warpCss">
					<view :class="['item', value.ornament.type]" v-for="(item, index) in list" :key="index" @click="toDetail(item)" :style="itemCss">
						<view class="article-img">
							<image class="cover-img" :src="$util.img(item.cover_img)" mode="widthFix" @error="imgError(index)" />
						</view>
						<view class="info-wrap">
							<text class="title">{{ item.article_title }}</text>
							<view class="read-wrap">
								<block v-if="item.category_name">
									<text class="category-icon"></text>
									<text>{{ item.category_name }}</text>
								</block>
								<text class="date">{{ $util.timeStampTurnTime(item.create_time, 'Y-m-d') }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
		</x-skeleton>
	</view>
</template>

<script>
	// 文章
	export default {
		name: 'diy-article',
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
				list: [],
				loading: true,
				skeletonConfig: {
					gridRows: 1,
					gridRowsGap: '40rpx',
					headWidth: '160rpx',
					headHeight: '160rpx',
					textRows: 2
				}
			};
		},
		created() {
			this.getList();
		},
		watch: {
			// 组件刷新监听
			componentRefresh: function (nval) {
				this.getList();
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
				return obj;
			},
			// 子项样式
			itemCss() {
				var obj = '';
				obj += 'background-color:' + this.value.elementBgColor + ';';
				if (this.value.elementAngle == 'round') {
					obj += 'border-top-left-radius:' + this.value.topElementAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.value.topElementAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.value.bottomElementAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.value.bottomElementAroundRadius * 2 + 'rpx;';
				}
				if (this.value.ornament.type == 'shadow') {
					obj += 'box-shadow:' + '0 0 10rpx ' + this.value.ornament.color;
				}
				if (this.value.ornament.type == 'stroke') {
					obj += 'border:' + '2rpx solid ' + this.value.ornament.color;
				}
				return obj;
			}
		},
		methods: {
			getList() {
				var data = {
					num: this.value.count
				};
				if (this.value.sources == 'diy') {
					data.num = 0;
					data.article_id_arr = this.value.articleIds.toString();
				}

				this.$api.sendRequest({
					url: '/api/article/lists',
					data: data,
					success: res => {
						if (res.code == 0 && res.data) {
							let data = res.data;
							this.list = data;
						}
						this.loading = false;
					}
				});
			},
			toDetail(item) {
				this.$util.redirectTo('/pages_tool/article/detail', {
					article_id: item.article_id
				});
			},
			imgError(index) {
				if (this.list[index]) this.list[index].cover_img = this.$util.getDefaultImage().article;
			}
		}
	};
</script>

<style lang="scss">
	.article-wrap {
		.list-wrap {
			&.style-1 {
				.item {
					display: flex;
					padding: 20rpx;
					margin-top: 24rpx;

					&:first-of-type {
						margin-top: 0;
					}

					.article-img {
						margin-right: 20rpx;
						width: 160rpx;
						height: 160rpx;
						overflow: hidden;
						display: flex;
						align-items: center;
						justify-content: center;

						image {
							width: 100%;
						}
					}

					.info-wrap {
						flex: 1;
						display: flex;
						flex-direction: column;
						justify-content: space-between;

						.title {
							font-weight: bold;
							margin-bottom: 10rpx;
							overflow: hidden;
							text-overflow: ellipsis;
							display: -webkit-box;
							-webkit-line-clamp: 2;
							-webkit-box-orient: vertical;
							font-size: 30rpx;
							line-height: 1.5;
						}

						.abstract {
							overflow: hidden;
							text-overflow: ellipsis;
							display: -webkit-box;
							-webkit-line-clamp: 2;
							-webkit-box-orient: vertical;
							font-size: $font-size-tag;
						}

						.read-wrap {
							display: flex;
							color: #999ca7;
							justify-content: flex-start;
							align-items: center;
							margin-top: 10rpx;
							line-height: 1;

							text {
								font-size: $font-size-tag;
							}

							.category-icon {
								width: 8rpx;
								height: 8rpx;
								border-radius: 50%;
								background: $base-color;
								margin-right: 10rpx;
							}

							.date {
								margin-left: 20rpx;
							}
						}
					}
				}
			}
		}
	}
</style>