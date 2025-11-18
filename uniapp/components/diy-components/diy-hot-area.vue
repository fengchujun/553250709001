<template>
	<view :style="value.pageStyle">
	<view :style="hotAreaWarp" class="hot-area-box">
		<view class="simple-graph-wrap">
			<image :style="{ height: value.imgHeight }" :src="$util.img(value.imageUrl)" mode="widthFix" :show-menu-by-longpress="true"/>
			<!-- 热区功能 -->
			<view class="heat-map" v-for="(mapItem, mapIndex) in value.heatMapData" :key="mapIndex" :style="{
					width: mapItem.width + '%',
					height: mapItem.height + '%',
					left: mapItem.left + '%',
					top: mapItem.top + '%'
				}" @click.stop="$util.diyRedirectTo(mapItem.link)"></view>
		</view>
	</view>
	</view>
</template>

<script>
	export default {
		name: 'diy-hot-area',
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
			hotAreaWarp: function() {
				var obj = 'background-color:' + this.value.componentBgColor + ';';
				if (this.value.componentAngle == 'round') {
					obj += 'border-top-left-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
				}
				return obj;
			}
		},
		methods: {}
	};
</script>

<style lang="scss" scoped>
	.hot-area-box {
		position: relative;
		width: 100%;
		overflow: hidden;
		box-sizing: border-box;
	}

	.simple-graph-wrap {
		line-height: 0;
		overflow: hidden;
		position: relative;

		image {
			width: 100%;
		}

		.heat-map {
			position: absolute;
		}
	}
</style>