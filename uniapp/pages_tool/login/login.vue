<template>
	<page-meta :page-style="themeColor"></page-meta>
	<scroll-view scroll-y="true" class="container">
		<!-- <view class="iconfont icon-close back-btn" @click="$util.redirectTo('/pages/member/index')"></view> -->
		<view class="header-wrap">
			<view class="title">登录</view>
			<view class="regisiter-agreement" v-if="registerConfig.register != ''">
				<text class="color-tip">还没有账号,</text>
				<text class="color-base-text" @click="toRegister">立即注册</text>
			</view>
		</view>
		<view class="body-wrap">
			<view class="form-wrap">
				<view class="input-wrap" v-show="loginMode == 'mobile'">
					<view class="content">
						<view class="area-code">+86</view>
						<input type="number" placeholder="仅限中国大陆手机号登录" placeholder-class="input-placeholder" class="input" maxlength="11" v-model="formData.mobile" />
					</view>
				</view>
				<view class="input-wrap" v-show="loginMode == 'account'">
					<view class="content">
						<input type="text" placeholder="请输入账号" placeholder-class="input-placeholder" class="input" v-model="formData.account" />
					</view>
				</view>
				<view class="input-wrap" v-show="loginMode == 'account'">
					<view class="content">
						<input type="password" placeholder="请输入密码" placeholder-class="input-placeholder" class="input" v-model="formData.password" />
						<view class="align-right" v-show="loginMode == 'account'">
							<text @click="forgetPassword">忘记密码?</text>
						</view>
					</view>
				</view>
				<view class="input-wrap" v-if="captchaConfig == 1">
					<view class="content">
						<input type="text" placeholder="请输入验证码" placeholder-class="input-placeholder" class="input" v-model="formData.vercode" />
						<image :src="captcha.img" class="captcha" @click="getCaptcha"></image>
					</view>
				</view>
				<view class="input-wrap" v-show="loginMode == 'mobile'">
					<view class="content">
						<input type="text" placeholder="请输入动态码" placeholder-class="input-placeholder" class="input" v-model="formData.dynacode" />
						<view class="dynacode" :class="dynacodeData.seconds == 120 ? 'color-base-text' : 'color-tip'" @click="sendMobileCode">{{ dynacodeData.codeText }}</view>
					</view>
				</view>
			</view>
			<view class="login-mode-box">
				<text @click="switchLoginMode" v-show="loginMode == 'mobile' && registerConfig.login.indexOf('username') != -1">使用账号登录</text>
				<text @click="switchLoginMode" v-show="loginMode == 'account' && registerConfig.login.indexOf('mobile') != -1">使用手机号登录</text>
			</view>
			<view class="btn_view">
				<button type="primary" @click="login" class="login-btn color-base-border color-base-bg">登录</button>
				<!-- #ifdef MP -->
				<button open-type="getPhoneNumber" class="auth-login color-base-border" v-if="Number(registerConfig.third_party)" @getphonenumber="mobileAuthLogin">
					<text class="color-base-text color-base-border">一键授权手机号登录</text>
				</button>
				<!-- #endif -->
			</view>
			<view class="regisiter-agreement" v-if="registerConfig.agreement_show">
				<text class="iconfont is-agree" :class=" isAgree ? 'icon-yuan_checked color-base-text' : 'icon-yuan_checkbox' " @click="isAgree = !isAgree"></text>
				<text class="tips">请阅读并同意</text>
				<text class="color-base-text" @click="toAggrement('PRIVACY')">《隐私协议》</text>
				<text class="tips">和</text>
				<text class="color-base-text" @click="toAggrement('SERVICE')">《用户协议》</text>
			</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
		<register-reward ref="registerReward"></register-reward>
	</scroll-view>
</template>

