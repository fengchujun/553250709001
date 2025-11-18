import Config from './config.js'
import Util from './util.js'
import store from '@/store/index.js'
import { Utils } from 'common/js/map-wx-jssdk.js';
	
// #ifdef H5
const app_type = Util.isWeiXin() ? 'wechat' : 'h5';
const app_type_name = Util.isWeiXin() ? '微信公众号' : 'H5';
// #endif

// #ifdef MP-WEIXIN
const app_type = 'weapp';
const app_type_name = '微信小程序';
// #endif

// #ifdef MP-ALIPAY
const app_type = 'aliapp';
const app_type_name = '支付宝小程序';
// #endif

// #ifdef MP-BAIDU
const app_type = 'baiduapp';
const app_type_name = '百度小程序';
// #endif

// #ifdef MP-TOUTIAO
const app_type = 'MP-TOUTIAO';
const app_type_name = '头条小程序';
// #endif

// #ifdef MP-QQ
const app_type = 'MP-QQ';
const app_type_name = 'QQ小程序';
// #endif

// #ifdef APP-PLUS
const app_type = 'app';
const app_type_name = 'APP';
// #endif

export default {
	sendRequest(params) {
		if (!Config.baseUrl) {
			uni.showToast({
				title: '未配置请求域名',
				'icon': 'none',
				duration: 10000
			});
			return;
		}

		var method = params.data != undefined ? 'POST' : 'GET', // 请求方式
			url = Config.baseUrl + params.url, // 请求路径
			data = {
				app_type,
				app_type_name
			};

		// token
		data.token = store.state.token || '';

		// 门店id
		var default_store_info = store.state.defaultStoreInfo;
		if (default_store_info) {
			data.store_id = default_store_info.store_id;
		}

		var store_info = store.state.globalStoreInfo;

		if (store_info) data.store_id = store_info.store_id;

		// 参数
		if (params.data != undefined) Object.assign(data, params.data);

		if (params.async === false) {
			//同步
			return new Promise((resolve, reject) => {
				uni.request({
					url: url,
					method: method,
					data: data,
					header: params.header || {
						// 'Accept': 'application/json',
						'content-type': 'application/x-www-form-urlencoded;application/json'
					},
					dataType: params.dataType || 'json',
					responseType: params.responseType || 'text',
					success: (res) => {
						// try {
						// 	res.data = JSON.parse(res.data);
						// } catch (e) {
						// 	//TODO handle the exception
						// 	console.log('api error：', e);
						// }
						if (res.data.code == -3 && store.state.siteState > 0) {
							store.commit('setSiteState', -3);
							Util.redirectTo('/pages_tool/storeclose/storeclose', {}, 'reLaunch');
							return;
						}
						if (res.data.refreshtoken) {
							store.commit('setToken', res.data.refreshtoken);
						}
						if (res.data.code == -10009 || res.data.code == -10010) {
							store.commit('setToken', '');
							store.commit('setMemberInfo', '');
						}
						resolve(res.data);
					},
					fail: (res) => {
						if (res.errMsg && res.errMsg == 'request:fail url not in domain list') {
							uni.showToast({
								title: Config.baseUrl + '不在request 合法域名列表中',
								'icon': 'none',
								duration: 10000
							});
							return;
						}
						reject(res);
					},
					complete: (res) => {
						if ((res.errMsg && res.errMsg != "request:ok") || (res.statusCode && (res.statusCode >= 400 && res.statusCode < 500) )) {
							/* uni.showToast({
								title: '网络错误，请稍后再试！',
								'icon': 'none',
								duration: 10000
							}); */
							return;
						}
						reject(res.data);
					}
				});
			});
		} else {
			//异步
			uni.request({
				url: url,
				method: method,
				data: data,
				header: params.header || {
					// 'Accept': 'application/json',
					'content-type': 'application/x-www-form-urlencoded;application/json'
				},
				dataType: params.dataType || 'json',
				responseType: params.responseType || 'text',
				success: (res) => {
					// try {
					// 	res.data = JSON.parse(res.data);
					// } catch (e) {
					// 	//TODO handle the exception
					// 	console.log('api error：', e);
					// }
					if (res.data.code == -3 && store.state.siteState > 0) {
						store.commit('setSiteState', -3);
						Util.redirectTo('/pages_tool/storeclose/storeclose', {}, 'reLaunch');
						return;
					}
					if (res.data.refreshtoken) {
						store.commit('setToken', res.data.refreshtoken);
					}
					if (res.data.code == -10009 || res.data.code == -10010) {
						store.commit('setToken', '');
						store.commit('setMemberInfo', '');
					}
					typeof params.success == 'function' && params.success(res.data);
				},
				fail: (res) => {
					if (res.errMsg && res.errMsg == 'request:fail url not in domain list') {
						uni.showToast({
							title: Config.baseUrl + '不在request 合法域名列表中',
							'icon': 'none',
							duration: 10000
						});
						return;
					}
					typeof params.fail == 'function' && params.fail(res);
				},
				complete: (res) => {
					if ((res.errMsg && res.errMsg != "request:ok") || (res.statusCode && (res.statusCode >= 400 && res.statusCode < 500) )) {
						/* uni.showToast({
							title: '网络错误，请稍后再试！',
							'icon': 'none',
							duration: 10000
						}); */
						return;
					}
					typeof params.complete == 'function' && params.complete(res.data);
				}
			});
		}
	},
	needMd5Fn(params,callback) {
		uni.request({
			url: Config.baseUrl + '/api/config/getApiConfig',
			method: 'POST',
			header: {
				'content-type': 'application/x-www-form-urlencoded;application/json'
			},
			data: {
				app_type,
				app_type_name,
			},
			dataType: 'json',
			responseType: 'text',
			success: res => {
				var sign_str = Utils.hexMD5('key=' + res.data.data.key+'&time='+res.data.data.time)
				typeof callback == 'function' && callback(sign_str,res.data.data.time);
			},
			fail: () => {
				typeof params.fail == 'function' && params.fail(res);
			}
		});
	},
	uploadBase64(params) {
		this.needMd5Fn(params,(sign_str,time)=>{
			uni.request({
				url: Config.baseUrl + '/api/upload/headimgBase64',
				method: 'POST',
				header: {
					'content-type': 'application/x-www-form-urlencoded;application/json'
				},
				data: {
					app_type,
					app_type_name,
					images: params.base64,
					token: store.state.token || '',
					sign: sign_str,
					time: time
				},
				dataType: 'json',
				responseType: 'text',
				success: res => {
					typeof params.success == 'function' && params.success(res.data);
				},
				fail: () => {
					typeof params.fail == 'function' && params.fail(res);
				}
			});
		})
	},
	pullImage(params) {
		this.needMd5Fn(params,(sign_str,time)=>{
			uni.request({
				url: Config.baseUrl + '/api/upload/headimgPull',
				method: 'POST',
				header: {
					'content-type': 'application/x-www-form-urlencoded;application/json'
				},
				data: {
					app_type,
					app_type_name,
					path: params.path,
					token: store.state.token || '',
					sign: sign_str,
					time: time
				},
				dataType: 'json',
				responseType: 'text',
				success: res => {
					typeof params.success == 'function' && params.success(res.data);
				},
				fail: () => {
					typeof params.fail == 'function' && params.fail(res);
				}
			});
		})
	},
	upload(params) {
		uni.uploadFile({
			url: Config.baseUrl + params.url,
			filePath: params.filePath,
			name: params.name || 'file',
			fileType: params.fileType || 'image',
			formData: {
				app_type,
				app_type_name,
				token: store.state.token || ''
			},
			header: {
				'content-type': 'application/x-www-form-urlencoded;application/json'
			},
			success: (res) => {
				typeof params.success == 'function' && params.success(JSON.parse(res.data));
			},
			fail: (res) => {
				typeof params.fail == 'function' && params.fail(res);
			}
		});
	}
}