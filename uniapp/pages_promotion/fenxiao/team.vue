<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<view class="team-cate" v-if="storeToken && levelNum > 1">
			<block v-for="(item, index) in levelList" :key="index">
				<view class="cate-li" :class="{ 'active color-base-text color-base-border': currentLevel == item.level }" @click="selectLevel(item.level)">{{ item.name }}</view>
			</block>
		</view>
		<mescroll-uni ref="mescroll" @getData="getData" :top="levelNum > 1 ? 90 : 0" class="member-point" :size="8" v-if="storeToken">
			<block slot="list">
				<view class="team-li" v-for="(item, index) in teamList" :key="index" v-if="teamList.length != 0" @click="toFenxiaoOrder(item)">
					<view class="li-box" :class="{ active: index + 1 == teamList.length }">
						<image v-if="item.headimg" :src="$util.img(item.headimg)" @error="imageError(index)" mode="aspectFill"></image>
						<image v-else :src="$util.getDefaultImage().head"></image>
						<view class="member-info">
							<view class="member-name">
								<block v-if="item.is_fenxiao">
									<view class="left">
										<view class="flex-box">
											<view class="name">{{ item.nickname }}</view>
											<view class="title  color-base-border color-base-text">{{ fenxiaoWords.fenxiao_name }}</view>
										</view>
										<view class="color-tip font-size-goods-tag">加入时间：{{ $util.timeStampTurnTime(item.bind_fenxiao_time, 'Y-m-d') }}</view>
									</view>
									<view class="consume-info">
										<view>
											<text>{{ item.one_child_num }}</text>
											人
										</view>
										<view>
											<text>{{ item.order_num }}</text>
											单
										</view>
										<view>
											<text>{{ item.order_money | moneyFormat }}</text>
											元
										</view>
									</view>
								</block>
								<block v-else>
									<view class="left">
										<view class="flex-box">
											<view class="name font-size-tag">
												<text>{{ item.nickname }}</text>
											</view>
										</view>
										<view class="color-tip font-size-goods-tag">加入时间：{{ $util.timeStampTurnTime(item.bind_fenxiao_time, 'Y-m-d') }}</view>
									</view>
									<view class="consume-info">
										<view>
											<text>0</text>
											人
										</view>
										<view>
											<text>{{ item.order_num }}</text>
											单
										</view>
										<view>
											<text>{{ item.order_money | moneyFormat }}</text>
											元
										</view>
									</view>
								</block>
							</view>
						</view>
					</view>
				</view>
				<block v-if="teamList.length == 0 && emptyShow"><ns-empty text="暂无数据" :isIndex="false"></ns-empty></block>
			</block>
		</mescroll-uni>

		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import fenxiaoWords from 'common/js/fenxiao-words.js';
