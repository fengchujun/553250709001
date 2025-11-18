<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<mescroll-uni @getData="getData" class="member-point">
			<view slot="list">
				<view class="detailed-wrap" v-if="withdrawList.length">
					<view class="cont">
						<view class="detailed-item" v-for="(item, index) in withdrawList" :key="index" @click="toDetail(item.id)">
							<view class="info">
								<view class="event">{{ item.transfer_type=='balance'&&'余额' || item.transfer_type=='alipay'&&'支付宝' || item.transfer_type=='bank'&&'银行卡' || item.transfer_type=='wechatpay'&&'微信' }}</view>
								<view>
									<text class="time">{{ $util.timeStampTurnTime(item.create_time) }}</text>
								</view>
							</view>
							<view class="right-wrap">
								<view class="num color-base-text">￥{{ item.money }}</view>
								<!-- <view class="status-name" :style="item.status == -1 || item.status == -2 ? 'color:red;' : ''">{{ item.status_name }}</view> -->
								<!-- #ifdef H5 -->
									<view class="actions" v-if="$util.isWeiXin() && isWithdrawWechat && item.transfer_type == 'wechatpay' && item.status == 2" @click.stop="toTransfer(item.id)">
										<view class="act-btn">收款</view>
									</view>
									<view class="status-name" v-else :style="item.status == -1 || item.status == -2 ? 'color:red;' : ''">{{ item.status_name }}</view>
								<!-- #endif -->
								
								<!-- #ifdef MP-WEIXIN -->
									<view class="actions" v-if="isWithdrawWechat && item.transfer_type == 'wechatpay' && item.status == 2" @click.stop="toTransfer(item.id)">
										<view class="act-btn">收款</view>
									</view>
									<view class="status-name" v-else :style="item.status == -1 || item.status == -2 ? 'color:red;' : ''">{{ item.status_name }}</view>
								<!-- #endif -->
							</view>
							<view v-if="item.status == -1" class="fail-reason">
								拒绝原因：{{ item.refuse_reason }}
							</view>
							<view v-if="item.status == -2" class="fail-reason">
								失败原因：{{ item.fail_reason }}
							</view>
						</view>
					</view>
				</view>
				<ns-empty v-else :isIndex="false" text="暂无提现记录"></ns-empty>
			</view>
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
			withdrawList: [],
			emptyShow: false,
			isWithdrawWechat: 0
		};
	},
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

		if(this.fenxiaoWords && this.fenxiaoWords.withdraw)this.$langConfig.title(this.fenxiaoWords.withdraw + '明细');
		
		if (!this.storeToken) {
			this.$nextTick(() => {
				this.$refs.login.open('/pages_promotion/fenxiao/withdraw_list');
			});
		}
		this.getWithdrawConfig()
	},
	mixins: [fenxiaoWords],
	methods: {
		toTransfer(id) {
			this.$util.redirectTo('/pages_promotion/fenxiao/withdrawal_detail', {
				id: id,
				action: 'transfer'
			});
		},
		getWithdrawConfig() {
			this.$api.sendRequest({
				url: '/wechatpay/api/transfer/getWithdrawConfig',
				success: res => {
					if (res.code == 0){
						this.isWithdrawWechat = res.data.transfer_type;
					}
				},
			});
		},
		//获得列表数据
		getData(mescroll) {
			this.emptyShow = false;
			if (mescroll.num == 1) {
				this.withdrawList = [];
			}
			this.$api.sendRequest({
				url: '/fenxiao/api/withdraw/page',
				data: {
					page_size: mescroll.size,
					page: mescroll.num,
				},
				success: res => {
					this.emptyShow = true;
					let newArr = [];
					let msg = res.message;
					if (res.code == 0 && res.data && res.data.list) {
						newArr = res.data.list;
					} else {
						this.$util.showToast({
							title: msg
						});
					}
					mescroll.endSuccess(newArr.length);
					//设置列表数据
					if (mescroll.num == 1) this.withdrawList = []; //如果是第一页需手动制空列表
					this.withdrawList = this.withdrawList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				},
				fail: res => {
					mescroll.endErr();
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		toDetail(id) {
			this.$util.redirectTo('/pages_promotion/fenxiao/withdrawal_detail', {
				id: id
			});
		}
	}
};
</script>

<style lang="scss">
.account-box {
	width: 100vw;
	padding: 30rpx;
	box-sizing: border-box;
	padding-bottom: 10rpx;
	display: flex;
	justify-content: space-between;
	align-items: center;

	.tit {
		color: #fff;
		line-height: 1;
	}

	.iconmn_jifen_fill {
		font-size: 60rpx;
		color: #fff;
	}

	.point {
		color: #fff;
		font-size: 60rpx;
		margin-left: 10rpx;
	}
}

.detailed-wrap {
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

		.detailed-item {
			padding: $padding 10rpx;
			margin: 0 $margin-both;
			border-bottom: 2rpx solid #eee;
			position: relative;

			&:last-of-type {
				border-bottom: none;
			}

			.info {
				padding-right: 180rpx;

				.event {
					font-size: $font-size-base;
					line-height: 1.3;
				}

				.time {
					font-size: $font-size-base;
					color: $color-tip;
				}
			}

			.right-wrap {
				position: absolute;
				right: 0;
				top: 0;
				text-align: right;

				.num {
					font-size: $font-size-toolbar;
				}
			}
			.fail-reason{
				font-size: $font-size-base;
				color:$color-tip;
			}
			.actions{
				display: flex;
				justify-content: flex-end;
				.act-btn{
					color: #fff;
					background-color: $base-color;
					font-size: $font-size-base;
					line-height: 1;
					padding: 10rpx $padding;
					border-radius: $border-radius;
				}
			}
		}
	}
}
</style>
