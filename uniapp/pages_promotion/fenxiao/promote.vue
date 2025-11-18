<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="fenxiao-promote" :style="{ backgroundImage: 'url(' + $util.img('public/uniapp/fenxiao/promote/promote_bg.png') + ')' }">
		<view class="my-earnings">
			<view class="earnings-head-wrap">
				<view class="earnings-head">
					<image class="name" :src="$util.img('public/uniapp/fenxiao/promote/my_earnings.png')" mode="aspectFit"></image>
					<image class="money-bg" :src="$util.img('public/uniapp/fenxiao/promote/money.png')" mode="aspectFill"></image>
					<view class="content">
						累计收益
						<text class="money-text">{{ detailData.total_commission }}</text>
						元
					</view>
				</view>
			</view>
			<view class="earnings-body">
				<view class="earnings-tab">
					<text :class="{ active: tabIndex == 0 }" @click="tabCut(0)">已邀请好友</text>
					<text :class="{ active: tabIndex == 1 }" @click="tabCut(1)">已下单好友</text>
				</view>
				<scroll-view scroll-y="true" class="earnings-content-wrap" @scrolltolower="getTeam()">
					<view class="earnings-list" v-if="promote.list.length">
						<view class="earnings-item" v-for="(item, index) in promote.list" :key="index">
							<image
								class="item-img"
								:src="item.headimg ? $util.img(item.headimg) : $util.getDefaultImage().head"
								@error="item.headimg = $util.getDefaultImage().head"
								mode="aspectFill"/>
							<view class="item-content">
								<view class="item-name multi-hidden">{{ item.nickname }}</view>
							</view>
						</view>
					</view>
					<view class="earnings-empty" v-else>暂无已邀请好友，快去邀请吧</view>
				</scroll-view>
			</view>
		</view>
		<view class="activity-rules">
			<image class="rules-name" :src="$util.img('public/uniapp/fenxiao/promote/activity_rules.png')" mode="aspectFit"></image>
			<view class="content" v-if="promoteContent.content">
				<ns-mp-html :content="promoteContent.content"></ns-mp-html>
			</view>
			<view class="rules-empty" v-else>暂无活动规则</view>
		</view>
		<view class="active-btn"><button type="primary" @click="toPoster()">邀请好友</button></view>
		<ns-copyright></ns-copyright>
		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
