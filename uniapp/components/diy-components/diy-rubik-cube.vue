<template>
	<view :style="value.pageStyle">
		<!-- 自定义 -->
		<view v-if="value.mode == 'custom-rubik-cube'">
			<view style="position: relative;"><!-- <rich-text :nodes="customHtml"></rich-text> -->
				<ns-mp-html :content="customHtml"></ns-mp-html>
			</view>
		</view>
		<view v-else :class="['rubik-cube', value.mode]" :style="rubikCubeWrapCss">
			<!-- 1左2右 -->
			<template v-if="value.mode == 'row1-lt-of2-rt'">
				<view class="template-left">
					<view :class="['item', value.mode]" @click="$util.diyRedirectTo(value.list[0].link)" :style="{ marginRight: value.imageGap * 2 + 'rpx', width: list[0].imgWidth, height: list[0].imgHeight + 'px' }">
						<image :src="$util.img(value.list[0].imageUrl)" :mode="list[0].imageMode || 'scaleToFill'" :style="list[0].pageItemStyle" :show-menu-by-longpress="true"/>
					</view>
				</view>

				<view class="template-right">
					<template v-for="(item, index) in list">
						<template v-if="index > 0">
							<view :key="index" :class="['item', value.mode]" @click="$util.diyRedirectTo(item.link)" :style="{ marginBottom: value.imageGap * 2 + 'rpx', width: item.imgWidth, height: item.imgHeight + 'px' }">
								<image :src="$util.img(item.imageUrl)" :mode="item.imageMode || 'scaleToFill'" :style="item.pageItemStyle" :show-menu-by-longpress="true"/>
							</view>
						</template>
					</template>
				</view>
			</template>

			<!-- 1左3右 -->
			<template v-else-if="value.mode == 'row1-lt-of1-tp-of2-bm'">
				<view class="template-left">
					<view :class="['item', value.mode]" :style="{ marginRight: value.imageGap * 2 + 'rpx', width: list[0].imgWidth, height: list[0].imgHeight + 'px' }" @click="$util.diyRedirectTo(value.list[0].link)">
						<image :src="$util.img(value.list[0].imageUrl)" :mode="list[0].imageMode || 'scaleToFill'" :style="list[0].pageItemStyle" :show-menu-by-longpress="true"/>
					</view>
				</view>

				<view class="template-right">
					<view :class="['item', value.mode]" :style="{ marginBottom: value.imageGap * 2 + 'rpx', width: list[1].imgWidth, height: list[1].imgHeight + 'px' }" @click="$util.diyRedirectTo(value.list[1].link)">
						<image :src="$util.img(value.list[1].imageUrl)" :mode="list[1].imageMode || 'scaleToFill'" :style="list[1].pageItemStyle" :show-menu-by-longpress="true"/>
					</view>
					<view class="template-bottom">
						<template v-for="(item, index) in list">
							<template v-if="index > 1">
								<view :key="index" :class="['item', value.mode]" @click="$util.diyRedirectTo(item.link)"
									:style="{
										marginRight: value.imageGap * 2 + 'rpx',
										width: item.imgWidth,
										height: item.imgHeight + 'px'
									}">
									<image :src="$util.img(item.imageUrl)" :mode="item.imageMode || 'scaleToFill'" :style="item.pageItemStyle" :show-menu-by-longpress="true"/>
								</view>
							</template>
						</template>
					</view>
				</view>
			</template>

			<template v-else>
				<view :class="['item', value.mode]" v-for="(item, index) in list" :key="index"
					@click="$util.diyRedirectTo(item.link)"
					:style="{ marginRight: value.imageGap * 2 + 'rpx', marginBottom: value.imageGap * 2 + 'rpx', width: item.widthStyle, height: item.imgHeight + 'px' }">
					<image :src="$util.img(item.imageUrl)" :mode="item.imageMode || 'scaleToFill'" :style="item.pageItemStyle" :show-menu-by-longpress="true"/>
				</view>
			</template>
		</view>
	</view>
</template>

