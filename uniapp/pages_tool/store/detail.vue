<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="store-detail" v-if="store">
		<view class="detail-head" :style="{ height: swiperHeight }">
			<swiper class="swiper" @change="swiperChange" autoplay="true" interval="4000" circular="true">
				<swiper-item v-for="(item, index) in store.store_images.images" :key="index" :item-id="'store_id_' + index">
					<view class="item" @click="previewMedia(index)">
						<image :src="$util.img(item)" @error="swiperImageError(index)" mode="aspectFit" />
					</view>
				</swiper-item>
			</swiper>
			<view class="img-indicator-dots" v-if="store.store_images.images && store.store_images.images.length">
				<text>{{ swiperCurrent }}</text>
				<text>/{{ store.store_images.images.length }}</text>
			</view>
		</view>
		<view class="detail-content">
			<view class="content-item">
				<view class="store-name multi-hidden">{{ store.store_name }}</view>
				<view class="store-state" :class="store.is_frozen.is_frozen == 1 || store.status == 0 ? 'warning' : ''">
					{{ (store.is_frozen.is_frozen == 1 && '已停业') || (store.status == 0 && '休息中') || (store.status == 1 && '营业中') || '--' }}
				</view>
			</view>
			<view class="content-item store-time-wrap" v-if="store.open_date || store.is_default || store.is_pickup || store.is_o2o || store.is_express">
				<view v-if="store.status == 0 && store.close_desc" class="close-desc">{{ store.close_desc }}</view>
				<view class="store-time" v-if="store.open_date">{{ store.open_date }}</view>
				<view class="tag-wrap" v-if="store.is_default || store.is_pickup || store.is_o2o || store.is_express">
					<text class="tag-item" v-if="store.is_default == 1">总店</text>
					<text class="tag-item" v-if="store.is_pickup == 1">门店自提</text>
					<text class="tag-item" v-if="store.is_o2o == 1">同城配送</text>
					<text class="tag-item" v-if="store.is_express == 1">物流配送</text>
				</view>
			</view>
			
			<view class="content-item address-wrap" v-if="store.show_address || store.distance">
				<view class="address-box">
					<view class="address-name" v-if="store.show_address">{{ store.show_address }}</view>
					<view class="address-location" v-if="store.distance">
						<text class="icondiy icon-system-weizhi"></text>
						<text>距您当前位置{{ store.distance }}km</text>
					</view>
				</view>
				<text class="icondiy icon-daohang" @click="mapRoute()"></text>
			</view>
			<view class="content-item telphone-wrap" v-if="store.telphone">
				<text v-if="store.telphone" class="telphone">{{ store.telphone }}</text>
				<text class="iconfont icon-dianhua" @click="phoneCall"></text>
			</view>
		</view>
		<view class="detail-map">
			<view class="map-head">门店地图</view>
			<map class="map-body" :latitude="store.latitude" :longitude="store.longitude" :markers="covers"></map>
		</view>
		<!-- <view class="store-action-fill"></view>
		<view class="store-action"><button type="primary" @click="storeTap()">进入门店</button></view> -->
	</view>
</template>

<script>
	import Map from '@/common/js/map/openMap.js';
	export default {
		data() {
			return {
				storeId: 0,
				latitude: null, // 纬度
				longitude: null, // 经度
				covers: [],
				store: null,
				swiperCurrent: 1,
				swiperHeight: ''
			};
		},
		onLoad(options) {
			this.storeId = options.store_id || 0;
			if (this.location) {
				this.latitude = this.location.latitude;
				this.longitude = this.location.longitude;
			} else if (this.mapConfig.wap_is_open == 1) {
				this.$util.getLocation();
			}
			this.getInfo();
		},
		watch: {
			location: function(nVal) {
				if (nVal) {
					this.latitude = nVal.latitude;
					this.longitude = nVal.longitude;
					this.getInfo();
				}
			}
		},
		methods: {
			//打电话
			phoneCall() {
				uni.makePhoneCall({
					phoneNumber: this.store.telphone //仅为示例
				});
			},
			//获取门店详情
			getInfo() {
				let data = {
					store_id: this.storeId
				};
				if (this.latitude && this.longitude) {
					data.latitude = this.latitude;
					data.longitude = this.longitude;
				}
				this.$api.sendRequest({
					url: '/api/store/info',
					data: data,
					success: res => {
						if (res.data) {
							// 默认数据
							let defaultData = {
								full_address: '',
								address: '',
								store_images: []
							};

							this.store = res.data || defaultData;
							this.covers.push({
								id: 1,
								latitude: this.store.latitude,
								longitude: this.store.longitude,
								iconPath: this.$util.img('public/uniapp/store/map_icon.png'),
								height: 25
							});
							this.store.show_address = this.store.full_address.replace(/,/g, ' ') + ' ' + this
								.store.address;
							this.handleStoreImage();
						} else {
							this.$util.showToast({
								title: '门店不存在'
							});
							setTimeout(() => {
								this.$util.redirectTo('/pages_tool/store/list', {}, 'redirectTo');
							}, 2000);
						}
					}
				});
			},
			// 处理门店图片+图片高度
			handleStoreImage() {
				if (!this.store.store_images) this.store.store_images = [];
				this.store.store_images = this.store.store_images.reduce((pre, cur) => {
					// 图片
					if (!pre.images) pre.images = [];
					if (pre.images) pre.images.push(cur.pic_path);
					// 图片规格
					if (!pre.spec) pre.spec = [];
					if (pre.spec) pre.spec.push(cur.pic_spec);
					return pre;
				}, {});

				let maxHeight = '';
				if (this.store.store_images.spec) {
					this.store.store_images.spec.forEach((item, index) => {
						if (typeof item == 'string') item = item.split('*');

						uni.getSystemInfo({
							success: res => {
								let ratio = item[0] / res.windowWidth;
								item[0] = item[0] / ratio;
								item[1] = item[1] / ratio;
							}
						});

						if (!maxHeight || maxHeight > item[1]) {
							maxHeight = item[1];
						}
					});
				}
				this.swiperHeight = Number(maxHeight) + 'px';

				if (!Object.keys(this.store.store_images).length) {
					this.store.store_images = {};
					this.store.store_images.images = [this.$util.img('public/static/img/default_img/square.png')];
					this.store.store_images.spec = ['350*350'];
					this.swiperHeight = '380px';
				}
			},
			swiperChange(e) {
				this.swiperCurrent = e.detail.current + 1;
			},
			mapRoute() {
				Map.openMap(Number(this.store.latitude), Number(this.store.longitude), this.store.store_name, 'gcj02');
			},
			swiperImageError() {
				this.store.store_images.images = this.$util.img('public/static/img/default_img/square.png');
			}
		}
	};
