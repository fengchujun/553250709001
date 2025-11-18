<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="reserve-wrap">
		<scroll-view :scroll-x="true" enable-flex="true"  class="reserve-nav">
			<view v-for="(item, index) in navStatus.list" :key="index" class="nav-item" :class="item.id == navStatus.index ? 'active' : ''" @click="ontabtap(index)">
				{{ item.name }}
			</view>
		</scroll-view>
		
		<mescroll-uni ref="mescroll" @getData="getListData" top="104rpx">
			<block slot="list">
				<view class="reserve-list" v-if="reserveList.length > 0">
					<view class="reserve-item"  v-for="(item, index) in reserveList" :key="index" @click="toDetail(item.goods_id)">
						<image :src="$util.img(item.goods_image)" mode="aspectFill" @error="imageError(index)"></image>
						<view class="conten">
							<view class="name multi-hidden">{{item.goods_name}}</view>
							<view class="price">
								<text>￥</text>
								<text>{{item.price}}</text>
							</view>
							<view class="btn-wrap">
								<text class="num">已售{{item.sale_num}}</text>
								<button type="default" @click="toDetail(item.goods_id)">立即购买</button>
							</view>
						</view>
					</view>
				</view>
				<view v-else><ns-empty :isIndex="false" text="暂无服务信息"></ns-empty></view>
			</block>
		</mescroll-uni>
		
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			navStatus:{
				list: [],
				index: ""
			},
			reserveList: [],
			service_category:''
		};
	},
	onLoad(option) {
		this.service_category = option.category_id || ''
		if(this.service_category) this.navStatus.index = this.service_category;
	},
	onShow() {
		this.getNavStatus();
	},
	methods: {
		/**
		 * 获取导航状态
		 */
		getNavStatus() {
			this.$api.sendRequest({
				url: '/cardservice/api/servicescategory/lists',
				success: res => {
					this.navStatus.list = [{name:'全部',id:''}];
					if(res.code >= 0){
						let data = res.data;
						data.forEach((item,index)=>{
							var obj = {};
							obj.name = item.category_name;
							obj.id = item.category_id;
							this.navStatus.list.push(obj);
						});
					}
				}
			});
		},
		ontabtap(index) {
			this.navStatus.index = this.navStatus.list[index].id;
			this.service_category = this.navStatus.list[index].id;
			this.$refs.loadingCover.show();
			this.$refs.mescroll.refresh();
		},
		getListData(mescroll) {
			this.$api.sendRequest({
				url: '/cardservice/api/service/page',
				data: {
					page: mescroll.num,
					page_size: mescroll.size,
					service_category: this.service_category
				},
				success: res => {
					let newArr = [];
					let msg = res.message;
					if (res.code == 0 && res.data) {
						newArr = res.data.list;
					} else {
						this.$util.showToast({ title: msg });
					}
					mescroll.endSuccess(newArr.length);
					//设置列表数据
					if (mescroll.num == 1){
						this.reserveList = []; //如果是第一页需手动制空列表
					}
					this.reserveList = this.reserveList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				},
				fail: res => {
					mescroll.endErr();
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		imageError(index) {
			this.reserveList[index].goods_image = this.$util.getDefaultImage().goods;
			this.$forceUpdate();
		},
		toDetail(id){
			this.$util.redirectTo('/pages/goods/detail',{"goods_id":id});
		}
	}
};
</script>

<style lang="scss">
	.reserve-nav{
		/*  #ifdef MP-WEIXIN */
		display: flex;
		align-items: center;
		/*  #endif  */
		width: auto;
		height: 84rpx;
		white-space: nowrap;
		background-color: #fff;
		padding: 0 24rpx;
		/deep/ .uni-scroll-view-content{
			display: flex;
			align-items: center;
		}
		.nav-item{
			flex-shrink: 0;
			margin-right: 24rpx;
			text-align: center;
			background-color: #F7F7F7;
			border-radius: 8rpx;
			font-size: $font-size-base;
			padding: 0 18rpx;
			/*  #ifdef MP-WEIXIN */
			align-self: flex-end;
			/*  #endif  */
			&:last-child{
				margin-right: 0;
			}
			&.active{
				color: $base-color;
				background-color: var(--main-color-shallow);
			}
		}
	}
	.reserve-list{
		padding: 0 24rpx;
		.reserve-item{
			margin-bottom: 24rpx;
			padding: 28rpx 24rpx;
			display: flex;
			border-radius: 18rpx;
			background-color: #fff;
			image{
				width: 200rpx;
				height: 200rpx;
				border-radius: 10rpx;
				background-color: pink;
				margin-right: 20rpx;
				overflow: hidden;
			}
			.conten{
				overflow: hidden;
				flex: 1;
				display: flex;
				flex-direction: column;
				width: 420rpx;
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
<style scoped>
/deep/ .uni-page {
	overflow: hidden;
}
/deep/ .mescroll-upwarp {
	padding-bottom: 100rpx;
}
</style>
