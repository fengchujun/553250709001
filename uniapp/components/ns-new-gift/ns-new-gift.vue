<template>
	<view>
		<view @touchmove.prevent.stop v-if="newgift" class="reward-popup">
			<uni-popup ref="nsNewGift" type="center" :maskClick="false">
				<view class="reward-wrap">
					<view class="newgift-content" :style="{ backgroundImage: 'url(' + $util.img('public/uniapp/new_gift/holiday_polite-bg.png') + ')' }">
						<view class="content-title-holiday">
							<image :src="$util.img('public/uniapp/new_gift/holiday_polite_left.png')" mode="" class="birthday-img-all" />
							<view class="font-size-toolbar activity-name">{{ newgift.activity_name }}</view>
							<image :src="$util.img('public/uniapp/new_gift/holiday_polite_right.png')" mode="" class="birthday-img-all" />
						</view>
						<view class="content-title-name" v-if="memberInfo">Dear {{ memberInfo.nickname }}</view>
						<view class="content-title-hint" v-if="newgift.remark">{{ newgift.remark }}</view>
						<view class="content-title-hint" v-else>感谢您一直以来的支持，为回馈会员，商城{{ newgift.activity_name ? newgift.activity_name : 'xx' }}节日，为您提供以下福利</view>
						<scroll-view scroll-y="true" class="register-box">
							<view :class="introduction > 38 ? 'reward-content' : 'reward-content-two'">
								<view class="content" v-if="newgift.award_list.point > 0">
									<view class="info">
										<text class="num">
											{{ newgift.award_list.point }}
											<text class="type">积分</text>
										</text>
										<view class="desc">用于参与活动购买商品时抵扣</view>
									</view>
									<view class="tip" @click="closeRewardPopup('1')">立即查看</view>
								</view>
								<view class="content" v-if="newgift.award_list.balance_type == 0 && newgift.award_list.balance > 0">
									<view class="info">
										<text class="num">
											{{ newgift.award_list.balance | int }}
											<text class="type">元红包</text>
										</text>
										<view class="desc">不可提现红包</view>
									</view>
									<view class="tip" @click="closeRewardPopup('2')">立即查看</view>
								</view>
								<view class="content" v-if="newgift.award_list.balance_type == 1 && newgift.award_list.balance_money > 0">
									<view class="info">
										<text class="num">
											{{ newgift.award_list.balance_money | int }}
											<text class="type">元红包</text>
										</text>
										<view class="desc">可提现红包</view>
									</view>
									<view class="tip" @click="closeRewardPopup('2')">立即查看</view>
								</view>
								<block v-if="newgift.award_list.coupon_list.length > 0">
									<block v-for="(item, index) in newgift.award_list.coupon_list" :key="index">
										<view class="content">
											<view class="info">
												<text v-if="item.type == 'reward'" class="num">
													{{ parseFloat(item.money) }}
													<text class="type">元优惠劵</text>
												</text>
												<text v-else-if="item.type == 'discount'" class="num">
													{{ item.discount | int }}
													<text class="type">折</text>
												</text>
												<view class="desc">用于下单时抵现或兑换商品等</view>
											</view>
											<view class="tip" @click="closeRewardPopup('3')">立即查看</view>
										</view>
									</block>
								</block>
							</view>
						</scroll-view>
					</view>
					<view class="close-btn" @click="cancel()">
						<text class="iconfont icon-close btn"></text>
					</view>
				</view>
			</uni-popup>
		</view>
	</view>
</template>

<script>
	import uniPopup from '../uni-popup/uni-popup.vue';

	export default {
		components: {
			uniPopup
		},
		data() {
			return {
				newgift: {
					flag: false,
					award_list: {
						point: 0,
						coupon_list: {}
					},
					remark: {}
				},
				bgHight: '940rpx !important',
				bytesCount: null,
				callback: null
			};
		},
		filters: {
			int(val) {
				var str = String(val);
				var arr = str.split('.');
				if (parseInt(arr[1]) > 0) {
					return str;
				} else {
					return arr[0];
				}
			}
		},
		computed: {
			introduction() {
				let bytesCount = 0;
				for (let i = 0, n = this.newgift.remark.length; i < n; i++) {
					let c = this.newgift.remark.charCodeAt(i);
					if ((c >= 0x0001 && c <= 0x007e) || (0xff60 <= c && c <= 0xff9f)) {
						bytesCount += 1;
					} else {
						bytesCount += 2;
					}
				}
				return bytesCount;
			}
		},
		created() {
			if (!this.storeToken) return;
			this.init();
		},
		methods: {
			init(callback = null) {
				if (callback) this.callback = callback;
				this.getHolidayGift();
			},
			// 查询节日有礼设置
			getHolidayGift() {
				this.$api.sendRequest({
					url: '/scenefestival/api/config/config',
					success: res => {
						if (res.data && res.data[0]) {
							this.newgift = res.data[0];
							if (this.newgift.award_list.award_type.length <= 1) {
								this.bgHight = '800rpx !important';
							}
							this.getGift();
						}
					}
				});
			},
			cancel() {
				this.$refs.nsNewGift.close();
			},
			getGift() {
				if (this.newgift.flag == true) {
					this.$refs.nsNewGift.open();
					this.$api.sendRequest({
						url: '/scenefestival/api/config/receive',
						data: {
							festival_id: this.newgift.festival_id
						},
						success: res => {
							if (this.callback) this.callback();
						}
					});
				}
			},
			closeRewardPopup(type) {
				if (type == 1) {
					this.$util.redirectTo('/pages_tool/member/point_detail', {});
				} else if (type == 2) {
					this.$util.redirectTo('/pages_tool/member/balance_detail', {});
				} else if (type == 3) {
					this.$util.redirectTo('/pages_tool/member/coupon', {});
				}
			}
		}
	};