import htmlParser from '@/common/js/html-parser';
export default {
	data() {
		return {
			tabIndex: 0,
			promoteContent: {},
			promote: {
				page: 0,
				page_size: 5,
				page_count: 0,
				list: []
			},
			isPay: 0,
			detailData: {},
			templateId: '',
			poster:'',
		};
	},
	onShow() {
		setTimeout( () => {
			if (!this.addonIsExist.fenxiao) {
				this.$util.showToast({
					title: '商家未开启分销',
					mask: true,
					duration: 2000
				});
				setTimeout(() => {
					this.$util.redirectTo('/pages/index/index');
				}, 2000);
			}
		},1000);

		if (this.storeToken) {
			this.getTeam();
			this.getPromoteRule();
			this.getFenxiaoDetail();
		} else {
			this.$nextTick(() => {
				this.$refs.login.open('/pages_promotion/fenxiao/promote_code');
			});
		}
	},
	methods: {
		toPoster(){
			this.$util.redirectTo('/pages_promotion/fenxiao/promote_code')
		},
		tabCut(index) {
			this.tabIndex = index;
			this.isPay = index;

			this.promote.page_count = 0;
			this.promote.page = 0;
			this.promote.page_size = 5;
			this.getTeam();
		},
		getTeam() {
			if (this.promote.page_count > 0 && this.promote.page == this.promote.page_count) return;
			this.promote.page++;
			this.$api.sendRequest({
				url: '/fenxiao/api/fenxiao/team',
				data: {
					page: this.promote.page,
					page_size: this.promote.page_size,
					is_pay: this.isPay,
					level: 1
				},
				success: res => {
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					if (res.code >= 0) {
						if (this.promote.page == 1) this.promote.list = [];
						this.promote.page_count = res.data.page_count;
						this.promote.list = this.promote.list.concat(res.data.list);
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
		// 获取分销商信息
		getFenxiaoDetail() {
			this.$api.sendRequest({
				url: '/fenxiao/api/fenxiao/detail',
				success: res => {
					if (res.data) {
						this.detailData = res.data;
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		// 获取富文本
		getPromoteRule() {
			this.$api.sendRequest({
				url: '/fenxiao/api/config/promoterule',
				success: res => {
					if (res.data) {
						this.promoteContent = res.data;
						// this.promoteContent.content = res.data.content ? htmlParser(res.data.content) : '';
						this.promoteContent.content = res.data.content ? res.data.content : '';
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
	}
};
</script>

<style lang="scss">
.fenxiao-promote {
	overflow: hidden;
	padding: 0 30rpx 160rpx;
	min-height: 100vh;
	background-color: #ff2d46;
	background-size: 100%;
	background-repeat: no-repeat;
	.my-earnings,
	.activity-rules {
		background-color: #fff;
		border-radius: 30rpx;
	}
	.my-earnings {
		margin-top: 230rpx;
		padding-bottom: 40rpx;
		.earnings-head-wrap {
			background-color: #fff7f5;
			height: 160rpx;
			border-radius: 30rpx;
			border-bottom-left-radius: 0;
			border-bottom-right-radius: 0;
		}
		.earnings-head {
			position: relative;
			padding-top: 60rpx;
			display: flex;
			align-items: center;
			justify-content: center;
			.name {
				position: absolute;
				width: 384rpx;
				height: 74rpx;
				top: -22rpx;
			}
			.content {
				font-size: $font-size-tag;
			}
			.money-bg {
				width: 80rpx;
				height: 80rpx;
			}
			.money-text {
				margin: 0 6rpx;
				font-size: 40rpx;
				line-height: 1;
			}
		}
		.earnings-body {
			padding: 0 30rpx;
			.earnings-tab {
				display: flex;
				justify-content: space-around;
				align-items: center;
				height: 100rpx;
				font-size: 30rpx;
				color: #e93224;
				margin-bottom: 10rpx;
				text.active {
					position: relative;
					font-weight: bold;
					&::after {
						position: absolute;
						content: '';
						height: 4rpx;
						width: 86rpx;
						left: 50%;
						bottom: -4rpx;
						transform: translateX(-50%);
						background-color: #e93224;
					}
				}
			}
			.earnings-content-wrap {
				max-height: 440rpx;
			}
			.earnings-item {
				display: flex;
				align-items: center;
				margin-bottom: 20rpx;
				&:last-child {
					margin-bottom: 0;
				}
				.item-img {
					width: 90rpx;
					height: 90rpx;
					margin-right: 20rpx;
					flex-shrink: 0;
				}
				.item-time {
					font-size: $font-size-tag;
					color: $color-tip;
				}
				.item-name {
					line-height: 1.3;
				}
				.money {
					margin-left: auto;
					color: #f9b124;
				}
			}
		}
		.earnings-empty {
			display: flex;
			align-items: center;
			justify-content: center;
			height: 160rpx;
			font-size: 30rpx;
		}
	}
	.activity-rules {
		position: relative;
		margin-top: 60rpx;
		min-height: 300rpx;
		.rules-name {
			position: absolute;
			width: 384rpx;
			height: 74rpx;
			top: -22rpx;
			left: 50%;
			transform: translateX(-50%);
		}
		.content {
			padding: 70rpx 20rpx 0;
		}
		.rules-empty {
			padding-top: 140rpx;
			text-align: center;
			font-size: 30rpx;
		}
	}

	.active-btn {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		z-index: 5;
		display: flex;
		align-items: center;
		justify-content: center;
		background-color: #ff2d46;
		height: 160rpx;
		padding: 0 60rpx;

		button {
			flex: 1;
			margin: 0;
			border-radius: 50rpx;
			color: #985400;
			background: linear-gradient(45deg, #ffe2ac 0%, #fdc174 100%);
		}
	}
}
</style>
