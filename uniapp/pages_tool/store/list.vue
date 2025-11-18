<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="store-list-wrap">
		<view class="curr-store" v-if="globalStoreConfig && globalStoreConfig.store_business == 'store'">
			<view class="store-desc">当前定位</view>
			<view class="store-name-wrap">
				<view class="store-name multi-hidden">{{ currentPosition || '定位中...' }}</view>
				<view class="store-position" @click="reposition()">
					<text class="iconfont icon-dingwei"></text>
					<text>重新定位</text>
				</view>
			</view>
		</view>
		<view class="store-list-box">
			<view class="store-list-head">
				<view class="head-name">门店列表</view>
				<view class="head-search">
					<text class="iconfont icon-sousuo" @click="getData()"></text>
					<input type="text" v-model="keyword" placeholder-class="input-placeholder" placeholder="搜索门店" @confirm="getData()" />
				</view>
			</view>

			<scroll-view scroll-y="true" class="store-list-body" :style="{ height: globalStoreConfig && globalStoreConfig.store_business == 'store' ? 'calc(100vh - 320rpx)' : '' }">
				<view :class="['store-item', { active: globalStoreInfo && item.store_id == globalStoreInfo.store_id }]" v-for="(item, index) in dataList" :key="index" @click="storeTap(item)">
					<view class="item-state" :class="item.is_frozen.is_frozen == 1 || item.status == 0 ? 'warning' : ''">
						{{ (item.is_frozen.is_frozen == 1 && '已停业') || (item.status == 0 && '休息中') || (item.status == 1 && '营业中') || '--' }}
					</view>
					<view class="item-name multi-hidden">{{ item.store_name }}</view>
					<view class="item-close-desc" v-if="item.status == 0 && item.close_desc">
						{{ item.close_desc }}
					</view>
					<view class="item-time">
						<view class="item-time-left">
							<text class="iconfont icon-shijian1"></text>
							<text>{{ item.open_date || '--' }}</text>
						</view>
						<view class="item-time-right" v-if="item.distance">
							{{ item.distance > 1 ? item.distance + 'km' : item.distance * 1000 + 'm' }}
						</view>
					</view>
					<view class="item-address">
						<text class="iconfont icon-location"></text>
						<text>{{ item.show_address }}</text>
					</view>
					<view class="item-other">
						<view class="other-tag-wrap">
							<text class="tag-item" v-if="item.is_default == 1">总店</text>
							<text class="tag-item" v-if="item.is_pickup == 1">门店自提</text>
							<text class="tag-item" v-if="item.is_o2o == 1">同城配送</text>
							<text class="tag-item" v-if="item.is_express == 1">物流配送</text>
						</view>
						<view class="other-action" @click.stop="selectStore(item)">
							<text>详情</text>
							<text class="iconfont icon-right"></text>
						</view>
					</view>
				</view>
				<ns-empty v-if="!dataList.length" text="您的附近暂无可选门店" :isIndex="false"></ns-empty>
			</scroll-view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
	import Config from '@/common/js/config.js';
	export default {
		components: {},
		data() {
			return {
				dataList: [],
				latitude: null, // 纬度
				longitude: null, // 经度
				currentPosition: '', //当前位置
				keyword: '' //搜索内容
			};
		},
		watch: {
			location: function(nVal) {
				if (nVal) {
					this.latitude = nVal.latitude;
					this.longitude = nVal.longitude;
					this.getData();
					this.getCurrentLocation();
				}
			}
		},
		onLoad(option) {
			// #ifdef H5
			// H5地图选择位置回调数据
			if (option.module && option.module == 'locationPicker') {
				this.latitude = option.latng.split(',')[0];
				this.longitude = option.latng.split(',')[1];
				this.currentPosition = option.addr + option.name;
			}
			// #endif

			//地图选点已经选中坐标的话就不要再重复选择了
			if(!this.currentPosition){
				if (this.location) {
					this.latitude = this.location.latitude;
					this.longitude = this.location.longitude;
					this.getCurrentLocation();
				} else if (this.mapConfig.wap_is_open == 1) {
					this.$nextTick(()=>{
						this.$util.getLocation({
							fail: res => {
								// 拒绝定位
								this.currentPosition = '未获取到定位';
							}
						});
					})
				}
			}
			
			this.getData();
		},
		onShow() {
			// 定位信息过期后，重新获取定位
			if(this.mapConfig.wap_is_open == 1 && this.locationStorage && this.locationStorage.is_expired) {
				this.$util.getLocation({
					fail: (res) => {
						// 拒绝定位
						this.currentPosition = '未获取到定位';
					}
				});
			}
		},
		methods: {
			getData() {
				let data = {};
				data.keyword = this.keyword;
				if (this.latitude && this.longitude) {
					data.latitude = this.latitude;
					data.longitude = this.longitude;
				}

				this.$api.sendRequest({
					url: '/api/store/page',
					data: data,
					success: res => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
						if (res.code == 0 && res.data) {
							this.dataList = res.data.list;
							this.dataList.forEach(item => {
								item.show_address = item.full_address.replace(/,/g, ' ') + ' ' + item.address;
							});
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
					},
					fail: res => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			storeTap(item) {
				uni.setStorageSync('manual_store_info', item); // 记录手动切换的门店
				this.changeStore(item, true); // 切换门店数据
			},
			selectStore(item) {
				this.$util.redirectTo('/pages_tool/store/detail', {
					store_id: item.store_id
				});
			},
			// 根据经纬度获取位置
			getCurrentLocation() {
				let data = {};
				if (this.latitude && this.longitude) {
					data.latitude = this.latitude;
					data.longitude = this.longitude;
				}

				this.$api.sendRequest({
					url: '/api/store/getLocation',
					data: data,
					success: res => {
						if (res.code == 0 && res.data) {
							this.currentPosition = res.data.formatted_addresses.recommend; // 结合知名地点形成的描述性地址，更具人性化特点
						} else {
							this.currentPosition = '未获取到定位';
						}
					}
				});
			},
			// 打开地图重新选择位置
			reposition() {
				// #ifdef MP
				uni.chooseLocation({
					success: res => {
						this.latitude = res.latitude;
						this.longitude = res.longitude;
						this.currentPosition = res.name;
						this.getData();
						this.getCurrentLocation();
					},
					fail(res) {
						uni.getSetting({
							success: function(res) {
								var statu = res.authSetting;
								if (!statu['scope.userLocation']) {
									uni.showModal({
										title: '是否授权当前位置',
										content: '需要获取您的地理位置，请确认授权，否则地图功能将无法使用',
										success(tip) {
											if (tip.confirm) {
												uni.openSetting({
													success: function(data) {
														if (data.authSetting['scope.userLocation'] === true) {
															this.$util.showToast({
																title: '授权成功'
															});
															//授权成功之后，再调用chooseLocation选择地方
															setTimeout(function() {
																uni.chooseLocation({
																	success: data => {
																		this.latitude = res.latitude;
																		this.longitude = res.longitude;
																		this.currentPosition = res.name;
																		this.getData();
																		this.getCurrentLocation();
																	}
																});
															}, 1000);
														}
													}
												});
											} else {
												this.$util.showToast({
													title: '授权失败'
												});
											}
										}
									});
								}
							}
						});
					}
				});
				// #endif

				// #ifdef H5
				let backurl = Config.h5Domain + '/pages_tool/store/list'; // 地图选择位置后的回调页面路径
				window.location.href = 'https://apis.map.qq.com/tools/locpicker?search=1&type=0&backurl=' + encodeURIComponent(backurl) + '&key=' + Config.mpKey + '&referer=myapp';
				// #endif
			}
		}
	};
</script>
<style scoped lang="scss">
	/deep/ .input-placeholder {
		color: #b3b4b9;
		font-size: $font-size-tag;
	}
</style>
<style lang="scss" scoped>
	.store-list-wrap {
		.curr-store {
			background-color: #fff;
			margin-bottom: 20rpx;
			padding: 20rpx 24rpx 0;

			.store-desc {
				font-size: $font-size-tag;
				color: #636363;
			}

			.store-name-wrap {
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding: 12rpx 0 30rpx;

				.store-name {
					width: 500rpx;
					font-size: $font-size-sub;
					font-weight: bold;
					line-height: 1.5;
				}

				.store-position {
					font-size: $font-size-tag;
					color: #df5948;

					.iconfont {
						margin-right: 10rpx;
					}
				}
			}
		}

		.store-list-box {
			background-color: #fff;
			padding: 0 24rpx 24rpx;

			.store-list-head {
				padding: 34rpx 0 10rpx;
				display: flex;
				align-items: center;
				justify-content: space-between;

				.head-name {
					font-size: $font-size-sub;
					color: #666;
				}

				.head-search {
					display: flex;
					align-items: center;
					width: 218rpx;
					height: 68rpx;
					background-color: #f0f1f3;
					border-radius: 50rpx;
					color: #b3b4b9;
					padding: 0 26rpx;
					box-sizing: border-box;

					.iconfont {
						font-size: $font-size-sub;
						margin-right: 10rpx;
					}

					input {
						color: #666;
					}
				}
			}

			.store-list-body {
				.store-item {
					margin: 20rpx 6rpx 30rpx;
					padding: 26rpx 28rpx;
					display: flex;
					flex-direction: column;
					align-items: baseline;
					box-shadow: 0 0 10rpx 0 rgba(128, 132, 148, 0.3);
					border-radius: 10rpx;

					&.active {
						border: 2rpx solid #df5948;
					}

					.item-state {
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

					.item-name {
						margin: 24rpx 0 10rpx;
						font-size: $font-size-toolbar;
						font-weight: bold;
						line-height: 1.5;
					}
					
					.item-close-desc{
						font-size: $font-size-tag;
						color:red;
					}

					.item-time {
						display: flex;
						align-items: center;
						justify-content: space-between;
						width: 100%;

						.item-time-left {
							display: flex;
							align-items: center;
							justify-content: space-between;
							font-size: $font-size-tag;
							color: #5f6067;

							.iconfont {
								margin-right: 10rpx;
							}
						}

						.item-time-right {
							color: #5f6067;
							font-size: $font-size-tag;
						}
					}

					.item-address {
						margin-top: 6rpx;
						font-size: $font-size-tag;
						color: #5f6067;
						line-height: 1.3;

						.iconfont {
							margin-right: 10rpx;
						}
					}

					.item-other {
						width: 100%;
						display: flex;
						align-items: center;
						justify-content: space-between;
						margin-top: 26rpx;

						.other-tag-wrap {
							.tag-item {
								padding: 8rpx 12rpx;
								margin-right: 20rpx;
								font-size: $font-size-tag;
								color: #77ab69;
								background-color: #f3f9ed;
								border-radius: 4rpx;
							}
						}

						.other-action {
							display: flex;
							align-items: baseline;
							font-size: $font-size-tag;
							color: #df5948;
							line-height: 1;

							.iconfont {
								font-size: $font-size-tag;
							}
						}
					}
				}
			}
		}
	}
</style>