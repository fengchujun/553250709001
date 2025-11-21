<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="auth-index" :style="warpStyle">
		<view class="website-logo">
			<image class="logo" v-if="siteInfo.logo" :src="$util.img(siteInfo.logo)" mode="aspectFit"></image>
			<view v-else class="logo"></view>
		</view>
		<view class="login-desc">{{registerConfig.wap_desc}}</view>
		<view class="login-area">
			<!-- #ifdef H5 -->
			<view class="btn quick-login" v-if="$util.isWeiXin() && wechatConfigStatus && registerConfig && Number(registerConfig.third_party)" @click="quickLogin">快捷登录/注册</view>
			<!-- #endif -->
			<!-- #ifdef MP-WEIXIN -->
			<button open-type="getPhoneNumber" class="btn quick-login" v-if="registerConfig && Number(registerConfig.third_party)" @getphonenumber="quickLoginWithPhone">一键授权手机号登录</button>
			<!-- #endif -->
			<view class="btn" :class="isQuickLogin ? '':'quick-login'" @click="toLogin('mobile')" v-if="registerConfig.login.indexOf('mobile') != -1">手机号登录</view>
			<view class="btn" :class="isQuickLogin ? '':'quick-login'" @click="toLogin('account')" v-if="registerConfig.login.indexOf('mobile') == -1 && registerConfig.login.indexOf('username') != -1">账号密码登录</view>
			<view class="agreement" v-if="registerConfig.agreement_show">
				<text class="iconfont agree" :class=" isAgree ? 'icon-yuan_checked color-base-text' : 'icon-yuan_checkbox' " @click="isAgree = !isAgree"></text>
				<view class="tips-text">
					<text class="tips">请阅读并同意</text>
					<text class="agreement-name color-base-text" @click="toAggrement('PRIVACY')">《隐私协议》</text>
					<text class="tips">和</text>
					<text class="agreement-name color-base-text" @click="toAggrement('SERVICE')">《用户协议》</text>
				</view>
			</view>
			<view class="footer" v-if="registerConfig.login.indexOf('mobile') != -1 && registerConfig.login.indexOf('username') != -1">
				<view class="text">其他方式登录</view>
				<view class="mine icondiy icon-system-wodi2" @click="toLogin('account')"></view>
				<view class="mode-name">账号密码登录</view>
			</view>
			
		</view>
		<ns-login ref="login"></ns-login>
	</view>
</template>