export default {
	data() {
		return {
			levelList: [
				{
					name: '一级',
					level: 1
				},
				{
					name: '二级',
					level: 2
				}
			],
			currentLevel: 1,
			teamList: [],
			emptyShow: false,
			levelNum: 0,
		};
	},
	mixins: [fenxiaoWords],
	onShow() {
		setTimeout( () => {
			if (!this.addonIsExist.fenxiao) {
				this.$util.showToast({
					title: '商家未开启分销',
					mask: true,
					duration: 2000
				});
				setTimeout(() => {
					this.$util.redirectTo('/pages/index/index');
				}, 2000);
			}
		},1000);

		if (this.fenxiaoWords && this.fenxiaoWords.my_team) this.$langConfig.title(this.fenxiaoWords.my_team);
		this.getFenXiaoLevel();

		if (!this.storeToken) {
			this.$nextTick(() => {
				this.$refs.login.open('/pages_promotion/fenxiao/team');
			});
		}
	},
	methods: {
		getData(mescroll) {
			this.emptyShow = false;
			if (mescroll.num == 1) {
				this.teamList = [];
			}
			this.$api.sendRequest({
				url: '/fenxiao/api/fenxiao/team',
				data: {
					page_size: mescroll.size,
					page: mescroll.num,
					level: this.currentLevel
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
					if (mescroll.num == 1) this.teamList = []; //如果是第一页需手动制空列表
					this.teamList = this.teamList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				},
				fail: res => {
					mescroll.endErr();
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		imageError(e) {
			this.teamList[e].headimg = this.$util.getDefaultImage().head;
			this.$forceUpdate();
		},
		selectLevel(e) {
			this.currentLevel = e;
			this.$refs.mescroll.refresh();
		},
		toFenxiaoOrder(item) {
			if (item.fenxiao_id) {
				this.$util.redirectTo('/pages_promotion/fenxiao/relation', { fenxiao_id: item.fenxiao_id });
			} else {
				this.$util.redirectTo('/pages_promotion/fenxiao/relation', { sub_member_id: item.member_id });
			}
		},
		async getFenXiaoLevel() {
			let res = await this.$api.sendRequest({
				url: '/fenxiao/api/config/basics',
				async: false,
				success: res => {}
			});
			if (res.code == 0 && res.data) {
				this.levelNum = res.data.level;
			}
		}
	},
	watch: {
		storeToken: function(nVal, oVal) {
			if (nVal) {
				this.$refs.mescroll.refresh();
			}
		}
	}
};
</script>

<style lang="scss">
.team-cate {
	padding: 0 30rpx;
	width: calc(100%);
	height: 90rpx;
	display: flex;
	box-sizing: border-box;
	background: #ffffff;
	position: fixed;
	left: 0;
	top: var(--window-top);

	.cate-li {
		flex: 1;
		justify-content: center;
		text-align: center;
		align-items: center;
		display: inline-block;
		line-height: 90rpx;
		height: 100%;
		font-size: 30rpx;

		&.active {
			box-sizing: border-box;
			border-bottom: 4rpx solid;
		}
	}
}

.team-member {
	width: 100%;
	height: 70rpx;
	line-height: 70rpx;
	color: $color-tip;
	padding: 0 $padding;
	box-sizing: border-box;
}

.team-li {
	margin: $margin-updown $margin-both;
	padding: $margin-both;
	box-sizing: border-box;
	background: #fff;
	margin-bottom: 20rpx;
	border-radius: 10rpx;

	.li-box {
		display: flex;
		box-sizing: border-box;
		align-items: center;

		image {
			width: 90rpx;
			height: 90rpx;
			border-radius: 50%;
		}

		.member-info {
			flex: 1;
			padding-left: $padding;
			box-sizing: border-box;
			display: flex;
			flex-direction: column;
			justify-content: center;

			.member-name {
				display: flex;
				justify-content: space-between;
				align-items: center;
				font-size: $font-size-base;

				.left {
					width: 0;
					flex: 1;

					.flex-box {
						display: flex;
						align-items: center;
						margin-bottom: 6rpx;
					}

					.name {
						overflow: hidden;
						text-overflow: ellipsis;
						white-space: nowrap;
					}

					.title {
						padding: 4rpx 16rpx;
						justify-content: center;
						align-items: center;
						text-align: center;
						font-size: $font-size-activity-tag;
						border-radius: 4rpx;
						margin-left: 10rpx;
						line-height: 1;
						border: 2rpx solid;
						color: #fff;
					}
				}

				.consume-info {
					text-align: right;

					text {
						margin-right: 6rpx;
					}

					view {
						line-height: 1.5;
						font-size: 24rpx;
					}
				}
			}

			.member-date {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-top: $padding;

				view {
					width: 50%;
					height: 100%;
					text-align: left;
					line-height: 1;

					text {
						font-size: $font-size-tag;
						color: $color-tip;
					}

					.tit {
						color: $color-tip;
					}
				}
			}
		}

		.btn-see {
			display: flex;
			flex-direction: row-reverse;
		}
	}
	.order-box-btn {
		display: inline-block;
		line-height: 56rpx;
		padding: 0 30rpx;
		font-size: 26rpx;
		color: #303133;
		border: 2rpx solid #999;
		-webkit-box-sizing: border-box;
		box-sizing: border-box;
		-webkit-border-radius: $border-radius;
		border-radius: $border-radius;
		margin-top: 30rpx;
	}
	.li-box.active {
		border: none;
	}
}
</style>
