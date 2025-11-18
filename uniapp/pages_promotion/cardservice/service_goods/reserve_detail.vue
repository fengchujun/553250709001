<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="reserve-wrap">
		<view class="reserve-list">
			<view class="reserve-head">预约信息</view>
			<view class="reserve-item">
				<text class="title">预约项目</text>
				<text class="content">{{ serviceDetail.goods_name }}</text>
			</view>
			<view class="reserve-item">
				<text class="title">预约时间</text>
				<view class="content">
					<text>{{ params.date }}</text>
					<text class="time">({{ params.time }})</text>
				</view>
			</view>
			<view class="reserve-item">
				<text class="title">预约人数</text>
				<text class="content">1</text>
			</view>
			<view class="reserve-item">
				<text class="title">服务人员</text>
				<text class="content">{{ params.username }}</text>
			</view>
			<view class="reserve-item remark-item">
				<text class="title">备注</text>
				<textarea type="text" maxlength="100" placeholder="备注信息" placeholder-class="color-tip" v-model="formData.remark"></textarea>
			</view>
		</view>

		<!-- <view class="reserve-list">
			<view class="reserve-item">
				<text class="title">手机号码</text>
				<input class="uni-input info-content" placeholder="请输入手机号" type="number" maxlength="11" v-model="formData.tel"/>
				<text>一键获取</text>
			</view>
			<view class="reserve-item">
				<text class="title">姓名</text>
				<input class="uni-input info-content" placeholder="请输入姓名" type="text" v-model="formData.relaname"/>
			</view>
		</view> -->

		<view class="tab-bar">
			<view class="tab-bar-item"></view>
			<button class="reserve-btn" type="default" @click="setAdd()">确认预约</button>
		</view>
		<ns-login ref="login"></ns-login>
		<!-- <loading-cover ref="loadingCover"></loading-cover> -->
	</view>
</template>

<script>
export default {
	data() {
		return {
			goodsId: 0,
			serviceDetail: '',
			params: {},
			formData: {
				remark: '',
				relaname: '',
				tel: ''
			},
			navStatus: {
				list: [],
				index: 'all'
			},
			reserveList: []
		};
	},
	onLoad(option) {
		this.goodsId = option.goods_id;
	},
	onShow() {
		this.isIphoneX = this.$util.uniappIsIPhoneX();

		if (this.storeToken) {
			if (this.$refs.mescroll) this.$refs.mescroll.refresh();
		} else {
			this.$nextTick(() => {
				this.$refs.login.open('/pages_promotion/cardservice/service_goods/my_reserve_list');
			});
		}

		this.getDetail();
		this.params = uni.getStorageSync('reserveParams');
	},
	onUnload() {
		uni.removeStorageSync('reserveParams');
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
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
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
		setAdd() {
			this.params.remark = this.formData.remark;
			this.params.relaname = this.formData.relaname;
			this.params.tel = this.formData.tel;
			this.$api.sendRequest({
				url: '/cardservice/api/reserve/addreserve',
				data: this.params,
				success: res => {
					if (res.code == 0) {
						this.$util.redirectTo('/pages_promotion/cardservice/service_goods/my_reserve_list');
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
		}
	}
};
</script>

<style lang="scss">
.reserve-wrap {
	padding: 24rpx;
	.reserve-list {
		margin-bottom: 20rpx;
		background-color: #fff;
		border-radius: 18rpx;
		.reserve-head {
			height: 100rpx;
			line-height: 100rpx;
			font-weight: bold;
			font-size: $font-size-toolbar;
			border-bottom: 2rpx solid #f2f2f2;
			padding: 0 24rpx;
		}
		.reserve-item {
			display: flex;
			align-items: center;
			padding: 0 24rpx;
			height: 90rpx;
			.title {
				margin-right: 20rpx;
				width: 160rpx;
				font-size: $font-size-sub;
			}
			.time {
				color: #888888;
			}
		}
		.remark-item {
			height: 160rpx;
			align-items: flex-start;
			padding-bottom: 20rpx;
			text {
				min-width: 160rpx;
			}
			textarea {
				height: 160rpx;
				font-size: 26rpx;
			}
		}
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
		padding: 0 30rpx;
		.tab-bar-item {
			display: flex;
			flex-direction: column;
			align-items: center;
			line-height: 1;
			text:first-of-type {
				margin-bottom: 6rpx;
				font-size: 40rpx;
				font-weight: bold;
			}
			text:last-of-type {
				font-size: $font-size-tag;
			}
		}
		.reserve-btn {
			margin: 0;
			width: 426rpx;
			background-color: $base-color;
			color: #fff;
			border-radius: 50rpx;
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
