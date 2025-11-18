<template>
	<page-meta :page-style="themeColor"></page-meta>
	<scroll-view scroll-y="true" class="scroll-view" >
		<view class="container" :style="{backgroundImage: 'url('+ $util.img('/public/uniapp/store/payment/header_bg.png') +')'}">
			<view class="header-wrap"></view>
			<view class="store-wrap">
				<view class="tips">支付时请确保您的账户有足够的余额</view>
				<view class="store-list" @click="$util.redirectTo('/pages_tool/store/list')" v-if="addonIsExist.store">
					<image :src="$util.img('/public/uniapp/store/payment/vip_icon.png')" mode="widthFix"></image>
					<text>查看门店列表</text>
					<text class="iconfont icon-right"></text>
				</view>
			</view>
			<view class="menu-wrap">
				<view class="menu-list">
					<view class="menu-item" @click="redirect('/pages_tool/recharge/list')">
						<image :src="$util.img('/public/uniapp/store/payment/recharge.png')" mode="widthFix"></image>
						<view class="title">余额充值</view>
						<view class="desc">余额账户充值</view>
					</view>
					<view class="menu-item" @click="redirect('/pages_tool/recharge/order_list')">
						<image :src="$util.img('/public/uniapp/store/payment/recharge_record.png')" mode="widthFix"></image>
						<view class="title">充值记录</view>
						<view class="desc">余额充值记录</view>
					</view>
					<view class="menu-item" @click="redirect('/pages_tool/member/balance_detail')">
						<image :src="$util.img('/public/uniapp/store/payment/balance_detail.png')" mode="widthFix"></image>
						<view class="title">余额明细</view>
						<view class="desc">余额变更明细</view>
					</view>
					<view class="menu-item" @click="redirect('/pages_tool/member/balance')">
						<image :src="$util.img('/public/uniapp/store/payment/balance.png')" mode="widthFix"></image>
						<view class="title">我的余额</view>
						<view class="desc">我的余额</view>
					</view>
				<!-- 	<view class="menu-item" @click="redirect('/pages_tool/member/point_detail')">
						<image :src="$util.img('/public/uniapp/store/payment/point.png')" mode="widthFix"></image>
						<view class="title">积分明细</view>
						<view class="desc">积分变更明细</view>
					</view> -->
				</view>
				<view class="pay-btn" @click="redirect('/pages_tool/store/payment_qrcode')">立即进入支付页面</view>
			</view>
			<view class="content-wrap">
				<image :src="$util.img('/public/uniapp/store/payment/payment_tips.png')" mode="widthFix"></image>
			</view>
			<view class="content-wrap">
				<image :src="$util.img('/public/uniapp/store/payment/payment_strategy.png')" mode="widthFix"></image>
			</view>
		</view>
		
		<ns-login ref="login"></ns-login>
		<diy-bottom-nav></diy-bottom-nav>
	</scroll-view>
</template>

<script>
export default {
	data() {
		return {};
	},
	onLoad(options) {},
	methods: {
		/**
		 * 跳转
		 * @param {Object} url
		 */
		redirect(url) {
			if (!this.storeToken) {
				this.$refs.login.open(url);
			} else {
				this.$util.redirectTo(url);
			}
		}
	}
};
</script>

<style lang="scss">
	.scroll-view {
		width: 100vw;
		height: 100vh;
		
		.container {
			width: 100%;
			background-repeat: no-repeat;
			background-size: 100%;
		}
	}
	
	.header-wrap {
		height: 200rpx;
	}
	
	.store-wrap {
		margin: 0 30rpx;
		background: linear-gradient(90deg, rgba(0,0,0,0.48) 0%, rgba(0,0,0,0.88) 100%);
		border-radius: 24rpx;
		padding: 30rpx 20rpx 60rpx 20rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		
		.tips {
			color: #F2C7B5;
			font-size: 24rpx;
			font-weight: 600;
		}
		
		.store-list {
			background: rgba(255, 255, 255, .2);
			display: flex;
			align-items: center;
			height: 40rpx;
			border-radius: 40rpx;
			padding: 0 10rpx 0 4rpx;
			
			text {
				font-size: 24rpx;
				color: #EFCAB6;
				margin-left: 10rpx;
				line-height: 1;
			}
			
			image {
				width: 32rpx;
				height: 32rpx;
			}
		}
	}
	
	.menu-wrap {
		background: #fff;
		border-radius: 24rpx;
		margin: 0 30rpx;
		padding: 30rpx 30rpx 60rpx 30rpx;
		transform: translateY(-40rpx);
		
		.menu-list {
			display: flex;
			
			.menu-item {
				flex: 1;
				display: flex;
				flex-direction: column;
				align-items: center;
				
				image {
					width: 96rpx;
					height: 96rpx;
				}
				
				.title {
					margin-top: 10rpx;
					font-size: 28rpx;
					color: #222;
					font-weight: 600;
				}
				
				.desc {
					font-size: 18rpx;
					color: #999999;
					text-align: center;
				}
			}
		}
		
		.pay-btn {
			height: 98rpx;
			line-height: 98rpx;
			background: #F72D1E;
			border-radius: 98rpx;
			margin: 60rpx auto 0 auto;
			font-size: 34rpx;
			font-weight: 600;
			color: #FFFFFF;
			text-align: center;
		}
	}
	
	.content-wrap {
		background: #fff;
		border-radius: 24rpx;
		margin: 30rpx;
		padding: 0;
		overflow: hidden;
		transform: translateY(-40rpx);
		
		image {
			width: 100%;
		}
	}
</style>
