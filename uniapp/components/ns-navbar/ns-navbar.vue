<template>
	<view class="ns-navbar-wrap" :class="'style-' + data.navStyle">
		<!-- #ifndef MP-ALIPAY -->
		<view class="u-navbar" :style="{ backgroundColor: bgColor, paddingTop: navbarHeight + 'px' }">
			<view class="navbar-inner" :style="navbarInnerStyle">
				<view class="back-wrap" v-if="isBack && isBackShow" @tap="goBack">
					<text class="iconfont icon-back_light" :style="{ color: titleColor }"></text>
				</view>

				<view v-if="data.navStyle == 1" class="content-wrap" :class="[data.textImgPosLink, isBack && isBackShow ? 'have-back' : '']" @click="toLink(data.moreLink.wap_url)">
					<view class="title-wrap" :style="{ fontSize: '16px', color: data.textNavColor, textAlign: data.textImgPosLink }">
						{{ data.title }}
					</view>
				</view>

				<view v-if="data.navStyle == 2" class="content-wrap" @click="toLink(data.moreLink.wap_url)">
					<view class="title-wrap" :style="{ color: data.textNavColor }">
						<view>
							<image :src="$util.img(data.topNavImg)" mode="heightFix"></image>
						</view>
						<view :style="{ color: data.textNavColor }">{{ data.title }}</view>
					</view>
				</view>

				<view v-if="data.navStyle == 3" class="content-wrap">
					<view class="title-wrap" @click="toLink(data.moreLink.wap_url)">
						<image :src="$util.img(data.topNavImg)" mode="aspectFit"></image>
					</view>
					<view class="search" @click="$util.redirectTo('/pages_tool/goods/search')" :style="{ height: menuButtonInfo.height - 2 + 'px', lineHeight: menuButtonInfo.height - 2 + 'px' }">
						<text class="iconfont icon-sousuo3"></text>
						<text>请输入商品名称</text>
					</view>
					<view :style="{ 'width': capsuleWidth }"></view>
				</view>

				<view v-if="data.navStyle == 4" class="content-wrap" :class="{ 'have-back': isBack && isBackShow }" @click="chooseOtherStore()">
					<text class="iconfont icon-dizhi" :style="{ color: data.textNavColor }"></text>
					<view v-if="globalStoreInfo && globalStoreInfo.store_id" class="title-wrap" :style="{ color: data.textNavColor }">{{ globalStoreInfo.store_name }}</view>
					<view v-else class="title-wrap" :style="{ color: data.textNavColor }">定位中...</view>
					<text class="iconfont icon-right" :style="{ color: data.textNavColor }"></text>
					<view class="nearby-store-name" :style="{ color: data.textNavColor }">附近门店</view>
				</view>
			</view>
		</view>

		<!-- 解决fixed定位后导航栏塌陷的问题 -->
		<view class="u-navbar-placeholder" :style="{ width: '100%', paddingTop: placeholderHeight + 'px' }"></view>
		<!-- #endif -->
	</view>
</template>

<script>
// 获取系统状态栏的高度
let systemInfo = uni.getSystemInfoSync();
let menuButtonInfo = {};
// 如果是小程序，获取右上角胶囊的尺寸信息，避免导航栏右侧内容与胶囊重叠(支付宝小程序非本API，尚未兼容)
// #ifdef MP-WEIXIN || MP-BAIDU || MP-TOUTIAO || MP-QQ
menuButtonInfo = uni.getMenuButtonBoundingClientRect();
// #endif

// 自定义导航栏
export default {
	name: 'ns-navbar',
	props: {
		data: {
			type: Object,
			default() {
				return {};
			}
		},
		// 标题的颜色
		titleColor: {
			type: String,
			default: '#606266'
		},
		// 自定义返回逻辑
		customBack: {
			type: Function,
			default: null
		},
		scrollTop: {
			type: [String, Number],
			default: '0'
		},
		// 是否显示导航栏左边返回图标和辅助文字
		isBack: {
			type: Boolean,
			default: true
		}
	},
	data() {
		return {
			menuButtonInfo: menuButtonInfo,
			isBackShow: false,
			placeholderHeight: 0
		};
	},
	computed: {
		// 导航栏内部盒子的样式
		navbarInnerStyle() {
			let style = '';
			// 导航栏宽度，如果在小程序下，导航栏宽度为胶囊的左边到屏幕左边的距离
			style += 'height:' + menuButtonInfo.height * 2 + 'rpx;';
			return style;
		},
		// 转换字符数值为真正的数值
		navbarHeight() {
			// #ifdef APP-PLUS || H5
			return 25;
			// #endif
			// #ifdef MP
			// 小程序特别处理，让导航栏高度 = 胶囊高度 + 两倍胶囊顶部与状态栏底部的距离之差(相当于同时获得了导航栏底部与胶囊底部的距离)
			let height = menuButtonInfo.top;
			return height;
			// #endif
		},
		bgColor() {
			var color = '';
			if (this.data.topNavBg) {
				// 顶部透明
				color = 'transparent';
				let top = 0;

				if (this.data.navStyle == 4) {
					// #ifdef H5
					top = 15;
					// #endif
					// #ifdef MP
					top = this.navbarHeight - 25;
					// #endif
				}

				if (this.scrollTop > top) {
					color = this.data.topNavColor;
				} else {
					color = 'transparent';
				}
			} else {
				color = this.data.topNavColor;
			}
			return color;
		},
		capsuleWidth() {
			let width = `calc(100vw - ${this.menuButtonInfo.right}px + ${this.menuButtonInfo.width}px + 10px)`;
			return width;
		}
	},
	created(e) {
		var pages = getCurrentPages();
		if (pages.length > 1) {
			this.isBackShow = true;
		}
		this.navbarPlaceholderHeight();
	},
	mounted() {
		this.setModuleLocationFn();
	},
	methods: {
		toLink(val) {
			if (val) this.$util.redirectTo(val);
		},
		goBack() {
			// 如果自定义了点击返回按钮的函数，则执行，否则执行返回逻辑
			if (typeof this.customBack === 'function') {
				this.customBack();
			} else {
				uni.navigateBack();
			}
		},
		// 选择其他门店
		chooseOtherStore() {
			if (this.globalStoreConfig && this.globalStoreConfig.is_allow_change == 1) {
				this.$util.redirectTo('/pages_tool/store/list');
			} else if (this.globalStoreInfo) {
				// 禁止切换门店，进入门店详情
				this.$util.redirectTo('/pages_tool/store/detail', {
					store_id: this.globalStoreInfo.store_id
				});
			}
		},
		navbarPlaceholderHeight() {
			setTimeout(() => {
				const query = uni.createSelectorQuery().in(this);
				query.select('.ns-navbar-wrap .u-navbar').boundingClientRect(data => {
					// 获取公告自身高度
					this.placeholderHeight = data.height;
				}).exec();
			});
		},
		// 向vuex中的diyIndexPositionObj增加公告导航组件定位位置
		setModuleLocationFn() {
			const query = uni.createSelectorQuery().in(this);
			query.select('.ns-navbar-wrap .u-navbar').boundingClientRect(data => {
				let diyIndexPage = {
					originalVal: data.height || 0, //自身高度 px
					currVal: 0 //定位高度
				};
				this.$store.commit('setDiyGroupPositionObj', {
					'nsNavbar': diyIndexPage
				});
			}).exec();
		}
	}
};
</script>

