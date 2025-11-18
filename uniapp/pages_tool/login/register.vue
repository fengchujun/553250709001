<template>
	<page-meta :page-style="themeColor"></page-meta>
	<scroll-view scroll-y="true" class="container">
		<!-- <view class="iconfont icon-close back-btn" @click="$util.goBack()"></view> -->
		<view class="header-wrap">
			<view class="title">注册</view>
			<view class="regisiter-agreement">
				<text class="color-tip">已有账号,</text>
				<text class="color-base-text" @click="toLogin">立即登录</text>
			</view>
		</view>
		<view class="body-wrap">
			<view class="form-wrap">
				<view class="input-wrap" v-show="registerMode == 'mobile'">
					<view class="content">
						<view class="area-code">+86</view>
						<input type="number" placeholder="仅限中国大陆手机号注册" placeholder-class="input-placeholder" class="input" maxlength="11" v-model="formData.mobile" />
					</view>
				</view>
				<view class="input-wrap" v-show="registerMode == 'account'">
					<view class="content">
						<input type="text" placeholder="请输入账号" placeholder-class="input-placeholder" class="input" v-model="formData.account" />
					</view>
				</view>
				<view class="input-wrap" v-show="registerMode == 'account'">
					<view class="content">
						<input type="password" placeholder="请输入密码" placeholder-class="input-placeholder" class="input" v-model="formData.password" />
					</view>
				</view>
				<view class="input-wrap" v-show="registerMode == 'account'">
					<view class="content">
						<input type="password" placeholder="请确认密码" placeholder-class="input-placeholder" class="input" v-model="formData.rePassword" />
					</view>
				</view>
				<view class="input-wrap" v-if="isOpenCaptcha">
					<view class="content">
						<input type="text" placeholder="请输入验证码" placeholder-class="input-placeholder" class="input" v-model="formData.vercode" />
						<image :src="captcha.img" class="captcha" @click="getCaptcha"></image>
					</view>
				</view>
				<view class="input-wrap" v-show="registerMode == 'mobile'">
					<view class="content">
						<input type="text" placeholder="请输入动态码" placeholder-class="input-placeholder" class="input" v-model="formData.dynacode" />
						<view class="dynacode" :class="dynacodeData.seconds == 120 ? 'color-base-text' : 'color-tip'" @click="sendMobileCode">{{ dynacodeData.codeText }}</view>
					</view>
				</view>
			</view>
			<view class="login-mode-box">
				<text @click="switchRegisterMode" v-show="registerMode == 'mobile' && registerConfig.register.indexOf('username') != -1">使用用户名注册</text>
				<text @click="switchRegisterMode" v-show="registerMode == 'account' && registerConfig.register.indexOf('mobile') != -1">使用手机号注册</text>
			</view>
			<view class="btn_view"><button type="primary" @click="register" class="login-btn color-base-border color-base-bg">注册</button></view>
			<view class="regisiter-agreement" v-if="registerConfig.agreement_show">
				<text class="iconfont is-agree" :class=" isAgree ? 'icon-yuan_checked color-base-text' : 'icon-yuan_checkbox' " @click="isAgree = !isAgree"></text>
				<text class="tips">请阅读并同意</text>
				<text class="color-base-text" @click="toAggrement('PRIVACY')">《隐私协议》</text>
				<text class="tips">和</text>
				<text class="color-base-text" @click="toAggrement('SERVICE')">《用户协议》</text>
			</view>
		</view>

		<view @touchmove.prevent>
			<uni-popup ref="registerPopup" type="center" :maskClick="false">
				<view class="conten-box">
					<view class="close"><text class="iconfont icon-close" @click="toClose"></text></view>
					<view class="title">{{ agreement.title }}</view>
					<view class="con">
						<scroll-view scroll-y="true" class="con">
							<!-- <rich-text :nodes="agreement.content"></rich-text> -->
							<ns-mp-html :content="agreement.content"></ns-mp-html>
						</scroll-view>
					</view>
				</view>
			</uni-popup>
		</view>

		<loading-cover ref="loadingCover"></loading-cover>
		<register-reward ref="registerReward"></register-reward>
	</scroll-view>
</template>

