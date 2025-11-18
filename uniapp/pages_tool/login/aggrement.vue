<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<view class="aggrement-info" v-if="aggrement && aggrement.content">
			<ns-mp-html :content="aggrement.content"></ns-mp-html>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				aggrement: null,
				type: 'SERVICE'
			}
		},
		onLoad(option) {
			this.type = option.type || ''
			this.getaggrementInfo();
		},
		methods: {
			getaggrementInfo() {
				this.$api.sendRequest({
					url: '/api/register/aggrement',
					data: {
						type: this.type
					},
					success: res => {
						if (res.code >= 0) {
							this.aggrement = res.data;
						}
						if(this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			}
		},
	}
</script>

<style lang="scss" scoped>
	.aggrement-info{
		padding: 20rpx;
	}
</style>