<script>
	import validate from 'common/js/validate.js';
	import registerReward from '@/components/register-reward/register-reward.vue';

	export default {
		data() {
			return {
				isAgree: false,
				loginMode: '',
				formData: {
					mobile: '',
					account: '',
					password: '',
					vercode: '',
					dynacode: '',
					key: ''
				},
				captcha: {
					id: '',
					img: ''
				},
				isSub: false, // 提交防重复
				back: '', // 返回页
				redirect: 'redirectTo', // 跳转方式
				dynacodeData: {
					seconds: 120,
					timer: null,
					codeText: '获取动态码',
					isSend: false
				},
				registerConfig: {
					register: '',
					login: ''
				},
				captchaConfig: 1,
				authInfo: null
			};
		},
		components: {
			registerReward
		},
		onLoad(option) {
			if(option.loginMode) this.loginMode = option.loginMode;
			if (option.back) this.back = option.back;
			this.getRegisterConfig();
			this.getCaptchaConfig();
			this.authInfo = uni.getStorageSync('authInfo');
		},
		onShow() {},
		onReady() {
			if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
		},
		methods: {
			toAggrement(type){
				this.$util.redirectTo('/pages_tool/login/aggrement',{type:type})
			},
			/**
			 * 获取验证码配置
			 */
			getCaptchaConfig() {
				this.$api.sendRequest({
					url: '/api/config/getCaptchaConfig',
					success: res => {
						if (res.code >= 0) {
							this.captchaConfig = res.data.shop_reception_login;
							if (this.captchaConfig == 1) this.getCaptcha();
						}
					}
				});
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
							if(!this.loginMode){
								if (this.registerConfig.login.indexOf('mobile') != -1) this.loginMode = 'mobile';
								else this.loginMode = 'account';
							}
						}
					}
				});
			},
			/**
			 * 切换登录方式
			 */
			switchLoginMode() {
				this.loginMode = this.loginMode == 'mobile' ? 'account' : 'mobile';
			},
			/**
			 * 获取验证码
			 */
			getCaptcha() {
				if (this.captchaConfig == 0) return;
				this.$api.sendRequest({
					url: '/api/captcha/captcha',
					data: {
						captcha_id: this.captcha.id
					},
					success: res => {
						if (res.code >= 0) {
							this.captcha = res.data;
							this.captcha.img = this.captcha.img.replace(/\r\n/g, '');
						}
					}
				});
			},
			/**
			 * 去注册
			 */
			toRegister() {
				if (this.back) this.$util.redirectTo('/pages_tool/login/register', {
					back: encodeURIComponent(this.back)
				});
				else this.$util.redirectTo('/pages_tool/login/register');
			},
			/**
			 * 忘记密码
			 */
			forgetPassword() {
				if (this.back) this.$util.redirectTo('/pages_tool/login/find', {
					back: encodeURIComponent(this.back)
				});
				else this.$util.redirectTo('/pages_tool/login/find');
			},
			/**
			 * 登录
			 */
			login() {
				if (this.loginMode == 'account') {
					var url = '/api/login/login';
					data = {
						username: this.formData.account,
						password: this.formData.password
					};
				} else {
					var url = '/api/login/mobile',
						data = {
							mobile: this.formData.mobile,
							key: this.formData.key,
							code: this.formData.dynacode
						};
				}
				if (this.captcha.id != '') {
					data.captcha_id = this.captcha.id;
					data.captcha_code = this.formData.vercode;
				}
				if (this.authInfo) Object.assign(data, this.authInfo);
				if (uni.getStorageSync('source_member')) data.source_member = uni.getStorageSync('source_member');

				if (this.verify(data)) {
					if (this.isSub) return;
					this.isSub = true;
					this.$api.sendRequest({
						url,
						data,
						success: res => {
							if (res.code >= 0) {
								var can_receive_registergift = res.data.can_receive_registergift;
								this.$store.commit('setToken', res.data.token);
								this.$store.dispatch('getCartNumber');
								this.getMemberInfo(() => {
									if (can_receive_registergift == 1) {
										this.$util.showToast({
											title: '登录成功'
										});
										
										this.$store.commit('setCanReceiveRegistergiftInfo',{status: true,path:this.$util.openRegisterRewardPath('/pages/member/index')});
										// if(this.$refs.registerReward) this.$refs.registerReward.open(back);
									}
									this.$util.loginComplete('/pages/member/index',{},this.redirect);
								});
							} else {
								this.isSub = false;
								this.getCaptcha();
								this.$util.showToast({
									title: res.message
								});
							}
						},
						fail: res => {
							this.isSub = false;
							this.getCaptcha();
						}
					});
				}
			},
			/**
			 * 登录验证
			 * @param {Object} data
			 */
			verify(data) {
				if (this.registerConfig.agreement_show && !this.isAgree) {
					this.$util.showToast({
						title: '请先阅读并同意协议'
					});
					return;
				}
				let rule = [];
				// 手机号验证
				if (this.loginMode == 'mobile') {
					rule = [{
						name: 'mobile',
						checkType: 'required',
						errorMsg: '请输入手机号'
					}, {
						name: 'mobile',
						checkType: 'phoneno',
						errorMsg: '请输入正确的手机号'
					}];
					if (this.captchaConfig == 1) {
						if (this.captcha.id != '') rule.push({
							name: 'captcha_code',
							checkType: 'required',
							errorMsg: this.$lang('captchaPlaceholder')
						});
					}
					rule.push({
						name: 'code',
						checkType: 'required',
						errorMsg: this.$lang('dynacodePlaceholder')
					});
				}

				// 账号验证
				if (this.loginMode == 'account') {
					rule = [{
							name: 'username',
							checkType: 'required',
							errorMsg: this.$lang('accountPlaceholder')
						},
						{
							name: 'password',
							checkType: 'required',
							errorMsg: this.$lang('passwordPlaceholder')
						}
					];
					if (this.captchaConfig == 1) {
						if (this.captcha.id != '') rule.push({
							name: 'captcha_code',
							checkType: 'required',
							errorMsg: this.$lang('captchaPlaceholder')
						});
					}
				}

				var checkRes = validate.check(data, rule);
				if (checkRes) {
					return true;
				} else {
					this.$util.showToast({
						title: validate.error
					});
					return false;
				}
			},
			mobileAuthLogin(e) {
				if (e.detail.errMsg == 'getPhoneNumber:ok') {
					uni.showLoading({
						title: '登录中'
					});

					var data = {
						iv: e.detail.iv,
						encryptedData: e.detail.encryptedData,
						code: e.detail.code
					};
					if (this.authInfo && Object.keys(this.authInfo).length) {
						Object.assign(data, this.authInfo);
						if (this.authInfo.nickName) data.nickname = this.authInfo.nickName;
						if (this.authInfo.avatarUrl) data.headimg = this.authInfo.avatarUrl;
					}
					if (uni.getStorageSync('source_member')) data.source_member = uni.getStorageSync('source_member');

					if (this.isSub) return;
					this.isSub = true;

					this.$api.sendRequest({
						url: '/api/tripartite/mobileauth',
						data,
						success: res => {
							if (res.code >= 0) {
								var can_receive_registergift = res.data.can_receive_registergift;
								this.$store.commit('setToken', res.data.token);
								this.$store.dispatch('getCartNumber');
								this.getMemberInfo(() => {
									if (can_receive_registergift == 1) {
										let back = this.back ? this.back : '/pages/member/index';
										this.$store.commit('setCanReceiveRegistergiftInfo', {status: true,path:this.$util.openRegisterRewardPath(back)});
										// if(this.$refs.registerReward) this.$refs.registerReward.open(back);
									}
									if (this.back != '') {
										this.$util.loginComplete(this.back,{},this.redirect);
									} else {
										this.$util.loginComplete('/pages/member/index',{},this.redirect);
									}
									setTimeout(() => {
										uni.hideLoading();
									}, 500);
								})

							} else {
								this.isSub = false;
								uni.hideLoading();
								this.$util.showToast({
									title: res.message
								});
							}
						},
						fail: res => {
							this.isSub = false;
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
			/**
			 * 发送手机动态码
			 */
			sendMobileCode() {
				if (this.dynacodeData.seconds != 120 || this.dynacodeData.isSend) return;
				var data = {
					mobile: this.formData.mobile,
					captcha_id: this.captcha.id,
					captcha_code: this.formData.vercode
				};
				var rule = [{
					name: 'mobile',
					checkType: 'required',
					errorMsg: '请输入手机号'
				}, {
					name: 'mobile',
					checkType: 'phoneno',
					errorMsg: '请输入正确的手机号'
				}];
				if (this.captchaConfig == 1) {
					rule.push({
						name: 'captcha_code',
						checkType: 'required',
						errorMsg: '请输入验证码'
					});
				}
				var checkRes = validate.check(data, rule);
				if (!checkRes) {
					this.$util.showToast({
						title: validate.error
					});
					return;
				}
				this.dynacodeData.isSend = true;
				this.dynacodeData.timer = setInterval(() => {
					this.dynacodeData.seconds--;
					this.dynacodeData.codeText = this.dynacodeData.seconds + 's后可重新获取';
				}, 1000);

				this.$api.sendRequest({
					url: '/api/login/mobileCode',
					data: data,
					success: res => {
						if (res.code >= 0) {
							this.formData.key = res.data.key;
						} else {
							this.refreshDynacodeData();
							this.$util.showToast({
								title: res.message
							});
						}
					},
					fail: () => {
						this.$util.showToast({
							title: 'request:fail'
						});
						this.refreshDynacodeData();
					}
				});
			},
			refreshDynacodeData() {
				this.getCaptcha();
				clearInterval(this.dynacodeData.timer);
				this.dynacodeData = {
					seconds: 120,
					timer: null,
					codeText: '获取动态码',
					isSend: false
				};
			},
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
			}
		},
		watch: {
			'dynacodeData.seconds': {
				handler(newValue, oldValue) {
					if (newValue == 0) {
						this.refreshDynacodeData();
					}
				},
				immediate: true,
				deep: true
			}
		}
	};
</script>

<style lang="scss">
	@import './public/css/common.scss';
</style>

<style scoped>
	/deep/ .reward-popup .uni-popup__wrapper-box {
		background: none !important;
		max-width: unset !important;
		max-height: unset !important;
		overflow: unset !important;
	}

	/deep/ uni-toast .uni-simple-toast__text {
		background: red !important;
	}
</style>