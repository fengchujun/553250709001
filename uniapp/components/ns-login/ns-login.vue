<template>
	<view>
		<!-- 完善会员资料 -->
		<view @touchmove.prevent.stop class="complete-info-popup">
			<uni-popup ref="completeInfoPopup" type="bottom" :maskClick="false">
				<view class="complete-info-wrap">
					<!-- #ifdef H5 -->
					<template v-if="forceBindingMobileControl">
						<view class="head">
							<text class="title">检测到您还未绑定手机号</text>
							<text class="color-tip tips">为了方便您接收订单等信息，需要绑定手机号</text>
							<text class="iconfont icon-close color-tip" @click="cancelCompleteInfo"></text>
						</view>
						<view class="item-wrap">
							<text class="label">手机号</text>
							<input type="number" placeholder="请输入手机号" v-model="formData.mobile" maxlength="11" />
						</view>
						<view class="item-wrap" v-if="isOpenCaptcha">
							<text class="label">验证码</text>
							<input type="number" placeholder="请输入验证码" v-model="formData.vercode" maxlength="4" />
							<image :src="captcha.img" class="captcha" @click="getCaptcha"></image>
						</view>
						<view class="item-wrap">
							<text class="label">动态码</text>
							<input type="number" placeholder="请输入动态码" v-model="formData.dynacode" maxlength="4" />
							<view class="send color-base-text" @click="sendMobileCode">{{ dynacodeData.codeText }}
							</view>
						</view>
					</template>
					<button type="default" class="save-btn" @click="saveH5" :disabled="isDisabled">保存</button>
					<!-- #endif -->

					<!-- #ifdef MP -->
					<view class="head">
						<text class="title">欢迎使用快捷登录</text>
						<text class="color-tip tips">
							点击下方按钮，授权手机号即可快速登录
						</text>
						<text class="iconfont icon-close color-tip" @click="cancelCompleteInfo"></text>
					</view>

					<!-- 一键授权手机号快捷登录 -->
					<view class="phone-auth-wrap">
						<button open-type="getPhoneNumber" class="phone-auth-btn color-base-bg" @getphonenumber="quickLoginWithPhone">
							<text>一键授权手机号登录</text>
						</button>
						<text class="tips-text">授权后将使用您的手机号自动注册并登录</text>
					</view>

					<!-- #endif  -->
				</view>
			</uni-popup>
		</view>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->

		<register-reward ref="registerReward"></register-reward>
	</view>
</template>

