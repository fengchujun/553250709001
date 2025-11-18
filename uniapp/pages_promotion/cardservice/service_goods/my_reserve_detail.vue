<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="store-body">
		<view class="store-info">
			<view class="store">
				<view class="store-title">店铺名称</view>
				<view class="store-name">{{ reserveDetail.store_name }}</view>
			</view>
			<view class="store-map" @click="mapRoute()">
				<img :src="$util.img('public/uniapp/cardservice/store_map.png')"/>
			</view>
		</view>
		
	</view>
	<view class="goods-body">
		<view class="goods-list">
			<view class="goods-wrap" v-for="(goodsItem, goodsIndex) in reserveDetail.item" :key="goodsIndex">
				<view class="goods-img">
					<image :src="$util.img(goodsItem.sku_image)" mode="aspectFill" :lazy-load="true"/>
				</view>
				<view class="goods-info">
					<view class="pro-info">
						<view class="goods-name">{{ goodsItem.goods_name }}</view>
					</view>
					<view class="pro-info-to">
						<view class="content">预约时间：{{ $util.timeStampTurnTime(reserveDetail.reserve_time) }}</view>
					</view>
					<view class="pro-info-to">
						<view class="content">预约人数： 1 </view>
					</view>
					<view class="pro-info-to">
						<view class="content">服务人员：{{ goodsItem.username }}</view>
					</view>
				</view>
			</view>
		</view>
	</view>
	<view class="reserve-wrap">
		<view class="reserve-list">
			<view class="reserve-item">
				<text class="title">姓名</text>
				<text class="content">{{ reserveDetail.nickname }}</text>
			</view>
			<view class="reserve-item remark-item">
				<text class="title">备注</text>
				<text v-if="reserveDetail.remark">{{ reserveDetail.remark }}</text>
				<text v-else>暂无备注</text>
			</view>
		</view>
		<view class="tab-bar" v-if="reserveDetail.reserve_state == 'wait_confirm' || reserveDetail.reserve_state == 'wait_to_store'">
			<button class="reserve-btn" type="default" @click="cancel()">取消预约</button>
		</view>
		
		<view @touchmove.prevent.stop>
			<uni-popup ref="storeMapPopup" type="bottom">
				<view class="goods-coupon-popup-layer popup-layer store-map-popuo">
					<view class="head-wrap" @click="closeStoreMapPopup()">
						<text>门店位置</text>
						<text class="iconfont icon-close"></text>
					</view>
					<view class="store-map">
						<map class="map" :latitude="reserveDetail.latitude" :longitude="reserveDetail.longitude" :markers="covers"></map>
					</view>
					<view class="button-box"><button type="primary" @click="closeStoreMapPopup()">确定</button></view>
				</view>
			</uni-popup>
		</view>
		
		<ns-login ref="login"></ns-login>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>
 
<script>
import Map from '@/common/js/map/openMap.js';

export default {
	data() {
		return {
			reserveId : 0,
			reserveDetail : {},
			covers :[],
		};
	},
	onLoad(option) {
		this.reserveId = option.reserve_id;
	},
	onShow() {
		this.isIphoneX = this.$util.uniappIsIPhoneX();
		
		if (!this.storeToken) {
			this.$nextTick(() => {
				this.$refs.login.open('/pages_promotion/cardservice/service_goods/my_reserve_detail?reserveId=' + this.reserveId);
			});
		}

		this.getDetail();
	},
	methods: {
		getDetail(){
			this.$api.sendRequest({
				url: '/cardservice/api/reserve/detail',
				data: {
					reserve_id: this.reserveId
				},
				success: res => {
					if(res.code >= 0){
						this.reserveDetail = res.data;
					}else{
						this.$util.showToast({
							title: '未找到预约信息',
							mask: true,
							duration: 2000
						});
						setTimeout(() => {
							this.$util.redirectTo('/pages/index/index');
						}, 2000);
					}
				}
			});
		},
		mapRoute() {
			Map.openMap(Number(this.reserveDetail.latitude), Number(this.reserveDetail.longitude), this.reserveDetail.store_name, 'gcj02');
		},
		cancel(){
			uni.showModal({
				title: '提示',
				content: '您确定要取消该预约吗？',
				success: res => {
					this.$api.sendRequest({
						url: '/cardservice/api/reserve/cancel',
						data: {
							reserve_id : this.reserveId
						},
						success: res => {
							if(res.code == 0){
								this.$util.redirectTo('/pages_promotion/cardservice/service_goods/my_reserve_list');
							}
						}
					});
				}
			})
		},
	}
};
</script>

