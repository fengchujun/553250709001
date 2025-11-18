<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<mescroll-uni ref="mescroll" @getData="getData" v-if="storeToken">
			<block slot="list">
				<view class="nc-info-list-content">
					<view class="list-item balance-item" @click="setBalanceDefault()" v-if="type=='fenxiao' && payList && payList.balance">
						<view class="item-top">
							<view class="item-left">
								<view class="title-text">提现到余额</view>
							</view>
						</view>
					</view>
					<block v-if="dataList.length > 0">
						<view class="list-item" v-for="(item, index) in dataList" :key="index">
							<view class="item-top">
								<view class="item-left">
									<view class="title-text">{{ item.withdraw_type_name }}</view>
									<view class="info-content">
										<text class="top-title">{{ item.realname }}</text>
										<text class="top-num">{{ item.mobile }}</text>
									</view>
									<view class="content-bottom">
										<block v-if="item.withdraw_type == 'alipay'">
											提现账号：{{ item.bank_account }}
										</block>
										<block v-if="item.withdraw_type == 'bank'">
											银行名称 ：{{ item.branch_bank_name }}
										</block>
									</view>
								</view>
								<view class="item-btn" @click.stop="editAccount('edit', item.id)">修改</view>
							</view>

							<view class="item-bottom">
								<view class="account-default" @click="setDefault(item.id,item.is_default)">
									<text class="default">设为默认账户</text>
									<switch v-if="item.is_default == 1" checked disabled style="transform:scale(0.7)" :color="themeStyle.main_color" />
									<switch v-else style="transform:scale(0.7)" :color="themeStyle.main_color" />
								</view>
								<view class="account-btn">
									<text class="delete" v-if="item.is_default != 1" @click="deleteAccount(item.id)">
										<text class="iconfont iconicon7"></text>
									</text>
								</view>
							</view>
						</view>
					</block>
				</view>
				<view v-if="dataList.length <= 0 && (type != 'fenxiao'  || (type == 'fenxiao' && payList && !payList.balance))" class="empty-box">
					<image :src="$util.img('public/uniapp/member/account/empty.png')" mode="widthFix"></image>
					<view class="tips">暂无账户信息，请添加</view>
					<button type="primary" class="add-account" @click="editAccount('add')">{{ $lang('newAddAccount') }}</button>
				</view>
			</block>
		</mescroll-uni>
		<view class="btn-add" v-if="dataList.length > 0 || (type == 'fenxiao' && payList && payList.balance)">
			<button class="add-account" type="primary" @click="editAccount('add')">{{ $lang('newAddAccount') }}</button>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
		<ns-login ref="login"></ns-login>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				dataList: [], //账号列表
				back: '', // 返回页
				redirect: 'redirectTo', // 跳转方式
				type: 'member',
				payList: null
			};
		},
		onLoad(option) {
			if (option.back) this.back = option.back;
			if (option.type) this.type = option.type;
			if (option.redirect) this.redirect = option.redirect;
		},
		onShow() {
			if (this.storeToken) {
				this.getTransferType();
				if (this.$refs.mescroll) this.$refs.mescroll.refresh();
			} else {
				this.$nextTick(() => {
					this.$refs.login.open('/pages_tool/member/account');
				});
			}
		},
		methods: {
			// 编辑提现账户信息
			editAccount(type, id) {
				let data = {};
				data.type = this.type;
				if (type == 'edit') data.id = id;
				if (this.back) data.back = this.back;
				this.$util.redirectTo('/pages_tool/member/account_edit', data);
			},
			deleteAccount(id) {
				uni.showModal({
					title: '操作提示',
					content: '确定要删除该账户吗？',
					success: res => {
						if (res.confirm) {
							this.$api.sendRequest({
								url: '/api/memberbankaccount/delete',
								data: {
									id: id
								},
								success: result => {
									if (result.code == 0) {
										this.$util.showToast({
											title: '删除成功'
										});
										this.$refs.mescroll.refresh();
									} else {
										this.$util.showToast({
											title: '删除失败'
										});
									}
								}
							});
						}
					}
				});
			},
			setDefault(id, is_default) {
				if (is_default == 1) return;
				this.$api.sendRequest({
					url: '/api/memberbankaccount/setdefault',
					data: {
						id
					},
					success: res => {
						if (res.data >= 0) {
							if (this.back != '') {
								this.$util.redirectTo(this.back, {}, this.redirect);
							} else {
								if (this.$refs.loadingCover) this.$refs.loadingCover.show();
								this.dataList = [];
								this.$refs.mescroll.refresh();
							}
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			},
			setBalanceDefault() {
				this.$util.redirectTo(this.back, {
					'is_balance': 1
				}, this.redirect);
			},
			getData(mescroll) {
				this.$api.sendRequest({
					url: '/api/memberbankaccount/page',
					data: {
						page_size: mescroll.size,
						page: mescroll.num
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
						if (mescroll.num == 1) this.dataList = []; //如果是第一页需手动制空列表
						this.dataList = this.dataList.concat(newArr); //追加新数据

						let withdrawType = {
							bank: '银行',
							alipay: '支付宝',
							wechatpay: '微信'
						};

						this.dataList.forEach(item => {
							item.withdraw_type_name = withdrawType[item.withdraw_type] ? withdrawType[item.withdraw_type] : '';
						});
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail: res => {
						mescroll.endErr();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			getTransferType() {
				let url = this.type == "member" ? "/api/memberwithdraw/transferType" : "/fenxiao/api/withdraw/transferType";
				this.$api.sendRequest({
					url: url,
					success: res => {
						if (res.code >= 0 && res.data) {
							this.payList = res.data;
						}
					}
				});
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
	.empty-box {
		width: 100vw;
		height: 100vh;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;

		image {
			width: 50%;
		}

		.tips {
			color: #999999;
			font-size: 26rpx;
		}

		.get-account,
		.add-account {
			width: 50%;
			height: 78rpx;
			line-height: 78rpx;
			border-radius: 78rpx;
			margin-top: 50rpx;
			font-weight: 600;
		}

		.get-account {
			width: 50%;
			background: #fff;
			color: $base-color;
			border: 2rpx solid $base-color;
			margin-top: 20rpx;
			box-sizing: border-box;
		}
	}

	.mescroll-downwarp+.empty-box {
		height: calc(100vh - 260rpx);
	}


	.btn-add {
		margin-top: 60rpx;
		bottom: 0;
		width: 100%;
		background: #fff;
		position: fixed;
		padding: 0 30rpx;
		box-sizing: border-box;
		padding-bottom: constant(safe-area-inset-bottom);
		padding-bottom: env(safe-area-inset-bottom);
		z-index: 10;

		.add-account {
			height: 80rpx;
			line-height: 80rpx;
			border-radius: 80rpx;
			margin: 30rpx 0 30rpx;
			font-size: $font-size-toolbar;

			text {
				margin-right: 10rpx;
				font-size: $font-size-base;
			}
		}
	}

	.zw {
		margin-top: 250rpx;
	}

	.list-item {
		margin: 0 0;
		padding: 24rpx $margin-both;
		box-sizing: border-box;
		display: flex;
		flex-direction: column;
		background-color: #fff;
		margin-bottom: 18rpx;
		border-radius: 10rpx;
		overflow: hidden;
		position: relative;

		&.balance-item {
			.item-top {
				border: none;
				padding: 0;
			}
		}

		&:first-child {
			margin-top: 18rpx;
		}

		.item-mr {
			font-size: $font-size-activity-tag;
			color: #fff;
			height: 150rpx;
			width: 150rpx;
			display: flex;
			align-items: flex-end;
			justify-content: center;
			position: absolute;
			right: -90rpx;
			top: -90rpx;
			transform: rotate(45deg);
		}

		.item-top {
			border-bottom: 2rpx solid $color-line;
			padding-bottom: 26rpx;
			display: flex;
			flex-direction: row;

			.item-left {
				display: flex;
				flex-direction: column;
				width: calc(100% - 100rpx);

				.title-text {
					font-size: 30rpx;
					font-weight: bold;
				}

				.info-content {
					display: flex;

					.top-title {
						font-size: 26rpx;
						margin-right: 15rpx;
					}

					.top-num {
						font-size: 26rpx;
					}
				}

				.content-bottom {
					font-size: 26rpx;
					height: 50rpx;
				}
			}

			.item-btn {
				width: 100rpx;
				display: flex;
				align-items: center;
				color: #999;
				font-size: 24rpx;
				justify-content: flex-end;
			}

		}

		.item-bottom {
			display: flex;
			justify-content: space-between;
			padding-top: 24rpx;

			.account-default {
				display: flex;
				align-items: center;
				font-size: 24rpx;
				line-height: 1;
				color: #666666;

				.default {}

				.iconfont {
					line-height: 1;
				}
			}

			.account-btn {
				font-size: $font-size-base;
				line-height: 1;
				display: flex;
				align-items: center;

				.edit {
					text {
						vertical-align: center;
						margin-right: 10rpx;
						font-size: 30rpx;
					}
				}

				.delete {
					background: #F1F1F1;
					justify-content: center;
					align-items: center;
					border-radius: 50%;
					padding: 10rpx;
					text-align: center;
					width: 48rpx;
					height: 48rpx;
					box-sizing: border-box;

					text {
						font-size: 26rpx;
					}
				}
			}
		}
	}
</style>
<style>
	/deep/ .mescroll-upwarp {
		padding-bottom: 150rpx;
	}
</style>
<style>
	.item-bottom>>>.uni-switch-wrapper .uni-switch-input {
		height: 48rpx !important;
		width: 88rpx !important;
	}

	.item-bottom>>>.uni-switch-wrapper .uni-switch-input:after {
		height: 44rpx !important;
		width: 44rpx !important;
	}

	.item-bottom>>>.uni-switch-wrapper .uni-switch-input:before {
		background-color: #EDEDED !important;
		height: 44rpx !important;
		width: 90rpx !important;
	}
</style>