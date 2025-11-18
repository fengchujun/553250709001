<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<mescroll-uni ref="mescroll" @getData="getListData" v-if="storeToken">
			<block slot="list">
				<view class="address-list">
					<template v-if="addressList.length !== 0">
						<view class="address-item" v-for="(item, index) in addressList" :key="index">
							<view class="address-item-top" v-if="localType == 2 && item.local_data">
								<view class="address-item-left">
									<view class="address-top-info">
										<view class="address-name">{{ item.name }}</view>
										<view class="address-tel">{{ item.mobile }}</view>
										<view class="address-status" v-if="localType == 2 && item.local_data">{{ item.local_data }}</view>
									</view>
									<view class="address-info">{{ item.full_address }}{{ item.address }}</view>
								</view>
								<view class="address-item-edit" @click="addAddress('edit', item.id)">
									{{ $lang('modify') }}
								</view>
							</view>

							<view class="address-item-top" v-else @click="setDefault(item.id)">
								<view class="address-item-left">
									<view class="address-top-info">
										<view class="address-name">{{ item.name }}</view>
										<view class="address-tel">{{ item.mobile }}</view>
									</view>
									<view class="address-info">{{ item.full_address }}{{ item.address }}</view>
								</view>
								<view class="address-item-edit" @click.stop="addAddress('edit', item.id)">
									{{ $lang('modify') }}
								</view>
							</view>

							<view class="address-item-bottom">
								<view class="address-default" @click="setDefault(item.id,item.is_default)">
									<text class="default" v-if="localType == 2 && item.local_data">设为默认地址</text>
									<text class="default" v-else>设为默认地址</text>
									<switch v-if="item.is_default == 1" checked disabled style="transform:scale(0.7)" :color="themeStyle.main_color" />
									<switch v-else style="transform:scale(0.7)" :color="themeStyle.main_color" />
								</view>
								<view class="address-btn">
									<text class="delete" v-if="item.is_default != 1" @click="deleteAddress(item.id, item.is_default)">
										<text class="iconfont icon-icon7"></text>
									</text>
								</view>
							</view>
						</view>

					</template>

					<view v-if="addressList.length == 0 && showEmpty" class="empty-box">
						<image :src="$util.img('public/uniapp/member/address/empty.png')" mode="widthFix"/>
						<view class="tips">暂无收货地址，请添加</view>

						<button type="primary" class="add-address" @click="addAddress('add')">{{ $lang('newAddAddress') }}</button>

						<!-- #ifdef H5 -->
						<button type="primary" class="get-address" @click="getChooseAddress()" v-if="$util.isWeiXin() && local != 1">{{ $lang('getAddress') }}</button>
						<!-- #endif -->
						<!-- #ifdef MP-WEIXIN || MP-BAIDU || MP-TOUTIAO -->
						<button type="primary" class="get-address" @click="getChooseAddress()" v-if="local != 1">{{ $lang('getAddress') }}</button>
						<!-- #endif -->
					</view>
				</view>
			</block>
		</mescroll-uni>

		<view class="btn-add" v-if="addressList.length !== 0">
			<!-- #ifdef MP-WEIXIN -->
			<view class="wx-add" @click="getChooseAddress()" v-if="local != 1">
				<text>{{ $lang('getAddress') }}</text>
			</view>
			<!-- #endif -->
			<!-- #ifdef H5 -->
			<button type="primary" class="add-address" @click="getChooseAddress()" v-if="$util.isWeiXin() && local != 1">{{ $lang('getAddress') }}</button>
			<!-- #endif -->
			<button type="primary" class="add-address" @click="addAddress('add')">
				<text class="iconfont icon-add1"></text>
				{{ $lang('newAddAddress') }}
			</button>
		</view>

		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
	import {
		Weixin
	} from 'common/js/wx-jssdk.js';

	export default {
		data() {
			return {
				addressList: [],
				back: '', // 返回页
				redirect: 'redirectTo', // 跳转方式
				isIndex: false,
				showEmpty: false,
				local: 0, //定位是否显示
				localType: 1,
			};
		},
		onLoad(option) {
			if (option.back) {
				this.back = option.back;
				uni.setStorageSync('addressBack', option.back);
			} else {
				if (uni.getStorageSync('addressBack')) {
					this.back = uni.getStorageSync('addressBack');
					uni.removeStorageSync('addressBack');
				}
			}
			if (option.redirect) this.redirect = option.redirect;
			if (option.local) this.local = option.local;
			if (option.type) this.localType = option.type;
		},
		onShow() {
			uni.removeStorageSync('addressInfo');

			if (this.storeToken) {
				if (this.$refs.mescroll) this.$refs.mescroll.refresh();
			} else {
				this.$nextTick(() => {
					this.$refs.login.open('/pages_tool/member/address');
				});
			}
		},
		methods: {
			getListData(mescroll) {
				let storeId = 0;
				let delivery = uni.getStorageSync('delivery');
				if (delivery && delivery.delivery_type == 'local') {
					storeId = delivery.store_id || 0;
				}
				this.showEmpty = false;
				this.$api.sendRequest({
					url: '/api/memberaddress/page',
					data: {
						page: mescroll.num,
						page_size: mescroll.size,
						type: this.localType,
						store_id: storeId
					},
					success: res => {
						this.showEmpty = true;
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
						if (mescroll.num == 1) this.addressList = []; //如果是第一页需手动制空列表
						this.addressList = this.addressList.concat(newArr); //追加新数据
						this.$forceUpdate();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail: res => {
						mescroll.endErr();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},

			/* 地址跳转 */
			addAddress(type, id) {
				let data = {
					type: this.localType
				};
				if (type == 'edit') data.id = id;
				if (this.back) data.back = this.back;

				this.$util.redirectTo('/pages_tool/member/address_edit', data);
			},
			/* 删除地址信息 */
			deleteAddress(id, is_default) {
				uni.showModal({
					title: '操作提示',
					content: '确定要删除该地址吗？',
					success: res => {
						if (res.confirm) {
							if (is_default == 1) {
								this.$util.showToast({
									title: '默认地址，不能删除'
								});
								return;
							}
							this.$api.sendRequest({
								url: '/api/memberaddress/delete',
								data: {
									id: id
								},
								success: res => {
									if (res.code == 0) {
										this.$util.showToast({
											title: '删除成功'
										});
									} else {
										this.$util.showToast({
											title: '删除失败'
										});
									}
									this.$refs.mescroll.refresh();
								},
								fail: res => {
									mescroll.endErr();
								}
							});
						}
					}
				});
			},
			setDefault(id, is_default) {
				if (is_default == 1) return;
				this.$api.sendRequest({
					url: '/api/memberaddress/setdefault',
					data: {
						id
					},
					success: res => {
						if (res.data > 0) {
							this.$refs.mescroll.refresh();
							if (this.back != '') {
								// this.$util.redirectTo(this.back, {}, 'redirectTo');
								// 回退到上一个页面
								uni.navigateBack({
								    delta: 1  // delta值为1时表示回退到上一级页面
								});
							} else {
								if (this.$refs.loadingCover) this.$refs.loadingCover.show();
								this.addressList = [];
								this.$refs.mescroll.refresh();
								this.$util.showToast({
									title: '修改默认地址成功'
								});
							}
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			},
			/**
			 * 一键获取地址
			 */
			getChooseAddress() {
				// #ifdef H5
				if (this.$util.isWeiXin()) {
					if (uni.getSystemInfoSync().platform == 'ios') {
						var url = uni.getStorageSync('initUrl');
					} else {
						var url = location.href;
					}
					// 获取jssdk配置
					this.$api.sendRequest({
						url: '/wechat/api/wechat/jssdkconfig',
						data: {
							url: url
						},
						success: jssdkRes => {
							if (jssdkRes.code == 0) {
								var wxJS = new Weixin();
								wxJS.init(jssdkRes.data);
								wxJS.openAddress(res => {
									if (res.errMsg == 'openAddress:ok') {
										this.saveAddress({
											name: res.userName, // 收货人姓名,
											mobile: res.telNumber, // 手机号
											province: res.provinceName, // 省
											city: res.cityName, // 市
											district: res.countryName, // 县
											address: res.detailInfo, // 详细地址
											full_address: res.provinceName + '-' + res.cityName + '-' + res.countryName,
											is_default: 1
										});
									} else {
										this.$util.showToast({
											title: res.errMsg == 'openAddress:function not implement' ? 'PC端微信不支持一键获取地址' : res.errMsg
										});
									}
								});
							} else {
								this.$util.showToast({
									title: jssdkRes.message
								});
							}
						}
					});
				}
				// #endif

				// #ifdef MP-WEIXIN || MP-BAIDU || MP-TOUTIAO
				uni.chooseAddress({
					success: res => {
						if (res.errMsg == 'chooseAddress:ok') {
							this.saveAddress({
								name: res.userName, // 收货人姓名,
								mobile: res.telNumber, // 手机号
								province: res.provinceName, // 省
								city: res.cityName, // 市
								district: res.countyName, // 县
								address: res.detailInfo, // 详细地址
								full_address: res.provinceName + '-' + res.cityName + '-' + res.countyName,
								is_default: 1
							});
						} else {
							this.$util.showToast({
								title: res.errMsg
							});
						}
					},
					fail: res => {
						console.log('fail', res);
					}
				});
				// #endif
			},
			/**
			 * 保存微信地址
			 * @param {Object} params
			 */
			saveAddress(params) {
				this.$api.sendRequest({
					url: '/api/memberaddress/addthreeparties',
					data: params,
					success: res => {
						if (res.code >= 0) {

							if (this.back != '') {
								// this.$util.redirectTo(this.back, {}, 'redirectTo');
								// 回退到上一个页面
								uni.navigateBack({
								    delta: 1  // delta值为1时表示回退到上一级页面
								});
							} else {
								this.$refs.mescroll.refresh();
							}

						} else {
							this.$util.showToast({
								title: res.message
							});
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

<style lang="scss" scoped>
	/deep/ .fixed {
		position: relative;
		top: 0;
	}

	.cart-empty {
		padding-top: 208rpx !important;
	}

	.address-list {
		width: 100%;
		height: 100%;
		padding-bottom: 40rpx;

		.local {
			display: flex;
			align-items: center;
			margin: $margin-updown $margin-both;
			background-color: #fff;
			padding: 30rpx;
			border-radius: $border-radius;

			text {
				margin-right: 10rpx;
			}
		}

		.address-item {
			margin: $padding 0 0;
			display: flex;
			flex-direction: column;
			background-color: #ffffff;
			padding: 24rpx 30rpx;
			box-sizing: border-box;
			border-radius: 0;

			.address-item-top {
				display: flex;
				flex-direction: row;
				border-bottom: 1rpx solid $color-line;
				justify-content: space-between;
				align-items: center;

				.address-item-left {
					display: flex;
					flex-direction: column;
					width: calc(100% - 100rpx);
				}

				.address-item-edit {
					color: #999;
					font-size: 24rpx;
					width: 100rpx;
					text-align: right;
				}

				.address-top-info {
					display: flex;
					justify-content: flex-start;
					position: relative;

					.address-name {
						color: #333333;
						font-size: 30rpx;
						font-weight: bold;
					}

					.address-tel {
						color: #333333;
						font-size: 30rpx;
						margin-left: 26rpx;
						font-weight: bold;
					}

					.address-status {
						color: #f00;
						font-size: $font-size-base;
						position: absolute;
						right: 0;
					}
				}

				.address-info {
					font-size: 26rpx;
					color: #888888;
					margin-top: 10rpx;
					margin-bottom: 28rpx;
				}
			}

			.address-item-bottom {
				display: flex;
				justify-content: space-between;
				padding-top: 24rpx;

				.address-default {
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

				.address-btn {
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

		.add-address {
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

	.wx-add {
		margin-top: 30rpx;
		margin-bottom: 30rpx;
		text-align: center;
		border-radius: 80rpx;
		height: 80rpx;
		line-height: 80rpx;
		background-color: var(--main-color);
		border: 2rpx solid var(--main-color);
		color: #FFFFFF;
		font-size: $font-size-toolbar;
	}

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

		.get-address,
		.add-address {
			width: 50%;
			height: 78rpx;
			line-height: 78rpx;
			border-radius: 78rpx;
			margin-top: 50rpx;

			font-size: $font-size-toolbar;
		}

		.get-address {
			width: 50%;
			border: 2rpx solid $base-color;
			margin-top: 20rpx;
			box-sizing: border-box;
		}
	}

	.mescroll-downwarp+.empty-box {
		height: calc(100vh - 260rpx);
	}
</style>
<style>
	.address-item>>>.uni-switch-wrapper .uni-switch-input {
		height: 48rpx !important;
		width: 88rpx !important;
	}

	.address-item>>>.uni-switch-wrapper .uni-switch-input:after {
		height: 44rpx !important;
		width: 44rpx !important;
	}

	.address-item>>>.uni-switch-wrapper .uni-switch-input:before {
		background-color: #EDEDED !important;
		height: 44rpx !important;
		width: 90rpx !important;
	}
</style>