</script>

<style scoped>
	/deep/ .newgift-content uni-image {
		width: 113rpx !important;
		height: 24rpx !important;
	}

	/deep/ .reward-popup .uni-popup__wrapper.uni-custom.center .uni-popup__wrapper-box {
		max-height: unset !important;
		overflow-y: unset;
	}

	.register-box /deep/ .uni-scroll-view {
		background: unset !important;
	}

	.register-box {
		max-height: 300rpx;
		overflow-y: scroll;
		/* margin-top: 610rpx; */
	}
</style>

<style lang="scss">
	.reward-wrap {
		width: 85vw;
		height: auto;

		.newgift-content {
			width: 100%;
			height: auto;
			background-size: 100%;
			background-repeat: no-repeat;
			padding-bottom: 40rpx;
		}

		.content-title-holiday {
			font-size: $font-size-toolbar;
			font-weight: bold;
			font-family: BDZongYi-A001;
			display: flex;
			align-items: center;
			justify-content: center;
			// margin-bottom: 20rpx;
			padding-top: 320rpx;
			line-height: 1;

			.birthday-img-all {
				width: 100rpx;
				height: 20rpx;
			}

			&>view {
				margin: 0 20rpx;
				color: #fff;
				font-weight: bold;
			}
		}

		.content-title-name {
			font-size: $font-size-toolbar;
			font-weight: bold;
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			-webkit-line-clamp: 2;
			-webkit-box-orient: vertical;
			text-align: center;
			color: #fff;
			margin: 30rpx 0 40rpx;
			line-height: 1;
		}

		.content-title-hint {
			margin: 0 70rpx 40rpx;
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			-webkit-line-clamp: 2;
			-webkit-box-orient: vertical;
			text-align: center;
			color: #fff;
		}

		.reward-content {
			max-height: 300rpx;
			margin: 0 56rpx;
		}

		.reward-content-two {
			max-height: 360rpx;
			margin: 0 56rpx;
		}

		.head {
			color: #fff;
			text-align: center;
			line-height: 1;
			margin: 20rpx 0;
		}

		& .content:last-child {
			margin-bottom: 0;
		}

		.content {
			display: flex;
			align-items: center;
			padding: 16rpx 26rpx;
			background: #fff;
			border-radius: 10rpx;
			margin-bottom: 20rpx;

			.info {
				flex: 1;
			}

			.tip {
				color: #fa5b14;
				padding: 10rpx 0 10rpx 20rpx;
				width: 60rpx;
				line-height: 1.5;
				letter-spacing: 2rpx;
				border-left: 2rpx dashed #e5e5e5;
			}

			.num {
				font-size: 48rpx;
				color: #fa5b14;
				font-weight: bolder;
				line-height: 1;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
				max-width: 300rpx;
			}

			.type {
				font-size: $font-size-tag;
				margin-left: 10rpx;
				line-height: 1;
				font-weight: normal;
				color: #606266;
			}

			.desc {
				margin-top: 8rpx;
				color: $color-tip;
				font-size: $font-size-tag;
				line-height: 1;
			}
		}

		.close-btn {
			text-align: center;
			margin-top: 20rpx;
			z-index: 500;

			.btn {
				color: #fff;
				font-size: 40rpx;
				border: 4rpx solid #fff;
				border-radius: 50%;
				padding: 10rpx;
				font-weight: bold;
				width: 40rpx;
				height: 40rpx;
				margin: 0 auto;
				line-height: 40rpx;
			}
		}
	}
</style>