<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<block v-if="diyData">
			<block v-for="(item, index) in diyData.value" :key="index">
				<view v-if="item.componentName == 'GoodsCategory'">
					<diy-category @tologin="toLogin" ref="category" :value="item"></diy-category>
				</view>
			</block>
		</block>

		<ns-login ref="login"></ns-login>

		<loading-cover ref="loadingCover"></loading-cover>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->

		<!-- 底部tabBar -->
		<view id="tab-bar">
			<diy-bottom-nav></diy-bottom-nav>
		</view>
	</view>
</template>

<script>
	export default {
		components: {},
		data() {
			return {
				diyData: null,
				mpShareData: null, //小程序分享数据
			};
		},
		onLoad() {
			uni.hideTabBar();
			this.getDiyInfo();
		},
		onShow() {
			if (this.$refs.category) this.$refs.category[0].pageShow();
		},
		onUnload() {
			if (!this.storeToken && this.$refs.login) this.$refs.login.cancelCompleteInfo();
		},
		methods: {
			getDiyInfo() {
				this.$api.sendRequest({
					url: '/api/diyview/info',
					data: {
						name: 'DIY_VIEW_GOODS_CATEGORY'
					},
					success: res => {
						if (res.code == 0 && res.data) {
							this.diyData = res.data;
							if (this.diyData.value) {
								this.diyData = JSON.parse(this.diyData.value);
								this.setPublicShare();
								this.setMpShare();
								if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
							}
							
							uni.stopPullDownRefresh();
						}
					}
				});
			},
			// 设置小程序分享
			setMpShare() {
				//小程序分享
				// #ifdef MP-WEIXIN
					let share_path = this.$util.getCurrentRoute().path;
					if(this.$store.state.memberInfo && this.$store.state.memberInfo.member_id){
						share_path = this.$util.getCurrentShareRoute(this.$store.state.memberInfo.member_id).path
					}
					let appMessageData = {
						title: this.diyData.global.weappShareTitle,
						path: share_path,
						imageUrl: this.$util.img(this.diyData.global.weappShareImage),
						success: res => {},
						fail: res => {}
					}
					let timeLineData = {
						title: this.diyData.global.weappShareTitle,
						query: share_path,
						imageUrl: this.$util.img(this.diyData.global.weappShareImage),
					}
					this.mpShareData = {
						appMessage: appMessageData,
						timeLine: timeLineData
					};
					//朋友圈不需要页面路径，只要要后面的参数就行
					this.mpShareData.timeLine.query = this.mpShareData.timeLine.query.split('?')[1] || '';
				// #endif
			},
			// 设置公众号分享
			setPublicShare() {
				let shareUrl = this.$config.h5Domain + '/pages/goods/category';
				var store_info = this.$store.state.globalStoreInfo;
				if (store_info) shareUrl += '?store_id=' + store_info.store_id;
				this.$util.setPublicShare({
					title: this.diyData.global.wechatShareTitle || this.diyData.global.title,
					desc: this.diyData.global.wechatShareDesc,
					link: shareUrl,
					imgUrl: this.diyData.global.weappShareImage ? this.$util.img(this.diyData.global.weappShareImage) : this.$util.img(this.siteInfo.logo_square)
				});
			},
			toLogin() {
				this.$refs.login.open('/pages/goods/category')
			}
		},
		onPullDownRefresh() {
			uni.hideTabBar();
			this.getDiyInfo();
		},
		// 分享给好友
		onShareAppMessage() {
			return this.mpShareData.appMessage;
		},
		// 分享到朋友圈
		onShareTimeline() {
			return this.mpShareData.timeLine;
		}
	};
</script>

<style lang="scss">
	/deep/ .uni-popup__wrapper.uni-center {
		background: rgba(0, 0, 0, 0.6);
	}

	/deep/ .uni-popup__wrapper-box {
		border-radius: 0 !important;
	}

	/deep/ .uni-popup__wrapper.uni-custom.center .uni-popup__wrapper-box {
		overflow-y: visible;
	}

	/deep/ .loading-layer {
		background: #fff !important;
	}

	// 分类四一级展开
	/deep/ .category-template-4 .template-four .uni-popup__wrapper-box {
		border-radius: 0px 0px 14px 14px !important;
		overflow: hidden;
	}

	/deep/ .category-template-4 .content-wrap .categoty-goods-wrap .goods-list {
		margin-top: 30rpx;
	}

	/deep/ .category-template-4 .content-wrap .goods-list .goods-item .footer-wrap .right-wrap .num-action {
		width: 46rpx;
		height: 46rpx;
	}

	/deep/ .uni-page-refresh-inner .uni-page-refresh__path {
		stroke: rgb(1, 1, 1) !important;
	}
</style>