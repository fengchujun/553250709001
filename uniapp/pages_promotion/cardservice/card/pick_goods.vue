<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="page">
		<view class="card-info">
			<view class="card-title">- 选择提货数量 -</view>
			<view class="card-desc" v-if="cardDetail.card_type == 'commoncard'">卡项内项目/商品总的可用次数为{{ cardDetail.total_num - cardDetail.total_use_num }}次</view>
			<view class="card-content">
				<view class="card-item" v-for="(item, index) in cardDetail.card_item" v-if="item.goods_class == 1">
					<image :src="$util.img(item.sku_image)" mode="aspectFill"/>
					<view class="content">
						<view class="name multi-hidden">{{ item.sku_name }}</view>
						<view class="total-num" v-if="cardDetail.card_type == 'oncecard'">总次数：{{ item.num }}</view>
						<text class="total-use-num" v-if="['timecard', 'oncecard'].includes(cardDetail.card_type)">
							{{ cardDetail.card_type == 'timecard' ? '使用次数：不限次数' : '已使用次数：' + item.use_num }}
						</text>
					</view>
					<view class="select-num">
						<uni-number-box
							:min="buyNum[index].min"
							:max="buyNum[index].max"
							:value="buyNum[index].curr"
							:input-disabled="true"
							size="small"
							@change="cartNumChange($event, index)"
						/>
					</view>
				</view>
			</view>
			<view class="pick-btn">
				<button type="default" @click="pickGoods" :disabled="pickDisabled">提货</button>
			</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import uniNumberBox from '@/components/uni-number-box/uni-number-box.vue';
