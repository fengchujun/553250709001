<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="page">
		<view class="recodrd-list">
			<view class="recodrd-item" v-for="(item,index) in list" :key="index" @click="toOrderDetail(item)">
				<view class="item-head">
					<text>{{$util.timeStampTurnTime(item.create_time)}}</text>
					<text>{{item.type =="verify" ? '核销成功' : '提货成功'}}</text>
				</view>
				<view class="item-body">
					<view class="image">
						<image :src="$util.img(item.sku_image)" mode="aspectFill" @error="imageError(index)"></image>
					</view>
					<view class="content-wrap">
						<view class="content-item name">
							<text class="multi-hidden">{{item.sku_name}}</text>
							<!-- <text>x {{item.item_num}}</text> -->
						</view>
						<view class="content-item">
							<text>{{item.type =="verify" ? '本次核销' : '本次提货'}}</text>
							<text class="color-base-text">x{{item.num}}</text>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view v-if="!list.length"><ns-empty text="暂无记录"></ns-empty></view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			list: [],
			card_id: ''
		};
	},
	onLoad(option) {
		let data = {
			id: option.card_id,
			msg: '缺少card_id参数',
		}
		this.initFn(data);
	},
	onShow() {},
	methods: {
		initFn(data){
			if(!data.id){
				this.$util.showToast({title: data.msg});
				setTimeout(()=>{
					this.$util.redirectTo('/pages_promotion/cardservice/card/my_card');
				},800);
				return false;
			}
			if(!this.storeToken){ 
				this.$util.redirectTo('/pages_tool/login/index', { back: '/pages_promotion/cardservice/card/card_record','card_id':this.card_id});
				return false;
			}
			this.getData(data.id);
		},
		getData(id) {
			this.$api.sendRequest({
				url: '/cardservice/api/membercard/records',
				data:{
					card_id: id
				},
				success: res => {
					if (res.code >= 0) {
						this.list = res.data;
					} else {
						this.backPage(res.message);
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		backPage(msg){
			this.$util.showToast({
				title: msg
			});
			setTimeout(()=>{
				this.$util.redirectTo('/pages_promotion/cardservice/card/my_detail', {'card_id':this.card_id});
			},800);
		},
		toOrderDetail(item) {
			if(item.type == "order"){
				this.$util.redirectTo('/pages/order/detail', {
					order_id: item.order_id
				});
			}
		},
		imageError(index) {
			this.list[index].sku_image = this.$util.getDefaultImage().goods;
			this.$forceUpdate();
		}
	}
};
</script>

<style lang="scss" scoped>
	.recodrd-list{
		padding-bottom: 24rpx;
		.recodrd-item{
			overflow: hidden;
			margin: 24rpx 24rpx 0;
			border-radius: 12rpx;
			background-color: #fff;
			.item-head{
				margin: 0 24rpx;
				display: flex;
				align-items: center;
				justify-content: space-between;
				height: 70rpx;
				border-bottom: 2rpx solid #F4F4F4;
				text:nth-child(1){
					font-size: $font-size-tag;
					color: #888888;
				}
				text:nth-child(2){
					font-size: $font-size-tag;
					color: $base-color;
				}
			}
			.item-body{
				display: flex;
				padding: 20rpx;
				.image{
					margin-right: 24rpx;
					width: 160rpx;
					height: 160rpx;
					border-radius: 10rpx;
					overflow: hidden;
					image{
						width: 100%;
						height: 100%;
					}
				}
				.content-wrap{
					flex: 1;
				}
				.content-item{
					flex: 1;
					display: flex;
					justify-content: space-between;
					align-items: center;
					font-size: $font-size-tag;
					line-height: 1.3;
					margin-bottom: 24rpx;
					&.name{
						font-weight: bold;
						font-size: $font-size-base;
					}
				}
			}
		}
	}
</style>