<style scoped lang="scss">
/* #ifdef H5 */
.style-1,
.style-2,
.style-3 {
	display: none;
}

/* #endif */

.u-navbar {
	width: 100%;
	transition: background 0.3s;
	position: fixed;
	left: 0;
	right: 0;
	top: 0;
	z-index: 991;
}

.navbar-inner {
	display: flex;
	justify-content: space-between;
	position: relative;
	align-items: center;
	padding-bottom: 20rpx;
	/* #ifdef H5 */
	padding-bottom: 40rpx;
	/* #endif */
}

.back-wrap {
	padding: 0 14rpx 0 24rpx;

	.iconfont {
		font-size: 44rpx;
	}
}

.content-wrap {
	display: flex;
	align-items: center;
	justify-content: flex-start;
	flex: 1;
	position: absolute;
	left: 0;
	right: 0;
	top: 0;
	height: 60rpx;
	text-align: center;
	flex-shrink: 0;
}

.title-wrap {
	line-height: 1;
	flex: 1;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	font-size: 32rpx;
	color: #000000;
}

.ns-navbar-wrap {

	&.style-1 {
		.content-wrap {
			.title-wrap {
				font-size: 27rpx;
			}

			&.left {
				left: 30rpx;
			}

			&.center {
				left: 0;
				padding-right: 0;
			}

			&.have-back {
				left: 90rpx;
				padding-right: 200rpx;
			}
		}
	}

	&.style-2 {
		.content-wrap {
			.title-wrap {
				display: flex;
				align-items: center;
				text-align: left;

				>view {
					height: 56rpx;
					line-height: 56rpx;
					max-width: 300rpx;
					margin-left: 30rpx;
					font-size: 27rpx;

					image {
						width: 100%;
						height: 100%;
					}

					&:last-child {
						overflow: hidden; //超出的文本隐藏
						text-overflow: ellipsis; //用省略号显示
						white-space: nowrap; //不换行
						flex: 1;
					}
				}
			}
		}
	}

	&.style-3 {
		.content-wrap {
			.title-wrap {
				height: 60rpx;
				width: 170rpx;
				max-width: 170rpx;
				margin-left: 30rpx;
				flex: initial;
				text-align: center;
				margin-right: 10rpx;

				image {
					height: 100%;
					width: 100%;
				}
			}

			.search {
				flex: 1;
				padding: 0 20rpx;
				background-color: #fff;
				text-align: left;
				border-radius: 60rpx;
				height: 60rpx;
				line-height: 60rpx;
				border: 1px solid #eeeeee;
				color: rgb(102, 102, 102);
				display: flex;
				align-items: center;
				margin-right: 10rpx;

				.iconfont {
					color: #909399;
					font-size: 32rpx;
					margin-right: 10rpx;
				}
			}
		}
	}

	&.style-4 {
		.icon-dizhi{
			font-size: 28rpx;
		}
		.content-wrap {
			top: initial;
			text-align: left;
			padding-left: 30rpx;

			&.have-back {
				left: 60rpx;
				right: 190rpx;
			}

			.title-wrap {
				flex: none;
				margin: 0 10rpx;
				max-width: 360rpx;
				font-size: 27rpx;
			}

			.icon-right {
				font-size: 24rpx;
			}

			.nearby-store-name {
				margin: 0 10rpx;
				background: rgba(0, 0, 0, .2);
				font-size: 22rpx;
				border-radius: 40rpx;
				padding: 10rpx 20rpx;
				line-height: 1;
			}
		}
	}
}
</style>