import htmlParser from '@/common/js/html-parser';
export default {
	components: {
		uniNumberBox
	},
	data() {
		return {
			cardDetail: {},
			emptyShow: false,
			minBuyNum: 1,
			currBuyNum: [],
			buyNum: []
		};
	},
	onLoad(option) {
		let data = {
			id: option.card_id,
			msg: '缺少card_id参数'
		};
		this.initFn(data);
	},
	onShow() {},
	computed: {
		// 提货按钮是否禁用标识
		pickDisabled() {
			var count = 0; // 统计提货数量为0的
			this.buyNum.forEach((item, index) => {
				if (item.curr == 0) count++;
			});
			// 如果提货数量为0，禁用按钮
			if (count == this.buyNum.length) {
				return true;
			}
			return false;
		}
	},
	methods: {
		initFn(data) {
			if (!data.id) {
				this.$util.showToast({ title: data.msg });
				setTimeout(() => {
					this.$util.redirectTo('/pages_promotion/cardservice/card/my_card');
				}, 800);
				return false;
			}
			if (!this.storeToken) {
				this.$util.redirectTo('/pages_tool/login/index', { back: '/pages_promotion/cardservice/card/pick_goods', card_id: this.card_id });
				return false;
			}
			this.getData(data.id);
		},
		getData(id) {
			this.$api.sendRequest({
				url: '/cardservice/api/membercard/detail',
				data: {
					card_id: id
				},
				success: res => {
					if (res.code >= 0) {
						this.cardDetail = res.data;
						this.cardDetail.goods_content = htmlParser(this.cardDetail.goods_content);
						this.cardDetail.card_item.forEach((item, index) => {
							var data = {
								max: 10,
								min: 0,
								curr: 0
							};
							this.buyNum.push(data);
							this.calcBuyNum(0, index);
						});
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					} else {
						this.$util.showToast({
							title: '未获取到卡信息！'
						});
						setTimeout(() => {
							this.$util.redirectTo('/pages_promotion/cardservice/card/my_card');
						}, 1500);
					}
				},
				fail: res => {
					uni.stopPullDownRefresh();
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		cartNumChange(e, index) {
			this.calcBuyNum(e, index);
		},
		calcBuyNum(num, index) {
			let item = this.cardDetail.card_item[index];
			if (this.cardDetail.card_type == 'oncecard') this.buyNum[index].max = item.num - item.use_num;
			else if (this.cardDetail.card_type == 'commoncard') this.buyNum[index].max = item.num - item.use_num;
			else if (this.cardDetail.card_type == 'timecard') {
				this.buyNum[index].max = item.stock;
			}

			// 增加所选数量超出库存数量
			if (num > item.stock && item.stock) {
				this.buyNum[index].max = item.stock;
				this.buyNum[index].curr = 0;
				this.$util.showToast({
					title: '商品库存不足'
				});
				setTimeout(() => {
					this.buyNum[index].curr = item.stock;
				});
				this.$forceUpdate();
				return false;
			}
			if (num <= this.buyNum[index].max) this.buyNum[index].curr = num;
			this.$forceUpdate();
		},
		pickGoods() {
			let data = {};
			data.member_card_id = this.cardDetail.card_id;
			data.member_card_item = [];
			let count = 0; // 统计提货数量为0的
			this.cardDetail.card_item.forEach((item, index) => {
				let obj = {};
				obj.item_id = item.item_id;
				obj.num = this.buyNum[index].curr;
				if (obj.num > 0) {
					data.member_card_item.push(obj);
				} else {
					count++;
				}
			});

			// 如果提货数量为0，禁用按钮
			if (count == this.cardDetail.card_item.length) {
				this.$util.showToast({
					title: '请选择提货数量'
				});
				return;
			}

			data.member_card_item = JSON.stringify(data.member_card_item);
			uni.setStorageSync('card_pick', data);
			this.$util.redirectTo('/pages_promotion/cardservice/card/pick_payment');
		},
		toDetail(item) {
			this.$util.redirectTo('/pages/goods/detail', {
				goods_id: item.goods_id
			});
		}
	}
};
</script>

<style lang="scss" scoped>
@mixin wrap {
	margin: 0 24rpx 24rpx;
	padding: 0 24rpx 30rpx;
	background-color: #fff;
	border-radius: 18rpx;
	box-sizing: border-box;
}
.page {
	padding-top: 24rpx;
	overflow: hidden;
}
.card-info {
	@include wrap;
	margin-bottom: calc(120rpx + constant(safe-area-inset-bottom));
	margin-bottom: calc(120rpx + env(safe-area-inset-bottom));

	.card-title {
		padding-top: 24rpx;
		text-align: center;
		font-weight: bold;
	}

	.card-desc {
		padding-left: 10rpx;
		font-size: $font-size-tag;
		color: $color-sub;
	}

	.card-content {
		margin-top: 20rpx;
		overflow: hidden;
	}

	.card-item {
		position: relative;
		margin-bottom: 28rpx;
		display: flex;
		padding: 20rpx;
		background-color: #fbf9fc;
		border-radius: 12rpx;
		&:last-of-type {
			margin-bottom: 0;
		}
		image {
			overflow: hidden;
			margin-right: 24rpx;
			width: 160rpx;
			height: 160rpx;
			border-radius: 10rpx;
		}
		.content {
			position: relative;
			flex: 1;
			display: flex;
			flex-direction: column;
			.name {
				padding-right: 30rpx;
				font-weight: bold;
				line-height: 1.3;
			}
			.total-num {
				margin-top: auto;
				line-height: 1.6;
			}
			.total-use-num,
			.total-num {
				font-size: $uni-font-size-sm;
				color: #666;
			}
		}
		.select-num {
			position: absolute;
			top: 70%;
			right: 24rpx;
			transform: translateY(-50%);
			padding: 0;
			margin: 0;
			height: 48rpx;
			line-height: 46rpx;
			border-radius: 50rpx;
			/deep/ .decrease {
				border-top-left-radius: 8rpx;
				border-bottom-left-radius: 8rpx;
			}
			/deep/ .increase {
				border-top-right-radius: 8rpx;
				border-bottom-right-radius: 8rpx;
			}
		}
	}
	.pick-btn {
		position: fixed;
		right: 0;
		left: 0;
		bottom: 0;
		display: flex;
		align-items: center;
		height: 98rpx;
		background-color: #fff;
		padding-bottom: 0;
		padding-bottom: constant(safe-area-inset-bottom);
		padding-bottom: env(safe-area-inset-bottom);
		button {
			flex: 1;
			border-radius: 50rpx;
			background-color: $base-color;
			color: #fff;
		}
	}
}
</style>
