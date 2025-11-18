<template>
	<view v-if="advList.length" :class="['container-box',className]">
		<swiper :indicator-dots="advList.length > 1" indicator-active-color="#ffffff" :autoplay="true" :interval="3000" :duration="1000" v-if="advList.length > 1" @change="changeSwiper" :current="currentIndex" :style="{ height: swiperHeight + 'px' }" class="item-wrap">
			<swiper-item v-for="(item, index) in advList" :key="index" @click="jumppage(item.adv_url)">
				<view class="image-box">
					<image :src="$util.img(item.adv_image)" mode="widthFix" :id="'content-wrap' + index"/>
				</view>
			</swiper-item>
		</swiper>
		<view v-else class="container-box item-wrap">
			<image :src="$util.img(advList[0]['adv_image'])" mode="widthFix" lazy-load="true" @load="imageLoad" @click="jumppage(advList[0].adv_url)"/>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'ns-advert',
		props: {
			keyword: {
				type: String
			},
			className: {
				type: String
			}
		},
		data() {
			return {
				advList: [],
				isImage: false,
				//滑块的高度(单位px)
				swiperHeight: 150,
				//当前索引
				currentIndex: 0,
			};
		},
		created() {
			this.getAdvList();
		},
		methods: {
			//获取广告位
			getAdvList() {
				var item = {
					adv_image: '',
					adv_url: ''
				};
				this.$api.sendRequest({
					url: '/api/adv/detail',
					data: {
						keyword: this.keyword
					},
					success: res => {
						if (res.code == 0) {
							var data = res.data.adv_list;
							for (var index in data) {
								if (data[index].adv_url) data[index].adv_url = JSON.parse(data[index].adv_url);
							}
							this.advList = res.data.adv_list;

							//动态设置swiper的高度
							this.$nextTick(() => {
								this.setSwiperHeight();
							});
						}
					}
				});
			},
			jumppage(e) {
				this.$util.diyRedirectTo(e);
			},
			imageLoad(data) {
				this.isImage = true;
			},
			//手动切换题目
			changeSwiper(e) {
				this.currentIndex = e.detail.current;
				//动态设置swiper的高度，使用nextTick延时设置
				this.$nextTick(() => {
					this.setSwiperHeight();
				});
			},
			//动态设置swiper的高度
			setSwiperHeight() {
				if (this.advList.length > 1) {
					setTimeout(() => {
						let element = "#content-wrap" + this.currentIndex;
						let query = uni.createSelectorQuery().in(this);
						query.select(element).boundingClientRect();
						query.exec((res) => {
							if (res && res[0]) {
								this.swiperHeight = res[0].height;
							}
						});
					}, 10);
				}
			},
		}
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
</style>