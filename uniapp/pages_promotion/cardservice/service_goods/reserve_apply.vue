<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="reserve-wrap">
		<view class="reserve-item">
			<image :src="$util.img(serviceDetail.goods_image)" mode="aspectFill"></image>
			<view class="conten">
				<view class="name multi-hidden">{{ serviceDetail.goods_name }}</view>
				<view class="price-wrap">
					<view class="price">
						<text>￥</text>
						<text v-if="serviceDetail.discount_price > 0 && serviceDetail.discount_price < serviceDetail.price">{{ serviceDetail.discount_price }}</text>
						<text v-else>{{ serviceDetail.price }}</text>
					</view>
					<view class="line-price">￥{{ serviceDetail.price }}</view>
				</view>
				<view class="btn-wrap">
					<text class="num">已预约{{ serviceDetail.sale_num }}人次</text>
					<view class="server-detail">
						<text>服务详情</text>
						<text class="icondiy icon-system-jiantouyou" @click="toDetail(goodsId)"></text>
					</view>
				</view>
			</view>
		</view>
		<view class="store-select">
			<view class="select-server" @click="openStorePopup()">
				<text class="iconfont icon-mendian"></text>
				<text class="txt">选择门店</text>
				<text class="icondiy icon-system-jiantouyou arrows"></text>
			</view>
			<view class="store-info" v-if="storeInfo">
				<view class="store-name">{{ storeInfo.store_name }}</view>
				<view class="store-time">营业时间：{{ storeInfo.open_date }}</view>
				<view class="store-addres">地址：{{ storeInfo.full_address }} {{ storeInfo.address }}</view>
			</view>
		</view>

		<view class="select-server" @click="openServicePopup()">
			<text class="icondiy icon-xuanzhaijishi"></text>
			<text class="txt">选择服务人员</text>
			<view class="service-user">
				<text class="txt" v-if="user">{{ user.username }}</text>
				<text class="icondiy icon-system-jiantouyou arrows"></text>
			</view>
		</view>

		<view class="reserve-panel">
			<view class="panel-title">
				<text class="icondiy icon-shijian"></text>
				<text>预约时间</text>
			</view>
			<yuyue-date ref="timePopup" :disableWeek="disableWeek" :beginTime="timeInfo.start_time" :endTime="timeInfo.end_time" :timeInterval="timeInterval" @change="getTime"></yuyue-date>
		</view>
		<view class="tab-bar-fill"></view>
		<view class="tab-bar">
			<ns-goods-action-icon text="首页" icon="icondiy icon-shouye" @click="goHome" />
			<ns-goods-action-icon text="客服" icon="icondiy icon-qiafu" :send-data="contactData" :chatParam="chatRoomParams" />
			<view class="tab-bar-item" @click="editCollection()">
				<view class="action-icon-wrap">
					<view v-if="whetherCollection == 0" class="icondiy icon-shouzang"></view>
					<view v-else class="icondiy icon-shouzang selected-collection"></view>
					<text>收藏</text>
				</view>
			</view>
			<view class="bnutton-body">
				<button class="reserve-btn" type="default" @click="setAdd()">确认预约</button>
			</view>
		</view>

		<view @touchmove.prevent.stop>
			<uni-popup ref="servicePopup" type="bottom">
				<view class="goods-coupon-popup-layer popup-layer">
					<view class="head-wrap" @click="closeServicePopup()">
						<text>选择服务人员</text>
						<text class="iconfont icon-close"></text>
					</view>
					<scroll-view class="service-body scroll" scroll-y>
						<view v-for="(item, index) in serviceList" :key="index" @click="setUser(item)">
							<text class="user-name">{{ item.username }}</text>
						</view>
					</scroll-view>
					<view class="button-box"><button type="primary" @click="closeServicePopup()">确定</button></view>
				</view>
			</uni-popup>
		</view>

		<view @touchmove.prevent.stop>
			<uni-popup ref="storePopup" type="bottom">
				<view class="goods-coupon-popup-layer popup-layer">
					<view class="head-wrap" @click="closeStorePopup()">
						<text>选择门店列表</text>
						<text class="iconfont icon-close"></text>
					</view>
					<scroll-view class="store-body scroll" scroll-y>
						<view class="store-select" v-for="(item, index) in storeList" :key="index" @click="setStore(item)">
							<view class="store-info">
								<view class="store-name">{{ item.store_name }}</view>
								<view class="store-time">营业时间：{{ item.open_date }}</view>
								<view class="store-addres">地址：{{ item.full_address }} {{ item.address }}</view>
							</view>
						</view>
					</scroll-view>
					<view class="button-box"><button type="primary" @click="closeStorePopup()">确定</button></view>
				</view>
			</uni-popup>
		</view>
		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
	import yuyueDate from '@/pages_promotion/components/yuyue-date/yuyue-date.vue';
	import uniPopup from '@/components/uni-popup/uni-popup.vue';

	export default {
		components: {
			yuyueDate,
			uniPopup
		},
		data() {
			return {
				disableWeek: [], // 限制周几不可以预约
				disableWeekTo: [],
				goodsId: 0,
				date: "",
				time: "",
				remark: "",
				serviceList: [],
				serviceDetail: "",
				member_id: 0,
				storeList: [],
				storeInfo: "",
				user: "",
				timeInfo: {},
				timeInterval: "1",
				contactData: {},
				chatRoomParams: {},
				whetherCollection: 0
			};
		},
		onLoad(option) {
			if (option.goods_id) {
				this.goodsId = option.goods_id;
			} else {
				this.$util.showToast({
					title: '未找到服务信息',
					mask: true,
					duration: 2000
				});
				setTimeout(() => {
					this.$util.redirectTo('/pages/index/index');
				}, 2000);
			}
		},
		onShow() {
			if (!this.storeToken) {
				this.$nextTick(() => {
					this.$refs.login.open('/pages_promotion/cardservice/service_goods/reserve_apply?goods_id=' + this.goodsId);
				});
			}

			this.getDetail();
			this.getWhetherCollection();
		},
		methods: {
			getDetail() {
				this.$api.sendRequest({
					url: '/cardservice/api/service/detail',
					data: {
						goods_id: this.goodsId
					},
					success: res => {
						if (res.code >= 0) {
							this.serviceDetail = res.data.goods_sku_detail;
							this.contactData = {
								title: this.serviceDetail.goods_name,
								path: "",
								img: this.$util.img(this.serviceDetail.goods_image, {
									size: 'big'
								})
							}

							this.chatRoomParams = {
								sku_id: this.serviceDetail.sku_id,
								type: 'cardservice',
								type_id: ""
							};

							if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
							this.getStoreList();
						} else {
							this.$util.showToast({
								title: '未找到服务信息',
								mask: true,
								duration: 2000
							});
							setTimeout(() => {
								this.$util.redirectTo('/pages/index/index');
							}, 2000);
						}
					},
					fail: res => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			getTime(data) {
				this.date = data.date;
				this.time = data.time;
			},
			toDetail(id) {
				this.$util.redirectTo('/pages/goods/detail', {
					"goods_id": this.goodsId
				});
			},
			// 打开员工弹出层
			openServicePopup() {
				if (this.storeInfo == "") {
					this.$util.showToast({
						title: '请先选择门店',
						mask: true,
						duration: 1000
					});
					return;
				}
				this.getServiceList();
			},
			// 关闭员工弹出层
			closeServicePopup() {
				this.$refs.servicePopup.close();
			},
			// 打开门店弹出层
			openStorePopup() {
				this.$refs.storePopup.open();
			},
			// 关闭门店弹出层
			closeStorePopup() {
				this.$refs.storePopup.close();
			},
			getServiceList() {
				this.$api.sendRequest({
					url: '/cardservice/api/reserve/servicerList',
					data: {
						'store_id': this.storeInfo.store_id
					},
					success: res => {
						if (res.code >= 0) {
							this.serviceList = res.data;
							if (this.serviceList.length > 0) {
								this.$refs.servicePopup.open();
							} else {
								this.$util.showToast({
									title: '该门店暂无服务人员',
									mask: true,
									duration: 1000
								});
							}
						}
					}
				});
			},
			setAdd() {
				let goods = [];
				goods.push({
					sku_id: this.serviceDetail.sku_id,
					uid: this.user.uid
				});
				if (!this.verify()) return;
				let params = {
					goods: JSON.stringify(goods),
					date: this.date,
					time: this.time,
					store_id: this.storeInfo.store_id,
					username: this.user.username
				};
				uni.setStorageSync('reserveParams', params);
				this.$util.redirectTo('/pages_promotion/cardservice/service_goods/reserve_detail', {
					"goods_id": this.goodsId
				});
			},
			getStoreList() {
				let store_ids = this.serviceDetail.sale_store;
				this.$api.sendRequest({
					url: '/api/store/page',
					data:{
						status : 'all',
						store_ids:store_ids,
					},
					success: res => {
						if (res.code >= 0 && res.data.list.length > 0) {
							this.storeList = res.data.list;
						}
					}
				});
			},
			setStore(item) {
				this.storeInfo = item;
				this.getStoreTime();
				this.closeStorePopup();
			},
			setUser(item) {
				this.user = item;
				this.closeServicePopup();
			},
			getStoreTime() {
				this.$api.sendRequest({
					url: '/cardservice/api/reserve/getTimeConfig',
					data: {
						store_id: this.storeInfo.store_id
					},
					success: res => {
						if (res.code == 0) {
							this.timeInfo = res.data.value;
							var timeArr = [1, 2, 3, 4, 5, 6, 0];
							for (var k = 0; k < this.timeInfo.week.length; k++) {
								this.timeInfo.week[k] = parseInt(this.timeInfo.week[k]);
							}
							this.disableWeek = this.getArrDifference(this.timeInfo.week, timeArr);
							this.timeInterval = this.timeInfo.interval;
							this.disableWeekTo = this.disableWeek;
							for (var i = 0; i < this.disableWeek.length; i++) {
								if (this.disableWeek[i] == 1) {
									this.disableWeek[i] = "周一";
								}
								if (this.disableWeek[i] == 2) {
									this.disableWeek[i] = "周二";
								}
								if (this.disableWeek[i] == 3) {
									this.disableWeek[i] = "周三";
								}
								if (this.disableWeek[i] == 4) {
									this.disableWeek[i] = "周四";
								}
								if (this.disableWeek[i] == 5) {
									this.disableWeek[i] = "周五";
								}
								if (this.disableWeek[i] == 6) {
									this.disableWeek[i] = "周六";
								}
								if (this.disableWeek[i] == 0) {
									this.disableWeek[i] = "周日";
								}
							}
						}
					}
				});
			},
			getArrDifference(arr1, arr2) {
				return arr1.concat(arr2).filter(function(v, i, arr) {
					return arr.indexOf(parseInt(v)) === arr.lastIndexOf(v);
				});
			},
			goHome() {
				this.$util.redirectTo('/pages/index/index');
			},
			verify() {
				if (this.storeInfo == "") {
					this.$util.showToast({
						title: '请选择门店',
					});
					return false;
				}

				if (this.user == "") {
					this.$util.showToast({
						title: '请选择服务人员',
					});
					return false;
				}

				let week = new Date().getDay()
				if (this.disableWeekTo.indexOf(week) != -1) {
					this.$util.showToast({
						title: '该天不支持预约',
					});
					return false;
				}
				
				if(!this.time){
					this.$util.showToast({
						title: '请选择时间段',
					});
					return false;
				}

				return true;
			},
			//获取用户是否关注
			getWhetherCollection() {
				this.$api.sendRequest({
					url: "/api/goodscollect/iscollect",
					data: {
						goods_id: this.goodsId
					},
					success: res => {
						this.whetherCollection = res.data;
					}
				});
			},
			editCollection() {
				if (this.storeToken) {
					//未关注添加关注
					if (this.whetherCollection == 0) {
						this.$api.sendRequest({
							url: "/api/goodscollect/add",
							data: {
								sku_id: this.serviceDetail.sku_id,
								goods_id: this.serviceDetail.goods_id,
								sku_name: this.serviceDetail.sku_name,
								sku_price: this.serviceDetail.price,
								sku_image: this.serviceDetail.sku_image
							},
							success: res => {
								var data = res.data;
								if (data > 0) {
									this.whetherCollection = 1;
									this.$util.showToast({
										title: '关注成功',
									});
								}
							}
						});
					} else {
						//已关注取消关注
						this.$api.sendRequest({
							url: "/api/goodscollect/delete",
							data: {
								goods_id: this.serviceDetail.goods_id
							},
							success: res => {
								var data = res.data;
								if (data > 0) {
									this.whetherCollection = 0;
									this.$util.showToast({
										title: '取消关注',
									});
								}
							}
						});
					}
				}
			},
		}
	};
</script>

<style lang="scss">
	.reserve-wrap {
		padding: 24rpx;

		.reserve-item {
			position: relative;
			margin-bottom: 20rpx;
			padding: 28rpx 24rpx;
			display: flex;
			border-radius: 18rpx;
			background-color: #fff;

			image {
				width: 200rpx;
				height: 200rpx;
				border-radius: 10rpx;
				background-color: pink;
				margin-right: 20rpx;
				overflow: hidden;
			}

			.conten {
				overflow: hidden;
				flex: 1;
				display: flex;
				flex-direction: column;
				width: 420rpx;

				.name {
					font-size: 30rpx;
					font-weight: bold;
					line-height: 1.5;
				}

				.price-wrap {
					display: flex;
					flex-wrap: wrap;
					align-items: center;
				}

				.price {
					display: flex;
					align-items: baseline;
					font-size: $font-size-tag;
					color: $base-color;
					margin-right: 20rpx;

					text:last-child {
						font-size: $font-size-toolbar;
					}
				}

				.line-price {
					color: #999;
					text-decoration: line-through;
				}

				.btn-wrap {
					display: flex;
					align-items: center;
					justify-content: space-between;
					margin-top: auto;

					.num {
						font-size: $font-size-tag;
						color: $color-tip;
					}

					.server-detail {
						position: absolute;
						right: 0;
						display: flex;
						align-items: center;
						justify-content: center;
						width: 158rpx;
						height: 44rpx;
						border-top-left-radius: 30rpx;
						border-bottom-left-radius: 30rpx;
						background-color: #F2F2F2;
						font-size: $font-size-tag;

						text:first-of-type {
							margin-left: 10rpx;
						}

						text:last-of-type {
							margin-left: 6rpx;
							font-size: $font-size-tag;
						}
					}
				}
			}
		}

		.select-server {
			margin-bottom: 20rpx;
			padding: 0 20rpx;
			height: 88rpx;
			display: flex;
			align-items: center;
			background-color: #fff;
			border-radius: 18rpx;

			text:first-child {
				margin-right: 12rpx;
			}

			.txt {
				font-weight: bold;
			}

			.arrows {
				margin-left: auto;
				font-size: $font-size-tag;
			}

			.service-user {
				margin-left: auto;

				.txt {
					color: #5A5A5A;
					font-size: 24rpx;
					margin-right: 20rpx;
				}

				.icondiy {
					vertical-align: text-top;
				}
			}
		}

		.reserve-panel {
			padding: 20rpx 24rpx;
			background-color: #fff;
			border-radius: 18rpx;

			.panel-title {
				display: flex;
				align-items: center;
				line-height: 1;
				margin-top: 10rpx;

				text:last-of-type {
					margin-left: 12rpx;
					font-weight: bold;
				}
			}
		}

		.tab-bar-fill {
			height: 98rpx;
		}

		.tab-bar {
			position: fixed;
			left: 0;
			right: 0;
			bottom: 0;
			display: flex;
			align-items: center;
			justify-content: space-between;
			height: 98rpx;
			line-height: 98rpx;
			background-color: #fff;

			.bnutton-body {
				margin: 0 20rpx;
			}

			.tab-bar-item {
				.action-icon-wrap {
					display: flex;
					flex-direction: column;
					justify-content: center;
					height: 100rpx;
					min-width: 90rpx;
					text-align: center;
					position: relative;
					margin-right: 6rpx;
					font-size: $font-size-tag;

					.icondiy {
						margin: 0 auto 10rpx;
						line-height: 1;
						font-size: 40rpx;
					}

					text {
						font-size: 24rpx;
						line-height: 1;
					}

					.selected-collection {
						color: #FF0000;
					}
				}
			}

			.reserve-btn {
				margin: 0;
				width: 426rpx;
				background-color: $base-color;
				border-radius: 50rpx;
				flex: 1;
				height: 72rpx;
				font-weight: 600;
				font-size: 30rpx;
				line-height: 72rpx;
				border: none;
				color: #fff;
				text-align: center;
			}
		}

		.store-select {
			margin: 30rpx 0;

			.select-server {
				margin-bottom: 0rpx;
			}

			.store-info {
				padding: 0 20rpx;
				flex-wrap: wrap;
				overflow: scroll;
				background-color: #fff;
				height: auto;

				.store-name {
					color: #333333;
					font-weight: bold;
					font-size: 26rpx;
				}

				.store-time {
					color: #666;
					font-size: 26rpx;
				}

				.store-addres {
					color: #666;
					font-size: 26rpx;
				}
			}
		}

	}

	.head-wrap {
		font-size: $font-size-toolbar;
		line-height: 100rpx;
		height: 100rpx;
		display: block;
		text-align: center;

		.iconfont {
			position: absolute;
			float: right;
			right: 44rpx;
			font-size: $font-size-toolbar;
		}
	}

	.scroll {
		min-height: 600rpx;
		max-height: 800rpx;
	}

	.store-body {
		padding: 0 20rpx;
		-webkit-flex-wrap: wrap;
		flex-wrap: wrap;
		overflow: scroll;
		background-color: #fff;
		height: auto;
	}

	.service-body {
		.user-name {
			text-align: center;
			padding: 10rpx;
			font-weight: bold;
			font-size: 26rpx;
			display: block;
		}
	}

	.tab-bar {
		.action-icon-wrap {
			min-width: auto;
		}
	}
</style>
<style scoped>
	/deep/ .uni-page {
		overflow: hidden;
	}

	/deep/ .mescroll-upwarp {
		padding-bottom: 100rpx;
	}
</style>