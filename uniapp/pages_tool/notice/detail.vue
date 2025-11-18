<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="page">
		<view class="notice-title">{{ detail.title }}</view>
		<view class="notice-meta">
			<text class="notice-time">发表时间: {{ $util.timeStampTurnTime(detail.create_time) }}</text>
		</view>
		<view class="notice-content">
			<!-- <rich-text :nodes="content"></rich-text> -->
			<ns-mp-html :content="content"></ns-mp-html>
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
				noticeId: 0,
				content: '',
				detail: {}
			};
		},
		onLoad(options) {
			this.noticeId = options.notice_id || 0;
			// 小程序扫码进入
			if (options.scene) {
				var sceneParams = decodeURIComponent(options.scene);
				this.noticeId = sceneParams.split('-')[1];
			}
			if (this.noticeId == 0) {
				this.$util.redirectTo('/pages_tool/notice/list', {}, 'redirectTo');
			}
		},
		onShow() {
			this.getData();
		},
		methods: {
			getData() {
				this.$api.sendRequest({
					url: '/api/notice/info',
					data: {
						id: this.noticeId
					},
					success: res => {
						if (res.code == 0) {
							if (res.data) {
								this.detail = res.data;
								// this.content = htmlParser(res.data.content);
								this.content = res.data.content;
								this.$langConfig.title(this.detail.title);
								this.setPublicShare();

								if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
							} else {
								this.$util.redirectTo('/pages_tool/notice/list', {}, 'redirectTo');
							}
						} else {
							this.$util.showToast({
								title: res.message
							});
							setTimeout(() => {
								this.$util.redirectTo('/pages_tool/notice/list', {}, 'redirectTo');
							}, 2000);
						}
					},
					fail: res => {
						this.$util.redirectTo('/pages_tool/notice/list', {}, 'redirectTo');
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			// 设置公众号分享
			setPublicShare() {
				let shareUrl = this.$config.h5Domain + '/pages_tool/notice/detail?notice_id=' + this.noticeId;
				this.$util.setPublicShare({
					title: this.detail.title,
					desc: '',
					link: shareUrl,
					imgUrl: this.siteInfo ? this.$util.img(this.siteInfo.logo_square) : ''
				});
			}
		},
		onShareAppMessage(res) {
			var title = '[公告]' + this.detail.title;
			var path = '/pages_tool/notice/detail?notice_id=' + this.noticeId;
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
			var query = 'notice_id=' + this.noticeId;
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
		background-color: #fff;
	}

	.notice-title {
		font-size: $font-size-toolbar;
		text-align: left;
		font-weight: bold;
	}

	.notice-content {
		margin-top: $margin-updown;
		word-break: break-all;
		font-size: $font-size-base;
	}

	.notice-meta {
		text-align: left;
		margin-top: $margin-updown;
		color: $color-tip;

		.notice-time {
			font-size: $font-size-tag;
		}
	}
</style>