</script>

<style lang="scss">
	page {
		background-color: #f5f6fa;
	}

	.store-detail {
		.detail-head {
			position: relative;
			width: 100%;
			height: 300rpx;
			background-color: #fff;

			&::after {
				content: '';
				position: absolute;
				left: 0;
				right: 0;
				bottom: 0;
				height: 112rpx;
				background-image: linear-gradient(transparent 10%, #f5f6fa);
			}

			.swiper {
				width: 100%;
				height: 100%;

				.item {
					width: 100%;
					height: 100%;
				}

				image {
					width: 100%;
					height: 100%;
				}
			}

			.img-indicator-dots {
				position: absolute;
				z-index: 5;
				bottom: 60rpx;
				right: 40rpx;
				background: rgba(100, 100, 100, 0.4);
				color: #fff;
				font-size: $font-size-tag;
				line-height: 40rpx;
				border-radius: 20rpx;
				padding: 0 20rpx;
			}
		}

		.detail-content {
			position: relative;
			background-color: #fff;
			margin: -30rpx 30rpx 30rpx;
			padding: 0 24rpx;
			border-radius: 18rpx;
			z-index: 9;

			.content-item {
				display: flex;
				align-items: center;
				justify-content: space-between;
				border-bottom: 2rpx solid #ededed;
				padding: 24rpx 0;

				&:last-of-type {
					border-bottom: 0;
				}
			}

			.store-name {
				font-size: $font-size-toolbar;
				font-weight: bold;
				line-height: 1.5;
				padding: 6rpx 0;
			}

			.store-state {
				padding: 8rpx 10rpx;
				font-size: $font-size-tag;
				border: 2rpx solid #66ad95;
				color: #66ad95;
				border-radius: 4rpx;
				line-height: 1;
				&.warning{
					color:red;
					border-color: red;
				}
			}
			
			.store-time-wrap {
				flex-direction: column;
				align-items: baseline;

				.store-time {
					font-size: $font-size-tag;
					color: $color-sub;
				}
				
				.close-desc{
					color:red;
					font-size: $font-size-tag;
				}

				.tag-wrap {
					margin-top: 20rpx;
					display: flex;
					flex-wrap: wrap;

					.tag-item {
						padding: 8rpx 10rpx;
						margin-right: 10rpx;
						color: #6f7dad;
						background: #f4f5fa;
						border-radius: 6rpx;
						line-height: 1;
						font-size: $font-size-tag;
					}
				}
			}

			.telphone-wrap {
				padding: 26rpx 0;

				.telphone {
					font-weight: bold;
					color: $base-color;
					font-size: $font-size-sub;
				}

				&>.iconfont {
					width: 60rpx;
					height: 48rpx;
					line-height: 48rpx;
					text-align: center;
					background-color: #f4f5fa;
					border-radius: 6rpx;
				}
			}

			.address-wrap {
				.address-name {
					width: 520rpx;
					line-height: 1.5;
					color: $color-sub;
					font-size: $font-size-tag;
				}

				.address-location {
					margin-top: 12rpx;
					display: flex;
					align-items: center;
					font-size: $font-size-tag;
					color: #999ca7;

					.icondiy {
						font-size: $font-size-tag;
						margin-right: 4rpx;
					}
				}

				&>.icondiy {
					width: 60rpx;
					height: 48rpx;
					line-height: 48rpx;
					text-align: center;
					background-color: #f4f5fa;
					border-radius: 6rpx;
				}
			}
		}

		.detail-map {
			background-color: #fff;
			margin: 0 30rpx 30rpx;
			border-radius: 18rpx;
			margin-bottom: calc(constant(safe-area-inset-bottom) + 170rpx);
			margin-bottom: calc(env(safe-area-inset-bottom) + 170rpx);

			.map-head {
				padding-left: 24rpx;
				height: 100rpx;
				line-height: 100rpx;
				font-size: $font-size-toolbar;
				font-weight: bold;
			}

			.map-body {
				width: 100%;
				height: 460rpx;
			}
		}

		.store-action-fill {
			padding-bottom: calc(constant(safe-area-inset-bottom) + 170rpx);
			padding-bottom: calc(env(safe-area-inset-bottom) + 170rpx);
		}

		.store-action {
			position: fixed;
			background-color: #fff;
			bottom: 0;
			right: 0;
			left: 0;
			display: flex;
			justify-content: center;
			padding: 30rpx 0;

			button {
				width: 406rpx;
				color: #fff;
				font-size: 30rpx;
				border-radius: 50rpx;
			}
		}
	}
</style>