<script>
	// 魔方、橱窗
	import htmlParser from '@/common/js/html-parser';
	export default {
		name: 'diy-rubik-cube',
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
				customHtml: ''
			};
		},
		created() {
			this.init()
		},
		watch: {
			// 组件刷新监听
			componentRefresh: function(nval) {
				this.init()
			}
		},
		computed: {
			list() {
				var arr = JSON.parse(JSON.stringify(this.value.list));
				arr.forEach((item, index) => {
					item.pageItemStyle = this.countBorderRadius(this.value.mode, index);
				});
				return arr;
			},
			rubikCubeWrapCss() {
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
			calcWidth(width){
				return width.indexOf('px') != -1 ? width.substring(0,width.indexOf('px')) : width;
			},
			init() {
				if (this.value.mode == 'custom-rubik-cube') {
					this.value.diyHtml = this.value.diyHtml.replace(/&quot;/g, '"');
					// this.customHtml = htmlParser(this.value.diyHtml);
					this.customHtml = this.value.diyHtml;
				} else {
					var singleRow = {
						'row1-of2': {
							ratio: 2,
							width: 'calc((100% - ' + uni.upx2px(this.value.imageGap * 2) + 'px) / 2)'
						},
						'row1-of3': {
							ratio: 3,
							width: 'calc((100% - ' + uni.upx2px(this.value.imageGap * 4) + 'px) / 3)'
						},
						'row1-of4': {
							ratio: 4,
							width: 'calc((100% - ' + uni.upx2px(this.value.imageGap * 6) + 'px) / 4)'
						}
					};
					if (singleRow[this.value.mode]) {
						this.calcSingleRow(singleRow[this.value.mode]);
					} else if (this.value.mode == 'row2-lt-of2-rt') {
						this.calcFourSquare();
					} else if (this.value.mode == 'row1-lt-of2-rt') {
						this.calcRowOneLeftOfTwoRight();
					} else if (this.value.mode == 'row1-tp-of2-bm') {
						this.calcRowOneTopOfTwoBottom();
					} else if (this.value.mode == 'row1-lt-of1-tp-of2-bm') {
						this.calcRowOneLeftOfOneTopOfTwoBottom();
					}
				}
				this.$forceUpdate()
			},
			/**
			 * 魔方：单行多个，平分宽度
			 * 公式：
			 * 宽度：屏幕宽度/2，示例：375/2=187.5
			 * 比例：原图高/原图宽，示例：322/690=0.46
			 * 高度：宽度*比例，示例：187.5*0.46=86.25
			 */
			calcSingleRow(params) {
				uni.getSystemInfo({
					success: res => {
						let maxHeight = 0;

						this.list.forEach((item, index) => {
							
							var ratio = item.imgHeight / this.calcWidth(item.imgWidth);
							let width = res.windowWidth - uni.upx2px(this.value.margin.both * 2); // 减去左右间距
							if (this.value.imageGap > 0) {
								width -= uni.upx2px(params.ratio * this.value.imageGap * 2); // 减去间隙
							}
							item.imgWidth = width / params.ratio;
							item.imgHeight = item.imgWidth * ratio;

							if (maxHeight == 0 || maxHeight < item.imgHeight) maxHeight = item.imgHeight;
						})

						this.list.forEach((item, index) => {
							item.widthStyle = params.width;
							item.imgHeight = maxHeight;
						});
					}
				})
			},
			/**
			 * 魔方：四方型，各占50%
			 */
			calcFourSquare() {
				uni.getSystemInfo({
					success: res => {
						let maxHeightFirst = 0;
						let maxHeightTwo = 0;
						this.list.forEach((item, index) => {
							var calc_width = item.imgWidth.indexOf('calc') != -1 ? ( res.windowWidth - 24 ) * ( parseInt( '100%' ) - parseInt( uni.upx2px(this.value.imageGap * 2) + 'px' ) ) / 2 / 100 : item.imgWidth;
							var ratio = item.imgHeight / calc_width ;
							item.imgWidth = res.windowWidth;
							item.imgWidth -= uni.upx2px(this.value.margin.both * 4);
							if (this.value.imageGap > 0) {
								item.imgWidth -= uni.upx2px(this.value.imageGap * 2);
							}
							item.imgWidth = item.imgWidth / 2;
							item.imgHeight = item.imgWidth * ratio;
							// 获取每行最大高度
							if (index <= 1) {
								if (maxHeightFirst == 0 || maxHeightFirst < item.imgHeight) {
									maxHeightFirst = item.imgHeight;
								}
							} else if (index > 1) {
								if (maxHeightTwo == 0 || maxHeightTwo < item.imgHeight) {
									maxHeightTwo = item.imgHeight;
								}
							}
						});
						this.list.forEach((item, index) => {
							item.imgWidth = 'calc((100% - ' + uni.upx2px(this.value.imageGap * 2) + 'px) / 2)';
							item.widthStyle = item.imgWidth;
							if (index <= 1) {
								item.imgHeight = maxHeightFirst;
							} else if (index > 1) {
								item.imgHeight = maxHeightTwo;
							}
						});
					}
				});
			},
			/**
			 * 魔方：1左2右
			 */
			calcRowOneLeftOfTwoRight() {
				let rightHeight = 0; // 右侧两图平分高度
				let divide = 'left'; // 划分规则，left：左，right：右
				if (this.list[1].imgWidth === this.list[2].imgWidth) divide = 'right';
				uni.getSystemInfo({
					success: res => {
						let leftWidth = 0; // 保存左侧图片的数值宽度
						this.list.forEach((item, index) => {
							if (index == 0) {
								var ratio = item.imgHeight / this.calcWidth(item.imgWidth); // 获取左图的尺寸比例
								item.imgWidth = res.windowWidth - uni.upx2px(this.value.margin.both * 4) - uni.upx2px(this.value.imageGap * 2);
								item.imgWidth = item.imgWidth / 2;
								leftWidth = item.imgWidth; // 保存数值宽度
								item.imgHeight = item.imgWidth * ratio;
								rightHeight = (item.imgHeight - uni.upx2px(this.value.imageGap * 2)) / 2;
								item.imgWidth += 'px';
							} else {
								item.imgWidth = leftWidth + 'px'; // 使用保存的数值宽度
								item.imgHeight = rightHeight;
							}
						});
					}
				});
			},
			/**
			 * 魔方：1上2下
			 */
			
			calcRowOneTopOfTwoBottom() {
				var maxHeight = 0;
				uni.getSystemInfo({
					success: res => {
						this.list.forEach((item, index) => {
							var ratio = item.imgHeight / this.calcWidth(item.imgWidth); // 获取左图的尺寸比例
							if (index == 0) {
								item.imgWidth = res.windowWidth - uni.upx2px(this.value.margin.both * 4);
							} else if (index > 0) {
								item.imgWidth = res.windowWidth - uni.upx2px(this.value.margin.both * 4) - uni.upx2px(this.value.imageGap * 2);
								item.imgWidth = item.imgWidth / 2;
							}

							item.imgHeight = item.imgWidth * ratio;

							// 获取最大高度
							if (index > 0 && (maxHeight == 0 || maxHeight < item.imgHeight))
								maxHeight = item.imgHeight;

						});
						this.list.forEach((item, index) => {
							item.imgWidth += 'px';
							item.widthStyle = item.imgWidth;
							if (index > 0) item.imgHeight = maxHeight;
						});
					}
				});
			},
			/**
			 * 魔方：1左3右
			 */
			calcRowOneLeftOfOneTopOfTwoBottom() {
				uni.getSystemInfo({
					success: res => {
						let leftWidth = 0; // 保存左侧图片的数值宽度
						let leftHeight = 0; // 保存左侧图片的高度
						let rightTopHeight = 0; // 保存右上图片的高度
						
						this.list.forEach((item, index) => {
							// 左图
							if (index == 0) {
								var ratio = item.imgHeight / this.calcWidth(item.imgWidth); // 获取左图的尺寸比例
								item.imgWidth = res.windowWidth - uni.upx2px(this.value.margin.both * 4) - uni.upx2px(this.value.imageGap * 2);
								item.imgWidth = item.imgWidth / 2;
								leftWidth = item.imgWidth; // 保存数值宽度
								item.imgHeight = item.imgWidth * ratio;
								leftHeight = item.imgHeight; // 保存左图高度
							} else if (index == 1) {
								item.imgWidth = leftWidth; // 使用保存的数值宽度
								item.imgHeight = (leftHeight - uni.upx2px(this.value.imageGap * 2)) / 2;
								rightTopHeight = item.imgHeight; // 保存右上图片高度
							} else if (index > 1) {
								item.imgWidth = (leftWidth - uni.upx2px(this.value.imageGap * 2)) / 2;
								item.imgHeight = rightTopHeight; // 使用保存的右上图片高度
							}
						});

						this.list.forEach((item, index) => {
							item.imgWidth += 'px';
						});
					}
				});
			},
			countBorderRadius(type, index) {
				var obj = '';
				if (this.value.elementAngle == 'right') {
					return obj;
				}
				var defaultData = {
					'row1-lt-of2-rt': [
						['border-top-right-radius', 'border-bottom-right-radius'],
						['border-top-left-radius', 'border-bottom-left-radius', 'border-bottom-right-radius'],
						['border-top-left-radius', 'border-bottom-left-radius', 'border-top-right-radius']
					],
					'row1-lt-of1-tp-of2-bm': [
						['border-top-right-radius', 'border-bottom-right-radius'],
						['border-top-left-radius', 'border-bottom-left-radius', 'border-bottom-right-radius'],
						['border-radius'],
						['border-top-left-radius', 'border-bottom-left-radius', 'border-top-right-radius']
					],
					'row1-tp-of2-bm': [
						['border-bottom-left-radius', 'border-bottom-right-radius'],
						['border-top-left-radius', 'border-bottom-right-radius', 'border-top-right-radius'],
						['border-top-left-radius', 'border-bottom-left-radius', 'border-top-right-radius']
					],
					'row2-lt-of2-rt': [
						['border-top-right-radius', 'border-bottom-left-radius', 'border-bottom-right-radius'],
						['border-top-left-radius', 'border-bottom-right-radius', 'border-bottom-left-radius'],
						['border-top-left-radius', 'border-bottom-right-radius', 'border-top-right-radius'],
						['border-top-left-radius', 'border-bottom-left-radius', 'border-top-right-radius']
					],
					'row1-of4': [
						['border-top-right-radius', 'border-bottom-right-radius'],
						['border-radius'],
						['border-radius'],
						['border-top-left-radius', 'border-bottom-left-radius']
					],
					'row1-of3': [
						['border-top-right-radius', 'border-bottom-right-radius'],
						['border-radius'],
						['border-top-left-radius', 'border-bottom-left-radius']
					],
					'row1-of2': [
						['border-top-right-radius', 'border-bottom-right-radius'],
						['border-top-left-radius', 'border-bottom-left-radius']
					]
				};

				defaultData[type][index].forEach((item, index) => {
					// obj += item + ':' + this.value.aroundRadius * 2 + 'rpx;';
					obj += 'border-top-left-radius:' + this.value.topElementAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.value.topElementAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.value.bottomElementAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.value.bottomElementAroundRadius * 2 + 'rpx;';
				});
				return obj;
			}
		}
	};
