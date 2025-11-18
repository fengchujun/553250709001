export default {
	data() {
		return {
			isIphoneX: false,
			orderCreateData: {
				buyer_message: '',
			},
			orderPaymentData: {},
			isSub: false,
			// 选择自提、配送防重判断
			judge: true,
			min: 1,
			editLock:false,
		};
	},
	methods: {
		/**
		 * 支付弹窗关闭
		 */
		payClose() {
			this.$util.redirectTo('/pages_promotion/giftcard/order_list', {}, 'redirectTo');
		},
		// 显示弹出层
		openPopup(ref) {
			this.$refs[ref].open();
		},
		// 关闭弹出层
		closePopup(ref) {
			this.$refs[ref].close();
		},
		// 获取订单初始化数据
		getOrderPaymentData() {
			this.orderCreateData = uni.getStorageSync('giftcardOrderCreateData');
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

			this.$api.sendRequest({
				url: '/giftcard/api/ordercreate/calculate',
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
		cartNumChange(num) {
			if(this.editLock) return;
			this.editLock = true;
			this.orderCreateData.num = num === '' ? 0 : num;
			Object.assign(this.orderPaymentData, this.orderCreateData);
			this.orderCalculate();
		},
		// 处理结算订单数据
		handlePaymentData() {
			this.orderCreateData.buyer_message = '';
			Object.assign(this.orderPaymentData, this.orderCreateData);
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
			this.$api.sendRequest({
				url: '/giftcard/api/ordercreate/calculate',
				data,
				success: res => {
					if (res.code >= 0) {
						this.orderPaymentData = res.data;
						this.createBtn();
						this.$forceUpdate();
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
					this.editLock = false;
				},
			})
		},
		/**
		 * 订单创建验证
		 */
		createBtn() {
			return true;
		},
		// 订单创建
		orderCreate() {
			if (this.verify()) {
				if (this.isSub) return;
				this.isSub = true;

				uni.setStorageSync('paySource', 'giftcard');

				var data = this.$util.deepClone(this.orderCreateData);

				this.$api.sendRequest({
					url: '/giftcard/api/ordercreate/create',
					data,
					success: res => {
						uni.hideLoading();
						if (res.code >= 0) {
							if (parseFloat(this.orderPaymentData.pay_money) > 0) {
								let orderCreateData = uni.getStorageSync('giftcardOrderCreateData');
								orderCreateData.out_trade_no = res.data;
								uni.setStorageSync('giftcardOrderCreateData', orderCreateData);

								this.$refs.choosePaymentPopup.getPayInfo(res.data);
								this.isSub = false;
							} else {
								this.$util.redirectTo('/pages_promotion/giftcard/order_list', {}, 'redirectTo');
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
			return true;
		},
		imageError(index) {
			this.orderPaymentData.order_goods_list[index].sku_image = this.$util.getDefaultImage().goods;
			this.$forceUpdate();
		},

		navigateTo(id) {
			this.$util.redirectTo('/pages_promotion/giftcard/detail', {
				id
			});
		},
		// 显示选择支付方式弹框
		openChoosePayment() {
			this.orderCreate();
		},
		/**
		 * 保存留言
		 */
		saveBuyerMessage() {
			this.orderCalculate();
			this.$refs.buyerMessagePopup.close();
		},
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

		this.isIphoneX = this.$util.uniappIsIPhoneX()
	},
	onHide() {
		if (this.$refs.loadingCover) this.$refs.loadingCover.show();
	},
	filters: {
		// 金额格式化输出
		moneyFormat(money) {
			return parseFloat(money).toFixed(2);
		}
	}
}
