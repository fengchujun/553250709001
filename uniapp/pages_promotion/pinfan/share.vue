<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<view class="pintuan-wrap">
			<view class="pintuan-list">
				<image class="pintuan-bg" :src="$util.img('public/uniapp/pintuan/pintuan_bg.png')" @error="imageError()" mode="widthFix"/>
				<view class="list-item" @click="toPintuanDetail(groupDetail.pintuan_id)">
					<view class="item-image">
						<image :src="$util.img(groupDetail.sku_image, { size: 'big' })" @error="imageError()" mode="widthFix"/>
					</view>
					<view class="item-desc">
						<view class="desc-title">{{ groupDetail.sku_name }}</view>
						<view class="pintuan-num">
							<text class="color-base-text color-base-bg-light">已团{{ groupDetail.group_num }}次</text>
							<text class="color-base-border color-base-text">{{ groupDetail.pintuan_num }}人团</text>
						</view>
						<view class="color-tip">
							<text class="color-base-text font-size-toolbar margin-right">
								<text class="font-size-tag price-btn">￥</text>
								{{ groupDetail.pintuan_price }}
							</text>
							<text class="old-price font-size-tag">￥{{ groupDetail.discount_price }}</text>
						</view>
					</view>
				</view>
			</view>
			<view class="pintuan-clustering">
				<template v-if="groupDetail.timeMachine">
					<view class="tips">
						<view class="tips-title" v-if="kill > 0">
							还差
							<text class="color-base-text">{{ kill }}</text>
							人成团
						</view>
						<view class="tips-title" v-else>拼团已成功</view>
						距结束还剩
						<uni-count-down :day="groupDetail.timeMachine.d" :hour="groupDetail.timeMachine.h"
							:minute="groupDetail.timeMachine.i" :second="groupDetail.timeMachine.s"
							backgroundColorClass="color-base-bg" splitorColorClass="color-base-text" borderColor="#fff"
							color="#fff" />
					</view>
				</template>
				<view class="tips" v-else>活动已结束</view>
				<view class="headimg-group">
					<view class="group-member" v-for="(item, index) in groupDetail.member_list" :key="index">
						<view class="mark ns-gradient-promotionpages-pintuan-share-share" v-if="groupDetail.head_id == item.member_id">团长</view>
						<view class="member-face">
							<image :src="item.member_img ? $util.img(item.member_img) : $util.img('public/uniapp/common/default_headimg.png')" mode="aspectFill"/>
						</view>
					</view>
					<view class="group-member" v-for="itm in kill">
						<view class="member-face">
							<image :src="$util.img('public/uniapp/common/spelling_who.png')" mode="aspectFill"></image>
						</view>
					</view>
				</view>
				<view class="pintuan-btn-box" v-if="groupDetail.timeMachine">
					<!-- #ifdef MP -->
					<button type="primary" class="pintuan-btn" open-type="share">邀请好友参团</button>
					<!-- #endif -->
					<!-- #ifdef H5 -->
					<button type="primary" class="pintuan-btn" @click="openSharePopup">邀请好友参团</button>
					<!-- #endif -->
					<button class="one_btn pintuan-btn" @click="orderDetail(groupDetail)">查看订单</button>
				</view>
			</view>
			<image class="pintuan-playing" :src="$util.img('public/uniapp/pinfan/pinfan_playing.png')" mode="widthFix">
			</image>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>

		<!-- 分享弹窗 -->
		<view @touchmove.prevent.stop>
			<uni-popup ref="sharePopup" type="bottom" class="share-popup">
				<view>
					<view class="share-title">分享</view>
					<view class="share-content">
						<!-- #ifdef MP -->
						<view class="share-box">
							<button class="share-btn" :plain="true" open-type="share">
								<view class="iconfont icon-share-friend"></view>
								<text>分享给好友</text>
							</button>
						</view>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view class="share-box" @click="copyUrl">
							<button class="share-btn" :plain="true">
								<view class="iconfont icon-fuzhilianjie"></view>
								<text>复制链接</text>
							</button>
						</view>
						<!-- #endif -->
						<view class="share-box" @click="openPosterPopup">
							<button class="share-btn" :plain="true">
								<view class="iconfont icon-pengyouquan"></view>
								<text>生成分享海报</text>
							</button>
						</view>
					</view>
					<view class="share-footer" @click="closeSharePopup"><text>取消分享</text></view>
				</view>
			</uni-popup>
		</view>

		<!-- 海报 -->
		<view @touchmove.prevent.stop class="poster-layer">
			<uni-popup ref="posterPopup" type="center">
				<template v-if="poster != '-1'">
					<view class="poster-wrap">
						<view class="image-wrap">
							<image :src="$util.img(poster)" :show-menu-by-longpress="true" mode="widthFix" />
							<view class="close iconfont icon-close" @click="closePosterPopup()"></view>
						</view>
						<!-- #ifdef MP || APP-PLUS  -->
						<view class="save-btn" @click="saveGoodsPoster()">保存图片</view>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view class="save-btn">长按图片进行保存</view>
						<!-- #endif -->
					</view>
				</template>
			</uni-popup>
		</view>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
	import uniCountDown from '@/components/uni-count-down/uni-count-down.vue';
	export default {
		components: {
			uniCountDown
		},
		data() {
			return {
				id: 0,
				groupDetail: {
					sku_image: ''
				},
				kill: 0,
				poster: "-1", //海报
				posterMsg: "", //海报错误信息
				posterHeight: 0,
			};
		},
		onLoad(e) {
			setTimeout( () => {
				if (!this.addonIsExist.pinfan) {
					this.$util.showToast({
						title: '商家未开启拼团返利',
						mask: true,
						duration: 2000
					});
					setTimeout(() => {
						this.$util.redirectTo('/pages/index/index');
					}, 2000);
				}
			},1000);

			this.id = e.id;

			this.getGroupDetail();
		},
		methods: {
			getGroupDetail() {
				this.$api.sendRequest({
					url: '/pinfan/api/order/detail',
					data: {
						id: this.id
					},
					success: res => {
						if (res.code == 0 && res.data) {
							let newArr = [];
							for (let i = 0; i < res.data.pintuan_num - res.data.member_list.length; i++) {
								let str = '';
								newArr.push(str);
							}
							this.kill = res.data.pintuan_num - res.data.pintuan_count;
							res.data.member_list = res.data.member_list.concat(newArr);
							if (res.data.group_end_time > res.timestamp) {
								res.data.timeMachine = this.$util.countDown(res.data.group_end_time - res.timestamp);
							} else {
								res.data.timeMachine = null;
							}
							this.groupDetail = res.data;

							this.groupDetail.member_list = this.groupDetail.member_list.filter(function(item,
								index) {
								return item;
							});

							this.setPublicShare();
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail: res => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			toPintuanDetail(id) {
				this.$util.redirectTo('/pages_promotion/pinfan/detail', {
					pinfan_id: id
				});
			},
			toDetail(id, groupId) {
				this.$util.redirectTo('/pages_promotion/pintuan/detail', {
					pintuan_id: this.groupDetail.pintuan_id,
					group_id: groupId
				});
			},
			imageError() {
				this.groupDetail.sku_image = this.$util.getDefaultImage().goods;
				this.$forceUpdate();
			},
			// 打开分享弹出层
			openSharePopup() {
				this.$refs.sharePopup.open();
			},
			// 关闭分享弹出层
			closeSharePopup() {
				this.$refs.sharePopup.close();
			},
			copyUrl() {
				let text = this.$config.h5Domain + '/pages_promotion/pinfan/detail?pinfan_id=' + this.groupDetail
					.pintuan_id + '&group_id=' + this.groupDetail.group_id;
				if (this.memberInfo && this.memberInfo.member_id) text += '&source_member=' + this.memberInfo.member_id;
				this.$util.copy(text, () => {
					this.closeSharePopup();
				});
			},
			orderDetail(data) {
				this.$util.redirectTo('/pages/order/detail', {
					order_id: data.order_id
				});
			},
			/**
			 * 设置公众号分享
			 */
			setPublicShare() {
				let shareUrl = this.$config.h5Domain + '/pages_promotion/pinfan/detail?pinfan_id=' + this.groupDetail.pintuan_id + '&group_id=' + this.groupDetail.group_id;
				if (this.memberInfo && this.memberInfo.member_id) shareUrl += '&source_member=' + this.memberInfo.member_id;

				this.$util.setPublicShare({
					title: this.groupDetail.sku_name,
					desc: '还差' + (this.groupDetail.pintuan_num - this.groupDetail.pintuan_count) + '人就拼团成功了',
					link: shareUrl,
					imgUrl: this.$util.img(this.groupDetail.sku_image)
				})
			},
			//-------------------------------------海报-------------------------------------
			// 打开海报弹出层
			openPosterPopup() {
				this.getGoodsPoster();
				this.$refs.sharePopup.close();
			},
			// 关闭海报弹出层
			closePosterPopup() {
				this.$refs.posterPopup.close();
			},
			/**
			 * 获取海报
			 */
			getGoodsPoster() {
				uni.showLoading({
					'title': '海报生成中...'
				})
				//活动海报信息
				// #ifdef MP
				let posterParams = {
					id: this.groupDetail.pintuan_goods_id,
					pinfan: this.groupDetail.pintuan_id,
					group: this.groupDetail.group_id
				};
				// #endif

				// #ifndef MP
				let posterParams = {
					id: this.groupDetail.pintuan_goods_id,
					pinfan_id: this.groupDetail.pintuan_id,
					group_id: this.groupDetail.group_id
				};
				// #endif

				this.$api.sendRequest({
					url: '/pinfan/api/goods/poster',
					data: {
						page: '/pages_promotion/rebate/order',
						qrcode_param: JSON.stringify(posterParams)
					},
					success: res => {
						if (res.code == 0) {
							this.poster = res.data.path + "?time=" + new Date().getTime();
							this.$refs.posterPopup.open();
						} else {
							this.posterMsg = res.message;
							this.$util.showToast({
								title: this.posterMsg
							})
						}
						uni.hideLoading();
					},
					fail: err => {
						uni.hideLoading();
					}
				});
			},
		},
		/**
		 * 自定义分享内容
		 * @param {Object} res
		 */
		onShareAppMessage(res) {
			var path = '/pages_promotion/pinfan/detail?pinfan_id=' + this.groupDetail.pintuan_id + '&group_id=' + this.groupDetail.group_id;
			if (this.memberInfo && this.memberInfo.member_id) path += '&source_member=' + this.memberInfo.member_id;
			return {
				title: '还差' + (this.groupDetail.pintuan_num - this.groupDetail.pintuan_count) + '人拼团成功，' + this.groupDetail.sku_name,
				imageUrl: this.$util.img(this.groupDetail.sku_image, {
					size: 'big'
				}),
				path: path,
				success: res => {},
				fail: res => {}
			};
		}
	};
</script>

<style lang="scss">
	.pintuan-wrap {
		.pintuan-list {
			position: relative;
			background-color: #fff;
			height: 428rpx;

			.pintuan-bg {
				width: 100%;
			}

			.list-item {
				position: absolute;
				top: 90rpx;
				display: flex;
				margin: 0 $margin-both;
				padding: $margin-both;
				width: 690rpx;
				height: 264rpx;
				border-radius: $border-radius;
				box-sizing: border-box;
				background-color: #fff;

				.item-image {
					overflow: hidden;
					margin-right: 34rpx;
					width: 192rpx;
					height: 208rpx;
					border-radius: $border-radius;

					image {
						width: 100%;
						height: 100%;
					}
				}

				.item-desc {
					flex: 1;
					display: flex;
					justify-content: space-between;
					flex-direction: column;

					.desc-title {
						overflow: hidden;
						font-size: $font-size-base;
						height: 84rpx;
						text-overflow: ellipsis;
						display: -webkit-box;
						-webkit-line-clamp: 2;
						-webkit-box-orient: vertical;
						line-height: 42rpx;
						color: $color-title;
					}

					.pintuan-num {
						// margin-top: 22rpx;
						display: flex;
						align-items: baseline;
						line-height: 1;

						text {
							display: flex;
							align-items: center;
							justify-content: center;
							border: 2rpx solid;
							padding: 6rpx;
							margin-right: 24rpx;
							font-size: $font-size-tag;
							border-radius: 4rpx;

							&:nth-child(1) {
								padding: 10rpx;
								border: 0;
							}
						}
					}

					.color-tip {
						line-height: 1;
						margin-top: 36rpx;

						.price-btn {
							margin-right: 4rpx;
						}

						.old-price {
							text-decoration: line-through;
						}
					}
				}
			}
		}

		.pintuan-clustering {
			padding: 50rpx 0 46rpx;
			margin-top: $margin-updown;
			background-color: #fff;

			.tips {
				font-size: $font-size-base;
				text-align: center;

				.tips-title {
					line-height: 1;
					margin-bottom: $margin-updown;
					font-size: 40rpx;
					font-weight: bold;
				}
			}
		}

		.headimg-group {
			padding-top: $margin-both;
			padding-bottom: 40rpx;
			background-color: #fff;
			line-height: 1;
			text-align: center;
			height: 100rpx;
			overflow: hidden;

			.group-member {
				position: relative;
				width: 100rpx;
				height: 100rpx;
				// margin: 0 20rpx 20rpx 0;
				display: inline-block;
				margin: 30rpx;

				.member-face {
					width: 100rpx;
					height: 100rpx;
					border-radius: 50%;
					overflow: hidden;
					line-height: 1;

					image {
						width: 100rpx;
						height: 100rpx;
					}
				}

				.mark {
					white-space: nowrap;
					position: absolute;
					z-index: 5;
					color: #fff;
					line-height: 1;
					font-size: $font-size-tag;
					padding: 4rpx 10rpx;
					border-radius: 28rpx;
					left: 50%;
					bottom: -14rpx;
					transform: translate(-50%, -20%);
				}
			}

			/* .group-image .member-face {
				width: 100rpx;
				height: 100rpx;
				border: 2rpx solid;
				image {
					width: 100rpx;
					height: 100rpx;
				}
			} */
		}

		.pintuan-btn-box {
			margin-top: 60rpx;
		}

		.pintuan-btn {
			width: 560rpx;
			height: 84rpx;
			line-height: 84rpx;
			margin: 22rpx auto 0;
		}
	}

	.pintuan-playing {
		margin-top: 20rpx;
		width: 100%;
	}

	button.one_btn {
		background-color: #fff;
		border: 2rpx solid $color-disabled;
		color: $color-tip;
	}

	.share-btn {
		margin-top: 20rpx;
		background-color: #fff;
		border: 1px solid;
	}

	.share-popup,
	.uni-popup__wrapper-box {
		.share-title {
			line-height: 60rpx;
			font-size: $font-size-toolbar;
			padding: 15rpx 0;
			text-align: center;
		}

		.share-content {
			display: flex;
			display: -webkit-flex;
			-webkit-flex-wrap: wrap;
			-moz-flex-wrap: wrap;
			-ms-flex-wrap: wrap;
			-o-flex-wrap: wrap;
			flex-wrap: wrap;
			padding: 15rpx;

			.share-box {
				flex: 1;
				text-align: center;

				.share-btn {
					margin: 0;
					padding: 0;
					border: none;
					line-height: 1;
					height: auto;

					text {
						margin-top: 20rpx;
						font-size: $font-size-tag;
						display: block;
						color: $color-title;
					}
				}

				.iconfont {
					font-size: 80rpx;
					line-height: initial;
				}

				.icon-fuzhilianjie,
				.icon-pengyouquan,
				.icon-haowuquan,
				.icon-share-friend {
					color: #07c160;
				}
			}
		}

		.share-footer {
			height: 90rpx;
			line-height: 90rpx;
			border-top: 2rpx #f5f5f5 solid;
			text-align: center;
			color: #666;
		}
	}

	.poster-layer {
		/deep/ .uni-popup__wrapper.center {
			width: 100vw !important;
			height: 100vh !important;
			background: none !important;
		}

		/deep/ .uni-popup__wrapper.uni-custom.center .uni-popup__wrapper-box {
			max-width: 100vw !important;
			max-height: 100vh !important;
			background: none !important;
			width: 100vw;
			height: 100vh;
		}

		.poster-wrap {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 100vw;
			height: 100vh;
			flex-direction: column;
		}

		.generate-poster {
			padding: 40rpx 0;

			.iconfont {
				font-size: 80rpx;
				color: #07c160;
				line-height: initial;
			}

			>view {
				text-align: center;

				&:last-child {
					margin-top: 20rpx;
				}
			}
		}

		.image-wrap {
			width: 80%;
			position: relative;

			image {
				width: 100%;
				line-height: 1;
				border-radius: 10rpx;
				overflow: hidden;
			}
		}

		.msg {
			padding: 40rpx;
		}

		.save-btn {
			text-align: center;
			height: 80rpx;
			line-height: 80rpx;
			background-color: $base-color;
			border-radius: 10rpx;
			width: 80%;
			color: #fff;
			margin-top: 30rpx;
		}

		.save-text {
			color: #fff;
			margin-top: 10rpx;
		}

		.close {
			position: absolute;
			top: 0;
			right: 20rpx;
			width: 40rpx;
			height: 80rpx;
			font-size: 50rpx;
			color: #999;
		}
	}
</style>