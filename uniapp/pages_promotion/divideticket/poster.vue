<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="container">
		<swiper class="swiper">
			<swiper-item>
				<view class="swiper-item">
					<view class="poster-wrap">
						<image :src="$util.img(poster)" mode="widthFix" :show-menu-by-longpress="true"></image>
					</view>
				</view>
			</swiper-item>
		</swiper>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
	export default {
		data() {
			return {
				poster: "", //海报
				posterMsg: "", //海报错误信息
				posterHeight: 0,
				couponId: '',
				groupId: 0,
				inviterId: ''
			}
		},
		onLoad(option) {
			this.couponId = option.coupon_id
			this.groupId = option.group_id
			this.inviterId = option.inviter_id
			this.getGoodsPoster()
		},
		methods: {
			//生成海报
			getGoodsPoster() {
				//活动海报信息
				this.$api.sendRequest({
					url: "/divideticket/api/divideticket/poster",
					data: {
						coupon_id: this.couponId,
						group_id: this.groupId == '' ? 0 : this.groupId,
						inviter_id: this.inviterId == '' ? 0 : this.inviterId
					},
					success: res => {
						if (res.code == 0) {
							this.poster = res.data.path;
						} else {
							this.posterMsg = res.message;
						}
					}
				});
			},
		}
	}
</script>

<style lang="scss">
	.container {
		width: 100vw;
		min-height: 100vh;
		background-color: #f5f5f5;
	}

	.poster-wrap {
		padding: 40rpx 0;
		width: calc(100vw - 80rpx);
		margin: 0 40rpx;
		line-height: 1;

		image {
			border-radius: 20rpx;
			overflow: hidden;
			width: 100%;
		}
	}

	.swiper {
		height: 100vh;
	}

</style>