<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="page">
		<mescroll-uni top="0" ref="mescroll" @getData="getData">
			<block slot="list">
				<view class="adv">
					<ns-adv keyword="NS_CARD"></ns-adv>
				</view>
				<view class="card-box" v-for="(typeitem, typeindex) in cardList">
					<view class="card-category-title" >
						<text class="before-line"></text>
						<text>{{typeitem.title}}</text>
						<text class="after-line"></text>
					</view>
					<view class="goodslist">
						<view class="goodsitem"  v-for="(item, index) in typeitem.card_list" :key="index" @click="toDetail(item)">
							<image :src="$util.img(item.goods_image.split(',')[0], {'size':'mid'})" mode="aspectFill"></image>
							<view class="conten">
								<view class="name multi-hidden">{{item.goods_name}}</view>
								<view class="price">
									<text class="price-font">￥</text>
									<text class="price-font">{{item.price}}</text>
								</view>
								<view class="btn-wrap">
									<text class="num">已售{{item.sale_num}}</text>
									<button type="default">抢购</button>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view v-if="cardList.length == 0 && emptyShow"><ns-empty text="暂无卡项"></ns-empty></view>
			</block>
		</mescroll-uni>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import nsAdv from '@/components/ns-adv/ns-adv.vue';
 
export default {
	components: {
		nsAdv
	},
	data() {
		return {
			cardList: [],
			emptyShow: false,
		};
	},
	onLoad(option) {},
	onShow() {}, 
	methods: {
		getData(mescroll) {
			this.emptyShow = false;
			this.$api.sendRequest({
				url: '/cardservice/api/card/getcardlistbytype',
				data:{
					page: mescroll.num,
					page_size: mescroll.size,
				},
				success: res => {
					let newArr = []
					let msg = res.message;
					this.emptyShow = true;
					if (res.code == 0) {
						newArr = Object.values(res.data);
					} else {
						this.$util.showToast({
							title: msg
						})
					}
					mescroll.endSuccess(newArr.length);
					//设置列表数据
					if (mescroll.num == 1) this.cardList = []; //如果是第一页需手动制空列表
					this.cardList = this.cardList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
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
.page{
	background: #F9FBFF;
	min-height: 100vh;
}

.bg{
	margin: 0;
	border-radius: 0;
	line-height: 1;
	height: fit-content;
	background: #F9FBFF;
	width: 100%;
	image {
		width: 100%;
	}
}

/deep/ .fixed {
	position: relative;
	top: 0;
}

/deep/ .empty {
	margin-top: 0 !important;
}

.topic-pic {
	height: 300rpx;

	image {
		height: 300rpx;
	}
}
.card-box{
	.card-category-title{
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 30rpx;
		color: #222222;
		padding: 20rpx 0;
		font-weight: bold;
		.before-line,.after-line{
			width: 30rpx;
			height: 4rpx;
			margin: 0 10rpx;
			background-color: #333;
		}
		
	}
	 
}

.goodslist{
		padding: 0 24rpx;
		.goodsitem{
			margin-bottom: 24rpx;
			padding: 28rpx 24rpx;
			display: flex;
			border-radius: 18rpx;
			background-color: #fff;
			image{
				width: 200rpx;
				height: 200rpx;
				border-radius: 10rpx;
				background-color: #eeeeee;
				margin-right: 20rpx;
				overflow: hidden;
			}
			.conten{
				flex: 1;
				display: flex;
				flex-direction: column;
				.name{
					font-size: 30rpx;
					font-weight: bold;
					line-height: 1.5;
				}
				.price{
					display: flex;
					align-items: baseline;
					font-size: $font-size-tag;
					color: $base-color;
					font-weight: bold;
					text:last-child{
						font-size: $font-size-toolbar;
					}
				}
				.btn-wrap{
					display: flex;
					align-items: center;
					justify-content: space-between;
					margin-top: auto;
					.num{
						font-size: $font-size-tag;
						color: $color-tip;
					}
					button{
						height: 56rpx;
						line-height: 56rpx;
						min-width: 88rpx;
						padding: 0 30rpx;
						margin: 0;
						border-radius: 30rpx;
						color: #fff;
						font-size: $font-size-sub;
						background-color: $base-color;
					}
				}
			}
		}
	}
 
</style>
<style>
	.adv{
		margin: 0;
		border-radius: 0;
		overflow: hidden;
	}
	.adv >>> image{
		max-height: 100%;
		width: 100%;
		border-radius: 0;
	}
</style>