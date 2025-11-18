export default {
	data() {
		return {
			authInfo: {}
		}
	},
	methods: {
		/**
		 * 获取用户登录凭证code
		 */
		getCode(callback) {
			// 微信小程序
			// #ifdef MP-WEIXIN
			uni.login({
				provider: 'weixin',
				timeout: 3000,
				success: res => {
					if (res.code) {
						this.$api.sendRequest({
							url: '/weapp/api/weapp/authcodetoopenid',
							data: {
								code: res.code
							},
							success: res => {
								if (res.code >= 0) {
									if (res.data.openid) this.authInfo.weapp_openid = res.data.openid;
									if (res.data.unionid) this.authInfo.wx_unionid = res.data.unionid;
									typeof callback == 'function' && callback(this.authInfo);
								} else {
									this.$util.showToast({
										title: res.message ? res.message : '小程序配置错误'
									});
								}
							}
						})
					}
				},
				fail: (res) => {
					// #ifdef MP-WEIXIN
					let scene = wx.getLaunchOptionsSync().scene;
					if ([1154, 1155].indexOf(scene) == -1) {
						this.$util.showToast({
							title: res.errMsg
						});
					}
					// #endif
				}
			})
			// #endif

			// #ifdef MP-ALIPAY
			uni.login({
				timeout: 3000,
				success: res => {
					if (res.code) {
						this.$api.sendRequest({
							url: '/aliapp/api/aliapp/authcodetouserid',
							data: {
								code: res.code
							},
							success: res => {
								if (res.code >= 0) {
									if (res.data.user_id) this.authInfo.ali_openid = res.data.user_id;
									typeof callback == 'function' && callback(this.authInfo);
								} else {
									this.$util.showToast({
										title: res.message ? res.message : '小程序配置错误'
									});
								}
							}
						})
					}
				},
				fail: (res) => {
					this.$util.showToast({
						title: res.errMsg
					});
				}
			})
			// #endif

			// #ifdef H5
			if (this.$util.isWeiXin()) {
				this.$api.sendRequest({
					url: '/wechat/api/wechat/authcode',
					data: {
						redirect_url: location.href,
						scopes: 'snsapi_userinfo'
					},
					success: res => {
						if (res.code >= 0) {
							location.href = res.data;
						}
					}
				});
			}
			// #endif
		}
	}
}