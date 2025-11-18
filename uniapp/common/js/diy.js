import { QQMapWX } from 'common/js/map-wx-jssdk.js';
import Config from '@/common/js/config.js';

let systemInfo = uni.getSystemInfoSync();
export default {
	data() {
		return {
			diyData: {
				global: {
					title: '',
					popWindow: {
						imageUrl: '',
						count: -1,
						link: {},
						imgWidth: '',
						imgHeight: ''
					}
				}
			},
			id: 0,
			name: '',

			topIndexValue: null,
			statusBarHeight: systemInfo.statusBarHeight,
			collectTop: 44,
			showTip: false,
			mpCollect: false,
			mpShareData: null, //小程序分享数据
			scrollTop: 0, // 滚动位置
			paddingTop: (44 + systemInfo.statusBarHeight) + 'px',
			marginTop: -(44 + systemInfo.statusBarHeight) + 'px',
			followOfficialAccount: null, // 关注公众号组件

			latitude: null, // 纬度
			longitude: null, // 经度
			currentPosition: '', // 当前位置
			currentStore:null,//当前门店
			nearestStore: null, // 离自己最近的门店

			diyRoute: '', // 页面路由
			openBottomNav: false,
			isShowCopyRight: false,
			option: null,
			firstDiy: true
		};
	},
	onLoad(option) {
		this.option = option;
		uni.hideTabBar();
		// 支付宝小程序传参处理
		// #ifdef MP-ALIPAY
		let aliapp_option = my.getLaunchOptionsSync();
		aliapp_option.query && Object.assign(option, aliapp_option.query);
		// #endif
		
		// 处理分享人数据
		if (option.source_member) uni.setStorageSync('source_member', option.source_member);// 分享链接进入
		if (option.scene) {// 小程序扫码进入
			var sceneParams = decodeURIComponent(option.scene);
			sceneParams = sceneParams.split('&');
			if (sceneParams.length) {
				sceneParams.forEach(item => {
					if (item.indexOf('m') != -1) uni.setStorageSync('source_member', item.split('-')[1]);
				});
			}
		}

		// H5地图选择位置回调
		// #ifdef H5
		if (option.module && option.module == 'locationPicker') {
			option.name = ''; // 自定义页面传参id和name，防止获取地址时变量混淆
			this.latitude = option.latng.split(',')[0];
			this.longitude = option.latng.split(',')[1];
		}
		// #endif

		//自定义页面的id和名称
		this.id = option.id || 0;
		this.name = option.name || '';
		
		//获取当前门店信息 必须是首页且不是手动切换操作
		let current_route = this.$util.getCurrentRoute();
		let manualChangeStore = uni.getStorageSync('manual_change_store'); // 手动切换门店
		if(current_route.path.indexOf('/pages/index/index') > -1 && !manualChangeStore){
			this.getCurrentStore(option);
		}
	},
	onShow() {
		if(this.firstDiy){
			this.firstDiy = false;
			this.getDiyMethod();
		}
		this.onShowMethod();
	},
	onHide() {
		// 清除限时秒杀定时器
		this.$store.commit('setDiySeckillInterval', 0);
	},
	computed: {
		bgColor() {
			let str = '';
			if (this.diyData && this.diyData.global) {
				str = this.diyData.global.pageBgColor;
			}
			return str;
		},
		bgImg() {
			let str = '';
			if (this.diyData && this.diyData.global) {
				str = this.diyData.global.topNavBg ? 'url(' + this.$util.img(this.diyData.global.bgUrl) + ')' : this.diyData.global.pageBgColor;
			}
			return str;
		},
		bgUrl() {
			let str = '';
			if (this.diyData && this.diyData.global) {
				str = this.diyData.global.topNavBg ? 'transparent' : this.diyData.global.bgUrl;
			}
			return str;
		},
		backgroundUrl() {
			var str = this.diyData.global.bgUrl && this.diyData.global.bgUrl != 'transparent' ? 'url(' + this.$util.img(this.diyData.global.bgUrl) + ') ' : '';
			return str;
		},
		textNavColor() {
			if (this.diyData && this.diyData.global && this.diyData.global.textNavColor) {
				return this.diyData.global.textNavColor;
			} else {
				return '#ffffff';
			}
		},
		//计算首页弹框的显示宽高
		popWindowStyle() {
			// 做大展示宽高
			let max_width = 290;
			let max_height = 410;
			// 参照宽高
			let refer_width = 290;
			let refer_height = 290;

			let scale = this.diyData.global.popWindow.imgHeight / this.diyData.global.popWindow.imgWidth;
			let width, height;
			if (scale < refer_height / refer_width) {
				width = max_width;
				height = width * scale;
			} else {
				height = max_height;
				width = height / scale;
			}

			let obj = '';
			if (this.diyData.global.popWindow && this.diyData.global.popWindow.count != -1 && this.diyData.global.popWindow.imageUrl) {
				obj += 'height:' + (height * 2) + 'rpx;';
				obj += 'width:' + (width * 2) + 'rpx;';
			}
			return obj;
		}
	},
	watch: {
		/* location: function (nVal) {
			if (nVal && !this.latitude && !this.longitude) {
				this.latitude = nVal.latitude;
				this.longitude = nVal.longitude;
				this.getStoreInfoByLocation();
			}
		}, */
		initStatus:function (val) {
			if(!this.option.store_id) this.getLocation();
		}
	},
	methods: {
		async getDiyMethod(){
			await this.getDiyInfo();
			
			this.$store.commit('setDiySeckillInterval', 1);
			this.$store.commit('setComponentRefresh');
		},
		onShowMethod() {

			// 定位信息过期后，重新获取定位
			// if (this.mapConfig.wap_is_open == 1 && this.locationStorage && this.locationStorage.is_expired) {
			// 	this.$util.getLocation({
			// 		fail: (res) => {
			// 			// 失败了不需要做任何处理，保持之前的门店选择即可
			// 		}
			// 	});
			// }

			if (this.storeToken) {
				//记录分享关系
				if (uni.getStorageSync('source_member')) {
					this.$util.onSourceMember(uni.getStorageSync('source_member'));
				}
			}
			
			

			let manualChangeStore = uni.getStorageSync('manual_change_store'); // 手动切换门店
			if (manualChangeStore) {
				uni.removeStorageSync('manual_change_store');
				let manualStoreInfo = uni.getStorageSync('manual_store_info'); // 手动选择门店
				uni.removeStorageSync('manual_store_info');
				if (manualStoreInfo) {
					this.currentStore = manualStoreInfo;
				}
				this.closeGetLocationFailPopup();
				// 滚动至顶部
				uni.pageScrollTo({
					duration: 200,
					scrollTop: 0
				});
			}
		},
		callback() {
			if (this.$refs.indexPage) {
				this.$refs.indexPage.initPageIndex();
			}
		},
		//计算高度
		getHeight() {
			// #ifdef H5
			if (this.diyData && this.diyData.global && this.diyData.global.navBarSwitch) {
				// H5端，导航栏样式1 2 3不显示，要减去高度
				if ([1, 2, 3].indexOf(parseInt(this.diyData.global.navStyle)) != -1) {
					this.paddingTop = 0;
					this.marginTop = 0;
				}
			}
			// #endif

			// #ifdef MP || APP-PLUS
			let time = setInterval(() => {
				this.$nextTick(() => {
					const query = uni.createSelectorQuery().in(this);
					query.select('.page-header').boundingClientRect(data => {
						if (data && data.height) {
							// 从状态栏高度开始算
							if (!this.diyData.global.topNavBg) {
								this.paddingTop = 0;
								this.marginTop = 0;
							} else {
								this.paddingTop = data.height + 'px';
								this.marginTop = -data.height + 'px';
							}

							clearInterval(time);
						}
					}).exec();
				});
			}, 50);
			// #endif
		},

		async getDiyInfo() {
			let res = await this.$api.sendRequest({
				url: '/api/diyview/info',
				data: {
					id: this.id,
					name: this.name
				},
				async: false
			});
			if (res.code != 0 || !res.data) {
				if (this.$refs.loadingCover) this.$refs.loadingCover.hide();

				if (res.code == -3) {
					this.$util.showToast({
						title: res.message
					});
					this.diyData = {};
					return;
				}

				this.$util.showToast({
					title: '未配置自定义页面数据'
				});
				this.diyData = {};
				return;
			}

			let diyDataValue = res.data;
			if (diyDataValue.value) {
				this.diyData = JSON.parse(diyDataValue.value);
				this.$langConfig.title(this.diyData.global.title);
				this.mpCollect = this.diyData.global.mpCollect;
				this.setPublicShare();
				if (this.diyData.global.popWindow && this.diyData.global.popWindow.imageUrl) {
					// 弹框形式，首次弹出 1，每次弹出 0
					setTimeout(() => {
						if (this.diyData.global.popWindow.count == 1) {
							var popwindow_count = uni.getStorageSync(this.id + this.name + '_popwindow_count');
							if ((this.$refs.uniPopupWindow && popwindow_count == '') || (
								this.$refs.uniPopupWindow && popwindow_count == 1)) {
								this.$refs.uniPopupWindow.open();
								uni.setStorageSync(this.id + this.name + '_popwindow_count', 1);
							}
						} else if (this.diyData.global.popWindow.count == 0) {
							this.$refs.uniPopupWindow.open();
							uni.setStorageSync(this.id + this.name + '_popwindow_count', 0);
						}
					}, 500);
				}

				// 修改diy数据结构排序
				let searchIndex = -1;
				let topCategoryIndex = -1;
				this.diyData.value.forEach((item, index) => {
					if (item.componentName == 'Search') {
						if (item.positionWay == 'fixed') {
							searchIndex = index;
						}
					}
					if (item.componentName == 'TopCategory') {
						topCategoryIndex = index;
					}
				})
				if (searchIndex != -1 && topCategoryIndex != -1) {
					let searchData = this.diyData.value.slice(searchIndex, searchIndex + 1);
					let topCategoryData = this.diyData.value.slice(topCategoryIndex, topCategoryIndex + 1);
					this.diyData.value.splice(searchIndex, 1);
					if (searchIndex > topCategoryIndex) {
						this.diyData.value.splice(topCategoryIndex, 1);
						this.diyData.value.splice(0, 0, ...topCategoryData);
						this.diyData.value.splice(1, 0, ...searchData);
					} else {
						this.diyData.value.splice(0, 0, ...searchData);
					}
				} else if (searchIndex != -1 && topCategoryIndex == -1) {
					let searchData = this.diyData.value.slice(searchIndex, searchIndex + 1);
					this.diyData.value.splice(searchIndex, 1);
					this.diyData.value.splice(0, 0, ...searchData);
				}
				this.topIndexValue = null;
				for (var i = 0; i < this.diyData.value.length; i++) {
					// 分类导航组件
					if (this.diyData.value[i].componentName == 'TopCategory') {
						this.topIndexValue = this.diyData.value[i];
						this.topIndexValue.moduleIndex = i; //设置定位索引，根据此来确定定位顺序
						this.diyData.value.splice(i, 1);
						continue;
					}

					// 关注公众号组件
					if (this.diyData.value[i].componentName == 'FollowOfficialAccount') {
						this.followOfficialAccount = this.diyData.value[i];
						// #ifdef H5
						this.diyData.value.splice(i, 1);
						// #endif
						continue;
					}

				}
				// #ifdef MP
				//小程序收藏
				if (!uni.getStorageSync('isCollect') && this.diyData.global.mpCollect) {
					this.$refs.collectPopupWindow.open();
					this.showTip = true;
				}
				// #endif

				this.getHeight();
				if (this.diyData && this.diyData.global) {
					this.openBottomNav = this.diyData.global.openBottomNav;
				}
				this.isShowCopyRight = true;
				
				//小程序分享
				// #ifdef MP-WEIXIN
					let path = this.$util.getCurrentRoute().path;
					if (path == '/pages/member/index') {
						this.mpShareData = {};
						return;
					}
					let share_path = path;
					if(this.$store.state.memberInfo && this.$store.state.memberInfo.member_id){
						share_path = this.$util.getCurrentShareRoute(this.$store.state.memberInfo.member_id).path
					}
					let appMessageData = {
						title: this.diyData.global.weappShareTitle,
						path: share_path,
						imageUrl: this.$util.img(this.diyData.global.weappShareImage),
						success: res => {},
						fail: res => {}
					}
					let timeLineData = {
						title: this.diyData.global.weappShareTitle,
						query: share_path,
						imageUrl: this.$util.img(this.diyData.global.weappShareImage),
					}
					
					this.mpShareData = {
						appMessage: appMessageData,
						timeLine: timeLineData
					};
					//console.log(this.mpShareData, 'this.mpShareData');
					
					var store_info = this.$store.state.globalStoreInfo;
					if (store_info){
						this.mpShareData.appMessage.path += (this.mpShareData.appMessage.path.indexOf('?') > -1 ? '&' : '?')+'store_id=' + store_info.store_id;
						this.mpShareData.timeLine.query += (this.mpShareData.timeLine.query.indexOf('?') > -1 ? '&' : '?')+'store_id=' + store_info.store_id;
					}
					//朋友圈不需要页面路径，只要要后面的参数就行
					this.mpShareData.timeLine.query = this.mpShareData.timeLine.query.split('?')[1] || '';
				// #endif
			}
		},
		closePopupWindow() {
			this.$refs.uniPopupWindow.close();
			uni.setStorageSync(this.id + this.name + '_popwindow_count', -1);
		},
		closeCollectPopupWindow() {
			this.$refs.collectPopupWindow.close();
			uni.setStorageSync('isCollect', true);
		},
		uniPopupWindowFn() {
			this.$util.diyRedirectTo(this.diyData.global.popWindow.link);
			this.closePopupWindow();
		},
		/******************************************** 获取门店相关 START ***************************************************/
		/**
		 * 1、分享携带门店id
		 * 	  门店id正确 进入门店
		 * 	  门店id错误 通过定位获取门店
		 * 2、通过定位获取门店
		 *    开启获取定位
		 *        同意获取定位 获取最近门店 进入门店
		 *        拒绝获取定位 
		 * 		      平台运营模式 进入默认门店
		 * 		      连锁门店模式 提示获取定位失败，手动选择门店或引导去开启定位
		 *    关闭获取定位
		 *        平台运营模式 进入默认门店
		 *        连锁门店模式 提示获取定位失败，手动选择门店
		 */
		getCurrentStore(option){
			if(option.store_id && !isNaN(parseInt(option.store_id))){
				this.getStoreInfoByShare(option.store_id);
			}else{
				this.getLocation();
			}
		},
		getStoreInfoByShare(store_id){
			this.$api.sendRequest({
				url: '/api/store/info',
				data: {store_id},
				success: res => {
					if(res.code >= 0 && res.data){
						this.changeCurrentStore(res.data);
					}else{
						this.getLocation();
					}
				},
				fail: res => {
					this.getLocation();
				}
			});
		},
		getLocation(){
			if (!this.latitude && !this.longitude && this.initStatus){
				if (this.mapConfig.wap_is_open == 1) {
					this.$util.getLocation({
						complete:(res)=>{
							if(res.latitude && res.longitude){
								this.closeGetLocationFailPopup();
								this.latitude = res.latitude;
								this.longitude = res.longitude;
								this.getStoreInfoByLocation();
							}else{
								let is_h5 = false;
								// #ifdef H5
								is_h5 = true;
								// #endif
								if(is_h5){
									//H5同意了也会进入失败，所以直接进入默认门店
									this.enterDefaultStore();
								}else{
									this.getLocationFail();
								}
							}
						}
					});
					// #ifdef H5
					//H5有的机型可能根本不会触发getLocation的任何执行，包括success，fail，completele
					//所以这里如果等待一定时间后还是没有获取到当前门店则进入默认门店
					setTimeout(()=>{
						let current_route = this.$util.getCurrentRoute();
						if(this.mapConfig.wap_is_open == 1 && !this.currentStore && current_route.path == '/pages/index/index'){
							this.enterDefaultStore();
						}
					}, 5000);
					// #endif
				} else {
					this.getLocationFail();
				}
			}
		},
		getStoreInfoByLocation(){
			if (this.latitude && this.longitude) {
				this.getNearestStore();
				this.getCurrentLocation();
			}
		},
		changeCurrentStore(store_info){
			this.currentStore = store_info;
			this.changeStore(store_info);
			this.openChooseStorePopup();
		},
		getLocationFail(){
			if(this.globalStoreConfig.store_business == 'shop'){
				this.enterDefaultStore();
			}else{
				this.openGetLocationFailPopup();
			}
		},
		openGetLocationFailPopup(){
			if(this.$refs.getLocationFailRef) this.$refs.getLocationFailRef.open();
		},
		closeGetLocationFailPopup(){
			if(this.$refs.getLocationFailRef) this.$refs.getLocationFailRef.close();
		},
		openChooseStorePopup() {
			let globalStoreInfo = this.globalStoreInfo;
			if (this.globalStoreConfig && this.globalStoreConfig.confirm_popup_control == 1) {
				this.currentStore.show_address = this.currentStore.full_address.replace(/,/g, ' ') + ' ' + this.currentStore.address;
				if (this.$refs.chooseStorePopup) this.$refs.chooseStorePopup.open();
			}
		},
		closeChooseStorePopup() {
			if (this.$refs.chooseStorePopup) this.$refs.chooseStorePopup.close();
		},
		// 选择其他门店
		chooseOtherStore() {
			this.$util.redirectTo('/pages_tool/store/list');
			this.closeChooseStorePopup();
		},
		// 打开地图重新选择位置
		reGetLocation() {
			// #ifdef MP
			uni.chooseLocation({
				success: res => {
					this.latitude = res.latitude;
					this.longitude = res.longitude;
					this.currentPosition = res.name;
					this.getStoreInfoByLocation();
				},
				fail(res) {
					uni.getSetting({
						success: function (res) {
							var statu = res.authSetting;
							if (!statu['scope.userLocation']) {
								uni.showModal({
									title: '是否授权当前位置',
									content: '需要获取您的地理位置，请确认授权，否则地图功能将无法使用',
									success(tip) {
										if (tip.confirm) {
											uni.openSetting({
												success: function (data) {
													if (data.authSetting['scope.userLocation'] === true) {
														this.$util.showToast({
															title: '授权成功'
														});
														//授权成功之后，再调用chooseLocation选择地方
														setTimeout(function () {
															uni.chooseLocation({
																success: data => {
																	this.latitude = res.latitude;
																	this.longitude = res.longitude;
																	this.currentPosition = res.name;
																	this.getStoreInfoByLocation();
																}
															});
														}, 1000);
													}
												}
											});
										} else {
											this.$util.showToast({
												title: '授权失败'
											});
										}
									}
								});
							}
						}
					});
				}
			});
			// #endif

			// #ifdef H5
			let backurl = Config.h5Domain; // 地图选择位置后的回调页面路径
			window.location.href = 'https://apis.map.qq.com/tools/locpicker?search=1&type=0&backurl=' +
				encodeURIComponent(backurl) + '&key=' + Config.mpKey + '&referer=myapp';
			// #endif
		},
		// 获取离自己最近的一个门店
		getNearestStore() {
			let data = {};
			if (this.latitude && this.longitude) {
				data.latitude = this.latitude;
				data.longitude = this.longitude;
			}
			this.$api.sendRequest({
				url: '/api/store/nearestStore',
				data: data,
				success: res => {
					if (res.code == 0 && res.data) {
						this.changeCurrentStore(res.data);
					}
				}
			});
		},
		// 根据经纬度获取位置
		getCurrentLocation() {
			var _this = this;
			let data = {};
			if (this.latitude && this.longitude) {
				data.latitude = this.latitude;
				data.longitude = this.longitude;
			}
			this.$api.sendRequest({
				url: '/api/store/getLocation',
				data: data,
				success: res => {
					if (res.code == 0 && res.data) {
						this.currentPosition = res.data.formatted_addresses.recommend; // 结合知名地点形成的描述性地址，更具人性化特点
					} else {
						this.currentPosition = '未获取到定位';
					}
				}
			});
		},
		// 定位失败，进入默认门店
		enterDefaultStore() {
			if (this.defaultStoreInfo) {
				this.changeCurrentStore(this.defaultStoreInfo);
			}
		},
		//连锁门店未定位选择门店
		chooseStore(){
			this.$util.redirectTo('/pages_tool/store/list');
		},
		//打开手机设置重新定位
		openSetting(){
			uni.openSetting({
				success: res => {
					this.getLocation();
				}
			})
		},
		/******************************************** 获取门店相关 END ***************************************************/
		// 设置公众号分享
		setPublicShare() {
			let shareUrl = this.$config.h5Domain + this.diyRoute;
			var store_info = this.$store.state.globalStoreInfo;
			//if (store_info) shareUrl += '?store_id=' + store_info.store_id;
			if (shareUrl.indexOf('?') > 0) {
				shareUrl += '&';
			}else{
				shareUrl += '?';
			}
			if (this.id) shareUrl += 'id=' + this.id;
			else if (this.name) shareUrl += 'name=' + this.name;
			// alert('diydiydiy')
			this.$util.setPublicShare({
				title: this.diyData.global.wechatShareTitle || this.diyData.global.title,
				desc: this.diyData.global.wechatShareDesc,
				link: shareUrl,
				imgUrl: this.diyData.global.wechatShareImage ? this.$util.img(this.diyData.global.wechatShareImage) : this.$util.img(this.siteInfo.logo_square)
			});
		},
	},
	onPageScroll(e) {
		this.scrollTop = e.scrollTop;
		if (this.$refs.topNav) {
			if (e.scrollTop >= 20) {
				this.$refs.topNav.navTopBg();
			} else {
				this.$refs.topNav.unSetnavTopBg();
			}
		}
	},
	// 下拉刷新
	onPullDownRefresh() {
		// this.$store.commit('setComponentRefresh');
		this.getDiyMethod();
		setTimeout(() => {
			uni.stopPullDownRefresh();
		}, 50);
	},
	// 分享给好友
	onShareAppMessage() {
		return this.mpShareData.appMessage;
	},
	// 分享到朋友圈
	onShareTimeline() {
		return this.mpShareData.timeLine;
	}
}