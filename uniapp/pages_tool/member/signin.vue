<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="signin">
		<block v-if="signState">
			<!-- #ifdef H5 -->
			<view class="head-nav color-base-bg"></view>
			<!-- #endif -->
			<view class="sigin-box">
				<view class="sigin-bg"></view>

				<view class="signin-wrap">
					<view class="member-info">
						<view class="headimg">
							<view class="headimg-img">
								<image :src="headimg ? $util.img(headimg) : $util.getDefaultImage().head" mode="aspectFill" @error="headimg = $util.getDefaultImage().head"/>
							</view>

							<view class="signin-info">
								<view>
									已连续签到
									<text>{{ signDaysSeries }}</text>
									天
								</view>
								<view>{{ hasSign ? '明日' : '今日' }}签到可获得{{ pointTomorrow }}积分</view>
							</view>
						</view>

						<view class="point-box" @click="sign()">
							<image :src="$util.img(hasSign == 1 ? 'public/uniapp/signin/sign-btn-res.png' : 'public/uniapp/signin/sign-btn.png')" mode="widthFix"/>
						</view>
					</view>

					<view class="signin-days-wrap">
						<view class="signin-desc">连续签到领好礼</view>
						<view class="signin-day-list">
							<view class="signin-day-con">
								<view class="signin-day-scroll">
									<block v-for="(item, index) in showSignDays">
										<view v-if="!item.is_last" :id="'id_' + item.day" class="signin-day-item"
											:class="{
												signed: item.day < signDaysSeries || (item.day == signDaysSeries && hasSign == 0) || (item.day == signDaysSeries && hasSign == 1)
											}">
											<view class="day">第{{ item.day }}天</view>
											<image :src="$util.img('public/uniapp/signin/sign-icon.png')"></image>
											<view class="point">{{ item.point }}积分</view>
										</view>
										<view v-else :id="'id_' + item.day" class="signin-day-item last" :class="{
												signed: item.day < signDaysSeries || (item.day == signDaysSeries && hasSign == 0) || (item.day == signDaysSeries && hasSign == 1)
											}">
											<view>
												<view class="day">第{{ item.day }}天</view>
												<view class="point">{{ item.point }}积分</view>
											</view>
											<image :src="$util.img('public/uniapp/signin/sign-box.png')" mode="widthFix"/>
										</view>
									</block>
								</view>
							</view>
						</view>
					</view>

					<view class="my-signin">
						<view class="my-signin-title">我的签到</view>

						<view class="my-signin-con">
							<view class="my-signin-item">
								<image :src="$util.img('public/uniapp/signin/sign-bg-yellow.png')"></image>
								<view class="my-signin-item-num">积分：{{ signPoint }}</view>
								<view>累计获得积分</view>
							</view>
							<view class="my-signin-item">
								<image :src="$util.img('public/uniapp/signin/sign-bg-pink.png')"></image>
								<view class="my-signin-item-num">成长值：{{ signGrowth }}</view>
								<view>累计获得成长值</view>
							</view>
						</view>
					</view>

					<view class="signin-rule" v-if="rule && rule.length">
						<view class="signin-rule-title">签到规则</view>

						<view class="signin-rule-con">
							<view class="rule-item" v-for="(item, index) in rule" :key="index">
								<block v-if="index == 0">1. 每日签到奖励：</block>
								<block v-else>{{ index + 1 + '. 连续签到' + item.day + '天额外奖励：' }}</block>
								<text v-if="item.point">{{ item.point + '积分 ' }}</text>
								<text v-if="item.growth">{{ item.growth + '成长值' }}</text>
							</view>

							<view class="rule-item">
								{{ rule.length + 1 }}.连续签到{{ cycle }}天为一个周期，连续签到天数签满一个周期或者签到中断，将清空连签天数重新计算签到天数
							</view>
							<view class="rule-item">
								{{ rule.length + 2 }}. 用户可在签到页每日签到一次，签到后可获得每日签到奖励；连续签到天数达到连签奖励的当天，可额外获得连签奖励
							</view>
						</view>
					</view>
				</view>
			</view>

			<view @touchmove.prevent.stop>
				<uni-popup ref="uniPopup" type="center" class="wap-floating" :maskClick="false">
					<view class="popup-box" @click="close()">
						<text class="iconfont icon-close" @click="$refs.uniPopup.close()"></text>
						<image class="pic" :src="$util.img('public/uniapp/signin/bg1.png')"></image>
						<view class="popup-content">
							<view class="popup-content-wrap">
								<view class="title" v-if="successTip.point || successTip.growth">
									<text>恭喜您获得</text>
									<text v-if="successTip.point">
										<text class="num color-base-text">{{ successTip.point }}</text>
										积分
									</text>
									<text v-if="successTip.growth">
										<text class="num color-base-text">{{ successTip.growth }}</text>
										成长值
									</text>
								</view>
								<view class="desc">连续签到可获得更多奖励！</view>
							</view>
							<view class="other-info color-base-bg" @click="$refs.uniPopup.close()">知道了</view>
						</view>
					</view>
				</uni-popup>
			</view>

			<!-- 悬浮按钮 -->
			<hover-nav :need="true"></hover-nav>
		</block>
		<block v-else><ns-empty text="暂未开启签到奖励" subText="请到营销中心开启签到奖励" :isIndex="false"></ns-empty></block>

		<loading-cover ref="loadingCover"></loading-cover>

		<ns-login ref="login"></ns-login>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>