</script>
<style lang="scss">
	.rubik-cube {
		overflow: hidden;
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
	}

	.rubik-cube .item {
		text-align: center;
		line-height: 0;
		overflow: hidden;
	}

	.rubik-cube .item image {
		width: 100%;
		max-width: 100%;
		height: 100%;
	}

	// 一行两个
	.rubik-cube .item.row1-of2 {
		box-sizing: border-box;
		margin-top: 0 !important;
		margin-bottom: 0 !important;
	}

	.rubik-cube .item.row1-of2:nth-child(1) {
		margin-left: 0 !important;
	}

	.rubik-cube .item.row1-of2:nth-child(2) {
		margin-right: 0 !important;
	}

	// 一行三个
	.rubik-cube .item.row1-of3 {
		box-sizing: border-box;
		margin-top: 0 !important;
		margin-bottom: 0 !important;
	}

	.rubik-cube .item.row1-of3:nth-child(1) {
		margin-left: 0 !important;
	}

	.rubik-cube .item.row1-of3:nth-child(3) {
		margin-right: 0 !important;
	}

	// 一行四个
	.rubik-cube .item.row1-of4 {
		box-sizing: border-box;
		margin-top: 0 !important;
		margin-bottom: 0 !important;
	}

	.rubik-cube .item.row1-of4:nth-child(1) {
		margin-left: 0 !important;
	}

	.rubik-cube .item.row1-of4:nth-child(4) {
		margin-right: 0 !important;
	}

	// 两左两右
	.rubik-cube .item.row2-lt-of2-rt {
		// width: 50%;
		display: inline-block;
		box-sizing: border-box;
	}

	.rubik-cube .item.row2-lt-of2-rt:nth-child(1) {
		margin-left: 0 !important;
		margin-top: 0 !important;
	}

	.rubik-cube .item.row2-lt-of2-rt:nth-child(2) {
		margin-right: 0 !important;
		margin-top: 0 !important;
	}

	.rubik-cube .item.row2-lt-of2-rt:nth-child(3) {
		margin-left: 0 !important;
		margin-bottom: 0 !important;
	}

	.rubik-cube .item.row2-lt-of2-rt:nth-child(4) {
		margin-right: 0 !important;
		margin-bottom: 0 !important;
	}

	// 一左两右
	.rubik-cube .template-left,
	.rubik-cube .template-right {
		// width: 50%;
		box-sizing: border-box;
	}

	.rubik-cube .template-left .item.row1-lt-of2-rt:nth-child(1) {
		margin-bottom: 0;
	}

	.rubik-cube .template-right .item.row1-lt-of2-rt:nth-child(2) {
		margin-bottom: 0 !important;
	}

	.rubik-cube.row1-lt-of2-rt .template-right {
		display: flex;
		flex-direction: column;
		justify-content: space-between;
	}

	// 一上两下
	.rubik-cube .item.row1-tp-of2-bm:nth-child(1) {
		width: 100%;
		box-sizing: border-box;
		margin-top: 0 !important;
		margin-left: 0 !important;
		margin-right: 0 !important;
	}

	.rubik-cube .item.row1-tp-of2-bm:nth-child(2) {
		// width: 50%;
		box-sizing: border-box;
		margin-left: 0 !important;
		margin-bottom: 0 !important;
	}

	.rubik-cube .item.row1-tp-of2-bm:nth-child(3) {
		// width: 50%;
		box-sizing: border-box;
		margin-right: 0 !important;
		margin-bottom: 0 !important;
	}

	// 一左三右
	.rubik-cube .template-left .item.row1-lt-of1-tp-of2-bm {
		width: 100%;
		box-sizing: border-box;
	}

	.rubik-cube .template-bottom {
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.rubik-cube .template-bottom .item:nth-child(2) {
		margin-right: 0 !important;
	}
</style>