<script>
	import uniPopup from '@/components/uni-popup/uni-popup.vue';
	import validate from 'common/js/validate.js';
	import registerReward from '@/components/register-reward/register-reward.vue';
	import htmlParser from '@/common/js/html-parser';

	export default {
		components: {
			uniPopup,
			registerReward
		},
		data() {
			return {
				allowRegister: true, // 是否允许注册
				registerMode: 'account',
				formData: {
					mobile: '',
					account: '',
					password: '',
					rePassword: '',
					vercode: '',
					dynacode: '',
					key: ''
				},
				agreement: {
					// 注册协议
					title: '',
					content: ''
				},
				captcha: {
					// 验证码
					id: '',
					img: ''
				},
				isSub: false,
				back: '', // 返回页
				dynacodeData: {
					seconds: 120,
					timer: null,
					codeText: '获取动态码',
					isSend: false
				},
				registerConfig: {
					register: ''
				},
				authInfo: null,
				isAgree: false,
				isOpenCaptcha: 0, // 前台登录验证码，0：关闭，1：开启
			};
		},
		onLoad(option) {
			if (option.back) this.back = option.back;
			this.getCaptchaConfig();
			this.getRegisiterAggrement();
			this.getRegisterConfig();
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
			 * 切换注册方式
			 */
			switchRegisterMode() {
				this.registerMode = this.registerMode == 'mobile' ? 'account' : 'mobile';
			},
			/**
			 * 展示注册协议
			 */
			openPopup() {
				this.$refs.registerPopup.open();
			},
			/**
			 * 点击关闭协议
			 */
			toClose() {
				this.$refs.registerPopup.close();
			},
			/**
			 * 获取注册协议
			 */
			getRegisiterAggrement() {
				this.$api.sendRequest({
					url: '/api/register/aggrement',
					success: res => {
						if (res.code >= 0) {
							this.agreement = res.data;
							// if (this.agreement.content) this.agreement.content = htmlParser(this.agreement.content);
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
							if (this.registerConfig.register == '') {
								this.$util.showToast({
									title: '平台未启用注册!'
								});
								setTimeout(() => {
									this.$util.redirectTo('/pages/index/index');
								}, 1000);
							} else if (this.registerConfig.register.indexOf('username') != -1) {
								this.registerMode = 'account';
							} else {
								this.registerMode = 'mobile';
							}
							if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
						}
					}
				});
			},
			/**
			 * 获取验证码配置
			 */
			getCaptchaConfig() {
				this.$api.sendRequest({
					url: '/api/config/getCaptchaConfig',
					success: res => {
						if (res.code >= 0) {
							this.isOpenCaptcha = res.data.shop_reception_register;
							if (this.isOpenCaptcha == 1) this.getCaptcha();
						}
					}
				});
			},
			/**
			 * 获取验证码
			 */
			getCaptcha() {
				if (this.isOpenCaptcha == 0) return;
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
			 * 注册
			 */
			register() {
				if (this.registerMode == 'account') {
					var url = '/api/register/username';
					data = {
						username: this.formData.account,
						password: this.formData.password
					};
				} else {
					var url = '/api/register/mobile',
						data = {
							mobile: this.formData.mobile,
							key: this.formData.key,
							code: this.formData.dynacode
						};
				}
				if (this.isOpenCaptcha == 1) {
					if (this.captcha.id != '') {
						data.captcha_id = this.captcha.id;
						data.captcha_code = this.formData.vercode;
					}
				}
				if (this.authInfo) {
					Object.assign(data, this.authInfo);
					if (this.authInfo.nickName) data.nickname = this.authInfo.nickName;
					if (this.authInfo.avatarUrl) data.headimg = this.authInfo.avatarUrl;
				}
				if (uni.getStorageSync('source_member')) data.source_member = uni.getStorageSync('source_member');

				if (this.verify(data)) {
					if (this.isSub) return;
					this.isSub = true;
					this.$api.sendRequest({
						url,
						data,
						success: res => {
							if (res.code >= 0) {
								this.$store.commit('setToken', res.data.token);
								this.$store.dispatch('getCartNumber');
								this.getMemberInfo(() => {
									this.$util.showToast({
										title: '注册成功'
									});
									let back = this.back ? this.back : '/pages/member/index';
									// this.$refs.registerReward.open(back);
									this.$store.commit('setCanReceiveRegistergiftInfo', {status: true,path:this.$util.openRegisterRewardPath(back)});
									this.$util.loginComplete(back,'redirectTo');
								})
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
			verify(data) {
				if (this.registerConfig.agreement_show && !this.isAgree) {
					this.$util.showToast({
						title: '请先阅读并同意协议'
					});
					return false;
				}

				// 手机号注册
				var user_test = /^[A-Za-z0-9]+$/;
				if (this.registerMode == 'mobile') {
					var rule = [{
						name: 'mobile',
						checkType: 'required',
						errorMsg: '请输入手机号'
					}, {
						name: 'mobile',
						checkType: 'phoneno',
						errorMsg: '请输入正确的手机号'
					}];
					if (this.isOpenCaptcha == 1) {
						if (this.captcha.id != '') {
							rule.push({
								name: 'captcha_code',
								checkType: 'required',
								errorMsg: this.$lang('captchaPlaceholder')
							});
						}
					}
					rule.push({
						name: 'code',
						checkType: 'required',
						errorMsg: this.$lang('dynacodePlaceholder')
					});
				}
				// 用户名注册
				if (this.registerMode == 'account') {
					var rule = [{
							name: 'username',
							checkType: 'required',
							errorMsg: '请输入账号'
						}, {
							name: 'password',
							checkType: 'required',
							errorMsg: '请输入密码'
						}],
						regConfig = this.registerConfig;

					if (!user_test.test(data.username)) {
						this.$util.showToast({
							title: '用户名只能输入数字跟英文'
						});
						return;
					}

					if (regConfig.pwd_len > 0) {
						rule.push({
							name: 'password',
							checkType: 'lengthMin',
							checkRule: regConfig.pwd_len,
							errorMsg: '密码长度不能小于' + regConfig.pwd_len + '位'
						});
					}
					if (regConfig.pwd_complexity != '') {
						let passwordErrorMsg = '密码需包含',
							reg = '';
						if (regConfig.pwd_complexity.indexOf('number') != -1) {
							reg += '(?=.*?[0-9])';
							passwordErrorMsg += '数字';
						}
						if (regConfig.pwd_complexity.indexOf('letter') != -1) {
							reg += '(?=.*?[a-z])';
							passwordErrorMsg += '、小写字母';
						}
						if (regConfig.pwd_complexity.indexOf('upper_case') != -1) {
							reg += '(?=.*?[A-Z])';
							passwordErrorMsg += '、大写字母';
						}
						if (regConfig.pwd_complexity.indexOf('symbol') != -1) {
							reg += '(?=.*?[#?!@$%^&*-])';
							passwordErrorMsg += '、特殊字符';
						}
						rule.push({
							name: 'password',
							checkType: 'reg',
							checkRule: reg,
							errorMsg: passwordErrorMsg
						});
					}
					if (this.formData.password != this.formData.rePassword) {
						this.$util.showToast({
							title: '两次密码不一致'
						});
						return false;
					}

					if (this.isOpenCaptcha == 1) {
						if (this.captcha.id != '') {
							rule.push({
								name: 'captcha_code',
								checkType: 'required',
								errorMsg: this.$lang('captchaPlaceholder')
							});
						}
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
			/**
			 * 去登录
			 */
			toLogin() {
				if (this.back) this.$util.redirectTo('/pages_tool/login/index', {
					back: encodeURIComponent(this.back)
				});
				else this.$util.redirectTo('/pages_tool/login/index');
			},
			/**
			 * 发送手机动态码
			 */
			sendMobileCode() {
				if (this.dynacodeData.seconds != 120 || this.dynacodeData.isSend) return;
				var data = {
					mobile: this.formData.mobile
				};

				if (this.isOpenCaptcha == 1) {
					if (this.captcha.id != '') {
						data.captcha_id = this.captcha.id;
						data.captcha_code = this.formData.vercode;
					}
				}

				var rule = [{
						name: 'mobile',
						checkType: 'required',
						errorMsg: '请输入手机号'
					},
					{
						name: 'mobile',
						checkType: 'phoneno',
						errorMsg: '请输入正确的手机号'
					}
				];

				if (this.isOpenCaptcha == 1) {
					if (this.captcha.id != '') {
						rule.push({
							name: 'captcha_code',
							checkType: 'required',
							errorMsg: '请输入验证码'
						});
					}
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
					url: '/api/register/mobileCode',
					data: data,
					success: res => {
						if (res.code >= 0) {
							this.formData.key = res.data.key;
						} else {
							this.$util.showToast({
								title: res.message
							});
							this.refreshDynacodeData();
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
							// 正常成功，存储会员信息
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
<style scoped lang="scss">
	.conten-box {
		padding: 0 30rpx 30rpx;

		.title {
			text-align: center;
			margin-top: 50rpx;
			margin-bottom: 10rpx;
		}

		.close {
			position: absolute;
			right: 30rpx;
			top: 10rpx;
		}

		.con {
			height: 500rpx;
		}
	}

	/deep/ .reward-popup .uni-popup__wrapper-box {
		background: none !important;
		max-width: unset !important;
		max-height: unset !important;
		overflow: unset !important;
	}
</style>