<script>
	import uniCalender from '@/pages_tool/components/uni-calendar/uni-calendar.vue';
	import uniPopup from '@/components/uni-popup/uni-popup.vue';
	import signIn from './public/js/signin.js';
	export default {
		components: {
			uniPopup,
			uniCalender
		},
		mixins: [signIn]
	};
</script>
<style lang="scss">
	uni-page-body,
	uni-page-refresh {
		height: 100%;
	}

	.signin {
		position: relative;
		height: 100%;

		.head-nav {
			width: 100%;
			height: var(--status-bar-height);
		}

		.head-nav.active {
			padding-top: 40rpx;
		}

		.sigin-box {
			position: relative;
		}

		.sigin-bg {
			width: 100%;
			position: absolute;
			top: 0;
			z-index: 1;
			background: linear-gradient(136deg, #fe7849 0%, #ff1959 100%);
			height: 500rpx;
			border-radius: 0 0 100% 100%/0 0 20% 20%;

			image {
				width: 100%;
			}
		}

		.signin-wrap {
			padding: 0 30rpx;
		}

		.member-info {
			position: relative;
			z-index: 9;
			padding: 74rpx 0 55rpx;
			display: flex;
			align-items: center;
			justify-content: space-between;

			.headimg {
				display: flex;
				align-items: center;

				.headimg-img {
					width: 100rpx;
					height: 100rpx;
					background: #fff;
					border: 0px solid #fff;
					border-radius: 50%;
					overflow: hidden;
					margin-right: 20rpx;

					image {
						width: 100%;
						height: 100%;
					}
				}

				.signin-info {
					view {
						color: #ffffff;
						font-size: 24rpx;
						line-height: 1;

						&:first-child {
							margin-bottom: 18rpx;
							font-size: 32rpx;
							font-weight: bold;

							text {
								margin: 0 9rpx;
							}
						}
					}
				}
			}

			.point-box {
				display: flex;
				align-items: center;
				height: 68rpx;
				padding: 13rpx 0 13rpx 0;
				box-sizing: border-box;

				image {
					width: 160rpx;
					height: 68rpx;
					max-height: 80rpx;
					margin-bottom: -6rpx;
				}

				.point {
					margin-left: 14rpx;
					font-size: $font-size-toolbar;
					color: #ffffff;
				}
			}
		}

		.signin-days-wrap {
			position: relative;
			z-index: 9;
			background-color: #ffffff;
			border-radius: 18rpx;
			padding: 30rpx 0 30rpx;
			height: 468rpx;

			.signin-desc {
				font-size: 32rpx;
				font-weight: bold;
				line-height: 1;
				padding: 0 30rpx;
			}

			.signin-day-list {
				margin-top: 30rpx;
				display: flex;
				justify-content: space-between;
				padding: 0 30rpx;

				.signin-day-con {
					width: 100%;
				}

				.signin-day-scroll {
					display: flex;
					flex-wrap: wrap;
					width: 100%;
					flex-direction: row;
					white-space: nowrap;
					line-height: 1;

					.signin-day-item {
						flex-shrink: 0;
						background: #f5f6fa;
						margin-right: 30rpx;
						width: calc((100% - 90rpx) / 4);
						height: 155rpx;
						border-radius: 18rpx;
						margin-bottom: 24rpx;

						&:last-child {
							margin-right: 0;
							width: calc((100% - 30rpx) / 2);
						}

						&:nth-child(4n) {
							margin-right: 0;
						}

						image {
							width: 60rpx;
							height: 60rpx;
							margin-top: 10rpx;
						}

						&.signed {
							background: linear-gradient(136deg, #fe7849 0%, #ff1959 100%);

							view {
								color: #ffffff;
							}
						}

						&.last {
							display: flex;
							flex-direction: row;

							>view {
								width: 50%;

								.point {
									margin: 15rpx;
								}
							}

							image {
								width: 40%;
								margin-top: 20rpx;
								max-width: 130rpx;
							}
						}
					}

					.reward {
						image {
							width: 100%;
							height: 100%;
							margin-top: 0;
						}
					}
				}

				.signin-day-item {
					display: inline-block;
					width: 85rpx;
					height: 103rpx;
					border-radius: 4rpx;
					text-align: center;
					background-color: #f6f6fb;
					flex-shrink: 0;

					.day {
						font-size: 24rpx;
						line-height: 1;
						margin-top: 18rpx;
						font-weight: bold;
					}

					.point {
						font-size: 20rpx;
						line-height: 1;
						margin-top: -4rpx;
						color: #abb0c1;
					}

					image {
						width: 100%;
						height: 100%;
					}
				}
			}

			.signin-btn {
				position: absolute;
				width: 100%;
				height: 80rpx;
				bottom: 40rpx;
				text-align: center;

				button {
					display: inline-block;
					width: 442rpx;
					height: 100%;
					color: #ffffff;
					background-color: #ff4544;
				}

				.btn-active {
					color: $color-title;
					background-color: #e1e1e1;
				}
			}
		}

		.my-signin {
			padding: 30rpx;
			margin-top: 30rpx;
			background-color: #ffffff;
			border-radius: 18rpx;

			.my-signin-title {
				font-size: 32rpx;
				line-height: 1;
				font-weight: bold;
			}

			.my-signin-con {
				display: flex;
				justify-content: space-between;
				margin-top: 30rpx;
			}

			.my-signin-item {
				width: 300rpx;
				height: 155rpx;
				position: relative;

				image {
					width: 300rpx;
					height: 155rpx;
					position: absolute;
					top: 0;
					left: 0;
				}

				.my-signin-item-num {
					position: relative;
					z-index: 9;
					padding: 44rpx 28rpx 0;
					line-height: $font-size-toolbar;
					font-weight: bold;
				}

				view:last-child {
					position: relative;
					z-index: 9;
					color: #abb0c1;
					padding-left: 28rpx;
					font-size: $font-size-activity-tag;
					line-height: 1;
					margin-top: 16rpx;
				}
			}
		}

		.signin-rule {
			margin-top: 30rpx;
			background-color: #ffffff;
			border-radius: 18rpx;
			padding: 30rpx;

			.signin-rule-title {
				display: flex;
				align-items: center;
				justify-content: flex-start;
				line-height: $font-size-toolbar;
				font-size: 32rpx;
				font-weight: bold;
				text-align: left;
			}

			.signin-rule-con {
				margin-top: 30rpx;
				padding: 0 0;

				.rule-item {
					font-size: 26rpx;
					color: $color-sub;
				}
			}
		}
	}

	.popup-box {
		position: relative;
		top: 0;
		display: flex;
		flex-direction: column;
		align-items: center;

		.icon-close {
			width: 42rpx;
			height: 42rpx;
			border: 1px solid #fff;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 50%;
			color: #fff;
			position: absolute;
			top: 70rpx;
			right: 0;
		}

		.pic {
			width: 274rpx;
			height: 200rpx;
			position: relative;
			margin-bottom: -30rpx;
		}

		.popup-content {
			background: #ffffff;
			width: 70vw;
			margin-top: -100rpx;
			border-radius: 20rpx;
			padding: 0 60rpx;
			padding-bottom: 40rpx;
			box-sizing: border-box;
			background-size: 100% 100rpx;
		}

		.popup-content-wrap {
			display: flex;
			flex-direction: column;
			align-items: center;

			.title {
				font-size: $font-size-base;
				margin-top: 160rpx;
				color: #999;

				.num {
					font-size: 32rpx;
				}
			}

			.desc {
				color: #999;
				font-size: $font-size-tag;
			}
		}

		.other-info {
			width: 300rpx;
			height: 70rpx;
			border-radius: 10rpx;
			margin: 0 auto;
			color: #fff;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-top: 40rpx;
		}
	}
</style>

<style scoped>
	.wap-floating>>>.uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
		background: none !important;
		display: flex !important;
		flex-direction: column !important;
		align-items: center !important;
	}
</style>