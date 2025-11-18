<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="page">
		<view class="card-nav">
			<view v-for="(statusItem, statusIndex) in statusList" :key="statusIndex" class="uni-tab-item"  @click="ontabtap(statusItem)">
				<text class="uni-tab-item-title" :class="statusItem.status == cardStatus ? 'uni-tab-item-title-active color-base-text' : ''">
					{{ statusItem.name }}
				</text>
			</view>
		</view>
		<mescroll-uni ref="mescroll" top="110rpx" @getData="getData">
			<block slot="list">
				<view class="goods-item" :style="{backgroundImage: 'url(' + $util.img(item.goods_image || 'public/uniapp/cardservice/card_bg.png') + ')'}" v-for="(item,index) in cardList" :key="index" @click="toDetail(item)">
					<view class="content">
						<view class="name using-hidden">{{item.goods_name}}</view>
						<view class="desc multi-hidden">{{item.introduction}}</view>
						<view class="other" :class="item.status == 0 ? 'warning' : ''">
							<text>总次数：{{item.card_type == "timecard" ? "不限次" : item.total_num }}</text>
							<text>已使用：{{ item.total_use_num }}</text>
							<text v-if="item.status == 1">{{parseInt(item.end_time) == 0 ? "长期有效" : '至 '+$util.timeStampTurnTime(item.end_time, 'Y-m-d')}}</text>
							<text v-else>{{item.invalid_reason || '已失效'}}</text>
						</view>
					</view>
				</view>
				<view v-if="cardList.length == 0 && emptyShow"><ns-empty text="暂无卡项" :emptyBtn="{'url': '/pages_promotion/cardservice/card/list', text: '去逛逛'}"></ns-empty></view>
			</block>
		</mescroll-uni>
		
		<loading-cover ref="loadingCover"></loading-cover>
		<ns-login ref="login"></ns-login>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
export default {
	data() {
		return {
			cardList: [],
			emptyShow: false,
			statusList:[
				{
					name: "全部",
					status: 'all'
				},
				{
					name: "待使用",
					status: 1
				},
				{
					name: "已失效",
					status: 0
				}
			],
			cardStatus: 'all'
		};
	},
	onLoad(option) {},
	onShow() {
		if(!this.storeToken)
			this.$util.redirectTo('/pages_tool/login/index', { back: '/pages_promotion/cardservice/card/my_card'});
	},
	methods: {
		getData(mescroll) {
			this.$api.sendRequest({
				url: '/cardservice/api/membercard/page',
				data:{
					page: mescroll.num,
					page_size: mescroll.size,
					status: this.cardStatus
				},
				success: res => {
					let newArr = []
					let msg = res.message;
					if (res.code == 0) {
						newArr = res.data.list;
						if (res.data.page_count == 0) {
							this.emptyShow = true
						}
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
		ontabtap(data){
			this.cardStatus = data.status;
			this.$refs.loadingCover.show();
			this.$refs.mescroll.refresh();
		},
		toDetail(item) {
			this.$util.redirectTo('/pages_promotion/cardservice/card/my_detail', {
				card_id: item.card_id
			});
		}
	}
};
</script>

<style lang="scss" scoped>
.goods-item{
	position: relative;
	border-radius: 18rpx;
	height: 320rpx;
	margin: 0 30rpx 24rpx;
	background-repeat: no-repeat;
	background-size: cover;
	background-position: center;
	box-sizing: border-box;
	.content{
		display: flex;
		flex-direction: column;
		border-radius: 18rpx;
		width: 100%;
		height: 100%;
		padding: 36rpx;
		box-sizing: border-box;
		background-color: rgba(0,0,0,0.4);
		.name{
			color: #ffffff;
			font-size: 34rpx;
			font-weight: bold;
			line-height: 1;
		}
		.desc{
			height: 52rpx;
			color: #ffffff;
			font-size: 28rpx;
			margin-top: 20rpx;
		}
			
		.other{
			display: flex;
			align-items: center;
			margin-top: auto;
			color: #ffffff;
			line-height: 1;
			font-size: $font-size-tag;
			
			text {
				flex: 1;
				&:nth-child(2) {
					padding-left: 60rpx;
				}
				&:nth-child(3) {
					text-align: right;
				}
			}
			&.warning{
				color:red;
			}
		}
	}
}

.card-nav {
	width: 100vw;
	height: 80rpx;
	flex-direction: row;
	/* #ifndef APP-PLUS */
	white-space: nowrap;
	/* #endif */
	background: #fff;
	display: flex;
	position: fixed;
	left: 0;
	z-index: 998;
	justify-content: space-around;
	border-radius: 0px 0px 24rpx 24rpx;

	.uni-tab-item {
		width: 120rpx;
		text-align: center;
	}

	.uni-tab-item-title {
		display: inline-block;
		height: 80rpx;
		line-height: 80rpx;
		border-bottom: 1px solid #fff;
		flex-wrap: nowrap;
		/* #ifndef APP-PLUS */
		white-space: nowrap;
		/* #endif */
		text-align: center;
		font-size: 30rpx;
		position: relative;
	}

	.uni-tab-item-title-active::after {
		content: " ";
		display: block;
		position: absolute;
		left: 0;
		bottom: 0;
		width: 100%;
		height: 6rpx;
		background: linear-gradient(270deg, var(--base-color-light-9) 0%, var(--base-color) 100%);
	}

	::-webkit-scrollbar {
		width: 0;
		height: 0;
		color: transparent;
	}
}

</style>