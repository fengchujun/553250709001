<template>
	<view :style="value.pageStyle">
		<view class="store-wrap">
			<block v-if="value.style == 1">
				<view class="store-box store-one">
					<view class="store-info">
						<view class="info-box" :style="{ color: value.textColor }" @click="toStoreList()">
							<block v-if="globalStoreInfo && globalStoreInfo.store_id">
								<text class="title">{{ globalStoreInfo.store_name }}</text>
								<text>
									<text class="change margin-left">切换</text>
									<text class="iconfont icon-right"></text>
								</text>
							</block>
							<text class="title" v-else>定位中...</text>
						</view>
						<view class="address-wrap" :style="{ color: value.textColor }">
							<text class="iconfont icon-dizhi"></text>
							<text v-if="globalStoreInfo && globalStoreInfo.store_id" @click="mapRoute" class="address">{{ globalStoreInfo.show_address }}</text>
							<text v-else>获取当前位置...</text>
						</view>
					</view>
					<view class="store-image" @click="selectStore()">
						<image :src="$util.img(globalStoreInfo.store_image)" v-if="globalStoreInfo && globalStoreInfo.store_image" mode="aspectFill"></image>
						<image :src="$util.getDefaultImage().store" v-else mode="aspectFill"></image>
					</view>
				</view>
			</block>

			<block v-if="value.style == 2">
				<view class="store-box store-three" @click="toStoreList()">
					<view class="store-info">
						<view class="store-image" @click="selectStore()">
							<image :src="$util.img(globalStoreInfo.store_image)" v-if="globalStoreInfo && globalStoreInfo.store_image" mode="aspectFill"></image>
							<image :src="$util.getDefaultImage().store" v-else mode="aspectFill"></image>
						</view>
						<view class="info-box" :style="{ color: value.textColor }">
							<block v-if="globalStoreInfo && globalStoreInfo.store_id">
								<text class="title">{{ globalStoreInfo.store_name }}</text>
								<text>
									<text class="change margin-left">切换</text>
									<text class="iconfont icon-right"></text>
								</text>
							</block>
							<text class="title" v-else>定位中...</text>
						</view>
					</view>
					<view class="store-icon" @click.stop="search()"><text class="iconfont icon-sousuo3" :style="{ color: value.textColor }"></text></view>
				</view>
			</block>

			<block v-if="value.style == 3">
				<view class="store-box store-four" @click="toStoreList()">
					<view class="store-left-wrap">
						<block v-if="globalStoreInfo && globalStoreInfo.store_id">
							<text class="iconfont icon-weizhi" :style="{ color: value.textColor }"></text>
							<text class="title" :style="{ color: value.textColor }">{{ globalStoreInfo.store_name }}</text>
							<text class="iconfont icon-unfold" :style="{ color: value.textColor }"></text>
						</block>
						<text class="title" v-else>定位中...</text>
					</view>
					<view class="store-right-search">
						<input type="text" class="uni-input font-size-tag" disabled placeholder="商品搜索" @click.stop="search()" />
						<text class="iconfont icon-sousuo3" @click.stop="search()"></text>
					</view>
				</view>
			</block>
		</view>
	</view>
</template>

<script>
// 门店展示
import Map from '@/common/js/map/openMap.js';
export default {
	name: 'diy-store',
	props: {
		value: {
			type: Object,
			default: () => {
				return {};
			}
		}
	},
	data() {
		return {};
	},
	computed: {},
	watch: {
		// 组件刷新监听
		componentRefresh: function(nval) {}
	},
	created() {},
	methods: {
		//跳转至门店列表
		toStoreList() {
			this.$util.redirectTo('/pages_tool/store/list');
		},
		selectStore() {
			if (this.globalStoreInfo) {
				this.$util.redirectTo('/pages_tool/store/detail', { store_id: this.globalStoreInfo.store_id });
			}
		},
		search() {
			this.$util.redirectTo('/pages_tool/goods/search');
		},
		mapRoute() {
			if (!isNaN(Number(this.globalStoreInfo.latitude)) && !isNaN(Number(this.globalStoreInfo.longitude))) {
				Map.openMap(Number(this.globalStoreInfo.latitude), Number(this.globalStoreInfo.longitude), this.globalStoreInfo.store_name, 'gcj02');
			}
		}
	}
};
</script>

<style lang="scss">
.store-wrap {
	.store-box {
		box-sizing: border-box;
		width: 100%;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.store-info {
		height: 100rpx;
		display: flex;
		flex: 1;
		flex-direction: column;
		justify-content: space-around;
		margin-right: 20rpx;

		.info-box {
			display: flex;
			align-items: flex-end;
			margin-bottom: 6rpx;

			text {
				line-height: 1.2;
			}

			.title {
				max-width: 480rpx;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
				font-size: $font-size-toolbar;
				flex: 1;
			}

			.change {
				font-size: $font-size-goods-tag;
			}

			.iconfont {
				font-size: $font-size-goods-tag;
			}
		}
		.address-wrap {
			line-height: 1.2;
			font-size: $font-size-goods-tag;
			display: flex;
			align-items: center;

			.iconfont {
				font-size: $font-size-goods-tag;
				margin-right: 6rpx;
			}

			.address {
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}
		}
	}

	.store-image {
		width: 72rpx;
		height: 72rpx;
		border-radius: 50%;

		image {
			width: 100%;
			height: 100%;
			border-radius: 50%;
		}
	}
}

.store-one,
.store-three {
	// padding: 0 20rpx;
}

.store-two {
	.store-image {
		align-self: flex-start;
		margin-right: 14rpx;
	}
	.info-box {
		margin-bottom: 0 !important;
	}
	.store-info {
		height: 106rpx;
	}
	.switchover {
		display: flex;
		width: 120rpx;
	}
}
.store-three {
	.store-info {
		height: auto;
		justify-content: flex-start;
		flex-direction: inherit;
		align-items: center;
	}
	.info-box {
		margin-left: 18rpx;
		margin-bottom: 0 !important;
	}
	.store-icon text {
		font-size: 36rpx;
		color: #fff;
	}
}

.store-four {
	padding: 0 !important;

	.store-left-wrap {
		display: flex;
		align-items: center;
		line-height: 1;
		.icon-weizhi {
			margin-right: 6rpx;
			font-size: 28rpx;
		}

		.icon-unfold {
			margin-left: 6rpx;
		}

		.title {
			display: inline-block;
			max-width: 160rpx;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
	}

	.store-right-search {
		width: calc(100% - 260rpx);
		position: relative;

		input {
			width: 100%;
			height: 72rpx;
			line-height: 72rpx;
			background-color: #ffffff;
			border: none;
			border-radius: 72rpx;
			padding-left: 30rpx;
			box-sizing: border-box;
		}

		.icon-sousuo3 {
			position: absolute;
			right: 30rpx;
			top: 10rpx;
			font-size: 28rpx;
			color: #909399;
		}
	}
}
</style>
