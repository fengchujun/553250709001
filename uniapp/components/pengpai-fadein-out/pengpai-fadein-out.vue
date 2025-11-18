<template>
	<view v-if="isShow">
		<view ref="ani" :animation="animationData" class="message" :style="{ top: top + 'px', left: left + 'px' }" v-if="show">
			<view class="round bg-gradual-orange flex justify-start shadow" style="padding: 3px;">
				<view class="cu-avatar cu-a-sm round" :style="{ backgroundImage: 'url(' + $util.img(penpaiData.img) + ')' }">
					<!-- #ifdef APP-NVUE -->
					<!-- <image :src="penpaiData.img" class="avatarimg"></image> -->
					<!-- #endif -->
				</view>
				<view class="padding-lr-sm flex align-center">
					<text class="text-sm">{{ penpaiData.title }}</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
// #ifdef APP-NVUE
const animation = uni.requireNativePlugin('animation');
// #endif
export default {
	name: 'pengpai-fadein-out',
	props: {
		//持续时间
		duration: {
			type: Number,
			default: 3000
		},
		//停留时间
		wait: {
			type: Number,
			default: 3500
		},
		//顶部距离px
		top: {
			type: Number,
			default: 350
		},
		//左边距离px
		left: {
			type: Number,
			default: 10
		},
		//动画半径
		radius: {
			type: Number,
			default: 30
		},
		//数据
		info: {
			type: [Array, Object],
			default: () => {
				return [];
			}
		}
	},
	data() {
		return {
			animationData: {},
			animationNumber: {},
			show: true,
			index: 0,
			penpaiData: {},
			timeIndex: 0
		};
	},
	computed:{
		isShow(){
			return this.penpaiData && Object.keys(this.penpaiData).length;
		}
	},
	mounted() {
		this.initData();
	},
	methods: {
		initData() {
			// 初始化执行第一次
			this.penpaiData = this.info[this.index];
			this.donghua();
			// 开启时间函数，轮询推值
			clearInterval(this.timeIndex);
			this.timeIndex = setInterval(() => {
				if (this.index == this.info.length - 1) {
					this.index = 0;
				} else {
					this.index++;
				}
				this.penpaiData = this.info[this.index];
				// 执行动画
				this.donghua();
			}, this.duration + this.wait);
		},
		donghua() {
			//进入
			// #ifndef APP-NVUE
			this.animationData = uni
				.createAnimation({
					duration: this.duration / 2,
					timingFunction: 'ease'
				})
				.top(this.top - this.radius)
				.opacity(0.9)
				.step()
				.export();

			// #endif

			// #ifdef APP-NVUE
			if (!this.$refs['ani']) return;
			animation.transition(this.$refs['ani'].ref, {
				styles: {
					transform: `translateY(-${this.radius / 2}px)`,
					opacity: 1
				},
				duration: this.duration / 2,
				timingFunction: 'linear',
				needLayout: false,
				delay: 0
			});
			// #endif

			//停留
			setTimeout(() => {
				//消失
				// #ifndef APP-NVUE
				this.animationData = uni.createAnimation({
					duration: this.duration / 2,
					timingFunction: 'ease'
				}).top(this.top - this.radius * 2).opacity(0).step().export();

				// #endif

				// #ifdef APP-NVUE
				if (!this.$refs['ani']) return;
				animation.transition(this.$refs['ani'].ref, {
					styles: {
						transform: `translateY(-${this.radius}px)`,
						opacity: 0
					},
					duration: this.duration / 2,
					timingFunction: 'linear',
					needLayout: false,
					delay: 0
				});
				// #endif
			}, this.wait);

			// console.log('this.top', this.top);
			// console.log('this.radius', this.radius);

			setTimeout(() => {
				this.animationData = uni.createAnimation({
					duration: this.duration / 2,
					timingFunction: 'ease'
				}).top(this.top).opacity(0).step().export();
			}, 2800);
		},
		closeTimer() {
			clearInterval(this.timeIndex); //关闭弹幕定时器
		}
	}
};
</script>

<style scoped lang="scss">
.message {
	position: absolute;
	z-index: 8;
	opacity: 0;
}

.round {
	border-radius: 50px;
}

.bg-gradual-orange {
	background-color: rgba($color: #000, $alpha: 0.4);
	color: #fff;
}

.shadow {
	box-shadow: 40rpx 40rpx 50rpx rgba(217, 109, 26, 0.2);
}

.flex {
	/* #ifndef APP-NVUE */
	display: flex;
	/* #endif */
	flex-direction: row;
}

.justify-start {
	justify-content: flex-start;
}

.cu-avatar {
	/* #ifndef APP-NVUE */
	font-variant: small-caps;
	display: inline-flex;
	white-space: nowrap;
	background-size: cover;
	background-position: center;
	vertical-align: middle;
	/* #endif */
	margin: 0;
	padding: 0;
	text-align: center;
	justify-content: center;
	align-items: center;
	background-color: #ccc;
	color: #ffffff;
	position: relative;
	width: 300rpx;
	height: 300rpx;
	font-size: 300rpx;
}

/* #ifdef APP-NVUE */
.avatarimg {
	width: 30rpx;
	height: 30rpx;
	border-radius: 50rpx;
}

/* #endif */
.cu-a-sm {
	width: 60rpx;
	height: 60rpx;
	font-size: 20rpx;
}

.padding-lr-sm {
	padding-left: 20upx;
	padding-right: 20upx;
}

.align-center {
	align-items: center;
}

.margin-left-xs {
	margin-left: 10upx;
}

.text-bold {
	font-weight: bold;
}

.margin-lr-sm {
	margin-left: 20upx;
	margin-right: 20upx;
}

.text-sm {
	font-size: $font-size-tag;
	color: #ffffff;
}
</style>
