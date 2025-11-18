<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="page">
		<view class="help-title">{{ detail.title }}</view>
		<view class="help-content">
			<!-- <rich-text :nodes="content"></rich-text> -->
			<ns-mp-html :content="content"></ns-mp-html>
		</view>
		<view class="help-meta">
			<text class="help-time">发表时间: {{ $util.timeStampTurnTime(detail.create_time) }}</text>
		</view>
		<!-- 悬浮按钮 -->
		<hover-nav></hover-nav>
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
				id: 0,
				detail: {},
				content: ''
			};
		},
		onLoad(options) {
			this.id = options.id || 0;
			// 小程序扫码进入
			if (options.scene) {
				var sceneParams = decodeURIComponent(options.scene);
				this.id = sceneParams.split('-')[1];
			}
			if (this.id == 0) {
				this.$util.redirectTo('/pages_tool/help/list', {}, 'redirectTo');
			}
		},
		onShow() {
			this.getData();
		},
		methods: {
			getData() {
				this.$api.sendRequest({
					url: '/api/help/info',
					data: {
						id: this.id
					},
					success: res => {
						if (res.code == 0) {
							if (res.data) {
								this.detail = res.data;
								this.$langConfig.title(this.detail.title);
								// this.content = htmlParser(res.data.content);
								this.content = res.data.content;
								this.setPublicShare();
							} else {
								this.$util.showToast({
									title: res.message
								});
								setTimeout(() => {
									this.$util.redirectTo('/pages_tool/help/list', {}, 'redirectTo');
								}, 2000);
							}
						} else {
							this.$util.showToast({
								title: res.message
							});
							setTimeout(() => {
								this.$util.redirectTo('/pages_tool/help/list', {}, 'redirectTo');
							}, 2000);
						}
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail: res => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			// 设置公众号分享
			setPublicShare() {
				let shareUrl = this.$config.h5Domain + '/pages_tool/help/detail?id=' + this.id;
				this.$util.setPublicShare({
					title: this.detail.title,
					desc: '',
					link: shareUrl,
					imgUrl: this.siteInfo ? this.$util.img(this.siteInfo.logo_square) : ''
				});
			}
		},
		onShareAppMessage(res) {
			var title = this.detail.title;
			var path = '/pages_tool/help/detail?id=' + this.id;
			return {
				title: title,
				path: path,
				success: res => {},
				fail: res => {}
			};
		},
		//分享到朋友圈
		onShareTimeline() {
			var title = this.detail.title;
			var query = 'id=' + this.id;
			return {
				title: title,
				query: query,
				imageUrl: ''
			};
		}
	};
</script>

<style lang="scss">
	.page {
		width: 100%;
		height: 100%;
		padding: 30rpx;
		box-sizing: border-box;
		background: #ffffff;
	}

	.help-title {
		font-size: $font-size-toolbar;
		text-align: center;
	}

	.help-content {
		margin-top: $margin-updown;
		word-break: break-all;
	}

	.help-meta {
		text-align: right;
		margin-top: $margin-updown;
		color: $color-tip;

		.help-time {
			font-size: $font-size-tag;
		}
	}
</style>