<template>
	<view :style="value.pageStyle">
		<view class="diy-notice">
			<view :class="['notice', value.contentStyle]" :style="noticeWrapCss">
				<image v-if="value.iconType == 'img'" class="notice-img" :src="$util.img(value.imageUrl)" mode="heightFix"/>
				<diy-icon v-if="value.iconType == 'icon'" :icon="value.icon" :value="value.style ? value.style : 'null'" :style="{ maxWidth: 30 * 2 + 'rpx', maxHeight: 30 * 2 + 'rpx', width: '100%', height: '100%' }"></diy-icon>
				<view class="notice-xian"></view>
				<view class="main-wrap">
					<!-- 横向滚动 -->
					<view class="horizontal-wrap" v-if="value.scrollWay == 'horizontal'">
						<view class="marquee-wrap">
							<view class="marquee" :style="marqueeStyle">
								<text v-for="(item, index) in list" :key="index" @click="toLink(item)" :style="{ color: value.textColor, fontSize: value.fontSize * 2 + 'rpx', fontWeight: value.fontWeight }">{{ item.title }}</text>
							</view>
							<view class="marquee" :style="marqueeAgainStyle">
								<text v-for="(item, index) in list" :key="index" @click="toLink(item)" :style="{ color: value.textColor, fontSize: value.fontSize * 2 + 'rpx', fontWeight: value.fontWeight }">{{ item.title }}</text>
							</view>
						</view>
					</view>

					<!-- 上下滚动 -->
					<template v-if="value.scrollWay == 'upDown'">
						<swiper :vertical="true" :duration="500" autoplay="true" circular="true">
							<swiper-item v-for="(item, index) in list" :key="index" @touchmove.prevent.stop>
								<text @click="toLink(item)" class="beyond-hiding using-hidden" :style="{ color: value.textColor, fontSize: value.fontSize * 2 + 'rpx', fontWeight: value.fontWeight }">
									{{ item.title }}
								</text>
							</swiper-item>
						</swiper>
					</template>
				</view>
			</view>

			<view @touchmove.prevent.stop>
				<uni-popup ref="noticePopup" type="center">
					<view class="notice-popup">
						<view class="head-wrap" @click="closeNoticePopup">
							<text>公告</text>
							<text class="iconfont icon-close"></text>
						</view>
						<view class="content-wrap">{{ notice }}</view>
						<button type="primary" @click="closeNoticePopup">我知道了</button>
					</view>
				</uni-popup>
			</view>
		</view>
	</view>
