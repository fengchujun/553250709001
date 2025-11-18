<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="container">
		<view class="action-wrap">
			<view class="record-wrap color-base-text" @click="$util.redirectTo('/pages_tool/verification/list')">
				<text class="iconfont icon-jilu color-base-text"></text>
				<text>核销记录</text>
			</view>

			<view class="sweep-code ns-gradient-otherpages-member-balance-balance-rechange" @click="scanCode" v-show="operationType == 'sweepCode'">
				<text class="iconfont icon-saoma"></text>
			</view>
			<view class="manual-input" v-show="operationType == 'manualInput'">
				<view class="process-wrap">
					<view class="wrap">
						<view class="_icon"><text class="iconfont icon-shurutianxiebi color-base-text"></text></view>
						<view class="_text">输入核销码</view>
					</view>
					<view>
						<view><text class="iconfont icon-jiang-copy color-tip"></text></view>
					</view>
					<view class="wrap">
						<view class="_icon"><text class="iconfont icon-hexiao color-base-text"></text></view>
						<view class="_text">核销</view>
					</view>
				</view>
				<input type="text" placeholder="请输入核销码" class="_input" placeholder-class="_placeholder" v-model="verify_code" :focus="isFocus" ref="input" />
				<view class="_btn" @click="confirm"><button type="primary">确认</button></view>
			</view>
		</view>
		<view class="arc-edge"></view>

		<view class="action-type-wrap">
			<view class="action" @click="changeOperationType('sweepCode')">
				<view class="_icon"><text class="iconfont icon-saoma"></text></view>
				<view class="_text">扫码核销</view>
			</view>
			<view class="iconfont icon-tiaoxingmasaomiao ns-gradient-otherpages-member-balance-balance-rechange"></view>
			<view class="action" @click="changeOperationType('manualInput')">
				<view class="_icon"><text class="iconfont icon-shuru"></text></view>
				<view class="_text" @click="focus">手动输入</view>
			</view>
		</view>

		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
import { Weixin } from 'common/js/wx-jssdk.js';
import Config from '@/common/js/config.js';

