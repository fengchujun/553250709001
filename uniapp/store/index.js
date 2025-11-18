import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)

import Http from '../common/js/http.js'
import colorList from '../common/js/style_color.js'

const store = new Vuex.Store({
	state: {
		token: null,
		siteInfo: null,
		memberInfo: null,
		tabBarList: '',
		siteState: 1,
		themeStyle: '',
		addonIsExist: {
			bundling: 0,
			coupon: 0,
			discount: 0,
			fenxiao: 0,
			gift: 0,
			groupbuy: 0,
			manjian: 0,
			memberconsume: 0,
			memberrecharge: 0,
			memberregister: 0,
			membersignin: 0,
			memberwithdraw: 0,
			memberrecommend: 0,
			pintuan: 0,
			pointexchange: 0,
			seckill: 0,
			store: 0,
			topic: 0,
			bargain: 0,
			membercancel: 0,
			servicer: 0,
			supermember: 0,
			giftcard: 0,
			divideticket: 0,
			scenefestival: 0,
			birthdaygift: 0,
			pinfan: 0,
			form: 0
		},
		
		authInfo: {}, // 授权信息
		flRefresh: 0,
		location: null, // 定位信息
		defaultImg: {
			goods: '',
			head: '',
			store: '',
			article: ''
		},
		cartList: {},
		cartIds: [],
		cartNumber: 0,
		cartMoney: 0,
		cartChange: 0,
		wechatConfigStatus:0,
		bottomNavHidden: false, // 底部导航是否隐藏，true：隐藏，false：显示
		globalStoreConfig: null, // 门店配置
		globalStoreInfo: null, // 门店信息
		defaultStoreInfo: null, // 默认门店
		cartPosition: null, // 购物车所在位置
		componentRefresh: 0, // 组件刷新
		servicerConfig: null, // 客服配置
		diySeckillInterval: 0,
		diyGroupPositionObj: {},
		diyGroupShowModule: '',
		tabBarHeight: '56px',
		mapConfig: {
			tencent_map_key: '',
			wap_is_open: 1,
			wap_valid_time: 0,
		},
		copyright: null,
		initStatus:false,
		offlineWhiteList:['pages/order/payment','pages/order/list','pages/order/detail'],//线下支付白名单
		canReceiveRegistergiftInfo: {
			status: false,
			path: ''
		},
	},
	mutations: {
		// 设置是否可以领取新人礼
		setCanReceiveRegistergiftInfo(state, data) {
			state.canReceiveRegistergiftInfo = data;
		},
		// 设置那些组件展示
		setDiyGroupShowModule(state, data) {
			state.diyGroupShowModule = data;
		},
		// 设置diyGroup中组件原有高度，通过他们来实现在首页的定位
		setDiyGroupPositionObj(state, data) {
			state.diyGroupPositionObj = Object.assign({}, state.diyGroupPositionObj, data);
		},
		setSiteState(state, siteStateVal) {
			state.siteState = siteStateVal;
		},
		setThemeStyle(state, value) {
			state.themeStyle = value
			uni.setStorageSync('themeStyle', value); // 初始化数据调用
		},
		setTabBarList(state, value) {
			state.tabBarList = value;
		},
		setAddonIsExist(state, value) {
			state.addonIsExist = value;
			uni.setStorageSync('addonIsExist', value); // 初始化数据调用
		},
		setToken(state, value) {
			state.token = value;
			if (value) {
				uni.setStorageSync('token', value); // 初始化数据调用
			} else {
				uni.removeStorageSync('token');
			}
		},
		setAuthinfo(state, value) {
			state.authInfo = value;
		},
		setflRefresh(state, flRefreshVal) {
			state.flRefresh = flRefreshVal;
		},
		setLocation(state, value) {
			var date = new Date();
			date.setSeconds(60 * state.mapConfig.wap_valid_time);
			value.valid_time = date.getTime() / 1000; // 定位信息 5分钟内有效，过期后将重新获取定位信息
			state.location = value;
			uni.setStorageSync('location', value); // 初始化数据调用
		},
		setDefaultImg(state, value) {
			state.defaultImg = value;
			uni.setStorageSync('defaultImg', value); // 初始化数据调用
		},
		setSiteInfo(state, value) {
			state.siteInfo = value;
			uni.setStorageSync('siteInfo', value); // 初始化数据调用
		},
		setCartChange(state) {
			state.cartChange += 1;
		},
		setBottomNavHidden(state, value) {
			state.bottomNavHidden = value;
		},
		setGlobalStoreConfig(state, value) {
			state.globalStoreConfig = value;
			uni.setStorageSync('globalStoreConfig', value); // 初始化数据调用
		},
		setGlobalStoreInfo(state, value) {
			state.globalStoreInfo = value;
			uni.setStorageSync('globalStoreInfo', value); // 初始化数据调用
		},
		setDefaultStoreInfo(state, value) {
			state.defaultStoreInfo = value;
			uni.setStorageSync('defaultStoreInfo', value); // 初始化数据调用
		},
		setCartPosition(state, value) {
			state.cartPosition = value;
		},
		setComponentRefresh(state) {
			state.componentRefresh += 1;
		},
		// 客服配置
		setServicerConfig(state, value) {
			state.servicerConfig = value;
			uni.setStorageSync('servicerConfig', value);
		},
		setDiySeckillInterval(state, value) {
			state.diySeckillInterval = value;
		},
		setTabBarHeight(state, value) {
			state.tabBarHeight = value;
		},
		setMapConfig(state, value) {
			state.mapConfig = value;
			uni.setStorageSync('mapConfig', value);
		},
		setCopyright(state, value) {
			state.copyright = value;
			uni.setStorageSync('copyright', value);
		},
		setMemberInfo(state, value) {
			// 会员被锁定后，清除会员登录信息
			if (value && value.status == 0) {
				value = null;
			}
			state.memberInfo = value;
			if (value) {
				uni.setStorageSync('memberInfo', value);
			} else {
				// 会员为空时，清除会员登录信息
				uni.removeStorageSync('memberInfo');
				this.commit('setToken', '');
				this.dispatch('emptyCart');
				//uni.removeStorageSync('authInfo');
			}
		},
		setCartNumber(state, cartNumber) {
			state.cartNumber = cartNumber
		},
		setCartList(state, value) {
			state.cartList = value;
		},
		setCartIds(state, value) {
			state.cartIds = value;
		},
		setCartMoney(state, value) {
			state.cartMoney = value;
		},
		setInitStatus(state,value){
			state.initStatus = value
		},
		setWechatConfigStatus(state,value){
			state.wechatConfigStatus = value
		}
	},
	actions: {
		init() {
			return new Promise((resolve, reject) => {
				Http.sendRequest({
					url: '/api/config/init',
					success: res => {
						var data = res.data;
						if (data) {

							this.commit('setThemeStyle', colorList[data.style_theme.name]);

							// 底部导航
							this.commit('setTabBarList', data.diy_bottom_nav);

							this.commit('setAddonIsExist', data.addon_is_exist);

							this.commit('setDefaultImg', data.default_img);

							this.commit('setSiteInfo', data.site_info);

							this.commit('setServicerConfig', data.servicer);

							this.commit('setCopyright', data.copyright);

							this.commit('setMapConfig', data.map_config);

							this.commit('setGlobalStoreConfig', data.store_config);
							
							this.commit('setWechatConfigStatus',data.wechat_config_status)
							// 默认总店
							if (data.store_info) {
								this.commit('setDefaultStoreInfo', data.store_info);
							} else {
								// 清空不存在的门店信息
								this.commit('setDefaultStoreInfo', null);
								this.commit('setGlobalStoreInfo', null);
							}
							this.commit('setInitStatus',true)
							resolve(data);
						}
					}
				});
			})
		},
		// 查询购物车列表、总数量、总价格
		getCartNumber() {
			Http.sendRequest({
				url: '/api/cart/lists',
				data: {},
				success: res => {
					if (res.code == 0) {
						let list = {};
						let ids = [];
						let totalMoney = 0;
						let totalNum = 0;

						if (res.data.length) {

							res.data.forEach((item) => {
								let cart = {
									cart_id: item.cart_id,
									goods_id: item.goods_id,
									sku_id: item.sku_id,
									num: item.num,
									discount_price: item.discount_price,
									min_buy: item.min_buy,
									stock: item.stock,
								};

								if (!list['goods_' + cart.goods_id]) {
									list['goods_' + cart.goods_id] = {};
								}
								list['goods_' + cart.goods_id]['max_buy'] = item.max_buy;
								list['goods_' + cart.goods_id]['goods_name'] = item.goods_name;
								list['goods_' + cart.goods_id]['sku_' + cart.sku_id] = cart;
								ids.push(cart.cart_id);
							});

							for (let goods in list) {
								let num = 0;
								let money = 0;
								for (let sku in list[goods]) {
									let item = list[goods][sku];
									if (typeof item == 'object') {
										num += item.num;
										money += parseFloat(item.discount_price) * parseInt(item.num);
									}
								}
								list[goods].num = num;
								list[goods].total_money = money;

								totalNum += num;
								totalMoney += money;

							}
						}
						this.commit('setCartList', list);

						this.commit('setCartIds', ids);

						this.commit('setCartNumber', totalNum);

						this.commit('setCartMoney', totalMoney);

					}
				}
			});
		},
		// 清空购物车 ns-goods-sku-index组件中引用
		emptyCart() {
			this.commit('setCartList', {});
			this.commit('setCartIds', []);
			this.commit('setCartNumber', 0);
			this.commit('setCartMoney', 0);
		},
		// 计算购物车数量、价格
		cartCalculate() {

			let ids = [];
			let totalMoney = 0;
			let totalNum = 0;

			for (let k in this.state.cartList) {
				let item = this.state.cartList[k];

				let num = 0;
				let money = 0;
				for (let sku in item) {
					if (typeof item[sku] == 'object') {
						num += item[sku].num;
						money += parseFloat(item[sku].discount_price) * parseInt(item[sku].num);
						ids.push(item[sku].cart_id);
					}
				}
				item.num = num;
				item.total_money = money;

				totalNum += num;
				totalMoney += money;

			}

			this.commit('setCartNumber', totalNum);

			this.commit('setCartMoney', totalMoney);

			this.commit('setCartIds', ids);

		}
	}
})
export default store