<style lang="scss">
	.reserve-wrap{
		padding: 24rpx;
		.reserve-list{
			margin-bottom: 20rpx;
			background-color: #fff;
			border-radius: 18rpx;
			.reserve-head{
				height: 100rpx;
				line-height: 100rpx;
				font-weight: bold;
				font-size: $font-size-toolbar;
				border-bottom: 2rpx solid #F2F2F2;
				padding: 0 24rpx;
			}
			.reserve-item{
				display: flex;
				align-items: center;
				padding: 0 24rpx;
				height: 90rpx;
				.title{
					margin-right: 20rpx;
					width: 160rpx;
					font-size: $font-size-sub;
				}
			}
			.remark-item{
				align-items: flex-start;
				padding-bottom: 20rpx;
				height: auto;
				text{
					min-width: 160rpx;
				}
			}
		}
		
		.tab-bar{
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
			padding: 0 30rpx;
			.tab-bar-item{
				display: flex;
				flex-direction: column;
				align-items: center;
				line-height: 1;
				text:first-of-type{
					margin-bottom: 6rpx;
					font-size: 40rpx;
					font-weight: bold;
				}
				text:last-of-type{
					font-size: $font-size-tag;
				}
			}
			.reserve-btn{
				margin: 0;
				width: 100%;
				background-color: $base-color;
				color: #fff;
				border-radius: 50rpx;
			}
		}
	}
	.store-body{
		padding: 24rpx;
		padding-bottom: 0rpx;
		.store-info{
			display: flex;
			background-color: #fff;
			padding:30rpx 30rpx 30rpx 60rpx;
			.store{
				flex: 1;
			}
			.store-title{
				font-size: 26rpx;
				font-weight: bold;
				color:#000;
				margin-bottom:10rpx
			}
			.store-map{
				width: 100rpx;
				height: 100rpx;
				img{
					width:100%;
					height: 100%;
				}
			}
		}
	}
	.goods-body {
		padding: 24rpx;
		padding-bottom: 0rpx;
		.goods-list{
			background-color: #fff;
			.goods-wrap {
				display: flex;
				position: relative;
				padding: 20rpx;
				
				&:last-of-type {
					margin-bottom: 0;
				}
				
				.goods-img {
					width: 160rpx;
					height: 160rpx;
					margin-right: 20rpx;
				
					image {
						width: 100%;
						height: 100%;
						border-radius: $border-radius;
					}
				}
				
				.goods-info {
					position: relative;
					max-width: calc(100% - 180rpx);
					display: flex;
					flex-direction: column;
					.goods-name {
						display: -webkit-box;
						-webkit-box-orient: vertical;
						-webkit-line-clamp: 1;
						overflow: hidden;
						line-height: 1.5;
						font-size: 24rpx;
						color: $color-title;
						font-weight: bold;
					}
					.content{
						font-size: 24rpx;
						color: #888;
					}
				
					.goods-sub-section {
						width: 100%;
						line-height: 1.3;
						display: flex;
						margin-top: 14rpx;
				
						.goods-price {
							font-size: $font-size-tag;
							color: var(--price-color);
							flex: 1;
							font-weight: bold;
						}
						.goods-num {
							font-size: $font-size-tag;
							color: $color-tip;
							flex: 1;
							text-align: right;
							line-height: 1;
				
							.iconfont {
								font-size: $font-size-tag;
							}
						}
						.goods-type {
							font-size: $font-size-tag;
						}
				
						.unit {
							font-size: $font-size-tag;
							margin-right: 2rpx;
						}
				
						view {
							flex: 1;
							line-height: 1.3;
							display: flex;
							flex-direction: column;
				
							&:last-of-type {
								text-align: right;
				
								.iconfont {
									line-height: 1;
									font-size: $font-size-tag;
								}
							}
						}
					}
				
					.goods-action {
						text-align: right;
				
						.action-btn {
							line-height: 1;
							padding: 14rpx 20rpx;
							color: $color-title;
							display: inline-block;
							border-radius: $border-radius;
							background: #fff;
							border: 2rpx solid #999;
							font-size: $font-size-tag;
							margin-left: 10rpx;
						}
					}
				}
			}
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