<script>
	import auth from 'common/js/auth.js';
	export default {
		mixins: [auth],
		data() {
			return {
				back: '',
				registerConfig: {
					register: '',
					login: '',
				},
				isAgree: false
			}
		},
		computed: {
			isQuickLogin() {
				// #ifdef H5
					return this.$util.isWeiXin() && this.wechatConfigStatus && this.registerConfig && Number(this.registerConfig.third_party);
				// #endif
				// #ifdef MP-WEIXIN
					return this.registerConfig && Number(this.registerConfig.third_party);
				// #endif
			},
			warpStyle() {
				var style = '';
				if(this.registerConfig.wap_bg){
					style += 'background-image:url(' + this.$util.img(this.registerConfig.wap_bg) + ');';
					style += 'background-size: 100%;';
					style += 'background-position: top;';
					style += 'background-repeat: no-repeat;';
				}
				return style
			},
			wechatConfigStatus() {
				return this.$store.state.wechatConfigStatus;
			}
		},
		onLoad(option) {
			this.back = option.back || '';
			// #ifdef MP-WEIXIN
				this.back = option.back ? decodeURIComponent(option.back) : '';
			// #endif
			if(this.back) uni.setStorageSync('initiateLogin',this.back)
			// #ifdef MP-WEIXIN
				this.getCode(authData => {
					uni.setStorageSync('authInfo', authData);
				});
			// #endif
			
			if(!uni.getStorageSync('authInfo')){
				// #ifdef H5
					if(this.$util.isWeiXin() && !option.code){
						this.getCode(authData => {
							uni.setStorageSync('authInfo', authData);
						});
					}
					if(option.code){
						this.$api.sendRequest({
							url: '/wechat/api/wechat/authcodetoopenid',
							data: {
								code: urlParams.code
							},
							success: res => {
								if (res.code >= 0) {
									let data = {};
									if (res.data.openid) data.wx_openid = res.data.openid;
									if (res.data.unionid) data.wx_unionid = res.data.unionid;
									if (res.data.userinfo) Object.assign(data, res.data.userinfo);
									uni.setStorageSync('authInfo', data);
								}
							}
						});
					}
				// #endif
			}
			
			
		},
		onShow() {
			this.getRegisterConfig();
		},
		methods: {
			toAggrement(type){
				this.$util.redirectTo('/pages_tool/login/aggrement',{type:type})
			},
			quickLogin(){
				if(this.registerConfig.agreement_show && !this.isAgree){
					this.$util.showToast({title:'请先阅读并同意协议'})
					return;
				}
				this.$refs.login.open(this.back,true);
			},
			// 一键授权手机号快捷登录
			quickLoginWithPhone(e) {
				// 检查是否同意协议
				if(this.registerConfig.agreement_show && !this.isAgree){
					this.$util.showToast({title:'请先阅读并同意协议'})
					return;
				}

				if (e.detail.errMsg == 'getPhoneNumber:ok') {
					uni.showLoading({
						title: '登录中'
					});

					let authData = uni.getStorageSync('authInfo');
					let data = {
						iv: e.detail.iv,
						encryptedData: e.detail.encryptedData,
						code: e.detail.code
					};

					if (authData) Object.assign(data, authData);
					if (uni.getStorageSync('source_member')) data.source_member = uni.getStorageSync('source_member');

					this.$api.sendRequest({
						url: '/api/tripartite/mobileauth',
						data,
						success: res => {
							if (res.code >= 0) {
								this.$store.commit('setToken', res.data.token);
								this.$store.dispatch('getCartNumber');

								// 登录成功后重新初始化配置（获取个性化主题）
								this.$store.dispatch('init');

								this.getMemberInfo(() => {

									if (res.data.is_register == 1 || res.data.can_receive_registergift == 1) {
										let back = this.back ? this.back : '/pages/index/index';
										this.$store.commit('setCanReceiveRegistergiftInfo', {
											status: true,
											path: this.$util.openRegisterRewardPath(back)
										});
									}

									if (this.back != '') {
										this.$util.loginComplete(this.back, {}, 'redirectTo');
									} else {
										this.$util.loginComplete('/pages/index/index', {}, 'redirectTo');
									}
									setTimeout(() => {
										uni.hideLoading();
									}, 500);
								});
							} else {
								uni.hideLoading();
								this.$util.showToast({
									title: res.message
								});
							}
						},
						fail: () => {
							uni.hideLoading();
							this.$util.showToast({
								title: '登录失败，请重试'
							});
						}
					});
				} else {
					this.$util.showToast({
						title: '取消授权将无法快速登录'
					});
				}
			},
			toLogin(loginMode){
				this.$util.redirectTo('/pages_tool/login/login',{loginMode:loginMode})
			},
			/**
			 * 获取注册配置
			 */
			getRegisterConfig() {
				this.$api.sendRequest({
					url: '/api/register/config',
					success: res => {
						if (res.code >= 0) {
							this.registerConfig = res.data.value;
							
						}
					}
				});
			},
			/**

			 * 获取会员信息

			 */

			getMemberInfo(callback) {

				this.$api.sendRequest({

					url: '/api/member/info',

					success: (res) => {

						if (res.code >= 0) {

							// 登录成功，存储会员信息

							this.$store.commit('setMemberInfo', res.data);

							if (callback) callback();

						}

					}

				});

			},
		},
	}
</script>

<style lang="scss">
	@import './public/css/common.scss';

	// button 元素样式重置，确保和 view 表现一致
	.login-area {
		button.btn {
			margin: 0 auto 30rpx;
			padding: 0;
			border: 2rpx solid var(--base-color);
			border-radius: 44rpx;
			line-height: 88rpx;
			display: flex;
			align-items: center;
			justify-content: center;

			&::after {
				border: none;
			}
		}
	}
</style>