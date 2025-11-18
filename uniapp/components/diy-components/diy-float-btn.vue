<template>
	<view :style="value.pageStyle">
		<view class="float-btn" :class="{ left_top: value.bottomPosition == 1, right_top: value.bottomPosition == 2, left_bottom: value.bottomPosition == 3, right_bottom: value.bottomPosition == 4 }" :style="style">
			<block v-for="(item, index) in value.list" :key="index">
				<view class="button-box" @click="$util.diyRedirectTo(item.link)" :style="{ width: value.imageSize + 'px', height: value.imageSize + 'px', fontSize: value.imageSize + 'px' }">
					<image v-if="!item.iconType || item.iconType == 'img'" :src="$util.img(item.imageUrl)" mode="aspectFit" :show-menu-by-longpress="true"/>
					<diy-icon v-else-if="item.iconType && item.iconType == 'icon'" :icon="item.icon" :value="item.style ? item.style : null"></diy-icon>
				</view>
			</block>
		</view>
	</view>
</template>

<script>
	// 获取系统状态栏的高度
	let systemInfo = uni.getSystemInfoSync();
	export default {
		name: 'diy-float-btn',
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
				navHeight: 0,
				statusBarHeight: systemInfo.statusBarHeight
			};
		},
		created() {},
		watch: {
			// 组件刷新监听
			componentRefresh: function(nval) {}
		},
		methods: {},
		computed: {
			style() {
				let style = {},
					height = 54;
				// #ifdef MP
				height = systemInfo.platform == 'ios' ? 54 : 58;
				// #endif
				switch (parseInt(this.value.bottomPosition)) {
					case 1:
						style.top = (this.navHeight + this.statusBarHeight + parseInt(this.value.btnBottom)) * 2 + 'rpx';
						break;
					case 2:
						style.top = (this.navHeight + this.statusBarHeight + parseInt(this.value.btnBottom)) * 2 + 'rpx';
						break;
					case 3:
						style.bottom = (100 + parseInt(this.value.btnBottom)) * 2 + 'rpx';
						break;
					case 4:
						style.bottom = (100 + parseInt(this.value.btnBottom)) * 2 + 'rpx';
						break;
				}
				return this.$util.objToStyle(style);
			}
		}
	};
</script>

<style lang="scss">
	.float-btn {
		position: fixed;
		bottom: 20%;
		right: 40rpx;
		z-index: 990;

		&.left_top {
			top: 100rpx;
			left: 30rpx;
		}

		&.right_top {
			top: 100rpx;
			right: 30rpx;
		}

		&.left_bottom {
			bottom: 160rpx;
			left: 30rpx;
			padding-bottom: constant(safe-area-inset-bottom);
			/*兼容 IOS<11.2*/
			padding-bottom: env(safe-area-inset-bottom);
			/*兼容 IOS>11.2*/
		}

		&.right_bottom {
			bottom: 160rpx;
			right: 30rpx;
			padding-bottom: constant(safe-area-inset-bottom);
			/*兼容 IOS<11.2*/
			padding-bottom: env(safe-area-inset-bottom);
			/*兼容 IOS>11.2*/
		}

		.button-box {
			margin-bottom: 20rpx;

			&:last-child {
				margin-bottom: 0;
			}

			image {
				width: 100%;
				height: 100%;
			}
		}
	}
</style>