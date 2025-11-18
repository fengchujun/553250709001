export default {
	data() {
		return {
			isIphoneX: false,
			orderCreateData: {
				member_address: {
					name: '',
					mobile: ''
				}
			},
			orderPaymentData: {
				exchange_info: {
					type: 0
				},
				delivery: {
					delivery_type: '',
					express_type: [],
					member_address: {
						name: '',
						mobile: ''
					},
					local: {
						info: {
							start_time: 0,
							end_time: 0,
							time_week: []
						}
					},
				},
			},
			isSub: false,
			tempData: null,
			// 门店信息
			storeInfo: {
				storeList: [], //门店列表
				currStore: {} //当前选择门店
			},
			// 自提地址
			member_address: {
				name: '',
				mobile: ''
			},
			// 当前时间
			timeInfo: {
				week: 0,
				start_time: 0,
				end_time: 0,
				showTime: false,
				showTimeBar: false
			},
			deliveryWeek: "",
			// 选择自提、配送防重判断
			judge: true,
			menuButtonBounding: {}
		};
	},
	methods: {
		// 显示弹出层
		openPopup(ref) {
			this.$refs[ref].open();
		},
		// 关闭弹出层
		closePopup(ref) {
			if (this.tempData) {
				Object.assign(this.orderCreateData, this.tempData);
				Object.assign(this.orderPaymentData, this.tempData);
				this.tempData = null;
				this.$forceUpdate();
			}
			this.$refs[ref].close();
		},
		// 选择收货地址
		selectAddress() {
			var params = {
				back: '/pages_promotion/point/payment',
				local: 0,
				type: 1
			}
			// 外卖配送需要定位地址
			if (this.orderPaymentData.delivery.delivery_type == 'local') {
				params.local = 1;
				params.type = 2;
			}
			this.$util.redirectTo('/pages_tool/member/address', params);
		},
		// 获取订单初始化数据
		getOrderPaymentData() {
			this.orderCreateData = uni.getStorageSync('exchangeOrderCreateData');
			var pay_flag = uni.getStorageSync("pay_flag"); // 支付中标识，防止返回时，提示,跳转错误
			if (!this.orderCreateData) {
				if (pay_flag == 1) {
					uni.removeStorageSync("pay_flag");
				} else {
					this.$util.showToast({
						title: '未获取到创建订单所需数据！'
					});
					setTimeout(() => {
						this.$util.redirectTo('/pages/index/index');
					}, 1500);
				}
				return;
			}

			// 获取经纬度
			if (this.location) {
				this.orderCreateData.latitude = this.location.latitude;
				this.orderCreateData.longitude = this.location.longitude;
			}

			this.$api.sendRequest({
				url: '/pointexchange/api/ordercreate/payment',
				data: this.orderCreateData,
				success: res => {
					if (res.code >= 0) {
						this.orderPaymentData = res.data;
						this.orderPaymentData.timestamp = res.timestamp;

						this.handlePaymentData();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					} else {
						this.$util.showToast({
							title: '未获取到创建订单所需数据！'
						});
						setTimeout(() => {
							this.$util.redirectTo('/pages/index/index');
						}, 1500);
					}
				},
				fail: res => {
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			})
		},
		// 处理结算订单数据
		handlePaymentData() {
			this.orderCreateData.delivery = {};
			this.orderCreateData.buyer_message = '';

			var data = JSON.parse(JSON.stringify(this.orderPaymentData));
			this.orderCreateData.order_key = data.order_key;

			this.orderCreateData.delivery.store_id = 0;
			this.orderCreateData.member_address = data.delivery.member_address;

			// 店铺配送方式
			if (data.delivery.express_type != undefined && data.delivery.express_type[0] != undefined) {

				let deliveryStorage = uni.getStorageSync('delivery');
				let delivery = data.delivery.express_type[0];
				data.delivery.express_type.forEach(item => {
					if (deliveryStorage && item.name == deliveryStorage.delivery_type) {
						delivery = item;
					}
				});

				this.selectDeliveryType(delivery);

			}

			if (this.orderPaymentData.is_virtual) this.orderCreateData.member_address = {
				mobile: data.member_account.mobile != '' ? data.member_account.mobile : ''
			};

			// Object.assign(this.orderPaymentData, this.orderCreateData);
			this.orderCalculate();
		},
		// 转化时间字符串
		getTimeStr(val) {
			var h = parseInt(val / 3600).toString();
			var m = parseInt((val % 3600) / 60).toString();
			if (m.length == 1) {
				m = '0' + m;
			}
			if (h.length == 1) {
				h = '0' + h;
			}
			return h + ':' + m;
		},
		// 订单计算
		orderCalculate() {
			var data = this.$util.deepClone(this.orderCreateData);
			data.delivery = JSON.stringify(data.delivery);
			if (this.orderCreateData.delivery.delivery_type == 'store') {
				data.member_address = JSON.stringify(this.member_address);
			} else {
				data.member_address = JSON.stringify(data.member_address);
			}

			this.$api.sendRequest({
				url: '/pointexchange/api/ordercreate/calculate',
				data,
				success: res => {
					if (res.code >= 0) {
						this.orderPaymentData.member_address = res.data.member_address;
						this.orderPaymentData.delivery_money = res.data.delivery_money;
						this.orderPaymentData.order_money = res.data.order_money;

						Object.assign(this.orderPaymentData.delivery, res.data.delivery);

						if (res.data.local_config) this.orderPaymentData.local_config = res.data.config.local;

						//时间选择判断
						if (res.data.delivery.delivery_store_info) {
							this.orderPaymentData.delivery_store_info = JSON.parse(res.data.delivery.delivery_store_info);
							if (this.judge) {
								if (this.orderPaymentData.delivery.delivery_type == "store") {
									this.storetime('no');
								} else if (this.orderPaymentData.delivery.delivery_type == 'local') {
									this.localtime('no');
								}
								this.judge = false;
							}
						}

						this.createBtn();
						this.$forceUpdate();
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				},
			})
		},
		/**
		 * 订单创建验证
		 */
		createBtn() {
			if (this.orderPaymentData.delivery &&
				this.orderPaymentData.delivery.delivery_type == 'local' &&
				this.orderPaymentData.delivery &&
				this.orderPaymentData.delivery.error &&
				this.orderPaymentData.delivery.start_money > this.orderPaymentData.price) {
				return false;
			}
			if (this.orderPaymentData.delivery &&
				this.orderPaymentData.delivery.delivery_type == 'local' &&
				this.orderPaymentData.delivery &&
				this.orderPaymentData.delivery.error &&
				this.orderPaymentData.delivery.error !== '') {
				return false;
			}
			return true;
		},
		// 订单创建
		orderCreate() {
			if (this.verify()) {
				if (this.isSub) return;
				this.isSub = true;

				uni.setStorageSync('paySource', 'pointexchange');

				var data = this.$util.deepClone(this.orderCreateData);
				data.delivery = JSON.stringify(data.delivery);
				if (this.orderCreateData.delivery.delivery_type == 'store') {
					data.member_address = JSON.stringify(this.member_address);
				} else {
					data.member_address = JSON.stringify(data.member_address);
				}

				this.$api.sendRequest({
					url: '/pointexchange/api/ordercreate/create',
					data,
					success: res => {
						uni.hideLoading();
						if (res.code >= 0) {
							if (this.orderPaymentData.exchange_info.type == 1 && this.orderPaymentData.order_money != '0.00') {

								let orderCreateData = uni.getStorageSync('exchangeOrderCreateData');
								orderCreateData.out_trade_no = res.data;
								uni.setStorageSync('exchangeOrderCreateData', orderCreateData);

								this.$refs.choosePaymentPopup.getPayInfo(res.data);
								this.isSub = false;
							} else {
								this.$util.redirectTo('/pages_promotion/point/result', {}, 'redirectTo');
							}
						} else {
							this.isSub = false;
							if (res.data.error_code == 10 || res.data.error_code == 12) {
								uni.showModal({
									title: '订单未创建',
									content: res.message,
									confirmText: '去设置',
									success: res => {
										if (res.confirm) {
											this.selectAddress();
										}
									}
								})
							} else {
								this.$util.showToast({
									title: res.message
								});
							}
						}
					},
					fail: res => {
						uni.hideLoading();
						this.isSub = false;
					}
				})
			}
		},
		// 订单验证
		verify() {
			if (this.orderPaymentData.exchange_info.type == 1) {
				if (this.orderPaymentData.is_virtual == 1) {
					if (!this.orderCreateData.member_address.mobile.length) {
						this.$util.showToast({
							title: '请输入您的手机号码'
						});
						return false;
					}
					if (!this.$util.verifyMobile(this.orderCreateData.member_address.mobile)) {
						this.$util.showToast({
							title: '请输入正确的手机号码'
						});
						return false;
					}
				}

				if (this.orderPaymentData.is_virtual == 0) {
					if (!this.orderCreateData.delivery || !this.orderCreateData.delivery.delivery_type) {
						this.$util.showToast({
							title: '商家未设置配送方式'
						});
						return false;
					}

					if (this.orderCreateData.delivery.delivery_type != 'store') {
						if (!this.orderCreateData.member_address) {
							this.$util.showToast({
								title: '请先选择您的收货地址'
							});
							return false;
						}
					}

					if (this.orderCreateData.delivery.delivery_type == 'store') {
						if (!this.orderCreateData.delivery.store_id) {
							this.$util.showToast({
								title: '没有可提货的门店,请选择其他配送方式'
							});
							return false;
						}
						if (!this.member_address.mobile) {
							this.$util.showToast({
								title: '请输入预留手机'
							});
							return false;
						}
						if (!this.$util.verifyMobile(this.member_address.mobile)) {
							this.$util.showToast({
								title: '请输入正确的预留手机'
							});
							return false;
						}

						if (!this.orderCreateData.delivery.buyer_ask_delivery_time.start_date || !this.orderCreateData.delivery.buyer_ask_delivery_time.end_date) {
							this.$util.showToast({
								title: '请选择自提时间'
							});
							return false;
						}
					}

					if (this.orderCreateData.delivery.delivery_type == 'local') {
						if (!this.orderCreateData.delivery.store_id) {
							this.$util.showToast({
								title: '没有可配送的门店,请选择其他配送方式'
							});
							return false;
						}
						if (this.orderPaymentData.config.local.is_use && this.orderPaymentData.delivery.local.info && this.orderPaymentData.delivery.local.info.time_is_open == 1 && (!this.orderCreateData.delivery.buyer_ask_delivery_time.start_date || !this.orderCreateData.delivery.buyer_ask_delivery_time.end_date)) {
							this.$util.showToast({
								title: '请选择配送时间'
							});
							return false;
						}
					}
				}

			}
			return true;
		},
		// 显示店铺配送信息
		openSiteDelivery() {
			this.tempData = {
				delivery: this.$util.deepClone(this.orderPaymentData.delivery)
			};
			this.$refs.deliveryPopup.open();
		},
		// 选择配送方式
		selectDeliveryType(data) {
			uni.setStorageSync('delivery', {
				delivery_type: data.name,
				delivery_type_name: data.title
			});
			this.orderCreateData.delivery.delivery_type = data.name;
			this.orderCreateData.delivery.delivery_type_name = data.title;

			// 如果是门店配送
			if (data.name == 'store') {
				this.storeSelected(data);
				this.member_address.name = this.orderPaymentData.member_account.nickname;
				if (!this.member_address.mobile) this.member_address.mobile = this.orderPaymentData.member_account.mobile != '' ? this.orderPaymentData.member_account.mobile : '';
			}
			if (data.name == 'local') {
				this.storeSelected(data);
			}
			// Object.assign(this.orderPaymentData, this.orderCreateData);

			this.judge = true;

			this.orderCalculate();
			this.$forceUpdate();
		},
		// 切换到门店
		storeSelected(data) {
			// 门店列表
			this.storeInfo.storeList = data.store_list;
			let store = data.store_list[0] ? data.store_list[0] : null;
			this.selectPickupPoint(store);
		},
		// 选择自提点
		selectPickupPoint(store_item) {
			if (store_item) {
				this.orderCreateData.delivery.store_id = store_item.store_id;
				this.storeInfo.currStore = store_item;
				// 存储所选门店
				let delivery = uni.getStorageSync('delivery') || {
					name: this.orderCreateData.delivery.delivery_type,
					title: this.orderCreateData.delivery.delivery_type_name
				};
				delivery.store_id = store_item.store_id;
				uni.setStorageSync('delivery', delivery)
			} else {
				this.orderCreateData.delivery.store_id = 0;
				this.storeInfo.currStore = {};
			}
			this.orderCreateData.delivery.buyer_ask_delivery_time = {
				start_date: '',
				end_date: ''
			};
			this.orderCreateData.buyer_ask_delivery_title = '';
			// Object.assign(this.orderPaymentData, this.orderCreateData);
			this.orderCalculate();
			this.$forceUpdate();
			this.$refs['deliveryPopup'].close();
		},
		imageError() {
			let imageUrl = ''
			if (this.orderPaymentData.exchange_info.type == 1) {
				imageUrl = this.$util.img(this.$util.getDefaultImage().goods);
			} else if (this.orderPaymentData.exchange_info.type == 2) {
				imageUrl = this.$util.img('public/uniapp/point/coupon.png');
			} else if (this.orderPaymentData.exchange_info.type == 3) {
				imageUrl = this.$util.img('public/uniapp/point/hongbao.png');
			} else {
				imageUrl = this.$util.getDefaultImage().goods;
			}
			this.orderPaymentData.exchange_info.image = imageUrl;
			this.$forceUpdate();
		},
		// 获取时间
		getTime() {
			// 必须是字符串,跟后端一致
			let weeks = ['0', '1', '2', '3', '4', '5', '6'];
			let week = new Date().getDay();
			this.timeInfo.week = weeks[week];
		},
		navigateTo(sku_id) {
			this.$util.redirectTo('/pages/goods/detail', {
				sku_id
			});
		},
		// 显示选择支付方式弹框
		openChoosePayment() {
			if (this.verify() && this.orderPaymentData.exchange_info.type == 1 && this.orderPaymentData.order_money != '0.00') this.$refs.choosePaymentPopup.open();
			else this.orderCreate();
		},
		/**
		 * 同城配送数据处理
		 */
		localtime(type = '') {
			let data = this.$util.deepClone(this.orderPaymentData.delivery.local.info);
			if (data.delivery_time) {
				data.end_time = data.delivery_time[(data.delivery_time.length - 1)].end_time;
			}
			let obj = {
				delivery: this.orderCreateData.delivery,
				dataTime: data
			}
			this.$refs.TimePopup.open(obj, type);
		},
		/**
		 * 门店自提数据处理
		 */
		storetime(type = '') {
			if (this.orderPaymentData.delivery.delivery_store_info) {
				let data = this.$util.deepClone(this.storeInfo.currStore);
				data.delivery_time = typeof data.delivery_time == 'string' && data.delivery_time ? JSON.parse(data.delivery_time) : data.delivery_time;
				if (!data.delivery_time || data.delivery_time.length == undefined && !data.delivery_time.length) {
					data.delivery_time = [{
						start_time: data.start_time,
						end_time: data.end_time
					}]
				}
				let obj = {
					delivery: this.orderCreateData.delivery,
					dataTime: data
				}
				this.$refs.TimePopup.open(obj, type);
				this.$forceUpdate();
			}
		},
		/**
		 * 弹窗返回数据
		 */
		selectTime(data) {
			if (data.data && data.data.month) {
				this.orderCreateData.delivery.buyer_ask_delivery_time = {
					start_date:data.data.start_date,
					end_date:data.data.end_date
				};
				if (data.data.title == '今天' || data.data.title == '明天') {
					this.orderCreateData.buyer_ask_delivery_title = data.data.title + '(' + data.data.time + ')'
				} else {
					this.orderCreateData.buyer_ask_delivery_title = data.data.month + '(' + data.data.time + ')'
				}
				this.orderCalculate();
				this.$forceUpdate();
			}
		},
		back() {
			uni.navigateBack({
				delta: 1
			});
		}
	},
	onShow() {
		if (uni.getStorageSync('addressBack')) {
			uni.removeStorageSync('addressBack');
		}
		// 判断登录
		if (!this.storeToken) {
			this.$util.redirectTo('/pages_tool/login/index');
		} else {
			this.getOrderPaymentData();
		}
		this.judge = true;

		this.getTime();
		this.isIphoneX = this.$util.uniappIsIPhoneX()
	},
	onHide() {
		if (this.$refs.loadingCover) this.$refs.loadingCover.show();
	},
	onLoad() {
		if (!this.location) this.$util.getLocation();
		// #ifdef MP
		this.menuButtonBounding = uni.getMenuButtonBoundingClientRect();
		// #endif
	},
	watch: {
		location: function(nVal) {
			if (nVal) {
				this.getOrderPaymentData();
			}
		}
	},
	filters: {
		// 金额格式化输出
		moneyFormat(money) {
			return parseFloat(money).toFixed(2);
		}
	}
}