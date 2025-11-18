<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="order-container">
		<view class="order-nav">
			<view class="uni-tab-item" @click="ontabtap(statusItem.state)" v-for="(statusItem, statusIndex) in statusList" :key="statusIndex" v-if="statusItem.state != 'cancelled'">
				<text class="uni-tab-item-title" :class="statusItem.state == status ? 'uni-tab-item-title-active color-base-text' : ''">
					{{ statusItem.name }}
				</text>
			</view>
		</view>
		
		<!-- #ifdef MP -->
		<mescroll-uni ref="mescroll" @getData="getListData" top="176rpx" v-if="storeToken">
		<!-- #endif -->
		<!-- #ifndef MP -->
		<mescroll-uni ref="mescroll" @getData="getListData" top="82rpx" v-if="storeToken">
		<!-- #endif -->
			<block slot="list">
				<view class="order-list" v-if="reserveList.length > 0">
					<view class="order-item" v-for="(reserveItem, reserveIndex) in reserveList" :key="reserveIndex" >
						<view class="order-header">
							<text class="order-no">{{ $util.timeStampTurnTime(reserveItem.create_time) }}</text>
							<text class="status-name">{{ reserveItem.reserve_state_name }}</text>
						</view>
						<view class="order-body" @click="jumpDetail(reserveItem.reserve_id)">
							<block v-if="reserveItem.item.length > 0">
								<view class="goods-wrap" v-for="(goodsItem, goodsIndex) in reserveItem.item" :key="goodsIndex">
									<view class="goods-img">
										<image :src="$util.img(goodsItem.sku_image)" mode="aspectFill" :lazy-load="true"/>
									</view>
									<view class="goods-info">
										<view class="pro-info">
											<view class="goods-name">{{ goodsItem.goods_name }}</view>
										</view>
										<view class="pro-info-to">
											<view class="content">预约时间：{{ $util.timeStampTurnTime(reserveItem.reserve_time) }}</view>
										</view>
										<view class="pro-info-to">
											<view class="content">{{ reserveItem.remark }}</view>
										</view>
									</view>
								</view>
							</block>
						</view>
						<view class="order-footer" v-if="reserveItem.reserve_state == 'wait_confirm' || reserveItem.reserve_state == 'wait_to_store'">
							<view class="order-action"><view class="order-box-btn" @click="cancel(reserveItem.reserve_id)">取消预约</view></view>
						</view>
					</view>
				</view>
				<view v-else><ns-empty :isIndex="false" text="暂无预约信息"></ns-empty></view>
			</block>
		</mescroll-uni>
		
		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>

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
			status : 'all',
			statusList: [],
			reserveList: [],
			scrollInto: '',
			isIphoneX: false,
			searchText : "",
			pageText : ""
		};
	},
	onLoad(option) {
		if (option.status) this.status = option.status;
		this.getStatus();
	},
	onShow() {
		this.isIphoneX = this.$util.uniappIsIPhoneX();
		if (this.storeToken) {
			if (this.$refs.mescroll) this.$refs.mescroll.refresh();
		} else {
			this.$nextTick(() => {
				this.$refs.login.open('/pages_promotion/cardservice/service_goods/my_reserve_list');
			});
		}
	},
	methods: {
		ontabtap(e) {
			this.status = e;
			this.$refs.loadingCover.show();
			this.$refs.mescroll.refresh();
		},
		getStatus(){
			this.$api.sendRequest({
				url: '/cardservice/api/reserve/status',
				success: res => {
					this.statusList.push({name:'全部',state:'all'});
					if(res.code == 0){
						for (let i in res.data){
							this.statusList.push(res.data[i]);
						}
					}
				}
			});
		},
		getListData(mescroll) {
			
			this.$api.sendRequest({
				url: '/cardservice/api/reserve/lists',
				data: {
					page: mescroll.num,
					page_size: mescroll.size,
					reserve_state: this.status,
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
		cancel(reserve_id){
			uni.showModal({
				title: '提示',
				content: '您确定要取消该预约吗？',
				success: res => {
					this.$api.sendRequest({
						url: '/cardservice/api/reserve/cancel',
						data: {
							reserve_id : reserve_id
						},
						success: res => {
							if(res.code == 0){
								this.$refs.mescroll.refresh();
							}
						}
					});
				}
			})
		},
		jumpDetail(reserve_id){
			this.$util.redirectTo('/pages_promotion/cardservice/service_goods/my_reserve_detail',{"reserve_id":reserve_id});
		},
		search() {
			this.pageText = this.searchText;
			this.$refs.mescroll.refresh();
		}
	},
};
</script>

<style lang="scss">
$margin-both: 24rpx;

.order-container {
	width: 100vw;
}

.align-right {
	text-align: right;
}

.cate-search {
	width: calc(100% - 48rpx);
	background: #ffffff;
	padding: 10rpx 24rpx 0 24rpx;
	/* #ifdef H5 */
	padding-top: 30rpx;
	/* #endif */
	position: relative;
	z-index: 998;
	
	input {
		font-size: $font-size-base;
		height: 76rpx;
		padding: 0 25rpx 0 30rpx;
		line-height: 60rpx;
		width: calc(100% - 120rpx);
	}

	text {
		font-size: 32rpx;
		color: $color-tip;
		width: 120rpx;
		text-align: center;
	}

	.search-box {
		width: 100%;
		background: $color-bg;
		display: flex;
		justify-content: center;
		align-items: center;
		border-radius: 100rpx;
	}
}

.order-nav {
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

.order-item {
	margin: $margin-updown $margin-both;
	border-radius: 12rpx;
	background: #fff;
	position: relative;

	.order-header {
		display: flex;
		align-items: center;
		position: relative;
		padding: $padding 24rpx 26rpx 24rpx;

		&.waitpay {
			padding-left: 70rpx;

			.icon-yuan_checked,
			.icon-yuan_checkbox {
				font-size: $font-size-toolbar;
				position: absolute;
				top: 48%;
				left: 20rpx;
				transform: translateY(-50%);
			}
			.icon-yuan_checkbox {
				color: $color-tip;
			}
		}

		.icon-dianpu {
			display: inline-block;
			line-height: 1;
			margin-right: 12rpx;
			font-size: $font-size-base;
		}
		
		.order-no {
			font-size: 26rpx;
		}

		.status-name {
			flex: 1;
			text-align: right;
			font-size: 26rpx;
			font-weight: 600;
		}
	}

	.order-body {
		.goods-wrap {
			display: flex;
			position: relative;
			padding: 0 24rpx 30rpx 24rpx;

			&:last-of-type {
				margin-bottom: 0;
			}

			.goods-img {
				width: 160rpx;
				height: 160rpx;
				margin-right: 20rpx;

				image {
					width: 100%;
					height: 100%;
					border-radius: $border-radius;
				}
			}

			.goods-info {
				position: relative;
				max-width: calc(100% - 180rpx);
				display: flex;
				flex-direction: column;

				.pro-info {
				}
				.pro-info-top {
					font-size: 24rpx;
					color: #888;
				}
				.goods-name {
					display: -webkit-box;
					-webkit-box-orient: vertical;
					-webkit-line-clamp: 2;
					overflow: hidden;
					line-height: 1.5;
					font-size: $font-size-base;
					color: $color-title;
				}

				.goods-sub-section {
					width: 100%;
					line-height: 1.3;
					display: flex;
					margin-top: 14rpx;

					.goods-price {
						font-size: $font-size-tag;
						color: var(--price-color);
						flex: 1;
						font-weight: bold;
					}
					.goods-num {
						font-size: $font-size-tag;
						color: $color-tip;
						flex: 1;
						text-align: right;
						line-height: 1;

						.iconfont {
							font-size: $font-size-tag;
						}
					}
					.goods-type {
						font-size: $font-size-tag;
					}

					.unit {
						font-size: $font-size-tag;
						margin-right: 2rpx;
					}

					view {
						flex: 1;
						line-height: 1.3;
						display: flex;
						flex-direction: column;

						&:last-of-type {
							text-align: right;

							.iconfont {
								line-height: 1;
								font-size: $font-size-tag;
							}
						}
					}
				}

				.goods-action {
					text-align: right;

					.action-btn {
						line-height: 1;
						padding: 14rpx 20rpx;
						color: $color-title;
						display: inline-block;
						border-radius: $border-radius;
						background: #fff;
						border: 2rpx solid #999;
						font-size: $font-size-tag;
						margin-left: 10rpx;
					}
				}
			}
		}
		
		.multi-order-goods {
			width: calc(100vw - 96rpx);
			white-space: nowrap;
			margin: 0 24rpx 30rpx 24rpx!important;
			position: relative;
			
			.scroll-view {
				width: 100%;
			}
			
			.goods-wrap {
				padding: 0;
			}
			
			.goods-img {
				min-width: 160rpx;
			}
			
			.shade {
				position: absolute;
				z-index: 5;
				height: 100%;
				width: 44rpx;
				right: 0;
				top: 0;
				
				image {
					width: 100%;
					height: 100%;
				}
			}
		}
	}

	.order-footer {
		.order-base-info {
			.total {
				padding: $padding;
				font-size: $font-size-tag;
				background: rgba(248, 248, 248, 0.5);
				display: flex;
				margin: 0 24rpx;

				& > text {
					flex: 1;
					line-height: 1;
					margin-left: 10rpx;
				}
			}

			.order-type {
				padding-top: 20rpx;
				flex: 0.5;

				& > text {
					line-height: 1;
				}
			}
		}

		.order-action {
			text-align: right;
			padding: 10rpx 24rpx;
			position: relative;
			
			.order-time {
				position: absolute;
				top: 35rpx;
				left: 30rpx;
				display: flex;
				align-items: center;
				font-size: 10px;
				color:#b5b6b9;
				
				image {
					width: 26rpx;
					height: 26rpx;
					margin-right: 6rpx;
				}
			}

			.action-btn {
				line-height: 1;
				padding: 20rpx 26rpx;
				color: #333;
				display: inline-block;
				border-radius: $border-radius;
				background: #fff;
				border: 2rpx solid #999;
				font-size: $font-size-tag;
				margin-left: 10rpx;
			}
		}
	}
}

/deep/ #action-date .uni-countdown .uni-countdown__number  {
	border: none !important;
	padding: 0 !important;
	margin: 0 !important;
}

.order-batch-action {
	position: fixed;
	z-index: 5;
	left: 0;
	bottom: 0;
	width: 100vw;
	height: 100rpx;
	background: #fff;
	box-shadow: 0 0px 10px rgba(0, 0, 0, 0.1);
	text-align: right;

	&.bottom-safe-area {
		padding-bottom: constant(safe-area-inset-bottom);
		padding-bottom: env(safe-area-inset-bottom);
	}

	.action-btn {
		height: 68rpx;
		line-height: 68rpx;
		background: #fff;
		padding: 0 40rpx;
		display: inline-block;
		text-align: center;
		margin: 16rpx 20rpx 16rpx 0;
		border-radius: $border-radius;
		border: 1px solid #ffffff;

		&.white {
			height: 68rpx;
			line-height: 68rpx;
			color: #333;
			border: 1px solid #999;
			background: #fff;
		}
	}
}
.sku {
	display: flex;
	line-height: 1;
	margin-top: 10rpx;
}
.goods-spec {
	color: $color-tip;
	font-size: $font-size-goods-tag;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	flex: 1;
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
