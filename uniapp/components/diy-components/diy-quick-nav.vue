<template>
	<view :style="value.pageStyle">
		<view :style="componentStyle">
			<scroll-view class="quick-nav" scroll-x="true">
				<!-- #ifdef MP -->
				<view class="uni-scroll-view-content">
				<!-- #endif -->
					<view
						class="quick-nav-item"
						v-for="(item, index) in value.list"
						:key="index"
						@click="redirectTo(item.link)"
						:style="{ background: 'linear-gradient(to right,' + item.bgColorStart ? item.bgColorStart : '' + ',' + item.bgColorEnd ? item.bgColorEnd : '' + ')' }"
					>
						<view class="quick-img" v-if="item.imageUrl || item.icon">
							<image v-if="item.iconType == 'img'" :src="$util.img(item.imageUrl) || $util.img('public/uniapp/default_img/goods.png')" mode="heightFix" :show-menu-by-longpress="true"></image>
							<diy-icon v-if="item.iconType == 'icon'" :icon="item.icon" :value="item.style ? item.style : null" :style="{ fontSize: '60rpx' }"></diy-icon>
						</view>
						<text class="quick-text" :style="{ color: item.textColor }">{{ item.title }}</text>
					</view>

				<!-- #ifdef MP -->
				</view>
				<!-- #endif -->
			</scroll-view>
			<ns-login ref="login"></ns-login>
		</view>
	</view>
</template>

<script>
export default {
	name: 'diy-quick-nav',
	props: {
		value: {
			type: Object,
			default: () => {
				return {};
			}
		}
	},
	data() {
		return {};
	},
	created() {},
	watch: {
		// 组件刷新监听
		componentRefresh: function(nval) {}
	},
	computed: {
		componentStyle() {
			var css = '';
			css += 'background-color:' + this.value.componentBgColor + ';';
			if (this.value.componentAngle == 'round') {
				css += 'border-top-left-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
				css += 'border-top-right-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
				css += 'border-bottom-left-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
				css += 'border-bottom-right-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
			}
			css += 'box-shadow:' + (this.value.ornament.type == 'shadow' ? '0 0 10rpx ' + this.value.ornament.color : '') + ';';
			css += 'border:' + (this.value.ornament.type == 'stroke' ? '2rpx solid ' + this.value.ornament.color : '') + ';';
			return css;
		}
	},
	methods: {
		redirectTo(link) {
			if (link.wap_url) {
				if (this.$util.getCurrRoute() == 'pages/member/index' && !this.storeToken) {
					this.$refs.login.open(link.wap_url);
					return;
				}
			}
			this.$util.diyRedirectTo(link);
		}
	}
};
</script>
<style>
.quick-nav >>> .uni-scroll-view-content {
	display: flex;
}
</style>
<style lang="scss">
.quick-nav {
	.quick-nav-item {
		display: flex;
		align-items: center;
		padding: 0 18rpx;
		box-sizing: border-box;
		flex-shrink: 0;
		border-radius: 40rpx;
		margin-right: 20rpx;
		height: 48rpx;
		&:first-of-type{
			padding-left: 12rpx;
		}
		&:last-child {
			margin-right: 0;
		}

		.quick-img {
			margin-right: 6rpx;
			height: 30rpx;
			line-height: 1;
			image {
				width: 30rpx;
				height: 30rpx;
			}
		}

		.quick-text {
			white-space: nowrap;
			text-overflow: ellipsis;
			overflow: hidden;
			width: 100%;
			font-size: 24rpx;
			line-height: 1;
		}
	}
}
</style>
