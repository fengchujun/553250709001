<template>
	<!-- 悬浮按钮 -->
	<view v-if="pageCount == 1 || need" class="fixed-box" :style="{ height: fixBtnShow ? '330rpx' : '120rpx' }">
		<view class="btn-item" v-if="fixBtnShow" @click="$util.redirectTo('/pages/index/index')">
			<text class="iconfont icon-shouye1"></text>
			<view>首页</view>
		</view>
		<view class="btn-item" v-if="fixBtnShow" @click="$util.redirectTo('/pages/member/index')">
			<text class="iconfont icon-yonghu"></text>
			<view>我的</view>
		</view>

		<view class="btn-item icon-xiala" v-if="fixBtnShow" @click="fixBtnShow ? (fixBtnShow = false) : (fixBtnShow = true)">
			<text class="iconfont icon-unfold"></text>
		</view>
		<view class="btn-item switch" v-else :class="{ show: fixBtnShow }" @click="fixBtnShow ? (fixBtnShow = false) : (fixBtnShow = true)">
			<view>快捷</view>
			<view>导航</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'hover-nav',
		props: {
			need: {
				type: Boolean,
				default: false
			},
		},
		data() {
			return {
				pageCount: 0,
				fixBtnShow: false
			};
		},
		created() {
			this.pageCount = getCurrentPages().length;
		},
		methods: {}
	};
</script>

<style lang="scss">
	.container-box {
		width: 100%;

		.item-wrap {
			border-radius: 10rpx;

			.image-box {
				border-radius: 10rpx;
			}

			image {
				width: 100%;
				height: auto;
				border-radius: 10rpx;
				will-change: transform;
			}
		}
	}

	//悬浮按钮
	.fixed-box {
		position: fixed;
		right: 20rpx;
		bottom: 300rpx;
		z-index: 10;
		background: #fff;
		box-shadow: 2rpx 2rpx 22rpx rgba(0, 0, 0, 0.3);
		border-radius: 120rpx;
		padding: 20rpx 0;
		display: flex;
		justify-content: center;
		flex-direction: column;
		width: 120rpx;
		box-sizing: border-box;
		transition: 0.3s;
		overflow: hidden;

		.btn-item {
			display: flex;
			justify-content: center;
			text-align: center;
			flex-direction: column;
			line-height: 1;
			margin: 14rpx 0;
			transition: 0.1s;

			text {
				font-size: 44rpx;
				font-weight: bold;
			}

			view {
				font-size: 26rpx;
				font-weight: bold;
			}

			&.show {
				transform: rotate(180deg);
			}

			&.switch {}

			&.icon-xiala {
				margin: 0;
				margin-top: 0.1rpx;
			}
		}
	}
</style>