export default {
	data() {
		return {
			// #ifdef H5
			operationType: 'manualInput',
			// #endif
			// #ifndef H5
			operationType: 'sweepCode',
			// #endif
			verify_code: '',
			isFocus: false,
			detail_path:'/pages_tool/verification/detail',
		};
	},
	onLoad() {},
	onShow() {
		if (this.storeToken) {
			this.checkIsVerifier();
		} else {
			this.$nextTick(() => {
				this.$refs.login.open('/pages_tool/verification/index');
			});
		}
	},
	methods: {
		focus() {
			this.isFocus = !this.isFocus;
		},
		scanCode() {
			// #ifdef MP
			uni.scanCode({
				onlyFromCamera: true,
				success: res => {
					if (res.errMsg == 'scanCode:ok') {
						let result = res.result;
						let code = '';
						switch(res.scanType){
							case 'CODE_128':
								code = result;
								break;
							case 'QR_CODE':
								if(result.indexOf(this.detail_path) > -1){
									let matchs = result.match(/\?code=(.+)/);
									if(matchs.length == 2){
										code = matchs[1];
									}
								}
								break;
						}
						if(!code){
							this.$util.showToast({
								title: '请扫码正确的条码或二维码'
							});
							return;
						}
						this.$util.redirectTo(this.detail_path+'?code='+code);
					} else {
						this.$util.showToast({
							title: res.errorMsg
						});
					}
				}
			});
			// #endif

			// #ifdef H5
			if (this.$util.isWeiXin()) {
				if (uni.getSystemInfoSync().platform == 'ios') {
					var url = uni.getStorageSync('initUrl');
				} else {
					var url = location.href;
				}
				this.$api.sendRequest({
					url: '/wechat/api/wechat/jssdkconfig',
					data: {
						url: url
					},
					success: jssdkRes => {
						if (jssdkRes.code == 0) {
							var wxJS = new Weixin();
							wxJS.init(jssdkRes.data);
							wxJS.scanQRCode(res => {
								if (res.resultStr) {
									let result = res.resultStr;
									let code = '';
									if (result.indexOf(this.detail_path) != -1){
										let matchs = result.match(/\?code=(.+)/);
										if(matchs.length == 2){
											code = matchs[1];
										}
									}else if(result.indexOf('CODE_128') != -1){
										code = result.split(',')[1];
									}
									if(!code){
										this.$util.showToast({
											title: '请扫码正确的条码或二维码'
										});
										return;
									}
									this.$util.redirectTo(this.detail_path+'?code='+code);
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
		},
		changeOperationType(type) {
			// #ifdef H5
			if (type == 'sweepCode' && !this.$util.isWeiXin()) {
				this.$util.showToast({
					title: 'H5端不支持扫码核销'
				});
				return;
			}
			// #endif
			this.operationType = type;
		},
		checkIsVerifier() {
			this.$api.sendRequest({
				url: '/api/verify/checkisverifier',
				success: res => {
					if (!res.data) {
						this.$util.showToast({ title: '非核销员无此权限' });
						setTimeout(() => {
							this.$util.redirectTo('/pages/member/index');
						}, 1000);
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		confirm() {
			var reg = /[\S]+/;
			if (!reg.test(this.verify_code)) {
				this.$util.showToast({ title: '请输入核销码' });
				return false;
			}
			this.$api.sendRequest({
				url: '/api/verify/verifyInfo',
				data: {
					verify_code: this.verify_code
				},
				success: res => {
					if (res.code >= 0) {
						this.$util.redirectTo('/pages_tool/verification/detail', { code: this.verify_code });
					} else {
						this.$util.showToast({ title: res.message });
					}
				}
			});
		}
	},
	watch: {
		storeToken: function(nVal, oVal) {
			if (nVal) {
				this.checkIsVerifier();
			}
		}
	}
};
</script>

<style lang="scss">
.container {
	width: 100vw;
	height: 100vh;

	.action-wrap {
		padding: 100rpx 0;
		background: #fff;
		position: relative;

		.record-wrap {
			position: absolute;
			top: 30rpx;
			right: 30rpx;

			.iconfont {
				font-size: $font-size-tag;
				margin-right: 10rpx;
			}
		}

		.sweep-code {
			width: 400rpx;
			height: 400rpx;
			box-shadow: 0 8px 8px 0 rgba(0, 0, 0, 0.03), 0 6px 3px 0 rgba(0, 0, 0, 0.02);
			border-radius: 50%;
			margin: 0 auto;
			text-align: center;
			line-height: 400rpx;
			background: $base-color;

			.iconfont {
				color: #fff;
				font-size: 150rpx;
			}
		}

		.manual-input {
			width: 70%;
			margin: auto;

			.process-wrap {
				height: 140rpx;
				display: flex;
				padding-top: 60rpx;

				.wrap {
					flex: 1;
					text-align: center;

					._icon {
						width: 60rpx;
						height: 60rpx;
						background: #eee;
						border-radius: 50%;
						margin: 0 auto;
						color: $color-tip;

						.iconfont {
							font-size: $font-size-toolbar;
						}
					}

					._text {
						font-size: $font-size-tag;
						margin-top: 10rpx;
						color: $color-tip;
					}
				}
			}

			._input {
				height: 80rpx;
				border: 1px solid #eee;
				border-radius: 8rpx;
				box-sizing: border-box;
				padding: 20rpx;
				font-size: $font-size-base;
				text-align: center;
			}

			._placeholder {
				font-size: $font-size-base;
				text-align: center;
			}

			._btn {
				margin-top: 40rpx;
				height: 80rpx;
				line-height: 80rpx;
			}
		}
	}

	.arc-edge {
		width: 100%;
		height: 80rpx;
		background: #fff;
		border-radius: 400rpx/40rpx; /*上下有弧度的边*/
		transform: translateY(-50%);
	}

	.action-type-wrap {
		width: 70%;
		height: 90rpx;
		background: #fff;
		border-radius: 90rpx;
		display: flex;
		position: relative;
		box-shadow: 0 6px 6px 0 rgba(0, 0, 0, 0.03), 0 4px 2px 0 rgba(0, 0, 0, 0.04);
		margin: 100rpx auto;

		.action {
			flex: 1;
			text-align: center;
			color: $color-title;

			._icon {
				line-height: 25px;
				height: 25px;
			}

			._text {
				font-size: $font-size-tag;
				line-height: 1;
			}
		}

		.icon-tiaoxingmasaomiao {
			width: 110rpx;
			height: 110rpx;
			border-radius: 50%;
			transform: translateY(-10rpx);
			box-shadow: 0 8px 8px 0 rgba(0, 0, 0, 0.03), 0 6px 3px 0 rgba(0, 0, 0, 0.02);
			text-align: center;
			line-height: 110rpx;
			background: $base-color;
			color: #fff;
			font-size: $font-size-toolbar;
		}
	}
}
</style>
