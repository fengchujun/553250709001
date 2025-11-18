<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<view class="tab color-bg">
			<view class="tab-left">
				<picker :range="monthData" :value="monthIndex" @change="bindDateChange" >
					<view class="uni-input">
						{{ monthData[monthIndex] }}
						<text class="iconfont icon-iconangledown"></text>
					</view>
				</picker>
			</view>
			<view class="tab-right">
				<picker @change="bindPickerChange" :value="pointIndex" :range="pointType" class="picker" range-key="label">
					<text class="desc uni-input">{{ pointType[pointIndex].label }}</text>
					<text class="iconfont icon-iconangledown"></text>
				</picker>
			</view>
		</view>

		<mescroll-uni @getData="getData" class="member-point" ref="mescroll">
			<view slot="list">
				<block v-if="dataList.length">
					<view class="detailed-wrap">
						<view class="cont">
							<view class="detailed-item" v-for="(item, index) in dataList" :key="index">
								<view class="info" @click="toFromDetail(item)">
									<view class="event">{{ item.type_name }}</view>
									<view class="time-box">
										<text class="time color-tip">{{ $util.timeStampTurnTime(item.create_time) }}</text>
									</view>
								</view>
								<view class="num color-base-text" v-if="item.account_data > 0">+{{ parseInt(item.account_data) }}</view>
								<view class="num " v-else>{{ parseInt(item.account_data) }}</view>
							</view>
						</view>
					</view>
				</block>
				<block v-else>
					<view class="cart-empty"><ns-empty></ns-empty></view>
				</block>
			</view>
		</mescroll-uni>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			memberAccount: {
				point: 0
			},
			dataList: [],
			searchType: {
				from_type: 0,
				date: ''
			},
			pointType: [
				{
					label: '全部',
					value: '0'
				}
			], //积分类型
			pointIndex: 0,
			related_id: 0,
			monthData:[],
			monthIndex:0,
		};
	},
	onShow() {
		if (!this.storeToken) {
			this.$util.redirectTo('/pages_tool/login/index', {
				back: '/pages_tool/member/point'
			}, 'redirectTo');
		}
	},
	onLoad(option) {
		if (option.related_id) this.related_id = option.related_id ? option.related_id : 0;
		if (option.from_type) this.searchType.from_type = option.from_type;
		this.getPointType();
		this.getMonthData();
	},
	methods: {
		bindDateChange: function(e) {
			var index = e.target.value;
			this.monthIndex = index;
			this.searchType.date = this.monthData[index];
			this.$refs.mescroll.refresh();
		},
		getMonthData(){
			this.$api.sendRequest({
				url: '/api/memberaccount/monthData',
				success: res => {
					this.monthData = res.data;
					this.searchType.date = res.data[0];
				}
			});
		},
		bindPickerChange(e) {
			this.pointIndex = e.detail.value;
			this.searchType.from_type = this.pointType[this.pointIndex].value;
			this.$refs.mescroll.refresh();
		},
		//获取分类类型
		getPointType() {
			var temp = [],
				that = this;
			this.$api.sendRequest({
				url: '/api/memberaccount/fromType',
				success: res => {
					for (var index in res.point) {
						var obg = {};
						obg.label = res.point[index].type_name;
						obg.value = index;
						that.pointType.push(obg);
					}
				}
			});
		},
		toList() {
			this.$util.redirectTo('/pages_promotion/point/list');
		},
		toOrderList() {
			this.$util.redirectTo('/pages_promotion/point/order_list');
		},
		toFromDetail(item) {
			if (item.from_type == 'pointexchange') {
				this.$api.sendRequest({
					url: '/pointexchange/api/order/info',
					data: {
						order_id: item.type_tag
					},
					success: res => {
						if (res.code >= 0) {
							var data = res.data;
							if (data.type == 1 && data.relate_order_id) {
								this.$util.redirectTo('/pages/order/detail', {
									order_id: data.relate_order_id
								});
							} else {
								this.$util.redirectTo('/pages/order/detail_point', {
									order_id: data.order_id
								});
							}
						}
					}
				});
			} else if (item.from_type == 'pointcash') {
				this.$util.redirectTo('/pages/order/detail', {
					order_id: item.type_tag
				});
			} else if (item.from_type == 'memberconsume') {
				// this.$util.redirectTo('/pages/order/detail', {
				// 	order_id: item.type_tag
				// });
			} else if (item.from_type == 'pointexchangerefund' && parseInt(item.type_tag) != 0) {
				this.$util.redirectTo('/pages/order/detail_point', {
					order_id: item.type_tag
				});
			} else if (item.from_type == 'refund' && parseInt(item.type_tag) != 0) {
				this.$util.redirectTo('/pages/order/detail', {
					order_id: item.type_tag
				});
			}
		},
		//获得列表数据
		getData(mescroll) {
			this.$api.sendRequest({
				url: '/api/memberaccount/page',
				data: {
					page_size: mescroll.size,
					page: mescroll.num,
					account_type: 'point',
					from_type: this.searchType.from_type,
					date: this.searchType.date,
					related_id: this.related_id
				},
				success: res => {
					let newArr = [];
					let msg = res.message;
					if (res.code == 0 && res.data) {
						newArr = res.data.list;
					} else {
						this.$util.showToast({
							title: msg
						});
					}
					mescroll.endSuccess(newArr.length);
					//设置列表数据
					if (mescroll.num == 1) {
						this.dataList = []; //如果是第一页需手动制空列表
						this.related_id = 0;
					}
					this.dataList = this.dataList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				},
				fail: res => {
					mescroll.endErr();
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		}
	}
};
</script>

<style lang="scss">
/deep/ .fixed {
	position: relative;
	top: 0;
}

.tab {
	position: fixed;
	top: 0;
	width: 100%;
	z-index: 10;
	display: flex;
	justify-content: space-between;
	height: 80rpx;
	background-color: $color-bg;

	view {
		flex: 1;
		text-align: center;
		line-height: 80rpx;

		text {
			margin-left: 10rpx;
			font-size: $font-size-base;
		}
	}
	.tab-left{
		display: flex;
		padding-left: 30rpx;
	}
	.tab-right{
		display: flex;
		justify-content: flex-end;
		padding-right: 40rpx;
	}
}

.cart-empty {
	margin-top: 208rpx !important;
}

.detailed-wrap {
	background: #fff;
	position: relative;
	z-index: 9;
	padding-top: 80rpx;

	.head {
		display: flex;
		height: 90rpx;

		& > view {
			flex: 1;
			text-align: left;
			padding: 0 $padding;
			line-height: 90rpx;
		}
	}

	.cont {
		background: #fff;
		width: 100%;
		margin: 0 auto;

		.detailed-item {
			padding: 30rpx 0 32rpx;
			margin: 0 32rpx;
			border-bottom: 2rpx solid $color-line;
			position: relative;
			box-sizing: border-box;

			&:last-of-type {
				border-bottom: none;
			}

			.info {
				padding-right: 180rpx;

				.event {
					font-size: $font-size-base;
					line-height: 1.3;
					font-weight: 500;
				}

				.time-box {
					line-height: 1;
					margin-top: 24rpx;
				}

				.time {
					font-size: $font-size-activity-tag;
					color: $color-tip;
				}
			}

			.num {
				width: 160rpx;
				position: absolute;
				right: 17rpx;
				top: 50%;
				transform: translateY(-50%);
				text-align: right;
				font-size: $font-size-toolbar;
				font-weight: 500;
			}
		}
	}
}
</style>
