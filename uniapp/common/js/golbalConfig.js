export default {
	data() {
		return {
			// 页面样式，动态设置主色调
			themeColor: '' //''--base-color:#fa5d14;--base-help-color:#ff7e00;'
		}
	},
	onLoad() {},
	onShow() {
		// 刷新多语言
		this.$langConfig.refresh();
		let time = setInterval(() => {
			let theme = this.themeStyle;
			if (theme && theme.main_color) {
				this.themeColorSet();
				clearInterval(time);
			}
		}, 50);
	},
	computed: {
		themeStyle() {
			return this.$store.state.themeStyle;
		},
		// 插件是否存在
		addonIsExist() {
			return this.$store.state.addonIsExist;
		},
		tabBarList() {
			return this.$store.state.tabBarList;
		},
		siteInfo() {
			return this.$store.state.siteInfo;
		},
		memberInfo() {
			return this.$store.state.memberInfo;
		},
		storeToken() {
			return this.$store.state.token;
		},
		bottomNavHidden() {
			return this.$store.state.bottomNavHidden;
		},
		globalStoreConfig() {
			return this.$store.state.globalStoreConfig;
		},
		globalStoreInfo() {
			return this.$store.state.globalStoreInfo;
		},
		// 定位信息
		location() {
			return this.$store.state.location;
		},
		// 定位信息（缓存）
		locationStorage() {
			let data = uni.getStorageSync('location');
			if (data) {
				var date = new Date();
				if (this.mapConfig.wap_valid_time > 0) {
					data.is_expired = (date.getTime() / 1000) > data.valid_time; // 是否过期
				} else {
					data.is_expired = false;
				}
			}
			return data;
		},
		// 默认总店（定位失败后使用）
		defaultStoreInfo() {
			return this.$store.state.defaultStoreInfo;
		},
		// 组件刷新计数
		componentRefresh() {
			return this.$store.state.componentRefresh;
		},
		// 客服配置
		servicerConfig() {
			return this.$store.state.servicerConfig;
		},
		diySeckillInterval() {
			return this.$store.state.diySeckillInterval;
		},
		tabBarHeight() {
			return this.$store.state.tabBarHeight;
		},
		mapConfig() {
			return this.$store.state.mapConfig;
		},
		initStatus(){
			return this.$store.state.initStatus;
		},
		copyright() {
			let copyright = this.$store.state.copyright;
			// 判断是否授权
			if (copyright && !copyright.auth) {
				copyright.logo = 'public/uniapp/common/logo_copy.png';
				copyright.copyright_link = 'http://www.niushop.com';
			}
			return copyright;
		},
		cartList() {
			return this.$store.state.cartList;
		},
		cartIds() {
			return this.$store.state.cartIds;
		},
		cartNumber() {
			return this.$store.state.cartNumber;
		},
		cartMoney() {
			return this.$store.state.cartMoney;
		}
	},
	methods: {
		themeColorSet() {
			let theme = this.themeStyle;
			this.themeColor = `--base-color:${theme.main_color};--base-help-color:${theme.aux_color};`;
			if (this.tabBarHeight != '56px') this.themeColor += `--tab-bar-height:${this.tabBarHeight};`;
			Object.keys(theme).forEach(key => {
				let data = theme[key];
				if (typeof(data) == "object") {
					Object.keys(data).forEach(k => {
						this.themeColor += '--' + k.replace(/_/g, "-") + ':' + data[k] + ';';
					});
				} else if (typeof(key) == "string" && key) {
					this.themeColor += '--' + key.replace(/_/g, "-") + ':' + data + ';';
				}
			});
			for (let i = 9; i >= 5; i--) {
				let color = this.$util.colourBlend(theme.main_color, '#ffffff', (i / 10));
				this.themeColor += `--base-color-light-${i}:${color};`;
			}
		},
		// 颜色变浅（>0）、变深函数（<0）
		lightenDarkenColor(color, amount) {

			var usePound = false;

			if (color[0] == "#") {
				color = color.slice(1);
				usePound = true;
			}

			var num = parseInt(color, 16);

			var r = (num >> 16) + amount;

			if (r > 255) r = 255;
			else if (r < 0) r = 0;

			var b = ((num >> 8) & 0x00FF) + amount;

			if (b > 255) b = 255;
			else if (b < 0) b = 0;

			var g = (num & 0x0000FF) + amount;

			if (g > 255) g = 255;
			else if (g < 0) g = 0;

			return (usePound ? "#" : "") + (g | (b << 8) | (r << 16)).toString(16);

		},
		/**
		 * 切换门店
		 * @param {Object} info 门店信息
		 * @param {Object} isJump 是否跳转到首页
		 */
		changeStore(info, isJump) {
			if (info) {
				this.$store.commit('setGlobalStoreInfo', info);
			}
			let route = this.$util.getCurrRoute();
			if (isJump && route != 'pages/index/index') {
				uni.setStorageSync('manual_change_store', true); // 手动切换门店
				this.$store.dispatch('getCartNumber'); //重新获取购物车数据
				this.$util.redirectTo('/pages/index/index');
			}
		}
	},
	filters: {
		/**
		 * 金额格式化输出
		 * @param {Object} money
		 */
		moneyFormat(money) {
			if (isNaN(parseFloat(money))) return money;
			return parseFloat(money).toFixed(2);
		}
	}
}