<script>
	import uniPopup from '@/components/uni-popup/uni-popup.vue';
	import registerReward from '@/components/register-reward/register-reward.vue';
	import auth from 'common/js/auth.js';
	import validate from 'common/js/validate.js';

	export default {
		mixins: [auth],
		name: 'ns-login',
		components: {
			uniPopup,
			registerReward
		},
		data() {
			return {
				url: '',
				registerConfig: {},
				avatarUrl: '', // 头像预览路径
				headImg: '', // 头像上传路径
				nickName: '', // 昵称
				isSub: false,

				// 绑定手机号
				isOpenCaptcha: 0, // 前台登录验证码，0：关闭，1：开启
				captcha: {
					id: '',
					img: ''
				},
				formData: {
					key: '',
					mobile: '',
					vercode: '',
					dynacode: ''
				},
				dynacodeData: {
					seconds: 120,
					timer: null,
					codeText: '获取动态码',
					isSend: false
				},
				// 小程序获取手机号所需数据
				authMobileData: {
					iv: '',
					encryptedData: ''
				}
			};
		},
		options: {
			styleIsolation: 'shared'
		},
		mounted() {},
		watch: {
			'dynacodeData.seconds': {
				handler(newValue, oldValue) {
					if (newValue == 0) {
						this.refreshDynacodeData();
					}
				},
				immediate: true,
				deep: true
			},
		},
		computed: {
			// 控制按钮是否禁用
			isDisabled() {
				// 移除昵称的必填验证，允许用户使用默认昵称直接保存
				// #ifdef MP-WEIXIN
				// if (this.nickName.length == 0) return true;
				// #endif

				// 强制绑定手机号验证
				if (this.forceBindingMobileControl) {
					if (this.formData.mobile.length == 0) return true;

					// #ifdef H5

					// 验证码
					if (this.isOpenCaptcha == 1 && this.formData.vercode.length == 0) return true;

					// 动态码
					if (this.formData.dynacode.length == 0) return true;

					// #endif
				}
				return false;
			},
			forceBindingMobileControl() {
				if (this.registerConfig && this.registerConfig.third_party == 1 && this.registerConfig.bind_mobile == 1) return true;
				else return false;
			},
			wechatConfigStatus() {
				return this.$store.state.wechatConfigStatus;
			},
			
		},
		methods: {
			// 获取注册配置
			getRegisterConfig(callback = null) {
				this.$api.sendRequest({
					url: '/api/register/config',
					success: res => {
						if (res.code >= 0) {
							this.registerConfig = res.data.value;
							if (callback) callback();
						}
					}
				});
			},
			open(url,isMiddleIndex = false) {
				if(!url) url = this.$util.getCurrentRoute().path;
				this.url = url;
				
				if(this.url) uni.setStorageSync('initiateLogin',this.url)
				if(!isMiddleIndex){
					this.toLogin();
					return;
				}
				// #ifdef MP
				this.getCode(authData => {
					this.authLogin(authData, 'authOnlyLogin');
				});
				// #endif

				// #ifdef H5
				if (this.$util.isWeiXin() && this.wechatConfigStatus) {
					let authData = uni.getStorageSync('authInfo');
					if (authData) this.authLogin(authData);
					else this.getCode();
				} else {
					this.toLogin();
				}
				// #endif
				// #ifndef MP || H5
				this.toLogin();
				// #endif
			},
			// 跳转去登录页
			toLogin() {
				if (this.url) this.$util.redirectTo('/pages_tool/login/index', {
					back: encodeURIComponent(this.url)
				});
				else this.$util.redirectTo('/pages_tool/login/index');
			},
			cancelCompleteInfo() {
				if (this.$refs.completeInfoPopup) this.$refs.completeInfoPopup.close();
				this.$store.commit('setBottomNavHidden', false); // 显示底部导航
			},
			blurNickName(e) {
				if (e.detail.value) this.nickName = e.detail.value;
			},
			onChooseAvatar(e) {
				this.avatarUrl = e.detail.avatarUrl;
				uni.getFileSystemManager().readFile({
					filePath: this.avatarUrl, //选择图片返回的相对路径
					encoding: 'base64', //编码格式
					success: res => {
						let base64 = 'data:image/jpeg;base64,' + res.data; //不加上这串字符，在页面无法显示的哦

						this.$api.uploadBase64({
							base64,
							success: res => {
								if (res.code == 0) {
									this.headImg = res.data.pic_path;
								} else {
									this.$util.showToast({
										title: res.message
									});
								}
							},
							fail: () => {
								this.$util.showToast({
									title: '上传失败'
								});
							}
						})
					}
				});
			},
			openCompleteInfoPop() {
				this.getRegisterConfig();

				this.$refs.completeInfoPopup.open(() => {
					this.$store.commit('setBottomNavHidden', false); //显示底部导航
				});
				this.$store.commit('setBottomNavHidden', true); //隐藏底部导航
			},
			// 获取前台登录验证码开关配置
			getCaptchaConfig() {
				this.$api.sendRequest({
					url: '/api/config/getCaptchaConfig',
					success: res => {
						if (res.code >= 0) {
							this.isOpenCaptcha = res.data.shop_reception_login;
							if (this.isOpenCaptcha) this.getCaptcha();
						}
					}
				});
			},
			// 获取验证码
			getCaptcha() {
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
			// 发送手机动态码
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
				if (this.isOpenCaptcha == 1) {
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

				if (this.dynacodeData.seconds == 120) {
					this.dynacodeData.timer = setInterval(() => {
						this.dynacodeData.seconds--;
						this.dynacodeData.codeText = this.dynacodeData.seconds + 's后可重新获取';
					}, 1000);
				}

				this.$api.sendRequest({
					url: '/api/tripartite/mobileCode',
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
			// 表单验证
			verify(data) {
				let rule = [{
					name: 'mobile',
					checkType: 'required',
					errorMsg: '请输入手机号'
				}, {
					name: 'mobile',
					checkType: 'phoneno',
					errorMsg: '请输入正确的手机号'
				}];
				if (this.isOpenCaptcha == 1) {
					if (this.captcha.id != '') rule.push({
						name: 'captcha_code',
						checkType: 'required',
						errorMsg: '请输入验证码'
					});
				}
				rule.push({
					name: 'code',
					checkType: 'required',
					errorMsg: '请输入动态码'
				});

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
			// 微信公众号强制绑定手机号
			forceBindMobile() {
				let authData = uni.getStorageSync('authInfo');
				let data = {
					mobile: this.formData.mobile,
					key: this.formData.key,
					code: this.formData.dynacode
				};
				if (this.captcha.id != '') {
					data.captcha_id = this.captcha.id;
					data.captcha_code = this.formData.vercode;
				}

				if (authData) Object.assign(data, authData);
				if (authData.avatarUrl) data.headimg = authData.avatarUrl;
				if (authData.nickName) data.nickname = authData.nickName;

				if (uni.getStorageSync('source_member')) data.source_member = uni.getStorageSync('source_member');

				if (this.isSub) return;
				this.isSub = true;

				this.$api.sendRequest({
					url: '/api/tripartite/mobile',
					data,
					success: res => {
						if (res.code >= 0) {
							this.$store.commit('setToken', res.data.token);
							this.getMemberInfo();
							this.$store.dispatch('getCartNumber');
							this.$refs.completeInfoPopup.close();
							this.$store.commit('setBottomNavHidden', false); // 显示底部导航
							if (res.data.is_register) {
								this.$store.commit('setCanReceiveRegistergiftInfo',{status: true,path: this.$util.openRegisterRewardPath('/pages/index/index')});
								this.$util.loginComplete('/pages/index/index','redirectTo');
							}
							
							// if (res.data.is_register) this.$refs.registerReward.open(this.url);
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
			},
			// 微信小程序获取手机号
			getPhoneNumber(e) {
				if (e.detail.errMsg == 'getPhoneNumber:ok') {
					let authData = uni.getStorageSync('authInfo');
					if (authData) Object.assign(this.authMobileData, authData, e.detail);
					if (uni.getStorageSync('source_member')) this.authMobileData.source_member = uni.getStorageSync('source_member');

					this.$api.sendRequest({
						url: '/api/tripartite/getPhoneNumber',
						data: this.authMobileData,
						success: res => {
							if (res.code >= 0) {
								this.formData.mobile = res.data.mobile;
							} else {
								this.formData.mobile = '';
								this.$util.showToast({
									title: res.message
								});
							}
						}
					});
				} else {
					this.$util.showToast({
						title: '为了保证您账户的统一性，取消授权将无法为您提供服务'
					})
				}
			},
			// 一键授权手机号快捷登录
			quickLoginWithPhone(e) {
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
								this.getMemberInfo();
								this.$store.dispatch('getCartNumber');
								this.cancelCompleteInfo();

								if (res.data.is_register) {
									// 新用户注册成功
									this.$store.commit('setCanReceiveRegistergiftInfo', {
										status: true,
										path: this.$util.openRegisterRewardPath('/pages/index/index')
									});
									this.$util.loginComplete('/pages/index/index', {}, 'redirectTo');
								} else {
									// 老用户直接登录
									if (this.url) this.$util.loginComplete(this.url, {}, 'redirectTo');
									else this.$util.loginComplete('/pages/member/index/index', {}, 'redirectTo');
								}

								setTimeout(() => {
									uni.hideLoading();
								}, 1000);
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
						title: '取消授权将无法登录'
					});
				}
			},
			// 微信小程序强制绑定手机号
			bindMobile() {
				let data = this.authMobileData;
				let authData = uni.getStorageSync('authInfo');
				if (authData) Object.assign(data, authData);
				if (authData.avatarUrl) data.headimg = authData.avatarUrl;
				if (authData.nickName) data.nickname = authData.nickName;

				this.$api.sendRequest({
					url: '/api/tripartite/mobileauth',
					data,
					success: res => {
						if (res.code >= 0) {
							this.$store.commit('setToken', res.data.token);
							this.getMemberInfo();
							this.$store.dispatch('getCartNumber');
							this.cancelCompleteInfo();
							if (res.data.is_register) {
								this.$store.commit('setCanReceiveRegistergiftInfo',{status: true,path:this.$util.openRegisterRewardPath('/pages/index/index')});
								this.$util.loginComplete('/pages/index/index','redirectTo');
							}
							// if (res.data.is_register) this.$refs.registerReward.open(this.url);
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
					},
					fail: res => {
						this.$util.showToast({
							title: 'request:fail'
						});
					}
				});
			},
			/**
			 * 授权登录
			 */
			authLogin(data, type = 'authLogin') {
				uni.showLoading({
					title: '登录中'
				});
				uni.setStorageSync('authInfo', data);
				if (uni.getStorageSync('source_member')) data.source_member = uni.getStorageSync('source_member');
				this.$api.sendRequest({
					url: type == 'authLogin' ? '/api/login/auth' : '/api/login/authonlylogin',
					data,
					success: res => {
						if (res.code >= 0) {
							this.$store.commit('setToken', res.data.token);
							this.getMemberInfo();
							this.$store.dispatch('getCartNumber');
							if (res.data.is_register){
								this.$store.commit('setCanReceiveRegistergiftInfo',{status: true,path:this.$util.openRegisterRewardPath('/pages/index/index')});
								this.$util.loginComplete('/pages/index/index','redirectTo');
								// this.$refs.registerReward.open(this.url);
							}else{
								// if(this.url) this.$util.redirectTo(this.url,{},'redirectTo')
								// else this.$util.redirectTo('/pages/member/index',{},'redirectTo')
								if(this.url) this.$util.loginComplete(this.url,{},'redirectTo');
								else this.$util.loginComplete('/pages/member/index/index',{},'redirectTo')
							}
							this.cancelCompleteInfo();

							setTimeout(() => {
								uni.hideLoading();
							}, 1000);
						} else if (res.data == 'MEMBER_NOT_EXIST') {
							this.getRegisterConfig(() => {
								uni.hideLoading();
								// 如果开启第三方注册，则打开完善信息弹窗（已预填充默认昵称和头像）
								if (this.registerConfig.third_party == 1) {
									this.openCompleteInfoPop();
								} else if (this.registerConfig.third_party == 0) {
									// 如果关闭第三方注册，跳转到登录页
									this.toLogin();
								}
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
							title: '登录失败'
						});
					}
				});
			},
			// 微信公众号，强制绑定手机号，验证
			saveH5() {
				if (this.$util.isWeiXin() && this.forceBindingMobileControl) {
					let data = {
						mobile: this.formData.mobile,
						key: this.formData.key,
						code: this.formData.dynacode
					};
					if (this.captcha.id != '') {
						data.captcha_id = this.captcha.id;
						data.captcha_code = this.formData.vercode;
					}
					if (!this.verify(data)) return;
				}
				this.forceBindMobile();
			},
			// 微信小程序保存数据
			saveMp() {
				// 如果昵称为空，使用默认昵称
				if (this.nickName.length == 0) {
					this.nickName = '微信用户' + Math.random().toString(36).substr(2, 6);
				}
				let authData = uni.getStorageSync('authInfo');
				if (authData) Object.assign(authData, {
					nickName: this.nickName,
					avatarUrl: this.headImg ? this.headImg : ''  // 如果没有上传头像，使用空字符串，后端会使用默认头像
				});
				uni.setStorageSync('authInfo', authData);

				if (this.forceBindingMobileControl) this.bindMobile();
				else this.authLogin(authData);
			},
			// #ifdef MP-ALIPAY
			aliappGetUserinfo() {
				my.getOpenUserInfo({
					success: (res) => {
						let userInfo = JSON.parse(res.response).response
						if (userInfo.code && userInfo.code == '10000') {
							if (userInfo.avatar) {
								this.avatarUrl = userInfo.avatar;
								this.$api.pullImage({
									path: this.avatarUrl,
									success: res => {
										if (res.code == 0) {
											this.headImg = res.data.pic_path;
										} else {
											this.$util.showToast({
												title: res.message
											});
										}
									},
									fail: () => {
										this.$util.showToast({
											title: '头像拉取失败'
										});
									}
								})
							}
							this.nickName = userInfo.nickName
						} else {
							this.$util.showToast({
								title: userInfo.subMsg
							})
						}
					},
					fail: (err) => {
						this.$util.showToast({
							title: err.subMsg
						})
					}
				});
			},
			// #endif
			getMemberInfo() {
				this.$api.sendRequest({
					url: '/api/member/info',
					success: (res) => {
						if (res.code >= 0) {
							// 登录成功，存储会员信息
							this.$store.commit('setMemberInfo', res.data);
						}
					}
				});
			}
		}
	};
</script>

<style lang="scss">
	.complete-info-popup {
		.complete-info-wrap {
			background: #fff;
			padding: 50rpx 40rpx 40rpx;

			.head {
				position: relative;
				border-bottom: 2rpx solid $color-line;
				padding-bottom: 20rpx;

				.title {
					font-size: $font-size-toolbar;
					display: block;
				}

				.tips {
					font-size: $font-size-base;
					display: block;
				}

				.iconfont {
					position: absolute;
					right: 0;
					top: -30rpx;
					display: inline-block;
					width: 56rpx;
					height: 56rpx;
					line-height: 56rpx;
					text-align: right;
					font-size: $font-size-toolbar;
					font-weight: bold;
				}
			}

			.item-wrap {
				border-bottom: 2rpx solid $color-line;
				display: flex;
				align-items: center;
				padding: 16rpx 0;
				flex-wrap: wrap;

				.label {
					font-size: $font-size-toolbar;
					margin-right: 40rpx;
					width: 100rpx;
				}

				.tips-small {
					width: 100%;
					font-size: 24rpx;
					color: #999;
					margin-top: 8rpx;
					padding-left: 140rpx;
				}

				button {
					background: transparent;
					margin: 0;
					padding: 0;
					border-radius: 0;
					flex: 1;
					text-align: left;
					display: flex;
					align-items: center;
					font-size: $font-size-toolbar;
					border: none;

					image {
						width: 100rpx;
						height: 100rpx;
						border-radius: 10rpx;
						overflow: hidden;
					}
				}

				.iconfont {
					flex: 1;
					text-align: right;
					font-size: $font-size-tag;
				}

				input {
					flex: 1;
					height: 80rpx;
					box-sizing: border-box;
					font-size: $font-size-toolbar;
				}

				.send {
					border: 2rpx solid $base-color;
					height: 60rpx;
					line-height: 60rpx;
					border-radius: 60rpx;
					font-size: $font-size-tag;
					text-align: center;
					padding: 0 40rpx;
				}

				.captcha {
					height: 80rpx;
					width: 200rpx;
				}

				.auth-login {
					width: calc(100% - 100rpx);
					height: 80rpx;
					line-height: 80rpx;
					border-radius: 80rpx;
				}
			}

			.save-btn {
				width: 280rpx;
				height: 90rpx;
				line-height: 90rpx;
				background-color: #07c160;
				color: #fff;
				margin: 40rpx auto 20rpx;
			}

			.phone-auth-wrap {
				padding: 60rpx 0 40rpx;
				display: flex;
				flex-direction: column;
				align-items: center;

				.phone-auth-btn {
					width: 500rpx;
					height: 88rpx;
					line-height: 88rpx;
					border-radius: 44rpx;
					color: #fff;
					font-size: 32rpx;
					text-align: center;
					margin: 0;
					padding: 0;
					border: none;

					text {
						color: #fff;
					}
				}

				.tips-text {
					margin-top: 30rpx;
					font-size: 24rpx;
					color: #999;
					text-align: center;
				}
			}
		}
	}
</style>
<style scoped>
	/deep/ .reward-popup .uni-popup__wrapper-box {
		background: none !important;
		max-width: unset !important;
		max-height: unset !important;
		overflow: unset !important;
	}

	.complete-info-popup /deep/ .uni-popup__wrapper.bottom,
	.complete-info-popup /deep/ .uni-popup__wrapper.bottom .uni-popup__wrapper-box {
		border-top-left-radius: 30rpx !important;
		border-top-right-radius: 30rpx !important;
	}
</style>