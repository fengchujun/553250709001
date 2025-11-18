<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="point">
		<!-- #ifdef MP-WEIXIN -->
		<view class="custom-navbar" :style="{ 'padding-top': menuButtonBounding.top + 'px', 'height': menuButtonBounding.height + 'px' }">
			<view class="navbar-wrap">
				<text class="iconfont icon-back_light back" @click="$util.redirectTo('/pages/member/index')"></text>
				<view class="navbar-title">我的积分</view>
			</view>
		</view>
		<!-- #endif -->
		
		<view class="head-wrap" :style="{ background: 'url(' + $util.img('public/uniapp/point/point_bg.png') + ') no-repeat right bottom/ auto 340rpx, linear-gradient(314deg, #F16914 0%, #FEAA4C 100%)' }">
			<view class="point price-font">{{ pointInfo.point }}</view>
			<view class="title">当前积分</view>
			<view class="flex-box">
				<view class="flex-item">
					<view class="num price-font">{{ pointInfo.totalPoint }}</view>
					<view class="font-size-tag">累计积分</view>
				</view>
				<view class="flex-item">
					<view class="num price-font">{{ pointInfo.totalConsumePoint }}</view>
					<view class="font-size-tag">累计消费</view>
				</view>
				<view class="flex-item">
					<view class="num price-font">{{ pointInfo.todayPoint }}</view>
					<view class="font-size-tag">今日获得</view>
				</view>
			</view>
		</view>

		<view class="menu-wrap">
			<view class="menu-item" @click="$util.redirectTo('/pages_tool/member/point_detail')">
				<view class="icon">
					<image :src="$util.img('public/uniapp/point/point_detail_icon.png')" mode="widthFix"></image>
				</view>
				<text class="title">积分明细</text>
			</view>
			<view class="menu-item" @click="$util.redirectTo('/pages_promotion/point/list')">
				<view class="icon">
					<image :src="$util.img('public/uniapp/point/point_shop.png')" mode="widthFix"></image>
				</view>
				<text class="title">积分商城</text>
			</view>
		</view>

		<view class="task-wrap">
			<view class="title">做任务赚积分</view>
			<view class="task-item" @click="toSign">
				<view class="icon"><text class="iconfont icon-qiandao1"></text></view>
				<view class="wrap">
					<view class="title">每日签到</view>
					<view class="desc color-tip font-size-tag">连续签到可获得更多积分</view>
				</view>
				<view class="btn">去签到</view>
			</view>
			<view class="task-item" @click="$util.redirectTo('/pages/index/index')">
				<view class="icon"><text class="iconfont icon-shangpin"></text></view>
				<view class="wrap">
					<view class="title">购买商品</view>
					<view class="desc color-tip font-size-tag">购买商品可获得积分</view>
				</view>
				<view class="btn">去下单</view>
			</view>
		</view>

		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			pointInfo: {
				point: 0,
				totalPoint: 0,
				totalConsumePoint: 0,
				todayPoint: 0
			},
			menuButtonBounding: {} // 小程序胶囊属性
		};
	},
	onShow() {
		if (!this.storeToken) {
			this.$nextTick(() => {
				this.$refs.login.open('/pages_tool/member/point');
			});
		} else {
			this.getMemberPoint();
		}
	},
	onLoad() {
		// #ifdef MP
		this.menuButtonBounding = uni.getMenuButtonBoundingClientRect();
		// #endif
	},
	methods: {
		toSign() {
			this.$util.redirectTo('/pages_tool/member/signin');
		},
		getMemberPoint() {
			this.$api.sendRequest({
				url: '/api/memberaccount/point',
				data: {
				},
				success: res => {
					if (res.code == 0) {
						this.pointInfo.point = parseInt(res.data.point);
						this.pointInfo.totalPoint = parseInt(res.data.point_all);
						this.pointInfo.totalConsumePoint = parseInt(res.data.point_use);
						this.pointInfo.todayPoint = parseInt(res.data.point_today);
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				},
				fail: res => {
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		}
	},
	onBackPress(options) {
		if (options.from === 'navigateBack') {
			return false;
		}
		this.$util.redirectTo('/pages/member/index', {}, 'reLaunch');
		return true;
	},
	watch: {
		storeToken: function(nVal, oVal) {
			if (nVal) {
				this.getMemberPoint();
			}
		}
	}
};
</script>

<style lang="scss">
@import './public/css/point.scss';
</style>
