export default {
	data() {
		return {
			currTime: '',
			isIphoneX: false,
			orderCreateData: {
				is_balance: 0,
				pay_password: '',

				// 发票
				is_invoice: 0, // 是否需要发票 0 无发票  1 有发票
				invoice_type: 0, // 发票类型  1 纸质 2 电子
				invoice_title_type: 1, // 抬头类型  1 个人 2 企业
				is_tax_invoice: 0, // 是否需要增值税专用发票  0 不需要 1 需要
				invoice_title: '', // 发票抬头
				taxpayer_number: '', // 纳税人识别号
				invoice_content: '', // 发票内容
				invoice_full_address: '', // 发票邮寄地址
				invoice_email: '', //发票邮箱
				buyer_message: '',
				buyer_ask_delivery_title: ''
			},
			orderPaymentData: {
				shop_goods_list: {
					site_name: '',
					express_type: [],
					coupon_list: [],
					invoice: {
						invoice_content_array: []
					}
				},
				delivery: {
					site_name: '',
					express_type: [],
					coupon_list: [],
					invoice: {
						invoice_content_array: []
					}
				},
				member_account: {
					balance: 0,
					is_pay_password: 0
				},
				local_config: {
					info: {
						start_time: 0,
						end_time: 0,
						time_week: []
					}
				},
				invoice: {
					invoice_status: 0
				},
				goods_num:0,
			},
			isSub: false,
			tempData: null,
			is_deposit_back: 0, // 是否退定金 0 是 1 否
			switch_state: true,
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
				showTimeBar: false
			},
			// 密码是否聚焦
			isFocus: false,
			presale_id: 0,
			pay_password: "",
			action: {
				icon: ''
			},
			deliveryWeek: "",
			menuButtonBounding: {},
			memberAddress: null,
			localMemberAddress: null,
		};
	},
	methods: {
		/**
		 * 显示弹出层
		 * @param {Object} ref
		 */
		openPopup(ref) {
			this.$refs[ref].open();
		},
		/**
		 * 关闭弹出层
		 * @param {Object} ref
		 */
		closePopup(ref) {
			if (this.tempData) {
				Object.assign(this.orderCreateData, this.tempData);
				Object.assign(this.orderPaymentData, this.tempData);
				this.tempData = null;
				this.$forceUpdate();
			}
			this.$refs[ref].close();
		},
		/**
		 * 选择收货地址
		 */
		selectAddress() {
			var params = {
				back: this.$util.getCurrentRoute().path,
				local: 0,
				type: 1
			}
			// 外卖配送需要定位地址
			if (this.orderCreateData.delivery.delivery_type == 'local') {
				params.local = 1;
				params.type = 2;
			}
			this.$util.redirectTo('/pages_tool/member/address', params);
		},
		/**
		 * 获取订单初始化数据
		 */
		getOrderPaymentData() {
			Object.assign(this.orderCreateData, uni.getStorageSync('presaleOrderCreateData'))
			// 获取经纬度
			if (this.location) {
				this.orderCreateData.latitude = this.location.latitude;
				this.orderCreateData.longitude = this.location.longitude;
			}

			if (!this.orderCreateData) {
				this.$util.showToast({
					title: '未获取到创建订单所需数据!！',
					success: () => {
						setTimeout(() => {
							this.$util.redirectTo('/pages/index/index');
						}, 1500)
					}
				});
				return;
			}
			this.$api.sendRequest({
				url: '/presale/api/ordercreate/depositPayment',
				data: this.orderCreateData,
				success: res => {
					if (res.code >= 0) {
						this.orderPaymentData = res.data;
						this.is_deposit_back = res.data.promotion_presale_info.is_deposit_back;
						this.handlePaymentData();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					} else {
						this.$util.showToast({
							title: '未获取到创建订单所需数据!！',
							success: () => {
								// setTimeout(() => {
								// 	this.$util.redirectTo('/pages/index/index');
								// }, 1500)
							}
						});
					}
				},
				fail: res => {
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			})
		},
		/**
		 * 处理结算订单数据
		 */
		handlePaymentData() {
			this.orderCreateData.delivery = {};
			this.orderCreateData.coupon = {};
			this.orderCreateData.buyer_message = '';

			this.orderCreateData.is_balance = 0;
			this.orderCreateData.pay_password = '';

			this.orderCreateData.is_invoice = 0; // 是否需要发票 0 无发票  1 有发票
			this.orderCreateData.invoice_type = 1; // 发票类型  1 纸质 2 电子
			this.orderCreateData.invoice_title_type = 1; // 发票抬头类型 1 个人 2企业
			this.orderCreateData.is_tax_invoice = 0; // 是否需要增值税专用发票  0 不需要 1 需要
			this.orderCreateData.invoice_title = '';
			if (this.orderPaymentData.invoice.invoice_status == 1) {
				this.orderPaymentData.invoice.invoice_type = this.orderPaymentData.invoice.invoice_type.split(',');
				this.orderCreateData.invoice_type = this.orderPaymentData.invoice.invoice_type[0]; // 发票类型  1 纸质 2 电子
			}

			var data = JSON.parse(JSON.stringify(this.orderPaymentData));
			this.orderCreateData.order_key = data.order_key;
			if (data.delivery.express_type != undefined && data.delivery.express_type[0] != undefined) {
				var express_type = data.delivery.express_type;
				this.orderCreateData.delivery.store_id = 0;
				// 获取选择配送方式缓存
				var delivery_storage = uni.getStorageSync('delivery');
				if (delivery_storage) {
					var delivery_type = delivery_storage.delivery_type;
					var delivery_type_name = delivery_storage.delivery_type_name;
					express_type.forEach(item => {
						if ((delivery_type == 'store' && item.name == delivery_type) || (delivery_type == 'local' && item.name == delivery_type)) {
							this.storeSelected(item);
						}
					})
					// 如果配送方式缓存是门店配送模拟点击门店tab选项
					if (delivery_type == 'store') {
						this.member_address = {
							name: data.member_account.nickname,
							mobile: data.member_account.mobile != '' ? data.member_account.mobile : ''
						};
					}
				} else {
					var delivery_type = express_type[0].name;
					if (delivery_type == "store") {
						this.member_address = {
							name: data.member_account.nickname,
							mobile: data.member_account.mobile != '' ? data.member_account.mobile : ''
						};
					}
					var delivery_type_name = express_type[0].title;
				}
				this.orderCreateData.delivery.delivery_type = delivery_type;
				this.orderCreateData.delivery.delivery_type_name = delivery_type_name;

				// 如果默认配送方式是门店配送模拟点击门店tab选项
				if (express_type[0].name == 'store' || express_type[0].name == 'local') {
					this.storeSelected(express_type[0]);
				}
			}

			// if (data.shop_goods_list.coupon_list != undefined && data.shop_goods_list.coupon_list[0] != undefined) {
			// 	var coupon_list = data.shop_goods_list.coupon_list;
			// 	this.orderCreateData.coupon.coupon_id = coupon_list[0].coupon_id;
			// 	this.orderCreateData.coupon.coupon_money = coupon_list[0].money;
			// }

			if (this.orderPaymentData.is_virtual) this.orderCreateData.member_address = {
				name: data.member_account.nickname,
				mobile: data.member_account.mobile != '' ? data.member_account.mobile : ''
			};

			if (this.orderPaymentData.invoice.invoice_status == 1) {
				var invoice_content_array = this.orderPaymentData.invoice.invoice_content_array;
				if (invoice_content_array.length) this.orderCreateData.invoice_content = invoice_content_array[0];
			}

			// Object.assign(this.orderPaymentData, this.orderCreateData);
			this.orderPaymentData.goods_list.forEach((v) => {
				if (v.sku_spec_format) {
					v.sku_spec_format = JSON.parse(v.sku_spec_format);
				} else {
					v.sku_spec_format = []
				}
			});
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
		/**
		 * 订单计算
		 */
		orderCalculate() {
			var data = this.$util.deepClone(this.orderCreateData);
			data.delivery = JSON.stringify(data.delivery);
			data.coupon = JSON.stringify(data.coupon);
			if (this.orderCreateData.delivery.delivery_type == 'store') {
				data.member_address = JSON.stringify(this.member_address);
			} else {
				data.member_address = JSON.stringify(data.member_address);
			}

			this.$api.sendRequest({
				url: '/presale/api/ordercreate/depositCalculate',
				data,
				success: res => {
					if (res.code >= 0) {
						if (!res.data.is_virtual && res.data.delivery) {
							if (res.data.delivery.delivery_type == 'express') this.memberAddress = res.data.delivery.member_address;
							if (res.data.delivery.delivery_type == 'local') this.localMemberAddress = res.data.delivery.member_address;
						}
						this.orderPaymentData.delivery_money = res.data.delivery_money;
						this.orderPaymentData.coupon_money = res.data.coupon_money;
						this.orderPaymentData.invoice_money = res.data.invoice_money;
						this.orderPaymentData.invoice_delivery_money = res.data
							.invoice_delivery_money;
						this.orderPaymentData.promotion_money = res.data.promotion_money;
						this.orderPaymentData.order_money = res.data.order_money;
						this.orderPaymentData.balance_money = res.data.balance_money;
						this.orderPaymentData.pay_money = res.data.pay_money;
						this.orderPaymentData.goods_money = res.data.goods_money;
						this.orderPaymentData.final_money = res.data.final_money;
						this.orderPaymentData.goods_num = res.data.goods_num;
						this.orderPaymentData.delivery.member_address = res.data.delivery.member_address;

						if (res.data.config.local) this.orderPaymentData.local_config = res.data.config.local;

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
				this.orderPaymentData.delivery.start_money > this.orderPaymentData.presale_deposit_money
			) {
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

		/**
		 * 订单创建
		 */
		orderCreate() {
			if (this.verify()) {
				if (this.isSub) return;
				this.isSub = true;

				var data = this.$util.deepClone(this.orderCreateData);
				data.delivery = JSON.stringify(data.delivery);
				data.coupon = JSON.stringify(data.coupon);
				if (this.orderCreateData.delivery.delivery_type == 'store') {
					data.member_address = JSON.stringify(this.member_address);
				} else {
					data.member_address = JSON.stringify(data.member_address);
				}

				this.$api.sendRequest({
					url: '/presale/api/ordercreate/depositCreate',
					data,
					success: res => {
						if (res.code >= 0) {
							if (this.orderPaymentData.pay_money == 0) {
								// #ifdef MP
								switch (this.orderCreateData.delivery.delivery_type) {
									// ORDER_URGE_PAYMENT,订单催付
									// ORDER_PAY,订单支付
									// ORDER_DELIVERY //订单发货
									// ORDER_TAKE_DELIVERY 订单收货

									case 'express': //物流配送
										// this.$util.subscribeMessage('ORDER_TAKE_DELIVERY');
										break;
									case 'store': //门店自提
										this.$util.subscribeMessage('ORDER_VERIFY_OUT_TIME,VERIFY_CODE_EXPIRE,VERIFY');
										break;
									case 'local': //同城配送
										// this.$util.subscribeMessage('ORDER_TAKE_DELIVERY');
										break;
									default:
										this.$util.subscribeMessage('ORDER_VERIFY_OUT_TIME,VERIFY_CODE_EXPIRE,VERIFY');
										break;
								}
								// #endif

								this.$util.redirectTo('/pages_tool/pay/result', {
									code: res.data
								}, 'redirectTo');
							} else {
								let orderCreateData = uni.getStorageSync('presaleOrderCreateData');
								orderCreateData.out_trade_no = res.data;
								uni.setStorageSync('presaleOrderCreateData', orderCreateData);

								this.$refs.choosePaymentPopup.getPayInfo(res.data);
								this.isSub = false;
							}
						} else {
							this.isSub = false;
							uni.hideLoading();
							if (this.$refs.payPassword) {
								this.$refs.payPassword.close();
							}
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
					}
				})
			}
		},
		/**
		 * 订单验证
		 */
		verify() {
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
				if (this.orderCreateData.delivery.delivery_type != 'store') {
					if (!this.orderPaymentData.delivery.member_address) {
						this.$util.showToast({
							title: this.orderCreateData.delivery.delivery_type == 'express' ? '请先选择您的收货地址' : '当前地址不在该门店配送区域，请重新选择可配送该区域的门店',
						});
						return false;
					}
				}

				if (JSON.stringify(this.orderCreateData.delivery) == "{}") {
					this.$util.showToast({
						title: '店铺未设置配送方式'
					});
					return false;
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
				}

				if (this.orderCreateData.delivery.delivery_type == 'local') {
					if (!this.orderCreateData.delivery.store_id) {
						this.$util.showToast({
							title: '没有可配送的门店,请选择其他配送方式'
						});
						return false;
					}
				}
			}

			// 如果使用发票进行验证
			if (this.orderCreateData.is_invoice == 1) {
				if (!this.invoiceVerify()) return false;
			}

			return true;
		},
		/**
		 * 显示店铺优惠信息
		 */
		openSitePromotion() {
			this.$refs.sitePromotionPopup.open();
		},
		/**
		 * 显示店铺配送信息
		 */
		openSiteDelivery() {
			this.tempData = {
				delivery: this.$util.deepClone(this.orderPaymentData.delivery)
			};
			this.$refs.deliveryPopup.open();
		},
		/**
		 * 选择配送方式
		 */
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
		/**
		 * 选择自提点 
		 */
		selectPickupPoint(store_item) {
			if (store_item) {
				this.orderCreateData.delivery.store_id = store_item.store_id;
				this.storeInfo.currStore = store_item;
				// 存储所选门店
				let delivery = uni.getStorageSync('delivery');
				if (delivery) {
					delivery.store_id = store_item.store_id;
					uni.setStorageSync('delivery', delivery)
				}
			} else {
				this.orderCreateData.delivery.store_id = 0;
				this.storeInfo.currStore = {};
			}
			// Object.assign(this.orderPaymentData, this.orderCreateData);
			this.orderCalculate();
			this.$forceUpdate();
			this.$refs['deliveryPopup'].close();
		},
		popupConfirm(ref) {
			this.$refs[ref].close();
			this.orderCalculate();
			this.$forceUpdate();
			this.tempData = null;
		},
		/**
		 * 使用余额
		 */
		useBalance() {
			if (this.orderCreateData.is_balance) this.orderCreateData.is_balance = 0;
			else this.orderCreateData.is_balance = 1;
			this.orderCalculate();
			this.$forceUpdate();
		},
		/**
		 * 设置支付密码
		 */
		setPayPassword() {
			this.$util.redirectTo('/pages_tool/member/pay_password', {
				back: '/pages_promotion/presale/payment'
			});
		},
		/**
		 * 暂不设置支付密码
		 */
		noSet() {
			this.orderCreateData.is_balance = 0;
			this.$refs.payPassword.close();
			this.orderCalculate();
			this.$forceUpdate();
		},
		/**
		 * 支付密码输入
		 */
		input(pay_password) {
			if (pay_password.length == 6) {
				uni.showLoading({
					title: '支付中...',
					mask: true
				})
				this.$api.sendRequest({
					url: '/api/member/checkpaypassword',
					data: {
						pay_password
					},
					success: res => {
						if (res.code >= 0) {
							if (this.finalPay) {
								this.finalPay.pay_password = pay_password;
								this.finalPay.member_id = this.memberInfo.member_id;
								this.finalPay.is_balance = this.finalPay.is_use_balance;
							} else {
								this.orderCreateData.pay_password = pay_password;
								this.orderCreate();
							}

						} else {
							uni.hideLoading();
							this.$util.showToast({
								title: res.message
							});
						}
					},
					fail: res => {
						uni.hideLoading();
					}
				})
			}
		},
		imageError(goodsIndex) {
			this.orderPaymentData.goods_list[goodsIndex].sku_image = this.$util.getDefaultImage()
				.goods_img;
			this.$forceUpdate();
		},
		navigateBack() {
			this.$util.goBack();
		},
		// 切换发票开关
		changeIsInvoice() {
			if (this.orderCreateData.is_invoice == 0) {
				this.orderCreateData.is_invoice = 1;
				if (!this.orderCreateData.invoice_type) this.orderCreateData.invoice_type = this.orderPaymentData.invoice.invoice_type.split(',')[0];
			} else {
				this.orderCreateData.is_invoice = 0;
			}
			this.orderCalculate();
			this.$forceUpdate();
		},
		// 切换发票类型
		changeInvoiceType(invoice_type) {
			this.orderCreateData.invoice_type = invoice_type;
			this.orderCalculate();
			this.$forceUpdate();
		},
		// 切换发票个人还是企业
		changeInvoiceTitleType(invoice_title_type) {
			this.orderCreateData.invoice_title_type = invoice_title_type;
			this.orderCalculate();
			this.$forceUpdate();
		},
		// 切换增值税专用发票开关
		changeIsTaxInvoice() {
			if (this.orderCreateData.is_tax_invoice == 0) this.orderCreateData.is_tax_invoice = 1;
			else this.orderCreateData.is_tax_invoice = 0;
			this.$forceUpdate();
		},
		// 选择发票内容
		changeInvoiceContent(invoice_content) {
			this.orderCreateData.invoice_content = invoice_content;
			this.$forceUpdate();
		},
		// 发票验证
		invoiceVerify() {
			if (!this.orderCreateData.invoice_title) {
				this.$refs.invoicePopup.open();
				this.$util.showToast({
					title: '请填写发票抬头'
				});
				return false;
			}
			if (!this.orderCreateData.taxpayer_number && this.orderCreateData.invoice_title_type == 2) {
				this.$refs.invoicePopup.open();
				this.$util.showToast({
					title: '请填写纳税人识别号'
				});
				return false;
			}
			if (this.orderCreateData.invoice_type == 1 && !this.orderCreateData.invoice_full_address && this.orderPaymentData.is_virtual == 1) {
				this.$refs.invoicePopup.open();
				this.$util.showToast({
					title: '请填写发票邮寄地址'
				});
				return false;
			}
			if (this.orderCreateData.invoice_type == 2 && !this.orderCreateData.invoice_email) {
				this.$refs.invoicePopup.open();
				this.$util.showToast({
					title: '请填写邮箱'
				});
				return false;
			}
			if (this.orderCreateData.invoice_type == 2) {
				var reg = /^([a-zA-Z]|[0-9])(\w|\-)+@[a-zA-Z0-9]+\.([a-zA-Z]{2,4})$/;
				if (!reg.test(this.orderCreateData.invoice_email)) {
					this.$refs.invoicePopup.open();
					this.$util.showToast({
						title: '请填写正确的邮箱'
					});
					return false;
				}
			}
			if (!this.orderCreateData.invoice_content) {
				this.$refs.invoicePopup.open();
				this.$util.showToast({
					title: '请选择发票内容'
				});
				return false;
			}
			return true;
		},
		// 保存发票信息
		saveInvoice() {
			if (this.orderCreateData.is_invoice == 1) {
				if (this.invoiceVerify()) {
					this.closePopup('invoicePopup');
				}
			} else {
				this.closePopup('invoicePopup');
			}
		},
		// 获取时间
		getTime() {
			// 必须是字符串,跟后端一致
			let weeks = ['0', '1', '2', '3', '4', '5', '6'];
			let week = new Date().getDay();
			this.timeInfo.week = weeks[week];
		},
		closeInvoicePopup() {
			// 发票
			// this.orderCreateData.is_invoice = 0;
			// this.orderCreateData.invoice_type = this.orderPaymentData.invoice.invoice_type[
			// 	0]; // 发票类型  1 纸质 2 电子
			// this.orderCreateData.invoice_title_type = 1; // 抬头类型  1 个人 2 企业
			// this.orderCreateData.is_tax_invoice = 0; // 是否需要增值税专用发票  0 不需要 1 需要
			// this.orderCreateData.invoice_title = ''; // 发票抬头
			// this.orderCreateData.taxpayer_number = ''; // 纳税人识别号
			// this.orderCreateData.invoice_content = ''; // 发票内容
			// this.orderCreateData.invoice_full_address = ''; // 发票邮寄地址
			// this.orderCreateData.invoice_email = ''; //发票邮箱
			// this.orderCalculate();
			// this.$forceUpdate();
			this.$refs.invoicePopup.close();
		},
		switchChange(e) {
			this.switch_state = e.detail.value;
		},
		navigateTo(e) {
			this.$util.redirectTo('/pages/goods/detail', {
				goods_id: e
			})
		},
		// 显示选择支付方式弹框
		openChoosePayment() {
			if (this.is_deposit_back == 1) {
				if (!this.switch_state) {
					this.$util.showToast({
						title: '预售商品定金不支持退款，请确定同意定金不退款协议。'
					})
					return false;
				} else {
					this.$refs.depositRefund.open();
				}
			} else {
				uni.setStorageSync('paySource', 'presale');
				console.log(111);
				if (this.verify()) {
					console.log(222);
					this.$refs.choosePaymentPopup.open();
					// #ifdef MP-WEIXIN
					switch (this.orderCreateData.delivery.delivery_type) {
						// ORDER_URGE_PAYMENT,订单催付
						// ORDER_PAY,订单支付
						// ORDER_DELIVERY //订单发货

						case 'express': //物流配送
							this.$util.subscribeMessage('ORDER_URGE_PAYMENT,ORDER_PAY,ORDER_DELIVERY');
							break;
						case 'store': //门店自提
							this.$util.subscribeMessage('ORDER_URGE_PAYMENT,ORDER_PAY');
							break;
						case 'local': //同城配送
							this.$util.subscribeMessage('ORDER_URGE_PAYMENT,ORDER_PAY,ORDER_DELIVERY');
							break;
						default:
							this.$util.subscribeMessage('ORDER_URGE_PAYMENT,ORDER_PAY');
							break;
					}
					// #endif
				}
			}
		},
		/**
		 * 微信订阅消息
		 */
		subscribeMessage() {
			this.$util.subscribeMessage('ORDER_PAY,ORDER_DELIVERY,ORDER_TAKE_DELIVERY');
		},
		toPayOrder() {
			uni.setStorageSync('paySource', 'presale');
			if (this.verify()) {
				this.$refs.depositRefund.close();
				this.$refs.choosePaymentPopup.open();
			}
		},
		closeDepositRefund() {
			this.$refs.depositRefund.close();
		},
		// 定金不退弹框打开
		presaleAgreement() {
			this.$refs.presaleAgreement.open();
		},
		closePresaleAgreement() {
			this.$refs.presaleAgreement.close();
		},
		saveBuyerMessage() {
			this.$refs.buyerMessagePopup.close();
		},
		back() {
			uni.navigateBack({
				delta: 1
			});
		}
	},
	onShow() {
		// 判断登录
		if (!this.storeToken) {
			this.$util.redirectTo('/pages_tool/login/index');
		} else {
			this.getOrderPaymentData();
		}

		//用户通过返回上一页返回时 如果订单已经创建则强制跳转到订单列表
		var orderCreateData = uni.getStorageSync('presaleOrderCreateData');
		if (orderCreateData && orderCreateData.out_trade_no) {
			this.$util.redirectTo('/pages_promotion/presale/order_list', {}, "redirectTo");
		}
		this.getTime();
		this.isIphoneX = this.$util.uniappIsIPhoneX()
	},
	onLoad(option) {
		if (option.id) {
			this.presale_id = option.id
		}
		if (!this.location) this.$util.getLocation();
		// #ifdef MP
		this.menuButtonBounding = uni.getMenuButtonBoundingClientRect();
		// #endif
	},
	onHide() {
		if (this.$refs.loadingCover) this.$refs.loadingCover.show();
	},
	computed: {
		// 余额抵扣
		balanceDeduct() {
			let balance = parseFloat(this.orderPaymentData.member_account.balance_total) < parseFloat(this.orderPaymentData.presale_deposit_money) ? parseFloat(this.orderPaymentData.member_account.balance_total) : parseFloat(this.orderPaymentData.presale_deposit_money);
			return balance.toFixed(2);
		},
		presaleDiscount() {
			return (parseFloat(this.orderPaymentData.presale_money) - parseFloat(this.orderPaymentData.presale_deposit_money)).toFixed(2);
		}
	},
	watch: {
		location: function(nVal) {
			if (nVal) {
				this.getOrderPaymentData();
			}
		}
	},
	filters: {
		/**
		 * 金额格式化输出
		 * @param {Object} money
		 */
		moneyFormat(money) {
			return parseFloat(money).toFixed(2);
		},
		/**
		 * 店铺优惠摘取
		 */
		promotion(data) {
			let promotion = '';
			if (data) {
				Object.keys(data).forEach((key) => {
					promotion += data[key].content + '　';
				})
			}
			return promotion;
		}
	}
}