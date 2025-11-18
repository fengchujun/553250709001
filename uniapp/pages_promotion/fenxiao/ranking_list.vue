<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="container">
		<mescroll-uni ref="mescroll" @getData="getData" top="0" :size="10">
			<view slot="list">
				<block v-if="list.length != 0">
					<view class="banner" :style="{background: 'url('+ $util.img('public/uniapp/fenxiao/index/header_bg.png') +') no-repeat top left / 100% 100%'}">
						<view class="info">
							<view class="info-pic">
								<image :src="info.headimg ? $util.img(info.headimg) : $util.getDefaultImage().head" @error="info.headimg = $util.getDefaultImage().head" mode="aspectFill"/>
							</view>
							<view class="member-info">
								<view class="rank-info-box">
									<text class="name">{{info.nickname}}</text>
								</view>
								<view class="withdrawal" @click="$util.redirectTo('/pages_promotion/fenxiao/withdraw_apply')" v-if="type == 'profit'">点击提现</view>
								<view class="withdrawal" @click="$util.redirectTo('/pages_promotion/fenxiao/team')" v-if="type == 'invited_num'">我的团队</view>
							</view>
						</view>
					</view>

					<view class="fenxiao-team" v-if="type == 'profit'">
						<view class="fenxiao-index-other">
							<view class="all-money-item">
								<view class="img-wrap">
									<text class="iconfont icon-fenxiao"></text>
								</view>
								<view class="all-money-tit-wrap">
									<text class="all-money-tit">分销佣金</text>
									<text class="all-money-num">{{ info.today_commission}}元</text>
								</view>
							</view>
						</view>
						<view class="fenxiao-index-other">
							<view class="all-money-item">
								<view class="img-wrap">
									<text class="iconfont icon-baixingbeng"></text>
								</view>
								<view class="all-money-tit-wrap">
									<text class="all-money-tit">佣金排行</text>
									<text class="all-money-num">您排行第{{ ranking }}名</text>
								</view>
							</view>
						</view>
					</view>
					<view class="fenxiao-team" v-if="type == 'invited_num'">
						<view class="fenxiao-index-other">
							<view class="all-money-item">
								<view class="img-wrap">
									<text class="iconfont icon-huodongtuiyan"></text>
								</view>
								<view class="all-money-tit-wrap">
									<text class="all-money-tit">推广人数</text>
									<text class="all-money-num">{{ info.one_child_num}}人</text>
								</view>
							</view>
						</view>
						<view class="fenxiao-index-other">
							<view class="all-money-item">
								<view class="img-wrap">
									<text class="iconfont icon-baixingbeng"></text>
								</view>
								<view class="all-money-tit-wrap">
									<text class="all-money-tit" v-if="type == 'invited_num'">推广排行</text>
									<text class="all-money-num">您排行第{{ ranking }}名</text>
								</view>
							</view>
						</view>
					</view>

					<view class="title-rakn-text"  v-if="type == 'profit'">佣金排行</view>
					<view class="title-rakn-text"  v-if="type == 'invited_num'">推广排行</view>

					<view class="ranking-list">
						<view class="ranking-item" v-for="(item, index) in list" :key="index">
							<view class="ranking price-font">{{ index + 1 }}</view>
							<view class="content">
								<view class="head-img">
									<image :src="item.headimg ? $util.img(item.headimg) : $util.getDefaultImage().head" @error="item.headimg = $util.getDefaultImage().head" mode="aspectFill"/>
								</view>
								<view class="nickname">{{ item.nickname }}</view>
							</view>
							<view class="price-font price-style" v-if="type == 'profit'">￥{{ item.total_commission|moneyFormat }}</view>
							<view class="price-font price-style" v-if="type == 'invited_num'">{{ item.child_num }}人</view>
						</view>
					</view>
				</block>
				<block v-if="list.length == 0 && emptyShow">
					<ns-empty text="暂无数据" :isIndex="false"></ns-empty>
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
				list: [],
				emptyShow: false,
				type: '',
				ranking: 0,
				info: {}
			}
		},
		onLoad(data) {
			this.type = data.type;
			this.getRanking();
			this.getFenxiaoDetail();
		},
		methods: {
			getData(mescroll) {
				this.emptyShow = false;
				if (mescroll.num == 1) {
					this.list = [];
				}
				this.$api.sendRequest({
					url: '/fenxiao/api/fenxiao/rankinglist',
					data: {
						page_size: mescroll.size,
						page: mescroll.num,
						type: this.type
					},
					success: res => {
						this.emptyShow = true;
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
						if (mescroll.num == 1) this.list = []; //如果是第一页需手动制空列表
						this.list = this.list.concat(newArr); //追加新数据
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail: res => {
						mescroll.endErr();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			getRanking() {
				this.$api.sendRequest({
					url: '/fenxiao/api/fenxiao/ranking',
					data: {
						type: this.type
					},
					success: res => {
						if (res.code >= 0) {
							this.ranking = res.data;
						}
					}
				})
			},
			getFenxiaoDetail() {
				this.$api.sendRequest({
					url: '/fenxiao/api/fenxiao/detail',
					success: res => {
						if (res.data) {
							this.info = res.data;
						}
					},
				});
			},
		}
	}
</script>

<style lang="scss">
	.container {
		width: 100vw;
		height: 100vh;
	}

	.banner {
		width: 100%;
		height: 200rpx;
		// background: $base-color;
	}

	.info {
		width: 100%;
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 50rpx 80rpx 0;
		box-sizing: border-box;

		.info-pic {
			width: 100rpx;
			height: 100rpx;
			border-radius: 50%;
			border: 4rpx solid #fff;
			position: relative;

			image {
				width: 100%;
				height: 100%;
				border-radius: 50%;
			}

		}

		.member-info {
			flex: 1;
			width: 0;
			margin-left: 32rpx;
			display: flex;

			view {
				color: #fff;
			}

			.rank-info-box {
				line-height: 1;
				flex: 1;
			}

			.name {
				font-size: 32rpx;
				font-weight: 600;
				color: #FFFFFF;
			}

			.withdrawal {
				border-radius: 4px;
				line-height: 23px;
				margin: auto;
				text-align: center;
				font-size: 11px;
			}

		}

	}

	.fenxiao-team {
		display: flex;
		width: 100%;
		margin-top: 22rpx;

		.fenxiao-index-other {
			margin: 0 24rpx 20rpx 24rpx;
			border-radius: 16rpx;
			background-color: #ffffff;
			padding: 30rpx 0;
			flex: 1;

			&:last-child {
				margin-left: 0;
			}

			.all-money-item {
				margin: 0 30rpx;
				display: flex;
				font-size: $font-size-tag;
				align-items: center;

				.img-wrap {
					display: flex;
					justify-content: center;
					align-items: center;
					width: 70rpx;
					height: 70rpx;

					image {
						width: 100%;
						height: 100%;
					}
				}

				.all-money-tit-wrap {
					flex: 1;
					margin-left: 24rpx;
					display: flex;
					flex-direction: column;
					height: 70rpx;

					.all-money-tit {
						line-height: 1;
						color: $color-title;
						font-size: $font-size-base;
						flex: 1;
					}

					.all-money-num {
						color: $color-tip;
						font-size: 24rpx;
						line-height: 1;
					}
				}
			}
		}
	}

	.icon-wenxiao {
		text-align: center;
		font-size: 50rpx;
		color: var(--base-color) !important;
	}

	.number {
		font-weight: 600;
		text-align: center;
	}

	.info-text {
		font-size: 20rpx;
		text-align: center;
	}

	.info-title {
		font-size: 50rpx;
		font-weight: 900;
		color: #f5f5f5;
		text-align: center;
	}

	.info-rank {
		color: #f5f5f5;
		margin-top: 10rpx;
	}

	.title-rakn-text {
		text-align: center;
		font-size: 30rpx;
		font-weight: 900;
	}

	.ranking-list {
		transform: translateY(-120rpx);
		margin: 200rpx 24rpx;
		background: #fff;
		border-radius: 16rpx;
		padding: 10rpx 20rpx;
		margin-top: 140rpx;

		.ranking-item {
			display: flex;
			align-items: center;
			padding: 20rpx 0;
			border-bottom: 2rpx solid #f5f5f5;

			&:last-child {
				border-bottom: 0;
			}

			.ranking {
				width: 60rpx;
				height: 60rpx;
				display: flex;
				align-items: center;
				justify-content: center;
				box-sizing: border-box;
				font-size: 24rpx;
			}

			&:nth-child(1) .ranking {
				background: rgb(249, 186, 1);
				border-radius: 50%;
				border: 10rpx solid rgb(254, 220, 92);
			}

			&:nth-child(2) .ranking {
				background: rgb(172, 185, 194);
				border-radius: 50%;
				border: 10rpx solid rgb(215, 223, 229);
			}

			&:nth-child(3) .ranking {
				background: rgb(211, 163, 136);
				border-radius: 50%;
				border: 10rpx solid rgb(235, 201, 190);
			}

			.content {
				flex: 1;
				width: 0;
				padding: 0 20rpx;
				display: flex;
				align-items: center;

				.head-img {
					width: 90rpx;
					height: 90rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					border-radius: 50%;
					overflow: hidden;

					image {
						width: 100%;
						height: 100%;
					}
				}

				.nickname {
					color: #333;
					margin: 0 20rpx;
					font-size: 28rpx;
				}
			}
		}
	}
</style>