</template>
<script>
	// 公告
	export default {
		name: 'diy-notice',
		props: {
			value: {
				type: Object,
				default: () => {
					return {};
				}
			}
		},
		data() {
			return {
				list: [],
				notice: '', // 当前点击的弹框内容
				marqueeWrapWidth: 0, // 容器宽度
				marqueeWidth: 0, // 公告内容累加宽度
				marqueeStyle: '', // 横向滚动样式
				marqueeAgainStyle: '', // 横向滚动复制样式
				time: 0, // 滚动完成时间
				delayTime: 1000 // 动画延迟时间
			};
		},
		watch: {
			// 组件刷新监听
			componentRefresh: function(nval) {
				if (this.value.sources == 'initial') this.getData();
			}
		},
		created() {},
		mounted() {
			// 数据源：公告系统
			if (this.value.sources == 'initial') {
				this.getData();
			} else {
				this.list = this.value.list;
				this.bindCrossSlipEvent();
			}
		},
		computed: {
			noticeWrapCss: function() {
				var obj = '';
				obj += 'background-color:' + this.value.componentBgColor + ';';
				if (this.value.componentAngle == 'round') {
					obj += 'border-top-left-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
				}
				return obj;
			}
		},
		methods: {
			getData() {
				var data = {
					page_size: 0
				};

				if (this.value.sources == 'initial') {
					data.page_size = this.value.count;
				}

				if (this.value.noticeIds.length) {
					data.id_arr = this.value.noticeIds.toString();
					data.page_size = 0;
				}
				this.$api.sendRequest({
					url: '/api/notice/page',
					data: data,
					success: res => {
						if (res.code == 0 && res.data) {
							this.list = res.data.list;
							this.bindCrossSlipEvent();
						}
					}
				});
			},
			toLink(item) {
				if (this.value.sources == 'initial') {
					this.$util.redirectTo('/pages_tool/notice/detail', {
						notice_id: item.id
					});
				} else if (!item) {
					this.$util.redirectTo('/pages_tool/notice/list');
				} else if (Object.keys(item.link).length > 1) {
					this.$util.diyRedirectTo(item.link);
				} else {
					// 如果不设置跳转链接，则点击弹框展示
					this.notice = item.title;
					this.$refs.noticePopup.open();
				}
			},
			closeNoticePopup() {
				this.$refs.noticePopup.close();
			},
			// 绑定横向滚动事件
			bindCrossSlipEvent() {
				if (this.value.scrollWay == 'horizontal') {
					setTimeout(() => {
						this.$nextTick(() => {
							uni.createSelectorQuery().in(this).select('.marquee-wrap').boundingClientRect(res => {
								this.marqueeWrapWidth = res.width;
								const query = uni.createSelectorQuery().in(this);
								query.select('.marquee').boundingClientRect(data => {
									this.marqueeWidth = data.width + 30; // 30px是间距
									this.time = Math.ceil(this.marqueeWidth * 10);

									if (this.marqueeWrapWidth > this.marqueeWidth) {
										this.marqueeStyle = `animation: none;`;
										this.marqueeAgainStyle = 'display:none;';
									} else {
										this.marqueeStyle = `
											width: ${this.marqueeWidth}px;
											animation-duration: ${this.time}ms;
											animation-delay: ${this.delayTime}ms;`;
										this.marqueeAgainStyle = `
											width: ${this.marqueeWidth}px;
											left: ${this.marqueeWidth}px;
											animation-duration: ${this.time}ms;
											animation-delay: ${this.delayTime}ms;`;
									}
								}).exec();
							}).exec();
						});
					});
				}
			}
		}
	};
</script>

<style lang="scss">
	.notice {
		height: 80rpx;
		position: relative;
		display: flex;
		align-items: center;
		overflow: hidden;
		padding: 20rpx 0 20rpx 20rpx;
		font-size: 70rpx;
		box-sizing: border-box;

		.notice-img {
			width: 44rpx;
			height: 40rpx;
		}

		.notice-xian {
			width: 1rpx;
			height: 26rpx;
			background-color: #e4e4e4;
			margin: 0 22rpx;
		}
	}

	.main-wrap {
		display: inline-block;
		width: calc(100% - 115rpx);
		position: relative;
	}

	swiper {
		height: 50rpx;
	}

	.beyond-hiding {
		display: inline-block;
		width: 100%;
		white-space: nowrap;
	}

	.notice-popup {
		padding: 0 30rpx 40rpx;
		background-color: #fff;

		.head-wrap {
			font-size: $font-size-toolbar;
			line-height: 100rpx;
			height: 100rpx;
			display: block;
			text-align: center;
			position: relative;
			border-bottom: 2rpx solid $color-line;
			margin-bottom: 20rpx;

			.iconfont {
				position: absolute;
				float: right;
				right: 0;
				font-size: $font-size-toolbar;
			}
		}

		.content-wrap {
			max-height: 600rpx;
			overflow-y: auto;
		}

		button {
			margin-top: 40rpx;
		}
	}

	.horizontal-wrap {
		height: 30px;
		line-height: 30px;
		position: relative;
		overflow: hidden;
		width: 100%;
	}

	.marquee-wrap {
		display: inline-block;
		width: 100%;
		height: 100%;
		vertical-align: middle;
		overflow: hidden;
		box-sizing: border-box;
		position: relative;
	}

	.marquee {
		display: flex;
		position: absolute;
		white-space: nowrap;
		animation: marquee 0s 0s linear infinite;

		text {
			margin-left: 40rpx;

			&:first-child {
				margin-left: 0;
			}
		}
	}

	@keyframes marquee {
		0% {
			transform: translateX(0);
		}

		100% {
			transform: translateX(-100%);
		}
	}
</style>